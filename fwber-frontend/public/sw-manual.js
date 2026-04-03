// fwber.me Service Worker - PWA Offline Support
const CACHE_NAME = 'fwber-v1.2.6';
const API_CACHE_NAME = 'fwber-api-v1.2.6';
const STATIC_CACHE_NAME = 'fwber-static-v1.2.6';

// Cache strategies
const CACHE_STRATEGIES = {
  // Static assets - cache first
  static: ['/static/', '/_next/static/', '/images/', '/icons/'],
  // API calls - network first, cache fallback
  api: ['/api/'],
  // Pages - network first, cache fallback
  pages: ['/dashboard', '/profile', '/matches', '/messages', '/nearby'],
  // Real-time - network only
  realtime: ['/broadcasting', '/stream', '/websocket']
};

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('Service Worker installing...');
  
  event.waitUntil(
    Promise.all([
      // Cache static assets
      caches.open(STATIC_CACHE_NAME).then((cache) => {
        return cache.addAll([
          '/',
          '/dashboard',
          '/matches',
          '/messages',
          '/nearby',
          '/manifest.json',
          '/icons/icon-192x192.png',
          '/icons/icon-512x512.png'
        ]);
      }),
      // Cache API responses
      caches.open(API_CACHE_NAME).then((cache) => {
        return cache.addAll([
          '/api/auth/me',
          '/api/location'
        ]);
      })
    ]).then(() => {
      console.log('Service Worker installed successfully');
      return self.skipWaiting();
    })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('Service Worker activating...');
  
  event.waitUntil(
    Promise.all([
      // Clean up old caches
      caches.keys().then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== CACHE_NAME && 
                cacheName !== API_CACHE_NAME && 
                cacheName !== STATIC_CACHE_NAME) {
              console.log('Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      // Take control of all clients
      self.clients.claim()
    ]).then(() => {
      console.log('Service Worker activated successfully');
    })
  );
});

// Fetch event - implement caching strategies
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }
  
  // Skip real-time connections
  if (isRealtimeRequest(url)) {
    return;
  }
  
  // Apply appropriate caching strategy
  if (isStaticAsset(url)) {
    event.respondWith(cacheFirst(request, STATIC_CACHE_NAME));
  } else if (isApiRequest(url)) {
    event.respondWith(networkFirst(request, API_CACHE_NAME));
  } else if (isPageRequest(url)) {
    event.respondWith(networkFirst(request, CACHE_NAME));
  } else {
    event.respondWith(networkOnly(request));
  }
});

// Background sync for offline messages
self.addEventListener('sync', (event) => {
  console.log('Background sync triggered:', event.tag);
  
  if (event.tag === 'chat-sync') {
    event.waitUntil(syncOfflineMessages());
  } else if (event.tag === 'location-update') {
    event.waitUntil(syncLocationUpdates());
  }
});

// Push notifications
self.addEventListener('push', (event) => {
  console.log('Push notification received:', event);
  
  let data = { title: 'fwber', body: 'New notification', url: '/dashboard' };
  
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
      url: data.url || '/dashboard',
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
    self.registration.showNotification(data.title || 'fwber', options)
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

// Cache strategies
async function cacheFirst(request, cacheName) {
  try {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    
    if (cached) {
      return cached;
    }
    
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    console.error('Cache first strategy failed:', error);
    return new Response('Offline', { status: 503 });
  }
}

async function networkFirst(request, cacheName) {
  try {
    const response = await fetch(request);
    
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    console.log('Network failed, trying cache:', error);
    
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    
    if (cached) {
      return cached;
    }
    
    return new Response('Offline', { status: 503 });
  }
}

async function networkOnly(request) {
  try {
    return await fetch(request);
  } catch (error) {
    console.error('Network only strategy failed:', error);
    return new Response('Offline', { status: 503 });
  }
}

// Helper functions
function isStaticAsset(url) {
  return CACHE_STRATEGIES.static.some(pattern => url.pathname.includes(pattern));
}

function isApiRequest(url) {
  return CACHE_STRATEGIES.api.some(pattern => url.pathname.includes(pattern));
}

function isPageRequest(url) {
  return CACHE_STRATEGIES.pages.some(pattern => url.pathname.includes(pattern));
}

function isRealtimeRequest(url) {
  return CACHE_STRATEGIES.realtime.some(pattern => url.pathname.includes(pattern));
}

// Background sync functions
async function syncOfflineMessages() {
    // Relying on the foreground CRDT sync in useChatSync.ts
    // In a full implementation, we'd replicate the IndexedDB IDB cursor logic here
    console.log('Background message sync deferred to foreground React app context.');
}

async function syncLocationUpdates() {
    console.log('Background location sync triggered.');
}
