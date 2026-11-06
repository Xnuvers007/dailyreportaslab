<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

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
    $petugas_nama       = trim($_POST['petugas_nama'] ?? '');
    
    $kabel_roll_tersedia = max(0, (int)($_POST['kabel_roll_tersedia'] ?? 0));
    $kabel_roll_rusak    = max(0, (int)($_POST['kabel_roll_rusak'] ?? 0));
    $kabel_roll_catatan  = trim($_POST['kabel_roll_catatan'] ?? '');
    
    $proyektor_tersedia  = max(0, (int)($_POST['proyektor_tersedia'] ?? 0));
    $proyektor_rusak     = max(0, (int)($_POST['proyektor_rusak'] ?? 0));
    $proyektor_catatan   = trim($_POST['proyektor_catatan'] ?? '');
    
    $catatan_umum        = trim($_POST['catatan_umum'] ?? '');
    $hapus_foto          = !empty($_POST['hapus_foto']);

    // 3. Validasi
    if (empty($petugas_nama)) {
        $errors[] = "Nama petugas aslab wajib diisi.";
    }
    if (!in_array($shift, ['Pagi', 'Siang', 'Sore', 'Malam'])) {
        $errors[] = "Pilihan shift tidak valid.";
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $errors[] = "Format tanggal tidak valid.";
    }

    $foto_filename = $laporan['foto_dokumentasi'];

    // Jika user mencentang hapus foto
    if ($hapus_foto && $foto_filename) {
        $file_to_delete = __DIR__ . '/uploads/' . basename($foto_filename);
        if (file_exists($file_to_delete)) {
            @unlink($file_to_delete);
        }
        $foto_filename = null;
    }

    // Jika user mengunggah foto baru
    if (isset($_FILES['foto_dokumentasi']) && $_FILES['foto_dokumentasi']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $new_foto = handle_secure_upload($_FILES['foto_dokumentasi'], __DIR__ . '/uploads/');
            if ($new_foto) {
                // Hapus foto lama jika ada
                if (!empty($laporan['foto_dokumentasi'])) {
                    $old_file = __DIR__ . '/uploads/' . basename($laporan['foto_dokumentasi']);
                    if (file_exists($old_file)) {
                        @unlink($old_file);
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

            set_flash('success', "Laporan berhasil diperbarui.");
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

<div class="row justify-content-center">
    <div class="col-12 col-lg-9">
        
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="h4 mb-1 fw-bold">
                    <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Laporan Harian
                </h3>
                <p class="text-muted small mb-0">Ubah rincian stok atau catatan laporan #<?= (int)$laporan['id'] ?>.</p>
            </div>
            <a href="detail.php?id=<?= (int)$laporan['id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Batal & Kembali
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0 ps-3 small">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="edit-laporan.php?id=<?= (int)$laporan['id'] ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_input() ?>

            <!-- Informasi Waktu & Petugas -->
            <div class="card mb-4">
                <div class="card-header py-3">
                    <i class="fa-solid fa-calendar-day text-primary me-2"></i>Informasi Waktu & Petugas
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="tanggal" class="form-label small fw-semibold">Tanggal Laporan</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= e($laporan['tanggal']) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="shift" class="form-label small fw-semibold">Shift Kerja</label>
                            <select class="form-select" id="shift" name="shift" required>
                                <option value="Pagi" <?= $laporan['shift'] === 'Pagi' ? 'selected' : '' ?>>Pagi (07:30 - 12:00)</option>
                                <option value="Siang" <?= $laporan['shift'] === 'Siang' ? 'selected' : '' ?>>Siang (12:00 - 16:30)</option>
                                <option value="Sore" <?= $laporan['shift'] === 'Sore' ? 'selected' : '' ?>>Sore (16:30 - 21:00)</option>
                                <option value="Malam" <?= $laporan['shift'] === 'Malam' ? 'selected' : '' ?>>Malam</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="petugas_nama" class="form-label small fw-semibold">Nama Petugas Aslab</label>
                            <input type="text" class="form-control" id="petugas_nama" name="petugas_nama" value="<?= e($laporan['petugas_nama']) ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kabel Roll -->
            <div class="card mb-4 border-start border-success border-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-plug text-success fs-5"></i>
                        <span class="fw-bold">Inventaris Kabel Roll</span>
                    </div>
                    <span class="badge bg-success-subtle text-success fs-6" id="total_kabel_display">0 Unit</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="kabel_roll_tersedia" class="form-label small fw-semibold text-success">Kabel Roll Siap Pakai / Tersedia</label>
                            <input type="number" class="form-control form-control-lg fw-bold" id="kabel_roll_tersedia" name="kabel_roll_tersedia" min="0" value="<?= (int)$laporan['kabel_roll_tersedia'] ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="kabel_roll_rusak" class="form-label small fw-semibold text-danger">Kabel Roll Rusak / Tidak Siap</label>
                            <input type="number" class="form-control form-control-lg fw-bold" id="kabel_roll_rusak" name="kabel_roll_rusak" min="0" value="<?= (int)$laporan['kabel_roll_rusak'] ?>">
                        </div>
                        <div class="col-12">
                            <label for="kabel_roll_catatan" class="form-label small fw-semibold">Keterangan Kerusakan Kabel</label>
                            <input type="text" class="form-control" id="kabel_roll_catatan" name="kabel_roll_catatan" value="<?= e($laporan['kabel_roll_catatan']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Proyektor -->
            <div class="card mb-4 border-start border-primary border-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-video text-primary fs-5"></i>
                        <span class="fw-bold">Inventaris Proyektor</span>
                    </div>
                    <span class="badge bg-primary-subtle text-primary fs-6" id="total_proyektor_display">0 Unit</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="proyektor_tersedia" class="form-label small fw-semibold text-primary">Proyektor Siap Pakai / Tersedia</label>
                            <input type="number" class="form-control form-control-lg fw-bold" id="proyektor_tersedia" name="proyektor_tersedia" min="0" value="<?= (int)$laporan['proyektor_tersedia'] ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="proyektor_rusak" class="form-label small fw-semibold text-danger">Proyektor Rusak / Kurang Kabel</label>
                            <input type="number" class="form-control form-control-lg fw-bold" id="proyektor_rusak" name="proyektor_rusak" min="0" value="<?= (int)$laporan['proyektor_rusak'] ?>">
                        </div>
                        <div class="col-12">
                            <label for="proyektor_catatan" class="form-label small fw-semibold">Keterangan Kendala Proyektor</label>
                            <input type="text" class="form-control" id="proyektor_catatan" name="proyektor_catatan" value="<?= e($laporan['proyektor_catatan']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Catatan & Foto -->
            <div class="card mb-4">
                <div class="card-header py-3">
                    <i class="fa-solid fa-camera-retro text-warning me-2"></i>Catatan Operasional & Foto
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="catatan_umum" class="form-label small fw-semibold">Catatan Khusus / Memo Operasional</label>
                            <textarea class="form-control" id="catatan_umum" name="catatan_umum" rows="3"><?= e($laporan['catatan_umum']) ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label for="foto_dokumentasi" class="form-label small fw-semibold">Ganti Foto Dokumentasi (Opsional)</label>
                            <input type="file" class="form-control" id="foto_dokumentasi" name="foto_dokumentasi" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text small">Biarkan kosong jika tidak ingin mengubah foto.</div>

                            <?php if (!empty($laporan['foto_dokumentasi']) && file_exists(__DIR__ . '/uploads/' . $laporan['foto_dokumentasi'])): ?>
                                <div class="mt-3 p-3 bg-body-tertiary rounded border">
                                    <div class="small fw-semibold mb-2">Foto Saat Ini:</div>
                                    <img src="uploads/<?= e($laporan['foto_dokumentasi']) ?>" alt="Foto Sekarang" class="img-thumbnail" style="max-height: 120px;">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="hapus_foto" value="1" id="hapus_foto">
                                        <label class="form-check-label small text-danger" for="hapus_foto">
                                            <i class="fa-solid fa-trash me-1"></i> Centang untuk menghapus foto ini tanpa mengganti yang baru
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Live Preview Foto Baru -->
                            <div id="image_preview_box" class="image-preview-container mt-3 d-none">
                                <img id="preview_img" src="#" alt="Preview Foto">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-5">
                <a href="detail.php?id=<?= (int)$laporan['id'] ?>" class="btn btn-outline-secondary px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-check"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>

        </form>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

