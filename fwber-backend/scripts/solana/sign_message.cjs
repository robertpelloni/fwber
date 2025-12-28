const path = require('path');

let nacl;
try {
    nacl = require('tweetnacl');
} catch (e) {
    // Try to find it in the project root node_modules
    try {
        nacl = require(path.resolve(__dirname, '../../node_modules/tweetnacl'));
    } catch (e2) {
        console.error("Error: Cannot find module 'tweetnacl'. Ensure npm install has been run in fwber-backend.");
        process.exit(1);
    }
}

let bs58;
try {
    bs58 = require('bs58').default || require('bs58');
} catch (e) {
    try {
        const bs58Module = require(path.resolve(__dirname, '../../node_modules/bs58'));
        bs58 = bs58Module.default || bs58Module;
    } catch (e2) {
        console.error("Error: Cannot find module 'bs58'. Ensure npm install has been run in fwber-backend.");
        process.exit(1);
    }
}

// Generate a random keypair
const keypair = nacl.sign.keyPair();
const publicKey = bs58.encode(keypair.publicKey);
const secretKey = bs58.encode(keypair.secretKey);

const message = process.argv[2] || "Test Message";
const messageBytes = new TextEncoder().encode(message);

const signature = nacl.sign.detached(messageBytes, keypair.secretKey);
const signatureEncoded = bs58.encode(signature);

console.log(JSON.stringify({
    wallet_address: publicKey,
    signature: signatureEncoded,
    message: message,
    secret_key: secretKey // useful if we needed it
}));
