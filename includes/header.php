<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/logger.php';

$user = current_user();
$flash = get_flash();
$current_page = basename($_SERVER['PHP_SELF']);

// Audit Trail: Catat otomatis fitur/halaman yang dibuka pengguna
$page_action_map = [
    'index.php'         => ['action' => 'NAVIGATE_DASHBOARD', 'desc' => 'Membuka Dashboard Inventaris Lab'],
    'riwayat.php'       => ['action' => 'NAVIGATE_RIWAYAT',   'desc' => 'Melihat Riwayat Arsip Laporan'],
    'buat-laporan.php'  => ['action' => 'NAVIGATE_INPUT',     'desc' => 'Membuka Form Input Laporan Harian'],
    'detail.php'        => ['action' => 'NAVIGATE_DETAIL',    'desc' => 'Melihat Detail Laporan ID #' . ((int)($_GET['id'] ?? 0))],
    'edit-laporan.php'  => ['action' => 'NAVIGATE_EDIT',      'desc' => 'Membuka Form Edit Laporan ID #' . ((int)($_GET['id'] ?? 0))],
    'kelola-aslab.php'  => ['action' => 'NAVIGATE_USERS',     'desc' => 'Membuka Halaman Kelola Akun Aslab'],
    'log-aktivitas.php' => ['action' => 'NAVIGATE_LOGS',      'desc' => 'Membuka Pusat Audit & Log Aktivitas'],
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($page_action_map[$current_page])) {
    $navInfo = $page_action_map[$current_page];
    record_log($navInfo['action'], $navInfo['desc'], 'INFO');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
    <!-- Primary Meta Tags -->
    <title><?= isset($page_title) ? e($page_title) . ' - ' : '' ?>Laporan Harian Aslab UNPAM</title>
    <meta name="title" content="<?= isset($page_title) ? e($page_title) . ' - ' : '' ?>Laporan Harian Aslab UNPAM">
    <meta name="description" content="Sistem Informasi Pelaporan Harian dan Monitoring Inventaris Asisten Laboratorium Komputer Universitas Pamulang (UNPAM). Rekapitulasi proyektor, kabel roll, dan catatan operasional shift.">
    <meta name="keywords" content="aslab unpam, laporan harian aslab, universitas pamulang, unpam, lab komputer unpam, teknik informatika unpam, asisten laboratorium, inventaris lab">
    <meta name="author" content="Laboratorium Komputer Universitas Pamulang">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Open Graph / Facebook / WhatsApp Share Preview -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
    <meta property="og:title" content="<?= isset($page_title) ? e($page_title) . ' - ' : '' ?>AslabReport UNPAM">
    <meta property="og:description" content="Sistem Monitoring Inventaris & Rekap Harian Asisten Lab Komputer Universitas Pamulang.">
    <meta property="og:image" content="assets/img/logo-unpam.png">
    <meta property="og:site_name" content="AslabReport Universitas Pamulang">
    <meta property="og:locale" content="id_ID">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= isset($page_title) ? e($page_title) . ' - ' : '' ?>AslabReport UNPAM">
    <meta name="twitter:description" content="Sistem Monitoring Inventaris & Rekap Harian Asisten Lab Komputer Universitas Pamulang.">
    <meta name="twitter:image" content="assets/img/logo-unpam.png">
    
    <!-- Favicon & PWA Settings -->
    <link rel="manifest" href="manifest.json">
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
    <meta name="format-detection" content="telephone=no">
    
    <!-- Script Anti-Flicker Dark/Light Theme -->
    <script>
        (function() {
            const saved = localStorage.getItem('aslab_theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-bs-theme', saved || (prefersDark ? 'dark' : 'light'));
        })();
    </script>

    <!-- Bootstrap 5 CSS (Local Vendor - 100% Offline & Private Network Ready) -->
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    
    <!-- FontAwesome 6 (Local Vendor) -->
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    
    <!-- Modular Custom CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <img src="assets/img/logo-unpam.png" alt="Logo UNPAM" style="height: 38px; width: auto; object-fit: contain;">
                <div class="d-flex flex-column" style="line-height: 1.15;">
                    <span class="fw-bold tracking-tight text-primary" style="font-size: 1.15rem;">AslabReport</span>
                    <span class="text-muted" style="font-size: 0.62rem; letter-spacing: 0.04em; font-weight: 700;">UNIVERSITAS PAMULANG</span>
                </div>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="fa-solid fa-chart-line me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'buat-laporan.php' ? 'active' : '' ?>" href="buat-laporan.php">
                            <i class="fa-solid fa-circle-plus me-1"></i> Input Laporan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'riwayat.php' ? 'active' : '' ?>" href="riwayat.php">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Riwayat Arsip
                        </a>
                    </li>
                    <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'kelola-aslab.php' ? 'active' : '' ?>" href="kelola-aslab.php">
                                <i class="fa-solid fa-users-gear me-1"></i> Kelola Aslab
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'log-aktivitas.php' ? 'active' : '' ?>" href="log-aktivitas.php">
                                <i class="fa-solid fa-shield-halved me-1"></i> Log Aktivitas
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <div class="d-flex align-items-center gap-2">
                    <!-- Tombol Install PWA -->
                    <button class="btn btn-outline-warning btn-sm d-none align-items-center gap-1 rounded-pill px-3" id="nav-install-btn" type="button" title="Pasang Aplikasi ke Layar Utama">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                        <span class="small fw-semibold">Pasang App</span>
                    </button>

                    <!-- Tombol Dark / Light Mode -->
                    <button class="theme-toggle-btn" type="button" title="Ganti Mode Gelap / Terang">
                        <i class="fa-solid fa-moon theme-icon"></i>
                    </button>

                    <?php if ($user): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle btn-sm d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-user-tie text-primary"></i>
                                <span><?= e($user['nama_lengkap']) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <span class="dropdown-item-text text-muted small">
                                        <i class="fa-solid fa-id-badge me-1"></i> Role: <strong><?= e(ucfirst($user['role'])) ?></strong>
                                    </span>
                                </li>
                                <?php if (is_admin()): ?>
                                    <li>
                                        <a class="dropdown-item" href="kelola-aslab.php">
                                            <i class="fa-solid fa-user-plus me-1 text-primary"></i> Pendaftaran & Akun Aslab
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="log-aktivitas.php">
                                            <i class="fa-solid fa-shield-halved me-1 text-info"></i> Log & Audit Aktivitas (30 Hari)
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="logout.php" method="POST" class="m-0 p-0" onsubmit="if('serviceWorker' in navigator && navigator.serviceWorker.controller) { navigator.serviceWorker.controller.postMessage({action:'LOGOUT'}); }">
                                        <?= csrf_input() ?>
                                        <button type="submit" class="dropdown-item text-danger border-0 bg-transparent w-100 text-start py-2">
                                            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> Login Aslab
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container with Smooth Fade-In Animation -->
    <main class="container py-4 flex-grow-1 animate-fade-in">
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show d-flex align-items-center justify-content-between shadow-sm" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <?php if ($flash['type'] === 'success'): ?>
                        <i class="fa-solid fa-circle-check text-success fs-5"></i>
                    <?php elseif ($flash['type'] === 'danger'): ?>
                        <i class="fa-solid fa-triangle-exclamation text-danger fs-5"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-circle-info text-primary fs-5"></i>
                    <?php endif; ?>
                    <div><?= e($flash['message']) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

