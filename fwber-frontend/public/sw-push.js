// FWBer.me Service Worker - Push Notifications & Background Sync
// Extracted from sw-manual.js to avoid conflicts with next-pwa caching

// Background sync for offline messages
self.addEventListener('sync', (event) => {
  console.log('Background sync triggered:', event.tag);
  
  if (event.tag === 'bulletin-message') {
    event.waitUntil(syncOfflineMessages());
  } else if (event.tag === 'location-update') {
    event.waitUntil(syncLocationUpdates());
  }
});

// Push notifications
self.addEventListener('push', (event) => {
  console.log('Push notification received:', event);
  
  let data = { title: 'FWBer.me', body: 'New notification', url: '/' };
  
  try {
    data = event.data ? event.data.json() : data;
  } catch (e) {
    data.body = event.data ? event.data.text() : data.body;
  }

  const options = {
    body: data.body,
    icon: data.icon || '/icons/icon-192x192.png',
    badge: '/icons/badge-72x72.png',
    vibrate: [200, 100, 200],
    data: {
      url: data.url || '/',
      timestamp: Date.now()
    },
    actions: data.actions || [
      {
        action: 'view',
        title: 'View',
        icon: '/icons/view-24x24.png'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title || 'FWBer.me', options)
  );
});

// Notification click handling
self.addEventListener('notificationclick', (event) => {
  console.log('Notification clicked:', event);
  
  event.notification.close();
  
  if (event.action === 'view' || !event.action) {
    event.waitUntil(
      clients.openWindow(event.notification.data.url)
    );
  }
});

// Background sync functions
async function syncOfflineMessages() {
  try {
    const offlineMessages = await getOfflineMessages();
    
    for (const message of offlineMessages) {
      const response = await fetch('/api/bulletin-boards/' + message.boardId + '/messages', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + message.token
        },
        body: JSON.stringify({
          content: message.content,
          lat: message.lat,
          lng: message.lng,
          is_anonymous: message.is_anonymous
        })
      });
      
      if (response.ok) {
        await removeOfflineMessage(message.id);
        console.log('Offline message synced:', message.id);
      }
    }
  } catch (error) {
    console.error('Failed to sync offline messages:', error);
  }
}

async function syncLocationUpdates() {
  try {
    const offlineLocations = await getOfflineLocations();
    
    for (const location of offlineLocations) {
      const response = await fetch('/api/location', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + location.token
        },
        body: JSON.stringify({
          latitude: location.latitude,
          longitude: location.longitude,
          accuracy: location.accuracy
        })
      });
      
      if (response.ok) {
        await removeOfflineLocation(location.id);
        console.log('Offline location synced:', location.id);
      }
    }
  } catch (error) {
    console.error('Failed to sync offline locations:', error);
  }
}

// IndexedDB helpers for offline storage
const DB_NAME = 'fwber_offline_store';
const DB_VERSION = 1;
const STORE_MESSAGES = 'offline_messages';
const STORE_LOCATIONS = 'offline_locations';

function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains(STORE_MESSAGES)) {
        db.createObjectStore(STORE_MESSAGES, { keyPath: 'id', autoIncrement: true });
      }
      if (!db.objectStoreNames.contains(STORE_LOCATIONS)) {
        db.createObjectStore(STORE_LOCATIONS, { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

async function getOfflineMessages() {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_MESSAGES, 'readonly');
    const store = transaction.objectStore(STORE_MESSAGES);
    const request = store.getAll();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function removeOfflineMessage(id) {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_MESSAGES, 'readwrite');
    const store = transaction.objectStore(STORE_MESSAGES);
    const request = store.delete(id);
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

async function getOfflineLocations() {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_LOCATIONS, 'readonly');
    const store = transaction.objectStore(STORE_LOCATIONS);
    const request = store.getAll();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function removeOfflineLocation(id) {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(STORE_LOCATIONS, 'readwrite');
    const store = transaction.objectStore(STORE_LOCATIONS);
    const request = store.delete(id);
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}
