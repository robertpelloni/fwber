/**
 * Local Media Vault - Cryptographic Utilities
 *
 * Provides client-side encryption/decryption using Web Crypto API.
 * Uses AES-GCM with PBKDF2-derived keys for secure local storage.
 *
 * Security notes:
 * - Keys are derived from user passphrase with 100k PBKDF2 iterations
 * - Each encryption uses a unique IV (Initialization Vector)
 * - Salt is stored separately and generated per-device
 */

const PBKDF2_ITERATIONS = 100000;
const SALT_LENGTH = 16;
const IV_LENGTH = 12;
const KEY_LENGTH = 256;
const ALGORITHM = 'AES-GCM';

/**
 * Generate a cryptographically secure random salt
 */
export function generateSalt(): Uint8Array {
  return crypto.getRandomValues(new Uint8Array(SALT_LENGTH));
}

/**
 * Generate a random IV for AES-GCM
 */
function generateIV(): Uint8Array {
  return crypto.getRandomValues(new Uint8Array(IV_LENGTH));
}

/**
 * Derive an AES key from a passphrase using PBKDF2
 */
async function deriveKey(
  passphrase: string,
  salt: Uint8Array
): Promise<CryptoKey> {
  const encoder = new TextEncoder();
  const keyMaterial = await crypto.subtle.importKey(
    'raw',
    encoder.encode(passphrase) as any,
    'PBKDF2',
    false,
    ['deriveKey']
  );

  return crypto.subtle.deriveKey(
    {
      name: 'PBKDF2',
      salt: salt.buffer as ArrayBuffer,
      iterations: PBKDF2_ITERATIONS,
      hash: 'SHA-256',
    },
    keyMaterial,
    { name: ALGORITHM, length: KEY_LENGTH },
    false,
    ['encrypt', 'decrypt']
  );
}

/**
 * Encrypt data with the given passphrase
 * Returns a combined buffer: [IV (12 bytes) | ciphertext]
 */
export async function encryptData(
  data: ArrayBuffer,
  passphrase: string,
  salt: Uint8Array
): Promise<ArrayBuffer> {
  const key = await deriveKey(passphrase, salt);
  const iv = generateIV();

  const ciphertext = await crypto.subtle.encrypt(
    { name: ALGORITHM, iv: iv.buffer as ArrayBuffer },
    key,
    data
  );

  // Combine IV + ciphertext
  const combined = new Uint8Array(iv.length + ciphertext.byteLength);
  combined.set(iv, 0);
  combined.set(new Uint8Array(ciphertext), iv.length);

  return combined.buffer;
}

/**
 * Decrypt data that was encrypted with encryptData
 * Expects the combined buffer format: [IV (12 bytes) | ciphertext]
 */
export async function decryptData(
  encryptedData: ArrayBuffer,
  passphrase: string,
  salt: Uint8Array
): Promise<ArrayBuffer> {
  const key = await deriveKey(passphrase, salt);
  const combined = new Uint8Array(encryptedData);

  // Extract IV and ciphertext
  const iv = combined.slice(0, IV_LENGTH);
  const ciphertext = combined.slice(IV_LENGTH);

  return crypto.subtle.decrypt({ name: ALGORITHM, iv: iv.buffer as ArrayBuffer }, key, ciphertext);
}

/**
 * Encrypt a Blob (image/video file)
 */
export async function encryptBlob(
  blob: Blob,
  passphrase: string,
  salt: Uint8Array
): Promise<Blob> {
  const arrayBuffer = await blob.arrayBuffer();
  const encryptedBuffer = await encryptData(arrayBuffer, passphrase, salt);
  return new Blob([encryptedBuffer], { type: 'application/octet-stream' });
}

/**
 * Decrypt a Blob back to its original form
 */
export async function decryptBlob(
  encryptedBlob: Blob,
  passphrase: string,
  salt: Uint8Array,
  originalMimeType: string
): Promise<Blob> {
  const arrayBuffer = await encryptedBlob.arrayBuffer();
  const decryptedBuffer = await decryptData(arrayBuffer, passphrase, salt);
  return new Blob([decryptedBuffer], { type: originalMimeType });
}

/**
 * Convert Uint8Array to base64 string for storage
 */
export function arrayBufferToBase64(buffer: ArrayBuffer | Uint8Array): string {
  const bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
  let binary = '';
  for (let i = 0; i < bytes.byteLength; i++) {
    binary += String.fromCharCode(bytes[i]);
  }
  return btoa(binary);
}

/**
 * Convert base64 string back to Uint8Array
 */
export function base64ToArrayBuffer(base64: string): Uint8Array {
  const binary = atob(base64);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }
  return bytes;
}

/**
 * Check passphrase entropy (basic strength check)
 * Returns a score from 0-100
 */
export function checkPassphraseStrength(passphrase: string): {
  score: number;
  feedback: string[];
} {
  const feedback: string[] = [];
  let score = 0;

  // Length checks
  if (passphrase.length >= 8) score += 20;
  if (passphrase.length >= 12) score += 15;
  if (passphrase.length >= 16) score += 10;
  if (passphrase.length < 8) {
    feedback.push('Use at least 8 characters');
  }

  // Character variety checks
  if (/[a-z]/.test(passphrase)) score += 10;
  if (/[A-Z]/.test(passphrase)) score += 10;
  if (/[0-9]/.test(passphrase)) score += 10;
  if (/[^a-zA-Z0-9]/.test(passphrase)) score += 15;

  if (!/[A-Z]/.test(passphrase) && !/[0-9]/.test(passphrase)) {
    feedback.push('Add uppercase letters or numbers');
  }
  if (!/[^a-zA-Z0-9]/.test(passphrase)) {
    feedback.push('Add special characters for extra security');
  }

  // Penalize common patterns
  if (/^[a-z]+$/i.test(passphrase)) {
    score -= 10;
    feedback.push('Avoid using only letters');
  }
  if (/^[0-9]+$/.test(passphrase)) {
    score -= 20;
    feedback.push('Avoid using only numbers');
  }

  // Bonus for word-like patterns (passphrase style)
  if (passphrase.split(/\s+/).length >= 3) {
    score += 10;
  }

  return {
    score: Math.max(0, Math.min(100, score)),
    feedback,
  };
}

/**
 * Verify a passphrase against stored verification data
 * Used to check if the user entered the correct passphrase
 */
export async function createPassphraseVerifier(
  passphrase: string,
  salt: Uint8Array
): Promise<string> {
  const encoder = new TextEncoder();
  const verificationData = 'VAULT_VERIFY_V1';
  const encrypted = await encryptData(
    encoder.encode(verificationData).buffer,
    passphrase,
    salt
  );
  return arrayBufferToBase64(encrypted);
}

/**
 * Verify a passphrase matches the stored verifier
 */
export async function verifyPassphrase(
  passphrase: string,
  salt: Uint8Array,
  verifier: string
): Promise<boolean> {
  try {
    const encrypted = base64ToArrayBuffer(verifier);
    const decrypted = await decryptData(encrypted.buffer as ArrayBuffer, passphrase, salt);
    const decoder = new TextDecoder();
    return decoder.decode(decrypted) === 'VAULT_VERIFY_V1';
  } catch {
    return false;
  }
}
