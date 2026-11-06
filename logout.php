<?php
require_once __DIR__ . '/config/security.php';
start_secure_session();

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

