<?php
/**
 * GATEKEEPER FOTO DOKUMENTASI TEROTENTIKASI (ANTI-UNAUTHORIZED DATA EXPOSURE)
 * Memastikan foto di folder uploads/ hanya bisa dibuka oleh pengguna yang sudah login.
 * Mencegah direct-access, path traversal, dan kebocoran data privasi lab.
 */

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

// 1. Wajib login untuk melihat foto dokumentasi
if (!is_logged_in()) {
    http_response_code(403);
    die("Akses ditolak. Silakan login terlebih dahulu untuk melihat foto dokumentasi.");
}

// 2. Ambil parameter file
$file = trim($_GET['file'] ?? $_GET['f'] ?? '');
if (empty($file)) {
    http_response_code(400);
    die("Parameter file tidak ditentukan.");
}

// 3. Sanitasi Nama File & Cegah Path Traversal (Server-Agnostic Storage Support)
$safe_filename = basename($file);
$uploads_dir   = get_uploads_directory();
$target_path   = $uploads_dir . DIRECTORY_SEPARATOR . $safe_filename;
$real_target   = realpath($target_path);

// Fallback jika file masih di direktori uploads lama
if (!$real_target || !is_file($real_target)) {
    $legacy_dir  = realpath(__DIR__ . '/uploads');
    $legacy_path = $legacy_dir ? ($legacy_dir . DIRECTORY_SEPARATOR . $safe_filename) : null;
    $real_legacy = $legacy_path ? realpath($legacy_path) : false;
    if ($real_legacy && $legacy_dir && strpos($real_legacy, $legacy_dir) === 0 && is_file($real_legacy)) {
        $real_target = $real_legacy;
        $uploads_dir = $legacy_dir;
    }
}

// Validasi keberadaan file dan pastikan berada strictly di dalam direktori penyimpanan yang sah
if (!$real_target || !$uploads_dir || strpos($real_target, $uploads_dir) !== 0 || !is_file($real_target)) {
    http_response_code(404);
    die("Foto dokumentasi tidak ditemukan.");
}

// 4. Deteksi MIME Type Nyata (Hanya izinkan gambar aman)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $real_target);
finfo_close($finfo);

$allowed_mimes = [
    'image/jpeg' => 'image/jpeg',
    'image/png'  => 'image/png',
    'image/webp' => 'image/webp'
];

if (!isset($allowed_mimes[$mime_type])) {
    http_response_code(403);
    die("Format file tidak diizinkan.");
}

// 5. Kirim Security Headers & Sajikan File
session_write_close(); // Lepas session lock agar request halaman lain tidak terblokir saat file disajikan
header("Content-Type: " . $mime_type);
header("Content-Length: " . filesize($real_target));
header("Content-Disposition: inline; filename=\"" . $safe_filename . "\"");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, max-age=86400"); // Cache 1 hari untuk user terotentikasi
header("Pragma: private");

// Bersihkan output buffer sebelum kirim file biner
if (ob_get_level()) {
    ob_end_clean();
}

readfile($real_target);
exit;
