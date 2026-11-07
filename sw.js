/**
 * ASLAB REPORT - PROGRESSIVE WEB APP (PWA) SERVICE WORKER
 * Versi: v1.0.0
 * Strategi:
 * - Pre-cache Static Assets (Vendor CSS, JS, Three.js, Fonts, Icons)
 * - Stale-While-Revalidate untuk Static Assets (Super Cepat & Selalu Update)
 * - Network-First dengan Fallback ke offline.html untuk Navigasi Halaman PHP
 * - Keamanan: Memisahkan session dinamis & bypass upload/auth
 */

const CACHE_NAME_STATIC = 'aslab-static-v1.0.0';
const CACHE_NAME_RUNTIME = 'aslab-runtime-v1.0.0';

// Aset Inti yang di-precache saat instalasi
const PRECACHE_ASSETS = [
    './offline.html',
    './manifest.json',
    './assets/img/logo-unpam.png',
    './assets/img/icons/icon-72.png',
    './assets/img/icons/icon-96.png',
    './assets/img/icons/icon-128.png',
    './assets/img/icons/icon-144.png',
    './assets/img/icons/icon-152.png',
    './assets/img/icons/icon-192.png',
    './assets/img/icons/icon-384.png',
    './assets/img/icons/icon-512.png',
    './assets/img/icons/icon-maskable-192.png',
    './assets/img/icons/icon-maskable-512.png',
    './assets/img/icons/apple-touch-icon.png',
    './assets/vendor/bootstrap/css/bootstrap.min.css',
    './assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
    './assets/vendor/fontawesome/css/all.min.css',
    './assets/vendor/fontawesome/webfonts/fa-solid-900.woff2',
    './assets/vendor/fontawesome/webfonts/fa-regular-400.woff2',
    './assets/vendor/three/three.min.js',
    './assets/css/theme.css',
    './assets/css/animations.css',
    './assets/css/components.css',
    './assets/css/forms.css',
    './assets/css/style.css',
    './assets/js/theme.js',
    './assets/js/animations.js',
    './assets/js/avatar3d.js',
    './assets/js/login-avatar3d.js',
    './assets/js/forms.js',
    './assets/js/app.js',
    './assets/js/pwa.js'
];

// 1. INSTALL EVENT
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME_STATIC)
            .then((cache) => {
                return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                    console.warn('[PWA ServiceWorker] Pre-cache partial warning:', err);
                });
            })
            .then(() => self.skipWaiting())
    );
});

// 2. ACTIVATE EVENT (Pembersihan Cache Versi Lama)
self.addEventListener('activate', (event) => {
    const currentCaches = [CACHE_NAME_STATIC, CACHE_NAME_RUNTIME];
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (!currentCaches.includes(cacheName)) {
                        console.log('[PWA ServiceWorker] Menghapus cache lama:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// 3. FETCH EVENT
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Hanya tangani GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Bypass ServiceWorker untuk streaming otentikasi foto privat & aksi logout
    if (url.pathname.includes('lihat-foto.php') || url.pathname.includes('logout.php')) {
        return;
    }

    // A. Strategi untuk Navigasi Halaman HTML / PHP (Network-First dengan Offline Fallback)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    // Simpan salinan di cache runtime jika status 200 OK
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME_RUNTIME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(() => {
                    // Jika offline, coba ambil dari cache terlebih dahulu
                    return caches.match(request).then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        // Jika tidak ada di cache, tampilkan offline.html yang informatif
                        return caches.match('./offline.html');
                    });
                })
        );
        return;
    }

    // B. Strategi untuk Static Assets: CSS, JS, Gambar, Fonts (Stale-While-Revalidate)
    if (
        url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/) ||
        url.pathname.includes('/assets/')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request)
                    .then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            const responseClone = networkResponse.clone();
                            caches.open(CACHE_NAME_STATIC).then((cache) => {
                                cache.put(request, responseClone);
                            });
                        }
                        return networkResponse;
                    })
                    .catch(() => {
                        // Gagal fetch background tidak masalah jika cache ada
                    });

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // C. Default: Network dengan Cache Fallback
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});
