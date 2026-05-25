const { Connection, Keypair, PublicKey } = require('@solana/web3.js');
const { getAssociatedTokenAddress } = require('@solana/spl-token');
const bs58 = require('bs58');
require('dotenv').config();

async function main() {
    const signature = process.argv[2];
    const expectedAmountStr = process.argv[3]; // Amount in whole tokens

    if (!signature || !expectedAmountStr) {
        console.error("Usage: node verify_transaction.cjs <signature> <amount>");
        process.exit(1);
    }

    const secretKeyString = process.env.SOLANA_SERVER_SECRET_KEY || process.env.SERVER_WALLET_SECRET;
    const mintAddressString = process.env.SOLANA_MINT_ADDRESS || process.env.MINT_ADDRESS;
    const rpcUrl = process.env.SOLANA_RPC_URL || 'https://api.devnet.solana.com';

    if (!secretKeyString || !mintAddressString) {
        console.error("Missing env vars: SERVER_WALLET_SECRET or MINT_ADDRESS");
        process.exit(1);
    }

    // MOCK MODE
    if (mintAddressString.includes('Mock') || signature.startsWith('mock_')) {
        console.log(`VERIFIED: ${expectedAmountStr}`);
        process.exit(0);
    }

    try {
        const connection = new Connection(rpcUrl, 'confirmed');
        const serverWallet = Keypair.fromSecretKey(bs58.default.decode(secretKeyString));
        const mint = new PublicKey(mintAddressString);

        // Derive Server ATA
        const serverATA = await getAssociatedTokenAddress(mint, serverWallet.publicKey);

        // Fetch Transaction
        const tx = await connection.getParsedTransaction(signature, { maxSupportedTransactionVersion: 0 });

        if (!tx) {
            console.error("Transaction not found");
            process.exit(1);
        }

        if (tx.meta.err) {
            console.error("Transaction failed on-chain");
            process.exit(1);
        }

        // Expected amount in lamports (9 decimals)
        const expectedRawAmount = Math.floor(parseFloat(expectedAmountStr) * 1_000_000_000);

        // Find transfer to our wallet
        // Look in preTokenBalances and postTokenBalances
        // Find index of our ATA
        const accountKeys = tx.transaction.message.accountKeys.map(k => k.pubkey.toBase58());
        const ataIndex = accountKeys.indexOf(serverATA.toBase58());

        let preBalance = 0;
        let postBalance = 0;

        // Check pre balances
        const pre = tx.meta.preTokenBalances.find(b => b.mint === mintAddressString && b.owner === serverWallet.publicKey.toBase58());
        if (pre) preBalance = parseInt(pre.uiTokenAmount.amount);

        // Check post balances
        const post = tx.meta.postTokenBalances.find(b => b.mint === mintAddressString && b.owner === serverWallet.publicKey.toBase58());
        if (post) postBalance = parseInt(post.uiTokenAmount.amount);

        const delta = postBalance - preBalance;

        // Allow small tolerance? No, exact match.
        // Assuming user sends exact amount.
        // Tolerance for float issues: check if delta matches expectedRawAmount
        if (delta >= expectedRawAmount) {
             console.log(`VERIFIED: ${delta / 1_000_000_000}`);
             process.exit(0);
        } else {
             console.error(`Amount mismatch. Received: ${delta}, Expected: ${expectedRawAmount}`);
             process.exit(1);
        }

    } catch (error) {
        console.error("Verification Failed:", error.message);
        process.exit(1);
    }
}

main();
