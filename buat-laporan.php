<?php
$page_title = "Input Laporan Harian";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/logger.php';

// Wajib login untuk membuat laporan
require_login("Silakan login sebagai aslab terlebih dahulu untuk membuat laporan harian.");

$errors = [];
$user = current_user();

// Nilai default form
$form_data = [
    'tanggal'             => date('Y-m-d'),
    'shift'               => 'Pagi',
    'petugas_nama'        => $user ? $user['nama_lengkap'] : '',
    'kabel_roll_tersedia' => 10,
    'kabel_roll_rusak'    => 1,
    'kabel_roll_catatan'  => '',
    'proyektor_tersedia'  => 38,
    'proyektor_rusak'     => 5,
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
    
    // KEAMANAN INTEGRITAS KETAT (ANTI-TAMPER):
    // Nama petugas tidak boleh diambil dari payload user mentah. 
    // Sekalipun dimodifikasi via Inspect Element / DevTools / HTTP injection,
    // server backend selalu memaksa nama petugas sesuai akun sesi login yang terotentikasi!
    $petugas_nama       = $user ? $user['nama_lengkap'] : 'Asisten Lab';
    
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
    if (!in_array($shift, ['Pagi', 'Siang', 'Malam', '1 Hari', 'Full Day', 'Sore'])) {
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
            
            // Catat Log Audit Aktivitas
            record_log('CREATE_REPORT', "Menginput laporan harian shift {$shift} (Kabel: {$kabel_roll_tersedia} siap, Proyektor: {$proyektor_tersedia} siap) ID #{$new_id}", 'SUCCESS');

            set_flash('success', "Laporan harian shift {$shift} berhasil disimpan!");
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

<div class="row justify-content-center animate-fade-up">
    <div class="col-12 col-xl-10">
        
        <!-- Header Page Title -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <div class="hologram-tag mb-2 d-inline-flex align-items-center gap-2">
                    <img src="assets/img/logo-unpam.png" alt="UNPAM" style="height: 18px; width: auto; object-fit: contain;">
                    <span>Formulir Serah Terima Shift &bull; Lab TI UNPAM</span>
                </div>
                <h2 class="h3 fw-bold mb-1">
                    Input Laporan Harian Inventaris
                </h2>
                <p class="text-muted small mb-0">
                    Catat ketersediaan fisik kabel roll, unit proyektor, dan catatan operasional shift sebelum perkuliahan dimulai.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="riwayat.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Riwayat Arsip</span>
                </a>
                <a href="index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm border-0 border-start border-danger border-4 mb-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-circle-exclamation text-danger fs-5"></i>
                    <strong>Mohon periksa dan lengkapi input berikut:</strong>
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

            <!-- KARTU 1: Shift & Identitas Petugas -->
            <div class="card mb-4 card-hover-lift border-gradient-top">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon-box stat-primary" style="width: 38px; height: 38px; font-size: 1.1rem;">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <span class="fw-bold">1. Informasi Waktu & Petugas Shift</span>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small px-3">
                        <i class="fa-regular fa-clock me-1"></i> <?= date('H:i') ?> WIB
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        
                        <!-- Pilihan Shift Kerja Modern (Radio Card Grid) -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-uppercase text-muted" style="letter-spacing: 0.05em;">
                                Pilih Shift Kerja
                            </label>
                            <div class="shift-selector-grid">
                                
                                <div class="shift-radio-item">
                                    <input type="radio" id="shift_pagi" name="shift" value="Pagi" <?= $form_data['shift'] === 'Pagi' ? 'checked' : '' ?> required>
                                    <label for="shift_pagi" class="shift-radio-label">
                                        <i class="fa-solid fa-sun text-warning"></i>
                                        <span class="shift-name">Shift Pagi</span>
                                        <span class="shift-time">07:00 - 10:00</span>
                                    </label>
                                </div>

                                <div class="shift-radio-item">
                                    <input type="radio" id="shift_siang" name="shift" value="Siang" <?= $form_data['shift'] === 'Siang' ? 'checked' : '' ?>>
                                    <label for="shift_siang" class="shift-radio-label">
                                        <i class="fa-solid fa-cloud-sun text-primary"></i>
                                        <span class="shift-name">Shift Siang</span>
                                        <span class="shift-time">10:00 - 15:00</span>
                                    </label>
                                </div>

                                <div class="shift-radio-item">
                                    <input type="radio" id="shift_malam" name="shift" value="Malam" <?= $form_data['shift'] === 'Malam' ? 'checked' : '' ?>>
                                    <label for="shift_malam" class="shift-radio-label">
                                        <i class="fa-solid fa-moon text-info"></i>
                                        <span class="shift-name">Shift Malam</span>
                                        <span class="shift-time">16:00 - 21:30</span>
                                    </label>
                                </div>

                                <div class="shift-radio-item">
                                    <input type="radio" id="shift_full_day" name="shift" value="1 Hari" <?= in_array($form_data['shift'], ['1 Hari', 'Full Day']) ? 'checked' : '' ?>>
                                    <label for="shift_full_day" class="shift-radio-label">
                                        <i class="fa-solid fa-calendar-day text-success"></i>
                                        <span class="shift-name">Shift 1 Hari</span>
                                        <span class="shift-time">07:00 - 21:30 (Full Day)</span>
                                    </label>
                                </div>

                            </div>
                        </div>

                        <!-- Tanggal & Petugas -->
                        <div class="col-12 col-md-6">
                            <label for="tanggal" class="form-label small fw-semibold text-muted">Tanggal Pelaporan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-calendar-days"></i></span>
                                <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= e($form_data['tanggal']) ?>" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="petugas_nama" class="form-label small fw-semibold text-muted">
                                Petugas Aslab Jaga (Terkunci Sesuai Akun Login)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary"><i class="fa-solid fa-lock text-success"></i></span>
                                <input type="text" class="form-control bg-body-tertiary fw-bold text-main" id="petugas_nama" name="petugas_nama" value="<?= e($user ? $user['nama_lengkap'] : 'Asisten Lab') ?>" readonly>
                                <span class="input-group-text bg-success-subtle text-success small fw-semibold">
                                    <i class="fa-solid fa-circle-check me-1"></i> Terverifikasi
                                </span>
                            </div>
                            <div class="form-text small text-muted">
                                <i class="fa-solid fa-shield-halved me-1 text-success"></i> Nama otomatis terkunci sesuai akun <strong>@<?= e($user ? $user['username'] : '') ?></strong> untuk mencegah manipulasi data.
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
                            <?= (int)$form_data['kabel_roll_tersedia'] + (int)$form_data['kabel_roll_rusak'] ?> Unit
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
                                    <input type="number" id="kabel_roll_tersedia" name="kabel_roll_tersedia" min="0" value="<?= (int)$form_data['kabel_roll_tersedia'] ?>">
                                    <button type="button" class="stepper-btn btn-plus" title="Tambah"><i class="fa-solid fa-plus"></i></button>
                                </div>
                                <div class="small text-muted mt-2">Kabel roll kondisi normal yang bisa dipinjamkan.</div>
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
                                    <input type="number" id="kabel_roll_rusak" name="kabel_roll_rusak" min="0" value="<?= (int)$form_data['kabel_roll_rusak'] ?>">
                                    <button type="button" class="stepper-btn btn-plus" title="Tambah"><i class="fa-solid fa-plus"></i></button>
                                </div>
                                <div class="small text-muted mt-2">Unit stop kontak terbakar, kabel putus, atau dalam perbaikan.</div>
                            </div>
                        </div>

                        <!-- Catatan Kabel -->
                        <div class="col-12">
                            <label for="kabel_roll_catatan" class="form-label small fw-semibold text-muted">
                                Keterangan Kerusakan Kabel Roll (Opsional)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-circle-info"></i></span>
                                <input type="text" class="form-control" id="kabel_roll_catatan" name="kabel_roll_catatan" placeholder="Contoh: 1 Gabisa digunakan kata pak Agus Wiranto" value="<?= e($form_data['kabel_roll_catatan']) ?>">
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
                            <?= (int)$form_data['proyektor_tersedia'] + (int)$form_data['proyektor_rusak'] ?> Unit
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
                                    <input type="number" id="proyektor_tersedia" name="proyektor_tersedia" min="0" value="<?= (int)$form_data['proyektor_tersedia'] ?>">
                                    <button type="button" class="stepper-btn btn-plus" title="Tambah"><i class="fa-solid fa-plus"></i></button>
                                </div>
                                <div class="small text-muted mt-2">Unit proyektor berfungsi baik, lengkap dengan kabel power & HDMI.</div>
                            </div>
                        </div>
                                <div class="small text-muted mt-2">Unit proyektor berfungsi baik, lengkap dengan kabel power & HDMI.</div>
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
                                    <input type="number" id="proyektor_rusak" name="proyektor_rusak" min="0" value="<?= (int)$form_data['proyektor_rusak'] ?>">
                                    <button type="button" class="stepper-btn btn-plus" title="Tambah"><i class="fa-solid fa-plus"></i></button>
                                </div>
                                <div class="small text-muted mt-2">Lampu mati, overheat, port VGA/HDMI rusak, atau kabel hilang.</div>
                            </div>
                        </div>

                        <!-- Catatan Proyektor -->
                        <div class="col-12">
                            <label for="proyektor_catatan" class="form-label small fw-semibold text-muted">
                                Keterangan Kerusakan / Detail Proyektor (Opsional)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-screwdriver-wrench"></i></span>
                                <input type="text" class="form-control" id="proyektor_catatan" name="proyektor_catatan" placeholder="Contoh: 5 Unit rusak / kurang kabel power di rak Lab 4" value="<?= e($form_data['proyektor_catatan']) ?>">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- KARTU 4: Memo Operasional & Upload Foto -->
            <div class="card mb-4 card-hover-lift">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon-box stat-secondary" style="width: 38px; height: 38px; font-size: 1.1rem;">
                            <i class="fa-solid fa-bullhorn text-warning"></i>
                        </div>
                        <span class="fw-bold">4. Memo Operasional & Foto Dokumentasi</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        
                        <!-- Catatan / Memo Umum -->
                        <div class="col-12">
                            <label for="catatan_umum" class="form-label small fw-semibold text-muted">
                                Catatan Khusus / Pesan untuk Shift Berikutnya
                            </label>
                            <textarea class="form-control" id="catatan_umum" name="catatan_umum" rows="3" placeholder="Contoh: Semua kabel baru di kardus PRODI TI S1 ini bisa dipake mulai hari ini."><?= e($form_data['catatan_umum']) ?></textarea>
                            <div class="form-text small">Informasi ini akan otomatis masuk ke format pesan WhatsApp dan pengumuman dashboard.</div>
                        </div>

                        <!-- Drag and Drop Upload Foto -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">
                                Upload Foto Dokumentasi (Kardus Kabel / Bukti Fisik Rusak)
                            </label>
                            
                            <input type="file" class="d-none" id="foto_dokumentasi" name="foto_dokumentasi" accept="image/jpeg,image/png,image/webp">

                            <div class="upload-dropzone" id="upload_dropzone">
                                <div class="dropzone-icon">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                </div>
                                <h6 class="fw-bold mb-1">Klik atau Geser Foto ke Sini</h6>
                                <p class="text-muted small mb-0">Format didukung: <strong>JPG, PNG, WEBP</strong> (Maksimal 5 MB)</p>
                            </div>

                            <!-- Live Image Preview Container -->
                            <div id="image_preview_box" class="mt-3 p-3 bg-body-tertiary rounded-3 border d-none position-relative">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-bold text-success"><i class="fa-solid fa-circle-check me-1"></i> Foto Siap Diupload:</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" id="btn_remove_preview">
                                        <i class="fa-solid fa-trash me-1"></i> Hapus Foto
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
                            <span class="fw-bold" id="live_summary_kabel">10 Unit Siap</span>
                        </div>
                        <span class="text-muted opacity-50">&bull;</span>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-video text-primary"></i>
                            <span class="small text-muted">Proyektor:</span>
                            <span class="fw-bold" id="live_summary_proyektor">38 Unit Siap</span>
                        </div>
                        <span class="text-muted opacity-50">&bull;</span>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-business-time text-secondary"></i>
                            <span class="badge bg-primary-subtle text-primary border" id="live_summary_shift">Shift Pagi</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="index.php" class="btn btn-outline-secondary px-3">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2 shadow">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan & Buat Format WA</span>
                        </button>
                    </div>
                </div>
            </div>

        </form>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
