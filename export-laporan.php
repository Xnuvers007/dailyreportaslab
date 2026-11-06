<?php
/**
 * ASLAB REPORT - EXPORT ENGINE (PDF & EXCEL)
 * Menghasilkan Dokumen Rekap Resmi (PDF Cetak A4 Landscape & Spreadsheet Excel XLS)
 * Mendukung filter Custom Range Tanggal, Jam, Shift, dan Petugas
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

// Sistem Privasi Wajib Login
require_login("Sistem bersifat tertutup. Silakan login terlebih dahulu untuk mengunduh laporan.");

$currentUser = current_user();

// Parameter Ekspor
$format        = strtolower(trim($_GET['format'] ?? 'pdf')); // 'pdf' atau 'excel'
$range_type    = trim($_GET['range_type'] ?? 'all');        // 'all', 'today', 'this_month', 'custom'
$tanggal_mulai = trim($_GET['tanggal_mulai'] ?? '');
$tanggal_akhir = trim($_GET['tanggal_akhir'] ?? '');
$jam_mulai     = trim($_GET['jam_mulai'] ?? '');
$jam_akhir     = trim($_GET['jam_akhir'] ?? '');
$shift         = trim($_GET['shift'] ?? '');
$petugas       = trim($_GET['petugas'] ?? '');

// Filter Builder
$sql = "SELECT * FROM `laporan` WHERE 1=1";
$params = [];
$filter_desc = [];

if ($range_type === 'today') {
    $today = date('Y-m-d');
    $sql .= " AND `tanggal` = :today";
    $params[':today'] = $today;
    $filter_desc[] = "Hari Ini (" . date('d/m/Y') . ")";
} elseif ($range_type === 'this_month') {
    $sql .= " AND YEAR(`tanggal`) = :cur_year AND MONTH(`tanggal`) = :cur_month";
    $params[':cur_year'] = date('Y');
    $params[':cur_month'] = date('m');
    $filter_desc[] = "Bulan Ini (" . date('F Y') . ")";
} elseif ($range_type === 'custom') {
    if (!empty($tanggal_mulai) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_mulai)) {
        $sql .= " AND `tanggal` >= :tgl_mulai";
        $params[':tgl_mulai'] = $tanggal_mulai;
    }
    if (!empty($tanggal_akhir) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_akhir)) {
        $sql .= " AND `tanggal` <= :tgl_akhir";
        $params[':tgl_akhir'] = $tanggal_akhir;
    }
    
    $tgl_text = (!empty($tanggal_mulai) ? date('d/m/Y', strtotime($tanggal_mulai)) : 'Awal') . 
                " s/d " . 
                (!empty($tanggal_akhir) ? date('d/m/Y', strtotime($tanggal_akhir)) : 'Sekarang');
    
    if (!empty($jam_mulai) || !empty($jam_akhir)) {
        $tgl_text .= " (Pukul " . ($jam_mulai ?: '00:00') . " - " . ($jam_akhir ?: '23:59') . ")";
        if (!empty($jam_mulai)) {
            $sql .= " AND (TIME(created_at) >= :jam_mulai OR created_at IS NULL)";
            $params[':jam_mulai'] = $jam_mulai . ':00';
        }
        if (!empty($jam_akhir)) {
            $sql .= " AND (TIME(created_at) <= :jam_akhir OR created_at IS NULL)";
            $params[':jam_akhir'] = $jam_akhir . ':59';
        }
    }
    $filter_desc[] = "Rentang: " . $tgl_text;
} else {
    $filter_desc[] = "Semua Arsip (Keseluruhan)";
}

if (!empty($shift) && in_array($shift, ['Pagi', 'Siang', 'Sore', 'Malam'])) {
    $sql .= " AND `shift` = :shift";
    $params[':shift'] = $shift;
    $filter_desc[] = "Shift: " . $shift;
}

if (!empty($petugas)) {
    $sql .= " AND `petugas_nama` LIKE :petugas";
    $params[':petugas'] = '%' . $petugas . '%';
    $filter_desc[] = "Petugas: " . $petugas;
}

$sql .= " ORDER BY `tanggal` ASC, `id` ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Agregasi Data
$total_data = count($rows);
$sum_kabel_siap = 0;
$sum_kabel_rusak = 0;
$sum_proyektor_siap = 0;
$sum_proyektor_rusak = 0;

foreach ($rows as $r) {
    $sum_kabel_siap += (int)$r['kabel_roll_tersedia'];
    $sum_kabel_rusak += (int)$r['kabel_roll_rusak'];
    $sum_proyektor_siap += (int)$r['proyektor_tersedia'];
    $sum_proyektor_rusak += (int)$r['proyektor_rusak'];
}

$periode_string = implode(' | ', $filter_desc);

// ========================================================
// 1. EXCEL SPREADSHEET EXPORT (.XLS)
// ========================================================
if ($format === 'excel') {
    $clean_filename = "Laporan_Aslab_UNPAM_" . date('Ymd_His') . ".xls";
    
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"{$clean_filename}\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #ea580c; color: #ffffff; font-weight: bold; border: 1px solid #c2410c; padding: 8px; text-align: center; }
            td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; }
            .header-title { font-size: 15pt; font-weight: bold; color: #1e3a8a; text-align: center; }
            .header-sub { font-size: 11pt; font-weight: bold; text-align: center; color: #475569; }
            .meta-table td { border: none; padding: 3px 5px; font-size: 10pt; }
            .num { text-align: right; }
            .center { text-align: center; }
            .total-row th { background-color: #f1f5f9; color: #0f172a; border: 1px solid #94a3b8; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="header-title">UNIVERSITAS PAMULANG &bull; FAKULTAS ILMU KOMPUTER</div>
        <div class="header-sub">REKAPITULASI LAPORAN HARIAN INVENTARIS ASISTEN LABORATORIUM</div>
        <br>
        <table class="meta-table">
            <tr>
                <td style="width: 140px;"><strong>Filter / Kriteria:</strong></td>
                <td><?= e($periode_string) ?></td>
            </tr>
            <tr>
                <td><strong>Waktu Unduh:</strong></td>
                <td><?= date('d/m/Y H:i:s') ?> WIB</td>
            </tr>
            <tr>
                <td><strong>Diunduh Oleh:</strong></td>
                <td><?= e($currentUser['nama_lengkap']) ?> (<?= e(ucfirst($currentUser['role'])) ?>)</td>
            </tr>
            <tr>
                <td><strong>Total Laporan:</strong></td>
                <td><strong><?= $total_data ?></strong> Catatan</td>
            </tr>
        </table>
        <br>
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th style="width: 90px;">Tanggal</th>
                    <th style="width: 70px;">Shift</th>
                    <th style="width: 130px;">Petugas Aslab</th>
                    <th style="width: 80px;">Kabel Siap</th>
                    <th style="width: 80px;">Kabel Rusak</th>
                    <th style="width: 80px;">Total Kabel</th>
                    <th style="width: 160px;">Catatan Kabel</th>
                    <th style="width: 90px;">Proyektor Siap</th>
                    <th style="width: 90px;">Proyektor Rusak</th>
                    <th style="width: 90px;">Total Proyektor</th>
                    <th style="width: 160px;">Catatan Proyektor</th>
                    <th style="width: 200px;">Memo / Catatan Operasional</th>
                    <th style="width: 120px;">Foto Bukti</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_data > 0): ?>
                    <?php $no = 1; foreach ($rows as $row): ?>
                        <?php 
                            $tot_kabel = (int)$row['kabel_roll_tersedia'] + (int)$row['kabel_roll_rusak'];
                            $tot_proyektor = (int)$row['proyektor_tersedia'] + (int)$row['proyektor_rusak'];
                        ?>
                        <tr>
                            <td class="center"><?= $no++ ?></td>
                            <td class="center"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td class="center"><?= e($row['shift']) ?></td>
                            <td><?= e($row['petugas_nama']) ?></td>
                            <td class="num"><?= (int)$row['kabel_roll_tersedia'] ?></td>
                            <td class="num"><?= (int)$row['kabel_roll_rusak'] ?></td>
                            <td class="num"><strong><?= $tot_kabel ?></strong></td>
                            <td><?= e($row['kabel_roll_catatan'] ?: '-') ?></td>
                            <td class="num"><?= (int)$row['proyektor_tersedia'] ?></td>
                            <td class="num"><?= (int)$row['proyektor_rusak'] ?></td>
                            <td class="num"><strong><?= $tot_proyektor ?></strong></td>
                            <td><?= e($row['proyektor_catatan'] ?: '-') ?></td>
                            <td><?= e($row['catatan_umum'] ?: '-') ?></td>
                            <td class="center"><?= !empty($row['foto_dokumentasi']) ? 'Ada Foto' : 'Tidak Ada' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <th colspan="4" class="center">TOTAL REKAPITULASI FISIK</th>
                        <th class="num"><?= $sum_kabel_siap ?></th>
                        <th class="num"><?= $sum_kabel_rusak ?></th>
                        <th class="num"><?= $sum_kabel_siap + $sum_kabel_rusak ?></th>
                        <th>-</th>
                        <th class="num"><?= $sum_proyektor_siap ?></th>
                        <th class="num"><?= $sum_proyektor_rusak ?></th>
                        <th class="num"><?= $sum_proyektor_siap + $sum_proyektor_rusak ?></th>
                        <th>-</th>
                        <th colspan="2">-</th>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="14" class="center" style="padding: 20px; color: #64748b;">
                            Tidak ada data laporan yang cocok dengan filter yang dipilih.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// ========================================================
// 2. PDF / PRINT-READY PREVIEW (A4 LANDSCAPE DENGAN KOP RESMI)
// ========================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Laporan Harian Aslab UNPAM (<?= date('d-m-Y') ?>)</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #1e40af;
            --orange: #ea580c;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 0.85rem;
            margin: 0;
            padding: 20px 0;
        }

        /* Printable Sheet Simulation */
        .sheet {
            background: #ffffff;
            width: 297mm;
            min-height: 210mm;
            margin: 0 auto;
            padding: 15mm 20mm;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            position: relative;
        }

        /* Kop Surat Resmi Universitas Pamulang */
        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px double #0f172a;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .kop-logo {
            width: 78px;
            height: 78px;
            object-fit: contain;
            background: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #ea580c;
        }

        .kop-text {
            flex-grow: 1;
            text-align: center;
            padding: 0 15px;
        }

        .kop-instansi {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #1e3a8a;
            margin: 0;
            text-transform: uppercase;
        }

        .kop-fakultas {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ea580c;
            margin: 2px 0;
            text-transform: uppercase;
        }

        .kop-lab {
            font-size: 0.88rem;
            font-weight: 600;
            color: #0f172a;
            margin: 2px 0;
        }

        .kop-alamat {
            font-size: 0.72rem;
            color: #64748b;
            margin: 0;
        }

        /* Document Title */
        .doc-title-box {
            text-align: center;
            margin-bottom: 16px;
        }

        .doc-title {
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 4px 0;
            color: #0f172a;
        }

        .doc-subtitle {
            font-size: 0.78rem;
            color: #475569;
            margin: 0;
        }

        /* Meta Filter Table */
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 0.78rem;
            margin-bottom: 14px;
        }

        /* Table Design */
        .table-export {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.76rem;
            margin-bottom: 18px;
        }

        .table-export th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.04em;
            border: 1px solid #1e3a8a;
            padding: 7px 6px;
            text-align: center;
        }

        .table-export td {
            border: 1px solid #cbd5e1;
            padding: 6px 7px;
            vertical-align: middle;
        }

        .table-export tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .table-export .total-row th {
            background-color: #e2e8f0;
            color: #0f172a;
            border-color: #94a3b8;
            font-weight: 700;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            page-break-inside: avoid;
        }

        .sig-box {
            text-align: center;
            width: 240px;
        }

        .sig-space {
            height: 60px;
        }

        .sig-name {
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        /* Floating Action Toolbar */
        .print-toolbar {
            position: fixed;
            top: 15px;
            right: 25px;
            z-index: 1000;
            background: rgba(15, 23, 42, 0.9);
            padding: 10px 16px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(8px);
        }

        /* Print Media Query */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .print-toolbar, .no-print {
                display: none !important;
            }
            .sheet {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: auto;
                padding: 10mm 15mm;
            }
            @page {
                size: A4 landscape;
                margin: 8mm 10mm;
            }
            .table-export th {
                background-color: #1e3a8a !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar (Non-printable) -->
    <div class="print-toolbar no-print">
        <span class="text-white small fw-bold me-2">
            <i class="fa-solid fa-file-pdf text-danger me-1"></i> Preview Rekap Cetak
        </span>
        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 d-flex align-items-center gap-1 shadow" onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            <span>Cetak / Simpan PDF</span>
        </button>
        <?php
            // Buat query string untuk tombol Excel dengan parameter filter yang persis sama
            $excel_params = $_GET;
            $excel_params['format'] = 'excel';
            $excel_url = 'export-laporan.php?' . http_build_query($excel_params);
        ?>
        <a href="<?= e($excel_url) ?>" class="btn btn-success btn-sm rounded-pill px-3 d-flex align-items-center gap-1 shadow">
            <i class="fa-solid fa-file-excel"></i>
            <span>Unduh Excel</span>
        </a>
        <a href="riwayat.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <!-- Printable Paper Sheet (A4 Landscape) -->
    <div class="sheet">
        
        <!-- Kop Surat Resmi UNPAM -->
        <div class="kop-surat">
            <div class="kop-logo">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div class="kop-text">
                <h1 class="kop-instansi">UNIVERSITAS PAMULANG</h1>
                <h2 class="kop-fakultas">FAKULTAS ILMU KOMPUTER &bull; PROGRAM STUDI TEKNIK INFORMATIKA</h2>
                <h3 class="kop-lab">LABORATORIUM KOMPUTER & MULTIMEDIA (ASLAB)</h3>
                <p class="kop-alamat">
                    Jl. Surya Kencana No. 1, Pamulang Barat, Kec. Pamulang, Kota Tangerang Selatan, Banten 15417 &bull; Telp: (021) 7412566
                </p>
            </div>
            <div class="kop-logo text-primary">
                <i class="fa-solid fa-layer-group"></i>
            </div>
        </div>

        <!-- Judul Laporan -->
        <div class="doc-title-box">
            <h3 class="doc-title">REKAPITULASI LAPORAN HARIAN INVENTARIS LAB</h3>
            <p class="doc-subtitle">Pemantauan Ketersediaan Proyektor, Kabel Roll, & Serah Terima Shift Asisten Laboratorium</p>
        </div>

        <!-- Filter / Metadata Ringkas -->
        <div class="meta-box d-flex justify-content-between align-items-center">
            <div>
                <strong>Kriteria Filter:</strong> <?= e($periode_string) ?>
            </div>
            <div>
                <strong>Waktu Cetak:</strong> <?= date('d/m/Y H:i') ?> WIB &bull; 
                <strong>Oleh:</strong> <?= e($currentUser['nama_lengkap']) ?>
            </div>
        </div>

        <!-- Tabel Rekap Data -->
        <table class="table-export">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 75px;">Tanggal</th>
                    <th style="width: 55px;">Shift</th>
                    <th style="width: 105px;">Petugas</th>
                    <th style="width: 60px;">Kabel Siap</th>
                    <th style="width: 60px;">Kabel Rusak</th>
                    <th style="width: 60px;">Tot Kabel</th>
                    <th>Catatan Kabel</th>
                    <th style="width: 65px;">Proyektor Siap</th>
                    <th style="width: 65px;">Proyektor Rusak</th>
                    <th style="width: 65px;">Tot Proyektor</th>
                    <th>Catatan Proyektor</th>
                    <th>Memo / Pengumuman</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_data > 0): ?>
                    <?php $no = 1; foreach ($rows as $row): ?>
                        <?php 
                            $tot_kabel = (int)$row['kabel_roll_tersedia'] + (int)$row['kabel_roll_rusak'];
                            $tot_proyektor = (int)$row['proyektor_tersedia'] + (int)$row['proyektor_rusak'];
                        ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $no++ ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><?= e($row['shift']) ?></span>
                            </td>
                            <td class="fw-semibold"><?= e($row['petugas_nama']) ?></td>
                            
                            <!-- Kabel -->
                            <td class="text-center text-success fw-bold"><?= (int)$row['kabel_roll_tersedia'] ?></td>
                            <td class="text-center <?= (int)$row['kabel_roll_rusak'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                <?= (int)$row['kabel_roll_rusak'] ?>
                            </td>
                            <td class="text-center fw-bold"><?= $tot_kabel ?></td>
                            <td class="small"><?= e($row['kabel_roll_catatan'] ?: '-') ?></td>

                            <!-- Proyektor -->
                            <td class="text-center text-primary fw-bold"><?= (int)$row['proyektor_tersedia'] ?></td>
                            <td class="text-center <?= (int)$row['proyektor_rusak'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                <?= (int)$row['proyektor_rusak'] ?>
                            </td>
                            <td class="text-center fw-bold"><?= $tot_proyektor ?></td>
                            <td class="small"><?= e($row['proyektor_catatan'] ?: '-') ?></td>

                            <!-- Memo -->
                            <td class="small"><?= e($row['catatan_umum'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <th colspan="4" class="text-center">TOTAL FISIK UNIT KESELURUHAN (<?= $total_data ?> Laporan)</th>
                        <th class="text-center"><?= $sum_kabel_siap ?></th>
                        <th class="text-center <?= $sum_kabel_rusak > 0 ? 'text-danger' : '' ?>"><?= $sum_kabel_rusak ?></th>
                        <th class="text-center"><?= $sum_kabel_siap + $sum_kabel_rusak ?></th>
                        <th>-</th>
                        <th class="text-center"><?= $sum_proyektor_siap ?></th>
                        <th class="text-center <?= $sum_proyektor_rusak > 0 ? 'text-danger' : '' ?>"><?= $sum_proyektor_rusak ?></th>
                        <th class="text-center"><?= $sum_proyektor_siap + $sum_proyektor_rusak ?></th>
                        <th>-</th>
                        <th>-</th>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="13" class="text-center py-4 text-muted">
                            Tidak ditemukan laporan pada periode atau filter yang dipilih.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Kolom Pengesahan Tanda Tangan -->
        <div class="signature-section">
            <div class="sig-box">
                <div>Mengetahui,</div>
                <div class="fw-semibold">Koordinator Laboratorium Komputer</div>
                <div class="sig-space"></div>
                <div class="sig-name">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
                <div class="small text-muted">NIDN. ........................................</div>
            </div>

            <div class="sig-box">
                <div>Tangerang Selatan, <?= date('d F Y') ?></div>
                <div class="fw-semibold">Petugas Asisten Laboratorium</div>
                <div class="sig-space"></div>
                <div class="sig-name">( <?= e($currentUser['nama_lengkap']) ?> )</div>
                <div class="small text-muted">Aslab TI S1 &bull; Universitas Pamulang</div>
            </div>
        </div>

    </div>

</body>
</html>
