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

$sql = "SELECT * FROM `laporan` WHERE 1=1";
$params = [];

if (!empty($q)) {
    $sql .= " AND (`petugas_nama` LIKE :q OR `catatan_umum` LIKE :q OR `kabel_roll_catatan` LIKE :q OR `proyektor_catatan` LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

if (!empty($shift) && in_array($shift, ['Pagi', 'Siang', 'Sore', 'Malam'])) {
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

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="h4 mb-1 fw-bold">
            <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Riwayat Arsip Laporan
        </h3>
        <p class="text-muted small mb-0">Daftar rekapan harian kondisi proyektor, kabel roll, dan catatan operasional aslab.</p>
    </div>
    <div>
        <a href="buat-laporan.php" class="btn btn-primary btn-sm d-flex align-items-center gap-2">
            <i class="fa-solid fa-plus-circle"></i>
            <span>Input Laporan Baru</span>
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="riwayat.php" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Kata Kunci / Petugas</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control" name="q" placeholder="Cari nama, memo, kendala..." value="<?= e($q) ?>">
                </div>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted">Filter Shift</label>
                <select class="form-select form-select-sm" name="shift">
                    <option value="">Semua Shift</option>
                    <option value="Pagi" <?= $shift === 'Pagi' ? 'selected' : '' ?>>Pagi</option>
                    <option value="Siang" <?= $shift === 'Siang' ? 'selected' : '' ?>>Siang</option>
                    <option value="Sore" <?= $shift === 'Sore' ? 'selected' : '' ?>>Sore</option>
                    <option value="Malam" <?= $shift === 'Malam' ? 'selected' : '' ?>>Malam</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                <input type="date" class="form-control form-control-sm" name="tanggal_mulai" value="<?= e($tanggal_mulai) ?>">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted">Sampai Tanggal</label>
                <input type="date" class="form-control form-control-sm" name="tanggal_akhir" value="<?= e($tanggal_akhir) ?>">
            </div>

            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center gap-1">
                    <i class="fa-solid fa-filter"></i>
                    <span>Terapkan</span>
                </button>
                <a href="riwayat.php" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Laporan -->
<div class="card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold">
            <i class="fa-solid fa-table-list text-primary me-2"></i>Daftar Laporan (<?= count($reports) ?> Data)
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Tanggal & Shift</th>
                    <th>Petugas</th>
                    <th>Kabel Roll</th>
                    <th>Proyektor</th>
                    <th>Catatan / Memo</th>
                    <th>Foto</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reports) > 0): ?>
                    <?php foreach ($reports as $row): ?>
                        <?php 
                            $tot_kabel = (int)$row['kabel_roll_tersedia'] + (int)$row['kabel_roll_rusak'];
                            $tot_proyektor = (int)$row['proyektor_tersedia'] + (int)$row['proyektor_rusak'];
                            $tgl_indo = date('d/m/Y', strtotime($row['tanggal']));

                            // Format WhatsApp untuk tombol salin per baris
                            $wa_row = "*LAPORAN HARIAN ASISTEN LAB*\n";
                            $wa_row .= "Tanggal : " . $tgl_indo . " (" . $row['shift'] . ")\n";
                            $wa_row .= "Petugas : " . $row['petugas_nama'] . "\n\n";
                            $wa_row .= "*KABEL ROLL*\n";
                            $cat_k = !empty($row['kabel_roll_catatan']) ? " (" . $row['kabel_roll_catatan'] . ")" : "";
                            $wa_row .= "• Tersedia: " . (int)$row['kabel_roll_tersedia'] . " unit" . $cat_k . "\n";
                            $wa_row .= "• Rusak: " . (int)$row['kabel_roll_rusak'] . " unit (Total: {$tot_kabel})\n\n";
                            $wa_row .= "*PROYEKTOR*\n";
                            $wa_row .= "• Tersedia: " . (int)$row['proyektor_tersedia'] . " unit\n";
                            $wa_row .= "• Rusak/Kurang: " . (int)$row['proyektor_rusak'] . " unit (Total: {$tot_proyektor})\n";
                            if (!empty($row['proyektor_catatan'])) {
                                $wa_row .= "• Ket: " . $row['proyektor_catatan'] . "\n";
                            }
                            if (!empty($row['catatan_umum'])) {
                                $wa_row .= "\n*CATATAN*: " . $row['catatan_umum'] . "\n";
                            }
                        ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold"><?= $tgl_indo ?></div>
                                <span class="badge bg-body-tertiary text-body border small">Shift <?= e($row['shift']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-regular fa-user text-primary"></i>
                                    <span class="fw-medium"><?= e($row['petugas_nama']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="text-success fw-bold"><?= (int)$row['kabel_roll_tersedia'] ?></span> Tersedia
                                    <?php if ((int)$row['kabel_roll_rusak'] > 0): ?>
                                        <span class="text-danger small">(-<?= (int)$row['kabel_roll_rusak'] ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">Total: <?= $tot_kabel ?> unit</div>
                                <?php if (!empty($row['kabel_roll_catatan'])): ?>
                                    <div class="text-danger text-truncate" style="max-width: 180px; font-size: 0.75rem;" title="<?= e($row['kabel_roll_catatan']) ?>">
                                        <?= e($row['kabel_roll_catatan']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <span class="text-primary fw-bold"><?= (int)$row['proyektor_tersedia'] ?></span> Tersedia
                                    <?php if ((int)$row['proyektor_rusak'] > 0): ?>
                                        <span class="text-danger small">(-<?= (int)$row['proyektor_rusak'] ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">Total: <?= $tot_proyektor ?> unit</div>
                                <?php if (!empty($row['proyektor_catatan'])): ?>
                                    <div class="text-danger text-truncate" style="max-width: 180px; font-size: 0.75rem;" title="<?= e($row['proyektor_catatan']) ?>">
                                        <?= e($row['proyektor_catatan']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['catatan_umum'])): ?>
                                    <div class="text-truncate small" style="max-width: 220px; color: var(--text-main);" title="<?= e($row['catatan_umum']) ?>">
                                        <i class="fa-regular fa-comment-dots me-1 text-warning"></i>
                                        <span><?= e($row['catatan_umum']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['foto_dokumentasi']) && file_exists(__DIR__ . '/uploads/' . $row['foto_dokumentasi'])): ?>
                                    <a href="uploads/<?= e($row['foto_dokumentasi']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2" title="Lihat Foto">
                                        <i class="fa-solid fa-image"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-success" title="Salin Format WhatsApp" onclick="copyToWhatsApp(<?= htmlspecialchars(json_encode($wa_row), ENT_QUOTES, 'UTF-8') ?>, this)">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </button>
                                    <a href="detail.php?id=<?= (int)$row['id'] ?>" class="btn btn-outline-primary" title="Detail">
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
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fa-2x mb-2 d-block text-secondary"></i>
                            Tidak ada laporan yang sesuai dengan kriteria filter.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

