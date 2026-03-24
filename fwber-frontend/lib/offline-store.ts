'use client';

const DB_NAME = 'fwber_offline_v1';
const STORE_NAME = 'pending_messages';

export interface OfflineMessage {
    id?: number;
    uuid: string;
    chatroom_id?: string;
    recipient_id: string;
    content: string;
    type: string;
    is_encrypted: boolean;
    created_at: string;
    retry_count: number;
}

export async function openDB(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, 1);
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        request.onupgradeneeded = (event) => {
            const db = (event.target as IDBOpenDBRequest).result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                const store = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                store.createIndex('uuid', 'uuid', { unique: true });
                store.createIndex('chatroom_id', 'chatroom_id', { unique: false });
            }
        };
    });
}

export async function storeOfflineMessage(message: Omit<OfflineMessage, 'id' | 'retry_count'>): Promise<number> {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE_NAME, 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.add({ ...message, retry_count: 0 });
        request.onsuccess = () => resolve(request.result as number);
        request.onerror = () => reject(request.error);
    });
}

export async function getPendingMessages(): Promise<OfflineMessage[]> {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE_NAME, 'readonly');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.getAll();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

export async function removePendingMessage(id: number): Promise<void> {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE_NAME, 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.delete(id);
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

export async function incrementRetryCount(id: number): Promise<void> {
    const db = await openDB();
    const transaction = db.transaction(STORE_NAME, 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    const message = await new Promise<OfflineMessage>((resolve) => {
        store.get(id).onsuccess = (e) => resolve((e.target as IDBRequest).result);
    });
    if (message) {
        message.retry_count += 1;
        store.put(message);
    }
}
