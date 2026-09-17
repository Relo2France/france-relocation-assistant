/**
 * Service Worker for Relo2France Member Portal PWA
 *
 * Provides:
 * - Offline caching for static assets
 * - Background sync for trip data
 * - Push notification handling
 */

const CACHE_VERSION = 'v1.0.0';
const STATIC_CACHE = `relo2france-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `relo2france-dynamic-${CACHE_VERSION}`;
const API_CACHE = `relo2france-api-${CACHE_VERSION}`;

// Files to cache immediately on install
const STATIC_ASSETS = [
  '/member-portal/',
  '/member-portal/schengen',
  '/member-portal/profile',
  '/member-portal/documents',
];

// API endpoints to cache with network-first strategy
const CACHEABLE_API_PATHS = [
  '/wp-json/framt/v1/profile',
  '/wp-json/framt/v1/schengen/trips',
  '/wp-json/framt/v1/schengen/summary',
  '/wp-json/framt/v1/schengen/settings',
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[ServiceWorker] Installing...');

  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => {
        console.log('[ServiceWorker] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .catch((error) => {
        console.error('[ServiceWorker] Failed to cache static assets:', error);
      })
  );

  // Activate immediately
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[ServiceWorker] Activating...');

  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => {
            return name.startsWith('relo2france-') &&
                   name !== STATIC_CACHE &&
                   name !== DYNAMIC_CACHE &&
                   name !== API_CACHE;
          })
          .map((name) => {
            console.log('[ServiceWorker] Deleting old cache:', name);
            return caches.delete(name);
          })
      );
    })
  );

  // Take control immediately
  self.clients.claim();
});

// Fetch event - handle requests
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip cross-origin requests
  if (url.origin !== location.origin) {
    return;
  }

  // API requests - network first, then cache
  if (url.pathname.startsWith('/wp-json/') && isApiCacheable(url.pathname)) {
    event.respondWith(networkFirstThenCache(request, API_CACHE));
    return;
  }

  // Static assets (CSS, JS, images) - cache first
  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirstThenNetwork(request, STATIC_CACHE));
    return;
  }

  // HTML pages - network first for fresh content
  if (request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(networkFirstThenCache(request, DYNAMIC_CACHE));
    return;
  }
});

// Check if URL is a cacheable API endpoint
function isApiCacheable(pathname) {
  return CACHEABLE_API_PATHS.some(path => pathname.includes(path));
}

// Check if URL is a static asset
function isStaticAsset(pathname) {
  return /\.(js|css|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|ico)(\?.*)?$/i.test(pathname);
}

// Cache-first strategy
async function cacheFirstThenNetwork(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cachedResponse = await cache.match(request);

  if (cachedResponse) {
    // Return cached response but fetch fresh in background
    fetchAndCache(request, cache);
    return cachedResponse;
  }

  // Fetch from network and cache
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.error('[ServiceWorker] Fetch failed:', error);
    return new Response('Offline', { status: 503 });
  }
}

// Network-first strategy
async function networkFirstThenCache(request, cacheName) {
  const cache = await caches.open(cacheName);

  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.log('[ServiceWorker] Network failed, trying cache');
    const cachedResponse = await cache.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    // Return offline page for navigation requests
    if (request.headers.get('accept')?.includes('text/html')) {
      return caches.match('/member-portal/');
    }

    return new Response('Offline', { status: 503 });
  }
}

// Fetch and cache in background
async function fetchAndCache(request, cache) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
  } catch (error) {
    // Ignore errors for background fetch
  }
}

// Push notification handling
self.addEventListener('push', (event) => {
  if (!event.data) {
    return;
  }

  const data = event.data.json();
  const options = {
    body: data.body || '',
    icon: '/wp-content/plugins/france-relocation-member-tools/assets/images/pwa-icon-192.png',
    badge: '/wp-content/plugins/france-relocation-member-tools/assets/images/pwa-badge.png',
    tag: data.tag || 'relo2france-notification',
    data: {
      url: data.url || '/member-portal/schengen',
    },
    vibrate: [200, 100, 200],
    actions: data.actions || [],
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'Relo2France', options)
  );
});

// Notification click handling
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url = event.notification.data?.url || '/member-portal/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Focus existing window if available
        for (const client of clientList) {
          if (client.url.includes('/member-portal') && 'focus' in client) {
            return client.focus();
          }
        }
        // Open new window
        if (clients.openWindow) {
          return clients.openWindow(url);
        }
      })
  );
});

// Background sync for offline trip entries
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-trips') {
    event.waitUntil(syncTrips());
  }
});

async function syncTrips() {
  // Get pending trips from IndexedDB
  // This would be implemented with actual IndexedDB integration
  console.log('[ServiceWorker] Syncing offline trips...');
}

// Message handling for cache updates
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'CLEAR_CACHE') {
    event.waitUntil(
      caches.keys().then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((name) => name.startsWith('relo2france-'))
            .map((name) => caches.delete(name))
        );
      })
    );
  }
});
