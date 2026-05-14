const UHMS_CACHE = 'uhms-static-v2';

const STATIC_ASSETS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/build/img/favicon.png',
    '/build/img/logo-small.svg'
];

const NETWORK_FIRST_PATHS = [
    '/sw.js',
    '/register-sw.js',
    '/manifest.webmanifest',
    '/build/manifest.json',
    '/build/registerSW.js',
    '/build/sw.js'
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

    if (
        NETWORK_FIRST_PATHS.includes(url.pathname)
        || url.pathname.startsWith('/build/js/')
        || url.pathname.startsWith('/build/css/')
        || ['style', 'script'].includes(request.destination)
    ) {
        event.respondWith(networkFirst(request));
        return;
    }

    if (url.pathname.startsWith('/build/') || ['font', 'image'].includes(request.destination)) {
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

async function networkFirst(request) {
    const cache = await caches.open(UHMS_CACHE);

    try {
        const response = await fetch(request);

        if (response && response.ok) {
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        const cached = await cache.match(request);

        if (cached) {
            return cached;
        }

        throw error;
    }
}
