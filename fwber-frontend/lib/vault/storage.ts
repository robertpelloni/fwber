/**
 * Local Media Vault - IndexedDB Storage Layer
 *
 * Uses IndexedDB for persistent local storage of encrypted media.
 * Wraps the native IndexedDB API with a cleaner async interface.
 */

const DB_NAME = 'fwber_media_vault';
const DB_VERSION = 1;
const STORE_MEDIA = 'encrypted_media';
const STORE_METADATA = 'metadata';

export interface VaultMediaItem {
  id: string;
  originalName: string;
  mimeType: string;
  encryptedBlob: Blob;
  thumbnailBlob?: Blob;
  createdAt: number;
  updatedAt: number;
  size: number;
  // Reference to server-side photo if synced
  remotePhotoId?: number;
}

export interface VaultMetadata {
  key: string;
  value: string;
}

let dbInstance: IDBDatabase | null = null;

/**
 * Open/create the IndexedDB database
 */
async function openDatabase(): Promise<IDBDatabase> {
  if (dbInstance) return dbInstance;

  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onerror = () => {
      reject(new Error('Failed to open vault database'));
    };

    request.onsuccess = () => {
      dbInstance = request.result;
      resolve(request.result);
    };

    request.onupgradeneeded = (event) => {
      const db = (event.target as IDBOpenDBRequest).result;

      // Create encrypted media store
      if (!db.objectStoreNames.contains(STORE_MEDIA)) {
        const mediaStore = db.createObjectStore(STORE_MEDIA, { keyPath: 'id' });
        mediaStore.createIndex('createdAt', 'createdAt', { unique: false });
        mediaStore.createIndex('remotePhotoId', 'remotePhotoId', { unique: false });
      }

      // Create metadata store for salt, verifier, settings
      if (!db.objectStoreNames.contains(STORE_METADATA)) {
        db.createObjectStore(STORE_METADATA, { keyPath: 'key' });
      }
    };
  });
}

/**
 * Store an encrypted media item
 */
export async function storeMedia(item: VaultMediaItem): Promise<void> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_MEDIA, 'readwrite');
    const store = transaction.objectStore(STORE_MEDIA);
    const request = store.put(item);

    request.onerror = () => reject(new Error('Failed to store media'));
    request.onsuccess = () => resolve();
  });
}

/**
 * Retrieve an encrypted media item by ID
 */
export async function getMedia(id: string): Promise<VaultMediaItem | null> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_MEDIA, 'readonly');
    const store = transaction.objectStore(STORE_MEDIA);
    const request = store.get(id);

    request.onerror = () => reject(new Error('Failed to retrieve media'));
    request.onsuccess = () => resolve(request.result || null);
  });
}

/**
 * Delete an encrypted media item
 */
export async function deleteMedia(id: string): Promise<void> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_MEDIA, 'readwrite');
    const store = transaction.objectStore(STORE_MEDIA);
    const request = store.delete(id);

    request.onerror = () => reject(new Error('Failed to delete media'));
    request.onsuccess = () => resolve();
  });
}

/**
 * List all media items (metadata only, blobs excluded for performance)
 */
export async function listMedia(): Promise<Omit<VaultMediaItem, 'encryptedBlob' | 'thumbnailBlob'>[]> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_MEDIA, 'readonly');
    const store = transaction.objectStore(STORE_MEDIA);
    const index = store.index('createdAt');
    const request = index.openCursor(null, 'prev'); // Newest first
    const results: Omit<VaultMediaItem, 'encryptedBlob' | 'thumbnailBlob'>[] = [];

    request.onerror = () => reject(new Error('Failed to list media'));
    request.onsuccess = (event) => {
      const cursor = (event.target as IDBRequest<IDBCursorWithValue>).result;
      if (cursor) {
        const { encryptedBlob, thumbnailBlob, ...metadata } = cursor.value;
        results.push(metadata);
        cursor.continue();
      } else {
        resolve(results);
      }
    };
  });
}

/**
 * Get media by remote photo ID
 */
export async function getMediaByRemoteId(remotePhotoId: number): Promise<VaultMediaItem | null> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_MEDIA, 'readonly');
    const store = transaction.objectStore(STORE_MEDIA);
    const index = store.index('remotePhotoId');
    const request = index.get(remotePhotoId);

    request.onerror = () => reject(new Error('Failed to retrieve media by remote ID'));
    request.onsuccess = () => resolve(request.result || null);
  });
}

/**
 * Count total media items
 */
export async function countMedia(): Promise<number> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_MEDIA, 'readonly');
    const store = transaction.objectStore(STORE_MEDIA);
    const request = store.count();

    request.onerror = () => reject(new Error('Failed to count media'));
    request.onsuccess = () => resolve(request.result);
  });
}

/**
 * Calculate total storage used (in bytes)
 */
export async function calculateStorageUsed(): Promise<number> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_MEDIA, 'readonly');
    const store = transaction.objectStore(STORE_MEDIA);
    const request = store.openCursor();
    let totalSize = 0;

    request.onerror = () => reject(new Error('Failed to calculate storage'));
    request.onsuccess = (event) => {
      const cursor = (event.target as IDBRequest<IDBCursorWithValue>).result;
      if (cursor) {
        totalSize += cursor.value.size || 0;
        cursor.continue();
      } else {
        resolve(totalSize);
      }
    };
  });
}

/**
 * Store metadata (salt, verifier, settings)
 */
export async function setMetadata(key: string, value: string): Promise<void> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_METADATA, 'readwrite');
    const store = transaction.objectStore(STORE_METADATA);
    const request = store.put({ key, value });

    request.onerror = () => reject(new Error('Failed to store metadata'));
    request.onsuccess = () => resolve();
  });
}

/**
 * Retrieve metadata
 */
export async function getMetadata(key: string): Promise<string | null> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_METADATA, 'readonly');
    const store = transaction.objectStore(STORE_METADATA);
    const request = store.get(key);

    request.onerror = () => reject(new Error('Failed to retrieve metadata'));
    request.onsuccess = () => resolve(request.result?.value || null);
  });
}

/**
 * Delete metadata
 */
export async function deleteMetadata(key: string): Promise<void> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_METADATA, 'readwrite');
    const store = transaction.objectStore(STORE_METADATA);
    const request = store.delete(key);

    request.onerror = () => reject(new Error('Failed to delete metadata'));
    request.onsuccess = () => resolve();
  });
}

/**
 * Clear all vault data (nuclear option)
 */
export async function clearAllVaultData(): Promise<void> {
  const db = await openDatabase();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction([STORE_MEDIA, STORE_METADATA], 'readwrite');
    transaction.objectStore(STORE_MEDIA).clear();
    transaction.objectStore(STORE_METADATA).clear();

    transaction.oncomplete = () => resolve();
    transaction.onerror = () => reject(new Error('Failed to clear vault data'));
  });
}

/**
 * Close the database connection
 */
export function closeDatabase(): void {
  if (dbInstance) {
    dbInstance.close();
    dbInstance = null;
  }
}

/**
 * Check if IndexedDB is supported
 */
export function isIndexedDBSupported(): boolean {
  return typeof indexedDB !== 'undefined';
}
