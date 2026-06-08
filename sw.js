const CACHE_NAME = 'rms-v2';
const urlsToCache = [
  'assets/css/main.css',
  'assets/css/admin-design-system.css',
  'assets/img/logo.png'
];

self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
      .catch(err => console.log('Cache addAll failed:', err))
  );
});

// Activate event to delete any old caches (like the broken v1)
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Network-First strategy: Always fetch from network first, only use cache if offline
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Optionally cache new successful responses here if desired, 
        // but for now just returning the live network response is safest
        return response;
      })
      .catch(() => caches.match(event.request))
  );
});
