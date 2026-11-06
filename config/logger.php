<?php
/**
 * ASLAB REPORT - ACTIVITY & AUDIT LOGGER
 * Mencatat semua aktivitas login, navigasi, modifikasi data, dan ekspor.
 * Otomatis menghapus data log yang berumur lebih dari 30 hari (30-day auto retention).
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Dapatkan IP Address Klien secara aman (Anti-IP Spoofing & Mendukung Reverse Proxy)
 * Mencegah pemalsuan header X-Forwarded-For dari koneksi publik langsung.
 */
function get_client_ip() {
    $remote_addr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // Periksa apakah koneksi berasal dari proxy internal / trusted proxy (localhost atau private network)
    $trusted_loopbacks = ['127.0.0.1', '::1'];
    $is_from_proxy = in_array($remote_addr, $trusted_loopbacks, true)
        || filter_var($remote_addr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

    if ($is_from_proxy) {
        $proxy_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP'
        ];

        foreach ($proxy_headers as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $forwarded_ip = trim($ips[0]);
                if (filter_var($forwarded_ip, FILTER_VALIDATE_IP)) {
                    return $forwarded_ip;
                }
            }
        }
    }

    if (filter_var($remote_addr, FILTER_VALIDATE_IP)) {
        return $remote_addr;
    }

    return '127.0.0.1';
}

/**
 * Catat aktivitas ke tabel `activity_logs`
 * 
 * @param string $action      Contoh: 'LOGIN_SUCCESS', 'LOGIN_FAILED', 'CREATE_REPORT', 'VIEW_PAGE', 'EXPORT_PDF'
 * @param string $description Penjelasan detail aktivitas
 * @param string $status      'SUCCESS', 'WARNING', 'DANGER', 'INFO'
 * @param string|null $override_user Username manual (berguna saat login gagal)
 */
function record_log($action, $description, $status = 'INFO', $override_user = null) {
    global $pdo;

    if (!$pdo) return false;

    try {
        $user = current_user();
        $user_id = $user ? (int)$user['id'] : null;
        $username = $override_user ?: ($user ? $user['username'] : 'Tamu (Guest)');
        
        $ip_address = get_client_ip();
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser', 0, 255);
        $url_path   = substr($_SERVER['REQUEST_URI'] ?? '', 0, 255);
        $method     = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $stmt = $pdo->prepare("INSERT INTO `activity_logs` 
            (`user_id`, `username`, `action`, `description`, `ip_address`, `user_agent`, `url_path`, `method`, `status`) 
            VALUES (:uid, :uname, :action, :desc, :ip, :ua, :url, :method, :status)");
        
        $stmt->execute([
            ':uid'     => $user_id,
            ':uname'   => $username,
            ':action'  => strtoupper(trim($action)),
            ':desc'    => trim($description),
            ':ip'      => $ip_address,
            ':ua'      => $user_agent,
            ':url'     => $url_path,
            ':method'  => $method,
            ':status'  => strtoupper($status)
        ]);

        // 30-Day Auto Retention: Hapus log yang lebih tua dari 30 hari secara berkala (1 in 5 requests)
        if (mt_rand(1, 5) === 1) {
            purge_old_logs();
        }

        return true;
    } catch (Exception $e) {
        error_log("Logging Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Hapus log yang berumur lebih dari 30 hari
 */
function purge_old_logs() {
    global $pdo;
    if (!$pdo) return;

    try {
        // Cek driver database (MySQL vs SQLite)
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $pdo->exec("DELETE FROM `activity_logs` WHERE `created_at` < datetime('now', '-30 days')");
        } else {
            $pdo->exec("DELETE FROM `activity_logs` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        }
    } catch (Exception $e) {
        error_log("Purge Logs Error: " . $e->getMessage());
    }
}

