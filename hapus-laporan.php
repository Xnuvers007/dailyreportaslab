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
    // Ambil data untuk mengecek file foto
    $stmt = $pdo->prepare("SELECT `foto_dokumentasi` FROM `laporan` WHERE `id` = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $laporan = $stmt->fetch();

    if ($laporan) {
        // Hapus file fisik foto secara aman jika ada
        if (!empty($laporan['foto_dokumentasi'])) {
            $safe_filename = basename($laporan['foto_dokumentasi']);
            $file_path = __DIR__ . '/uploads/' . $safe_filename;
            if (file_exists($file_path) && is_file($file_path)) {
                @unlink($file_path);
            }
        }

        // Hapus record di database dengan Prepared Statement
        $delete_stmt = $pdo->prepare("DELETE FROM `laporan` WHERE `id` = :id");
        $delete_stmt->execute([':id' => $id]);

        // Catat ke audit log
        record_log('DELETE_REPORT', "Menghapus laporan ID #{$id}", 'DANGER');

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

