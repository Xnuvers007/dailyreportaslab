<?php
/**
 * Modul Autentikasi Pengguna & Sesi Aslab
 */
require_once __DIR__ . '/security.php';
start_secure_session();

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login($message = "Sistem bersifat tertutup. Silakan login terlebih dahulu.") {
    if (!is_logged_in()) {
        if ($message && empty($_SESSION['flash_message'])) {
            set_flash('info', $message);
        }
        $current_script = basename($_SERVER['PHP_SELF'] ?? 'index.php');
        $query_str = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        $redirect = $current_script . $query_str;
        if ($redirect === 'index.php') {
            header("Location: login.php");
        } else {
            header("Location: login.php?redirect=" . urlencode($redirect));
        }
        exit;
    }
}

function is_admin() {
    return is_logged_in() && (($_SESSION['role'] ?? '') === 'admin');
}

function require_admin() {
    require_login("Silakan login sebagai admin untuk mengakses halaman ini.");
    if (!is_admin()) {
        set_flash('danger', "Akses ditolak. Halaman ini hanya dapat diakses oleh Administrator.");
        header("Location: index.php");
        exit;
    }
}

function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id'           => $_SESSION['user_id'],
        'username'     => $_SESSION['username'] ?? '',
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'role'         => $_SESSION['role'] ?? 'aslab'
    ];
}

function set_flash($type, $message) {
    $_SESSION['flash_message'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

