const initSqlJs = require('sql.js');
const fs = require('fs');

const DB_PATH = process.env.MUMBLE_DB || 'C:\\Program Files\\Mumble\\server\\mumble-server.sqlite';

initSqlJs().then(SQL => {
    const db = new SQL.Database(fs.readFileSync(DB_PATH));
    const tables = db.exec("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    console.log('Tables:', JSON.stringify(tables[0]?.values.flat()));

    // Also show first few rows from any table that might be users
    const allTables = tables[0]?.values.flat() || [];
    for (const table of allTables) {
        try {
            const rows = db.exec(`SELECT * FROM ${table} LIMIT 2`);
            console.log(`\n[${table}] columns:`, rows[0]?.columns);
        } catch (e) { }
    }
    db.close();
});
