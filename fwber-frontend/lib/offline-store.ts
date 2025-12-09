const DB_NAME = 'fwber_offline_store';
const DB_VERSION = 1;
const STORE_MESSAGES = 'offline_messages';
const STORE_LOCATIONS = 'offline_locations';

function openDB(): Promise<IDBDatabase> {
  if (typeof window === 'undefined' || !window.indexedDB) {
    return Promise.reject(new Error('IndexedDB not supported'));
  }

  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    request.onupgradeneeded = (event) => {
      const db = (event.target as IDBOpenDBRequest).result;
      if (!db.objectStoreNames.contains(STORE_MESSAGES)) {
        db.createObjectStore(STORE_MESSAGES, { keyPath: 'id', autoIncrement: true });
      }
      if (!db.objectStoreNames.contains(STORE_LOCATIONS)) {
        db.createObjectStore(STORE_LOCATIONS, { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

export async function storeOfflineMessage(message: any): Promise<void> {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_MESSAGES, 'readwrite');
    const store = transaction.objectStore(STORE_MESSAGES);
    const request = store.add(message);
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

export async function storeOfflineLocation(location: any): Promise<void> {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_LOCATIONS, 'readwrite');
    const store = transaction.objectStore(STORE_LOCATIONS);
    const request = store.add(location);
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}
