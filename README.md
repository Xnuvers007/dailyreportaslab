# 📋 Sistem Laporan Harian Asisten Laboratorium (Aslab)

Aplikasi web modern, berkecepatan tinggi, dan berkeamanan tinggi untuk mencatat inventaris harian (kabel roll, proyektor, peralatan lab), catatan operasional shift, dokumentasi foto terisolasi, dan fitur **Salin Format WhatsApp (1-Click)** siap kirim ke grup.

Mendukung deployment menggunakan **Docker & Docker Compose**, **Server VPS (Apache / Nginx)**, hosting **InfinityFree**, maupun server lokal kampus (**XAMPP**).

---

## ✨ Fitur Utama

1. **Sistem Tertutup & Privasi Terjaga (100% Private)**:
   - Pengunjung luar / publik **sama sekali tidak dapat melihat data stok, catatan, memo, maupun foto**.
   - Setiap akses publik otomatis dialihkan ke halaman Login.
2. **Penyimpanan Foto Terisolasi (Private Storage Gateway)**:
   - Seluruh foto dokumentasi tersimpan di direktori storage privat di luar jangkauan langsung web server.
   - Foto **hanya bisa dibuka melalui gateway terotentikasi (`lihat-foto.php`)** bagi pengguna yang memiliki hak akses dan sudah login.
3. **Pendaftaran Khusus Administrator**:
   - Pendaftaran akun aslab baru **hanya dapat dilakukan oleh Administrator** melalui menu konsol **Kelola Aslab**.
   - Admin dapat menambah aslab baru, mereset password, atau menghapus akun.
4. **Dashboard Ketersediaan Real-Time**:
   - Menampilkan total unit, stok siap pakai, dan barang yang rusak/trouble.
   - Ringkasan memo / pengumuman aslab terkini disertai dokumentasi foto.
5. **Kalkulasi Otomatis Stok**:
   - Menghitung secara otomatis `Total Unit = Tersedia + Rusak` di form input tanpa perlu hitung manual.
   - Kolom catatan spesifik per barang (contoh: *"1 Gabisa digunakan kata pak Agus Wiranto"*).
6. **Salin ke WhatsApp (1-Click Copy WA)**:
   - Sekali klik, format teks laporan harian langsung rapi tersalin ke clipboard dan siap di-paste ke grup WhatsApp Aslab/Dosen/Laboran.
7. **Pencarian, Filter, & Ekspor Resmi**:
   - Filter laporan berdasarkan tanggal, shift (Pagi/Siang/Sore/Malam), maupun pencarian kata kunci.
   - Ekspor rekapitulasi data ke format **PDF Cetak Resmi** dan **Spreadsheet Excel (.XLS)**.
8. **Audit Trail & Log Aktivitas (30-Day Auto-Retention)**:
   - Mencatat seluruh riwayat login, input laporan, perubahan data, dan ekspor dengan rotasi otomatis 30 hari.

---

## 🛡️ Keamanan Sistem (Security Hardening)

Aplikasi ini telah diaudit dan diperkuat dengan standar keamanan OWASP:

* **Private Storage Architecture (Anti-Unauthorized File Exposure)**:
  - File foto disimpan di direktori privat (`storage/uploads/` atau `/var/www/storage/uploads` di luar web root).
  - Akses langsung via HTTP diblokir total lintas server:
    - **Apache / LiteSpeed**: `Require all denied` di `.htaccess`.
    - **Microsoft IIS**: `<authorization><deny users="*" /></authorization>` di `web.config`.
    - **Nginx**: Disediakan `nginx.conf.example` dengan aturan `location ^~ /uploads/ { deny all; return 403; }`.
    - **Fallback**: Script `index.php` yang langsung menghasilkan respons `403 Forbidden`.
  - Hanya gateway `lihat-foto.php` dengan verifikasi sesi aktif yang berhak membaca dan menyajikan file.
* **Dual-Tier Rate Limiting (Anti-Brute Force & Anti-Account Lockout DoS)**:
  - Pembatasan tingkat IP (maksimal 15 percobaan gagal dalam 15 menit) untuk memblokir flood & bot.
  - Pembatasan tingkat akun (maksimal 5 kesalahan password dengan token CSRF valid dalam 15 menit).
  - Percobaan dengan token CSRF tidak valid tidak pernah dikaitkan ke username korban, mencegah serangan DoS penguncian akun.
* **Anti-IDOR (Insecure Direct Object Reference)**:
  - Validasi kepemilikan ketat: Petugas hanya dapat mengedit dan menghapus laporan miliknya sendiri, kecuali jika memiliki hak akses Administrator.
* **Anti-SQL Injection (SQLi)**:
  - Menggunakan **PDO Prepared Statements** dengan parameter binding di seluruh query database.
  - Mematikan `PDO::ATTR_EMULATE_PREPARES` di level driver MySQL.
* **Anti-Cross-Site Scripting (XSS)**:
  - Seluruh output data user disanitasi menggunakan `e()` (`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`).
  - Dilengkapi HTTP Security Headers (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, dll.).
* **Anti-Formula / CSV Injection (OWASP CWE-1236)**:
  - Seluruh data ekspor log dan rekap laporan dinetralisir dari awalan formula (`=`, `+`, `-`, `@`, `\t`, `\r`) agar aman saat dibuka di Microsoft Excel atau Google Sheets.
* **Anti-RCE & Upload Bypass (Strict File Upload)**:
  - Verifikasi MIME type nyata dari isi file menggunakan `finfo_file()`.
  - Validasi integritas gambar menggunakan `getimagesize()`.
  - Nama file diganti total dengan string acak kriptografis 128-bit (`bin2hex(random_bytes(16))`).
* **Anti-CSRF & Keamanan Sesi**:
  - Seluruh request mutasi data (POST) wajib menyertakan token CSRF (termasuk aksi Logout).
  - Cookie sesi diatur dengan `HttpOnly`, `SameSite=Lax`, `session.use_strict_mode=1`, dan idle session timeout 2 jam.

---

## 🐳 Menjalankan via Docker (Rekomendasi Praktis)

Menjalankan aplikasi menggunakan Docker adalah cara tercepat dan paling terisolasi tanpa perlu menginstal PHP atau MySQL secara manual di komputer Anda.

### Prasyarat:
Pastikan **Docker Desktop** atau **Docker Engine + Docker Compose** telah terinstal di komputer Anda.

### 1. Menjalankan Container:
Buka terminal di folder proyek ini, lalu jalankan perintah:

```bash
docker compose up -d --build
```

Docker akan otomatis:
1. Membangun image PHP 8.2 Apache dengan ekstensi `pdo_mysql`, `gd`, `fileinfo`.
2. Menyiapkan database MariaDB 10.11 dan mengimpor skema tabel dari `database.sql` secara otomatis.
3. Membuat volume penyimpanan privat `storage_uploads` di luar document root web.
4. Menjalankan phpMyAdmin untuk pengelolaan database visual.

### 2. Akses Aplikasi di Browser:
- **Aplikasi Web Aslab**: [http://localhost:8080](http://localhost:8080)
- **phpMyAdmin (Database GUI)**: [http://localhost:8081](http://localhost:8081)
  - Server: `db`
  - Username: `root`
  - Password: `root_secret123`

### 3. Akun Login Bawaan Pertama Kali:
- **Username**: `aslab`
- **Password**: `admin123`

> [!IMPORTANT]
> Segera ganti password akun default atau daftarkan akun admin baru melalui menu **Kelola Aslab** setelah pertama kali login.

### 4. Perintah Docker yang Berguna:
- **Melihat status container**:
  ```bash
  docker compose ps
  ```
- **Melihat log aplikasi real-time**:
  ```bash
  docker compose logs -f app
  ```
- **Menghentikan container**:
  ```bash
  docker compose down
  ```
- **Menghentikan container dan mereset volume data**:
  ```bash
  docker compose down -v
  ```

---

## 💻 Menjalankan di Komputer Lokal Tanpa Docker (XAMPP)

1. Salin seluruh isi folder ini ke dalam folder `C:\xampp\htdocs\laporanharianaslab`.
2. Buka **XAMPP Control Panel**, lalu nyalakan modul **Apache** dan **MySQL**.
3. Buka browser: `http://localhost/phpmyadmin`.
4. Buat database baru bernama `laporan_aslab`, lalu import file `database.sql`.
5. Buka aplikasi di browser: [http://localhost/laporanharianaslab](http://localhost/laporanharianaslab).

---

## 🚀 Panduan Deploy ke Hosting InfinityFree

1. Buat database MySQL baru melalui **Control Panel (cPanel)** InfinityFree.
2. Buka **phpMyAdmin** di InfinityFree, pilih database tersebut, lalu import file `database.sql`.
3. Buka file `config/database.php`, lalu sesuaikan `$db_host`, `$db_name`, `$db_user`, dan `$db_pass` dengan informasi dari vPanel InfinityFree.
4. Upload seluruh file proyek ke folder **`htdocs/`** via Online File Manager atau FTP (FileZilla).
5. Selesai! Aplikasi Anda siap digunakan melalui domain InfinityFree Anda.
