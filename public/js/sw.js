/* ReadIn52 service worker — network-first for app assets, offline cache fallback */
const CACHE = 'readin52-v3';
const ASSETS = [
  '/static/css/style.css',
  '/static/js/app.js',
  '/static/js/bible-api.js',
  '/static/icons/icon.svg',
  '/manifest.webmanifest',
];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);
  if (e.request.method !== 'GET') return;

  // Bible API: stale-while-revalidate (fast + offline-friendly)
  if (url.hostname === 'bible.helloao.org') {
    e.respondWith(caches.open(CACHE).then(async (c) => {
      const cached = await c.match(e.request);
      const network = fetch(e.request).then((res) => { c.put(e.request, res.clone()); return res; }).catch(() => cached);
      return cached || network;
    }));
    return;
  }

  // Same-origin static assets: network-first, fall back to cache offline
  if (url.origin === self.location.origin && (url.pathname.startsWith('/static/') || url.pathname === '/manifest.webmanifest')) {
    e.respondWith(
      fetch(e.request).then((res) => {
        const clone = res.clone();
        caches.open(CACHE).then((c) => c.put(e.request, clone));
        return res;
      }).catch(() => caches.match(e.request))
    );
    return;
  }

  // Navigations/pages: pass through to network (keeps auth + CSRF correct)
});
