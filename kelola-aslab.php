<?php
$page_title = "Kelola Akun Aslab";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/logger.php';

// Hanya Admin yang berhak mengelola akun aslab
require_admin();

$currentUser = current_user();
$errors = [];

// 1. Handle Tambah Aslab Baru
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_aslab') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = "Sesi form telah kedaluwarsa. Silakan coba kembali.";
    } else {
        $username     = strtolower(trim($_POST['username'] ?? ''));
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $password     = (string)($_POST['password'] ?? '');
        $role         = trim($_POST['role'] ?? 'aslab');

        // Validasi
        if (empty($username) || !preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
            $errors[] = "Username hanya boleh huruf kecil, angka, atau garis bawah (_) dengan panjang 3-30 karakter.";
        }
        if (empty($nama_lengkap) || strlen($nama_lengkap) < 3) {
            $errors[] = "Nama lengkap minimal 3 karakter.";
        }
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password minimal 8 karakter dan wajib memuat kombinasi huruf dan angka.";
        }
        if (!in_array($role, ['aslab', 'admin'])) {
            $role = 'aslab';
        }

        // Cek apakah username sudah ada
        if (empty($errors)) {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = :username");
            $stmt_check->execute([':username' => $username]);
            if ($stmt_check->fetchColumn() > 0) {
                $errors[] = "Username '{$username}' sudah digunakan oleh pengguna lain.";
            } else {
                try {
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt_insert = $pdo->prepare("INSERT INTO `users` (`username`, `password_hash`, `nama_lengkap`, `role`) VALUES (:username, :password_hash, :nama_lengkap, :role)");
                    $stmt_insert->execute([
                        ':username'      => $username,
                        ':password_hash' => $password_hash,
                        ':nama_lengkap'  => $nama_lengkap,
                        ':role'          => $role
                    ]);

                    record_log('CREATE_USER', "Menambahkan akun pengguna baru: {$username} ({$nama_lengkap}) role {$role}", 'SUCCESS');

                    set_flash('success', "Akun aslab baru ({$username} - {$nama_lengkap}) berhasil didaftarkan!");
                    header("Location: kelola-aslab.php");
                    exit;
                } catch (PDOException $e) {
                    error_log("Tambah Aslab Error: " . $e->getMessage());
                    $errors[] = "Gagal menyimpan akun ke database.";
                }
            }
        }
    }
}

// 2. Handle Reset Password Aslab
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        set_flash('danger', "Token CSRF tidak valid.");
    } else {
        $target_id = (int)($_POST['user_id'] ?? 0);
        $new_pass  = (string)($_POST['new_password'] ?? '');

        if ($target_id <= 0 || strlen($new_pass) < 8 || !preg_match('/[A-Za-z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass)) {
            set_flash('danger', "Password baru minimal 8 karakter dan wajib memuat kombinasi huruf dan angka.");
        } else {
            try {
                $hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE `users` SET `password_hash` = :pass WHERE `id` = :id");
                $stmt->execute([':pass' => $hash, ':id' => $target_id]);
                record_log('RESET_PASSWORD', "Mereset password akun pengguna ID #{$target_id}", 'WARNING');
                set_flash('success', "Password aslab berhasil direset.");
            } catch (PDOException $e) {
                set_flash('danger', "Gagal mereset password.");
            }
        }
    }
    header("Location: kelola-aslab.php");
    exit;
}

// 3. Handle Hapus Aslab
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_aslab') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        set_flash('danger', "Token CSRF tidak valid.");
    } else {
        $target_id = (int)($_POST['user_id'] ?? 0);
        
        // Mencegah admin menghapus dirinya sendiri
        if ($target_id === (int)$currentUser['id']) {
            set_flash('danger', "Anda tidak dapat menghapus akun Anda sendiri.");
        } else {
            try {
                $stmt_del = $pdo->prepare("DELETE FROM `users` WHERE `id` = :id");
                $stmt_del->execute([':id' => $target_id]);
                record_log('DELETE_USER', "Menghapus akun pengguna ID #{$target_id}", 'DANGER');
                set_flash('success', "Akun aslab berhasil dihapus.");
            } catch (PDOException $e) {
                set_flash('danger', "Gagal menghapus akun aslab.");
            }
        }
    }
    header("Location: kelola-aslab.php");
    exit;
}

// Ambil semua daftar akun aslab
$stmt_users = $pdo->query("SELECT `id`, `username`, `nama_lengkap`, `role`, `created_at` FROM `users` ORDER BY `id` ASC");
$users = $stmt_users->fetchAll();

$count_admin = 0;
$count_aslab = 0;
foreach ($users as $u) {
    if ($u['role'] === 'admin') $count_admin++;
    else $count_aslab++;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="animate-fade-up">

    <!-- Header Page Title & Action Button -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="hologram-tag mb-2 d-inline-flex align-items-center gap-2">
                <img src="assets/img/logo-unpam.png" alt="UNPAM" style="height: 18px; width: auto; object-fit: contain;">
                <span>Administrator Console &bull; Universitas Pamulang</span>
            </div>
            <h2 class="h3 fw-bold mb-1">
                Kelola Akun Asisten Laboratorium
            </h2>
            <p class="text-muted small mb-0">
                Pusat manajemen hak akses, pendaftaran anggota aslab baru, dan keamanan autentikasi sistem.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 shadow" data-bs-toggle="modal" data-bs-target="#tambahModal">
                <i class="fa-solid fa-user-plus"></i>
                <span>Tambah Aslab Baru</span>
            </button>
        </div>
    </div>

    <!-- 4 KPI Metrics Admin Cards -->
    <div class="row g-3 mb-4">
        
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-blue">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Pengguna</span>
                    <div class="stat-icon-box stat-primary" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1 text-primary">
                    <span class="counter-value" data-target="<?= count($users) ?>"><?= count($users) ?></span>
                    <span class="fs-6 fw-normal text-muted">Akun</span>
                </div>
                <div class="small text-muted d-flex align-items-center gap-1">
                    <i class="fa-solid fa-user-check text-success me-1"></i> Terdaftar di database
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-orange">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Administrator</span>
                    <div class="stat-icon-box stat-orange" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-crown text-warning"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1" style="color: var(--unpam-orange);">
                    <span class="counter-value" data-target="<?= $count_admin ?>"><?= $count_admin ?></span>
                    <span class="fs-6 fw-normal text-muted">Admin</span>
                </div>
                <div class="small text-muted">Akses penuh sistem & akun</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-green">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Asisten Lab Aktif</span>
                    <div class="stat-icon-box stat-success" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                </div>
                <div class="h3 fw-bold mb-1 text-success">
                    <span class="counter-value" data-target="<?= $count_aslab ?>"><?= $count_aslab ?></span>
                    <span class="fs-6 fw-normal text-muted">Petugas</span>
                </div>
                <div class="small text-muted">Hak input & laporan harian</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card kpi-purple">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Status Sistem</span>
                    <div class="stat-icon-box stat-secondary" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fa-solid fa-lock text-success"></i>
                    </div>
                </div>
                <div class="h5 fw-bold mb-1 text-success">
                    Privat & Tertutup
                </div>
                <div class="small text-muted">Pendaftaran hanya via Admin</div>
            </div>
        </div>

    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm border-0 border-start border-danger border-4 mb-4">
            <div class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i> Mohon perbaiki kesalahan:</div>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Tabel Akun Aslab Modern -->
    <div class="card card-hover-lift">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-address-book text-primary fs-5"></i>
                <span class="fw-bold">Daftar Akun Terdaftar (<?= count($users) ?> Anggota)</span>
            </div>
            <span class="badge bg-body-tertiary text-body border small">
                Login Sebagai: <strong><?= e($currentUser['nama_lengkap']) ?></strong>
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width: 60px;">#</th>
                        <th>Petugas Aslab</th>
                        <th>Username</th>
                        <th>Hak Akses (Role)</th>
                        <th>Tanggal Terdaftar</th>
                        <th class="text-end pe-3" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $idx => $u): ?>
                        <tr>
                            <td class="ps-3 text-muted fw-bold"><?= $idx + 1 ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar-circle <?= $u['role'] === 'admin' ? 'avatar-admin' : 'avatar-aslab' ?>">
                                        <?= strtoupper(substr($u['nama_lengkap'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-main"><?= e($u['nama_lengkap']) ?></div>
                                        <?php if ($u['id'] == $currentUser['id']): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">
                                                <i class="fa-solid fa-check me-1"></i> Akun Anda (Sedang Aktif)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-body-tertiary text-body border font-monospace px-2 py-1">
                                    @<?= e($u['username']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge-unpam">
                                        <i class="fa-solid fa-crown me-1 text-warning"></i> Administrator
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill">
                                        <i class="fa-solid fa-laptop-code me-1"></i> Asisten Lab
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <i class="fa-regular fa-clock me-1 text-primary"></i>
                                <?= date('d M Y, H:i', strtotime($u['created_at'])) ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-warning" title="Reset Password" data-bs-toggle="modal" data-bs-target="#resetModal<?= (int)$u['id'] ?>">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                    <?php if ($u['id'] != $currentUser['id']): ?>
                                        <button type="button" class="btn btn-outline-danger" title="Hapus Akun" data-bs-toggle="modal" data-bs-target="#hapusModal<?= (int)$u['id'] ?>">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-secondary disabled" title="Tidak dapat menghapus akun sendiri">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Reset Password -->
                        <div class="modal fade" id="resetModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg);">
                                    <form action="kelola-aslab.php" method="POST">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        
                                        <div class="modal-header border-bottom py-3">
                                            <h5 class="modal-title fs-6 fw-bold">
                                                <i class="fa-solid fa-key text-warning me-2"></i>Reset Password: <?= e($u['nama_lengkap']) ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="p-3 bg-body-tertiary rounded-3 border mb-3">
                                                <div class="small text-muted">Petugas: <strong><?= e($u['nama_lengkap']) ?></strong> (@<?= e($u['username']) ?>)</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Password Baru</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                                    <input type="password" name="new_password" class="form-control" placeholder="Minimal 8 karakter (huruf & angka)" required minlength="8">
                                                </div>
                                                <div class="form-text small">Password baru akan langsung aktif dan dienkripsi bcrypt.</div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top py-3">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary shadow">Simpan Password Baru</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Hapus User -->
                        <?php if ($u['id'] != $currentUser['id']): ?>
                            <div class="modal fade" id="hapusModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg);">
                                        <form action="kelola-aslab.php" method="POST">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="hapus_aslab">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            
                                            <div class="modal-header border-bottom py-3 text-danger">
                                                <h5 class="modal-title fs-6 fw-bold">
                                                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Konfirmasi Hapus Akun
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4 text-center">
                                                <div class="stat-icon-box bg-danger-subtle text-danger mx-auto mb-3" style="width: 58px; height: 58px; font-size: 1.5rem;">
                                                    <i class="fa-solid fa-user-xmark"></i>
                                                </div>
                                                <h6 class="fw-bold mb-2">Hapus Akun Aslab Ini?</h6>
                                                <p class="text-muted small mb-0">
                                                    Akun <strong><?= e($u['nama_lengkap']) ?></strong> (@<?= e($u['username']) ?>) akan dihapus secara permanen dari basis data sistem.
                                                </p>
                                            </div>
                                            <div class="modal-footer border-top py-3">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-danger shadow">Ya, Hapus Akun</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah Aslab Baru -->
<div class="modal fade" id="tambahModal" tabindex="-1" aria-labelledby="tambahModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg);">
            <form action="kelola-aslab.php" method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="tambah_aslab">
                
                <div class="modal-header border-bottom py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon-box stat-primary" style="width: 40px; height: 40px; font-size: 1.15rem;">
                            <i class="fa-solid fa-user-plus"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="tambahModalLabel">Pendaftaran Akun Aslab Baru</h5>
                            <span class="small text-muted">Akun hanya dapat dibuat oleh Administrator Lab</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        
                        <div class="col-12 col-md-6">
                            <label for="nama_lengkap" class="form-label small fw-semibold">Nama Lengkap Petugas</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-id-card"></i></span>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Contoh: Muhammad Lucky" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="username" class="form-label small fw-semibold">Username Login</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-at"></i></span>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Contoh: lucky" required pattern="[a-zA-Z0-9_]{3,30}">
                            </div>
                            <div class="form-text small">Hanya huruf, angka, atau underscore (_) tanpa spasi.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="password" class="form-label small fw-semibold">Password Awal</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 8 karakter (huruf & angka)" required minlength="8">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="role" class="form-label small fw-semibold">Hak Akses (Role)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-user-shield"></i></span>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="aslab" selected>Asisten Lab (Input & Edit Laporan)</option>
                                    <option value="admin">Administrator (Akses Penuh & Kelola Akun)</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2 shadow">
                        <i class="fa-solid fa-check"></i>
                        <span>Daftarkan Akun Aslab</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
