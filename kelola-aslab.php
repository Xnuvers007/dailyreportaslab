<?php
$page_title = "Kelola Akun Aslab";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

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
        if (strlen($password) < 6) {
            $errors[] = "Password minimal 6 karakter.";
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

        if ($target_id <= 0 || strlen($new_pass) < 6) {
            set_flash('danger', "Password baru minimal 6 karakter.");
        } else {
            try {
                $hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE `users` SET `password_hash` = :pass WHERE `id` = :id");
                $stmt->execute([':pass' => $hash, ':id' => $target_id]);
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

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="h4 mb-1 fw-bold">
            <i class="fa-solid fa-users-gear text-primary me-2"></i>Kelola Akun Asisten Lab
        </h3>
        <p class="text-muted small mb-0">Hanya Administrator yang dapat mendaftarkan akun aslab baru atau mereset password.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#tambahModal">
        <i class="fa-solid fa-user-plus"></i>
        <span>Tambah Aslab Baru</span>
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm">
        <div class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i> Perhatian:</div>
        <ul class="mb-0 ps-3 small">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold">
            <i class="fa-solid fa-id-card-clip text-primary me-2"></i>Daftar Petugas Terdaftar (<?= count($users) ?> Akun)
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3" style="width: 50px;">#</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Hak Akses (Role)</th>
                    <th>Tanggal Terdaftar</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $idx => $u): ?>
                    <tr>
                        <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= e($u['nama_lengkap']) ?></div>
                            <?php if ($u['id'] == $currentUser['id']): ?>
                                <span class="badge bg-secondary-subtle text-secondary small">(Akun Anda)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code><?= e($u['username']) ?></code>
                        </td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="fa-solid fa-crown me-1 text-warning"></i> Administrator
                                </span>
                            <?php else: ?>
                                <span class="badge bg-body-tertiary text-body border">
                                    <i class="fa-solid fa-user me-1"></i> Asisten Lab
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?= date('d/m/Y H:i', strtotime($u['created_at'])) ?>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" title="Reset Password" data-bs-toggle="modal" data-bs-target="#resetModal<?= (int)$u['id'] ?>">
                                    <i class="fa-solid fa-key"></i>
                                </button>
                                <?php if ($u['id'] != $currentUser['id']): ?>
                                    <button type="button" class="btn btn-outline-danger" title="Hapus Akun" data-bs-toggle="modal" data-bs-target="#hapusModal<?= (int)$u['id'] ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Reset Password -->
                    <div class="modal fade" id="resetModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="kelola-aslab.php" method="POST">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fs-6 fw-bold">
                                            <i class="fa-solid fa-key text-warning me-2"></i>Reset Password: <?= e($u['nama_lengkap']) ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Password Baru</label>
                                            <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary btn-sm">Simpan Password Baru</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Hapus User -->
                    <?php if ($u['id'] != $currentUser['id']): ?>
                        <div class="modal fade" id="hapusModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="kelola-aslab.php" method="POST">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="hapus_aslab">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fs-6 fw-bold text-danger">
                                                <i class="fa-solid fa-triangle-exclamation me-2"></i>Hapus Akun Aslab
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-0">Apakah Anda yakin ingin menghapus akun <strong><?= e($u['nama_lengkap']) ?> (<?= e($u['username']) ?>)</strong>?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-danger btn-sm">Ya, Hapus Akun</button>
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

<!-- Modal Tambah Aslab Baru -->
<div class="modal fade" id="tambahModal" tabindex="-1" aria-labelledby="tambahModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="kelola-aslab.php" method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="tambah_aslab">
                <div class="modal-header">
                    <h5 class="modal-title fs-6 fw-bold" id="tambahModalLabel">
                        <i class="fa-solid fa-user-plus text-primary me-2"></i>Pendaftaran Akun Aslab Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label small fw-semibold">Nama Lengkap Aslab</label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Contoh: Muhammad Lucky" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold">Username Login</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Contoh: lucky" required pattern="[a-zA-Z0-9_]{3,30}">
                        <div class="form-text small">Hanya huruf, angka, atau garis bawah (_).</div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">Password Awal</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label small fw-semibold">Hak Akses (Role)</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="aslab">Asisten Lab (Bisa input & edit laporan)</option>
                            <option value="admin">Administrator (Bisa input, edit, & kelola akun aslab)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                        <i class="fa-solid fa-check"></i>
                        <span>Daftarkan Akun</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

