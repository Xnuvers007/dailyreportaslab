<?php
$page_title = "Login Aslab";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = null;
$flash = get_flash();

// Validasi Redirect Aman (Anti-Open Redirect)
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php';
if (preg_match('/^(https?:|\/\/)/i', $redirect) || !preg_match('/^[a-zA-Z0-9_\-\.\?=&]+$/', $redirect)) {
    $redirect = 'index.php';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // 1. Validasi CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $error = "Token sesi keamanan tidak valid. Silakan coba kembali.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        // Delay kecil untuk mitigasi brute force timing attack
        usleep(150000); // 150ms

        if (empty($username) || empty($password)) {
            $error = "Silakan masukkan username dan password.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :username LIMIT 1");
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Login Berhasil - Regenerasi ID sesi untuk mencegah Session Fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id']      = $user['id'];
                    $_SESSION['username']     = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['role']         = $user['role'];

                    set_flash('success', "Selamat datang kembali, " . $user['nama_lengkap'] . "!");
                    header("Location: " . $redirect);
                    exit;
                } else {
                    $error = "Username atau password yang Anda masukkan salah.";
                }
            } catch (PDOException $e) {
                error_log("Login DB Error: " . $e->getMessage());
                $error = "Terjadi gangguan sistem. Silakan coba kembali nanti.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Laporan Harian Aslab</title>
    
    <!-- Script Anti-Flicker Dark/Light Theme -->
    <script>
        (function() {
            const saved = localStorage.getItem('aslab_theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-bs-theme', saved || (prefersDark ? 'dark' : 'light'));
        })();
    </script>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        
        <div class="text-center mb-4">
            <div class="stat-icon-box bg-primary text-white mx-auto mb-3 shadow floating-icon" style="width: 60px; height: 60px; font-size: 1.6rem; border-radius: 1rem;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <h4 class="fw-bold mb-1 text-main">AslabReport</h4>
            <p class="text-muted small mb-0">Sistem Informasi Inventaris & Laporan Harian</p>
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
                            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2 mt-4 shadow">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Masuk ke Sistem</span>
                    </button>
                </form>

                <div class="p-3 bg-body-tertiary rounded mt-4 border">
                    <div class="small fw-semibold text-secondary mb-1">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i> Akun Bawaan (Default):
                    </div>
                    <div class="small text-muted font-monospace">
                        Username: <strong>aslab</strong><br>
                        Password: <strong>admin123</strong>
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

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Three.js Library via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <!-- Modular App Scripts -->
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/animations.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
