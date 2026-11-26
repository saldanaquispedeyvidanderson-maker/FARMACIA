const CACHE_NAME = 'farm-sjb-v1';
const ASSETS_TO_CACHE = [
    '/css/bootstrap.css',
    '/css/custom.css',
    '/js/bootstrap.bundle.min.js',
    '/js/app.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_TO_CACHE))
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
