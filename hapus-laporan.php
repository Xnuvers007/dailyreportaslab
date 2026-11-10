<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/logger.php';

// Wajib login untuk menghapus laporan
require_login("Silakan login sebagai aslab terlebih dahulu.");

// Hanya izinkan HTTP POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    die("Metode request tidak diizinkan.");
}

// Validasi CSRF Token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf_token)) {
    set_flash('danger', "Validasi keamanan gagal (Token CSRF kedaluwarsa). Penghapusan dibatalkan.");
    header("Location: riwayat.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', "ID laporan tidak valid.");
    header("Location: riwayat.php");
    exit;
}

try {
    // Ambil data untuk verifikasi kepemilikan dan file foto
    $stmt = $pdo->prepare("SELECT `id`, `user_id`, `petugas_nama`, `tanggal`, `shift`, `foto_dokumentasi` FROM `laporan` WHERE `id` = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $laporan = $stmt->fetch();

    if ($laporan) {
        $current_user = current_user();
        
        // HAK AKSES KHUSUS ADMINISTRATOR (INTEGRITAS ARSIP LABORATORIUM):
        // Aslab biasa TIDAK DIIZINKAN menghapus laporan harian demi mencegah manipulasi data inventaris.
        // Hanya role Administrator yang memiliki wewenang menghapus arsip resmi.
        if (!is_admin()) {
            record_log('UNAUTHORIZED_DELETE_ATTEMPT', "Percobaan hapus laporan ID #{$id} ({$laporan['petugas_nama']} - Shift {$laporan['shift']}) oleh non-admin ditolak", 'WARNING');
            set_flash('danger', "Akses ditolak! Laporan inventaris resmi hanya dapat dihapus oleh Administrator demi menjaga integritas data.");
            header("Location: detail.php?id=" . (int)$id);
            exit;
        }

        // Hapus file fisik foto secara aman jika ada (Anti-Path-Traversal & Server-Agnostic Storage)
        if (!empty($laporan['foto_dokumentasi'])) {
            $safe_filename = basename($laporan['foto_dokumentasi']);
            $uploads_dir   = get_uploads_directory();
            $file_path     = $uploads_dir . DIRECTORY_SEPARATOR . $safe_filename;
            $real_file     = realpath($file_path);
            if ($real_file && $uploads_dir && strpos($real_file, $uploads_dir) === 0 && is_file($real_file)) {
                @unlink($real_file);
            }
        }

        // Hapus record di database dengan Prepared Statement
        $delete_stmt = $pdo->prepare("DELETE FROM `laporan` WHERE `id` = :id");
        $delete_stmt->execute([':id' => $id]);

        // Catat ke audit log dengan detail laporan
        record_log('DELETE_REPORT', "Menghapus laporan ID #{$id} ({$laporan['petugas_nama']} - " . date('d/m/Y', strtotime($laporan['tanggal'])) . " Shift {$laporan['shift']})", 'DANGER');

        set_flash('success', "Laporan berhasil dihapus secara permanen.");
    } else {
        set_flash('warning', "Laporan yang ingin dihapus tidak ditemukan.");
    }
} catch (PDOException $e) {
    error_log("Delete Laporan Error: " . $e->getMessage());
    set_flash('danger', "Gagal menghapus laporan dari database.");
}

header("Location: riwayat.php");
exit;

