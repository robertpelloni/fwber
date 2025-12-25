const nacl = require('tweetnacl');
const bs58 = require('bs58');

const [,, message, signature, publicKey] = process.argv;

try {
    const messageBytes = new TextEncoder().encode(message);
    const signatureBytes = bs58.decode(signature);
    const publicKeyBytes = bs58.decode(publicKey);

    const verified = nacl.sign.detached.verify(messageBytes, signatureBytes, publicKeyBytes);

    if (verified) {
        console.log(JSON.stringify({ verified: true }));
        process.exit(0);
    } else {
        console.error(JSON.stringify({ verified: false, error: 'Signature verification failed' }));
        process.exit(1);
    }
} catch (error) {
    console.error(JSON.stringify({ verified: false, error: error.message }));
    process.exit(1);
}
