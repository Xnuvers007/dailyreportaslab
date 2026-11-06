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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `laporan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `laporan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `tanggal` DATE NOT NULL,
  `shift` ENUM('Pagi', 'Siang', 'Sore', 'Malam') NOT NULL DEFAULT 'Pagi',
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
-- Data awal untuk user default: admin / admin123
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `password_hash`, `nama_lengkap`, `role`) VALUES
(1, 'aslab', '$2y$10$437rOIKmjXP5fXAVYQ5KS.4NNPcQvh1ec0qylnSDpu3/2Jrc9jn02', 'Asisten Laboratorium', 'admin')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- --------------------------------------------------------
-- Data sample laporan sesuai studi kasus
-- --------------------------------------------------------
INSERT INTO `laporan` (
  `id`, `user_id`, `tanggal`, `shift`, `petugas_nama`, 
  `kabel_roll_tersedia`, `kabel_roll_rusak`, `kabel_roll_catatan`, 
  `proyektor_tersedia`, `proyektor_rusak`, `proyektor_catatan`, 
  `catatan_umum`, `foto_dokumentasi`
) VALUES
(
  1, 1, CURDATE(), 'Pagi', 'Lucky',
  10, 1, '1 Gabisa digunakan kata pak Agus Wiranto',
  38, 5, '5 Unit rusak / kurang kabel power',
  'Semua kabel baru di kardus PRODI TI S1 ini bisa dipake mulai hari ini.',
  'sample_kardus_kabel.jpg'
);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
