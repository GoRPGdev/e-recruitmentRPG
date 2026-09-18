# E-Recruitment Ratu Pertiwi Group (RPG)

Sistem informasi rekrutmen dan seleksi calon karyawan terpadu untuk lingkungan **Ratu Pertiwi Group (RPG)** yang mengelola dua kanal rekrutmen sekaligus: **Headquarter (HQ)** dan **Manpower Outlet / Retail**.

Sistem dirancang dengan arsitektur **Database-First**, di mana seluruh logika mutasi bisnis, validasi integritas, transisi alur seleksi, serta audit mutasi dieksekusi melalui **Stored Procedure (T-SQL)** di SQL Server 2008 R2, sedangkan lapisan PHP CodeIgniter 3 bertindak sebagai kontroler transport dan perender antarmuka pengguna yang aman dan cepat.

---

## 1. Tech Stack Resmi (Terkunci)

| Komponen | Versi / Spesifikasi | Keterangan |
|---|---|---|
| **Backend Framework** | CodeIgniter 3.1.x | Arsitektur MVC ringan, model tipis pemanggil Stored Procedure |
| **Bahasa Pemrograman** | PHP 7.4.33 (NTS / TS x64) | Wajib versi 7.4 (sesuai target server produksi Windows) |
| **Basis Data** | Microsoft SQL Server 2008 R2 | Versi minimum SP2 (10.50.4000.0) x64 |
| **Ekstensi PHP SQL** | `sqlsrv` & `pdo_sqlsrv` 5.9.0 | Satu-satunya seri driver PHP resmi yang mendukung PHP 7.4 |
| **ODBC Driver** | Microsoft ODBC Driver 17 (17.4+) | **Larangan Keras Driver 18** (Driver 18 tidak mendukung 2008 R2) |
| **Frontend Styling** | Vanilla CSS3 + Native Tokens | Palet interior AdminLTE skin-purple (identik shell Payroll RPG) |
| **Tipografi** | Source Sans Pro (WOFF2) | Self-hosted lokal (tanpa dependensi koneksi internet luar) |
| **Integrasi Eksternal** | Single Sign-On (SSO) | Jembatan tiket HMAC-SHA256 dari aplikasi Payroll RPG |
| **Background Scheduler** | Windows Task Scheduler | Rekap matriks funnel harian & anonimisator retensi otomatis |

---

## 2. Cara Menjalankan Proyek dari Nol (Quick Start)

### A. Langkah Menjalankan Server Lokal
1. Buka terminal **PowerShell** atau **Git Bash** di komputer Anda.
2. Masuk ke direktori utama proyek:
   ```powershell
   cd D:\KAHFI-RPG\e-rekruitmenRPG
   ```
3. Jalankan server bawaan PHP menggunakan router lokal:
   ```powershell
   php -S localhost:8080 -t web router.php
   ```
   *(Atau jalankan via Apache Laragon/IIS dengan document root mengarah ke folder `web/`).*

### B. Mengakses Aplikasi di Browser
Buka browser favorit Anda (Google Chrome / Microsoft Edge) dan akses URL berikut:

* **Portal Internal Pegawai & HR:**  
  👉 **[http://localhost:8080/auth/login](http://localhost:8080/auth/login)** *(atau [http://localhost:8080](http://localhost:8080))*
* **Formulir Publik Pelamar (Contoh):**  
  👉 `http://localhost:8080/lamar/{url_slug}`
* **Formulir Onboarding Mandiri Pelamar (Contoh):**  
  👉 `http://localhost:8080/onboarding/{token_unik}`

---

## 3. Akun Pengguna Bawaan (Default Credentials)

Autentikasi sistem menggunakan **Nomor Induk Karyawan (NIK)** yang terintegrasi penuh dengan data Payroll:

| Peran (Role) | NIK Karyawan (Login ID) | Kata Sandi | Cakupan Hak Akses |
|---|:---:|:---:|---|
| **Super Administrator** | `EMP-001` | `demo123` | Akses penuh seluruh modul, konfigurasi alur, master data, dan lintas departemen. |
| **User Departemen (Head of Mkt)** | `EMP-010` | `demo123` | Pengajuan formasi MPR, evaluasi kandidat departemen Marketing. |

> **Masa Berlaku Sesi (*Session Timeout*):**  
> Sesi login aktif berlaku selama **maksimal 6 jam (21.600 detik)**. Melebihi batas waktu tersebut, sistem secara otomatis melakukan *auto-logout* demi mematuhi standar keamanan data perusahaan.

---

## 4. Struktur Direktori Proyek

```text
e-recruitmentRPG/
├── database/
│   ├── bootstrap/          # Skrip inisialisasi awal database dev (DB kosong + hak DDL)
│   ├── migrations/         # Skrip migrasi DDL berurut (YYYYMMDD_HHMM__deskripsi.sql)
│   ├── procedures/         # File Stored Procedure T-SQL idempotent (CREATE/ALTER)
│   └── seed/               # Data uji organisasi dev & dokumentasi data master
├── docs/
│   ├── ERD_Terkoreksi_E-Recruitment_RPG.md  # Arsitektur relasi tabel resmi
│   ├── Formulir_Data_Pelamar_RPG.pdf        # Standar fisik formulir pelamar RPG
│   ├── MATRIKS_HAK_AKSES.md                 # Matriks izin per peran (RBAC Matrix)
│   ├── PANDUAN_UAT_HR.md                    # Lembar kerja & skenario pengujian UAT
│   └── SSO_PAYROLL.md                       # Spesifikasi integrasi SSO Payroll RPG
├── tools/
│   ├── build-funnel.php    # Task harian: menghitung agregat corong seleksi
│   ├── run-retensi.php     # Task harian: anonimisasi pelamar & pembersihan berkas
│   ├── migrate.php         # Runner migrasi skema & prosedur SQL Server
│   ├── mkuser.php          # Utilitas CLI pembuatan / reset kata sandi user via NIK
│   ├── setup-real-users.php# Utilitas provisi akun operasional riil tim HR
│   ├── test-koneksi.php    # Validasi driver, ekstensi, & batasan SQL Server 2008 R2
│   ├── test-production-hardening.php # Suite uji keamanan & proteksi kebocoran data
│   └── test-file-upload.php          # Suite uji validasi berkas CV, MIME spoofing, dsb.
├── web/
│   ├── application/        # Kode aplikasi CodeIgniter 3 (Controllers, Models, Views)
│   │   ├── config/         # Konfigurasi aplikasi, sesi 6 jam, CSRF, & database
│   │   ├── controllers/    # Transport controllers (Auth, Requisitions, Pipeline, dll)
│   │   ├── models/         # Model tipis pemanggil Stored Procedure
│   │   └── views/          # Tampilan antarmuka (Responsive UI, Views, Layouts)
│   ├── assets/             # Aset statis lokal (fonts WOFF2, CSS, logo resmi RPG, favicon)
│   └── index.php           # Front controller utama aplikasi
├── router.php              # Router server lokal PHP (clean URL simulation)
├── README.md               # Dokumentasi utama proyek
└── SETUP.md                # Panduan setup lingkungan mesin dev dari nol
```

---

## 5. Fitur Utama Sistem

1. **Alur Pengajuan Formasi (MPR - Manpower Requisition):**
   - Pengajuan kebutuhan karyawan oleh Kepala Departemen (HQ) atau Area Leader (Outlet).
   - Alur persetujuan 2 putaran terstruktur: *Review HR* $\rightarrow$ *Persetujuan Direksi (BOD)*.
2. **Papan Seleksi Kandidat (Interactive Pipeline Board):**
   - Pelacakan alur seleksi per kandidat dengan pemisahan tahapan yang jelas.
   - **Persistent Collapse Memory:** Status tahapan yang dilipat otomatis tersimpan di `localStorage` per lowongan.
   - **Smart Scroll Memory:** Posisi layar tidak melompat ke atas saat menyimpan evaluasi.
   - **Pulse Glow Effect:** Baris pelamar yang baru dinilai otomatis disorot dengan warna hijau lembut.
3. **Formulir Pelamar & Onboarding Digital Komprehensif:**
   - Formulir pendaftaran publik 21 field dengan perlindungan token anti-spam.
   - Formulir onboarding A-I mandiri: data keluarga inti (minimal 1 anggota keluarga, opsi anak spesifik & wali, centang almarhum, auto-fill jenis kelamin), riwayat kerja, riwayat pelatihan, dan rekening payroll.
4. **Keamanan & Kepatuhan Data Pribadi (UU PDP No. 27/2022):**
   - Seluruh berkas identitas fisik (KTP, KK, Ijazah, NPWP, Pas Foto) disimpan terisolasi **di luar direktori webroot**.
   - Setiap pembukaan berkas sensitif otomatis tercatat ke dalam tabel `ACCESS_LOG_SENSITIF`.
   - Anonimisasi otomatis setelah masa retensi 1 tahun berakhir (`tools/run-retensi.php`).
5. **Single Sign-On (SSO) Payroll RPG:**
   - Navigasi mulus antar-aplikasi dari sistem Payroll ke e-Recruitment tanpa harus login ulang menggunakan tiket bertanda tangan HMAC-SHA256.

---

## 6. Perintah Perawatan & Pengujian Penting (CLI)

```powershell
# 1. Cek status dan jalankan migrasi database:
php tools/migrate.php status
php tools/migrate.php up

# 2. Deploy ulang seluruh Stored Procedure T-SQL:
php tools/migrate.php proc

# 3. Buat atau ubah akun pengguna via NIK:
php tools/mkuser.php EMP-001 "PasswordKuat123!" "Nama Lengkap" SUPER_ADMIN

# 4. Jalankan pengujian kesiapan produksi & keamanan upload:
php tools/test-production-hardening.php
php tools/test-file-upload.php

# 5. Jalankan simulasi background task scheduler:
php tools/build-funnel.php
php tools/run-retensi.php --dry-run
```

---

## 7. Kontribusi & Git Workflow

- **Branch Utama:** `main` (hanya menerima perubahan yang telah diuji dan lolos verifikasi).
- **Format Commit:** Mengikuti *Conventional Commits* (`feat: ...`, `fix: ...`, `refactor: ...`, `style: ...`).
- **Aturan Migrasi Skema:** File migrasi yang sudah pernah di-commit ke branch `main` **tidak boleh diedit kembali**. Selalu buat file migrasi baru bertanda timestamp untuk perubahan susulan.
