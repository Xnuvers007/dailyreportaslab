<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

// Sistem Privasi: Wajib login untuk melihat detail laporan
require_login("Sistem bersifat tertutup. Silakan login terlebih dahulu.");

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', "ID laporan tidak valid.");
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM `laporan` WHERE `id` = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$laporan = $stmt->fetch();

if (!$laporan) {
    set_flash('danger', "Laporan tidak ditemukan dalam sistem.");
    header("Location: riwayat.php");
    exit;
}

$page_title = "Detail Laporan - " . date('d/m/Y', strtotime($laporan['tanggal']));

$tot_kabel = (int)$laporan['kabel_roll_tersedia'] + (int)$laporan['kabel_roll_rusak'];
$tot_proyektor = (int)$laporan['proyektor_tersedia'] + (int)$laporan['proyektor_rusak'];
$tgl_indo = date('d/m/Y', strtotime($laporan['tanggal']));

// Format WhatsApp
$wa_text = "*LAPORAN HARIAN ASISTEN LAB*\n";
$wa_text .= "Tanggal : " . $tgl_indo . " (" . $laporan['shift'] . ")\n";
$wa_text .= "Petugas : " . $laporan['petugas_nama'] . "\n\n";

$wa_text .= "*KABEL ROLL*\n";
$catatan_kabel = !empty($laporan['kabel_roll_catatan']) ? " (" . $laporan['kabel_roll_catatan'] . ")" : "";
$wa_text .= "• Tersedia : " . (int)$laporan['kabel_roll_tersedia'] . " unit" . $catatan_kabel . "\n";
$wa_text .= "• Rusak : " . (int)$laporan['kabel_roll_rusak'] . " unit\n";
$wa_text .= "• Total Kabel : " . $tot_kabel . " unit\n\n";

$wa_text .= "*PROYEKTOR*\n";
$wa_text .= "• Tersedia : " . (int)$laporan['proyektor_tersedia'] . " unit\n";
$wa_text .= "• Rusak/Kurang Kabel : " . (int)$laporan['proyektor_rusak'] . " unit\n";
if (!empty($laporan['proyektor_catatan'])) {
    $wa_text .= "• Keterangan : " . $laporan['proyektor_catatan'] . "\n";
}
$wa_text .= "• Total Proyektor : " . $tot_proyektor . " unit\n\n";

if (!empty($laporan['catatan_umum'])) {
    $wa_text .= "*CATATAN / PENGUMUMAN*\n";
    $wa_text .= "• " . $laporan['catatan_umum'] . "\n\n";
}
$wa_text .= "_Sistem Informasi Inventaris & Laporan Harian Aslab_";

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-1">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="riwayat.php" class="text-decoration-none">Riwayat</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail Laporan #<?= (int)$laporan['id'] ?></li>
            </ol>
        </nav>
        <h3 class="h4 mb-0 fw-bold">
            <i class="fa-solid fa-file-lines text-primary me-2"></i>Laporan <?= date('d F Y', strtotime($laporan['tanggal'])) ?>
        </h3>
    </div>
    
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1" onclick="copyToWhatsApp(<?= htmlspecialchars(json_encode($wa_text), ENT_QUOTES, 'UTF-8') ?>, this)">
            <i class="fa-brands fa-whatsapp fs-6"></i>
            <span>Salin WA</span>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            <span>Cetak</span>
        </button>
        <?php if ($user): ?>
            <?php 
            $can_edit = is_admin() || empty($laporan['user_id']) || ((int)$laporan['user_id'] === (int)$user['id']);
            if ($can_edit): 
            ?>
                <a href="edit-laporan.php?id=<?= (int)$laporan['id'] ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Edit</span>
                </a>
            <?php endif; ?>
            <?php if (is_admin()): ?>
                <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Hanya Administrator yang berhak menghapus arsip">
                    <i class="fa-solid fa-trash"></i>
                    <span>Hapus</span>
                </button>
            <?php endif; ?>
        <?php else: ?>
            <a href="login.php?redirect=<?= urlencode('detail.php?id=' . (int)$laporan['id']) ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Login aslab">
                <i class="fa-solid fa-lock"></i>
                <span>Login Aslab</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    
    <!-- Kolom Kiri: Rincian Fisik & Kondisi Barang -->
    <div class="col-12 col-lg-7">
        
        <!-- Header Info -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 text-center text-sm-start">
                    <div class="col-12 col-sm-4 border-end-sm">
                        <span class="text-muted small d-block">Tanggal Laporan</span>
                        <span class="fw-bold fs-6"><?= date('d M Y', strtotime($laporan['tanggal'])) ?></span>
                    </div>
                    <div class="col-12 col-sm-4 border-end-sm">
                        <span class="text-muted small d-block">Shift Kerja</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                            Shift <?= e($laporan['shift']) ?>
                        </span>
                    </div>
                    <div class="col-12 col-sm-4">
                        <span class="text-muted small d-block">Petugas Aslab</span>
                        <span class="fw-bold fs-6 d-flex align-items-center justify-content-center justify-content-sm-start gap-1">
                            <i class="fa-regular fa-user text-primary"></i>
                            <?= e($laporan['petugas_nama']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rincian Proyektor -->
        <div class="card mb-4 border-start border-primary border-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-video text-primary fs-5"></i>
                    <span class="fw-bold">Status Inventaris Proyektor</span>
                </div>
                <span class="badge bg-primary-subtle text-primary fs-6">Total: <?= $tot_proyektor ?> Unit</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="p-3 bg-body-tertiary rounded text-center">
                            <span class="text-muted small d-block mb-1">Siap Pakai / Tersedia</span>
                            <span class="h3 fw-bold text-success mb-0"><?= (int)$laporan['proyektor_tersedia'] ?></span>
                            <span class="small text-muted d-block">Unit</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-body-tertiary rounded text-center">
                            <span class="text-muted small d-block mb-1">Rusak / Kurang Kabel</span>
                            <span class="h3 fw-bold text-danger mb-0"><?= (int)$laporan['proyektor_rusak'] ?></span>
                            <span class="small text-muted d-block">Unit</span>
                        </div>
                    </div>
                </div>
                <?php if (!empty($laporan['proyektor_catatan'])): ?>
                    <div class="p-3 bg-danger-subtle text-danger rounded small">
                        <strong><i class="fa-solid fa-circle-exclamation me-1"></i> Keterangan Kendala:</strong>
                        <div><?= e($laporan['proyektor_catatan']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rincian Kabel Roll -->
        <div class="card mb-4 border-start border-success border-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-plug text-success fs-5"></i>
                    <span class="fw-bold">Status Inventaris Kabel Roll</span>
                </div>
                <span class="badge bg-success-subtle text-success fs-6">Total: <?= $tot_kabel ?> Unit</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="p-3 bg-body-tertiary rounded text-center">
                            <span class="text-muted small d-block mb-1">Siap Pakai / Tersedia</span>
                            <span class="h3 fw-bold text-success mb-0"><?= (int)$laporan['kabel_roll_tersedia'] ?></span>
                            <span class="small text-muted d-block">Unit</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-body-tertiary rounded text-center">
                            <span class="text-muted small d-block mb-1">Rusak / Tidak Bisa Dipakai</span>
                            <span class="h3 fw-bold text-danger mb-0"><?= (int)$laporan['kabel_roll_rusak'] ?></span>
                            <span class="small text-muted d-block">Unit</span>
                        </div>
                    </div>
                </div>
                <?php if (!empty($laporan['kabel_roll_catatan'])): ?>
                    <div class="p-3 bg-danger-subtle text-danger rounded small">
                        <strong><i class="fa-solid fa-triangle-exclamation me-1"></i> Catatan Kabel Roll:</strong>
                        <div><?= e($laporan['kabel_roll_catatan']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Memo Operasional Aslab -->
        <?php if (!empty($laporan['catatan_umum'])): ?>
            <div class="card mb-4">
                <div class="card-header py-3">
                    <i class="fa-solid fa-comment-dots text-warning me-2"></i>Catatan Operasional / Pengumuman Aslab
                </div>
                <div class="card-body">
                    <div class="p-3 bg-body-tertiary rounded border">
                        <p class="mb-0" style="color: var(--text-main);"><?= nl2br(e($laporan['catatan_umum'])) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Kolom Kanan: Foto Dokumentasi & WhatsApp Format Box -->
    <div class="col-12 col-lg-5">
        
        <!-- Foto Dokumentasi -->
        <div class="card mb-4">
            <div class="card-header py-3">
                <i class="fa-solid fa-camera text-primary me-2"></i>Foto Dokumentasi
            </div>
            <div class="card-body text-center">
                <?php if (!empty($laporan['foto_dokumentasi']) && photo_file_exists($laporan['foto_dokumentasi'])): ?>
                    <a href="lihat-foto.php?file=<?= urlencode($laporan['foto_dokumentasi']) ?>" target="_blank" class="image-preview-wrapper text-decoration-none" title="Klik untuk membuka foto ukuran penuh">
                        <div class="image-preview-container" style="height: 280px; max-height: 280px;">
                            <img src="lihat-foto.php?file=<?= urlencode($laporan['foto_dokumentasi']) ?>" alt="Foto Dokumentasi">
                            <div class="image-preview-overlay">
                                <span class="badge bg-dark bg-opacity-75 text-white px-3 py-2 rounded-pill small border border-secondary">
                                    <i class="fa-solid fa-up-right-and-down-left-from-center me-1 text-warning"></i> Buka Ukuran Penuh
                                </span>
                            </div>
                        </div>
                    </a>
                    <span class="text-muted small d-block mt-2">
                        <i class="fa-solid fa-up-right-from-square me-1"></i> Klik foto untuk melihat ukuran penuh
                    </span>
                <?php else: ?>
                    <div class="py-4 text-muted">
                        <i class="fa-regular fa-image fa-3x text-secondary mb-2"></i>
                        <p class="small mb-0">Tidak ada lampiran foto untuk laporan ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Format WhatsApp -->
        <div class="card mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-brands fa-whatsapp text-success fs-5"></i>
                    <span class="fw-bold">Teks Format WhatsApp</span>
                </div>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="copyToWhatsApp(<?= htmlspecialchars(json_encode($wa_text), ENT_QUOTES, 'UTF-8') ?>, this)">
                    <i class="fa-solid fa-copy me-1"></i> Salin
                </button>
            </div>
            <div class="card-body">
                <div class="whatsapp-box" style="font-size: 0.8rem;"><?= e($wa_text) ?></div>
            </div>
        </div>

    </div>

</div>

<?php if (is_admin()): ?>
<!-- Delete Confirmation Modal (Khusus Administrator) -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="deleteModalLabel">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Hapus Laporan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-1">Apakah Anda yakin ingin menghapus laporan pada tanggal <strong><?= date('d/m/Y', strtotime($laporan['tanggal'])) ?> (Shift <?= e($laporan['shift']) ?>)</strong>?</p>
                <p class="small text-muted mb-0">Laporan dan file foto dokumentasi terkait akan dihapus secara permanen dari server.</p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <form action="hapus-laporan.php" method="POST" class="d-inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="id" value="<?= (int)$laporan['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm d-flex align-items-center gap-1">
                        <i class="fa-solid fa-trash"></i>
                        <span>Ya, Hapus Sekarang</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

