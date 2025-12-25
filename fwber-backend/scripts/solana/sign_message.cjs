const nacl = require('tweetnacl');
const bs58 = require('bs58');

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
