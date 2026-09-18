# Panduan Setup Lingkungan Pengembangan — E-Recruitment RPG

Dokumen ini berisi panduan teknis langkah demi langkah untuk menyiapkan **satu mesin komputer pengembang (Developer Workstation) dari nol** hingga sistem siap dijalankan dan diuji.

Proses setup dinyatakan berhasil sempurna apabila perintah `php tools/test-koneksi.php` menghasilkan status **`>>> GERBANG FASE 0 TERBUKA`**.

---

## 0. Prasyarat Sistem

* **Sistem Operasi:** Windows 10 / 11 x64 (atau Windows Server 2012+).
* **Git:** Versi terbaru untuk Windows.
* **Akses ke SQL Server 2008 R2 (SP2, 10.50.4000.0) x64**:
  * **Opsi A (Instance Lokal):** Install SQL Server 2008 R2 Express atau Developer Edition di komputer sendiri. **Wajib mengaktifkan Mixed Mode Authentication (SQL Server and Windows Authentication)**.
  * **Opsi B (Instance Bersama / Jaringan Kantor):** Menghubungkan ke server SQL Server 2008 R2 yang sudah aktif di jaringan lokal kantor RPG (port default `1433`).

> ⚠️ **Catatan Penting Kompatibilitas T-SQL:**  
> Pengujian sistem **wajib menggunakan SQL Server 2008 R2 asli**. Versi yang lebih baru (2012, 2019, 2022) akan meloloskan sintaks T-SQL modern (seperti `CONCAT`, `OFFSET FETCH`, `TRY_CONVERT`, atau `THROW`) yang akan langsung menyebabkan error fatal saat kode dideploy ke server produksi RPG.

---

## 1. Pemasangan PHP 7.4.33 (NTS x64)

Karena PHP 7.4 sudah berstatus arsip resmi, unduh dari repositori resmi Windows PHP:

1. Unduh file zip:  
   👉 [php-7.4.33-nts-Win32-vc15-x64.zip](https://windows.php.net/downloads/releases/archives/php-7.4.33-nts-Win32-vc15-x64.zip)
2. Ekstrak file zip tersebut ke direktori:  
   `C:\php7.4` *(atau ke folder PHP Laragon jika menggunakan Laragon)*.

---

## 2. Pemasangan Driver `sqlsrv` & `pdo_sqlsrv` 5.9

Driver versi **5.9.0** adalah satu-satunya seri driver Microsoft resmi yang dirancang dan diuji untuk PHP 7.4:

1. Unduh kedua paket driver PECL resmi:
   * [php_sqlsrv-5.9.0-7.4-nts-vc15-x64.zip](https://windows.php.net/downloads/pecl/releases/sqlsrv/5.9.0/php_sqlsrv-5.9.0-7.4-nts-vc15-x64.zip)
   * [php_pdo_sqlsrv-5.9.0-7.4-nts-vc15-x64.zip](https://windows.php.net/downloads/pecl/releases/pdo_sqlsrv/5.9.0/php_pdo_sqlsrv-5.9.0-7.4-nts-vc15-x64.zip)
2. Dari masing-masing file zip, ambil file:
   * `php_sqlsrv.dll`
   * `php_pdo_sqlsrv.dll`
3. Salin kedua file `.dll` tersebut ke dalam folder ekstensi PHP:  
   `C:\php7.4\ext\`

---

## 3. Pemasangan Microsoft ODBC Driver 17 for SQL Server

Sistem membutuhkan driver ODBC level sistem operasi agar PHP dapat berkomunikasi dengan SQL Server:

1. Unduh dan pasang **Microsoft ODBC Driver 17 for SQL Server** (versi 17.4+ x64).
2. ⛔ **JANGAN MENGGUNAKAN DRIVER 18:**  
   Microsoft ODBC Driver 18 telah memutus dukungan untuk SQL Server 2008 R2 dan koneksi database akan selalu ditolak (*Handshake Failure*).
3. Verifikasi instalasi driver melalui PowerShell:
   ```powershell
   Get-OdbcDriver | Where-Object { $_.Name -like "*17*" }
   ```

---

## 4. Konfigurasi `php.ini`

1. Masuk ke `C:\php7.4\` dan buat file `php.ini`:
   ```powershell
   copy C:\php7.4\php.ini-development C:\php7.4\php.ini
   ```
2. Buka file `php.ini` dengan teks editor, pastikan direktif berikut aktif (hilangkan tanda titik koma `;` di depannya):
   ```ini
   extension_dir = "ext"
   ```
3. Tambahkan baris konfigurasi ekstensi dan zona waktu berikut pada bagian paling bawah file:
   ```ini
   extension=sqlsrv
   extension=pdo_sqlsrv
   extension=mbstring
   extension=openssl
   extension=curl
   extension=fileinfo
   extension=gd

   date.timezone = Asia/Jakarta
   ```

---

## 5. Menambahkan PHP ke User Environment Variable (PATH)

1. Tambahkan `C:\php7.4` ke dalam **User Environment Variable `Path`** Windows.
2. Tutup seluruh jendela terminal, lalu buka terminal **PowerShell** baru.
3. Jalankan pengujian:
   ```powershell
   php -v
   # Harus menampilkan: PHP 7.4.33 ... NTS ... x64

   php -m
   # Harus memuat: sqlsrv, pdo_sqlsrv, mbstring, curl, gd
   ```

---

## 6. Clone Repositori Proyek

```powershell
git clone https://github.com/GoRPGdev/e-recruitmentRPG.git e-recruitmentRPG
cd e-recruitmentRPG
```

---

## 7. Inisialisasi Database Pengembang (Sekali Saja)

Jalankan skrip bootstrap menggunakan akun administrator SQL Server (`sa` atau anggota grup `sysadmin`):

```powershell
# Contoh untuk mesin dev Kahfi:
sqlcmd -S localhost -U sa -P PasswordSaAnda -i database\bootstrap\create_dev_db_KAHFI.sql
```

> **Sebelum menjalankan:** Buka file `create_dev_db_KAHFI.sql`, sesuaikan kata sandi login pengguna database `erec_app` yang diinginkan. Kata sandi ini nantinya dimasukkan ke file `tools/koneksi.local.php`.

---

## 8. Konfigurasi Koneksi Lokal (`tools/koneksi.local.php`)

Salin file contoh konfigurasi lokal:

```powershell
copy tools\koneksi.local.sample.php tools\koneksi.local.php
```

Sesuaikan isinya:

```php
<?php
return array(
    'host'     => 'localhost',              // Atau IP server instance bersama
    'database' => 'RPG_EREC_DEV_KAHFI',     // Nama database pengembang Anda
    'user'     => 'erec_app',               // Akun pengguna aplikasi
    'password' => 'KataSandiErecAppAnda',   // Kata sandi dari skrip bootstrap
    'storage'  => 'D:\\erecruitment-storage',// Direktori fisik penyimpanan berkas (DI LUAR webroot)
);
```

> 🔒 **Keamanan:** File `tools/koneksi.local.php` telah dimasukkan ke `.gitignore` dan tidak akan pernah ter-commit ke GitHub. Jangan pernah membagikan atau meng-commit kata sandi basis data.

---

## 9. Validasi Gerbang Fase 0 (Koneksi & Driver)

Jalankan skrip pengujian gerbang utama:

```powershell
php tools/test-koneksi.php
```

Pastikan skrip berakhir dengan tulisan hijau:
```text
==============================================================
  RINGKASAN
==============================================================
  Lulus      : 19
  Peringatan : 1 (Peringatan DriverName pada ODBC 17 wajar & aman diabaikan)
  Gagal      : 0

  >>> GERBANG FASE 0 TERBUKA.
```

---

## 10. Eksekusi Migrasi Skema & Stored Procedure

Setelah gerbang terbuka, terapkan seluruh migrasi tabel dan prosedur:

```powershell
# 1. Jalankan seluruh skrip migrasi database:
php tools/migrate.php up

# 2. Deploy seluruh Stored Procedure T-SQL:
php tools/migrate.php proc

# 3. Periksa status sinkronisasi:
php tools/migrate.php status
# Seluruh baris migrasi harus berstatus [ OK ]
```

---

## 11. Menjalankan Aplikasi Web

Jalankan server pengembangan bawaan:

```powershell
php -S localhost:8080 -t web router.php
```

Buka peramban di: **[http://localhost:8080/auth/login](http://localhost:8080/auth/login)**

* **Super Admin Login:**  
  NIK: `EMP-001`  
  Password: `demo123`
* **User Departemen Login:**  
  NIK: `EMP-010`  
  Password: `demo123`

---

## 12. Menjalankan Automated Test Suite (Verifikasi Kesiapan)

Untuk memastikan seluruh aspek keamanan, penanganan token, proteksi kebocoran path server, dan validasi berkas berjalan 100% sempurna:

```powershell
# Jalankan uji kesiapan produksi:
php tools/test-production-hardening.php

# Jalankan uji keamanan unggah berkas CV & MIME spoofing:
php tools/test-file-upload.php
```
Kedua pengujian di atas harus memberikan hasil **100% PASS**.
