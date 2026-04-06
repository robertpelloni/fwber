/**
 * E2E Encryption - IndexedDB Storage Layer
 *
 * Stores the user's private key securely in the browser.
 * The private key never leaves the device.
 * Supports multiple key types (ECDH for local, RSA for federated).
 */

const DB_NAME = 'fwber_e2e_keys';
const DB_VERSION = 2;
const STORE_KEYS = 'keys'; 
const STORE_SESSIONS = 'sessions';

export interface KeyPairStorage {
  id: string; // userId:keyType
  userId: number;
  keyType: 'ecdh' | 'rsa';
  publicKey: JsonWebKey;
  privateKey: JsonWebKey; 
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
        db.createObjectStore(STORE_KEYS, { keyPath: 'id' });
      }
      
      if (!db.objectStoreNames.contains(STORE_SESSIONS)) {
        db.createObjectStore(STORE_SESSIONS, { keyPath: 'sessionId' });
      }
    };
  });
}

export async function storeKeyPair(userId: number, keyPair: CryptoKeyPair, keyType: 'ecdh' | 'rsa' = 'ecdh'): Promise<void> {
  const db = await openDatabase();
  
  // Export keys to JWK for storage
  const publicKeyJwk = await window.crypto.subtle.exportKey('jwk', keyPair.publicKey);
  const privateKeyJwk = await window.crypto.subtle.exportKey('jwk', keyPair.privateKey);

  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_KEYS, 'readwrite');
    const store = transaction.objectStore(STORE_KEYS);
    const request = store.put({
      id: `${userId}:${keyType}`,
      userId,
      keyType,
      publicKey: publicKeyJwk,
      privateKey: privateKeyJwk,
      createdAt: Date.now(),
    });

    request.onerror = () => reject(new Error('Failed to store key pair'));
    request.onsuccess = () => resolve();
  });
}

export async function getKeyPair(userId: number, keyType: 'ecdh' | 'rsa' = 'ecdh'): Promise<CryptoKeyPair | null> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_KEYS, 'readonly');
    const store = transaction.objectStore(STORE_KEYS);
    const request = store.get(`${userId}:${keyType}`);

    request.onerror = () => reject(new Error('Failed to retrieve key pair'));
    request.onsuccess = async () => {
      const result = request.result as KeyPairStorage;
      if (!result) {
        resolve(null);
        return;
      }

      try {
        const algo = keyType === 'ecdh' 
            ? { name: 'ECDH', namedCurve: 'P-256' }
            : { name: 'RSA-OAEP', hash: 'SHA-256' };

        const usages: KeyUsage[] = keyType === 'ecdh' ? [] : ['encrypt'];
        const privUsages: KeyUsage[] = keyType === 'ecdh' ? ['deriveKey', 'deriveBits'] : ['decrypt'];

        const publicKey = await window.crypto.subtle.importKey(
          'jwk',
          result.publicKey,
          algo,
          true,
          usages
        );

        const privateKey = await window.crypto.subtle.importKey(
          'jwk',
          result.privateKey,
          algo,
          true,
          privUsages
        );

        resolve({ publicKey, privateKey });
      } catch (e) {
        reject(e);
      }
    };
  });
}

export async function getKeyPairMetadata(userId: number, keyType: 'ecdh' | 'rsa' = 'ecdh'): Promise<{ createdAt: number } | null> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_KEYS, 'readonly');
    const store = transaction.objectStore(STORE_KEYS);
    const request = store.get(`${userId}:${keyType}`);

    request.onerror = () => reject(new Error('Failed to retrieve key pair metadata'));
    request.onsuccess = () => {
      const result = request.result as KeyPairStorage;
      if (!result) {
        resolve(null);
        return;
      }
      resolve({ createdAt: result.createdAt });
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
