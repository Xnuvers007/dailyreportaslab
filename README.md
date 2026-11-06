# 📋 Sistem Laporan Harian Asisten Laboratorium (Aslab)

Aplikasi web modern, ringan, dan aman untuk mencatat inventaris harian (kabel roll, proyektor, peralatan lab), catatan operasional shift, dokumentasi foto, dan fitur **Salin Format WhatsApp** siap kirim ke grup.

Didesain khusus untuk hosting gratis di **InfinityFree** (PHP 8.2 & MySQL) maupun server lokal kampus.

---

## ✨ Fitur Utama

1. **Sistem Tertutup & Privasi Terjaga (100% Private)**:
   - Pengunjung luar / publik **sama sekali tidak dapat melihat data stok, catatan, memo, maupun foto**.
   - Setiap akses ke website otomatis dialihkan ke halaman Login.
2. **Tidak Ada Registrasi Publik**:
   - Pendaftaran akun aslab baru **hanya dapat dilakukan oleh Administrator** melalui menu khusus **Kelola Aslab**.
   - Admin dapat menambah aslab baru, mereset password aslab yang lupa, atau menonaktifkan akun.
3. **Dashboard Ketersediaan Real-Time**:
   - Menampilkan total unit, stok siap pakai, dan barang yang rusak/trouble.
   - Ringkasan memo / pengumuman aslab terkini disertai dokumentasi foto.
4. **Kalkulasi Otomatis Stok**:
   - Menghitung secara otomatis `Total Unit = Tersedia + Rusak` di form input tanpa perlu hitung manual.
   - Kolom catatan spesifik per barang (contoh: *"1 Gabisa digunakan kata pak Agus Wiranto"*).
5. **Salin ke WhatsApp (1-Click Copy WA)**:
   - Sekali klik, format teks laporan harian langsung rapi tersalin ke clipboard dan siap di-paste ke grup WhatsApp Aslab/Dosen/Laboran.
6. **Dokumentasi Foto**:
   - Upload foto bukti (kardus kabel baru, barang rusak, nota perbaikan) dengan kompresi dan preview langsung.
7. **Pencarian & Arsip Riwayat**:
   - Filter laporan berdasarkan tanggal, shift (Pagi/Siang/Sore/Malam), maupun pencarian kata kunci.
8. **Desain Modern & Bebas Emoji**:
   - Menggunakan ikon rapi **FontAwesome 6** dan tata letak dashboard yang responsif di smartphone.

---

## 🛡️ Keamanan Sistem (Security Hardening)

Aplikasi ini telah diprogram dengan standar keamanan ketat untuk mencegah berbagai celah eksploitasi:

* **Anti-SQL Injection (SQLi)**:
  - Menggunakan **PDO Prepared Statements** dengan parameter binding di seluruh query database.
  - Mematikan `PDO::ATTR_EMULATE_PREPARES` di driver MySQL.
* **Anti-Cross-Site Scripting (XSS)**:
  - Seluruh output data user di-escape menggunakan fungsi sanitasi `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
  - Dilengkapi HTTP Security Headers (`X-XSS-Protection`, `X-Content-Type-Options: nosniff`).
* **Anti-RCE & Upload Bypass (Strict File Upload)**:
  - **MIME Type Inspection**: Verifikasi MIME type nyata dari isi file menggunakan PHP `finfo`, bukan mempercayai header browser (`$_FILES['type']`).
  - **Image Integrity Check**: Validasi byte gambar menggunakan `getimagesize()` untuk mencegah file executable palsu/polyglot.
  - **Extension Whitelist**: Hanya mengizinkan `.jpg`, `.jpeg`, `.png`, dan `.webp`.
  - **Random Cryptographic Filename**: Nama file diacak total (`bin2hex(random_bytes(8))`) untuk mencegah *Path Traversal*, *Null Byte Injection*, dan *Double Extension* (`shell.php.jpg`).
  - **Anti-Script Execution `.htaccess`**: Folder `uploads/` dikunci dengan `.htaccess` khusus yang mematikan PHP engine dan memblokir eksekusi seluruh script.
* **Anti-CSRF (Cross-Site Request Forgery)**:
  - Seluruh form POST (buat laporan, edit, hapus) wajib menyertakan token CSRF yang divalidasi dengan `hash_equals()`.
* **Keamanan Sesi**:
  - Cookie sesi diatur dengan flag `HttpOnly`, `SameSite=Lax`, dan regenerasi Session ID saat login untuk mencegah *Session Fixation*.

---

## 🚀 Panduan Deploy ke InfinityFree

### Langkah 1: Buat Database di InfinityFree
1. Login ke dashboard **InfinityFree**.
2. Masuk ke **Control Panel (cPanel)** akun hosting Anda.
3. Buka menu **MySQL Databases**.
4. Buat database baru, misalnya: `laporanaslab`.
5. Catat informasi berikut yang tertera di halaman MySQL InfinityFree:
   - **MySQL Hostname**: (contoh: `sql123.infinityfree.com`)
   - **MySQL Database Name**: (contoh: `if0_12345678_laporanaslab`)
   - **MySQL Username**: (contoh: `if0_12345678`)
   - **MySQL Password**: (password akun vPanel Anda)

### Langkah 2: Import Tabel SQL via phpMyAdmin
1. Di Control Panel InfinityFree, klik **phpMyAdmin**.
2. Pilih database yang baru Anda buat.
3. Klik tab **Import** di bagian atas.
4. Klik **Choose File** dan pilih file `database.sql` dari folder proyek ini.
5. Klik **Go** / **Import** di bagian bawah.

### Langkah 3: Konfigurasi Database di Web
1. Buka file `config/database.php` menggunakan teks editor.
2. Ubah variabel kredensial sesuai data dari Langkah 1:
   ```php
   $db_host = 'sql123.infinityfree.com'; // Sesuaikan host MySQL Anda
   $db_port = '3306';
   $db_name = 'if0_12345678_laporanaslab'; // Sesuaikan nama database
   $db_user = 'if0_12345678';             // Sesuaikan username
   $db_pass = 'Password_Anda_Disini';    // Sesuaikan password
   ```
3. Simpan perubahan file.

### Langkah 4: Upload File ke InfinityFree
1. Buka **Online File Manager** di InfinityFree (atau gunakan **FileZilla** dengan FTP).
2. Masuk ke direktori **`htdocs/`**.
3. Upload seluruh isi folder proyek ini ke dalam folder **`htdocs/`**:
   - `assets/`
   - `config/`
   - `includes/`
   - `uploads/`
   - `.htaccess`
   - `index.php`, `buat-laporan.php`, `detail.php`, `edit-laporan.php`, `hapus-laporan.php`, `login.php`, `logout.php`, `riwayat.php`.
4. Selesai! Website laporan aslab Anda sudah aktif dan bisa diakses lewat domain gratis InfinityFree Anda.

---

## 💻 Cara Menjalankan di Komputer Lokal (Uji Coba)

Jika Anda ingin mencoba di komputer terlebih dahulu:

### Menggunakan XAMPP:
1. Salin seluruh isi folder ini ke dalam folder `C:\xampp\htdocs\laporanharianaslab`.
2. Buka XAMPP Control Panel, nyalakan **Apache** dan **MySQL**.
3. Buka browser: `http://localhost/phpmyadmin`.
4. Buat database baru bernama `laporan_aslab`, lalu import file `database.sql`.
5. Buka aplikasi di browser: `http://localhost/laporanharianaslab`.

### Akun Login Default:
* **Username**: `aslab`
* **Password**: `admin123`
*(Password dapat diubah kapan saja di database)*

