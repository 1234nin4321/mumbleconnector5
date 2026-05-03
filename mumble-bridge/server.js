const express = require('express');
const fs = require('fs');
const path = require('path');
const initSqlJs = require('sql.js');
const crypto = require('crypto');
const Ice = require('ice').Ice;
const Murmur = require('./murmur.js').Murmur; // We will generate this or use dynamic loading

const app = express();
app.use(express.json());

const PORT = process.env.BRIDGE_PORT || 8082;
const DB_PATH = process.env.MUMBLE_DB || '/var/lib/mumble-server/mumble-server.sqlite';
const KEY_FILE = path.join(__dirname, 'mumble-bridge.key');
const ICE_HOST = process.env.MUMBLE_ICE_HOST || 'mumble-server';
const ICE_PORT = process.env.MUMBLE_ICE_PORT || 6502;
const BRIDGE_HOST = process.env.BRIDGE_HOST || 'mumble-bridge';

let API_KEY = process.env.MUMBLE_REST_KEY;
if (!API_KEY) {
    if (fs.existsSync(KEY_FILE)) {
        API_KEY = fs.readFileSync(KEY_FILE, 'utf8').trim();
    } else {
        API_KEY = crypto.randomBytes(32).toString('hex');
        fs.writeFileSync(KEY_FILE, API_KEY, { mode: 0o600 });
    }
}

// --- Ice Authenticator Implementation ---
class Authenticator extends Murmur.ServerAuthenticator {
    async authenticate(name, pw, certificates, certhash, certstrong, currentContext) {
        console.log(`[Ice] Auth request for: ${name}`);
        
        try {
            const SQL = await initSqlJs();
            const filebuffer = fs.readFileSync(DB_PATH);
            const db = new SQL.Database(filebuffer);

            const res = db.exec("SELECT user_id, pw FROM users WHERE name = ?", [name]);
            db.close();

            if (res.length > 0 && res[0].values[0][1] === pw) {
                console.log(`[Ice] Auth SUCCESS: ${name}`);
                return {
                    returnValue: res[0].values[0][0], // UserID
                    newname: name,
                    groups: []
                };
            }
        } catch (err) {
            console.error(`[Ice] Auth Error: ${err.message}`);
        }

        console.log(`[Ice] Auth FAILED: ${name}`);
        return { returnValue: -1, newname: "", groups: [] };
    }

    async getInfo(id, currentContext) { return { returnValue: false, info: {} }; }
    async nameToId(name, currentContext) { return -2; }
    async idToName(id, currentContext) { return ""; }
    async idToTexture(id, currentContext) { return []; }
}

async function startIce() {
    let communicator;
    try {
        console.log(`[Ice] Initializing connection to ${ICE_HOST}:${ICE_PORT}...`);
        communicator = Ice.initialize();
        
        const proxy = communicator.stringToProxy(`Meta:tcp -h ${ICE_HOST} -p ${ICE_PORT}`);
        const meta = await Murmur.MetaPrx.checkedCast(proxy);
        
        if (!meta) throw new Error("Invalid Meta proxy");

        // Create adapter for callbacks from Murmur to us
        const adapter = await communicator.createObjectAdapterWithEndpoints("AuthenticatorAdapter", `tcp -h 0.0.0.0 -p 10000`);
        const servant = new Authenticator();
        const authPrx = Murmur.ServerAuthenticatorPrx.uncheckedCast(adapter.addWithUUID(servant));
        await adapter.activate();

        const servers = await meta.getBootedServers();
        for (const server of servers) {
            await server.setAuthenticator(authPrx);
            console.log(`[Ice] Authenticator registered for server ID: ${await server.id()}`);
        }

        console.log("[Ice] System Ready.");
    } catch (err) {
        console.error(`[Ice] Error: ${err.message}`);
        if (communicator) await communicator.destroy();
        setTimeout(startIce, 10000);
    }
}

// Note: In Node.js with Ice, you usually need to pre-compile the .ice file
// or use a helper that does it. Since I cannot run slice2js easily here,
// I will assume the user has a way to generate murmur.js or I will provide 
// a version that uses the dynamic loader if possible.
// For now, I'll use the common 'ice' package pattern.

startIce();

// --- REST API Endpoints ---
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

        const resUser = db.exec("SELECT user_id FROM users WHERE name = ?", [mumbleName]);
        
        if (resUser.length > 0) {
            db.run("UPDATE users SET pw = ? WHERE name = ?", [password, mumbleName]);
        } else {
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
