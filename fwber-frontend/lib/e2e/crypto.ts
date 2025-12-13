/**
 * E2E Encryption - Cryptographic Primitives
 *
 * Uses Web Crypto API for ECDH key exchange and AES-GCM encryption.
 */

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
 * Encrypt a message using the shared key.
 * Returns JSON string containing IV and Ciphertext.
 */
export async function encryptMessage(
  text: string,
  sharedKey: CryptoKey
): Promise<string> {
  const iv = window.crypto.getRandomValues(new Uint8Array(12)); // 96-bit IV for AES-GCM
  const encoder = new TextEncoder();
  const data = encoder.encode(text);

  const ciphertext = await window.crypto.subtle.encrypt(
    {
      name: 'AES-GCM',
      iv: iv,
    },
    sharedKey,
    data
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
    const iv = new Uint8Array(payload.iv);
    const data = new Uint8Array(payload.data);

    const decrypted = await window.crypto.subtle.decrypt(
      {
        name: 'AES-GCM',
        iv: iv,
      },
      sharedKey,
      data
    );

    const decoder = new TextDecoder();
    return decoder.decode(decrypted);
  } catch (e) {
    console.error('Decryption failed', e);
    throw new Error('Failed to decrypt message');
  }
}
