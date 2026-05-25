# fwber WASM Binaries

This directory is intended for compiled WebAssembly binaries from the `fwber-wasm` Rust package.

## Build Instructions
1. Navigate to the root `fwber-wasm` directory.
2. Run `wasm-pack build --target web --out-dir ../fwber-frontend/lib/wasm`.
3. The `fwber-frontend/lib/e2e/crypto.ts` file is already configured to lazy-load from this location.

## Current Primitives
- `encrypt_message(text, key_hex)`: High-performance AES-GCM encryption.
- `decrypt_message(ciphertext, nonce, key_hex)`: High-performance AES-GCM decryption.
