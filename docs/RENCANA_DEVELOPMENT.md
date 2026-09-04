# Rencana Development — E-Recruitment RPG
**Tim:** Kiki + Kahfi · **Tanggal:** 3 September 2026
**Acuan:** `ERD_Terkoreksi_E-Recruitment_RPG.md` v1.2 · feedback HR (Mas Fachri) · `Formulir Data Pelamar RPG` (Google Forms)

> Checklist ini di-commit ke repo. Centang `[x]` saat selesai, push, biar dua-duanya lihat progres yang sama.

---

## 0. Ringkasan keputusan dari feedback HR

| # | Feedback | Keputusan | Dampak DB |
|---|---|---|---|
| 1 | Posisi open bisa ditambah HR sendiri | CRUD `M_POSISI` di Master Data, soft-delete | Tidak ada perubahan skema — sudah ada di ERD |
| 2 | Intake lewat link form (mirip GForm) | Link publik per lowongan + link personal bertoken | Perluasan `CANDIDATES`, tabel baru `APPLICATION_PROFILE` & `CANDIDATE_HEALTH` |
| 3 | Pipeline vertikal (per baris, scroll ke bawah) | Murni UI | Tidak ada — tapi query pipeline jadi 1 SP, bukan 1 per tahap |
| 4 | Workflow & dropdown fleksibel, bisa diedit admin | Template flow + snapshot per lamaran + soft-delete | `versi` di `M_FLOW`, `flow_versi` di `APPLICATIONS`, `is_sistem`/`is_aktif` di `M_STAGE` |
| 5 | Filter dashboard | Dua sumbu: fleksibel (stage) untuk operasional, tetap (tipe_tahap + status_global) untuk report | Tabel agregat `RPT_FUNNEL_HARIAN` |

---

## 1. Prinsip yang menjaga "fleksibel" tidak jadi "berantakan"

Ini aturan main yang harus dipegang dua-duanya. Kalau dilanggar, modul Report yang pertama mati.

**1.1 — Yang boleh fleksibel vs yang harus tetap**

| Boleh diubah HR lewat UI | Wajib tetap (kode/skema) |
|---|---|
| Daftar & urutan tahap dalam satu flow | Daftar `status_global` (9 nilai, terkunci) |
| Tahap wajib atau opsional | `tipe_tahap` (SCREENING/KONTAK/FORM/TEST/INTERVIEW/OFFER/ONBOARD) |
| SLA & PIC per tahap | Struktur entitas inti |
| Batas upaya kontak per flow | Aturan dedupe kandidat |
| Isi dropdown alasan (`M_REMARKS`) + efek statusnya | Transaksi, penguncian baris, audit trail |
| Daftar posisi, departemen, outlet, dokumen | Hak akses |

**1.2 — Soft delete wajib, hard delete haram.**
Kalau HR hapus remark "Ekspektasi gaji tidak sesuai", 40 lamaran lama yang memakainya jadi yatim dan history-nya rusak. Semua master pakai `is_aktif = 0`: baris lama tetap bisa dibaca, dropdown baru tidak menampilkannya. Berlaku untuk `M_STAGE`, `M_REMARKS`, `M_POSISI`, `M_DOKUMEN`, `M_FLOW`.

**1.3 — Flow di-snapshot per lamaran.**
Saat lamaran dibuat, seluruh `APPLICATION_STAGES` di-generate dari template. Perubahan `M_FLOW` sesudahnya **tidak** menyentuh lamaran yang sedang berjalan. Tanpa ini, HR menambah satu tahap dan 200 lamaran aktif berubah alurnya diam-diam.

**1.4 — Tahap beda per orang = `is_sisipan`.**
HR boleh menyisipkan tahap ad-hoc ke satu lamaran tanpa mengubah template. Saat menyisip, `urutan` seluruh baris lamaran itu di-renumber dalam satu transaksi.

**1.5 — JANGAN bikin field dinamis (EAV).**
Godaan berikutnya: "field per tahap juga bisa ditambah HR". Jangan. SQL Server 2008 R2 tidak punya tipe JSON, jadi ujungnya kolom teks yang tidak bisa di-query, dan report mati. Yang fleksibel cukup **struktur alur + daftar pilihan**. Yang mau dihitung (skor, tanggal, status, gaji) harus kolom bertipe jelas.

---

## 2. Jawaban: dashboard & filter kalau tiap flow beda tahapnya

Masalahnya nyata — kalau HQ Manager punya 10 tahap dan MP Outlet 2 tahap, "funnel" tidak bisa dihitung dari nama tahap.

**Solusinya dua sumbu:**

- **Sumbu fleksibel — `id_stage`.** Dipakai papan pipeline: "siapa ada di mana". Berubah-ubah sesuai template.
- **Sumbu tetap — `M_STAGE.tipe_tahap` + `status_global`.** Dipakai semua report. Flow apa pun tetap punya tahap bertipe KONTAK dan ONBOARD, jadi selalu bisa dibandingkan.

Setiap tahap baru yang dibuat HR **wajib memilih `tipe_tahap`** dari 7 nilai tetap. Itu satu-satunya syarat supaya tahap buatan sendiri tetap masuk hitungan report.

**Filter dashboard yang disiapkan:** Periode · Departemen · Posisi · Outlet · Flow · Tipe tahap · Status global · Channel · PIC.

**Performa:** join berlapis (`APPLICATIONS` → `APPLICATION_STAGES` → `M_FLOW_STAGE` → `M_STAGE`) akan berat di 2008 R2 begitu data lewat ±5.000 lamaran. Dashboard baca dari tabel agregat `RPT_FUNNEL_HARIAN` yang diisi job terjadwal (Windows Task Scheduler), bukan hitung langsung.

---

## 3. Perubahan skema dari feedback ini

Semua ditulis sebagai migrasi baru, bukan edit file lama.

```sql
-- === 3.1 Form Pelamar (22 field: 21 dari Google Forms + Email) ===
-- CANDIDATES.email SUDAH ADA di ERD v1.2 -> tidak perlu ALTER, cukup
-- tambahkan field Email (WAJIB) di form publik. Dedupe: no_wa_normal -> email -> hash CV.

-- Data milik ORANG (jarang berubah antar lamaran)
ALTER TABLE CANDIDATES ADD
  tempat_lahir         VARCHAR(100)  NULL,
  jenis_kelamin        CHAR(1)       NULL,   -- 'L' / 'P'  (disimpan, TIDAK jadi filter)
  nama_sekolah         VARCHAR(150)  NULL,
  jurusan              VARCHAR(100)  NULL,
  alamat_lengkap       VARCHAR(500)  NULL,
  status_pernikahan    VARCHAR(20)   NULL,   -- Belum_Menikah / Menikah / Cerai
  kontak_darurat_nama  VARCHAR(150)  NULL,
  kontak_darurat_telp  VARCHAR(30)   NULL,
  kontak_darurat_hub   VARCHAR(30)   NULL;   -- Ayah/Ibu/Kakak_Adik/Sepupu/Paman_Bibi

-- Data milik LAMARAN (bisa beda tiap kali orang yang sama melamar lagi)
CREATE TABLE APPLICATION_PROFILE (
  id_lamaran           INT           NOT NULL PRIMARY KEY,
  perusahaan_terakhir  VARCHAR(150)  NULL,
  jabatan_terakhir     VARCHAR(150)  NULL,
  periode_kerja        VARCHAR(100)  NULL,
  gaji_terakhir        DECIMAL(18,2) NULL,   -- akses: HR Admin ke atas (LIHAT_GAJI_PELAMAR)
  gaji_diharapkan      DECIMAL(18,2) NULL,   -- akses: HR Admin ke atas
  diisi_pada           DATETIME      NULL,
  CONSTRAINT FK_APROF_APP FOREIGN KEY (id_lamaran) REFERENCES APPLICATIONS(id_lamaran)
);

-- Data kesehatan = data pribadi SPESIFIK (UU PDP 27/2022 Pasal 4 ayat 2). HR pakai.
-- Consent TERPISAH dari consent umum. Akses paling ketat: HR Spv saja, dicatat di log.
-- Dipisah ke tabel sendiri supaya SELECT di layar biasa tidak pernah ikut membawanya.
CREATE TABLE CANDIDATE_HEALTH (
  id_kandidat          INT           NOT NULL PRIMARY KEY,
  riwayat_penyakit     VARCHAR(500)  NULL,
  consent_khusus       BIT           NOT NULL DEFAULT 0,
  consent_pada         DATETIME      NULL,
  CONSTRAINT FK_CHEALTH_CAND FOREIGN KEY (id_kandidat) REFERENCES CANDIDATES(id_kandidat)
);

-- === 3.2 Link form publik: 1 posisi = 1 link ===
-- url_slug SUDAH ADA di JOB_POSTINGS (ERD v1.2). Rantainya:
--   /lamar/<slug>  ->  JOB_POSTINGS.url_slug  ->  id_posting  ->  id_req  ->  id_posisi
-- Jadi link SUDAH menentukan posisi. Field "Posisi Yang Dilamar" di GForm
-- TIDAK jadi dropdown -- ditampilkan read-only ("Anda melamar: <posisi>").
ALTER TABLE JOB_POSTINGS ADD
  form_aktif    BIT      NOT NULL DEFAULT 1,
  form_dibuka   DATETIME NULL,
  form_ditutup  DATETIME NULL,
  jumlah_submit INT      NOT NULL DEFAULT 0;

-- Opsi link UMUM (walk-in / talent pool, posisi belum pasti):
-- 1 slug khusus tanpa id_posting; DI SINI dropdown posisi MUNCUL,
-- diisi dari M_POSISI yang punya requisition berstatus Sourcing.

-- sp_SubmitApplication (via FORM_PUBLIC) mengisi otomatis:
--   id_req, id_posting  <- dari slug
--   id_flow             <- SNAPSHOT dari flow requisition
--   intake_method       <- 'FORM_PUBLIC'
--   id_channel          <- 'Portal Sendiri'
-- lalu sp_GenerateApplicationStages -> kandidat langsung masuk pipeline posisi itu.

-- rem anti-spam untuk link yang disebar bebas
CREATE TABLE FORM_SUBMIT_LOG (
  id_log     INT IDENTITY PRIMARY KEY,
  url_slug   VARCHAR(100) NOT NULL,
  ip         VARCHAR(45)  NOT NULL,
  waktu      DATETIME     NOT NULL
);

-- === 3.3 Workflow fleksibel ===
ALTER TABLE M_FLOW ADD
  versi         INT NOT NULL DEFAULT 1,
  id_flow_induk INT NULL;        -- kalau flow ini hasil "simpan sebagai template baru"

ALTER TABLE APPLICATIONS ADD
  flow_versi    INT NULL;        -- versi template saat lamaran dibuat

ALTER TABLE M_STAGE ADD
  is_sistem     BIT NOT NULL DEFAULT 0,   -- tahap inti: boleh dinonaktifkan, tak boleh dihapus
  is_aktif      BIT NOT NULL DEFAULT 1;

ALTER TABLE M_REMARKS ADD
  urutan        INT NOT NULL DEFAULT 0;   -- urutan tampil di dropdown

-- === 3.4 Report ===
CREATE TABLE RPT_FUNNEL_HARIAN (
  tanggal       DATE        NOT NULL,
  id_req        INT         NOT NULL,
  id_flow       INT         NOT NULL,
  tipe_tahap    VARCHAR(20) NOT NULL,
  status_global VARCHAR(20) NOT NULL,
  jumlah        INT         NOT NULL,
  CONSTRAINT PK_RPT_FUNNEL PRIMARY KEY (tanggal, id_req, tipe_tahap, status_global)
);
```

### Keputusan HR — dijawab Mas Fachri, 3 September 2026

| # | Pertanyaan | Keputusan | Yang dikerjakan |
|---|---|---|---|
| 1 | Form tidak punya Email | **Tambah kolom Email** | Field Email **wajib** di form publik. Kolom `CANDIDATES.email` sudah ada — tak perlu ALTER. Dedupe: WA → email → hash CV. |
| 2 | Simpan data "penyakit berat"? | **Butuh** | Tabel `CANDIDATE_HEALTH`, consent **terpisah** dari consent umum, akses HR Spv saja, tiap akses dicatat di `ACCESS_LOG_SENSITIF`. |
| 3 | Siapa boleh lihat gaji terakhir & harapan | **Admin HR** | Permission `LIHAT_GAJI_PELAMAR` untuk HR Admin ke atas. Beda dari `LIHAT_FINANSIAL` (rekening, HR Spv saja) dan `LIHAT_GAJI` (range gaji & offer, HR Spv + BOD). |
| 4 | Gender & status pernikahan | **Ya, seperti usul** | Disimpan sebagai data. Tidak jadi opsi filter. Tidak pernah tampil di posting/form publik. |
| 5 | Posisi di form ikut MPR yang buka | **Ya — 1 posisi 1 link** | Lihat §3.2 di atas. |

**Detail #5 — jawaban "masuknya otomatis per posisi yang dia daftar?": ya.**

- Tiap requisition yang di-posting punya `JOB_POSTINGS.url_slug` sendiri → satu link `/lamar/<slug>` per posisi. Beda posisi = beda link.
- Slug itu sudah menunjuk ke `id_posting → id_req → id_posisi`, jadi kandidat **tidak perlu memilih posisi** — cukup ditampilkan read-only "Anda melamar untuk: Marketing Manager".
- Saat submit, `sp_SubmitApplication` membuat lamaran dengan `id_req` & `id_posting` dari slug, meng-*snapshot* `id_flow` dari requisition itu, lalu `sp_GenerateApplicationStages` men-generate tahapannya. Kandidat **langsung muncul di pipeline posisi tersebut** tanpa entri manual HR.
- Satu link "umum" tetap disediakan untuk walk-in / talent pool (posisi belum pasti). Di link ini **dropdown posisi muncul**, isinya dari MPR yang berstatus Sourcing.
- Kalau MPR sudah `Terpenuhi` / `Dibatalkan` / lewat `tanggal_tutup` → `form_aktif = 0`, link menampilkan "Lowongan sudah ditutup".

---

## 4. Setup di PC kantor

Betul, kerjakan di PC kantor. Alasannya bukan sekadar praktis: **Fase 0 tidak bisa divalidasi di tempat lain** karena butuh SQL Server 2008 R2 asli.

### 4.1 Yang disiapkan sekali di awal

- [x] Install Claude Code di PC kantor
- [x] Buat repo GitHub **privat**, `git init`, push pertama
- [x] Copy ke `docs/`: `ERD_Terkoreksi_E-Recruitment_RPG.md`, `Formulir Data Pelamar.pdf`, `preview.html`, file ini
- [x] Buat `CLAUDE.md` di root repo (isi di §4.3) — supaya Claude Code langsung paham konteks tiap sesi baru
- [x] Buat `.gitignore`
- [x] Buat **dua database dev terpisah**: `RPG_EREC_DEV_KIKI` dan `RPG_EREC_DEV_KAHFI`

### 4.2 `.gitignore`

```gitignore
application/config/database.php
application/logs/*.php
uploads/
storage/
*.log
.env
```

Yang di-commit: `application/config/database.sample.php` tanpa password.

### 4.3 `CLAUDE.md` untuk repo (salin apa adanya)

```markdown
# E-Recruitment Ratu Pertiwi Group

## Stack (terkunci)
CodeIgniter 3 · PHP 7.4 · SQL Server 2008 R2 · Database-First (logika transaksi di Stored Procedure)

## Batasan T-SQL SQL Server 2008 R2 — JANGAN dipakai
| Tidak ada | Pakai ini |
|---|---|
| `OFFSET ... FETCH NEXT` | `ROW_NUMBER() OVER (...)` di CTE |
| `THROW` | `RAISERROR(@msg, 16, 1)` |
| `TRY_CONVERT` / `TRY_CAST` | `ISDATE()` / `ISNUMERIC()` + `CASE` |
| `CONCAT()` / `IIF()` | `+` dengan `ISNULL()`, `CASE WHEN` |
| tipe & fungsi JSON | normalisasi penuh — jangan simpan JSON di kolom teks |
| `SEQUENCE` | `IDENTITY` |
| `FORMAT()` | `CONVERT()` dengan style code |
| `STRING_SPLIT` | tabel bantu atau XML split |

## Aturan wajib
- Semua query pakai **parameter binding**. Tidak pernah string concat ke SQL.
- Paginasi selalu pola `ROW_NUMBER()` — jangan andalkan `limit()` CI3 driver sqlsrv.
- Setiap perubahan status menulis `APPLICATION_HISTORY` di transaksi yang sama.
- Master data pakai **soft delete** (`is_aktif = 0`). Tidak pernah `DELETE`.
- Flow di-snapshot ke `APPLICATION_STAGES` saat lamaran dibuat.
- Batas upaya kontak dibaca dari `M_FLOW.maks_upaya_kontak`, bukan angka tetap di SP.
- File fisik disimpan di luar webroot; DB hanya simpan path, hash, metadata.

## Migrasi
File di `database/migrations/`, format `YYYYMMDD_HHMM__nama.sql`.
**File yang sudah di-push tidak pernah diedit** — buat file baru.
Setiap skrip mencatat dirinya ke `SCHEMA_MIGRATIONS` di akhir eksekusi.

## JANGAN
- Jangan pernah menyentuh database produksi. Dev saja.
- Jangan install ODBC Driver 18 (tidak mendukung SQL Server 2008 R2). Pakai 17.4+.
- Jangan bikin field dinamis / EAV.
```

---

## 5. Alur Git untuk dua orang

```
main                 ← selalu jalan, tidak pernah di-push langsung
 ├── feat/master-requisition   (Kahfi)
 └── feat/intake-form          (Kiki)
```

**Rutinitas harian**

```bash
git pull --rebase origin main      # sebelum mulai
# ... kerja ...
git add -A && git commit -m "feat(mpr): form pengajuan + validasi"
git pull --rebase origin main      # sebelum push
git push origin feat/master-requisition
```

Merge ke `main` lewat Pull Request, minimal dilihat satu sama lain. Jangan `--force` push.

**Aturan yang mencegah konflik paling sering:**

1. **Nama file migrasi pakai timestamp, bukan nomor urut.** `20260903_1430__create_master.sql`, bukan `V001__...`. Kalau dua orang bikin migrasi di hari yang sama, tidak tabrakan nomor.
2. **Migrasi yang sudah di-push tidak pernah diedit.** Salah? Buat migrasi koreksi baru.
3. **Satu modul = satu orang.** Jangan dua-duanya menyentuh controller yang sama di minggu yang sama.
4. **File yang sering bentrok** (`routes.php`, layout, CSS) diubah di commit kecil sendiri dan langsung di-merge, jangan menumpuk di branch panjang.

---

## 6. Fase & pembagian kerja

Prinsip pembagian: **per modul vertikal** (satu orang pegang DB + SP + controller + view untuk satu modul), bukan per lapisan. Kalau dibagi per lapisan (satu orang DB, satu orang PHP), yang PHP menunggu terus.

Pembagian di bawah bisa ditukar sesuai preferensi — yang penting jangan dua orang di satu modul.

---

### FASE 0 — Validasi Asumsi · 2–3 hari · **BARENG**
> Gerbang: **tidak boleh menulis Stored Procedure sebelum tes koneksi hijau.**

- [x] Install PHP 7.4 (TS/NTS sesuai Apache) di mesin target
- [x] Install `php_sqlsrv_74_*.dll` + `php_pdo_sqlsrv_74_*.dll` versi **5.9**
- [x] Install **Microsoft ODBC Driver 17 for SQL Server (17.4+)** — bukan 18
- [x] `test-koneksi.php`: `SELECT @@VERSION` + `sqlsrv_client_info()` + `EXEC` SP dummy dengan parameter binding
- [ ] Jalankan di **mesin produksi**, bukan cuma Laragon
- [x] Putuskan runtime produksi (Windows+IIS / Windows+Apache / Linux+Nginx) dan siapa yang mengelola _(**Windows**; IIS vs Apache dipastikan saat Fase 5)_
- [x] Repo GitHub privat + `CLAUDE.md` + `.gitignore` + struktur folder
- [x] Dua database dev terpisah
- [ ] Minta HR: sample export pelamar JobStreet & Glints (field apa saja, CV ikut atau tidak)
- [x] Minta HR: konfirmasi 5 pertanyaan di §3 (email, data kesehatan, gaji, gender, sumber posisi)
- [ ] Telusuri 3 rekrutmen nyata yang sudah selesai (1 staff, 1 staff krusial, 1 manager) di atas skema — **di atas kertas**
- [ ] **Checkpoint:** tidak ada kolom baru yang dibutuhkan setelah menelusuri ketiga kasus itu

---

### FASE 1 — Fondasi · ± 1 minggu · **BARENG**
> Harus barengan: semua modul bergantung ke sini. Jangan dipecah.

- [x] `20260908_1000__master_referensi.sql` — `M_ROLES`, `M_PERMISSIONS`, `M_ROLE_PERMISSIONS`, `M_USERS`, `M_DEPARTEMEN`, `M_OUTLET`, `M_POSISI`, `M_FLOW`, `M_STAGE`, `M_FLOW_STAGE`, `M_FLOW_STAGE_DOKUMEN`, `M_DOKUMEN`, `M_REMARKS`, `M_CHANNEL`
- [x] `..__requisition.sql` — `REQUISITIONS`, `REQUISITION_APPROVALS`, `JOB_POSTINGS`, `JOB_POSTING_STATS`
- [x] `..__kandidat_seleksi.sql` — `CANDIDATES`, `APPLICATIONS`, `APPLICATION_STAGES`, `APPLICATION_CONTACTS`, `INTERVIEWS`, `INTERVIEW_PARTICIPANTS`, `OFFERS`, `APPLICATION_HISTORY`, `APPLICATION_PROFILE`, `CANDIDATE_HEALTH` _(dipecah: `..__kandidat_lamaran.sql`)_
- [x] `..__dokumen_import_audit.sql` — `CANDIDATE_DOCUMENTS`, `CANDIDATE_BANK`, `FORM_TOKENS`, `IMPORT_BATCHES`, `IMPORT_BATCH_ROWS`, `IMPORT_TEMPLATES`, `ACCESS_LOG_SENSITIF`, `AUDIT_LOG`, `FORM_SUBMIT_LOG`, `RPT_FUNNEL_HARIAN`
- [x] `..__seed.sql` — 10 stage, 4 flow + flow_stage, remark per tahap, 9 status_global, role & permission, outlet, posisi awal _(organisasi dev dipindah ke `database/seed/dev_organisasi.sql`)_
- [x] `SCHEMA_MIGRATIONS` + skrip runner (PHP CLI sederhana)
- [x] Layout + CSS diambil dari `preview.html` _(diimplementasikan di `layouts/main.php`: font IBM Plex Sans & Archivo, palet warna `:root`, styling tag, modal dialog, sticky navbar)_
- [x] `sp_Login`, `sp_GetUserPermissions`

---

### FASE 2 — Modul inti · ± 2 minggu · **PARALEL**

**KAHFI — Master Data + Requisition**
- [x] CRUD `M_POSISI` (+ departemen, outlet) — soft delete, level posisi, default flow
- [x] `sp_SavePosisi`, `sp_TogglePosisi`
- [x] Form pengajuan MPR + validasi + auto-nomor `MPR/YYYY/MM/NNN`
- [x] `sp_CreateRequisition`, `sp_SubmitToBOD`
- [x] Pencatatan keputusan BOD per putaran + upload lampiran WA
- [x] `sp_RecordApproval` — putaran baru saat diajukan ulang
- [x] Daftar MPR + filter + paginasi `ROW_NUMBER()`
- [x] Job posting + `JOB_POSTING_STATS` (input manual)

**KIKI — Intake & Form Publik**
- [x] Halaman form publik `/lamar/<slug>` — 21 field sesuai GForm + consent + consent kesehatan terpisah
- [x] `sp_SubmitApplication` — dedupe WA ternormalisasi → email → hash CV
- [x] Normalisasi WA: `0812…` / `62 812…` / `+62812…` → `62812…`
- [x] Generator & pengelola link form: aktif/nonaktif, tanggal buka-tutup, hitung submit
- [x] Upload CV ke luar webroot + hash SHA-256 + validasi mime/ukuran
- [x] Rate limit sederhana via `FORM_SUBMIT_LOG`
- [x] Link personal bertoken (`FORM_TOKENS`) untuk lengkapi berkas — kadaluarsa, sekali pakai, bisa dicabut & digenerate ulang
- [x] Entry manual cepat (MP/outlet: nama, WA, outlet, tanggal join)
- [x] Import file portal: preview → dedupe → commit / rollback (`IMPORT_BATCHES`) _(CSV; XLSX ditunda — tanpa Composer)_

---

### FASE 3 — Seleksi & Dashboard · ± 2 minggu · **PARALEL**

**KAHFI — Flow Engine + Pipeline**
- [x] Flow builder: susun tahap, drag urutan, wajib/opsional, SLA, PIC
- [x] "Simpan sebagai template baru" (clone `M_FLOW` + `M_FLOW_STAGE`, isi `id_flow_induk`)
- [x] Naikkan `M_FLOW.versi` setiap kali template diubah
- [x] CRUD `M_STAGE` (dengan `tipe_tahap` wajib) & `M_REMARKS` (+ `efek_status`, urutan, soft delete) _(M_STAGE ✅ `sp_SaveStage`/`sp_ToggleStage` di Flow Builder → `EDIT_FLOW_TEMPLATE`; M_REMARKS ✅ `sp_SaveRemark` + edit)_
- [x] `sp_GenerateApplicationStages` — snapshot flow saat lamaran dibuat
- [x] `sp_AdvanceStage` — baca `efek_status` dari remark, tulis history, atomik
- [x] `sp_InsertAdHocStage` — tahap sisipan + renumber urutan
- [x] `sp_LogContact` — baca `maks_upaya_kontak` dari flow, auto `Unreachable` saat batas tercapai
- [x] Status `Unreachable` reversible saat kandidat merespons
- [x] Halaman pipeline **vertikal**: satu baris per tahap, kartu kandidat mengalir ke kanan, scroll ke bawah
- [x] `sp_GetPipeline @id_req` — satu query untuk semua tahap, di-group di PHP (jangan 1 query per tahap)
- [x] Batas 12 kartu per baris + "lihat semua" kalau kandidat banyak _(horizontal scroll + toggle expand/collapse)_
- [x] Integrasi modul seleksi lanjutan: Wawancara/Interview (`sp_SaveInterview`), Psikotes (`sp_SavePsikotes`), Offering (`sp_SaveOffer`) langsung pada kartu pipeline via modal dialog interaktif

**KIKI — Dashboard, Report, Dokumen**
- [x] Dashboard: kartu metrik, funnel per `tipe_tahap`, aging SLA, pipeline per flow
- [x] Filter: periode, departemen, posisi, outlet, flow, tipe tahap, status global, channel, PIC
- [x] Dua metrik waktu terpisah: `lama_proses` (dari tanggal permintaan) & `hari_menunggu_approval` (diajukan→keputusan BOD)
- [x] `sp_BuildFunnelHarian` + penjadwalan Windows Task Scheduler _(`tools/build-funnel.php`; penjadwalan = langkah ops)_
- [x] Modul dokumen: upload, verifikasi, `M_FLOW_STAGE_DOKUMEN` (dokumen wajib per tahap)
- [x] Export Excel (hormati RBAC — kolom sensitif ikut disaring)

---

### FASE 4 — Keamanan & Kepatuhan · ± 1 minggu · **BARENG** _(dipecah per concern: retensi/audit-lamaran = Kiki, RBAC/access-log = Kahfi)_
- [x] RBAC tiga tingkat: umum / dokumen identitas / finansial — mekanisme + matriks + checklist uji per peran ✅ (`docs/MATRIKS_HAK_AKSES.md`, helper `gate_sensitif`/`require_any`/`require_all`); gap G1 (`Requisitions::approve`), G2 (`range_gaji`), G4b (`scoping USER_DEPT`), G6 (`BUAT_MPR`), G7 (`Flowbuilder::flow_docs`) telah diselesaikan dan teruji
- [x] `ACCESS_LOG_SENSITIF` — buka dokumen IDENTITAS/FINANSIAL ✅ (`gate_sensitif`), export kolom gaji/rekening ✅, view MPR & form offer ber-gate GAJI tercatat ke log
- [x] `AUDIT_LOG` untuk perubahan master & data lamaran — data lamaran ✅ (`sp_AuditLog` + hook retensi di `sp_AdvanceStage`/`sp_LogContact`); master posisi & dokumen ✅ (`sp_SavePosisi`, `sp_TogglePosisi`, `sp_VerifyDocument`); flow, stage, remark & seleksi ✅ (`sp_SaveStage`, `sp_ToggleStage`, `sp_SaveRemark`, `sp_SaveFlow`, `sp_SaveFlowStage`, `sp_CloneFlow`, `sp_SaveInterview`, `sp_SavePsikotes`, `sp_SaveOffer`)
- [x] Consent: versi teks tersimpan, checkbox talent pool terpisah, consent kesehatan terpisah _(sejak Fase 2; `consent_versi`/`consent_pada`/`setuju_talent_pool` + `CANDIDATE_HEALTH.consent_khusus`)_
- [x] `retensi_sampai` otomatis (12 bulan sejak ditolak, 24 bulan jika setuju talent pool) _(`sp_SetRetensi`, dipanggil dari `sp_AdvanceStage` & `sp_LogContact`)_
- [x] Job penghapusan/anonimisasi lewat Task Scheduler _(`sp_AnonimisasiRetensi` + `tools/run-retensi.php`; penjadwalan = langkah ops)_
- [x] Matriks hak akses diuji per peran, satu per satu — probe otomatis 6 peran ✅ (`tools/rbac-probe.sh`, hasil di `docs/MATRIKS_HAK_AKSES.md §3b`); G1, G4, G4b, G5, G6, G7 terverifikasi tuntas

---

### FASE 5 — UAT & Go-Live · ± 1–2 minggu · **BARENG**
- [ ] UAT bersama HR dengan data nyata
- [ ] Migrasi data historis 2026
- [ ] Rekonsiliasi `JOB_POSTING_STATS` manual vs hitungan sistem
- [ ] Rencana backup **database dan direktori file**
- [ ] Rencana rollback
- [ ] Serah terima + dokumentasi operasional

---

## 7. Urutan mulai — 3 hari pertama

| Hari | Kiki | Kahfi |
|---|---|---|
| 1 | Setup repo, `CLAUDE.md`, `.gitignore`, struktur folder | Install PHP 7.4 + sqlsrv 5.9 + ODBC 17.4 |
| 1 | — | `test-koneksi.php` sampai hijau |
| 2 | Kirim 5 pertanyaan §3 ke Mas Fachri, minta sample export portal | Telusuri 3 rekrutmen nyata di atas skema |
| 3 | Tulis migrasi master bareng (pair, satu layar) | Tulis migrasi master bareng |

Setelah hari ke-3 baru pecah branch dan jalan paralel.

---

## 8. Risiko yang paling mungkin menggagalkan jadwal

| Risiko | Tanda awal | Mitigasi |
|---|---|---|
| Driver PHP↔SQL Server tidak jalan | `test-koneksi.php` gagal | Gerbang Fase 0 — jangan lanjut sebelum hijau |
| Runtime produksi belum diputuskan | Tidak ada yang bisa jawab "deploy ke mana" | Putuskan di Fase 0, tulis di `CLAUDE.md` |
| Portal tidak izinkan external apply URL | HR bilang link tidak bisa dipasang | Sudah diantisipasi — jalur `IMPORT_FILE` tetap dibangun |
| Skema berubah di tengah jalan | Muncul kolom baru terus di Fase 3 | Checkpoint Fase 0: telusuri 3 kasus nyata dulu |
| Dua orang bentrok di file sama | Konflik merge berulang | Satu modul satu orang; migrasi pakai timestamp |
| Dashboard lambat | Query > 3 detik di data uji | Tabel agregat sejak Fase 3, jangan ditunda |
