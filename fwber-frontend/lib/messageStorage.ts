import { ChatMessage } from './hooks/use-websocket';

const DB_NAME = 'fwber_messages';
const DB_VERSION = 1;
const STORE_NAME = 'chat_messages';
const MAX_MESSAGES_PER_CONVERSATION = 100;
const MESSAGE_RETENTION_DAYS = 30;

/**
 * Initialize IndexedDB for message storage
 */
function openDatabase(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);

    request.onupgradeneeded = (event) => {
      const db = (event.target as IDBOpenDBRequest).result;

      // Create object store if it doesn't exist
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        const store = db.createObjectStore(STORE_NAME, { keyPath: 'message_id' });
        
        // Create indexes for efficient querying
        store.createIndex('conversation', 'conversation_id', { unique: false });
        store.createIndex('timestamp', 'timestamp', { unique: false });
        store.createIndex('from_user', 'from_user_id', { unique: false });
        store.createIndex('to_user', 'to_user_id', { unique: false });
      }
    };
  });
}

/**
 * Get conversation ID for two users (sorted for consistency)
 */
function getConversationId(userId1: string, userId2: string): string {
  return [userId1, userId2].sort().join('_');
}

/**
 * Store a message in IndexedDB
 */
export async function storeMessage(message: ChatMessage): Promise<void> {
  if (!message.message_id || !message.from_user_id || !message.to_user_id) {
    console.warn('Cannot store message: missing required fields', message);
    return;
  }

  try {
    const db = await openDatabase();
    const transaction = db.transaction(STORE_NAME, 'readwrite');
    const store = transaction.objectStore(STORE_NAME);

    const messageWithConversation = {
      ...message,
      conversation_id: getConversationId(message.from_user_id, message.to_user_id),
      stored_at: new Date().toISOString(),
    };

    await new Promise<void>((resolve, reject) => {
      const request = store.put(messageWithConversation);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });

    // Clean up old messages for this conversation
    await cleanupOldMessages(db, messageWithConversation.conversation_id);
    
    db.close();
  } catch (error) {
    console.error('Error storing message:', error);
  }
}

/**
 * Store multiple messages in a batch
 */
export async function storeMessages(messages: ChatMessage[]): Promise<void> {
  if (messages.length === 0) return;

  try {
    const db = await openDatabase();
    const transaction = db.transaction(STORE_NAME, 'readwrite');
    const store = transaction.objectStore(STORE_NAME);

    for (const message of messages) {
      if (!message.message_id || !message.from_user_id || !message.to_user_id) {
        continue;
      }

      const messageWithConversation = {
        ...message,
        conversation_id: getConversationId(message.from_user_id, message.to_user_id),
        stored_at: new Date().toISOString(),
      };

      store.put(messageWithConversation);
    }

    await new Promise<void>((resolve, reject) => {
      transaction.oncomplete = () => resolve();
      transaction.onerror = () => reject(transaction.error);
    });

    db.close();
  } catch (error) {
    console.error('Error storing messages batch:', error);
  }
}

/**
 * Retrieve messages for a conversation
 */
export async function getConversationMessages(
  userId1: string,
  userId2: string,
  limit: number = MAX_MESSAGES_PER_CONVERSATION
): Promise<ChatMessage[]> {
  try {
    const db = await openDatabase();
    const transaction = db.transaction(STORE_NAME, 'readonly');
    const store = transaction.objectStore(STORE_NAME);
    const index = store.index('conversation');
    
    const conversationId = getConversationId(userId1, userId2);
    const request = index.getAll(conversationId);

    const messages = await new Promise<ChatMessage[]>((resolve, reject) => {
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });

    db.close();

    // Sort by timestamp and limit
    return messages
      .sort((a, b) => new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime())
      .slice(-limit);
  } catch (error) {
    console.error('Error retrieving conversation messages:', error);
    return [];
  }
}

/**
 * Get all messages from a specific user
 */
export async function getMessagesFromUser(userId: string): Promise<ChatMessage[]> {
  try {
    const db = await openDatabase();
    const transaction = db.transaction(STORE_NAME, 'readonly');
    const store = transaction.objectStore(STORE_NAME);
    const index = store.index('from_user');
    
    const request = index.getAll(userId);

    const messages = await new Promise<ChatMessage[]>((resolve, reject) => {
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });

    db.close();
    return messages;
  } catch (error) {
    console.error('Error retrieving messages from user:', error);
    return [];
  }
}

/**
 * Update message status
 */
export async function updateMessageStatus(
  messageId: string,
  status: 'sent' | 'delivered' | 'read' | 'failed',
  timestamp?: string
): Promise<void> {
  try {
    const db = await openDatabase();
    const transaction = db.transaction(STORE_NAME, 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    
    const request = store.get(messageId);
    
    await new Promise<void>((resolve, reject) => {
      request.onsuccess = () => {
        const message = request.result;
        if (message) {
          message.status = status;
          
          if (timestamp) {
            if (status === 'delivered') {
              message.delivered_at = timestamp;
            } else if (status === 'read') {
              message.read_at = timestamp;
            }
          }
          
          const updateRequest = store.put(message);
          updateRequest.onsuccess = () => resolve();
          updateRequest.onerror = () => reject(updateRequest.error);
        } else {
          resolve(); // Message not found, nothing to update
        }
      };
      request.onerror = () => reject(request.error);
    });

    db.close();
  } catch (error) {
    console.error('Error updating message status:', error);
  }
}

/**
 * Delete old messages beyond retention period
 */
async function cleanupOldMessages(db: IDBDatabase, conversationId: string): Promise<void> {
  try {
    const transaction = db.transaction(STORE_NAME, 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    const index = store.index('conversation');
    
    const request = index.getAll(conversationId);

    await new Promise<void>((resolve, reject) => {
      request.onsuccess = () => {
        const messages = request.result;
        
        // Sort by timestamp
        messages.sort((a, b) => 
          new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime()
        );

        // Keep only the most recent messages
        const messagesToDelete = messages.slice(0, -MAX_MESSAGES_PER_CONVERSATION);
        
        // Delete old messages
        messagesToDelete.forEach(msg => {
          if (msg.message_id) {
            store.delete(msg.message_id);
          }
        });

        resolve();
      };
      request.onerror = () => reject(request.error);
    });
  } catch (error) {
    console.error('Error cleaning up old messages:', error);
  }
}

/**
 * Delete messages older than retention period
 */
export async function deleteExpiredMessages(): Promise<void> {
  try {
    const db = await openDatabase();
    const transaction = db.transaction(STORE_NAME, 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    const index = store.index('timestamp');
    
    const cutoffDate = new Date();
    cutoffDate.setDate(cutoffDate.getDate() - MESSAGE_RETENTION_DAYS);
    
    const request = index.openCursor();

    await new Promise<void>((resolve, reject) => {
      request.onsuccess = (event) => {
        const cursor = (event.target as IDBRequest).result;
        if (cursor) {
          const message = cursor.value;
          if (new Date(message.timestamp) < cutoffDate) {
            cursor.delete();
          }
          cursor.continue();
        } else {
          resolve();
        }
      };
      request.onerror = () => reject(request.error);
    });

    db.close();
  } catch (error) {
    console.error('Error deleting expired messages:', error);
  }
}

/**
 * Clear all messages for a conversation
 */
export async function clearConversation(userId1: string, userId2: string): Promise<void> {
  try {
    const db = await openDatabase();
    const transaction = db.transaction(STORE_NAME, 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    const index = store.index('conversation');
    
    const conversationId = getConversationId(userId1, userId2);
    const request = index.openCursor(conversationId);

    await new Promise<void>((resolve, reject) => {
      request.onsuccess = (event) => {
        const cursor = (event.target as IDBRequest).result;
        if (cursor) {
          cursor.delete();
          cursor.continue();
        } else {
          resolve();
        }
      };
      request.onerror = () => reject(request.error);
    });

    db.close();
  } catch (error) {
    console.error('Error clearing conversation:', error);
  }
}

/**
 * Clear all stored messages
 */
export async function clearAllMessages(): Promise<void> {
  try {
    const db = await openDatabase();
    const transaction = db.transaction(STORE_NAME, 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    
    await new Promise<void>((resolve, reject) => {
      const request = store.clear();
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });

    db.close();
  } catch (error) {
    console.error('Error clearing all messages:', error);
  }
}

/**
 * Get message statistics
 */
export async function getMessageStats(): Promise<{
  totalMessages: number;
  conversations: number;
  oldestMessage: string | null;
  newestMessage: string | null;
}> {
  try {
    const db = await openDatabase();
    const transaction = db.transaction(STORE_NAME, 'readonly');
    const store = transaction.objectStore(STORE_NAME);
    
    const countRequest = store.count();
    const allRequest = store.getAll();

    const totalMessages = await new Promise<number>((resolve, reject) => {
      countRequest.onsuccess = () => resolve(countRequest.result);
      countRequest.onerror = () => reject(countRequest.error);
    });

    const allMessages = await new Promise<ChatMessage[]>((resolve, reject) => {
      allRequest.onsuccess = () => resolve(allRequest.result);
      allRequest.onerror = () => reject(allRequest.error);
    });

    const conversations = new Set(
      allMessages.map(m => getConversationId(m.from_user_id, m.to_user_id))
    ).size;

    const timestamps: string[] = allMessages
      .map(m => typeof m.timestamp === 'string' ? m.timestamp : new Date(m.timestamp).toISOString())
      .sort();
    const oldestMessage = timestamps.length > 0 ? timestamps[0] : null;
    const newestMessage = timestamps.length > 0 ? timestamps[timestamps.length - 1] : null;

    db.close();

    return {
      totalMessages,
      conversations,
      oldestMessage,
      newestMessage,
    };
  } catch (error) {
    console.error('Error getting message stats:', error);
    return {
      totalMessages: 0,
      conversations: 0,
      oldestMessage: null,
      newestMessage: null,
    };
  }
}
