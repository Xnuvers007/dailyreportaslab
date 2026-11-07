<?php
/**
 * ROUTER SCRIPT UNTUK PHP BUILT-IN SERVER (`php -S localhost:8000 router.php`)
 * Memberikan perlindungan keamanan setara .htaccess / Nginx saat menjalankan server lokal / Cloudflare Tunnel.
 */

$uri_path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 1. BLOKIR TOTAL AKSES LANGSUNG KE DIREKTORI SENSITIF
// Mencegah publik melihat isi folder uploads, storage, config, includes
if (preg_match('#^/(uploads|storage|config|includes)(/|$)#i', $uri_path)) {
    http_response_code(403);
    header("Content-Type: text/plain; charset=utf-8");
    exit("403 Forbidden: Akses langsung ke direktori ini diblokir demi keamanan data privasi lab.");
}

// 2. BLOKIR FILE DATABASE, DEPLOYMENT, & KONFIGURASI SENSITIF
if (preg_match('#(^/(router\.php|Dockerfile|docker-compose\.yml|\.dockerignore|\.gitignore|\.env.*)|\.(sql|sqlite|db|env|ini|log|sh|bak|md|git.*|htaccess|config|yml|yaml|example))$#i', $uri_path)) {
    http_response_code(404);
    header("Content-Type: text/plain; charset=utf-8");
    exit("404 Not Found.");
}

// 3. SAJIKAN ASET STATIS PUBLIK YANG AMAN (Folder assets/: CSS, JS, Gambar UI, Font)
if (preg_match('#^/assets/#i', $uri_path)) {
    $asset_file = __DIR__ . $uri_path;
    if (is_file($asset_file)) {
        return false; // Serahkan ke internal web server untuk melayani file statis
    }
}

// 4. JIKA FILE PHP SPESIFIK DIMINTA
$target_file = __DIR__ . $uri_path;
if ($uri_path !== '/' && is_file($target_file) && pathinfo($target_file, PATHINFO_EXTENSION) === 'php') {
    require $target_file;
    exit;
}

// 5. DEFAULT KE INDEX
require __DIR__ . '/index.php';

