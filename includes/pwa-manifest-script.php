<!-- PWA Dynamic Manifest Blob Generator (Bypass Firewall Anti-Bot/Cookie InfinityFree) -->
<script>
(function() {
    try {
        // Tentukan base URL absolut dari halaman aktif agar resolusi di dalam Blob URL valid
        const baseDir = new URL('.', window.location.href).href;
        const resolve = function(rel) {
            return new URL(rel, baseDir).href;
        };

        const manifestData = {
            "name": "AslabReport - Universitas Pamulang",
            "short_name": "AslabReport",
            "description": "Sistem Informasi Monitoring Inventaris & Laporan Harian Laboratorium Komputer Universitas Pamulang",
            "start_url": resolve('index.php'),
            "scope": resolve('./'),
            "display": "standalone",
            "orientation": "portrait-primary",
            "background_color": "#0f172a",
            "theme_color": "#ea580c",
            "lang": "id-ID",
            "dir": "ltr",
            "categories": ["education", "productivity", "utilities"],
            "icons": [
                { "src": resolve('assets/img/icons/icon-72.png'), "sizes": "72x72", "type": "image/png", "purpose": "any" },
                { "src": resolve('assets/img/icons/icon-96.png'), "sizes": "96x96", "type": "image/png", "purpose": "any" },
                { "src": resolve('assets/img/icons/icon-128.png'), "sizes": "128x128", "type": "image/png", "purpose": "any" },
                { "src": resolve('assets/img/icons/icon-144.png'), "sizes": "144x144", "type": "image/png", "purpose": "any" },
                { "src": resolve('assets/img/icons/icon-152.png'), "sizes": "152x152", "type": "image/png", "purpose": "any" },
                { "src": resolve('assets/img/icons/icon-192.png'), "sizes": "192x192", "type": "image/png", "purpose": "any" },
                { "src": resolve('assets/img/icons/icon-384.png'), "sizes": "384x384", "type": "image/png", "purpose": "any" },
                { "src": resolve('assets/img/icons/icon-512.png'), "sizes": "512x512", "type": "image/png", "purpose": "any" },
                { "src": resolve('assets/img/icons/icon-maskable-192.png'), "sizes": "192x192", "type": "image/png", "purpose": "maskable" },
                { "src": resolve('assets/img/icons/icon-maskable-512.png'), "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
            ],
            "shortcuts": [
                {
                    "name": "Input Laporan Harian",
                    "short_name": "Input Laporan",
                    "description": "Form input rekapitulasi inventaris shift baru",
                    "url": resolve('buat-laporan.php'),
                    "icons": [{ "src": resolve('assets/img/icons/icon-96.png'), "sizes": "96x96", "type": "image/png" }]
                },
                {
                    "name": "Riwayat Arsip Laporan",
                    "short_name": "Riwayat",
                    "description": "Lihat arsip rekapitulasi shift sebelumnya",
                    "url": resolve('riwayat.php'),
                    "icons": [{ "src": resolve('assets/img/icons/icon-96.png'), "sizes": "96x96", "type": "image/png" }]
                },
                {
                    "name": "Dashboard Utama",
                    "short_name": "Dashboard",
                    "description": "Ringkasan statistik laboratorium",
                    "url": resolve('index.php'),
                    "icons": [{ "src": resolve('assets/img/icons/icon-96.png'), "sizes": "96x96", "type": "image/png" }]
                }
            ],
            "prefer_related_applications": false
        };

        const blob = new Blob([JSON.stringify(manifestData)], { type: 'application/manifest+json' });
        const manifestURL = URL.createObjectURL(blob);
        let link = document.querySelector('link[rel="manifest"]');
        if (!link) {
            link = document.createElement('link');
            link.rel = 'manifest';
            document.head.appendChild(link);
        }
        link.href = manifestURL;
    } catch (e) {
        console.warn('[PWA] Dynamic manifest error:', e);
    }
})();
</script>
