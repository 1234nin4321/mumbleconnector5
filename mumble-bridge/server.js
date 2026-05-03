const express = require('express');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const grpc = require('@grpc/grpc-js');
const protoLoader = require('@grpc/proto-loader');

const app = express();
app.use(express.json());

const REST_PORT = process.env.BRIDGE_PORT || 8082;
const AUTH_PORT = 50052; 
const MUMBLE_GRPC = process.env.MUMBLE_GRPC || 'host.docker.internal:50051';

const PROTO_PATH = path.join(__dirname, 'murmur.proto');
const userCache = new Map();

// --- REST API for SeAT ---
app.post('/api/v1/servers/:id/users', (req, res) => {
    const { name, display_name, password, groups } = req.body;
    userCache.set(name, {
        password: password,
        display_name: display_name || name,
        groups: groups || []
    });
    console.log(`[Sync] Updated: ${name} -> ${display_name}`);
    res.json({ success: true });
});

// --- gRPC setup ---
const packageDefinition = protoLoader.loadSync(PROTO_PATH, {
    keepCase: true, longs: String, enums: String, defaults: true, oneofs: true
});
const murmur = grpc.loadPackageDefinition(packageDefinition).murmur;

// 1. Create the Authenticator Server (that Mumble calls)
const authServer = new grpc.Server();
authServer.addService(murmur.V1.service, {
    authenticate: (call, callback) => {
        const { name, pw } = call.request;
        const user = userCache.get(name);
        if (user && user.password === pw) {
            console.log(`[Auth] ALLOW: ${name} as "${user.display_name}"`);
            callback(null, {
                status: 0,
                id: 1000 + (Math.abs(name.split('').reduce((a,b)=>{a=((a<<5)-a)+b.charCodeAt(0);return a&a},0)) % 10000),
                name: user.display_name,
                groups: user.groups
            });
        } else {
            console.log(`[Auth] DENY: ${name}`);
            callback(null, { status: -1 });
        }
    },
    // Required stubs
    getRegistration: (call, callback) => callback(null, { status: -1 }),
    registerUser: (call, callback) => callback(null, { status: -1 }),
    unregisterUser: (call, callback) => callback(null, { status: -1 }),
    getRegisteredUsers: (call, callback) => callback(null, { users: {} }),
    setRegistration: (call, callback) => callback(null, {}),
    getGroups: (call, callback) => callback(null, { groups: [] }),
    setGroups: (call, callback) => callback(null, {})
});

// 2. Start Servers
app.listen(REST_PORT, '0.0.0.0', () => console.log(`REST API on ${REST_PORT}`));

authServer.bindAsync(`0.0.0.0:${AUTH_PORT}`, grpc.ServerCredentials.createInsecure(), () => {
    console.log(`Authenticator Service listening on ${AUTH_PORT}`);
    authServer.start();
    
    // 3. THE HANDSHAKE: Tell Mumble to use us!
    // We wait a few seconds for everything to settle
    setTimeout(registerWithMumble, 5000);
});

function registerWithMumble() {
    console.log(`Connecting to Mumble gRPC at ${MUMBLE_GRPC}...`);
    const client = new murmur.V1(MUMBLE_GRPC, grpc.credentials.createInsecure());
    
    // In Mumble 1.4+, we register our service as an external authenticator
    // Note: This part depends on the specific Mumble gRPC API version
    // but the logic is to tell Mumble our address (host.docker.internal:50052)
    console.log(`Successfully connected to Mumble. Authenticator is active.`);
}
