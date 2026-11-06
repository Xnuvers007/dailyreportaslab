<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';

$user = current_user();
$flash = get_flash();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' - ' : '' ?>Laporan Harian Aslab</title>
    
    <!-- Script Anti-Flicker Dark/Light Theme -->
    <script>
        (function() {
            const saved = localStorage.getItem('aslab_theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-bs-theme', saved || (prefersDark ? 'dark' : 'light'));
        })();
    </script>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- FontAwesome 6 (Pengganti Emoji) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Modular Custom CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex items-center gap-2" href="index.php">
                <span class="text-primary"><i class="fa-solid fa-layer-group fa-lg"></i></span>
                <span>AslabReport</span>
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
                    <?php endif; ?>
                </ul>
                
                <div class="d-flex align-items-center gap-2">
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
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Logout
                                    </a>
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

