/**
 * ReadIn52 Service Worker
 * Provides offline support and caching
 */

const CACHE_NAME = 'readin52-v6';
const STATIC_CACHE = 'readin52-static-v6';
const API_CACHE = 'readin52-api-v6';

// Static assets to cache
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    '/assets/css/style.css',
    '/assets/js/app.js',
    '/assets/js/bible-api.js',
    '/assets/images/icon.svg',
    '/assets/images/logo.svg'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(name => name !== STATIC_CACHE && name !== API_CACHE)
                        .map(name => caches.delete(name))
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Handle Bible API requests
    if (url.hostname === 'bible.helloao.org') {
        event.respondWith(
            caches.open(API_CACHE)
                .then(cache => {
                    return cache.match(request)
                        .then(cached => {
                            if (cached) {
                                return cached;
                            }

                            return fetch(request)
                                .then(response => {
                                    if (response.ok) {
                                        cache.put(request, response.clone());
                                    }
                                    return response;
                                });
                        });
                })
        );
        return;
    }

    // Handle static assets
    if (url.pathname.startsWith('/assets/')) {
        event.respondWith(
            caches.match(request)
                .then(cached => {
                    if (cached) {
                        return cached;
                    }

                    return fetch(request)
                        .then(response => {
                            if (response.ok) {
                                const clone = response.clone();
                                caches.open(STATIC_CACHE)
                                    .then(cache => cache.put(request, clone));
                            }
                            return response;
                        });
                })
        );
        return;
    }

    // Network first for HTML pages
    event.respondWith(
        fetch(request)
            .then(response => {
                return response;
            })
            .catch(() => {
                return caches.match(request)
                    .then(cached => {
                        if (cached) {
                            return cached;
                        }

                        // Return offline page if available
                        if (request.headers.get('accept').includes('text/html')) {
                            return caches.match('/');
                        }
                    });
            })
    );
});

// Handle background sync for progress updates
self.addEventListener('sync', event => {
    if (event.tag === 'sync-progress') {
        event.waitUntil(syncProgress());
    }
});

async function syncProgress() {
    // This would sync any offline progress updates
    // Implementation would depend on IndexedDB storage of offline actions
    console.log('Syncing progress...');
}

// Handle push notifications (future feature)
self.addEventListener('push', event => {
    if (event.data) {
        const data = event.data.json();
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/assets/images/icon.svg',
            badge: '/assets/images/icon.svg'
        });
    }
});
