const express = require('express');
const fs = require('fs');
const path = require('path');
const initSqlJs = require('sql.js');
const crypto = require('crypto');

const app = express();
app.use(express.json());

const PORT = process.env.BRIDGE_PORT || 8082;
const DB_PATH = process.env.MUMBLE_DB || '/var/lib/mumble-server/mumble-server.sqlite';
const KEY_FILE = path.join(__dirname, 'mumble-bridge.key');

let API_KEY = process.env.MUMBLE_REST_KEY;
if (!API_KEY) {
    if (fs.existsSync(KEY_FILE)) {
        API_KEY = fs.readFileSync(KEY_FILE, 'utf8').trim();
    } else {
        API_KEY = crypto.randomBytes(32).toString('hex');
        fs.writeFileSync(KEY_FILE, API_KEY, { mode: 0o600 });
    }
}

const auth = (req, res, next) => {
    const key = req.header('X-API-Key');
    if (!key || key !== API_KEY) return res.status(401).json({ error: 'Unauthorized' });
    next();
};

app.get('/api/v1/health', (req, res) => res.json({ status: 'ok', database: fs.existsSync(DB_PATH) }));

app.post('/api/v1/servers/:id/users', auth, async (req, res) => {
    const { name, display_name, password } = req.body;
    const mumbleName = display_name || name;
    
    try {
        const SQL = await initSqlJs();
        const filebuffer = fs.readFileSync(DB_PATH);
        const db = new SQL.Database(filebuffer);

        // Check if user exists
        const resUser = db.exec("SELECT user_id FROM users WHERE name = ?", [mumbleName]);
        
        if (resUser.length > 0) {
            // Update
            db.run("UPDATE users SET pw = ? WHERE name = ?", [password, mumbleName]);
        } else {
            // Insert
            db.run("INSERT INTO users (server_id, name, pw, lastactive) VALUES (1, ?, ?, datetime('now'))", [mumbleName, password]);
        }

        const data = db.export();
        fs.writeFileSync(DB_PATH, Buffer.from(data));
        db.close();

        console.log(`[Sync] Updated Mumble user: ${mumbleName}`);
        res.json({ success: true });
    } catch (err) {
        console.error(err);
        res.status(500).json({ error: err.message });
    }
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Mumble SQLite bridge listening on port ${PORT}`);
    console.log(`Database: ${DB_PATH}`);
});
