<?php
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/logger.php';

start_secure_session();

// Validasi CSRF Token untuk mencegah serangan Logout CSRF (Cross-Site Request Forgery)
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
if (!validate_csrf_token($token)) {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
    set_flash('danger', "Permintaan logout ditolak: Token CSRF tidak valid atau telah kedaluwarsa.");
    header("Location: index.php");
    exit;
}

// Catat log sebelum sesi dihancurkan
$user = current_user();
if ($user) {
    record_log('LOGOUT', "Pengguna '{$user['nama_lengkap']}' ({$user['username']}) logout dari sistem", 'INFO');
}

// Kosongkan array sesi
$_SESSION = [];

// Hapus cookie sesi dari browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan sesi di server
session_destroy();

// Mulai sesi baru untuk pesan flash
start_secure_session();
require_once __DIR__ . '/config/auth.php';
set_flash('info', "Anda telah berhasil logout.");

header("Location: login.php");
exit;

