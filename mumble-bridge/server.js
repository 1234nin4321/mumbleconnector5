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
const MY_ADDRESS = process.env.MY_ADDRESS || 'host.docker.internal:50052';
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

// For compatibility with SeAT PUT requests
app.put('/api/v1/servers/:id/users/:user_id', (req, res) => {
    const { name, display_name, password, groups } = req.body;
    userCache.set(name, {
        password: password,
        display_name: display_name || name,
        groups: groups || []
    });
    res.json({ success: true });
});

// --- gRPC setup ---
const packageDefinition = protoLoader.loadSync(PROTO_PATH, {
    keepCase: true, longs: String, enums: String, defaults: true, oneofs: true
});
const murmur = grpc.loadPackageDefinition(packageDefinition).murmur;

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
    }
});

// Start servers
app.listen(REST_PORT, '0.0.0.0', () => console.log(`REST API on ${REST_PORT}`));

authServer.bindAsync(`0.0.0.0:${AUTH_PORT}`, grpc.ServerCredentials.createInsecure(), () => {
    console.log(`Authenticator Service listening on ${AUTH_PORT}`);
    authServer.start();
    
    // THE BRAIN ACTIVATION: Tell Mumble to use us.
    setTimeout(registerWithMumble, 5000);
});

function registerWithMumble() {
    console.log(`Attempting to register Authenticator at ${MUMBLE_GRPC}...`);
    const client = new murmur.V1(MUMBLE_GRPC, grpc.credentials.createInsecure());
    
    client.AuthenticatorRegister({ address: MY_ADDRESS }, (err, response) => {
        if (err) {
            console.error('[Handshake] FAILED to register:', err.message);
            console.log('Retrying in 10 seconds...');
            setTimeout(registerWithMumble, 10000);
        } else {
            console.log('[Handshake] SUCCESS! The Bridge is now controlling Mumble access.');
        }
    });
}
