// FWBer Service Worker for PWA functionality
const CACHE_NAME = 'fwber-v1.0.0';
const OFFLINE_URL = '/offline.html';

// Core files to cache for offline functionality
const CORE_CACHE = [
  '/',
  '/offline.html',
  '/styles.css',
  '/js/jquery-3.4.1.min.js',
  '/bootstrap-4.3.1-dist/css/bootstrap.min.css',
  '/bootstrap-4.3.1-dist/js/bootstrap.bundle.min.js',
  '/images/fwber_logo_icon.png',
  '/favicon.ico'
];

// Install event - cache core files
self.addEventListener('install', event => {
  console.log('Service Worker installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Caching core files');
        return cache.addAll(CORE_CACHE);
      })
      .then(() => {
        // Skip waiting to activate immediately
        return self.skipWaiting();
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker activating...');
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames
            .filter(cacheName => cacheName !== CACHE_NAME)
            .map(cacheName => caches.delete(cacheName))
        );
      })
      .then(() => {
        // Take control of all clients immediately
        return self.clients.claim();
      })
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip external requests
  if (!event.request.url.startsWith(self.location.origin)) {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        // Return cached version if available
        if (cachedResponse) {
          return cachedResponse;
        }

        // Try to fetch from network
        return fetch(event.request)
          .then(networkResponse => {
            // Don't cache non-successful responses
            if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
              return networkResponse;
            }

            // Cache successful responses
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });

            return networkResponse;
          })
          .catch(() => {
            // Network failed, try to serve offline page for navigation requests
            if (event.request.destination === 'document') {
              return caches.match(OFFLINE_URL);
            }
            
            // For other requests, return a generic offline response
            return new Response('Offline', { 
              status: 503, 
              statusText: 'Service Unavailable' 
            });
          });
      })
  );
});

// Background sync for form submissions when offline
self.addEventListener('sync', event => {
  if (event.tag === 'signup-sync') {
    event.waitUntil(syncSignupData());
  }
});

// Push notifications for matches
self.addEventListener('push', event => {
  if (!event.data) {
    return;
  }

  const data = event.data.json();
  const options = {
    body: data.body,
    icon: '/android-chrome-192x192.png',
    badge: '/android-chrome-96x96.png',
    vibrate: [100, 50, 100],
    data: {
      url: data.url || '/matches'
    },
    actions: [
      {
        action: 'view',
        title: 'View Match',
        icon: '/images/view-icon.png'
      },
      {
        action: 'dismiss',
        title: 'Dismiss',
        icon: '/images/dismiss-icon.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'view') {
    event.waitUntil(
      clients.openWindow(event.notification.data.url)
    );
  }
});

// Sync signup data when back online
async function syncSignupData() {
  try {
    const cache = await caches.open(CACHE_NAME);
    const pendingSignups = await cache.match('/pending-signups');
    
    if (pendingSignups) {
      const signups = await pendingSignups.json();
      
      for (const signup of signups) {
        try {
          await fetch('/_makeAccount', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(signup)
          });
        } catch (error) {
          console.error('Failed to sync signup:', error);
        }
      }
      
      // Clear pending signups after successful sync
      await cache.delete('/pending-signups');
    }
  } catch (error) {
    console.error('Background sync failed:', error);
  }
}

// Periodic background sync for location updates
self.addEventListener('periodicsync', event => {
  if (event.tag === 'location-update') {
    event.waitUntil(updateUserLocation());
  }
});

async function updateUserLocation() {
  // Update user location for real-time matching
  try {
    if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition(async (position) => {
        await fetch('/api/location', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
          })
        });
      });
    }
  } catch (error) {
    console.error('Location update failed:', error);
  }
}