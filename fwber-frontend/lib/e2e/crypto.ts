/**
 * E2E Encryption - Cryptographic Primitives
 *
 * Uses Web Crypto API for ECDH key exchange and AES-GCM encryption.
 * Supports WASM offloading for high-performance media payloads.
 */

// WASM Bridge (Lazy loaded)
let wasmModule: any = null;

async function loadWasm() {
  if (wasmModule) return wasmModule;
  try {
    // @ts-ignore
    wasmModule = await import('@/lib/wasm/fwber_wasm');
    await wasmModule.default();
    console.log('E2E: WASM encryption acceleration active');
    return wasmModule;
  } catch (e) {
    console.debug('E2E: WASM acceleration not available, using WebCrypto fallback');
    return null;
  }
}

// Configuration for ECDH
const ECDH_ALGO = {
  name: 'ECDH',
  namedCurve: 'P-256',
};

// Configuration for AES-GCM (Shared Secret)
const AES_ALGO = {
  name: 'AES-GCM',
  length: 256,
};

/**
 * Generate a new ECDH key pair.
 */
export async function generateKeyPair(): Promise<CryptoKeyPair> {
  return window.crypto.subtle.generateKey(
    ECDH_ALGO,
    true, // extractable (need to export public key and store private key)
    ['deriveKey', 'deriveBits']
  ) as Promise<CryptoKeyPair>;
}

/**
 * Export public key to Base64 string (for API upload).
 */
export async function exportPublicKey(key: CryptoKey): Promise<string> {
  const exported = await window.crypto.subtle.exportKey('jwk', key);
  return btoa(JSON.stringify(exported));
}

/**
 * Import public key from Base64 string (from API).
 */
export async function importPublicKey(base64Key: string): Promise<CryptoKey> {
  try {
    const jwk = JSON.parse(atob(base64Key));
    return window.crypto.subtle.importKey(
      'jwk',
      jwk,
      ECDH_ALGO,
      true,
      []
    );
  } catch (e) {
    console.error('Failed to import public key', e);
    throw new Error('Invalid public key format');
  }
}

/**
 * Derive a shared AES-GCM key from own private key and peer's public key.
 */
export async function deriveSharedKey(
  privateKey: CryptoKey,
  publicKey: CryptoKey
): Promise<CryptoKey> {
  return window.crypto.subtle.deriveKey(
    {
      name: 'ECDH',
      public: publicKey,
    },
    privateKey,
    AES_ALGO,
    false, // Shared key doesn't need to be extractable
    ['encrypt', 'decrypt']
  );
}

/**
 * Import an RSA Public Key from PEM format (ActivityPub standard).
 */
export async function importRsaPublicKey(pem: string): Promise<CryptoKey> {
  const pemHeader = "-----BEGIN PUBLIC KEY-----";
  const pemFooter = "-----END PUBLIC KEY-----";
  const pemContents = pem.substring(
    pem.indexOf(pemHeader) + pemHeader.length,
    pem.indexOf(pemFooter)
  ).replace(/\s/g, '');
  
  const binaryDer = Uint8Array.from(atob(pemContents), c => c.charCodeAt(0));

  return window.crypto.subtle.importKey(
    'spki',
    binaryDer,
    {
      name: 'RSA-OAEP',
      hash: 'SHA-256',
    },
    true,
    ['encrypt']
  );
}

/**
 * Encrypt a message using an RSA Public Key.
 */
export async function encryptWithRsa(text: string, publicKey: CryptoKey): Promise<string> {
  const encoder = new TextEncoder();
  const data = encoder.encode(text);

  const ciphertext = await window.crypto.subtle.encrypt(
    {
      name: 'RSA-OAEP',
    },
    publicKey,
    data
  );

  return JSON.stringify({
    data: Array.from(new Uint8Array(ciphertext)),
    algo: 'RSA-OAEP',
  });
}

/**
 * Encrypt a message using the shared key.
 * Returns JSON string containing IV and Ciphertext.
 */
export async function encryptMessage(
  text: string,
  sharedKey: CryptoKey
): Promise<string> {
  // Attempt WASM offload for large payloads
  if (text.length > 5000) {
    const wasm = await loadWasm();
    if (wasm) {
      try {
        const rawKey = await window.crypto.subtle.exportKey('raw', sharedKey);
        const keyHex = Array.from(new Uint8Array(rawKey)).map(b => b.toString(16).padStart(2, '0')).join('');
        const result = wasm.encrypt_message(text, keyHex);
        return JSON.stringify({
          iv: result.nonce,
          data: result.ciphertext,
          wasm: true
        });
      } catch (e) {
        console.warn('WASM encryption failed, falling back to WebCrypto', e);
      }
    }
  }

  const iv = window.crypto.getRandomValues(new Uint8Array(12)); // 96-bit IV for AES-GCM
  const encoder = new TextEncoder();
  const data = encoder.encode(text);

  const ciphertext = await window.crypto.subtle.encrypt(
    {
      name: 'AES-GCM',
      iv: iv,
    },
    sharedKey,
    data as any
  );

  // Pack IV and Ciphertext into a portable format
  const payload = {
    iv: Array.from(iv),
    data: Array.from(new Uint8Array(ciphertext)),
  };

  return JSON.stringify(payload);
}

/**
 * Decrypt a message using the shared key.
 */
export async function decryptMessage(
  encryptedJson: string,
  sharedKey: CryptoKey
): Promise<string> {
  try {
    const payload = JSON.parse(encryptedJson);

    // Check if was encrypted via WASM
    if (payload.wasm) {
        const wasm = await loadWasm();
        if (wasm) {
            const rawKey = await window.crypto.subtle.exportKey('raw', sharedKey);
            const keyHex = Array.from(new Uint8Array(rawKey)).map(b => b.toString(16).padStart(2, '0')).join('');
            return wasm.decrypt_message(payload.data, payload.iv, keyHex);
        }
    }

    const iv = new Uint8Array(payload.iv);
    const data = new Uint8Array(payload.data);

    const decrypted = await window.crypto.subtle.decrypt(
      {
        name: 'AES-GCM',
        iv: iv,
      },
      sharedKey,
      data as any
    );

    const decoder = new TextDecoder();
    return decoder.decode(decrypted);
  } catch (e) {
    console.error('Decryption failed', e);
    throw new Error('Failed to decrypt message');
  }
}
