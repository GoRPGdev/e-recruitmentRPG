# Laporan Progres Pengembangan Sistem E-Recruitment RPG
## Dari Fase 0 (Fondasi) hingga Kondisi Terkini & Rencana Pengembangan Kedepan

---

### Informasi Dokumen
- **Nama Proyek:** E-Recruitment Ratu Pertiwi Group (RPG)
- **Target Platform:** Web Application Internal (Windows Server, CodeIgniter 3, PHP 7.4.33, SQL Server 2008 R2)
- **Arsitektur:** *Database-First Enterprise* (Seluruh transaksi & mutasi data di T-SQL Stored Procedure)
- **Tanggal Laporan:** 14 September 2026
- **Status Sistem Saat Ini:** Berjalan Stabil (31 Migrasi Database T-SQL, Single Standard Flow, Form Pelamar Formal A-I, Alur Persetujuan MPR 2-Putaran HR & BOD, Pipeline Multi-Tahap Ad-Hoc)

---

## 1. Ringkasan Eksekutif (Executive Summary)

Sistem **E-Recruitment Ratu Pertiwi Group (RPG)** dirancang untuk memodernisasi, mempercepat, dan mengamankan seluruh siklus penerimaan karyawan di lingkungan unit bisnis RPG (Headquarter/HQ maupun Store/Outlet). Proyek ini dibangun di atas infrastruktur basis data korporat **SQL Server 2008 R2** dengan pendekatan **Database-First**, di mana seluruh integritas relasional, logika bisnis seleksi, validasi data, dan jejak audit diamankan langsung di level Stored Procedure T-SQL.

Hingga September 2026, pengembangan telah berhasil menyelesaikan seluruh target inti dari **Fase 0 hingga Fase 4**, ditambah seluruh agenda peningkatan lanjutan:
1. **Validasi Lingkungan & Driver (Fase 0):** Memastikan kestabilan koneksi PHP 7.4 ke SQL Server 2008 R2 via driver `sqlsrv` 5.9 dan ODBC Driver 17.
2. **Fondasi Skema Terstruktur (Fase 1):** 31 skrip migrasi database otomatis mencakup master organisasi, alur seleksi, requisition, pelamar, dokumen, kuesioner profil lengkap, dan audit log.
3. **Manajemen Pengajuan Lowongan / MPR 2-Putaran (Fase 2):** Alur review bertingkat Pemohon -> Review HR (analisis beban kerja & budget) -> Review BOD (persetujuan akhir formasi). Dilengkapi mekanisme arahan revisi formal (`catatan_hr` dan `catatan_bod`), banner peringatan visual, serta penomoran otomatis `MPR/YYYY/MM/NNN`.
4. **Intake Lamaran Publik & Deduplikasi (Fase 2):** Formulir online 21 field identik Google Forms RPG dengan deduplikasi otomatis (No. WhatsApp normalisasi, Email, dan SHA-256 berkas CV) serta isolasi penyimpanan berkas di luar webroot.
5. **Formulir Data Pelamar Formal A-I & Onboarding Portal:** Digitalisasi menyeluruh formulir formal pelamar identik dokumen cetak resmi RPG (Bagian A-I: Biodata, Keluarga, Pendidikan, Pengalaman Kerja, Kuesioner Evaluasi Diri, Riwayat Penyakit, Kontak Darurat, dan Pas Foto). Didukung generator link aman bertoken (`FORM_TOKENS`) dengan integrasi share WhatsApp otomatis (`wa.me`) dan cetak formulir resmi.
6. **Engine Seleksi & Pipeline Pelamar Lanjutan (Fase 3):** 
   - Papan pemantauan pelamar vertikal terstruktur dengan pengelompokan per `id_stage` mandiri dan tombol aksi cepat kebab menu (⋮).
   - Fitur tahap sisipan (ad-hoc) terpadu: mewajibkan pemilihan remark `LANJUT` untuk menyelesaikan tahap saat ini, secara otomatis menyembunyikan tahap yang sudah pernah dilalui, dan memindahkan kandidat langsung aktif (`Berjalan`) di tahap tambahan tersebut.
   - Pencatatan wawancara (`sp_SaveInterview`), psikotes (`sp_SavePsikotes`), penawaran kerja (`sp_SaveOffer`), dan log kontak WA.
7. **Dashboard Rekrutmen Single-Screen Viewport & Matriks Dinamis (Fase 3):**
   - Tampilan pas 1 layar (single-screen) dengan matriks dinamis `Posisi Lowongan x Tahapan Seleksi` berbasis filter tanggal spesifik maupun range tanggal.
   - Filter No. MPR dinamis yang memisahkan kelompok *MPR Aktif / Dibuka* vs *MPR Selesai / Ditutup*.
   - Widget distribusi alasan penolakan/keputusan remark pelamar serta tab terpadu untuk metrik SLA pemenuhan lowongan, approval BOD, dan tren 14 hari.
8. **Keamanan, RBAC, & Kepatuhan UU PDP No. 27/2022 (Fase 4):**
   - Retensi otomatis (12 bulan) dan engine anonimisasi data pribadi pelamar yang ditolak.
   - Pencatatan log akses data sensitif (`ACCESS_LOG_SENSITIF`) untuk NIK, data kesehatan, nomor rekening, dan nominal gaji.
   - Scoping ketat multi-departemen (`USER_DEPT` hanya dapat mengakses lowongan dan kandidat pada departemen miliknya).
9. **Standarisasi Alur Tunggal & Hak Akses Bersih:**
   - Standardisasi ke 1 Alur Tunggal Resmi Rekrutmen RPG (`STD_RPG`) dengan 3 efek status baku (`LANJUT`, `HIRED`, `TOLAK`).
   - Penyederhanaan hak akses menjadi 2 peran aktif: `SUPER_ADMIN` (akses penuh semua modul) dan `USER_DEPT` (khusus departemen pemohon).
   - Seluruh mutasi data diproteksi via Stored Procedure T-SQL dengan penanganan multi-statement `sqlsrv_next_result`.
   - Portal login bersih dan mandiri (tombol quick-fill pengujian dihilangkan).

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

### 3.6. Penyempurnaan Backend & Manajemen Pengguna
- **Standardisasi T-SQL Stored Procedure Penuh:**
  - Pembuatan SP `dbo.sp_SaveUser` (Insert & Update akun dengan validasi keunikan username dan audit trail).
  - Pembuatan SP `dbo.sp_DeleteUser` (Soft delete dengan proteksi pencegahan penghapusan akun Super Admin terakhir).
  - Refactoring `User_model.php` menjadi wrapper tipis pemanggil SP.
- **Penyederhanaan Role Sistem:**
  - Menetapkan 2 peran aktif yang beroperasi: `SUPER_ADMIN` (akses menyeluruh tanpa batas) dan `USER_DEPT` (akses operasional terbatas ke departemen pemohon).
- **Pembersihan Permanen Akun Nonaktif:**
  - Migrasi `20260910_1400__purge_inactive_users.sql` mengalihkan seluruh referensi foreign key historis (interview, log audit, stage) ke akun aktif `demo_super_admin`, lalu menghapus fisik seluruh user nonaktif dari tabel `dbo.M_USERS`.

### 3.7. Alur Pengajuan MPR 2-Putaran Evaluasi (Review HR & Review BOD)
- **Tingkat Persetujuan Formal Berjenjang:**
  - Pemohon (User Dept / HR) membuat permohonan formasi karyawan (status `Draft`).
  - Pemohon mengajukan dokumen ke HR (status `Review_HR`).
  - Tim HR melakukan analisis beban kerja, justifikasi formasi, dan alokasi budget:
    - **Minta Revisi HR** (`status_req = 'Revisi_HR'`): Mengisi `catatan_hr` yang muncul sebagai banner arahan revisi di detail dan form edit MPR pemohon.
    - **Tolak HR** (`status_req = 'Ditolak_HR'`): Permohonan ditolak oleh HR dengan alasan terdokumentasi.
    - **Teruskan ke BOD** (`status_req = 'Review_BOD'`): HR memvalidasi dan menyerahkan dokumen ke BOD.
  - BOD melakukan evaluasi strategis formasi dan budget:
    - **Minta Revisi BOD** (`status_req = 'Revisi_BOD'`): Mengisi `catatan_bod` yang tampil sebagai banner revisi arahan BOD.
    - **Tolak BOD** (`status_req = 'Ditolak_BOD'`): Penolakan resmi dari BOD.
    - **Setujui (Approved)** (`status_req = 'Approved'`): Permohonan resmi disetujui, kuota lowongan terkunci, dan form lowongan siap diterbitkan.
- **Standardisasi Terminologi BOD:**
  - Seluruh label status dan tampilan yang memuat kata "Direksi" distandarisasikan menjadi **BOD** (*Review BOD*, *Revisi dari BOD*, *Ditolak BOD*) agar selaras dengan nomenklatur korporat RPG.

### 3.8. Formulir Data Pelamar Formal A-I & Generator Tautan Onboarding WhatsApp
- **Digitalisasi Formulir Resmi RPG (Bagian A-I):**
  - Implementasi formulir onboarding formal (`onboarding/form`) yang persis identik dengan dokumen PDF resmi *Formulir Data Pelamar RPG*:
    - Bagian A: Data Pribadi & Kontak Lengkap.
    - Bagian B: Susunan Keluarga (Orang Tua, Saudara Kandung, Pasangan, dan Anak).
    - Bagian C: Riwayat Pendidikan Formal & Kursus/Pelatihan.
    - Bagian D: Pengalaman Kerja Terinci (Gaji, Alasan Berhenti, Uraian Tugas).
    - Bagian E: Kuesioner Evaluasi Diri (Kekuatan, Kelemahan, Target Karir, Budaya Kerja).
    - Bagian F: Minat dan Konsep Pribadi.
    - Bagian G: Informasi Lainnya (Relasi di RPG, Kendaraan, SIM).
    - Bagian H: Riwayat Kesehatan & Rawat Inap (dengan consent terpisah UU PDP).
    - Bagian I: Kontak Darurat dan Upload Berkas (Pas Foto Resmi, KTP, KK, Ijazah, NPWP, Surat Referensi).
- **Generator Tautan Ber-token & Integrasi WhatsApp:**
  - Pada papan seleksi kandidat (pipeline), disediakan tombol menu aksi titik tiga (⋮) untuk memunculkan modal popup pembuatan tautan form pelamar aman (`FORM_TOKENS`).
  - Dilengkapi tombol salin tautan instan dan tombol buka WhatsApp otomatis (`https://wa.me/...`) yang telah terisi teks undangan pengisian form pelamar formal.
  - Tersedia opsi *Cetak Form Pelamar A-I* untuk menghasilkan lembar dokumen fisik siap pakai bagi interviewer.

### 3.9. Standardisasi Alur Tunggal Resmi RPG (`STD_RPG`) & 3 Efek Status Baku
- **Alur Tunggal Rekrutmen Universal:**
  - Mengeliminasi percabangan multi-template menjadi 1 Alur Tunggal Resmi RPG (`STD_RPG`):
    `Screening CV` ➔ `Kontak Kandidat` ➔ `Interview HR` ➔ `Pengisian Form Pelamar` ➔ `Psikotes` ➔ `Interview User` ➔ `Interview BOD` ➔ `Penawaran & Negosiasi (Offer)` ➔ `Onboarding & Pemberkasan`.
- **Penyederhanaan Efek Status Baku Menjadi 3 Kondisi:**
  - Master remark (`M_REMARKS`) distandarisasikan hanya ke 3 efek status:
    1. **`LANJUT`**: Lulus tahap aktif saat ini, berlanjut ke tahap berikutnya (`In_Progress`).
    2. **`HIRED`**: Lulus tahap akhir, diterima bekerja (`Hired`) dan otomatis memotong sisa kuota MPR.
    3. **`TOLAK`**: Gugur / ditolak seleksi (`Rejected`), otomatis masuk antrean masa retensi UU PDP.

### 3.10. Redesain Dashboard Single-Screen & Matriks Dinamis Posisi x Tahap
- **Tata Letak Pas 1 Layar Penuh (Single-Screen Viewport):**
  - Mengatur container dashboard tanpa scrollbar ganda, membagi area kerja menjadi filter bar ringkas, grid KPI status lamaran horizontal, serta panel kerja 2 kolom.
- **Matriks Funnel Dinamis Posisi x Tahapan:**
  - Menggantikan tabel statis lama dengan matriks dinamis yang menghubungkan posisi lowongan yang dibuka (vertikal) dengan proses tahapan seleksi aktif (horizontal).
  - Posisi maupun tahapan yang tidak memiliki aktivitas kandidat pada rentang tanggal terpilih otomatis dieliminasi dari tampilan.
  - Mendukung filter tanggal tunggal ("sesuai tanggal saja") maupun rentang tanggal ("range tanggal dari-sampai").
- **Filter No. MPR Dinamis:**
  - Menyediakan filter No. MPR terkelompok rapi: *MPR Aktif / Dibuka* (`Sourcing`, `Approved`, `Sourcing_Ulang`) vs *MPR Selesai / Ditutup* (`Terpenuhi`, `Ditolak_HR`, `Ditolak_BOD`, `Dibatalkan`, `Kadaluarsa`).
  - Terintegrasi langsung ke penghitungan KPI, matriks funnel, widget distribusi remark kelulusan/penolakan, dan ekspor spreadsheet kandidat.

### 3.11. Penyempurnaan Tahap Sisipan (Ad-Hoc Stage) Auto-Lanjut
- **Wajib Remark Lanjut:** Form modal sisip tahap kini mewajibkan pemilihan remark berstatus `LANJUT` untuk menyelesaikan tahap aktif kandidat saat ini (opsi tolak disembunyikan otomatis).
- **Auto-Lanjut Langsung:** Kandidat yang disisipi tahap otomatis langsung berpindah dan aktif (`status_tahap = 'Berjalan'`, `is_sisipan = 1`) di tahap tambahan tersebut dalam satu aksi tunggal tanpa perlu memproses manual lagi dari tahap sebelumnya.
- **Penyaringan Tahap Duplikat:** Tahap-tahap yang sudah pernah dijalani atau dimiliki kandidat (seperti *tes koding*) secara otomatis dihilangkan dari dropdown pilihan tahap tambahan, didukung validasi ganda di level controller dan stored procedure.
- **Visualisasi Mandiri:** Setiap tahap tambahan ditampilkan di section mandiri pada papan pipeline lengkap dengan lencana khusus *"Tahap Tambahan"*.

---

## 4. Hasil Evaluasi, Migrasi, & Pengujian Otomatis

Seluruh modul dan integritas basis data dikelola melalui CLI runner `tools/migrate.php` dan suite pengujian.

### Status Komponen Basis Data Saat Ini (September 2026):
- **Total File Migrasi Database:** **31 File Migrasi T-SQL** (seluruhnya berstatus `[ OK ]` tercatat di `dbo.SCHEMA_MIGRATIONS`).
- **Total Stored Procedure T-SQL:** **46 Stored Procedure & Function** ter-deploy penuh dan idempotent.
- **Integritas Multi-Statement Driver:** Seluruh stored procedure multi-statement telah dilengkapi penanganan `sqlsrv_next_result` untuk menjamin atomisitas transaksi commit.

---

## 5. Rencana Pemeliharaan & Operasional Produksi (Fase 5 Roadmap)

Seluruh 6 agenda peningkatan alur kerja HR yang direncanakan pada laporan sebelumnya telah **100% selesai diimplementasikan**. Fokus pengembangan berikutnya beralih ke persiapan kesiapan rilis produksi (Fase 5):

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       AGENDA PERSIAPAN RILIS FASE 5                         │
├───────────────────┬─────────────────────────────────────────────────────────┤
│ 1. Windows Task   │ Pemasangan Windows Task Scheduler untuk job retensi &   │
│    Scheduler      │ rekap funnel harian (build-funnel.php & run-retensi.php)│
│ 2. Web Server IIS │ Konfigurasi rewrite rule dan virtual host IIS / Apache  │
│ 3. Backup Rutin   │ Otomatisasi backup berkala basis data SQL Server 2008 R2│
│ 4. Storage Folder │ Pemastian izin read/write folder storage di luar webroot│
└───────────────────┴─────────────────────────────────────────────────────────┘
```

---

## 6. Kesimpulan

Sistem **E-Recruitment Ratu Pertiwi Group (RPG)** telah berevolusi menjadi platform rekrutmen yang lengkap, modern, dan kokoh. Sistem telah berhasil mengintegrasikan seluruh siklus seleksi: mulai dari pengajuan dan peninjauan MPR 2-putaran (HR & BOD), intake publik terdeduplikasi, formulir pelamar formal A-I identik standar cetak RPG, papan pipeline seleksi vertikal dengan fitur sisip tahap auto-lanjut, hingga dashboard analitik matriks dinamis berbasis role. Seluruh mutasi data tetap terjaga aman dan patuh pada aturan ketat *Database-First Enterprise* serta regulasi privasi UU PDP No. 27/2022.

Dengan implementasi 6 butir rencana pengembangan kedepan (CRUD Remark, CRUD Departemen, integrasi sidebar form, tampilan pipeline tabular ke bawah, simplifikasi menu import, dan standardisasi 1 flow utama), sistem akan menjadi semakin praktis, intuitif, dan siap digunakan secara penuh (*production-ready*) oleh tim HR dan User Departemen RPG.
