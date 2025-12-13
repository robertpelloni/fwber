/**
 * E2E Encryption - IndexedDB Storage Layer
 *
 * Stores the user's private key securely in the browser.
 * The private key never leaves the device.
 */

const DB_NAME = 'fwber_e2e_keys';
const DB_VERSION = 1;
const STORE_KEYS = 'keys'; // Stores the user's own key pair
const STORE_SESSIONS = 'sessions'; // Stores derived shared secrets for conversations (optional optimization)

export interface KeyPairStorage {
  userId: number;
  publicKey: JsonWebKey;
  privateKey: JsonWebKey; // Stored as non-extractable CryptoKey if possible, but IDB needs serializable. JWK is fine if IDB is secure.
  createdAt: number;
}

let dbInstance: IDBDatabase | null = null;

async function openDatabase(): Promise<IDBDatabase> {
  if (dbInstance) return dbInstance;

  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onerror = () => reject(new Error('Failed to open E2E database'));

    request.onsuccess = () => {
      dbInstance = request.result;
      resolve(request.result);
    };

    request.onupgradeneeded = (event) => {
      const db = (event.target as IDBOpenDBRequest).result;

      if (!db.objectStoreNames.contains(STORE_KEYS)) {
        db.createObjectStore(STORE_KEYS, { keyPath: 'userId' });
      }
      
      if (!db.objectStoreNames.contains(STORE_SESSIONS)) {
        db.createObjectStore(STORE_SESSIONS, { keyPath: 'sessionId' });
      }
    };
  });
}

export async function storeKeyPair(userId: number, keyPair: CryptoKeyPair): Promise<void> {
  const db = await openDatabase();
  
  // Export keys to JWK for storage
  const publicKeyJwk = await window.crypto.subtle.exportKey('jwk', keyPair.publicKey);
  const privateKeyJwk = await window.crypto.subtle.exportKey('jwk', keyPair.privateKey);

  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_KEYS, 'readwrite');
    const store = transaction.objectStore(STORE_KEYS);
    const request = store.put({
      userId,
      publicKey: publicKeyJwk,
      privateKey: privateKeyJwk,
      createdAt: Date.now(),
    });

    request.onerror = () => reject(new Error('Failed to store key pair'));
    request.onsuccess = () => resolve();
  });
}

export async function getKeyPair(userId: number): Promise<CryptoKeyPair | null> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_KEYS, 'readonly');
    const store = transaction.objectStore(STORE_KEYS);
    const request = store.get(userId);

    request.onerror = () => reject(new Error('Failed to retrieve key pair'));
    request.onsuccess = async () => {
      const result = request.result as KeyPairStorage;
      if (!result) {
        resolve(null);
        return;
      }

      try {
        // Import keys back to CryptoKey objects
        const publicKey = await window.crypto.subtle.importKey(
          'jwk',
          result.publicKey,
          { name: 'ECDH', namedCurve: 'P-256' },
          true,
          []
        );

        const privateKey = await window.crypto.subtle.importKey(
          'jwk',
          result.privateKey,
          { name: 'ECDH', namedCurve: 'P-256' },
          true, // Private key must be extractable to be stored/retrieved this way, or we use non-extractable and store CryptoKey directly if IDB supports it (modern browsers do)
          ['deriveKey', 'deriveBits']
        );

        resolve({ publicKey, privateKey });
      } catch (e) {
        reject(e);
      }
    };
  });
}

export async function clearKeys(): Promise<void> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_KEYS, 'readwrite');
    transaction.objectStore(STORE_KEYS).clear();
    transaction.oncomplete = () => resolve();
    transaction.onerror = () => reject(new Error('Failed to clear keys'));
  });
}
