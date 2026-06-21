const crypto = require('crypto');

const questId = 1;
const userId = 1;
const secret = 'super-secret-123';

const expectedProof = crypto
  .createHash('sha256')
  .update(`${questId}${userId}${secret}`)
  .digest('hex');

console.log('Backend standard:', expectedProof);

// Simulate subtle crypto logic for the browser
async function runSubtle() {
    const { webcrypto } = require('crypto');
    const msgBuffer = new TextEncoder().encode(`${questId}${userId}${secret}`);
    const hashBuffer = await webcrypto.subtle.digest('SHA-256', msgBuffer);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    console.log('Frontend subtle :', hashArray.map(b => b.toString(16).padStart(2, '0')).join(''));
}
runSubtle();
