<?php
$page_title = "Riwayat Arsip Laporan";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

// Sistem Privasi: Wajib login untuk melihat arsip riwayat laporan
require_login("Sistem bersifat tertutup. Silakan login terlebih dahulu.");

// Filter & Pencarian
$q              = trim($_GET['q'] ?? '');
$shift          = trim($_GET['shift'] ?? '');
$tanggal_mulai  = trim($_GET['tanggal_mulai'] ?? '');
$tanggal_akhir  = trim($_GET['tanggal_akhir'] ?? '');
$preset         = trim($_GET['preset'] ?? '');

if ($preset === 'today') {
    $tanggal_mulai = date('Y-m-d');
    $tanggal_akhir = date('Y-m-d');
}

$sql = "SELECT * FROM `laporan` WHERE 1=1";
$params = [];

if (!empty($q)) {
    $sql .= " AND (`petugas_nama` LIKE :q OR `catatan_umum` LIKE :q OR `kabel_roll_catatan` LIKE :q OR `proyektor_catatan` LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

if (!empty($shift) && in_array($shift, ['Pagi', 'Siang', 'Sore', 'Malam', '1 Hari', 'Full Day'])) {
    $sql .= " AND `shift` = :shift";
    $params[':shift'] = $shift;
}

if (!empty($tanggal_mulai) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_mulai)) {
    $sql .= " AND `tanggal` >= :tanggal_mulai";
    $params[':tanggal_mulai'] = $tanggal_mulai;
}

if (!empty($tanggal_akhir) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_akhir)) {
    $sql .= " AND `tanggal` <= :tanggal_akhir";
    $params[':tanggal_akhir'] = $tanggal_akhir;
}

$sql .= " ORDER BY `tanggal` DESC, `id` DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Hitung Statistik Arsip
$total_arsip = count($reports);
$total_kabel_siap = 0;
$total_kabel_rusak = 0;
$total_proyektor_siap = 0;
$total_proyektor_rusak = 0;

foreach ($reports as $r) {
    $total_kabel_siap += (int)$r['kabel_roll_tersedia'];
    $total_kabel_rusak += (int)$r['kabel_roll_rusak'];
    $total_proyektor_siap += (int)$r['proyektor_tersedia'];
    $total_proyektor_rusak += (int)$r['proyektor_rusak'];
}

$grand_total_unit = $total_kabel_siap + $total_kabel_rusak + $total_proyektor_siap + $total_proyektor_rusak;
$grand_total_siap = $total_kabel_siap + $total_proyektor_siap;
$health_score = $grand_total_unit > 0 ? round(($grand_total_siap / $grand_total_unit) * 100) : 100;

require_once __DIR__ . '/includes/header.php';
?>

<div class="animate-fade-up">
    
    <!-- Header Page Title & Action Buttons -->
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="hologram-tag mb-2 d-inline-flex align-items-center gap-2">
                <img src="assets/img/logo-unpam.png" alt="UNPAM" style="height: 18px; width: auto; object-fit: contain;">
                <span>Arsip Rekapitulasi Inventaris &bull; Universitas Pamulang</span>
            </div>
            <h2 class="h3 fw-bold mb-1">
                Riwayat Arsip Laporan Harian
            </h2>
            <p class="text-muted small mb-0">
                Pusat data rekapan fisik ketersediaan proyektor, kabel roll, dan serah terima tugas seluruh aslab.
            </p>
        </div>
        
        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Tombol Buka Modal Ekspor Canggih (PDF & Excel) -->
            <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="fa-solid fa-file-arrow-down text-primary"></i>
                <span class="fw-semibold">Ekspor Laporan (PDF / Excel)</span>
            </button>
            <a href="buat-laporan.php" class="btn btn-primary btn-sm d-flex align-items-center gap-2 shadow-sm">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Input Laporan Baru</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI Analytics Metric Cards -->
    <div class="row g-3 mb-4">
        
        <!-- Total Laporan -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-blue">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Rekapan</span>
                    <div class="stat-icon-box stat-primary" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1 text-primary">
                    <span class="counter-value" data-target="<?= $total_arsip ?>"><?= $total_arsip ?></span>
                    <span class="fs-6 fw-normal text-muted">Laporan</span>
                </div>
                <div class="small text-muted d-flex align-items-center gap-1">
                    <i class="fa-solid fa-filter me-1 text-primary"></i> Sesuai filter pencarian
                </div>
            </div>
        </div>

        <!-- Kesiapan Proyektor -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-orange">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Kesiapan Proyektor</span>
                    <div class="stat-icon-box stat-orange" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-video"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1" style="color: var(--unpam-orange);">
                    <span class="counter-value" data-target="<?= $total_proyektor_siap ?>"><?= $total_proyektor_siap ?></span>
                    <span class="fs-6 fw-normal text-muted">/ <?= $total_proyektor_siap + $total_proyektor_rusak ?> Unit</span>
                </div>
                <div class="small <?= $total_proyektor_rusak > 0 ? 'text-danger' : 'text-success' ?>">
                    <i class="fa-solid <?= $total_proyektor_rusak > 0 ? 'fa-triangle-exclamation' : 'fa-check' ?> me-1"></i>
                    <?= $total_proyektor_rusak ?> unit dalam kendala/rusak
                </div>
            </div>
        </div>

        <!-- Kesiapan Kabel Roll -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-green">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Kesiapan Kabel Roll</span>
                    <div class="stat-icon-box stat-success" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-plug"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1 text-success">
                    <span class="counter-value" data-target="<?= $total_kabel_siap ?>"><?= $total_kabel_siap ?></span>
                    <span class="fs-6 fw-normal text-muted">/ <?= $total_kabel_siap + $total_kabel_rusak ?> Unit</span>
                </div>
                <div class="small <?= $total_kabel_rusak > 0 ? 'text-danger' : 'text-success' ?>">
                    <i class="fa-solid <?= $total_kabel_rusak > 0 ? 'fa-circle-exclamation' : 'fa-circle-check' ?> me-1"></i>
                    <?= $total_kabel_rusak ?> unit rusak / tidak siap
                </div>
            </div>
        </div>

        <!-- Overall Lab Health Score -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-purple">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Kesiapan Inventaris</span>
                    <div class="stat-icon-box stat-secondary" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-heart-pulse text-danger"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1 text-info">
                    <span class="counter-value" data-target="<?= $health_score ?>"><?= $health_score ?></span>%
                </div>
                <div class="progress" style="height: 6px; border-radius: 4px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $health_score ?>%"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Filter & Toolbar Bar Modern -->
    <div class="card mb-4 card-hover-lift">
        <div class="card-body p-3 p-md-4">
            
            <!-- Quick Preset Filter Pills -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pb-3 border-bottom">
                <span class="small fw-semibold text-muted me-1"><i class="fa-solid fa-bolt text-warning me-1"></i> Filter Cepat:</span>
                <a href="riwayat.php" class="filter-pill <?= empty($preset) && empty($shift) && empty($tanggal_mulai) ? 'active' : '' ?>">
                    Semua
                </a>
                <a href="riwayat.php?preset=today" class="filter-pill <?= $preset === 'today' ? 'active' : '' ?>">
                    <i class="fa-regular fa-calendar-check"></i> Hari Ini
                </a>
                <a href="riwayat.php?shift=Pagi" class="filter-pill <?= $shift === 'Pagi' ? 'active' : '' ?>">
                    <i class="fa-solid fa-sun text-warning"></i> Shift Pagi (07:00 - 10:00)
                </a>
                <a href="riwayat.php?shift=Siang" class="filter-pill <?= $shift === 'Siang' ? 'active' : '' ?>">
                    <i class="fa-solid fa-cloud-sun text-primary"></i> Shift Siang (10:00 - 15:00)
                </a>
                <a href="riwayat.php?shift=Malam" class="filter-pill <?= $shift === 'Malam' ? 'active' : '' ?>">
                    <i class="fa-solid fa-moon text-info"></i> Shift Malam (16:00 - 21:30)
                </a>
                <a href="riwayat.php?shift=1+Hari" class="filter-pill <?= ($shift === '1 Hari' || $shift === 'Full Day') ? 'active' : '' ?>">
                    <i class="fa-solid fa-clock-rotate-left text-success"></i> Shift 1 Hari (07:00 - 21:30)
                </a>
            </div>

            <!-- Detailed Search Form -->
            <form method="GET" action="riwayat.php" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-muted">Cari Kata Kunci / Petugas / Memo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control" name="q" placeholder="Cari nama, memo, kendala..." value="<?= e($q) ?>">
                    </div>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted">Pilih Shift</label>
                    <select class="form-select" name="shift">
                        <option value="">Semua Shift</option>
                        <option value="Pagi" <?= $shift === 'Pagi' ? 'selected' : '' ?>>Pagi (07:00 - 10:00)</option>
                        <option value="Siang" <?= $shift === 'Siang' ? 'selected' : '' ?>>Siang (10:00 - 15:00)</option>
                        <option value="Malam" <?= $shift === 'Malam' ? 'selected' : '' ?>>Malam (16:00 - 21:30)</option>
                        <option value="1 Hari" <?= ($shift === '1 Hari' || $shift === 'Full Day') ? 'selected' : '' ?>>1 Hari (07:00 - 21:30 Full Day)</option>
                        <?php if ($shift === 'Sore'): ?>
                            <option value="Sore" selected>Sore (Legacy)</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                    <input type="date" class="form-control" name="tanggal_mulai" value="<?= e($tanggal_mulai) ?>">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="tanggal_akhir" value="<?= e($tanggal_akhir) ?>">
                </div>

                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1 d-flex align-items-center justify-content-center gap-1 shadow-sm">
                        <i class="fa-solid fa-filter"></i>
                        <span>Filter</span>
                    </button>
                    <a href="riwayat.php" class="btn btn-outline-secondary" title="Reset Semua Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>

        </div>
    </div>

    <!-- Tabel Daftar Laporan Interaktif -->
    <div class="card card-hover-lift">
        <div class="card-header py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-table-list text-primary fs-5"></i>
                <span class="fw-bold">Data Rekapan Laporan (<?= count($reports) ?> Data Ditemukan)</span>
            </div>
            
            <?php
                // Link Ekspor Cepat Langsung (Tanpa harus buka modal jika ingin download data saat ini)
                $current_params = $_GET;
                $params_pdf = $current_params;
                $params_pdf['format'] = 'pdf';
                $link_quick_pdf = 'export-laporan.php?' . http_build_query($params_pdf);

                $params_excel = $current_params;
                $params_excel['format'] = 'excel';
                $link_quick_excel = 'export-laporan.php?' . http_build_query($params_excel);
            ?>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= e($link_quick_pdf) ?>" target="_blank" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 shadow-sm" title="Langsung Cetak / Unduh PDF dari data yang tampil">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Cetak PDF</span>
                </a>
                <a href="<?= e($link_quick_excel) ?>" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1 shadow-sm" title="Langsung Unduh Excel (.XLS) dari data yang tampil">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Unduh Excel</span>
                </a>
                <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#exportModal" title="Buka Pengaturan Ekspor Rentang Kustom">
                    <i class="fa-solid fa-sliders"></i>
                    <span>Kustom Range</span>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width: 170px;">Tanggal & Shift</th>
                        <th style="width: 200px;">Petugas Jaga</th>
                        <th style="width: 160px;">Kabel Roll</th>
                        <th style="width: 170px;">Proyektor</th>
                        <th>Memo & Pengumuman</th>
                        <th class="text-center" style="width: 70px;">Foto</th>
                        <th class="text-end pe-3" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reports) > 0): ?>
                        <?php foreach ($reports as $row): ?>
                            <?php 
                                $tot_kabel = (int)$row['kabel_roll_tersedia'] + (int)$row['kabel_roll_rusak'];
                                $tot_proyektor = (int)$row['proyektor_tersedia'] + (int)$row['proyektor_rusak'];
                                
                                // Teks WhatsApp untuk baris ini
                                $tgl_indo = date('d/m/Y', strtotime($row['tanggal']));
                                $wa_row = "*LAPORAN HARIAN ASISTEN LAB*\n";
                                $wa_row .= "Tanggal : " . $tgl_indo . " (" . $row['shift'] . ")\n";
                                $wa_row .= "Petugas : " . $row['petugas_nama'] . "\n\n";
                                $wa_row .= "*KABEL ROLL*\n• Tersedia : " . (int)$row['kabel_roll_tersedia'] . " unit\n• Rusak : " . (int)$row['kabel_roll_rusak'] . " unit\n• Total : " . $tot_kabel . " unit\n\n";
                                $wa_row .= "*PROYEKTOR*\n• Tersedia : " . (int)$row['proyektor_tersedia'] . " unit\n• Rusak : " . (int)$row['proyektor_rusak'] . " unit\n• Total : " . $tot_proyektor . " unit\n\n";
                                if (!empty($row['catatan_umum'])) {
                                    $wa_row .= "*MEMO:*\n• " . $row['catatan_umum'] . "\n\n";
                                }
                                $wa_row .= "_Sistem Informasi Inventaris & Laporan Harian Aslab_";

                                // Shift badge color
                                $shift_class = match($row['shift']) {
                                    'Pagi'  => 'bg-warning-subtle text-warning border-warning-subtle',
                                    'Siang' => 'bg-primary-subtle text-primary border-primary-subtle',
                                    'Sore'  => 'bg-info-subtle text-info border-info-subtle',
                                    'Malam' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                    default => 'bg-body-tertiary text-body'
                                };
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-main"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></div>
                                    <span class="badge <?= $shift_class ?> border small mt-1">
                                        Shift <?= e($row['shift']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar-circle avatar-aslab" style="width: 34px; height: 34px; font-size: 0.82rem;">
                                            <?= strtoupper(substr($row['petugas_nama'], 0, 2)) ?>
                                        </div>
                                        <div class="text-truncate" style="max-width: 140px;">
                                            <span class="fw-semibold text-main"><?= e($row['petugas_nama']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="text-success fw-bold"><?= (int)$row['kabel_roll_tersedia'] ?></span>
                                        <span class="text-muted small">/ <?= $tot_kabel ?> Siap</span>
                                    </div>
                                    <?php if ((int)$row['kabel_roll_rusak'] > 0): ?>
                                        <div class="text-danger small fw-semibold">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i><?= (int)$row['kabel_roll_rusak'] ?> rusak
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <span class="text-primary fw-bold"><?= (int)$row['proyektor_tersedia'] ?></span>
                                        <span class="text-muted small">/ <?= $tot_proyektor ?> Siap</span>
                                    </div>
                                    <?php if ((int)$row['proyektor_rusak'] > 0): ?>
                                        <div class="text-danger small fw-semibold">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i><?= (int)$row['proyektor_rusak'] ?> kendala
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['catatan_umum'])): ?>
                                        <div class="small text-truncate" style="max-width: 260px;" title="<?= e($row['catatan_umum']) ?>">
                                            <i class="fa-solid fa-comment-dots text-warning me-1"></i>
                                            <?= e($row['catatan_umum']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($row['foto_dokumentasi']) && photo_file_exists($row['foto_dokumentasi'])): ?>
                                        <a href="lihat-foto.php?file=<?= urlencode($row['foto_dokumentasi']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2" title="Lihat Foto Dokumentasi">
                                            <i class="fa-solid fa-image"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-success" title="Salin Pesan WhatsApp" onclick="copyToWhatsApp(<?= htmlspecialchars(json_encode($wa_row), ENT_QUOTES, 'UTF-8') ?>, this)">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </button>
                                        <a href="detail.php?id=<?= (int)$row['id'] ?>" class="btn btn-outline-primary" title="Lihat Detail">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <?php if (is_admin() || (!empty($row['user_id']) && (int)$row['user_id'] === (int)$user['id'])): ?>
                                            <a href="edit-laporan.php?id=<?= (int)$row['id'] ?>" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="py-3">
                                    <i class="fa-solid fa-magnifying-glass-chart fa-3x text-secondary opacity-50 mb-3"></i>
                                    <h6>Tidak Ada Data Laporan yang Cocok</h6>
                                    <p class="small text-muted mb-3">Coba ubah kata kunci atau rentang tanggal filter Anda.</p>
                                    <a href="riwayat.php" class="btn btn-outline-primary btn-sm">Reset Filter</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ======================================================== -->
<!-- MODAL EKSPOR LAPORAN (PDF & EXCEL DENGAN CUSTOM RANGE)   -->
<!-- ======================================================== -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg);">
            
            <form action="export-laporan.php" method="GET" target="_blank">
                
                <div class="modal-header border-bottom py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon-box stat-primary" style="width: 40px; height: 40px; font-size: 1.15rem;">
                            <i class="fa-solid fa-file-export"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="exportModalLabel">Ekspor Dokumen Laporan Lab</h5>
                            <span class="small text-muted">Unduh rekapitulasi inventaris ke format PDF Cetak atau Excel XLS</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    
                    <!-- Pilihan Format Ekspor (Radio Buttons Visual) -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase text-muted" style="letter-spacing: 0.05em;">
                            1. Pilih Format File
                        </label>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="shift-radio-item">
                                    <input type="radio" id="format_pdf" name="format" value="pdf" checked>
                                    <label for="format_pdf" class="shift-radio-label p-3">
                                        <i class="fa-solid fa-file-pdf text-danger fs-3 mb-2"></i>
                                        <span class="shift-name fs-6">Dokumen Resmi PDF</span>
                                        <span class="shift-time">Kop surat UNPAM resmi & siap cetak A4 landscape</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="shift-radio-item">
                                    <input type="radio" id="format_excel" name="format" value="excel">
                                    <label for="format_excel" class="shift-radio-label p-3">
                                        <i class="fa-solid fa-file-excel text-success fs-3 mb-2"></i>
                                        <span class="shift-name fs-6">Spreadsheet Excel (.XLS)</span>
                                        <span class="shift-time">Tabel rapi berborder siap olah data & rumus</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pilihan Cakupan Data / Range Type -->
                    <div class="mb-3">
                        <label for="export_range_type" class="form-label small fw-bold text-uppercase text-muted" style="letter-spacing: 0.05em;">
                            2. Pilih Cakupan Data Laporan
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-calendar-range"></i></span>
                            <select class="form-select" id="export_range_type" name="range_type">
                                <option value="all">Semua Data (Keseluruhan Arsip)</option>
                                <option value="today">Hari Ini Saja (<?= date('d/m/Y') ?>)</option>
                                <option value="this_month">Bulan Ini (<?= date('F Y') ?>)</option>
                                <option value="custom" selected>Rentang Kustom (Bisa Atur Tanggal & Jam Bebas)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Custom Range Section (Tanggal & Jam Bebas) -->
                    <div id="export_custom_range_section" class="p-3 bg-body-tertiary rounded-3 border mb-3">
                        <div class="small fw-bold text-primary mb-2">
                            <i class="fa-solid fa-sliders me-1"></i> Atur Tanggal & Jam Kustom:
                        </div>
                        <div class="row g-2">
                            
                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted mb-1">Dari Tanggal & Jam Mulai</label>
                                <div class="input-group input-group-sm mb-1">
                                    <span class="input-group-text"><i class="fa-regular fa-calendar"></i></span>
                                    <input type="date" class="form-control" name="tanggal_mulai" value="<?= date('Y-m-01') ?>">
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fa-regular fa-clock"></i></span>
                                    <input type="time" class="form-control" name="jam_mulai" value="07:00" placeholder="07:00">
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted mb-1">Sampai Tanggal & Jam Selesai</label>
                                <div class="input-group input-group-sm mb-1">
                                    <span class="input-group-text"><i class="fa-regular fa-calendar"></i></span>
                                    <input type="date" class="form-control" name="tanggal_akhir" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fa-regular fa-clock"></i></span>
                                    <input type="time" class="form-control" name="jam_akhir" value="21:30" placeholder="21:30">
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Filter Tambahan: Shift & Petugas -->
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">Filter Berdasarkan Shift</label>
                            <select class="form-select" name="shift">
                                <option value="">Semua Shift</option>
                                <option value="Pagi">Shift Pagi (07:00 - 10:00)</option>
                                <option value="Siang">Shift Siang (10:00 - 15:00)</option>
                                <option value="Malam">Shift Malam (16:00 - 21:30)</option>
                                <option value="1 Hari">Shift 1 Hari (07:00 - 21:30 Full Day)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">Filter Nama Petugas (Opsional)</label>
                            <input type="text" class="form-control" name="petugas" placeholder="Semua Petugas atau ketik nama...">
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2 shadow">
                        <i class="fa-solid fa-download"></i>
                        <span>Unduh / Buka Dokumen</span>
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
