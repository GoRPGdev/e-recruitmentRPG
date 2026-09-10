# E-Recruitment Ratu Pertiwi Group

Aplikasi web internal untuk mengelola **seluruh siklus rekrutmen** Ratu Pertiwi
Group — dari permintaan tenaga kerja sampai karyawan baru masuk — dalam satu
sistem, dengan jejak audit dan kepatuhan UU Pelindungan Data Pribadi (UU PDP
No. 27/2022) yang melekat di setiap langkah.

CodeIgniter 3 · PHP 7.4 · SQL Server 2008 R2 · *Database-First* (semua logika
mutasi bisnis ada di Stored Procedure T-SQL; model CI hanya pemanggil tipis).

---

## Untuk apa aplikasi ini

Sebelumnya proses rekrutmen RPG tersebar di Google Forms, spreadsheet, dan chat.
Aplikasi ini menyatukannya jadi satu alur yang bisa dilacak:

- **Permintaan tenaga kerja (MPR)** diajukan, direview HR, disetujui Direksi —
  lengkap dengan riwayat revisi dan penomoran resmi `MPR/YYYY/MM/NNN`.
- **Lowongan** dipublikasikan sebagai tautan formulir online per-MPR.
- **Pelamar** masuk dari 3 kanal — form publik, input manual (walk-in/outlet),
  atau impor massal CSV — dengan **deduplikasi otomatis** (No. WhatsApp
  ternormalisasi, email, hash SHA-256 berkas CV).
- **Seleksi** berjalan di papan *pipeline* per tahap: kontak, interview,
  psikotes, penawaran, sampai onboarding. Setiap perubahan status tercatat.
- **Dashboard & laporan** menampilkan funnel konversi, time-to-hire, pemenuhan
  formasi, dan alasan gugur per tahap.
- **Data pribadi pelamar** (NIK, rekening, gaji, riwayat kesehatan) dibatasi
  per peran, setiap akses dicatat, dan pelamar yang ditolak dianonimkan otomatis
  setelah masa retensi 12 bulan.

### Dua jalur penempatan

| Jalur | Untuk |
|---|---|
| **HQ** | Posisi kantor pusat / back-office (Staff, Staff Krusial, Spv, Manager, dst.) |
| **MP / Outlet** | Tenaga lini operasional gerai / lapangan (Man Power) |

### Alur seleksi standar (STD_RPG)

```
Screening CV → Kontak Kandidat → Interview HR → Pengisian Form Pelamar
   → Interview User → Penawaran & Negosiasi → Onboarding & Pemberkasan
```

Tahap **Psikotes** dan **Interview BOD** tersedia sebagai sisipan *ad-hoc* untuk
kandidat / posisi tertentu. Setiap lamaran meng-**snapshot** tahapannya saat
dibuat (`APPLICATION_STAGES`) — mengubah template alur tidak mengganggu lamaran
yang sedang berjalan.

---

## Modul

| Modul | Isi |
|---|---|
| **Requisitions (MPR)** | Buat MPR, submit Review HR / BOD, revisi multi-putaran, catatan arahan HR & BOD, batal / kadaluarsa |
| **Postings** | Publikasi tautan lowongan per-MPR, buka/tutup form, konfigurasi kuesioner & berkas prasyarat, metrik submit |
| **Lamar** (publik) | Form pendaftaran kerja online per slug lowongan + upload CV ke penyimpanan di luar webroot |
| **Manual / Import** | Input pelamar walk-in/outlet; impor massal CSV dengan preview + commit transaksional |
| **Pipeline** | Papan seleksi interaktif: advance stage, log kontak WA/telepon, jadwal interview, sisipan tahap ad-hoc |
| **Candidates** | Daftar & profil lengkap kandidat, cetak/PDF formulir aplikasi, token form onboarding mandiri |
| **Documents / Berkas** | Unggah & verifikasi berkas (KTP, CV, Ijazah, Pas Foto, dll.), streaming file aman, gate data sensitif + `ACCESS_LOG_SENSITIF` |
| **Onboarding** | Formulir data karyawan baru (Section A–J) dengan auto-save: identitas, keluarga, pelatihan, pengalaman, referensi, kuesioner, rekening payroll |
| **Flowbuilder** | Susun urutan tahap seleksi, remark keputusan & efek status, dokumen wajib per tahap, batas upaya kontak |
| **Master** | Posisi (job desc, kualifikasi, level organisasi), departemen, outlet, jenis dokumen, remark, efek status — semua *soft delete* |
| **Dashboard / Reports / Export** | KPI & matriks funnel, laporan analitik, ekspor Excel/CSV (dengan audit saat kolom sensitif ikut) |
| **Users** | Manajemen akun & scoping departemen, reset password bcrypt, aktif/nonaktif *soft delete* |

---

## Peran & hak akses

Sistem aktif memakai **2 peran**:

| Peran | Akses |
|---|---|
| **SUPER_ADMIN** | Kontrol penuh lintas modul & lintas departemen (tanpa scoping) |
| **USER_DEPT** | Hanya MPR & kandidat pada lowongan **departemennya sendiri**; pipeline read-only; tanpa akses finansial/kesehatan/export/manajemen user |

Detail matriks per-permission ada di `docs/MATRIKS_HAK_AKSES.md`.

---

## Kepatuhan UU PDP No. 27/2022

- **Hak akses berjenjang** untuk NIK, riwayat kesehatan, nomor rekening, dan
  nominal gaji — masing-masing permission terpisah.
- **`ACCESS_LOG_SENSITIF`** mencatat setiap buka dokumen / ekspor data sensitif.
- **Consent data kesehatan** terpisah dari consent umum, wajib dicatat.
- **Retensi otomatis 12 bulan** untuk pelamar yang ditolak, lalu **anonimisasi**
  data pribadi lewat `sp_AnonimisasiRetensi` (dijalankan via Windows Task
  Scheduler, `tools/run-retensi.php`).

---

## Mulai dari mana

Setup mesin dev dari nol (PHP, driver, DB, gerbang Fase 0): ikuti **`SETUP.md`**.
Ringkasnya:

1. **Baca dulu** — `CLAUDE.md` (aturan main & batasan T-SQL SQL Server 2008 R2)
   dan `docs/RENCANA_DEVELOPMENT.md` (fase, checklist, pembagian tugas).
2. **Siapkan koneksi**
   ```bash
   cp tools/koneksi.local.sample.php tools/koneksi.local.php
   cp web/application/config/database.sample.php web/application/config/database.php
   cp web/application/config/config.sample.php   web/application/config/config.php
   # isi host / database / user / password (3 file di atas gitignored)
   # config.php: isi encryption_key -> php -r "echo bin2hex(random_bytes(16));"
   ```
3. **Buka gerbang Fase 0** — jangan menulis Stored Procedure sebelum ini hijau:
   ```bash
   php tools/test-koneksi.php
   ```
4. **Jalankan migrasi & deploy Stored Procedure**
   ```bash
   php tools/migrate.php status
   php tools/migrate.php up
   php tools/migrate.php proc
   ```
5. **Isi data demo & jalankan** (dev saja)
   ```bash
   php tools/seed-demo.php
   php -S localhost:8090 -t web
   ```
   Buka `http://localhost:8090/` → login `demo_super_admin` / `demo_user_dept`
   (password `demo123`).

---

## Struktur

```
CLAUDE.md                 Aturan main untuk Claude Code & developer
SETUP.md                  Checklist setup mesin dev dari nol
docs/                     ERD, rencana development, matriks hak akses, laporan progres, preview UI
database/migrations/      Migrasi berurut (tidak pernah diedit setelah dijalankan)
database/procedures/      Stored procedure (idempotent, boleh di-deploy ulang)
database/bootstrap/       Skrip buat DB dev + login (manual, sekali, bukan migrasi)
database/seed/            Data contoh untuk pengujian
tools/test-koneksi.php    Validasi driver & koneksi — gerbang Fase 0
tools/migrate.php         Runner migrasi (status | up | proc), mencatat ke SCHEMA_MIGRATIONS
tools/seed-demo.php       Data demo end-to-end (--reset untuk hapus)
tools/test-*.php          Skrip uji per-alur (jalankan manual)
web/                      Aplikasi CodeIgniter 3 (document root = web/)
```

---

## Lingkungan

| Komponen | Versi | Catatan |
|---|---|---|
| PHP | 7.4.x (NTS/TS ikut Apache) | EOL — ambil dari arsip resmi |
| sqlsrv / pdo_sqlsrv | 5.9 | Satu-satunya seri yang mendukung PHP 7.4 |
| ODBC Driver | **17.4+** | **JANGAN 18** — tidak mendukung SQL Server 2008 R2 |
| SQL Server | 2008 R2 (10.50.x) | Mixed Mode auth wajib |

Job terjadwal (`tools/build-funnel.php`, `tools/run-retensi.php`) pakai
**Windows Task Scheduler**, bukan SQL Server Agent.

---

## Database dev

Masing-masing developer punya database sendiri, disinkronkan lewat file migrasi
di Git — bukan lewat backup/restore.

| Orang | Database |
|---|---|
| Kiki | `RPG_EREC_DEV_KIKI` |
| Kahfi | `RPG_EREC_DEV_KAHFI` |

## Alur Git

```
main                  selalu jalan, hanya lewat Pull Request
 └─ feat/<modul>       satu branch per modul
```

```bash
git pull --rebase origin main      # sebelum mulai & sebelum push
git push origin feat/<modul>
```

Migrasi pakai timestamp (`YYYYMMDD_HHMM__deskripsi.sql`) supaya tidak tabrakan
nomor. **File migrasi yang sudah di-push tidak pernah diedit** — kalau salah,
buat file migrasi koreksi baru.

## Preview UI

`docs/preview.html` — prototipe klik-able dengan data dummy yang sudah
divalidasi ke HR. Buka langsung di browser.
