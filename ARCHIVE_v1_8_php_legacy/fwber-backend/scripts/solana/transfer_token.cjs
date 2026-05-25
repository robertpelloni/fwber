const { Connection, Keypair, PublicKey } = require('@solana/web3.js');
const { getOrCreateAssociatedTokenAccount, transfer } = require('@solana/spl-token');
const bs58 = require('bs58');
require('dotenv').config();

async function main() {
    const destAddress = process.argv[2];
    const amountStr = process.argv[3]; // Amount in whole tokens

    if (!destAddress || !amountStr) {
        console.error("Usage: node transfer_token.cjs <dest_address> <amount>");
        process.exit(1);
    }

    const secretKeyString = process.env.SOLANA_SERVER_SECRET_KEY || process.env.SERVER_WALLET_SECRET;
    const mintAddressString = process.env.SOLANA_MINT_ADDRESS || process.env.MINT_ADDRESS;
    const rpcUrl = process.env.SOLANA_RPC_URL || 'https://api.devnet.solana.com';

    if (!secretKeyString || !mintAddressString) {
        console.error("Missing env vars: SERVER_WALLET_SECRET or MINT_ADDRESS");
        process.exit(1);
    }

    // MOCK MODE CHECK
    if (mintAddressString.includes('Mock')) {
        console.log(`MOCK_MODE: Transferred ${amountStr} FWB to ${destAddress}`);
        console.log("TX_SIGNATURE: mock_tx_signature_" + Date.now());
        process.exit(0);
    }

    // REAL MODE
    try {
        const connection = new Connection(rpcUrl, 'confirmed');
        const serverWallet = Keypair.fromSecretKey(bs58.default.decode(secretKeyString));
        const mint = new PublicKey(mintAddressString);
        const destination = new PublicKey(destAddress);

        // Get Server ATA
        const serverATA = await getOrCreateAssociatedTokenAccount(
            connection,
            serverWallet,
            mint,
            serverWallet.publicKey
        );

        // Get Dest ATA
        const destATA = await getOrCreateAssociatedTokenAccount(
            connection,
            serverWallet, // Server pays for account creation (Trojan Horse user experience)
            mint,
            destination
        );

        // Calculate Amount (assuming 9 decimals)
        const amount = BigInt(Math.floor(parseFloat(amountStr) * 1_000_000_000));

        const signature = await transfer(
            connection,
            serverWallet,
            serverATA.address,
            destATA.address,
            serverWallet.publicKey,
            amount
        );

        console.log(`Transferred ${amountStr} FWB to ${destAddress}`);
        console.log(`TX_SIGNATURE: ${signature}`);

    } catch (error) {
        console.error("Transfer Failed:", error.message);
        process.exit(1);
    }
}

main();
