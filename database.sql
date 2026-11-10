-- ========================================================
-- SISTEM LAPORAN HARIAN ASLAB (ASISTEN LABORATORIUM)
-- Database Schema for MySQL / MariaDB (phpMyAdmin / InfinityFree)
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+07:00";

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `role` ENUM('aslab', 'admin') NOT NULL DEFAULT 'aslab',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `laporan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `laporan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `tanggal` DATE NOT NULL,
  `shift` VARCHAR(50) NOT NULL DEFAULT 'Pagi',
  `petugas_nama` VARCHAR(100) NOT NULL,

-- Inventaris Kabel Roll
`kabel_roll_tersedia` INT NOT NULL DEFAULT 0,
`kabel_roll_rusak` INT NOT NULL DEFAULT 0,
`kabel_roll_catatan` VARCHAR(255) NULL,

-- Inventaris Proyektor
`proyektor_tersedia` INT NOT NULL DEFAULT 0,
`proyektor_rusak` INT NOT NULL DEFAULT 0,
`proyektor_catatan` VARCHAR(255) NULL,

-- Memo / Pengumuman & Bukti Foto
`catatan_umum` TEXT NULL,
  `foto_dokumentasi` VARCHAR(255) NULL,
  
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_tanggal` (`tanggal`),
  INDEX `idx_shift` (`shift`),
  CONSTRAINT `fk_laporan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `activity_logs` (Audit Trail)
-- Retensi otomatis 30 hari & mendukung export
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `username` VARCHAR(100) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `url_path` VARCHAR(255) NULL,
    `method` VARCHAR(10) NOT NULL DEFAULT 'GET',
    `status` VARCHAR(20) NOT NULL DEFAULT 'INFO',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Data awal untuk user default: admin / admin123
-- --------------------------------------------------------
INSERT INTO
    `users` (
        `id`,
        `username`,
        `password_hash`,
        `nama_lengkap`,
        `role`
    )
VALUES (
        1,
        'aslab',
        '$2y$10$437rOIKmjXP5fXAVYQ5KS.4NNPcQvh1ec0qylnSDpu3/2Jrc9jn02',
        'Asisten Laboratorium',
        'admin'
    )
ON DUPLICATE KEY UPDATE
    `username` = `username`;

-- --------------------------------------------------------
-- Data sample riwayat laporan Aslab UNPAM (Multi-Shift & Multi-Petugas)
-- --------------------------------------------------------
INSERT INTO `laporan` (
  `id`, `user_id`, `tanggal`, `shift`, `petugas_nama`, 
  `kabel_roll_tersedia`, `kabel_roll_rusak`, `kabel_roll_catatan`, 
  `proyektor_tersedia`, `proyektor_rusak`, `proyektor_catatan`, 
  `catatan_umum`, `foto_dokumentasi`
) VALUES
(
  1, 1, '2026-11-01', 'Pagi', 'Lucky',
  10, 1, '1 Gabisa digunakan kata pak Agus Wiranto',
  38, 5, '5 Unit rusak / kurang kabel power',
  'Semua kabel baru di kardus PRODI TI S1 ini bisa dipake mulai hari ini.',
  'sample_kardus_kabel.jpg'
),
(
  2, 1, '2026-11-01', 'Siang', 'Rizky Pratama',
  11, 0, 'Semua kabel roll berfungsi normal',
  39, 4, 'Proyektor Lab 2 flickering sesekali',
  'Praktikum Basis Data shift siang selesai lancar. Remote AC Lab 3 baterai habis.',
  NULL
),
(
  3, 1, '2026-11-02', 'Pagi', 'Sarah Amalia',
  9, 2, '1 Steker longgar di Lab 1 meja 12',
  40, 3, 'Kabel VGA proyektor Lab 1 diganti HDMI',
  'Kabel roll yang kemarin dipinjam Pak Budi di R.410 sudah dikembalikan ke Lab.',
  NULL
),
(
  4, 1, '2026-11-02', 'Malam', 'Dimas Arya',
  11, 0, NULL,
  41, 2, '2 Unit di lemari servis sarpras',
  'Praktikum Pemrograman Web malam selesai pukul 21:00. Semua AC dan saklar lampu lab sudah dimatikan.',
  NULL
),
(
  5, 1, '2026-11-03', 'Pagi', 'Putri Wulandari',
  10, 1, '1 Unit kabel roll terbakar steker kecil',
  37, 6, 'Proyektor Lab 4 mati total tidak ada indikator power',
  'Sudah dibuatkan tiket laporan perbaikan ke teknisi sarpras untuk proyektor Lab 4.',
  NULL
),
(
  6, 1, '2026-11-04', 'Siang', 'Fajar Hidayat',
  11, 0, 'Lengkap 11 unit di rak inventaris',
  39, 4, NULL,
  'Lab Komputer 2 digunakan untuk simulasi sertifikasi BNSP. Jaringan LAN stabil.',
  NULL
),
(
  7, 1, '2026-11-05', 'Pagi', 'Lucky',
  11, 0, NULL,
  40, 3, '3 Unit sedang dalam perbaikan',
  'Pengecekan kabel LAN dan colokan PC di Lab 1, 2, dan 3 telah selesai.',
  NULL
),
(
  8, 1, '2026-11-06', 'Siang', 'Aditia Nugraha',
  10, 1, '1 Kabel roll tombol switch mati',
  41, 2, NULL,
  'Serah terima shift siang ke shift malam. Kunci lab ada di meja piket.',
  NULL
),
(
  9, 1, '2026-11-06', 'Malam', 'Dimas Arya',
  11, 0, NULL,
  42, 1, '1 Proyektor Lab 3 lampu redup',
  'Kelas malam karyawan berakhir aman. Seluruh jendela dan pintu sudah terkunci ganda.',
  NULL
),
(
  10, 1, '2026-11-07', 'Pagi', 'Asisten Laboratorium',
  31, 7, '7 Unit steker rusak butuh diganti',
  37, 6, '6 Unit dalam kendala kabel dan adaptor',
  'Ada 2 kabel hdmi di dus kabel roll. Lab 1 siap digunakan untuk praktikum jam ke-1.',
  NULL
)
ON DUPLICATE KEY UPDATE `id` = `id`;

COMMIT;

SET FOREIGN_KEY_CHECKS = 1;