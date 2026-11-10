<?php
$page_title = "Log Aktivitas & Audit Keamanan";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/logger.php';

// Hanya Admin yang berhak melihat log aktivitas sistem
require_admin();

$currentUser = current_user();

// Handle Manual Purge (> 30 Hari) jika admin klik tombol bersihkan
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'purge_logs') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        purge_old_logs();
        record_log('SYSTEM_PURGE', "Admin membersihkan riwayat log audit > 30 hari secara manual", 'INFO');
        set_flash('success', "Pembersihan log lama berhasil dijalankan. Semua log berumur lebih dari 30 hari telah dihapus.");
    } else {
        set_flash('danger', "Token CSRF tidak valid. Pembersihan log dibatalkan.");
    }
    header("Location: log-aktivitas.php");
    exit;
}

// Parameter Filter & Pencarian
$q            = trim($_GET['q'] ?? '');
$action_filter= trim($_GET['action_filter'] ?? '');
$status_filter= trim($_GET['status_filter'] ?? '');
$tanggal_mulai= trim($_GET['tanggal_mulai'] ?? '');
$tanggal_akhir= trim($_GET['tanggal_akhir'] ?? '');
$export_format= strtolower(trim($_GET['export'] ?? ''));

// Query Builder
$where_clauses = ["1=1"];
$params = [];

if (!empty($q)) {
    $where_clauses[] = "(`username` LIKE :q OR `description` LIKE :q OR `ip_address` LIKE :q OR `url_path` LIKE :q OR `action` LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

if (!empty($action_filter)) {
    if ($action_filter === 'AUTH') {
        $where_clauses[] = "(`action` LIKE 'LOGIN%' OR `action` = 'LOGOUT')";
    } elseif ($action_filter === 'REPORT') {
        $where_clauses[] = "(`action` LIKE '%REPORT%' OR `action` LIKE 'EXPORT%')";
    } elseif ($action_filter === 'USERS') {
        $where_clauses[] = "`action` IN ('CREATE_USER', 'RESET_PASSWORD', 'DELETE_USER')";
    } elseif ($action_filter === 'NAVIGATE') {
        $where_clauses[] = "`action` LIKE 'NAVIGATE%'";
    } else {
        $where_clauses[] = "`action` = :act";
        $params[':act'] = $action_filter;
    }
}

if (!empty($status_filter)) {
    $where_clauses[] = "`status` = :st";
    $params[':st'] = $status_filter;
}

if (!empty($tanggal_mulai) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_mulai)) {
    $where_clauses[] = "DATE(`created_at`) >= :tgl_mulai";
    $params[':tgl_mulai'] = $tanggal_mulai;
}

if (!empty($tanggal_akhir) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_akhir)) {
    $where_clauses[] = "DATE(`created_at`) <= :tgl_akhir";
    $params[':tgl_akhir'] = $tanggal_akhir;
}

$where_sql = implode(' AND ', $where_clauses);
$sql = "SELECT * FROM `activity_logs` WHERE {$where_sql} ORDER BY `id` DESC";

// Jika Ekspor Diminta (Excel / CSV)
if ($export_format === 'excel' || $export_format === 'csv') {
    // Jalankan query tanpa limit
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs_export = $stmt->fetchAll();

    $filename_base = "Audit_Log_Aslab_" . date('Ymd_His');

    if ($export_format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename_base . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM agar rapi di Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header Kolom
        fputcsv($output, ['ID', 'Waktu (WIB)', 'Username / Akun', 'Aksi', 'Status', 'Keterangan Aktivitas', 'Metode', 'Halaman / URL', 'IP Address', 'User Agent / Perangkat']);
        
        foreach ($logs_export as $l) {
            fputcsv($output, [
                (int)$l['id'],
                date('Y-m-d H:i:s', strtotime($l['created_at'])),
                sanitize_csv_cell($l['username']),
                sanitize_csv_cell($l['action']),
                sanitize_csv_cell($l['status']),
                sanitize_csv_cell($l['description']),
                sanitize_csv_cell($l['method']),
                sanitize_csv_cell($l['url_path']),
                sanitize_csv_cell($l['ip_address']),
                sanitize_csv_cell($l['user_agent'])
            ]);
        }
        fclose($output);
        exit;
    } else {
        // EXCEL FORMAT (HTML SPREADSHEET TABLE DENGAN FORMULA SANITIZATION)
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename_base . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8">';
        echo '<style>
            body { font-family: Arial, sans-serif; font-size: 11pt; }
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #0d6efd; color: #ffffff; border: 1px solid #999; padding: 8px; text-align: center; }
            td { border: 1px solid #ccc; padding: 6px; font-size: 10pt; }
            .badge-SUCCESS { background-color: #d1e7dd; color: #0f5132; font-weight: bold; }
            .badge-DANGER { background-color: #f8d7da; color: #842029; font-weight: bold; }
            .badge-WARNING { background-color: #fff3cd; color: #664d03; font-weight: bold; }
            .badge-INFO { background-color: #cff4fc; color: #055160; }
        </style>';
        echo '</head><body>';
        echo '<h2>AUDIT LOG & REKAP AKTIVITAS SISTEM ASLAB REPORT</h2>';
        echo '<p>Dicetak oleh: ' . htmlspecialchars($currentUser['nama_lengkap']) . ' pada ' . date('d/m/Y H:i:s') . ' WIB</p>';
        echo '<p>Total Baris Log: ' . count($logs_export) . ' record (Retensi Otomatis 30 Hari)</p>';
        echo '<table>';
        echo '<thead><tr>
            <th>ID</th>
            <th>Waktu (WIB)</th>
            <th>Pengguna</th>
            <th>Aksi</th>
            <th>Status</th>
            <th>Keterangan Aktivitas</th>
            <th>Metode</th>
            <th>Halaman / URL</th>
            <th>IP Address</th>
            <th>Perangkat / Browser</th>
        </tr></thead><tbody>';

        foreach ($logs_export as $l) {
            $badge_class = 'badge-' . $l['status'];
            echo '<tr>';
            echo '<td style="text-align: center;">#' . (int)$l['id'] . '</td>';
            echo '<td style="text-align: center;">' . date('d/m/Y H:i:s', strtotime($l['created_at'])) . '</td>';
            echo '<td><strong>' . htmlspecialchars(sanitize_csv_cell($l['username'])) . '</strong></td>';
            echo '<td>' . htmlspecialchars(sanitize_csv_cell($l['action'])) . '</td>';
            echo '<td class="' . $badge_class . '" style="text-align: center;">' . htmlspecialchars($l['status']) . '</td>';
            echo '<td>' . htmlspecialchars(sanitize_csv_cell($l['description'])) . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars(sanitize_csv_cell($l['method'])) . '</td>';
            echo '<td>' . htmlspecialchars(sanitize_csv_cell($l['url_path'])) . '</td>';
            echo '<td><code>' . htmlspecialchars(sanitize_csv_cell($l['ip_address'])) . '</code></td>';
            echo '<td><small>' . htmlspecialchars(sanitize_csv_cell($l['user_agent'])) . '</small></td>';
            echo '</tr>';
        }

        echo '</tbody></table></body></html>';
        exit;
    }
}

// Pagination untuk Tampilan Web
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Hitung Total Log untuk Pagination
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM `activity_logs` WHERE {$where_sql}");
$stmt_count->execute($params);
$total_rows = (int)$stmt_count->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

// Ambil Data Halaman Ini
$sql_paged = $sql . " LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
$stmt_paged = $pdo->prepare($sql_paged);
$stmt_paged->execute($params);
$logs = $stmt_paged->fetchAll();

// Statistik KPI Ringkasan
$stat_total = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs`")->fetchColumn();
$stat_failed = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `status` IN ('DANGER', 'WARNING') OR `action` LIKE '%FAIL%'")->fetchColumn();
$stat_success_login = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `action` = 'LOGIN_SUCCESS'")->fetchColumn();
$stat_modifications = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `action` IN ('CREATE_REPORT', 'UPDATE_REPORT', 'DELETE_REPORT', 'CREATE_USER', 'RESET_PASSWORD', 'DELETE_USER')")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<div class="animate-fade-up">

    <!-- Header Page Title & Action Buttons -->
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="hologram-tag mb-2 d-inline-flex align-items-center gap-2">
                <img src="assets/img/logo-unpam.png" alt="UNPAM" style="height: 18px; width: auto; object-fit: contain;">
                <span>Sistem Keamanan & Audit Trail &bull; Universitas Pamulang</span>
            </div>
            <h1 class="h3 fw-bold mb-1">
                Pusat Log Aktivitas & Audit Keamanan
            </h1>
            <p class="text-muted small mb-0">
                Pencatatan real-time seluruh aktivitas login, percobaan masuk, navigasi fitur, input & perubahan data dengan rotasi retensi otomatis 30 hari.
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Ekspor ke Excel (.XLS) -->
            <a href="log-aktivitas.php?export=excel<?= !empty($_SERVER['QUERY_STRING']) ? '&' . http_build_query(array_diff_key($_GET, ['export' => ''])) : '' ?>" class="btn btn-outline-success btn-sm d-flex align-items-center gap-2 shadow-sm">
                <i class="fa-solid fa-file-excel text-success"></i>
                <span class="fw-semibold">Ekspor ke Excel (.XLS)</span>
            </a>

            <!-- Ekspor ke CSV -->
            <a href="log-aktivitas.php?export=csv<?= !empty($_SERVER['QUERY_STRING']) ? '&' . http_build_query(array_diff_key($_GET, ['export' => ''])) : '' ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2 shadow-sm">
                <i class="fa-solid fa-file-csv text-primary"></i>
                <span class="fw-semibold">Ekspor ke CSV</span>
            </a>

            <!-- Manual Purge Modal Trigger -->
            <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#purgeConfirmModal">
                <i class="fa-solid fa-trash-can"></i>
                <span>Bersihkan > 30 Hari</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <div><?= e($flash['message']) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- KPI Ringkasan Audit -->
    <div class="row g-3 mb-4">
        
        <!-- Total Log Tersimpan -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-blue">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Log (30 Hari)</span>
                    <div class="stat-icon-box stat-primary" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1 text-primary">
                    <span class="counter-value" data-target="<?= $stat_total ?>"><?= $stat_total ?></span>
                </div>
                <div class="small text-muted">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i>Siklus retensi bergulir 30 hari
                </div>
            </div>
        </div>

        <!-- Percobaan Gagal / Ancaman -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-orange">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Percobaan Gagal / Peringatan</span>
                    <div class="stat-icon-box stat-orange" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1 <?= $stat_failed > 0 ? 'text-danger' : 'text-success' ?>">
                    <span class="counter-value" data-target="<?= $stat_failed ?>"><?= $stat_failed ?></span>
                </div>
                <div class="small <?= $stat_failed > 0 ? 'text-danger' : 'text-success' ?>">
                    <i class="fa-solid <?= $stat_failed > 0 ? 'fa-shield-virus' : 'fa-shield-halved' ?> me-1"></i>
                    <?= $stat_failed > 0 ? 'Terdeteksi anomali/salah sandi' : 'Sistem aman & stabil' ?>
                </div>
            </div>
        </div>

        <!-- Login Berhasil -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-green">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Sesi Login Sukses</span>
                    <div class="stat-icon-box stat-success" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-right-to-bracket"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1 text-success">
                    <span class="counter-value" data-target="<?= $stat_success_login ?>"><?= $stat_success_login ?></span>
                </div>
                <div class="small text-muted">
                    <i class="fa-solid fa-user-check text-success me-1"></i>Otentikasi aslab terverifikasi
                </div>
            </div>
        </div>

        <!-- Aksi Modifikasi Data -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-purple">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Modifikasi Data</span>
                    <div class="stat-icon-box stat-secondary" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-pen-to-square text-info"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1 text-info">
                    <span class="counter-value" data-target="<?= $stat_modifications ?>"><?= $stat_modifications ?></span>
                </div>
                <div class="small text-muted">
                    <i class="fa-solid fa-database text-info me-1"></i>Input, edit, dan hapus laporan/user
                </div>
            </div>
        </div>

    </div>

    <!-- Filter & Toolbar Bar -->
    <div class="card mb-4 card-hover-lift">
        <div class="card-body p-3 p-md-4">
            
            <!-- Quick Preset Filter Pills -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pb-3 border-bottom">
                <span class="small fw-semibold text-muted me-1"><i class="fa-solid fa-filter text-primary me-1"></i> Filter Cepat:</span>
                <a href="log-aktivitas.php" class="filter-pill <?= empty($action_filter) && empty($status_filter) && empty($q) ? 'active' : '' ?>">
                    Semua Log
                </a>
                <a href="log-aktivitas.php?action_filter=AUTH" class="filter-pill <?= $action_filter === 'AUTH' ? 'active' : '' ?>">
                    <i class="fa-solid fa-key text-warning"></i> Login & Autentikasi
                </a>
                <a href="log-aktivitas.php?action_filter=REPORT" class="filter-pill <?= $action_filter === 'REPORT' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-lines text-primary"></i> Laporan & Ekspor
                </a>
                <a href="log-aktivitas.php?action_filter=USERS" class="filter-pill <?= $action_filter === 'USERS' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users-gear text-success"></i> Manajemen Aslab
                </a>
                <a href="log-aktivitas.php?action_filter=NAVIGATE" class="filter-pill <?= $action_filter === 'NAVIGATE' ? 'active' : '' ?>">
                    <i class="fa-solid fa-compass text-info"></i> Navigasi Halaman
                </a>
                <a href="log-aktivitas.php?status_filter=DANGER" class="filter-pill <?= $status_filter === 'DANGER' ? 'active' : '' ?>">
                    <i class="fa-solid fa-triangle-exclamation text-danger"></i> Anomali / Bahaya
                </a>
            </div>

            <!-- Form Pencarian & Filter Lengkap yang Responsif -->
            <form method="GET" action="log-aktivitas.php" class="row g-2 g-md-3 align-items-end">
                <!-- Kata Kunci -->
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Cari Kata Kunci / User / IP</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control" name="q" placeholder="Cari nama, IP, aksi..." value="<?= e($q) ?>">
                    </div>
                </div>

                <!-- Kategori Aksi -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Kategori Aksi</label>
                    <select class="form-select" name="action_filter">
                        <option value="">Semua Aksi</option>
                        <option value="AUTH" <?= $action_filter === 'AUTH' ? 'selected' : '' ?>>Login & Logout</option>
                        <option value="REPORT" <?= $action_filter === 'REPORT' ? 'selected' : '' ?>>Laporan & Ekspor</option>
                        <option value="USERS" <?= $action_filter === 'USERS' ? 'selected' : '' ?>>Manajemen Aslab</option>
                        <option value="NAVIGATE" <?= $action_filter === 'NAVIGATE' ? 'selected' : '' ?>>Navigasi Halaman</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                    <select class="form-select" name="status_filter">
                        <option value="">Semua Status</option>
                        <option value="SUCCESS" <?= $status_filter === 'SUCCESS' ? 'selected' : '' ?>>SUCCESS</option>
                        <option value="INFO" <?= $status_filter === 'INFO' ? 'selected' : '' ?>>INFO</option>
                        <option value="WARNING" <?= $status_filter === 'WARNING' ? 'selected' : '' ?>>WARNING</option>
                        <option value="DANGER" <?= $status_filter === 'DANGER' ? 'selected' : '' ?>>DANGER</option>
                    </select>
                </div>

                <!-- Dari Tanggal -->
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Dari Tanggal</label>
                    <input type="date" class="form-control" name="tanggal_mulai" value="<?= e($tanggal_mulai) ?>">
                </div>

                <!-- Sampai Tanggal -->
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="tanggal_akhir" value="<?= e($tanggal_akhir) ?>">
                </div>

                <!-- Tombol Terapkan & Reset (Responsif Penuh di Semua Layar) -->
                <div class="col-12 col-md-4 col-lg-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill d-flex align-items-center justify-content-center gap-1 shadow-sm px-2" title="Terapkan Filter" style="height: 38px;">
                        <i class="fa-solid fa-filter"></i>
                        <span class="d-inline d-lg-none"> Terapkan</span>
                    </button>
                    <?php if (!empty($q) || !empty($action_filter) || !empty($status_filter) || !empty($tanggal_mulai) || !empty($tanggal_akhir)): ?>
                        <a href="log-aktivitas.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-center shadow-sm px-3" title="Reset Filter" style="height: 38px;">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

        </div>
    </div>

    <!-- Tabel Log Aktivitas Modern -->
    <div class="card card-hover-lift mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-table-list text-primary"></i>
                <span class="fw-bold">Rekaman Jejak Audit (<?= number_format($total_rows) ?> Data Ditemukan)</span>
            </div>
            <span class="badge bg-body-tertiary text-muted border">
                Halaman <?= $page ?> dari <?= max(1, $total_pages) ?>
            </span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 150px;">Waktu & Status</th>
                        <th style="width: 170px;">Pengguna</th>
                        <th style="width: 180px;">Aksi & Metode</th>
                        <th>Keterangan Aktivitas</th>
                        <th style="width: 150px;">IP Address</th>
                        <th style="width: 180px;">Endpoint URL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="mb-3">
                                    <i class="fa-solid fa-shield-cat fa-3x text-muted opacity-50"></i>
                                </div>
                                <h6 class="fw-bold mb-1">Belum Ada Rekaman Log</h6>
                                <p class="small text-muted mb-0">Tidak ada aktivitas yang sesuai dengan kriteria filter saat ini.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $badge_color = 'primary';
                            $status = strtoupper($log['status'] ?? 'INFO');
                            if ($status === 'SUCCESS') $badge_color = 'success';
                            elseif ($status === 'WARNING') $badge_color = 'warning';
                            elseif ($status === 'DANGER') $badge_color = 'danger';
                            elseif ($status === 'INFO') $badge_color = 'info';

                            $is_failed_login = ($log['action'] === 'LOGIN_FAILED' || $log['action'] === 'LOGIN_CSRF_FAILED');
                            ?>
                            <tr class="<?= $is_failed_login ? 'table-danger-subtle' : '' ?>">
                                
                                <!-- Waktu & Status Badge -->
                                <td>
                                    <div class="fw-semibold small"><?= date('d/m/Y', strtotime($log['created_at'])) ?></div>
                                    <div class="text-muted" style="font-size: 0.78rem;">
                                        <i class="fa-regular fa-clock me-1"></i><?= date('H:i:s', strtotime($log['created_at'])) ?> WIB
                                    </div>
                                    <span class="badge bg-<?= $badge_color ?>-subtle text-<?= $badge_color ?> border border-<?= $badge_color ?>-subtle mt-1" style="font-size: 0.7rem;">
                                        <?= e($status) ?>
                                    </span>
                                </td>

                                <!-- Identitas Pengguna -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle-sm" style="width: 32px; height: 32px; font-size: 0.8rem; background: <?= $is_failed_login ? 'var(--bs-danger-bg-subtle)' : 'var(--bs-primary-bg-subtle)' ?>; color: <?= $is_failed_login ? 'var(--bs-danger)' : 'var(--bs-primary)' ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fa-solid <?= $is_failed_login ? 'fa-user-xmark' : 'fa-user' ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold small"><?= e($log['username']) ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <?= $log['user_id'] ? 'ID #' . (int)$log['user_id'] : 'Tamu / Auth' ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Aksi & Metode HTTP -->
                                <td>
                                    <div class="fw-bold small text-primary font-monospace"><?= e($log['action']) ?></div>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;">
                                            <?= e($log['method'] ?: 'GET') ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Deskripsi Aktivitas -->
                                <td>
                                    <div class="small fw-semibold text-break"><?= e($log['description']) ?></div>
                                    <div class="text-muted small text-truncate" style="max-width: 400px; font-size: 0.75rem;" title="<?= e($log['user_agent']) ?>">
                                        <i class="fa-solid fa-laptop-code me-1"></i><?= e($log['user_agent']) ?>
                                    </div>
                                </td>

                                <!-- IP Address -->
                                <td>
                                    <span class="badge bg-dark-subtle text-dark border font-monospace" style="font-size: 0.8rem;">
                                        <i class="fa-solid fa-network-wired me-1 text-muted"></i><?= e($log['ip_address']) ?>
                                    </span>
                                </td>

                                <!-- URL Path -->
                                <td>
                                    <span class="small font-monospace text-muted text-truncate d-inline-block" style="max-width: 170px;" title="<?= e($log['url_path']) ?>">
                                        <?= e($log['url_path'] ?: '/') ?>
                                    </span>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="card-footer py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span class="small text-muted">
                    Menampilkan log baris <?= $offset + 1 ?> sampai <?= min($total_rows, $offset + $per_page) ?> dari total <?= number_format($total_rows) ?>
                </span>
                
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Prev -->
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="log-aktivitas.php?page=<?= $page - 1 ?><?= !empty($_SERVER['QUERY_STRING']) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        </li>

                        <?php
                        $start_p = max(1, $page - 2);
                        $end_p   = min($total_pages, $page + 2);
                        for ($p = $start_p; $p <= $end_p; $p++):
                        ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="log-aktivitas.php?page=<?= $p ?><?= !empty($_SERVER['QUERY_STRING']) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next -->
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="log-aktivitas.php?page=<?= $page + 1 ?><?= !empty($_SERVER['QUERY_STRING']) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- Modal Konfirmasi Pembersihan Log Manual -->
<div class="modal fade" id="purgeConfirmModal" tabindex="-1" aria-labelledby="purgeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form method="POST" action="log-aktivitas.php">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="purge_logs">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="purgeModalLabel">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Pembersihan Log Berumur > 30 Hari</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="mb-2">
                        Sistem secara otomatis menghapus rekaman log yang berumur <strong>lebih dari 30 hari</strong> secara berkala demi privasi, kepatuhan audit, dan penghematan kapasitas database.
                    </p>
                    <p class="small text-muted mb-0">
                        Apakah Anda ingin mengeksekusi pembersihan log lama sekarang juga secara manual? Tindakan ini aman dan tidak akan menghapus log yang berumur 30 hari terakhir.
                    </p>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger d-flex align-items-center gap-1">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Jalankan Pembersihan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

