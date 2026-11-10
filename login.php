<?php
$page_title = "Login Aslab";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/logger.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = null;
$flash = get_flash();

// Validasi Redirect Aman (Anti-Open Redirect dengan Reconstruct Whitelist Ketat)
$redirect_input = (string)($_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php');
$allowed_targets = ['index.php', 'buat-laporan.php', 'riwayat.php', 'detail.php', 'kelola-aslab.php', 'log-aktivitas.php'];
$parsed_url = parse_url($redirect_input);
$base_target = basename($parsed_url['path'] ?? 'index.php');

if (!in_array($base_target, $allowed_targets, true)) {
    $redirect = 'index.php';
    $base_target = 'index.php';
} else {
    // Bangun URL lokal yang aman secara eksplisit, buang semua skema, host, dan awalan path eksternal
    $query_part = !empty($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $redirect = $base_target . $query_part;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // 1. Validasi CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (!validate_csrf_token($csrf_token)) {
        $error = "Token sesi keamanan tidak valid. Silakan coba kembali.";
        // PERBAIKAN VULNERABILITY (Anti-Account Lockout DoS via CSRF):
        // Jangan asosiasikan input username dari request yang token CSRF-nya tidak valid ke akun pengguna.
        // Ini mencegah penyerang mengunci akun korban dengan spamming invalid CSRF token.
        record_log('LOGIN_CSRF_FAILED', "Percobaan login ditolak: Token CSRF tidak valid dari IP " . get_client_ip(), 'DANGER', 'Tamu (CSRF Mismatch)');
    } else {
        // Delay kecil untuk mitigasi timing attack
        usleep(150000); // 150ms

        if (empty($username) || empty($password)) {
            $error = "Silakan masukkan username dan password.";
        } else {
            // RATE LIMITING & BRUTE-FORCE PROTECTION (OWASP ASVS & NIST 800-63B Compliant)
            // Mencegah Brute-Force Password Guessing sekaligus Mencegah Account Lockout DoS terhadap pengguna yang sah
            $client_ip = get_client_ip();
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $time_sql = ($driver === 'sqlite') 
                ? "created_at >= datetime('now', '-15 minutes')" 
                : "created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)";

            // A. Rate Limiting Tingkat IP (Mencegah bot, flood, credential stuffing, & DDoS)
            // Batasi maksimal 15 kegagalan dari IP yang sama dalam 15 menit
            $stmt_ip_rate = $pdo->prepare("SELECT COUNT(*) FROM `activity_logs` 
                WHERE `ip_address` = :ip 
                AND `action` IN ('LOGIN_FAILED', 'LOGIN_CSRF_FAILED') 
                AND {$time_sql}");
            $stmt_ip_rate->execute([':ip' => $client_ip]);
            $ip_failed_attempts = (int)$stmt_ip_rate->fetchColumn();

            // Progressive Delay (Jeda Progresif): Memperlambat serangan otomatis secara eksponensial seiring bertambahnya kegagalan dari IP ini
            if ($ip_failed_attempts > 2) {
                usleep(min(1000000, 150000 + ($ip_failed_attempts * 100000))); // 0.35s s/d 1s delay
            }

            // B. Rate Limiting Kombinasi IP + Akun (Anti-Brute Force Spesifik Tanpa Mengunci Akun Secara Global)
            // Sesuai rekomendasi OWASP & NIST: Penguncian diterapkan pada kombinasi IP Pengakses + Akun Target.
            // Dengan cara ini, jika penyerang sengaja salah menebak 5x untuk akun korban, yang dibatasi HANYA koneksi penyerang.
            // Pemilik akun asli dari koneksi/IP lain TIDAK AKAN ter-lockout (Mencegah Denial of Service terhadap korban).
            $stmt_pair_rate = $pdo->prepare("SELECT COUNT(*) FROM `activity_logs` 
                WHERE `ip_address` = :ip 
                AND `username` = :uname 
                AND `action` = 'LOGIN_FAILED' 
                AND {$time_sql}");
            $stmt_pair_rate->execute([':ip' => $client_ip, ':uname' => $username]);
            $pair_failed_attempts = (int)$stmt_pair_rate->fetchColumn();

            $max_pair_attempts = 5;
            $max_ip_attempts   = 15;

            if ($ip_failed_attempts >= $max_ip_attempts) {
                $error = "Terlalu banyak percobaan masuk dari koneksi/IP Anda ({$ip_failed_attempts}x). Akses login dari koneksi Anda dibatasi sementara demi keamanan. Silakan coba kembali dalam 15 menit.";
                record_log('LOGIN_RATE_LIMITED', "Akses login IP {$client_ip} dibatasi oleh Rate Limiter ({$ip_failed_attempts}x percobaan gagal)", 'DANGER', $username);
            } elseif ($pair_failed_attempts >= $max_pair_attempts) {
                $error = "Terlalu banyak percobaan kata sandi salah untuk akun ini dari koneksi Anda ({$pair_failed_attempts}x). Akses login ke akun ini dari perangkat/koneksi Anda dibatasi sementara demi keamanan. Silakan coba kembali dalam 15 menit.";
                record_log('LOGIN_RATE_LIMITED', "Percobaan login akun '{$username}' dari IP {$client_ip} dibatasi ({$pair_failed_attempts}x password salah)", 'DANGER', $username);
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :username LIMIT 1");
                    $stmt->execute([':username' => $username]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password_hash'])) {
                        // Login Berhasil - Regenerasi ID sesi untuk mencegah Session Fixation
                        session_regenerate_id(true);
                        
                        // Perbarui token CSRF untuk konteks sesi terotentikasi (OWASP CSRF Token Rotation)
                        $_SESSION['csrf_token']          = bin2hex(random_bytes(32));
                        $_SESSION['user_id']             = $user['id'];
                        $_SESSION['username']            = $user['username'];
                        $_SESSION['nama_lengkap']        = $user['nama_lengkap'];
                        $_SESSION['role']                = $user['role'];
                        $_SESSION['auth_hash_signature'] = substr($user['password_hash'], 0, 16);

                        record_log('LOGIN_SUCCESS', "Login berhasil sebagai '{$user['nama_lengkap']}' ({$user['role']})", 'SUCCESS', $user['username']);

                        // Role-Based Redirect Guard (Defense-in-Depth):
                        // Jika akun bukan admin tetapi mencoba redirect ke halaman admin, arahkan ke index.php
                        $admin_only_pages = ['kelola-aslab.php', 'log-aktivitas.php'];
                        if ($user['role'] !== 'admin' && in_array($base_target, $admin_only_pages, true)) {
                            $redirect = 'index.php';
                        }

                        set_flash('success', "Selamat datang kembali, " . $user['nama_lengkap'] . "!");
                        header("Location: " . $redirect);
                        exit;
                    } else {
                        $sisa = max(0, $max_pair_attempts - ($pair_failed_attempts + 1));
                        $peringatan_sisa = ($sisa > 0) ? " Sisa percobaan dari koneksi ini: {$sisa}x." : "";
                        $error = "Username atau password yang Anda masukkan salah." . $peringatan_sisa;
                        record_log('LOGIN_FAILED', "Percobaan login gagal (password salah) untuk username '{$username}'", 'WARNING', $username);
                    }
                } catch (PDOException $e) {
                    error_log("Login DB Error: " . $e->getMessage());
                    $error = "Terjadi gangguan sistem. Silakan coba kembali nanti.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
    <!-- Primary Meta Tags -->
    <title>Login Aslab - Laporan Harian UNPAM</title>
    <meta name="title" content="Login Aslab - Laporan Harian UNPAM">
    <meta name="description" content="Portal Otentikasi Asisten Laboratorium Komputer Universitas Pamulang (UNPAM). Masuk untuk input rekapitulasi inventaris dan catatan operasional shift.">
    <meta name="keywords" content="aslab unpam, login aslab, universitas pamulang, unpam, lab komputer unpam, asisten laboratorium">
    <meta name="author" content="Laboratorium Komputer Universitas Pamulang">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Open Graph & Twitter -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Login Aslab - Laporan Harian UNPAM">
    <meta property="og:description" content="Portal Otentikasi Asisten Laboratorium Komputer Universitas Pamulang.">
    <meta property="og:image" content="assets/img/logo-unpam.png">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:image" content="assets/img/logo-unpam.png">
    
    <!-- Favicon & PWA Settings -->
    <?php include __DIR__ . '/includes/pwa-manifest-script.php'; ?>
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/icons/icon-72.png">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/img/icons/icon-192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/icons/apple-touch-icon.png">
    <link rel="shortcut icon" href="favicon.ico">
    <meta name="theme-color" content="#ea580c">
    <meta name="application-name" content="AslabReport">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="AslabReport">
    
    <!-- Script Anti-Flicker Dark/Light Theme -->
    <script>
        (function() {
            const saved = localStorage.getItem('aslab_theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-bs-theme', saved || (prefersDark ? 'dark' : 'light'));
        })();
    </script>

    <!-- Bootstrap 5 CSS (Local Vendor) -->
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- FontAwesome 6 (Local Vendor) -->
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <!-- Modular Custom CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center position-relative" style="min-height: 100vh;">

    <!-- 3D Interactive Three.js Canvas Background -->
    <canvas id="bg-canvas"></canvas>

    <!-- Floating Theme Toggle (Top Right) -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 20;">
        <button class="theme-toggle-btn shadow" type="button" title="Ganti Mode Gelap / Terang">
            <i class="fa-solid fa-moon theme-icon"></i>
        </button>
    </div>

    <!-- Login Container -->
    <div class="container position-relative animate-fade-up" style="max-width: 440px; z-index: 10;">
        
        <div class="text-center mb-2">
            <div class="mb-2">
                <img src="assets/img/logo-unpam.png" alt="Logo Universitas Pamulang" class="img-fluid floating-icon" style="height: 64px; width: auto; object-fit: contain; filter: drop-shadow(0 4px 14px rgba(249, 115, 22, 0.35));">
            </div>
            <h4 class="fw-bold mb-1 text-main">AslabReport</h4>
            <p class="text-muted small mb-0 fw-semibold" style="letter-spacing: 0.03em;">UNIVERSITAS PAMULANG</p>
            <p class="text-muted small mb-0" style="font-size: 0.78rem;">Sistem Informasi Inventaris & Laporan Harian Lab</p>
        </div>

        <!-- 3D Interactive Mascot (Three.js Companion) -->
        <div class="login-avatar-container text-center position-relative" style="height: 200px; margin-bottom: -22px; z-index: 12; pointer-events: none;">
            <canvas id="login-avatar-canvas" style="width: 100%; height: 100%; display: block; outline: none;"></canvas>
        </div>

        <div class="glass-card shadow-lg">
            <div class="card-body p-4 p-sm-5">
                
                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 small py-2 mb-3" role="alert">
                        <i class="fa-solid fa-circle-info"></i>
                        <div><?= e($flash['message']) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 small py-2 mb-3" role="alert">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <?= csrf_input() ?>
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold">Username Aslab</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus autocomplete="username">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" title="Lihat / Sembunyikan Password">
                                <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2 mt-4 shadow">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Masuk ke Sistem</span>
                    </button>
                </form>

                <div class="p-3 bg-body-tertiary rounded mt-4 border text-center">
                    <div class="small text-muted">
                        <i class="fa-solid fa-lock text-primary me-1"></i>
                        Portal Resmi Internal Asisten Lab UNPAM.<br>
                        Gunakan akun yang telah didaftarkan oleh Administrator.
                    </div>
                </div>

            </div>
        </div>

        <div class="text-center mt-4 text-muted small">
            <div>
                <i class="fa-solid fa-shield-halved me-1 text-success"></i> 
                Sistem tertutup. Pendaftaran akun aslab hanya melalui <strong>Administrator</strong>.
            </div>
        </div>

    </div>

    <!-- Bootstrap 5 Bundle JS (Local Vendor) -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Three.js Library (Local Vendor) -->
    <script src="assets/vendor/three/three.min.js"></script>
    <!-- Modular App Scripts -->
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/animations.js"></script>
    <script src="assets/js/background3d.js"></script>
    <script src="assets/js/login-avatar3d.js"></script>
    <script src="assets/js/pwa.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
