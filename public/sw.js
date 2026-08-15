const CACHE_VERSION = 'hvu-ts-v6';
const STATIC_ASSETS = [
    './',
    './assets/css/tailwind.min.css',
    './assets/img/Logo.png',
    './assets/js/background-particles.js'
];

// Install — pre-cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate — clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter(key => key !== CACHE_VERSION)
                    .map(key => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

// Fetch — Network First with Cache Fallback
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Bypass Admin area and non-GET requests
    if (url.pathname.includes('/admin/') || event.request.method !== 'GET') return;
    if (!url.origin.includes(self.location.hostname)) return;

    // For API calls — network only (don't cache dynamic data)
    if (url.pathname.includes('/api/')) return;

    // For static assets (css, js, images) — Cache First
    if (url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?)$/)) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                if (cached) return cached;
                return fetch(event.request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_VERSION).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // For HTML pages — Network First with Offline Fallback
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                const clone = response.clone();
                caches.open(CACHE_VERSION).then(cache => cache.put(event.request, clone));
                return response;
            })
            .catch(() => {
                return caches.match(event.request).then((cached) => {
                    if (cached) return cached;
                    // Return offline page
                    return caches.match('/').then(homepage => {
                        if (homepage) return homepage;
                        return new Response(
                            '<html><body style="display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;background:#f8fafc"><div style="text-align:center"><h1 style="color:#BE1E2D;font-size:2rem">📡</h1><h2>Không có kết nối Internet</h2><p style="color:#6b7280">Vui lòng kiểm tra kết nối mạng và thử lại.</p><button onclick="location.reload()" style="margin-top:1rem;padding:0.75rem 2rem;background:#BE1E2D;color:#fff;border:none;border-radius:9999px;cursor:pointer;font-weight:bold">Thử lại</button></div></body></html>',
                            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                        );
                    });
                });
            })
    );
});
