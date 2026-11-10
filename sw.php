<?php
/**
 * PWA Service Worker Gateway
 * Menyajikan Service Worker melalui PHP agar tidak dicegat atau dirusak oleh sistem firewall/proxy hosting gratis (InfinityFree).
 */
header('Content-Type: application/javascript; charset=utf-8');
header('Service-Worker-Allowed: /');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($origin)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
}

$sw_path = __DIR__ . '/sw.js';
if (file_exists($sw_path) && is_readable($sw_path)) {
    readfile($sw_path);
} else {
    http_response_code(404);
    echo "console.error('[PWA ServiceWorker] File sw.js tidak ditemukan di server.');";
}
exit;

