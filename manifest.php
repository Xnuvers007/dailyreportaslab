<?php
/**
 * PWA Manifest Gateway & Fallback Controller
 * Memastikan manifest PWA selalu disajikan dengan header Content-Type application/manifest+json
 * dan kebal dari restriksi static file hosting gratis (InfinityFree).
 */
header('Content-Type: application/manifest+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Cache-Control: public, max-age=86400');

$manifest_path = __DIR__ . '/manifest.json';
if (file_exists($manifest_path) && is_readable($manifest_path)) {
    readfile($manifest_path);
} else {
    http_response_code(404);
    echo json_encode([
        'error' => 'manifest.json tidak ditemukan di direktori root server.'
    ]);
}
exit;
