<?php
/**
 * PWA Manifest Gateway & Dynamic Controller
 * Memastikan manifest PWA selalu disajikan dengan header Content-Type application/manifest+json
 * dan kebal dari restriksi static file hosting gratis (InfinityFree).
 */
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($origin)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
}

$manifest_path = __DIR__ . '/manifest.json';
if (file_exists($manifest_path) && is_readable($manifest_path)) {
    readfile($manifest_path);
} else {
    // Fallback embedded manifest jika file fisik tidak sengaja terhapus
    echo json_encode([
        'name' => 'AslabReport - Universitas Pamulang',
        'short_name' => 'AslabReport',
        'description' => 'Sistem Monitoring Inventaris & Laporan Harian Lab Komputer UNPAM',
        'start_url' => 'index.php',
        'scope' => './',
        'display' => 'standalone',
        'orientation' => 'portrait-primary',
        'background_color' => '#0f172a',
        'theme_color' => '#ea580c',
        'icons' => [
            ['src' => 'assets/img/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => 'assets/img/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png']
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
exit;
