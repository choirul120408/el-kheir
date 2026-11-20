# SMK Bangun Nusa Bangsa

Website profil menggunakan PHP, PDO, dan MySQL/MariaDB.

## Menjalankan secara lokal

1. Pastikan PHP dengan ekstensi `pdo_mysql` dan MySQL/MariaDB tersedia.
2. Impor `database.sql` ke server database.
3. Atur variabel lingkungan koneksi sebelum menjalankan PHP. Contoh PowerShell:

   ```powershell
   $env:DB_HOST = "127.0.0.1"
   $env:DB_PORT = "3306"
   $env:DB_NAME = "smk_bnb"
   $env:DB_USER = "root"
   $env:DB_PASSWORD = ""
   php -S 127.0.0.1:8000
   ```

4. Buka `http://127.0.0.1:8000` atau buka melalui Apache XAMPP `http://localhost/smk`.

## Portal Akademik & Login (Indodax Fintech Edition)

Portal login baru bertema **Indodax** berlokasi di `login.php` dan dashboard di `dashboard.php`.

### Fitur Utama:
- **Tampilan Dark Mode Eksklusif Khas Indodax**: Palet warna Deep Tech Navy (`#07090e`), Electric Cyan (`#00c2ff`), Indodax Blue (`#0066f5`), dan Crypto Green (`#0ecb81`).
- **Live Crypto & Academic Ticker**: Running ticker harga aset kripto dan indeks prestasi akademik secara real-time.
- **Animasi Neural Network Canvas**: Latar belakang partikel konstelasi dinamis.
- **Trading-Style Chart**: Grafik area neon pergerakan indeks performa vokasi siswa.
- **Multi-Role Login**: Siswa / Taruna, Guru & Pengajar, Administrator.
- **Autentikasi Dual-Mode**:
  1. Login Form (Email / NISN / NIP) dengan pengukur kekuatan sandi kriptografi.
  2. Scan QR Code Mobile App dengan laser scanner animasi.
- **Verifikasi Kriptografis Anti-Bot**: Kotak verifikasi keamanan interaktif berstandar exchange kripto.
- **Akses Demo 1-Klik**: Tombol instan untuk langsung mengisi kredensial uji coba tanpa mengetik.

### Akun Demo:
1. **Siswa**: `siswa@smkbnb.sch.id` / `siswa123` (atau NISN: `202401001`)
2. **Guru**: `guru@smkbnb.sch.id` / `guru123` (atau NIP: `198503152010011002`)
3. **Admin**: `admin@smkbnb.sch.id` / `admin123` (atau username: `admin`)

Variabel koneksi juga dapat diatur pada konfigurasi virtual host/server. Jangan menyimpan kata sandi database produksi di repositori. Pesan dari formulir kontak tersimpan pada tabel `contact_messages`.
