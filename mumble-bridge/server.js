const express = require('express');
const initSqlJs = require('sql.js');
const fs = require('fs');
const crypto = require('crypto');

const app = express();
app.use(express.json());

// --- Config ---
const DB_PATH = process.env.MUMBLE_DB || '/var/lib/mumble-server/mumble-server.sqlite';
const SERVER_ID = parseInt(process.env.MUMBLE_SERVER_ID || '1');
const BRIDGE_PORT = parseInt(process.env.BRIDGE_PORT || '8082');

// --- API Key Auth Setup ---
const path = require('path');
const KEY_FILE = path.join(__dirname, 'mumble-bridge.key');

let API_KEY = process.env.API_KEY || '';

if (!API_KEY) {
    if (fs.existsSync(KEY_FILE)) {
        API_KEY = fs.readFileSync(KEY_FILE, 'utf8').trim();
    } else {
        // Generate a cryptographically secure random 32-character key
        API_KEY = crypto.randomBytes(32).toString('hex');
        fs.writeFileSync(KEY_FILE, API_KEY, { mode: 0o600 });
        console.log(`\n=============================================================`);
        console.log(`[SECURITY] GENERATED NEW API KEY:`);
        console.log(`-> ${API_KEY}`);
        console.log(`\nThis key has been saved to: ${KEY_FILE}`);
        console.log(`Please copy this key into your SeAT plugin settings!`);
        console.log(`=============================================================\n`);
    }
}

// --- Auth Middleware ---
app.use((req, res, next) => {
    // Allow health checks without auth
    if (req.path === '/health') return next();

    const providedKey = req.header('x-api-key') || req.header('X-API-Key');
    if (providedKey !== API_KEY) {
        console.warn(`[AUTH FAILED] Unauthorized access attempt from ${req.ip} to ${req.path}`);
        return res.status(401).json({ error: 'Unauthorized: Invalid or missing API Key' });
    }
    next();
});

// --- DB helpers ---

async function openDb() {
    const SQL = await initSqlJs();
    const fileBuffer = fs.readFileSync(DB_PATH);
    return new SQL.Database(fileBuffer);
}

function saveDb(db) {
    const data = db.export();
    fs.writeFileSync(DB_PATH, Buffer.from(data));
    db.close();
}

// Use legacy plain SHA1 hash.
// Mumble accepts this (when kdfiterations=0) and automatically upgrades it 
// to PBKDF2 upon the user's first successful login. This is bulletproof.
function hashPassword(password) {
    if (!password) return { pw: '', salt: '', kdfiterations: 0 };
    const pw = crypto.createHash('sha1').update(password).digest('hex');
    return { pw, salt: '', kdfiterations: 0 };
}

// --- Routes ---

app.get('/health', async (req, res) => {
    try {
        const db = await openDb();
        const result = db.exec(`SELECT COUNT(*) FROM users WHERE server_id = ${SERVER_ID}`);
        db.close();
        res.json({ status: 'ok', db: DB_PATH, registered_users: result[0]?.values[0][0] ?? 0 });
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

app.get('/api/v1/servers/:id/stats', async (req, res) => {
    try {
        const db = await openDb();
        const result = db.exec(`SELECT COUNT(*) FROM users WHERE server_id = ${SERVER_ID}`);
        db.close();
        res.json({ name: 'Mumble Server', users: result[0]?.values[0][0] ?? 0 });
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

app.get('/api/v1/servers/:id/users', async (req, res) => {
    try {
        const db = await openDb();
        const filter = req.query.filter;
        let query = `SELECT user_id, name FROM users WHERE server_id = ${SERVER_ID} AND user_id > 0`;
        if (filter) {
            query += ` AND name = '${filter.replace(/'/g, "''")}'`;
        }
        const result = db.exec(query);
        db.close();
        const users = (result[0]?.values || []).map(row => ({ id: row[0], name: row[1] }));
        res.json(users);
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

app.post('/api/v1/servers/:id/users', async (req, res) => {
    try {
        const { name, password } = req.body;
        const db = await openDb();

        // Return existing user if already registered
        const existing = db.exec(
            `SELECT user_id FROM users WHERE server_id = ${SERVER_ID} AND name = '${name.replace(/'/g, "''")}'`
        );
        if (existing[0]?.values?.length) {
            const id = existing[0].values[0][0];
            db.close();
            return res.json({ id, name });
        }

        // Get next user_id
        const maxResult = db.exec(`SELECT COALESCE(MAX(user_id), 0) + 1 FROM users WHERE server_id = ${SERVER_ID}`);
        const nextId = maxResult[0].values[0][0];

        const { pw, salt, kdfiterations } = hashPassword(password);

        db.run(
            `INSERT INTO users (server_id, user_id, name, pw, salt, kdfiterations, lastchannel, last_active)
             VALUES (?, ?, ?, ?, ?, ?, 0, strftime('%s','now'))`,
            [SERVER_ID, nextId, name, pw, salt, kdfiterations]
        );
        saveDb(db);

        console.log(`Registered: [${nextId}] ${name}`);
        res.json({ id: nextId, name });
    } catch (e) {
        console.error('POST /users:', e.message);
        res.status(500).json({ error: e.message });
    }
});

app.put('/api/v1/servers/:id/users/:userId', async (req, res) => {
    try {
        const userId = parseInt(req.params.userId);
        const { name, password } = req.body;
        const db = await openDb();

        if (password) {
            const { pw, salt, kdfiterations } = hashPassword(password);
            db.run(
                `UPDATE users SET name = ?, pw = ?, salt = ?, kdfiterations = ? WHERE server_id = ? AND user_id = ?`,
                [name, pw, salt, kdfiterations, SERVER_ID, userId]
            );
        } else {
            db.run(
                `UPDATE users SET name = ? WHERE server_id = ? AND user_id = ?`,
                [name, SERVER_ID, userId]
            );
        }
        saveDb(db);

        console.log(`Updated: [${userId}] ${name}`);
        res.json({ success: true });
    } catch (e) {
        console.error('PUT /users:', e.message);
        res.status(500).json({ error: e.message });
    }
});

app.delete('/api/v1/servers/:id/users/:userId', async (req, res) => {
    try {
        const userId = parseInt(req.params.userId);
        const db = await openDb();
        db.run(`DELETE FROM users WHERE server_id = ? AND user_id = ?`, [SERVER_ID, userId]);
        saveDb(db);
        console.log(`Deregistered: [${userId}]`);
        res.json({ success: true });
    } catch (e) {
        console.error('DELETE /users:', e.message);
        res.status(500).json({ error: e.message });
    }
});

app.listen(BRIDGE_PORT, '0.0.0.0', () => {
    console.log(`Mumble SQLite bridge listening on port ${BRIDGE_PORT}`);
    console.log(`Database: ${DB_PATH}`);
});
