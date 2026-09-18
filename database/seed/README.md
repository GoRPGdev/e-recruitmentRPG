# Dokumentasi Seed Data & Akun Uji — E-Recruitment RPG

Direktori ini mendokumentasikan pemisahan tegas antara **Data Master Sistem (Produksi)** dan **Data Contoh Pengujian (Development / UAT)** pada sistem E-Recruitment Ratu Pertiwi Group.

---

## 1. Perbedaan Filosofi Data

| Kategori | Lokasi File | Sifat & Kepatuhan | Tujuan Penggunaan |
|---|---|---|---|
| **Seed SISTEM** | `database/migrations/*.sql` | Wajib & Ikut ke Produksi | Berisi nilai baku organisasi resmi RPG yang tercatat di `SCHEMA_MIGRATIONS`. Tidak boleh dihapus atau diubah sembarangan. |
| **Data UJI DEV** | `database/seed/*.sql` & `tools/` | Opsional (Hanya Dev / UAT) | Berisi data dummy/contoh untuk pengujian alur seleksi, presentasi, dan verifikasi fitur. **Dilarang dijalankan di server produksi**. |

---

## 2. Rincian Seed SISTEM (Baku di Produksi)

Dijalankan secara otomatis melalui skrip migrasi `php tools/migrate.php up`:

1. **Peran Pengguna (`dbo.M_ROLES`)**:
   - `SUPER_ADMIN`: Administrator Utama Sistem (Akses seluruh menu, konfigurasi alur, master data, dan lintas departemen).
   - `USER_DEPT`: Pengguna Departemen (Kepala Departemen HQ atau Area Leader Outlet untuk mengajukan formasi MPR dan menilai kandidat).
2. **Matriks Izin Modular (`dbo.M_PERMISSIONS` & `dbo.M_ROLE_PERMISSIONS`)**:
   - 13 kode izin spesifik, termasuk: `LIHAT_KANDIDAT`, `LIHAT_CV`, `LIHAT_INTERVIEW`, `LIHAT_DOK_IDENTITAS`, `LIHAT_FINANSIAL`, `LIHAT_GAJI`, `LIHAT_GAJI_PELAMAR`, `LIHAT_KESEHATAN`, `APPROVE`, `EXPORT`, `EDIT_FLOW_TEMPLATE`, `KELOLA_REKRUTMEN`, dan `BUAT_MPR`.
3. **Master Tahapan Seleksi (`dbo.M_STAGE`)**:
   - 11 master tahapan yang terikat ke 7 tipe sumbu laporan baku (`SCREENING`, `KONTAK`, `FORM`, `TEST`, `INTERVIEW`, `OFFER`, `ONBOARD`).
4. **Alur Seleksi Tunggal Standar RPG (`dbo.M_FLOW` & `dbo.M_FLOW_STAGE`)**:
   - Alur baku 8 urutan tahap standar perusahaan yang otomatis di-snapshot saat lamaran pelamar terbentuk.
5. **Master Keputusan & Efek Status (`dbo.M_REMARKS` & `dbo.M_EFEK_STATUS`)**:
   - 30 pilihan alasan keputusan yang terikat pada 3 efek alur baku: `LANJUT`, `GAGAL`, dan `HOLD`.
6. **Master Dokumen Wajib (`dbo.M_DOKUMEN`)**:
   - Standar berkas onboarding: KTP, Kartu Keluarga, Ijazah Terakhir, NPWP, Pas Foto Resmi, Rekening Bank Payroll, dan Surat Bebas Narkoba / MCU.

---

## 3. Akun Pengguna Bawaan (Default Credentials)

Sistem menggunakan format login **NIK Karyawan** (bukan username) yang terintegrasi dengan data Payroll RPG:

| Peran (Role) | NIK Karyawan (Login ID) | Password Bawaan | Lingkup Departemen / Wilayah |
|---|:---:|:---:|---|
| **Super Administrator** | `EMP-001` | `demo123` | Lintas Departemen (Global) |
| **User Departemen (Head of Marketing)** | `EMP-010` | `demo123` | Terkunci pada Departemen Marketing (`MKT`) |

> ⏱️ **Masa Berlaku Sesi:** Sesi login aktif otomatis kedaluwarsa (*auto-logout*) setelah **6 jam**.

---

## 4. Prosedur Penyemaian & Reset Data Uji (Development)

### A. Memasukkan Struktur Master Contoh Organisasi (`dev_organisasi.sql`)
File ini berisi contoh data departemen (`MKT`, `OPS`, `FIN`, `HRD`, `ITD`, dll), posisi kerja, dan outlet retail (`Naughty`, `SOYU`, `Les Femmes`):

```powershell
sqlcmd -S localhost -U erec_app -P PasswordAnda -d RPG_EREC_DEV_KAHFI -i database\seed\dev_organisasi.sql
```

### B. Membuat atau Mengatur Ulang Akun Pengguna via CLI (`tools/mkuser.php`)
Jika ingin membuat akun baru atau me-reset password akun tertentu secara instan dari command line:

```powershell
# Format: php tools/mkuser.php <nik_karyawan> <password> <nama_lengkap> <kode_role> [kode_departemen]

# Contoh membuat Super Admin baru:
php tools/mkuser.php EMP-002 "Rahasia2026!#" "Fachri Recruitment" SUPER_ADMIN

# Contoh membuat User Dept baru untuk Operasional:
php tools/mkuser.php EMP-011 "Operasional2026!" "Head of Operations" USER_DEPT OPS
```

### C. Provisi Akun Standar Organisasi Lengkap (`tools/setup-real-users.php`)
Menyiapkan serangkaian akun resmi untuk tim HR dan para kepala departemen:

```powershell
# Menampilkan daftar pengguna yang ada di database:
php tools/setup-real-users.php --list

# Mengisi akun-akun standar tim HR & Kepala Departemen RPG:
php tools/setup-real-users.php --seed-initial

# Menonaktifkan akun demo jika sistem sudah siap diserahkan ke pengguna riil:
php tools/setup-real-users.php --disable-demo
```

### D. Reset Bersih & Penyemaian Data Demo Komprehensif (`tools/seed-demo.php`)
Jika Anda membutuhkan data dummy pelamar di berbagai tahapan seleksi (mulai dari pelamar baru, tahap interview, psikotes, hingga offering) untuk keperluan demo presentasi atau UAT:

```powershell
# Mengisi skenario lamaran lengkap di berbagai tahapan seleksi:
php tools/seed-demo.php

# Membersihkan kembali seluruh data pelamar demo:
php tools/seed-demo.php --reset
```
