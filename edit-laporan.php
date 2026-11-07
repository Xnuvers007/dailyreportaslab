<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/logger.php';

// Wajib login untuk mengedit laporan
require_login("Silakan login sebagai aslab terlebih dahulu untuk mengedit laporan.");

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', "ID laporan tidak valid.");
    header("Location: riwayat.php");
    exit;
}

// Ambil data laporan yang akan diedit
$stmt = $pdo->prepare("SELECT * FROM `laporan` WHERE `id` = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$laporan = $stmt->fetch();

if (!$laporan) {
    set_flash('danger', "Laporan tidak ditemukan.");
    header("Location: riwayat.php");
    exit;
}

$page_title = "Edit Laporan - " . date('d/m/Y', strtotime($laporan['tanggal']));
$user = current_user();

// Kontrol Akses Berbasis Kepemilikan (Anti-IDOR / Broken Access Control):
// Hanya Admin atau petugas pembuat laporan yang berhak mengedit laporan ini.
if (!is_admin() && !empty($laporan['user_id']) && (int)$laporan['user_id'] !== (int)$user['id']) {
    set_flash('danger', "Akses ditolak. Anda hanya berhak mengedit laporan yang Anda buat sendiri. Hubungi Administrator jika perlu perubahan.");
    header("Location: detail.php?id=" . (int)$id);
    exit;
}

$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // 1. Validasi CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = "Sesi form telah kedaluwarsa atau token tidak valid.";
    }

    // 2. Sanitasi Input
    $tanggal            = trim($_POST['tanggal'] ?? $laporan['tanggal']);
    $shift              = trim($_POST['shift'] ?? $laporan['shift']);
    
    // KEAMANAN INTEGRITAS KETAT (ANTI-TAMPER):
    // Nama petugas dijaga sesuai pembuat asli atau nama akun login aktif,
    // tidak boleh ditimpa dari manipulasi DevTools / raw POST.
    $petugas_nama       = !empty($laporan['petugas_nama']) ? $laporan['petugas_nama'] : ($user ? $user['nama_lengkap'] : 'Asisten Lab');
    
    $kabel_roll_tersedia = max(0, (int)($_POST['kabel_roll_tersedia'] ?? 0));
    $kabel_roll_rusak    = max(0, (int)($_POST['kabel_roll_rusak'] ?? 0));
    $kabel_roll_catatan  = trim($_POST['kabel_roll_catatan'] ?? '');
    
    $proyektor_tersedia  = max(0, (int)($_POST['proyektor_tersedia'] ?? 0));
    $proyektor_rusak     = max(0, (int)($_POST['proyektor_rusak'] ?? 0));
    $proyektor_catatan   = trim($_POST['proyektor_catatan'] ?? '');
    
    $catatan_umum        = trim($_POST['catatan_umum'] ?? '');
    $hapus_foto          = !empty($_POST['hapus_foto']);

    // 3. Validasi
    if (!in_array($shift, ['Pagi', 'Siang', 'Malam', '1 Hari', 'Full Day', 'Sore'])) {
        $errors[] = "Pilihan shift tidak valid.";
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $errors[] = "Format tanggal tidak valid.";
    }

    $foto_filename = $laporan['foto_dokumentasi'];

    // Jika user mencentang hapus foto (Anti-Path-Traversal & Server-Agnostic Storage)
    $uploads_dir = get_uploads_directory();
    if ($hapus_foto && $foto_filename) {
        $file_to_delete = $uploads_dir . DIRECTORY_SEPARATOR . basename($foto_filename);
        $real_delete    = realpath($file_to_delete);
        if ($real_delete && $uploads_dir && strpos($real_delete, $uploads_dir) === 0 && is_file($real_delete)) {
            @unlink($real_delete);
        }
        $foto_filename = null;
    }

    // Jika user mengunggah foto baru
    if (isset($_FILES['foto_dokumentasi']) && $_FILES['foto_dokumentasi']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $new_foto = handle_secure_upload($_FILES['foto_dokumentasi']);
            if ($new_foto) {
                // Hapus foto lama jika ada secara aman
                if (!empty($laporan['foto_dokumentasi'])) {
                    $old_file = $uploads_dir . DIRECTORY_SEPARATOR . basename($laporan['foto_dokumentasi']);
                    $real_old = realpath($old_file);
                    if ($real_old && $uploads_dir && strpos($real_old, $uploads_dir) === 0 && is_file($real_old)) {
                        @unlink($real_old);
                    }
                }
                $foto_filename = $new_foto;
            }
        } catch (Exception $e) {
            $errors[] = "Gagal upload foto: " . $e->getMessage();
        }
    }

    // 4. Update Database
    if (empty($errors)) {
        try {
            $sql = "UPDATE `laporan` SET
                        `tanggal` = :tanggal,
                        `shift` = :shift,
                        `petugas_nama` = :petugas_nama,
                        `kabel_roll_tersedia` = :kabel_roll_tersedia,
                        `kabel_roll_rusak` = :kabel_roll_rusak,
                        `kabel_roll_catatan` = :kabel_roll_catatan,
                        `proyektor_tersedia` = :proyektor_tersedia,
                        `proyektor_rusak` = :proyektor_rusak,
                        `proyektor_catatan` = :proyektor_catatan,
                        `catatan_umum` = :catatan_umum,
                        `foto_dokumentasi` = :foto_dokumentasi
                    WHERE `id` = :id";
            
            $stmt_update = $pdo->prepare($sql);
            $stmt_update->execute([
                ':tanggal'             => $tanggal,
                ':shift'               => $shift,
                ':petugas_nama'        => $petugas_nama,
                ':kabel_roll_tersedia' => $kabel_roll_tersedia,
                ':kabel_roll_rusak'    => $kabel_roll_rusak,
                ':kabel_roll_catatan'  => $kabel_roll_catatan ?: null,
                ':proyektor_tersedia'  => $proyektor_tersedia,
                ':proyektor_rusak'     => $proyektor_rusak,
                ':proyektor_catatan'   => $proyektor_catatan ?: null,
                ':catatan_umum'        => $catatan_umum ?: null,
                ':foto_dokumentasi'    => $foto_filename,
                ':id'                  => $id
            ]);

            // Catat log audit aktivitas
            record_log('UPDATE_REPORT', "Memperbarui laporan shift {$shift} tanggal " . date('d/m/Y', strtotime($tanggal)) . " (ID #{$id})", 'SUCCESS');

            set_flash('success', "Laporan tanggal " . date('d/m/Y', strtotime($tanggal)) . " berhasil diperbarui.");
            header("Location: detail.php?id=" . (int)$id);
            exit;
        } catch (PDOException $e) {
            error_log("Update Laporan Error: " . $e->getMessage());
            $errors[] = "Gagal memperbarui data laporan. Silakan coba lagi.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center animate-fade-up">
    <div class="col-12 col-xl-10">
        
        <!-- Header Page Title -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <div class="hologram-tag mb-2">
                    <i class="fa-solid fa-pen-to-square text-warning"></i>
                    <span>Mode Edit &bull; ID #<?= (int)$laporan['id'] ?></span>
                </div>
                <h2 class="h3 fw-bold mb-1">
                    Edit Laporan Harian Inventaris
                </h2>
                <p class="text-muted small mb-0">
                    Perbarui data ketersediaan fisik kabel roll, unit proyektor, atau perbaiki catatan operasional aslab.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="detail.php?id=<?= (int)$laporan['id'] ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                    <i class="fa-solid fa-eye"></i>
                    <span>Lihat Detail</span>
                </a>
                <a href="riwayat.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Batal</span>
                </a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm border-0 border-start border-danger border-4 mb-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-circle-exclamation text-danger fs-5"></i>
                    <strong>Mohon perbaiki kesalahan berikut:</strong>
                </div>
                <ul class="mb-0 ps-3 small">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="edit-laporan.php?id=<?= (int)$laporan['id'] ?>" method="POST" enctype="multipart/form-data" class="needs-validation">
            <?= csrf_input() ?>

            <!-- KARTU 1: Shift & Identitas Petugas -->
            <div class="card mb-4 card-hover-lift border-gradient-top">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon-box stat-primary" style="width: 38px; height: 38px; font-size: 1.1rem;">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <span class="fw-bold">1. Informasi Waktu & Petugas Shift</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        
                        <!-- Pilihan Shift Kerja Modern -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-uppercase text-muted" style="letter-spacing: 0.05em;">
                                Pilih Shift Kerja
                            </label>
                            <div class="shift-selector-grid">
                                
                                <div class="shift-radio-item">
                                    <input type="radio" id="edit_shift_pagi" name="shift" value="Pagi" <?= $laporan['shift'] === 'Pagi' ? 'checked' : '' ?> required>
                                    <label for="edit_shift_pagi" class="shift-radio-label">
                                        <i class="fa-solid fa-sun text-warning"></i>
                                        <span class="shift-name">Shift Pagi</span>
                                        <span class="shift-time">07:00 - 10:00</span>
                                    </label>
                                </div>

                                <div class="shift-radio-item">
                                    <input type="radio" id="edit_shift_siang" name="shift" value="Siang" <?= $laporan['shift'] === 'Siang' ? 'checked' : '' ?>>
                                    <label for="edit_shift_siang" class="shift-radio-label">
                                        <i class="fa-solid fa-cloud-sun text-primary"></i>
                                        <span class="shift-name">Shift Siang</span>
                                        <span class="shift-time">10:00 - 15:00</span>
                                    </label>
                                </div>

                                <div class="shift-radio-item">
                                    <input type="radio" id="edit_shift_malam" name="shift" value="Malam" <?= $laporan['shift'] === 'Malam' ? 'checked' : '' ?>>
                                    <label for="edit_shift_malam" class="shift-radio-label">
                                        <i class="fa-solid fa-moon text-info"></i>
                                        <span class="shift-name">Shift Malam</span>
                                        <span class="shift-time">16:00 - 21:30</span>
                                    </label>
                                </div>

                                <div class="shift-radio-item">
                                    <input type="radio" id="edit_shift_1hari" name="shift" value="1 Hari" <?= ($laporan['shift'] === '1 Hari' || $laporan['shift'] === 'Full Day') ? 'checked' : '' ?>>
                                    <label for="edit_shift_1hari" class="shift-radio-label">
                                        <i class="fa-solid fa-clock-rotate-left text-success"></i>
                                        <span class="shift-name">Shift 1 Hari</span>
                                        <span class="shift-time">07:00 - 21:30 (Full)</span>
                                    </label>
                                </div>

                                <?php if ($laporan['shift'] === 'Sore'): ?>
                                    <div class="shift-radio-item">
                                        <input type="radio" id="edit_shift_sore" name="shift" value="Sore" checked>
                                        <label for="edit_shift_sore" class="shift-radio-label">
                                            <i class="fa-solid fa-cloud-moon text-secondary"></i>
                                            <span class="shift-name">Shift Sore</span>
                                            <span class="shift-time">Legacy</span>
                                        </label>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>

                        <!-- Tanggal & Petugas -->
                        <div class="col-12 col-md-6">
                            <label for="tanggal" class="form-label small fw-semibold text-muted">Tanggal Pelaporan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-calendar-days"></i></span>
                                <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= e($laporan['tanggal']) ?>" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="petugas_nama" class="form-label small fw-semibold text-muted d-flex align-items-center justify-content-between">
                                <span>Nama Petugas Aslab Jaga</span>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    <i class="fa-solid fa-lock me-1"></i>Terkunci Permanen
                                </span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary border-end-0">
                                    <i class="fa-solid fa-shield-halved text-primary"></i>
                                </span>
                                <input type="text" class="form-control bg-body-tertiary border-start-0 fw-bold" id="petugas_nama" name="petugas_nama" value="<?= e($laporan['petugas_nama']) ?>" readonly>
                            </div>
                            <div class="form-text small text-muted">
                                <i class="fa-solid fa-circle-check text-success me-1"></i>Identitas petugas terkunci dan diverifikasi server (anti-tamper).
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- KARTU 2: Inventaris Kabel Roll -->
            <div class="card mb-4 card-hover-lift border-start border-warning border-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon-box stat-orange" style="width: 38px; height: 38px; font-size: 1.1rem;">
                            <i class="fa-solid fa-plug"></i>
                        </div>
                        <div>
                            <span class="fw-bold">2. Inventaris Kabel Roll / Stop Kontak</span>
                            <span class="badge bg-secondary-subtle text-muted ms-2 small">Opsional</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted d-none d-sm-inline">Total Fisik:</span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-6" id="total_kabel_display">
                            <?= (int)$laporan['kabel_roll_tersedia'] + (int)$laporan['kabel_roll_rusak'] ?> Unit
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i>Boleh dikosongkan atau diisi 0 jika shift ini hanya membuat catatan memo / operasional lab saja.
                    </p>
                    <div class="row g-4">
                        
                        <!-- Kabel Siap -->
                        <div class="col-12 col-md-6">
                            <div class="p-3 bg-body-tertiary rounded-3 border">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label for="kabel_roll_tersedia" class="form-label small fw-bold text-success mb-0">
                                        <i class="fa-solid fa-circle-check me-1"></i> Siap Pakai / Tersedia
                                    </label>
                                    <span class="badge bg-success-subtle text-success small">Siap Pakai</span>
                                </div>
                                <div class="number-stepper">
                                    <button type="button" class="stepper-btn btn-minus" title="Kurangi"><i class="fa-solid fa-minus"></i></button>
                                    <input type="number" id="kabel_roll_tersedia" name="kabel_roll_tersedia" min="0" value="<?= (int)$laporan['kabel_roll_tersedia'] ?>">
                                    <button type="button" class="stepper-btn btn-plus" title="Tambah"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Kabel Rusak -->
                        <div class="col-12 col-md-6">
                            <div class="p-3 bg-body-tertiary rounded-3 border">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label for="kabel_roll_rusak" class="form-label small fw-bold text-danger mb-0">
                                        <i class="fa-solid fa-circle-xmark me-1"></i> Rusak / Tidak Siap
                                    </label>
                                    <span class="badge bg-danger-subtle text-danger small">Bermasalah</span>
                                </div>
                                <div class="number-stepper">
                                    <button type="button" class="stepper-btn btn-minus" title="Kurangi"><i class="fa-solid fa-minus"></i></button>
                                    <input type="number" id="kabel_roll_rusak" name="kabel_roll_rusak" min="0" value="<?= (int)$laporan['kabel_roll_rusak'] ?>">
                                    <button type="button" class="stepper-btn btn-plus" title="Tambah"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Kabel -->
                        <div class="col-12">
                            <label for="kabel_roll_catatan" class="form-label small fw-semibold text-muted">
                                Keterangan Kerusakan Kabel Roll
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-circle-info"></i></span>
                                <input type="text" class="form-control" id="kabel_roll_catatan" name="kabel_roll_catatan" value="<?= e($laporan['kabel_roll_catatan']) ?>">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- KARTU 3: Inventaris Proyektor -->
            <div class="card mb-4 card-hover-lift border-start border-primary border-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon-box stat-primary" style="width: 38px; height: 38px; font-size: 1.1rem;">
                            <i class="fa-solid fa-video"></i>
                        </div>
                        <div>
                            <span class="fw-bold">3. Inventaris Proyektor Lab</span>
                            <span class="badge bg-secondary-subtle text-muted ms-2 small">Opsional</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted d-none d-sm-inline">Total Fisik:</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6" id="total_proyektor_display">
                            <?= (int)$laporan['proyektor_tersedia'] + (int)$laporan['proyektor_rusak'] ?> Unit
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i>Boleh dikosongkan atau diisi 0 jika shift ini hanya membuat catatan memo / operasional lab saja.
                    </p>
                    <div class="row g-4">
                        
                        <!-- Proyektor Siap -->
                        <div class="col-12 col-md-6">
                            <div class="p-3 bg-body-tertiary rounded-3 border">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label for="proyektor_tersedia" class="form-label small fw-bold text-primary mb-0">
                                        <i class="fa-solid fa-circle-check me-1"></i> Proyektor Siap Digunakan
                                    </label>
                                    <span class="badge bg-primary-subtle text-primary small">Normal & Siap</span>
                                </div>
                                <div class="number-stepper">
                                    <button type="button" class="stepper-btn btn-minus" title="Kurangi"><i class="fa-solid fa-minus"></i></button>
                                    <input type="number" id="proyektor_tersedia" name="proyektor_tersedia" min="0" value="<?= (int)$laporan['proyektor_tersedia'] ?>">
                                    <button type="button" class="stepper-btn btn-plus" title="Tambah"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Proyektor Rusak -->
                        <div class="col-12 col-md-6">
                            <div class="p-3 bg-body-tertiary rounded-3 border">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label for="proyektor_rusak" class="form-label small fw-bold text-danger mb-0">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Rusak / Kurang Kabel
                                    </label>
                                    <span class="badge bg-danger-subtle text-danger small">Trouble</span>
                                </div>
                                <div class="number-stepper">
                                    <button type="button" class="stepper-btn btn-minus" title="Kurangi"><i class="fa-solid fa-minus"></i></button>
                                    <input type="number" id="proyektor_rusak" name="proyektor_rusak" min="0" value="<?= (int)$laporan['proyektor_rusak'] ?>">
                                    <button type="button" class="stepper-btn btn-plus" title="Tambah"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Proyektor -->
                        <div class="col-12">
                            <label for="proyektor_catatan" class="form-label small fw-semibold text-muted">
                                Keterangan Kerusakan / Detail Proyektor
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-screwdriver-wrench"></i></span>
                                <input type="text" class="form-control" id="proyektor_catatan" name="proyektor_catatan" value="<?= e($laporan['proyektor_catatan']) ?>">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- KARTU 4: Memo & Foto -->
            <div class="card mb-4 card-hover-lift">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon-box stat-secondary" style="width: 38px; height: 38px; font-size: 1.1rem;">
                            <i class="fa-solid fa-bullhorn text-warning"></i>
                        </div>
                        <span class="fw-bold">4. Memo & Dokumentasi Foto</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        
                        <div class="col-12">
                            <label for="catatan_umum" class="form-label small fw-semibold text-muted">Catatan Khusus / Memo Operasional</label>
                            <textarea class="form-control" id="catatan_umum" name="catatan_umum" rows="3"><?= e($laporan['catatan_umum']) ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Foto Dokumentasi</label>
                            
                            <?php if (!empty($laporan['foto_dokumentasi']) && file_exists(__DIR__ . '/uploads/' . $laporan['foto_dokumentasi'])): ?>
                                <div class="p-3 bg-body-tertiary rounded-3 border mb-3">
                                    <div class="small fw-bold mb-2 text-primary"><i class="fa-solid fa-image me-1"></i> Foto Saat Ini:</div>
                                    <div class="image-preview-container" style="height: 180px;">
                                        <img src="lihat-foto.php?file=<?= urlencode($laporan['foto_dokumentasi']) ?>" alt="Foto Sekarang">
                                    </div>
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" name="hapus_foto" value="1" id="hapus_foto">
                                        <label class="form-check-label small text-danger fw-semibold" for="hapus_foto">
                                            <i class="fa-solid fa-trash me-1"></i> Hapus foto ini dari server
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <input type="file" class="d-none" id="foto_dokumentasi" name="foto_dokumentasi" accept="image/jpeg,image/png,image/webp">

                            <div class="upload-dropzone" id="upload_dropzone">
                                <div class="dropzone-icon">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                </div>
                                <h6 class="fw-bold mb-1">Upload / Ganti Foto Dokumentasi</h6>
                                <p class="text-muted small mb-0">Klik atau geser file baru untuk mengganti foto lama</p>
                            </div>

                            <div id="image_preview_box" class="mt-3 p-3 bg-body-tertiary rounded-3 border d-none position-relative">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-bold text-success"><i class="fa-solid fa-circle-check me-1"></i> Pratinjau Foto Baru:</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" id="btn_remove_preview">
                                        <i class="fa-solid fa-trash me-1"></i> Batalkan Foto Baru
                                    </button>
                                </div>
                                <div class="image-preview-container" style="height: 200px;">
                                    <img id="preview_img" src="#" alt="Preview Foto">
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

            <!-- Sticky Live Summary Bar & Action Button -->
            <div class="form-live-summary-card mb-5">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-plug text-warning"></i>
                            <span class="small text-muted">Kabel:</span>
                            <span class="fw-bold" id="live_summary_kabel"><?= (int)$laporan['kabel_roll_tersedia'] ?> Unit Siap</span>
                        </div>
                        <span class="text-muted opacity-50">&bull;</span>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-video text-primary"></i>
                            <span class="small text-muted">Proyektor:</span>
                            <span class="fw-bold" id="live_summary_proyektor"><?= (int)$laporan['proyektor_tersedia'] ?> Unit Siap</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="detail.php?id=<?= (int)$laporan['id'] ?>" class="btn btn-outline-secondary px-3">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2 shadow">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </div>
            </div>

        </form>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
