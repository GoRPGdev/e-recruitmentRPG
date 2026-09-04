# Laporan Progres Pengembangan Sistem E-Recruitment RPG
## Dari Fase 0 (Fondasi) hingga Kondisi Terkini & Rencana Pengembangan Kedepan

---

### Informasi Dokumen
- **Nama Proyek:** E-Recruitment Ratu Pertiwi Group (RPG)
- **Target Platform:** Web Application Internal (Windows Server, CodeIgniter 3, PHP 7.4.33, SQL Server 2008 R2)
- **Arsitektur:** *Database-First Enterprise* (Seluruh transaksi & mutasi data di T-SQL Stored Procedure)
- **Tanggal Laporan:** 4 September 2026
- **Status Sistem Saat Ini:** Berjalan Stabil (56/56 Skenario Test Otomatis Lulus 100%)

---

## 1. Ringkasan Eksekutif (Executive Summary)

Sistem **E-Recruitment Ratu Pertiwi Group (RPG)** dirancang untuk memodernisasi, mempercepat, dan mengamankan seluruh siklus penerimaan karyawan di lingkungan unit bisnis RPG (Headquarter/HQ maupun Store/Outlet). Proyek ini dibangun di atas infrastruktur basis data korporat **SQL Server 2008 R2** dengan pendekatan **Database-First**, di mana seluruh integritas relasional, logika bisnis seleksi, validasi data, dan jejak audit diamankan langsung di level Stored Procedure T-SQL.

Hingga saat ini, pengembangan telah berhasil menyelesaikan seluruh target inti dari **Fase 0 hingga Fase 4**, ditambah serangkaian penyempurnaan krusial:
1. **Validasi Lingkungan & Driver (Fase 0):** Memastikan kestabilan koneksi PHP 7.4 ke SQL Server 2008 R2 via driver `sqlsrv` 5.9 dan ODBC Driver 17.
2. **Fondasi Skema Terstruktur (Fase 1):** 14 migrasi database otomatis mencakup master organisasi, alur seleksi, requisition, pelamar, dokumen, dan audit log.
3. **Manajemen Pengajuan Lowongan / MPR & Posting (Fase 2):** Alur approval BOD multi-putaran, penomoran otomatis `MPR/YYYY/MM/NNN`, dan penerbitan link lamaran per lowongan.
4. **Intake Lamaran Publik & Deduplikasi (Fase 2):** Formulir online 21 field identik Google Forms RPG dengan deduplikasi otomatis (No. WhatsApp normalisasi, Email, dan SHA-256 berkas CV) serta isolasi penyimpanan berkas di luar webroot.
5. **Engine Seleksi & Pipeline Pelamar (Fase 3):** Pelacakan status pelamar, pencatatan wawancara, psikotes, penawaran kerja (offering), kontak WA otomatis, hingga penyisipan tahap ad-hoc.
6. **Dashboard Rekrutmen & Verifikasi Berkas (Fase 3):** Agregasi matriks funnel harian (`RPT_FUNNEL_HARIAN`), indikator aging SLA, verifikasi berkas, dan ekspor data ke Excel.
7. **Keamanan, RBAC, & Kepatuhan UU PDP No. 27/2022 (Fase 4):**
   - Retensi otomatis (12 bulan) dan engine anonimisai data pribadi pelamar yang ditolak.
   - Pencatatan log akses data sensitif (`ACCESS_LOG_SENSITIF`) untuk NIK, data kesehatan, nomor rekening, dan nominal gaji.
   - Scoping ketat multi-departemen (peran `USER_DEPT` hanya dapat mengakses kandidat pada lowongan departemen miliknya).
8. **Standarisasi Backend & Pembersihan Data Terkini:**
   - Seluruh mutasi akun (`sp_SaveUser`, `sp_DeleteUser`) distandarisasikan via Stored Procedure.
   - Penyederhanaan hak akses menjadi 2 peran aktif: `SUPER_ADMIN` (kontrol penuh lintas modul) dan `USER_DEPT` (khusus departemen pemohon).
   - Seluruh akun nonaktif berhasil dibersihkan permanen dari database dengan pengalihan aman relasi foreign key.

Seluruh fitur diuji secara komprehensif melalui suite uji otomatis `tools/test-comprehensive.php` dengan tingkat kelulusan **100% (56 dari 56 skenario lulus)**.

---

## 2. Arsitektur & Prinsip Teknis Utama

Sistem dikembangkan dengan berpegang teguh pada aturan arsitektur yang disepakati:

| Komponen | Spesifikasi / Aturan | Justifikasi Teknis |
|---|---|---|
| **Framework Backend** | CodeIgniter 3 (PHP 7.4.33) | Kompatibilitas penuh dengan ekstensi `sqlsrv` 5.9 pada lingkungan Windows Server. |
| **Database Engine** | Microsoft SQL Server 2008 R2 (10.50) | Basis data relasional enterprise lokal RPG. |
| **Pola Transaksi** | **Database-First (Stored Procedure)** | Menghindari *race condition*, menjamin atomisitas transisi status lamaran, dan memastikan model PHP hanya bertindak sebagai pemanggil tipis. |
| **Batasan T-SQL 2008 R2** | - `RAISERROR` (Bukan `THROW`)<br>- `ROW_NUMBER()` CTE (Bukan `OFFSET/FETCH`)<br>- Operator `+` & `CASE` (Bukan `CONCAT`/`IIF`)<br>- Normalisasi penuh (Tanpa tipe data JSON) | Menghindari *syntax error* fatal pada engine SQL Server 2008 R2. |
| **Integritas Seleksi** | **Snapshot Per Lamaran** | Setiap lamaran mengunci salinan tahapan alurnya (`APPLICATION_STAGES`). Perubahan template flow tidak merusak proses lamaran yang sedang berjalan. |
| **Kepatuhan Privasi** | **UU Pelindungan Data Pribadi No. 27/2022** | Pemisahan consent data kesehatan, pembatasan hak akses berjenjang, audit log buka dokumen, dan pemusnahan/anonimisasi otomatis paska masa retensi. |

---

## 3. Rincian Progres Pengembangan: Dari Awal Hingga Sekarang

```
[FASE 0] Validasi Lingkungan & Driver
   │
[FASE 1] Fondasi Skema, 14 Migrasi T-SQL, & Desain Antarmuka Enterprise
   │
[FASE 2] Modul Requisition (MPR), Posting Lowongan, & Form Pelamar Publik
   │
[FASE 3] Engine Seleksi (Interview, Psikotes, Offering), Pipeline, & Dashboard
   │
[FASE 4] Keamanan Data, RBAC Multi-Dept (G4b), & UU PDP 27/2022
   │
[PENYEMPURNAAN] Standarisasi Stored Procedure, Super Admin, & Purge Inactive Users
```

### 3.1. Fase 0 — Validasi Asumsi & Arsitektur Lingkungan
- **Verifikasi Stack:** Pengecekan driver `php_sqlsrv_74` versi 5.9, Microsoft ODBC Driver 17.4+, dan koneksi ke SQL Server 2008 R2 melalui skrip `tools/test-koneksi.php`.
- **Keputusan Runtime:** Memastikan sistem siap dijalankan pada lingkungan Windows Server menggunakan IIS/Apache dan *Windows Task Scheduler* untuk background cron.
- **Konfirmasi Kebutuhan Bisnis:** Mengakomodasi 5 poin klarifikasi feedback HR (penanganan email unik, consent kesehatan terpisah, masking gaji pelamar, penguncian gender untuk non-filter, dan relasi posisi-link).

### 3.2. Fase 1 — Fondasi Skema Basis Data & Desain Antarmuka
- **Sistem Migrasi Mandiri:** Membangun CLI migration runner (`tools/migrate.php`) berbasis tabel `SCHEMA_MIGRATIONS`.
- **Skema Master & Transaksional Inti:**
  - `M_ROLES`, `M_PERMISSIONS`, `M_ROLE_PERMISSIONS`, `M_USERS`, `M_DEPARTEMEN`, `M_OUTLET`, `M_POSISI`.
  - `M_FLOW`, `M_STAGE`, `M_FLOW_STAGE`, `M_FLOW_STAGE_DOKUMEN`, `M_DOKUMEN`, `M_REMARKS`, `M_CHANNEL`.
  - `REQUISITIONS`, `REQUISITION_APPROVALS`, `JOB_POSTINGS`, `JOB_POSTING_STATS`.
  - `CANDIDATES`, `APPLICATIONS`, `APPLICATION_STAGES`, `APPLICATION_CONTACTS`, `APPLICATION_HISTORY`.
  - `INTERVIEWS`, `INTERVIEW_PARTICIPANTS`, `PSIKOTES_RESULTS`, `OFFERS`.
  - `CANDIDATE_DOCUMENTS`, `CANDIDATE_BANK`, `CANDIDATE_HEALTH`, `ACCESS_LOG_SENSITIF`, `AUDIT_LOG`.
- **Desain UI Korporat Enterprise:** Implementasi layout modern (`views/layouts/main.php`) mengadopsi palet warna profesional RPG, tipografi *IBM Plex Sans* & *Archivo*, responsive cards, komponen tag status, dan modal dialog interaktif.

### 3.3. Fase 2 — Modul Inti (Requisition / MPR & Form Publik Intake)
- **Master Posisi & Pengajuan MPR:**
  - SP `sp_SavePosisi` dan `sp_TogglePosisi` dengan penentuan default flow dan departemen.
  - Alur pengajuan MPR dengan nomor otomatis `MPR/YYYY/MM/NNN` (`sp_CreateRequisition`).
  - Pencatatan keputusan persetujuan BOD per putaran (`sp_RecordApproval`) beserta unggah bukti approval WhatsApp.
- **Penerbitan Lowongan & Form Publik:**
  - Penerbitan lowongan per MPR dengan tautan unik (`/lamar/<slug>`) dan kontrol buka/tutup form.
  - Form pelamar publik 21 field identik Google Forms RPG dengan validasi input komprehensif.
  - SP `sp_SubmitApplication` dengan mekanisme deduplikasi 3 lapis: No. WhatsApp normalisasi (`628...`), Email, dan Hash SHA-256 berkas CV.
  - Penyimpanan berkas CV fisik diamankan di luar folder publik webroot (`storage/lamaran/`).
- **Kanal Intake Tambahan:**
  - *Link Bertoken (`FORM_TOKENS`):* Link sekali pakai berbatas waktu untuk melengkapi berkas pelamar.
  - *Entry Manual Cepat:* Formulir input ringkas untuk pelamar walk-in/outlet (`Manual.php`).
  - *Import File Portal:* Modul impor batch kandidat berbasis file CSV (`Import.php`).

### 3.4. Fase 3 — Engine Seleksi, Pipeline Rekrutmen, & Dashboard Analitik
- **Flow Builder & Snapshot Generator:**
  - Pengelolaan alur seleksi fleksibel (HQ Staff, HQ Manager, HQ Krusial, MP Outlet).
  - SP `sp_GenerateApplicationStages`: Men-generate salinan urutan tahap saat pelamar masuk.
  - SP `sp_InsertAdHocStage`: Menyisipkan tahapan ad-hoc secara dinamis untuk kandidat tertentu tanpa merusak template flow utama.
- **Manajemen Pipeline Rekrutmen:**
  - Papan pemantauan kandidat per lowongan berbasis SP `sp_GetPipeline`.
  - Logika transisi tahap via SP `sp_AdvanceStage` yang membaca efek status dari master remark dan secara atomik mencatat riwayat di `APPLICATION_HISTORY`.
  - SP `sp_LogContact`: Pencatatan kontak WhatsApp/Telepon dengan deteksi otomatis status `Unreachable` saat mencapai batas `maks_upaya_kontak`.
  - Integrasi modal seleksi: Form jadwal & hasil Wawancara (`sp_SaveInterview`), hasil Psikotes (`sp_SavePsikotes`), serta pengajuan Offering Letter (`sp_SaveOffer`).
- **Dashboard & Analitik Rekrutmen:**
  - Kartu metrik ringkasan (Pelamar Aktif, Kebutuhan Posisi, Hired, SLA Warning).
  - SP `sp_BuildFunnelHarian`: Agregasi data funnel seleksi harian ke tabel `RPT_FUNNEL_HARIAN` untuk performa tinggi.
  - Verifikasi berkas pelamar dan fitur ekspor data kandidat ke Excel.

### 3.5. Fase 4 — Keamanan, Hak Akses RBAC, & Kepatuhan UU PDP
- **Penerapan UU Pelindungan Data Pribadi (UU PDP No. 27/2022):**
  - SP `sp_SetRetensi`: Memberikan masa retensi otomatis (12 bulan) saat lamaran berstatus `Rejected`.
  - SP `sp_AnonimisasiRetensi` & CLI Job `tools/run-retensi.php`: Menghapus data identitas pribadi (Nama, NIK, No WA, Email, berkas fisik) setelah masa retensi terlewati, menyisakan data agregat statistik non-personal.
  - Proteksi dan pemisahan consent riwayat penyakit (`CANDIDATE_HEALTH`).
- **Pencatatan Audit Akses Data Sensitif:**
  - Tabel `ACCESS_LOG_SENSITIF` dan SP `sp_LogAksesSensitif` otomatis mencatat pembukaan data identitas (KTP/KK), nomor rekening bank, catatan kesehatan, dan nominal gaji.
- **Scoping Ketat Departemen (G4b):**
  - Isolasi data untuk peran `USER_DEPT`: hanya dapat melihat lowongan, kandidat, pipeline, dan berkas di departemen miliknya sendiri.

### 3.6. Penyempurnaan Terkini (Standardisasi Backend & Manajemen Pengguna)
- **Standardisasi T-SQL Stored Procedure Penuh:**
  - Pembuatan SP `dbo.sp_SaveUser` (Insert & Update akun dengan validasi keunikan username dan audit trail).
  - Pembuatan SP `dbo.sp_DeleteUser` (Soft delete dengan proteksi pencegahan penghapusan akun Super Admin terakhir).
  - Refactoring `User_model.php` menjadi wrapper tipis pemanggil SP.
- **Penyederhanaan Role Sistem:**
  - Menetapkan hanya 2 peran aktif yang beroperasi: `SUPER_ADMIN` (akses menyeluruh tanpa batas) dan `USER_DEPT` (akses operasional terbatas ke departemen pemohon).
- **Pembersihan Permanen Akun Nonaktif:**
  - Migrasi `20260910_1400__purge_inactive_users.sql` mengalihkan seluruh referensi foreign key historis (interview, log audit, stage) ke akun aktif `demo_super_admin`, lalu menghapus fisik seluruh user nonaktif dari tabel `dbo.M_USERS`.

---

## 4. Hasil Evaluasi & Pengujian Otomatis

Seluruh modul dan integritas basis data divalidasi melalui pengujian end-to-end `tools/test-comprehensive.php`.

### Ringkasan Hasil Pengujian (4 September 2026):
- **Total Skenario Pengujian:** 56 Skenario
- **Status Kelulusan:** **56 PASS / 0 FAILED (100% Lulus)**

```
======================================================================
  E-RECRUITMENT RATU PERTIWI GROUP (RPG) — TEST SUITE LENGKAP
======================================================================
  [PASS] Stored Procedures Inti (sp_GenerateStages, sp_AdvanceStage, dll)
  [PASS] Stored Procedure Manajemen User (sp_SaveUser, sp_DeleteUser)
  [PASS] Integritas Snapshot Flow (114 Tahap) & Riwayat Transaksi (62 Baris)
  [PASS] Agregasi Matriks Funnel Harian (sp_BuildFunnelHarian)
  [PASS] Role Aktif Terverifikasi: SUPER_ADMIN & USER_DEPT
  [PASS] Hak Akses Penuh SUPER_ADMIN (13/13 Permission Aktif Tanpa Batas)
  [PASS] Scoping USER_DEPT Terisolasi ke Departemen Pemohon (Marketing)
  [PASS] Kepatuhan UU PDP: Retensi Otomatis & Log Akses Data Sensitif
  [PASS] Seluruh Endpoint Web Controller (HTTP 200 / Redirect Valid)
  [PASS] Komponen UI Enterprise, Form Publik, & Modul Manajemen Pengguna
======================================================================
  HASIL: 100% LULUS TANPA CATATAN BUG / ERROR SISTEM
======================================================================
```

---

## 5. Rencana Pengembangan Kedepan (Next Development Roadmap)

Berdasarkan evaluasi kemudahan operasional dan kebutuhan alur kerja praktis tim HR RPG, berikut adalah 6 agenda utama rencana pengembangan berikutnya:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    AGENDA PENGEMBANGAN TAHAP BERIKUTNYA                     │
├───────────────────┬─────────────────────────────────────────────────────────┤
│ 1. Remark CRUD    │ Manajemen alasan/keterangan penolakan & status via UI   │
│ 2. Dept CRUD      │ Manajemen master departemen organisasi via UI           │
│ 3. Form Publik UI │ Integrasi form pelamar menyatu dalam sidebar internal   │
│ 4. Pipeline Table │ Redesain tampilan pipeline kanban menjadi list tabel    │
│ 5. Hide Import    │ Menyembunyikan menu Import Portal dari navigasi         │
│ 6. Standard Flow  │ Penyederhanaan Flow Builder menjadi 1 alur standar      │
└───────────────────┴─────────────────────────────────────────────────────────┘
```

### 5.1. Menambahkan Modul CRUD Master Remarks (`M_REMARKS`)
- **Tujuan:** Memberikan kemudahan bagi Administrator HR untuk menambah, mengubah, menonaktifkan, dan mengatur urutan alasan/keterangan proses seleksi langsung melalui antarmuka web.
- **Rincian Pekerjaan:**
  1. Membuat Stored Procedure `dbo.sp_SaveRemark` (dukungan Insert/Update) dan `dbo.sp_ToggleRemark` (soft-delete `is_aktif`).
  2. Menyediakan formulir CRUD lengkap pada Controller & View Master Data:
     - Field: Kode Remark, Label Tampilan, Tahap Terkait, Efek Status (`status_global`: `Hired`, `Rejected`, `In_Progress`, dsb.), dan Urutan Tampil.
  3. Memastikan perubahan master remark otomatis terhubung ke dropdown modal aksi pada papan seleksi pelamar.

### 5.2. Menambahkan Modul CRUD Master Departemen (`M_DEPARTEMEN`)
- **Tujuan:** Memfasilitasi pembaruan struktur organisasi (penambahan atau penyesuaian divisi/departemen baru di RPG) secara mandiri tanpa intervensi teknis database.
- **Rincian Pekerjaan:**
  1. Membuat Stored Procedure `dbo.sp_SaveDepartemen` dan `dbo.sp_ToggleDepartemen`.
  2. Membangun halaman antarmuka CRUD Departemen di modul Master Data (Kode Departemen, Nama Departemen, Status Aktif).
  3. Mengintegrasikan daftar departemen aktif secara dinamis ke dropdown pengajuan MPR, manajemen akun pengguna, dan filter analitik dashboard.

### 5.3. Penyatuan Form Publik Pelamar dengan Layout Sidebar
- **Tujuan:** Mengintegrasikan tampilan formulir pelamar publik (`/lamar/<slug>`) agar memiliki opsi tampilan yang menyatu harmonis dengan shell navigasi/sidebar aplikasi utama (sangat berguna untuk kebutuhan pratinjau HR, input langsung oleh resepsionis/HR di kantor, maupun konsistensi antarmuka).
- **Rincian Pekerjaan:**
  1. Menyesuaikan controller `Lamar.php` agar dapat mendeteksi konteks tampilan (mode internal dalam shell aplikasi vs. mode publik eksternal).
  2. Saat diakses dari dalam sesi login atau mode pratinjau, form dirender lengkap dengan sidebar korporat RPG, status user, dan navigasi cepat kembali ke modul Requisition/Pipeline.
  3. Mempertahankan responsivitas formulir 21 field agar tetap nyaman diisi pada perangkat desktop maupun tablet.

### 5.4. Redesain Tampilan Pipeline Menjadi Format Tabel Kebawah (List/Table View)
- **Tujuan:** Mengubah representasi visual kandidat pada pipeline dari bentuk kanban kartu horizontal menjadi **Format Tabel List Kebawah** yang ringkas dan padat informasi.
- **Latar Belakang:** Pada posisi yang memiliki volume pelamar tinggi (puluhan hingga ratusan orang), tampilan tabel vertikal jauh lebih efisien untuk memindai nama, status, nilai tes, SLA hari, dan melakukan aksi massal dibandingkan menggeser deretan kartu.
- **Rincian Pekerjaan:**
  1. Merancang tampilan tabel pada `views/pipeline/board.php`:
     - **Kolom Tabel:** No, Nama Kandidat, Kontak (WA/Email), Tahap Saat Ini, Status Global, Durasi di Tahap (Aging SLA), PIC Pemroses, dan Tombol Aksi Cepat.
  2. Menyediakan fitur filter bar langsung di atas tabel (filter berdasarkan tahap, status, pencarian nama/keyword).
  3. Tombol aksi inline pada setiap baris tabel untuk memunculkan modal: Geser Tahap (`sp_AdvanceStage`), Jadwalkan Wawancara, Input Hasil Psikotes, Kirim Penawaran (Offering), atau Buka Detail Profil.

### 5.5. Menyembunyikan Menu "Import Portal" Sementara Waktu
- **Tujuan:** Merapikan menu navigasi agar fokus pada modul yang saat ini aktif digunakan operasional, serta mencegah kebingungan pengguna selama format integrasi scraper/parser JobStreet & Glints difinalisasi.
- **Rincian Pekerjaan:**
  1. Menyembunyikan item navigasi "Import Portal" pada sidebar navigasi utama (`views/layouts/main.php`).
  2. Mengamankan rute controller `Import.php` sehingga tidak memicu kebingungan bagi pengguna umum, namun tetap siap diaktifkan kembali saat format data portal telah dibakukan.

### 5.6. Penyederhanaan Flow Builder Menjadi 1 Flow Standar Saja
- **Tujuan:** Menyederhanakan kompleksitas pengelolaan alur rekrutmen dari multi-template menjadi **Satu Alur Standar Rekrutmen RPG (Universal Standard Flow)** yang seragam dan mudah dikontrol.
- **Rincian Pekerjaan:**
  1. Menstandarisasikan urutan tahapan rekrutmen baku RPG:
     `Screening CV` ➔ `Kontak Pelamar` ➔ `Form Data Pelamar` ➔ `Tes & Psikotes` ➔ `Wawancara (HR & User)` ➔ `Offering (Penawaran Kerja)` ➔ `Onboarding / Masuk Kerja`.
  2. Mengunci template flow aktif ke 1 flow standar ini sebagai acuan otomatis seluruh pengajuan MPR baru.
  3. Menyesuaikan tampilan Flow Builder agar fokus mengelola konfigurasi SLA, penugasan PIC, dan dokumen wajib pada alur standar tunggal tersebut tanpa membingungkan HR dengan banyak cabang template.

---

## 6. Kesimpulan

Pengembangan sistem **E-Recruitment Ratu Pertiwi Group (RPG)** telah berhasil mencapai fondasi yang kokoh, stabil, dan patuh terhadap standar regulasi privasi (UU PDP 27/2022) serta batasan infrastruktur lokal (SQL Server 2008 R2). Seluruh logika mutasi penting telah terpusat di dalam Stored Procedure T-SQL berkinerja tinggi.

Dengan implementasi 6 butir rencana pengembangan kedepan (CRUD Remark, CRUD Departemen, integrasi sidebar form, tampilan pipeline tabular ke bawah, simplifikasi menu import, dan standardisasi 1 flow utama), sistem akan menjadi semakin praktis, intuitif, dan siap digunakan secara penuh (*production-ready*) oleh tim HR dan User Departemen RPG.
