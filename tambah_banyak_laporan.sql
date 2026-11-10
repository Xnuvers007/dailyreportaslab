-- ========================================================
-- QUERY TAMBAH DATA BANYAK LAPORAN HARIAN ASLAB UNPAM
-- Copy dan Paste seluruh kode ini ke tab "SQL" di phpMyAdmin InfinityFree
-- ========================================================

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
ON DUPLICATE KEY UPDATE 
  `tanggal` = VALUES(`tanggal`),
  `shift` = VALUES(`shift`),
  `petugas_nama` = VALUES(`petugas_nama`),
  `kabel_roll_tersedia` = VALUES(`kabel_roll_tersedia`),
  `kabel_roll_rusak` = VALUES(`kabel_roll_rusak`),
  `kabel_roll_catatan` = VALUES(`kabel_roll_catatan`),
  `proyektor_tersedia` = VALUES(`proyektor_tersedia`),
  `proyektor_rusak` = VALUES(`proyektor_rusak`),
  `proyektor_catatan` = VALUES(`proyektor_catatan`),
  `catatan_umum` = VALUES(`catatan_umum`);
