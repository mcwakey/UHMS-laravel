const UHMS_CACHE = 'uhms-static-v1';

const STATIC_ASSETS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/build/css/style.css',
    '/build/js/jquery-3.7.1.min.js',
    '/build/js/bootstrap.bundle.min.js',
    '/build/js/script.js',
    '/build/img/favicon.png',
    '/build/img/logo-small.svg'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(UHMS_CACHE).then((cache) => Promise.allSettled(
            STATIC_ASSETS.map((asset) => cache.add(asset))
        ))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== UHMS_CACHE).map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (url.pathname.startsWith('/build/') || ['style', 'script', 'font', 'image'].includes(request.destination)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html'))
        );
    }
});

async function cacheFirst(request) {
    const cache = await caches.open(UHMS_CACHE);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    const response = await fetch(request);

    if (response && response.ok) {
        cache.put(request, response.clone());
    }

    return response;
}
