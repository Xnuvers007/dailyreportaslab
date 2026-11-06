<?php
$page_title = "Input Laporan Harian";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

// Wajib login untuk membuat laporan
require_login("Silakan login sebagai aslab terlebih dahulu untuk membuat laporan harian.");

$errors = [];
$user = current_user();

// Nilai default form
$form_data = [
    'tanggal'             => date('Y-m-d'),
    'shift'               => 'Pagi',
    'petugas_nama'        => $user ? $user['nama_lengkap'] : '',
    'kabel_roll_tersedia' => 0,
    'kabel_roll_rusak'    => 0,
    'kabel_roll_catatan'  => '',
    'proyektor_tersedia'  => 0,
    'proyektor_rusak'     => 0,
    'proyektor_catatan'   => '',
    'catatan_umum'        => ''
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // 1. Validasi CSRF Token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = "Sesi form telah kedaluwarsa atau token tidak valid. Silakan coba lagi.";
    }

    // 2. Sanitasi & Ambil Input
    $tanggal            = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $shift              = trim($_POST['shift'] ?? 'Pagi');
    $petugas_nama       = trim($_POST['petugas_nama'] ?? '');
    
    $kabel_roll_tersedia = max(0, (int)($_POST['kabel_roll_tersedia'] ?? 0));
    $kabel_roll_rusak    = max(0, (int)($_POST['kabel_roll_rusak'] ?? 0));
    $kabel_roll_catatan  = trim($_POST['kabel_roll_catatan'] ?? '');
    
    $proyektor_tersedia  = max(0, (int)($_POST['proyektor_tersedia'] ?? 0));
    $proyektor_rusak     = max(0, (int)($_POST['proyektor_rusak'] ?? 0));
    $proyektor_catatan   = trim($_POST['proyektor_catatan'] ?? '');
    
    $catatan_umum        = trim($_POST['catatan_umum'] ?? '');

    // Simpan kembali untuk form repopulation jika error
    $form_data = [
        'tanggal'             => $tanggal,
        'shift'               => $shift,
        'petugas_nama'        => $petugas_nama,
        'kabel_roll_tersedia' => $kabel_roll_tersedia,
        'kabel_roll_rusak'    => $kabel_roll_rusak,
        'kabel_roll_catatan'  => $kabel_roll_catatan,
        'proyektor_tersedia'  => $proyektor_tersedia,
        'proyektor_rusak'     => $proyektor_rusak,
        'proyektor_catatan'   => $proyektor_catatan,
        'catatan_umum'        => $catatan_umum
    ];

    // 3. Validasi Dasar
    if (empty($petugas_nama)) {
        $errors[] = "Nama petugas aslab wajib diisi.";
    }
    if (!in_array($shift, ['Pagi', 'Siang', 'Sore', 'Malam'])) {
        $errors[] = "Pilihan shift tidak valid.";
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $errors[] = "Format tanggal tidak valid.";
    }

    // 4. Handle Upload Foto dengan Keamanan Ketat (Anti-RCE & Anti-Bypass)
    $foto_filename = null;
    if (isset($_FILES['foto_dokumentasi']) && $_FILES['foto_dokumentasi']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $foto_filename = handle_secure_upload($_FILES['foto_dokumentasi'], __DIR__ . '/uploads/');
        } catch (Exception $e) {
            $errors[] = "Gagal upload foto: " . $e->getMessage();
        }
    }

    // 5. Simpan ke Database jika tidak ada error (Prepared Statement Anti-SQLi)
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO `laporan` (
                        `user_id`, `tanggal`, `shift`, `petugas_nama`,
                        `kabel_roll_tersedia`, `kabel_roll_rusak`, `kabel_roll_catatan`,
                        `proyektor_tersedia`, `proyektor_rusak`, `proyektor_catatan`,
                        `catatan_umum`, `foto_dokumentasi`
                    ) VALUES (
                        :user_id, :tanggal, :shift, :petugas_nama,
                        :kabel_roll_tersedia, :kabel_roll_rusak, :kabel_roll_catatan,
                        :proyektor_tersedia, :proyektor_rusak, :proyektor_catatan,
                        :catatan_umum, :foto_dokumentasi
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id'             => $user ? $user['id'] : null,
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
                ':foto_dokumentasi'    => $foto_filename
            ]);

            $new_id = $pdo->lastInsertId();
            set_flash('success', "Laporan harian berhasil disimpan!");
            header("Location: detail.php?id=" . (int)$new_id);
            exit;
        } catch (PDOException $e) {
            error_log("Insert Laporan Error: " . $e->getMessage());
            $errors[] = "Terjadi kesalahan sistem saat menyimpan laporan. Silakan coba kembali.";
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
                    <i class="fa-solid fa-file-signature text-primary me-2"></i>Input Laporan Harian
                </h3>
                <p class="text-muted small mb-0">Catat jumlah fisik proyektor, kabel roll, dan kondisi perlengkapan lab.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm">
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

        <form action="buat-laporan.php" method="POST" enctype="multipart/form-data" class="needs-validation">
            <?= csrf_input() ?>

            <!-- Bagian 1: Data Shift & Petugas -->
            <div class="card mb-4">
                <div class="card-header py-3">
                    <i class="fa-solid fa-calendar-day text-primary me-2"></i>Informasi Waktu & Petugas
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="tanggal" class="form-label small fw-semibold">Tanggal Laporan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-calendar"></i></span>
                                <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= e($form_data['tanggal']) ?>" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="shift" class="form-label small fw-semibold">Shift Kerja</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-business-time"></i></span>
                                <select class="form-select" id="shift" name="shift" required>
                                    <option value="Pagi" <?= $form_data['shift'] === 'Pagi' ? 'selected' : '' ?>>Pagi (07:30 - 12:00)</option>
                                    <option value="Siang" <?= $form_data['shift'] === 'Siang' ? 'selected' : '' ?>>Siang (12:00 - 16:30)</option>
                                    <option value="Sore" <?= $form_data['shift'] === 'Sore' ? 'selected' : '' ?>>Sore (16:30 - 21:00)</option>
                                    <option value="Malam" <?= $form_data['shift'] === 'Malam' ? 'selected' : '' ?>>Malam</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="petugas_nama" class="form-label small fw-semibold">Nama Petugas Aslab</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                                <input type="text" class="form-control" id="petugas_nama" name="petugas_nama" placeholder="Contoh: Lucky" value="<?= e($form_data['petugas_nama']) ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Inventaris Kabel Roll -->
            <div class="card mb-4 border-start border-success border-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-plug text-success fs-5"></i>
                        <span class="fw-bold">Inventaris Kabel Roll / Stop Kontak</span>
                    </div>
                    <span class="badge bg-success-subtle text-success fs-6" id="total_kabel_display">0 Unit</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="kabel_roll_tersedia" class="form-label small fw-semibold text-success">
                                <i class="fa-solid fa-check-circle me-1"></i> Kabel Roll Siap Pakai / Tersedia
                            </label>
                            <input type="number" class="form-control form-control-lg fw-bold" id="kabel_roll_tersedia" name="kabel_roll_tersedia" min="0" value="<?= (int)$form_data['kabel_roll_tersedia'] ?>" required>
                            <div class="form-text small">Jumlah kabel roll yang siap dipinjamkan ke kelas/dosen.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="kabel_roll_rusak" class="form-label small fw-semibold text-danger">
                                <i class="fa-solid fa-circle-xmark me-1"></i> Kabel Roll Rusak / Tidak Siap
                            </label>
                            <input type="number" class="form-control form-control-lg fw-bold" id="kabel_roll_rusak" name="kabel_roll_rusak" min="0" value="<?= (int)$form_data['kabel_roll_rusak'] ?>">
                            <div class="form-text small">Unit yang putus, terbakar, atau sedang diperbaiki.</div>
                        </div>
                        <div class="col-12">
                            <label for="kabel_roll_catatan" class="form-label small fw-semibold">Keterangan / Detail Kerusakan Kabel</label>
                            <input type="text" class="form-control" id="kabel_roll_catatan" name="kabel_roll_catatan" placeholder="Contoh: 1 Gabisa digunakan kata pak Agus Wiranto" value="<?= e($form_data['kabel_roll_catatan']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Inventaris Proyektor -->
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
                            <label for="proyektor_tersedia" class="form-label small fw-semibold text-primary">
                                <i class="fa-solid fa-check-circle me-1"></i> Proyektor Siap Pakai / Tersedia
                            </label>
                            <input type="number" class="form-control form-control-lg fw-bold" id="proyektor_tersedia" name="proyektor_tersedia" min="0" value="<?= (int)$form_data['proyektor_tersedia'] ?>" required>
                            <div class="form-text small">Jumlah proyektor dalam kondisi baik lengkap dengan kabel.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="proyektor_rusak" class="form-label small fw-semibold text-danger">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Proyektor Rusak / Kurang Kabel
                            </label>
                            <input type="number" class="form-control form-control-lg fw-bold" id="proyektor_rusak" name="proyektor_rusak" min="0" value="<?= (int)$form_data['proyektor_rusak'] ?>">
                            <div class="form-text small">Unit mati total, lampu redup, atau kabel power/HDMI kurang.</div>
                        </div>
                        <div class="col-12">
                            <label for="proyektor_catatan" class="form-label small fw-semibold">Keterangan / Lokasi Kendala Proyektor</label>
                            <input type="text" class="form-control" id="proyektor_catatan" name="proyektor_catatan" placeholder="Contoh: 5 Unit rusak / kurang kabel di Lab 3" value="<?= e($form_data['proyektor_catatan']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 4: Catatan Operasional & Bukti Foto -->
            <div class="card mb-4">
                <div class="card-header py-3">
                    <i class="fa-solid fa-camera-retro text-warning me-2"></i>Catatan Operasional & Dokumentasi Foto
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="catatan_umum" class="form-label small fw-semibold">Catatan Khusus / Memo Operasional Aslab</label>
                            <textarea class="form-control" id="catatan_umum" name="catatan_umum" rows="3" placeholder="Contoh: semua kabel baru di kardus ini bisa dipake mulai hari ini"><?= e($form_data['catatan_umum']) ?></textarea>
                            <div class="form-text small">Informasi penting untuk aslab shift berikutnya atau pengumuman barang baru datang.</div>
                        </div>
                        <div class="col-12">
                            <label for="foto_dokumentasi" class="form-label small fw-semibold">Upload Foto Bukti (Kardus Kabel / Fisik Rusak)</label>
                            <input type="file" class="form-control" id="foto_dokumentasi" name="foto_dokumentasi" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text small">Format: JPG, PNG, WEBP. Maksimal 5 MB.</div>
                            
                            <!-- Container Preview Gambar -->
                            <div id="image_preview_box" class="image-preview-container mt-3 d-none">
                                <img id="preview_img" src="#" alt="Preview Foto">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="d-flex justify-content-end gap-2 mb-5">
                <a href="index.php" class="btn btn-outline-secondary px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Laporan Harian</span>
                </button>
            </div>

        </form>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

