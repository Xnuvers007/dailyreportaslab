<?php
/**
 * Konfigurasi Database PDO (MySQL / MariaDB)
 * Kompatibel dengan Hosting InfinityFree & Localhost (XAMPP / Standalone PHP)
 */

// Kredensial MySQL InfinityFree / Localhost
$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'laporan_aslab';
$db_user = 'root';
$db_pass = '';

/*
 * PANDUAN DEPLOY KE INFINITYFREE:
 * Ganti data di atas dengan kredensial MySQL dari Control Panel InfinityFree:
 * $db_host = 'sqlXXX.infinityfree.com';
 * $db_name = 'if0_XXXXXXXX_laporanaslab';
 * $db_user = 'if0_XXXXXXXX';
 * $db_pass = 'Password_vPanel_Anda';
 */

if (getenv('DB_HOST')) $db_host = getenv('DB_HOST');
if (getenv('DB_PORT')) $db_port = getenv('DB_PORT');
if (getenv('DB_NAME')) $db_name = getenv('DB_NAME');
if (getenv('DB_USER')) $db_user = getenv('DB_USER');
if (getenv('DB_PASS') !== false) $db_pass = getenv('DB_PASS');

$is_remote_or_docker = ($db_host !== '127.0.0.1' && $db_host !== 'localhost');
$pdo = null;

// Koneksi MySQL dengan mekanisme retry otomatis (Sangat berguna saat container database Docker baru booting)
$max_retries = $is_remote_or_docker ? 5 : 1;
for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
    try {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Mencegah SQL Injection di level driver
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        break; // Berhasil terhubung
    } catch (PDOException $e) {
        if ($attempt < $max_retries) {
            sleep(1);
            continue;
        }
        // Jika koneksi remote/docker tetap gagal setelah batas percobaan
        if ($is_remote_or_docker) {
            error_log("Database connection error: " . $e->getMessage());
            die("Koneksi database MySQL gagal. Pastikan database telah berjalan dan kredensial di config/database.php atau environment Docker sudah sesuai.");
        }
    }
}

if (!$pdo) {
    // Jika di Localhost dan MySQL belum dinyalakan, fallback otomatis ke SQLite lokal
    try {
        $sqlite_file = __DIR__ . '/database.sqlite';
        $init_needed = !file_exists($sqlite_file);
        
        $pdo = new PDO("sqlite:" . $sqlite_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        if ($init_needed) {
            // Auto-create schema untuk pengujian offline lokal
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                nama_lengkap TEXT NOT NULL,
                role TEXT DEFAULT 'aslab',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS laporan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                tanggal DATE NOT NULL,
                shift TEXT DEFAULT 'Pagi',
                petugas_nama TEXT NOT NULL,
                kabel_roll_tersedia INTEGER DEFAULT 0,
                kabel_roll_rusak INTEGER DEFAULT 0,
                kabel_roll_catatan TEXT NULL,
                proyektor_tersedia INTEGER DEFAULT 0,
                proyektor_rusak INTEGER DEFAULT 0,
                proyektor_catatan TEXT NULL,
                catatan_umum TEXT NULL,
                foto_dokumentasi TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                username TEXT NOT NULL,
                action TEXT NOT NULL,
                description TEXT NOT NULL,
                ip_address TEXT NOT NULL,
                user_agent TEXT NULL,
                url_path TEXT NULL,
                method TEXT DEFAULT 'GET',
                status TEXT DEFAULT 'INFO',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Default user: aslab / admin123
            $pass_hash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt_u = $pdo->prepare("INSERT OR IGNORE INTO users (id, username, password_hash, nama_lengkap, role) VALUES (1, 'aslab', :p, 'Asisten Laboratorium', 'admin')");
            $stmt_u->execute([':p' => $pass_hash]);

            // Sample laporan awal
            $stmt_l = $pdo->prepare("INSERT INTO laporan (
                id, user_id, tanggal, shift, petugas_nama,
                kabel_roll_tersedia, kabel_roll_rusak, kabel_roll_catatan,
                proyektor_tersedia, proyektor_rusak, proyektor_catatan,
                catatan_umum, foto_dokumentasi
            ) VALUES (
                1, 1, date('now'), 'Pagi', 'Lucky',
                10, 1, '1 Gabisa digunakan kata pak Agus Wiranto',
                38, 5, '5 Unit rusak / kurang kabel power',
                'Semua kabel baru di kardus PRODI TI S1 ini bisa dipake mulai hari ini.',
                'sample_kardus_kabel.jpg'
            )");
            $stmt_l->execute();
        }
    } catch (Exception $sqle) {
        error_log("SQLite Fallback Error: " . $sqle->getMessage());
        die("Koneksi database gagal. Mohon periksa config/database.php.");
    }
}
