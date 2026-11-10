/**
 * ASLAB REPORT - PROGRESSIVE WEB APP (PWA) SERVICE WORKER
 * Versi: v1.1.0
 * Strategi Keamanan & Kinerja:
 * - Pre-cache Static Assets (Vendor CSS, JS, Three.js, Fonts, Icons, offline.html)
 * - Stale-While-Revalidate murni untuk Static Assets (Super Cepat & Selalu Update)
 * - Network-Only dengan Fallback ke offline.html untuk Navigasi Halaman Dinamis PHP
 * - KEAMANAN KOMPUTER BERSAMA (OWASP Anti-Session Exposure):
 *   TIDAK PERNAH menyimpan salinan respons halaman PHP dinamis atau data laporan terautentikasi ke cache browser.
 */

const CACHE_NAME_STATIC = 'aslab-static-v1.1.1';

// Aset Inti yang di-precache saat instalasi (Murni aset statis & halaman offline umum)
const PRECACHE_ASSETS = [
    './offline.html',
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

// 2. ACTIVATE EVENT (Pembersihan Semua Cache Lama & Runtime Cache Pengguna)
self.addEventListener('activate', (event) => {
    const allowedCaches = [CACHE_NAME_STATIC];
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (!allowedCaches.includes(cacheName)) {
                        console.log('[PWA ServiceWorker] Menghapus cache lama/runtime:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// 3. MESSAGE EVENT (Penanganan Perintah Pembersihan Sesi dari Client)
self.addEventListener('message', (event) => {
    if (event.data && (event.data.action === 'CLEAR_USER_CACHE' || event.data.action === 'LOGOUT')) {
        caches.keys().then((cacheNames) => {
            cacheNames.forEach((name) => {
                if (name !== CACHE_NAME_STATIC) {
                    caches.delete(name);
                }
            });
        });
    }
});

// 4. FETCH EVENT
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Hanya tangani GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Bypass ServiceWorker untuk streaming otentikasi foto privat, file unduhan, & aksi logout
    if (
        url.pathname.includes('lihat-foto.php') ||
        url.pathname.includes('logout.php') ||
        url.pathname.includes('export-laporan.php')
    ) {
        return;
    }

    // A. Strategi Navigasi Halaman PHP / HTML (Network-Only dengan Fallback ke offline.html)
    // KEAMANAN KOMPUTER BERSAMA (Anti-Session Exposure on Shared Devices):
    // Jangan pernah menyimpan respons halaman PHP dinamis atau data laporan ke cache runtime.
    // Jika offline, sajikan halaman aman offline.html tanpa membocorkan data sesi pengguna sebelumnya.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('./offline.html'))
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

    // C. Default: Network dengan Fallback Offline
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});
