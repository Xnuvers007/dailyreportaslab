<?php
/**
 * Modul Autentikasi Pengguna & Sesi Aslab
 */
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/database.php';
start_secure_session();

/**
 * Mengambil data pengguna yang terotentikasi saat ini dengan verifikasi real-time ke database.
 * Mencegah session zombie: jika akun telah dihapus oleh Admin atau password direset,
 * sesi langsung dicabut seketika (Immediate Session Revocation).
 */
function current_user() {
    global $pdo;
    static $cached_user = null;
    static $has_checked = false;

    if ($has_checked) {
        return $cached_user;
    }

    if (empty($_SESSION['user_id'])) {
        $has_checked = true;
        $cached_user = null;
        return null;
    }

    // Verifikasi real-time ke database: pastikan akun masih aktif & belum dihapus
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT `id`, `username`, `nama_lengkap`, `role`, `password_hash` FROM `users` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $db_user = $stmt->fetch();

            if (!$db_user) {
                // Akun telah dihapus oleh Admin! Cabut sesi seketika (Immediate Session Revocation)
                session_unset();
                session_destroy();
                $has_checked = true;
                $cached_user = null;
                return null;
            }

            // Verifikasi Password Signature: Jika password telah direset oleh Admin, cabut sesi lama
            $current_sig = substr($db_user['password_hash'], 0, 16);
            if (isset($_SESSION['auth_hash_signature']) && $_SESSION['auth_hash_signature'] !== $current_sig) {
                session_unset();
                session_destroy();
                $has_checked = true;
                $cached_user = null;
                return null;
            }

            // Sinkronisasi data sesi terkini dari database secara real-time (misal perubahan role)
            $_SESSION['role']         = $db_user['role'];
            $_SESSION['nama_lengkap'] = $db_user['nama_lengkap'];
            $_SESSION['username']     = $db_user['username'];
            if (!isset($_SESSION['auth_hash_signature'])) {
                $_SESSION['auth_hash_signature'] = $current_sig;
            }

            $has_checked = true;
            $cached_user = [
                'id'           => (int)$db_user['id'],
                'username'     => $db_user['username'],
                'nama_lengkap' => $db_user['nama_lengkap'],
                'role'         => $db_user['role']
            ];
            return $cached_user;
        } catch (Exception $e) {
            error_log("Auth verification error: " . $e->getMessage());
        }
    }

    // Fallback jika database belum terhubung
    $has_checked = true;
    $cached_user = [
        'id'           => (int)$_SESSION['user_id'],
        'username'     => $_SESSION['username'] ?? '',
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'role'         => $_SESSION['role'] ?? 'aslab'
    ];
    return $cached_user;
}

function is_logged_in() {
    return current_user() !== null;
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
    $u = current_user();
    return $u !== null && ($u['role'] === 'admin');
}

function require_admin() {
    require_login("Silakan login sebagai admin untuk mengakses halaman ini.");
    if (!is_admin()) {
        set_flash('danger', "Akses ditolak. Halaman ini hanya dapat diakses oleh Administrator.");
        header("Location: index.php");
        exit;
    }
}

function set_flash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        start_secure_session();
    }
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
