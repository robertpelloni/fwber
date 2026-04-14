const { Keypair } = require('@solana/web3.js');
const bs58 = require('bs58');
require('dotenv').config();

const secretKeyString = process.env.SOLANA_SERVER_SECRET_KEY || process.env.SERVER_WALLET_SECRET;

if (!secretKeyString) {
    console.error("Missing env vars");
    process.exit(1);
}

try {
    const serverWallet = Keypair.fromSecretKey(bs58.default.decode(secretKeyString));
    console.log(serverWallet.publicKey.toBase58());
} catch (e) {
    console.error(e.message);
    process.exit(1);
}
