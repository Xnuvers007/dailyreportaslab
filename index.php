<?php
$page_title = "Dashboard";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

// Sistem Privasi: Wajib login untuk melihat dashboard dan isi laporan
require_login("Sistem bersifat tertutup. Silakan login dengan akun Aslab Anda.");

require_once __DIR__ . '/includes/header.php';

// Ambil Laporan Terbaru (Hari ini atau paling terakhir dibuat)
$stmt_latest = $pdo->prepare("SELECT * FROM `laporan` ORDER BY `tanggal` DESC, `id` DESC LIMIT 1");
$stmt_latest->execute();
$latest = $stmt_latest->fetch();

// Ambil 5 Laporan Terakhir untuk tabel ringkasan
$stmt_recent = $pdo->prepare("SELECT * FROM `laporan` ORDER BY `tanggal` DESC, `id` DESC LIMIT 5");
$stmt_recent->execute();
$recent_reports = $stmt_recent->fetchAll();

// Hitung data agregat
$total_laporan = $pdo->query("SELECT COUNT(*) FROM `laporan`")->fetchColumn();

// Siapkan format WhatsApp untuk laporan terbaru jika ada
$wa_text = "";
if ($latest) {
    $tgl_indo = date('d/m/Y', strtotime($latest['tanggal']));
    $tot_kabel = (int)$latest['kabel_roll_tersedia'] + (int)$latest['kabel_roll_rusak'];
    $tot_proyektor = (int)$latest['proyektor_tersedia'] + (int)$latest['proyektor_rusak'];

    $wa_text = "*LAPORAN HARIAN ASISTEN LAB*\n";
    $wa_text .= "Tanggal : " . $tgl_indo . " (" . $latest['shift'] . ")\n";
    $wa_text .= "Petugas : " . $latest['petugas_nama'] . "\n\n";

    $wa_text .= "*KABEL ROLL*\n";
    $catatan_kabel = !empty($latest['kabel_roll_catatan']) ? " (" . $latest['kabel_roll_catatan'] . ")" : "";
    $wa_text .= "• Tersedia : " . (int)$latest['kabel_roll_tersedia'] . " unit" . $catatan_kabel . "\n";
    $wa_text .= "• Rusak : " . (int)$latest['kabel_roll_rusak'] . " unit\n";
    $wa_text .= "• Total Kabel : " . $tot_kabel . " unit\n\n";

    $wa_text .= "*PROYEKTOR*\n";
    $wa_text .= "• Tersedia : " . (int)$latest['proyektor_tersedia'] . " unit\n";
    $wa_text .= "• Rusak/Kurang Kabel : " . (int)$latest['proyektor_rusak'] . " unit\n";
    if (!empty($latest['proyektor_catatan'])) {
        $wa_text .= "• Keterangan : " . $latest['proyektor_catatan'] . "\n";
    }
    $wa_text .= "• Total Proyektor : " . $tot_proyektor . " unit\n\n";

    if (!empty($latest['catatan_umum'])) {
        $wa_text .= "*CATATAN / PENGUMUMAN*\n";
        $wa_text .= "• " . $latest['catatan_umum'] . "\n\n";
    }

    $wa_text .= "_Sistem Informasi Inventaris & Laporan Harian Aslab_";
}
?>

<!-- Hero Welcome Banner with Interactive 3D Aslab Character (Orange PDH UNPAM) -->
<div class="hero-welcome-card p-4 p-md-5 mb-4 animate-fade-up border-gradient-top">
    <div class="row align-items-center g-4">
        <div class="col-12 col-md-7 col-lg-8">
            <div class="hologram-tag mb-3">
                <i class="fa-solid fa-atom"></i>
                <span>Asisten Laboratorium &bull; Universitas Pamulang</span>
            </div>
            <h1 class="h3 fw-bold mb-2">
                Halo, <?= e($user ? $user['nama_lengkap'] : 'Rekan Aslab') ?>! 👋
            </h1>
            <p class="text-muted mb-4 small" style="max-width: 580px; line-height: 1.6;">
                Sistem monitoring kondisi proyektor, kabel roll, dan serah terima shift Lab Komputer Informatika UNPAM. Pastikan semua perlengkapan terdata dengan rapi sebelum kelas dimulai.
            </p>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="buat-laporan.php" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>Input Laporan Shift Ini</span>
                </a>
                <a href="riwayat.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Lihat Semua Arsip</span>
                </a>
                <span class="badge bg-body-tertiary text-body border p-2 px-3 small ms-md-2">
                    <i class="fa-regular fa-calendar-check text-primary me-1"></i>
                    <?= date('l, d F Y') ?>
                </span>
            </div>
        </div>

        <!-- 3D Interactive Avatar Viewport -->
        <div class="col-12 col-md-5 col-lg-4 text-center position-relative">
            <div class="avatar-3d-wrapper">
                <canvas id="aslab-avatar-canvas" class="avatar-3d-canvas"></canvas>
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.72rem; letter-spacing: 0.02em;">
                <i class="fa-solid fa-hand-pointer me-1 text-primary"></i> 
                <strong>3D Aslab UNPAM (Baju PDH Oranye)</strong> &bull; Gerakkan kursor
            </div>
        </div>
    </div>
</div>

<?php if ($latest): ?>
    <?php 
        $tot_proyektor_latest = (int)$latest['proyektor_tersedia'] + (int)$latest['proyektor_rusak'];
        $tot_kabel_latest = (int)$latest['kabel_roll_tersedia'] + (int)$latest['kabel_roll_rusak'];
    ?>
    <!-- Highlight Cards (Laporan Terkini) -->
    <div class="row g-3 mb-4">
        <!-- Proyektor Card -->
        <div class="col-12 col-md-6 col-lg-3 animate-fade-up delay-1">
            <div class="card card-hover-lift h-100 p-3 border-start border-primary border-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Proyektor Siap</span>
                    <div class="stat-icon-box stat-primary">
                        <i class="fa-solid fa-video"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1 text-primary">
                    <span class="counter-value" data-target="<?= (int)$latest['proyektor_tersedia'] ?>"><?= (int)$latest['proyektor_tersedia'] ?></span>
                    <span class="fs-6 fw-normal text-muted">/ <?= $tot_proyektor_latest ?> Unit</span>
                </div>
                <div class="small text-danger d-flex align-items-center gap-1">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?= (int)$latest['proyektor_rusak'] ?> trouble / kurang kabel</span>
                </div>
            </div>
        </div>

        <!-- Kabel Roll Card -->
        <div class="col-12 col-md-6 col-lg-3 animate-fade-up delay-2">
            <div class="card card-hover-lift h-100 p-3 border-start border-warning border-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Kabel Roll Siap</span>
                    <div class="stat-icon-box stat-orange">
                        <i class="fa-solid fa-plug"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1" style="color: var(--unpam-orange-light);">
                    <span class="counter-value" data-target="<?= (int)$latest['kabel_roll_tersedia'] ?>"><?= (int)$latest['kabel_roll_tersedia'] ?></span>
                    <span class="fs-6 fw-normal text-muted">/ <?= $tot_kabel_latest ?> Unit</span>
                </div>
                <div class="small text-danger d-flex align-items-center gap-1">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= (int)$latest['kabel_roll_rusak'] ?> unit tidak siap</span>
                </div>
            </div>
        </div>

        <!-- Shift & Petugas Card -->
        <div class="col-12 col-md-6 col-lg-3 animate-fade-up delay-3">
            <div class="card card-hover-lift h-100 p-3 border-start border-info border-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Petugas Terakhir</span>
                    <div class="stat-icon-box stat-primary">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                </div>
                <div class="h5 fw-bold mb-1 text-truncate"><?= e($latest['petugas_nama']) ?></div>
                <div class="small text-muted d-flex align-items-center gap-1">
                    <i class="fa-solid fa-clock"></i>
                    <span>Shift <?= e($latest['shift']) ?> (<?= date('d M Y', strtotime($latest['tanggal'])) ?>)</span>
                </div>
            </div>
        </div>

        <!-- Total Laporan Card -->
        <div class="col-12 col-md-6 col-lg-3 animate-fade-up delay-4">
            <div class="card card-hover-lift h-100 p-3 border-start border-secondary border-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Arsip</span>
                    <div class="stat-icon-box stat-secondary">
                        <i class="fa-solid fa-folder-closed"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1">
                    <span class="counter-value" data-target="<?= (int)$total_laporan ?>"><?= (int)$total_laporan ?></span>
                </div>
                <div class="small text-muted d-flex align-items-center gap-1">
                    <i class="fa-solid fa-database"></i>
                    <span>Laporan tersimpan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Quick Copy WhatsApp & Catatan Operasional -->
    <div class="row g-4 mb-4">
        <!-- Kolom Kiri: WhatsApp Quick Share -->
        <div class="col-12 col-lg-7 animate-fade-up delay-2">
            <div class="card card-hover-lift h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-brands fa-whatsapp text-success fs-5"></i>
                        <span class="fw-bold">Format Siap Salin ke WhatsApp</span>
                    </div>
                    <button type="button" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1" onclick="copyToWhatsApp(<?= htmlspecialchars(json_encode($wa_text), ENT_QUOTES, 'UTF-8') ?>, this)">
                        <i class="fa-solid fa-copy"></i>
                        <span>Salin Format WA</span>
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Klik tombol di atas untuk menyalin pesan yang sudah diformat rapi untuk dikirim ke grup Aslab / Laboran:</p>
                    <div class="whatsapp-box"><?= e($wa_text) ?></div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Pengumuman / Memo Terkini & Foto -->
        <div class="col-12 col-lg-5 animate-fade-up delay-3">
            <div class="card card-hover-lift h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-bullhorn text-warning fs-5"></i>
                        <span class="fw-bold">Memo / Update Terkini</span>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        <?= date('d M Y', strtotime($latest['tanggal'])) ?>
                    </span>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <?php if (!empty($latest['catatan_umum'])): ?>
                            <div class="p-3 bg-body-tertiary rounded-3 mb-3 border">
                                <p class="mb-0 small fw-medium">
                                    <i class="fa-solid fa-quote-left text-muted me-1"></i>
                                    <?= nl2br(e($latest['catatan_umum'])) ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small fst-italic">Tidak ada memo khusus pada laporan terkini.</p>
                        <?php endif; ?>

                        <?php if (!empty($latest['kabel_roll_catatan'])): ?>
                            <div class="small mb-2">
                                <span class="fw-semibold text-secondary"><i class="fa-solid fa-plug me-1"></i> Catatan Kabel:</span>
                                <span class="text-danger"><?= e($latest['kabel_roll_catatan']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($latest['proyektor_catatan'])): ?>
                            <div class="small mb-2">
                                <span class="fw-semibold text-secondary"><i class="fa-solid fa-video me-1"></i> Catatan Proyektor:</span>
                                <span class="text-danger"><?= e($latest['proyektor_catatan']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($latest['foto_dokumentasi'])): ?>
                        <div class="mt-3 pt-3 border-top">
                            <span class="small text-muted d-block mb-2">
                                <i class="fa-solid fa-image me-1"></i> Foto Dokumentasi:
                            </span>
                            <a href="uploads/<?= e($latest['foto_dokumentasi']) ?>" target="_blank" class="d-block text-decoration-none">
                                <div class="image-preview-container" style="max-height: 180px;">
                                    <img src="uploads/<?= e($latest['foto_dokumentasi']) ?>" alt="Dokumentasi Laporan" class="img-fluid rounded">
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- State jika database belum ada laporan -->
    <div class="card text-center p-5 mb-4">
        <div class="py-4">
            <div class="stat-icon-box bg-primary-subtle text-primary mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.75rem;">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <h5 class="fw-bold">Belum Ada Laporan Harian</h5>
            <p class="text-muted small max-w-md mx-auto mb-4">Mulai catat kondisi ketersediaan proyektor, kabel roll, dan catatan aslab hari ini.</p>
            <a href="buat-laporan.php" class="btn btn-primary">
                <i class="fa-solid fa-plus-circle me-1"></i> Input Laporan Perdana
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Tabel Riwayat Singkat -->
<div class="card card-hover-lift animate-fade-up delay-4">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-list-check text-primary"></i>
            <span class="fw-bold">Riwayat Laporan Terakhir</span>
        </div>
        <a href="riwayat.php" class="btn btn-outline-secondary btn-sm">
            <span>Lihat Semua Arsip</span>
            <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Tanggal & Shift</th>
                    <th>Petugas</th>
                    <th>Kabel Roll</th>
                    <th>Proyektor</th>
                    <th>Foto</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recent_reports) > 0): ?>
                    <?php foreach ($recent_reports as $row): ?>
                        <?php 
                            $tot_kabel = (int)$row['kabel_roll_tersedia'] + (int)$row['kabel_roll_rusak'];
                            $tot_proyektor = (int)$row['proyektor_tersedia'] + (int)$row['proyektor_rusak'];
                        ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></div>
                                <span class="badge bg-body-tertiary text-body border small">Shift <?= e($row['shift']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-regular fa-user text-muted"></i>
                                    <span><?= e($row['petugas_nama']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="text-success fw-semibold"><?= (int)$row['kabel_roll_tersedia'] ?></span> Tersedia
                                    <?php if ((int)$row['kabel_roll_rusak'] > 0): ?>
                                        <span class="text-danger">(<?= (int)$row['kabel_roll_rusak'] ?> rusak)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">Total: <?= $tot_kabel ?> Unit</div>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="text-primary fw-semibold"><?= (int)$row['proyektor_tersedia'] ?></span> Tersedia
                                    <?php if ((int)$row['proyektor_rusak'] > 0): ?>
                                        <span class="text-danger">(<?= (int)$row['proyektor_rusak'] ?> trouble)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">Total: <?= $tot_proyektor ?> Unit</div>
                            </td>
                            <td>
                                <?php if (!empty($row['foto_dokumentasi'])): ?>
                                    <a href="uploads/<?= e($row['foto_dokumentasi']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2">
                                        <i class="fa-solid fa-image"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <a href="detail.php?id=<?= (int)$row['id'] ?>" class="btn btn-outline-primary" title="Lihat Detail">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="edit-laporan.php?id=<?= (int)$row['id'] ?>" class="btn btn-outline-secondary" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat laporan yang tersimpan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

