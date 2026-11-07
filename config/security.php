<?php
/**
 * Modul Keamanan: Anti-XSS, Anti-CSRF, Anti-RCE, & Secure File Upload
 */

// Kirim Security Headers standar
function send_security_headers() {
    if (!headers_sent()) {
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
}

// Inisialisasi Session Aman (Anti-Session Fixation & Anti-Session Hijacking)
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        
        // Mencegah Session Fixation: server menolak session ID inisiasi klien yang belum ada
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');

        session_set_cookie_params([
            'lifetime' => 86400, // 1 hari batas cookie
            'path'     => '/',
            'domain'   => '',
            'secure'   => $is_https,
            'httponly' => true,      // Mencegah cookie dicuri via JavaScript XSS
            'samesite' => 'Lax'      // Perlindungan dasar CSRF pada cookie
        ]);
        
        session_start();

        // Idle Session Timeout (2 Jam) untuk keamanan komputer bersama di laboratorium
        $idle_timeout = 7200; // 2 jam
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $idle_timeout)) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
    }
    send_security_headers();
}

/**
 * Anti-XSS Helper: Escape HTML dengan aman
 */
function e($string) {
    if ($string === null) return '';
    return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Anti-Formula / CSV Injection Helper (OWASP CWE-1236 Compliant)
 * Menetralisir karakter awalan rumus spreadsheet (=, +, -, @, \t, \r)
 * agar tidak dieksekusi sebagai perintah DDE / formula saat dibuka di Excel atau Google Sheets.
 */
function sanitize_csv_cell($value) {
    if ($value === null || $value === '') {
        return '';
    }
    $str = (string)$value;
    $dangerous_prefixes = ['=', '+', '-', '@', "\t", "\r"];
    if (in_array($str[0], $dangerous_prefixes, true)) {
        return "'" . $str;
    }
    return $str;
}

/**
 * Generate CSRF Token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate Input Hidden CSRF untuk Form HTML
 */
function csrf_input() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Validasi CSRF Token dengan timing-attack safe comparison
 */
function validate_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Dapatkan direktori penyimpanan upload foto secara aman dan server-agnostic.
 * Mendukung penyimpanan di luar web root via env UPLOADS_DIR atau folder storage/
 */
function get_uploads_directory() {
    if (getenv('UPLOADS_DIR') && is_dir(getenv('UPLOADS_DIR'))) {
        return realpath(getenv('UPLOADS_DIR'));
    }
    $private_storage = __DIR__ . '/../../storage/uploads';
    if (is_dir($private_storage)) {
        return realpath($private_storage);
    }
    return realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
}

/**
 * Secure Image Upload Handler (Anti-RCE, Anti-Extension Tampering, Anti-MIME Spoofing)
 *
 * @param array $file_array Elemen dari $_FILES['nama_input']
 * @param string|null $destination_dir Path folder tujuan (opsional, default via get_uploads_directory())
 * @return string|false Nama file baru yang aman, atau false/throw jika gagal
 */
function handle_secure_upload($file_array, $destination_dir = null) {
    if ($destination_dir === null) {
        $destination_dir = get_uploads_directory();
    }
    // 1. Cek apakah ada file yang di-upload
    if (!isset($file_array) || $file_array['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // Tidak ada file diupload (opsional)
    }

    if ($file_array['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Terjadi kesalahan saat mengunggah file. Kode error: " . (int)$file_array['error']);
    }

    // 2. Batasi ukuran file (Maksimal 5 MB)
    $max_bytes = 5 * 1024 * 1024;
    if ($file_array['size'] > $max_bytes) {
        throw new Exception("Ukuran foto terlalu besar. Maksimal 5 MB.");
    }

    // 3. Verifikasi MIME type nyata dari isi file (BUKAN dari header $_FILES['type'] yang bisa dimanipulasi)
    $tmp_path = $file_array['tmp_name'];
    if (!is_uploaded_file($tmp_path)) {
        throw new Exception("File tidak valid.");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_mime = finfo_file($finfo, $tmp_path);
    finfo_close($finfo);

    // Whitelist ketat hanya tipe gambar aman
    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!array_key_exists($real_mime, $allowed_mimes)) {
        throw new Exception("Format file tidak diizinkan. Hanya format JPG, PNG, dan WEBP yang diperbolehkan.");
    }

    // 4. Verifikasi integritas gambar menggunakan getimagesize (mencegah file palsu/polyglot payload)
    $image_info = @getimagesize($tmp_path);
    if ($image_info === false) {
        throw new Exception("File gambar rusak atau bukan format gambar valid.");
    }

    // 5. Generate nama file ACAK baru (Cryptographically Secure - 128 bit entropy)
    // Menghilangkan nama asli user sepenuhnya untuk mencegah path traversal (../), null byte, dan double extension (shell.php.jpg)
    $extension = $allowed_mimes[$real_mime];
    $safe_filename = 'doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(16)) . '.' . $extension;

    // Pastikan folder tujuan ada
    if (!is_dir($destination_dir)) {
        @mkdir($destination_dir, 0755, true);
    }

    $destination_path = rtrim($destination_dir, '/\\') . DIRECTORY_SEPARATOR . $safe_filename;

    // 6. Pindahkan file
    if (!move_uploaded_file($tmp_path, $destination_path)) {
        throw new Exception("Gagal menyimpan file ke direktori server.");
    }

    // Berikan permission aman
    @chmod($destination_path, 0644);

    return $safe_filename;
}

