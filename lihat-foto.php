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

// 3. Sanitasi Nama File & Cegah Path Traversal
$safe_filename = basename($file);
$uploads_dir   = realpath(__DIR__ . '/uploads');
$target_path   = __DIR__ . '/uploads/' . $safe_filename;
$real_target   = realpath($target_path);

// Validasi keberadaan file dan pastikan berada strictly di dalam direktori uploads/
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
