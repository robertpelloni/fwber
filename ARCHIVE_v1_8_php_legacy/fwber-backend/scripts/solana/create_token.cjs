const { Connection, Keypair, LAMPORTS_PER_SOL } = require('@solana/web3.js');
const { createMint, getOrCreateAssociatedTokenAccount, mintTo } = require('@solana/spl-token');
const fs = require('fs');
const bs58 = require('bs58');

async function main() {
    // Connect to Devnet
    const connection = new Connection('https://api.devnet.solana.com', 'confirmed');

    // Generate a new wallet for the Server
    const serverWallet = Keypair.generate();

    console.log("Requesting airdrop for fee payer...");
    try {
        const signature = await connection.requestAirdrop(serverWallet.publicKey, 0.5 * LAMPORTS_PER_SOL);
        await connection.confirmTransaction(signature);
        console.log("Airdrop confirmed.");
    } catch (e) {
        console.log("Airdrop failed (likely rate limit). Continuing with Key Generation only.");
        // We can't mint if we have no SOL, but we can print the keys so the user can fund it later.
        const secretKeyBase58 = bs58.default.encode(serverWallet.secretKey);
        console.log("\n--- SAVE THESE TO .ENV ---");
        console.log(`SOLANA_SERVER_SECRET_KEY="${secretKeyBase58}"`);
        console.log(`SOLANA_RPC_URL="https://api.devnet.solana.com"`);
        console.log(`SERVER_WALLET_PUBLIC_KEY="${serverWallet.publicKey.toBase58()}"`);
        console.log("--------------------------\n");
        console.log("NOTE: You must fund this wallet and run a mint script manually later.");
        process.exit(0);
    }

    console.log("Creating Mint...");
    const mint = await createMint(
        connection,
        serverWallet,          // Payer
        serverWallet.publicKey, // Mint Authority
        serverWallet.publicKey, // Freeze Authority
        9                       // Decimals
    );

    console.log("Mint Address:", mint.toBase58());

    // Get the ATA for the server wallet to hold the initial supply
    const serverTokenAccount = await getOrCreateAssociatedTokenAccount(
        connection,
        serverWallet,
        mint,
        serverWallet.publicKey
    );

    console.log("Server Token Account:", serverTokenAccount.address.toBase58());

    // Mint 1 Billion tokens
    // 1_000_000_000 * 10^9
    const amount = 1_000_000_000n * 1_000_000_000n;

    await mintTo(
        connection,
        serverWallet,
        mint,
        serverTokenAccount.address,
        serverWallet.publicKey,
        amount
    );

    console.log("Minted 1,000,000,000 FWB tokens to server wallet.");

    // Output keys
    const secretKeyBase58 = bs58.default.encode(serverWallet.secretKey);

    const output = {
        MINT_ADDRESS: mint.toBase58(),
        SERVER_WALLET_PUBLIC_KEY: serverWallet.publicKey.toBase58(),
        SERVER_WALLET_SECRET_KEY: secretKeyBase58
    };

    console.log("\n--- SAVE THESE TO .ENV ---");
    console.log(`SOLANA_MINT_ADDRESS="${output.MINT_ADDRESS}"`);
    console.log(`SOLANA_SERVER_SECRET_KEY="${output.SERVER_WALLET_SECRET_KEY}"`);
    console.log(`SOLANA_RPC_URL="https://api.devnet.solana.com"`);
    console.log("--------------------------\n");

    fs.writeFileSync('fwber-backend/solana-keys.json', JSON.stringify(output, null, 2));
}

main().catch(err => {
    console.error(err);
    process.exit(1);
});
