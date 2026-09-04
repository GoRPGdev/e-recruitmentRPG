# Dokumentasi Problem Solving & Catatan Teknis E-Recruitment RPG

Dokumentasi ini mencatat rekaman problem solving, kendala teknis, serta solusi yang diterapkan pada proyek E-Recruitment Ratu Pertiwi Group (RPG). Dokumen ini wajib dibaca dan diupdate setiap kali melakukan troubleshooting dan implementasi tugas untuk mencegah terjadinya error/kesalahan berulang.

---

## 1. Lingkungan & Batasan Platform

| Komponen | Versi / Batasan | Catatan Penting |
|---|---|---|
| **OS Developer** | Windows (PowerShell) | `&&` tidak didukung di PowerShell 5.1; gunakan `;` atau jalankan terpisah. |
| **PHP** | 7.4.33 (CLI/Web) | Kompatibel dengan `sqlsrv` 5.9. |
| **Database Engine** | SQL Server 2008 R2 (10.50) | Tidak ada `OFFSET/FETCH`, `THROW`, `TRY_CONVERT`, `CONCAT`, `STRING_SPLIT`, JSON. Logika transaksi wajib di Stored Procedure. |
| **ODBC Driver** | Microsoft ODBC Driver 17 | **DILARANG** memakai ODBC Driver 18 karena tidak mendukung SQL Server 2008 R2. |

---

## 2. Log Problem Solving & Solusi

### [PS-001] Pembagian & Batasan T-SQL pada Stored Procedure SQL Server 2008 R2
- **Problem:**
  Penerapan pagination atau fungsi bawaan baru (seperti `THROW`, `CONCAT`) akan memicu syntax error di SQL Server 2008 R2.
- **Identifikasi:**
  Driver SQL Server mengembalikan error fatal saat sintaks SQL Server 2012+ dijalankan.
- **Solusi:**
  1. Pengganti `THROW`: Gunakan `RAISERROR(@msg, 16, 1)`.
  2. Pengganti pagination: Gunakan `ROW_NUMBER() OVER (...)` dalam CTE.
  3. Transaksi bersarang di SP: Gunakan pola `DECLARE @outer INT = @@TRANCOUNT; IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SavePoint;`.

---

### [PS-002] CLI Command Chaining di Windows PowerShell
- **Problem:**
  Saat menjalankan multiple command seperti `php -l file1.php && php -l file2.php`, PowerShell menampilkan error:
  `ParserError: The token '&&' is not a valid statement separator in this version.`
- **Identifikasi:**
  PowerShell versi default di Windows (5.1) tidak mengenal token bash `&&`.
- **Solusi:**
  Gunakan separator titik koma `;` (misal: `php -l file1.php; php -l file2.php`) atau eksekusi perintah satu per satu.

---

### [PS-003] Proteksi Integritas Master Stage (`M_STAGE`) & Sumbu Report
- **Problem:**
  Admin HR dapat membuat tahap seleksi baru secara fleksibel. Namun jika HR membuat nama atau tipe tahap sembarangan, agregasi laporan funnel dan dashboard (`RPT_FUNNEL_HARIAN`) akan rusak karena join dan sumbu report membutuhkan kategori tahap yang pasti.
- **Identifikasi:**
  Aturan sistem menetapkan sumbu fleksibel (`id_stage`) untuk papan pipeline, dan sumbu tetap (`tipe_tahap`) untuk modul report.
- **Solusi:**
  1. Pada SP `sp_SaveStage`: Kolom `tipe_tahap` divalidasi ketat hanya boleh salah satu dari 7 nilai tetap:
     `('SCREENING', 'KONTAK', 'FORM', 'TEST', 'INTERVIEW', 'OFFER', 'ONBOARD')`.
  2. Jika tahap adalah tahap bawaan sistem (`is_sistem = 1`), `kode_stage` dan `tipe_tahap` dikunci (read-only) agar tidak diubah sembarangan, sementara nama tahap dan status terminal/aktif tetap boleh disesuaikan.
  3. Soft delete (`sp_ToggleStage`): Data lama tidak pernah di-`DELETE`. Jika dinonaktifkan (`is_aktif = 0`), sistem memvalidasi dan memberi peringatan bila tahap masih dipakai oleh template flow aktif (`M_FLOW_STAGE`).

---

### [PS-004] Tampilan Pipeline Board Kandidat Menumpuk Saat Volume Besar
- **Problem:**
  Pada pipeline vertikal, jika satu lowongan memiliki puluhan pelamar di satu tahap (misalnya 50 kandidat di tahap Screening atau Kontak), merender semua kartu secara vertikal/horizontal tanpa batas akan menyebabkan halaman terlalu panjang, lambat di-scroll, dan membingungkan pengguna.
- **Identifikasi:**
  Item Fase 3 mensyaratkan: *"Batas 12 kartu per baris + lihat semua kalau kandidat banyak"*.
- **Solusi:**
  1. Pada `web/application/views/pipeline/board.php`, deretan kartu dialirkan secara horizontal (`display: flex; overflow-x: auto; gap: 12px;`) per tahap.
  2. Hanya 12 kartu pertama yang langsung dirender secara default (`array_slice($s['cards'], 0, 12)`).
  3. Jika total kandidat > 12, sistem menampilkan kartu ringkasan `+N kandidat lagi` beserta tombol *"Lihat Semua (Total)"*.
  4. Kartu ke-13 dan seterusnya ditempatkan pada container tersembunyi (`display: none`) yang dapat di-toggle buka/tutup secara instan via fungsi JavaScript `toggleStageCards(urut)`.

---

### [PS-005] Kelengkapan CRUD Master Remarks
- **Problem:**
  Sebelumnya `M_REMARKS` sudah memiliki SP `sp_SaveRemark` dan toggle soft-delete, namun antarmuka belum menyediakan mekanisme pengeditan label/kode/urutan untuk remark yang sudah tersimpan tanpa harus memasukkan ulang.
- **Identifikasi:**
  Formulir di `web/application/views/flow/remarks.php` hanya mendukung aksi tambah (insert).
- **Solusi:**
  1. Di `web/application/controllers/Flowbuilder.php`, tangkap parameter `?edit_remark={id}` dan ambil data baris remark yang bersangkutan.
  2. Di view `web/application/views/flow/remarks.php`, sediakan tombol `edit` pada setiap baris tabel, serta ubah judul formulir menjadi *"Edit Remark #ID"* dengan nilai form terisi otomatis dan tombol *"Batal edit / tambah baru"*.

---

### [PS-006] Penanganan Nilai 0 / NULL pada Parameter OUTPUT Stored Procedure T-SQL
- **Problem:**
  Saat memanggil Stored Procedure yang memiliki parameter `@id_x INT = NULL OUTPUT` dari driver PHP `sqlsrv`, jika variabel output diinisialisasi dengan angka 0 (`$id = 0`), kondisi T-SQL `IF @id_x IS NULL` bernilai FALSE. Hal ini menyebabkan SP menganggap nilai 0 sebagai primary key yang sudah ada dan masuk ke blok `UPDATE`, sehingga memicu Foreign Key violation (misalnya `FK_IP_interview` pada tabel `INTERVIEW_PARTICIPANTS`) atau data tidak ditemukan.
- **Identifikasi:**
  Di PHP, binding parameter output sering menggunakan referensi variabel bertipe integer (`$id = 0`), yang dikirimkan ke SQL Server sebagai nilai literal `0`, bukan `NULL`.
- **Solusi:**
  Pada seluruh Stored Procedure yang menangani dual-action INSERT / UPDATE (seperti `sp_SaveInterview`, `sp_SavePsikotes`, `sp_SaveOffer`), ubah pengecekan parameter ID menjadi:
  `IF @id_x IS NULL OR @id_x <= 0`
  Dengan demikian, baik nilai `NULL` maupun integer `<= 0` akan secara konsisten memicu logika `INSERT` dan mengembalikan ID baru via `SCOPE_IDENTITY()`.

---

### [PS-007] Penyesuaian Kolom Nama Snapshot User pada Skema `M_USERS`
- **Problem:**
  Query yang memanggil `u.nama_lengkap` atau `u.role` langsung dari tabel `dbo.M_USERS` menghasilkan error SQL Server:
  `Invalid column name 'nama_lengkap'.`
- **Identifikasi:**
  Berdasarkan migrasi `20260908_1000__master_referensi.sql`, tabel `M_USERS` menyimpan nama pengguna dalam kolom `nama_snapshot NVARCHAR(150)`, sedangkan kode peran berada pada tabel relasi `dbo.M_ROLES` via foreign key `id_role`.
- **Solusi:**
  Pada query join di model (seperti `Requisition_model` untuk pewawancara, pembuat offer, dan evaluator psikotes), gunakan alias eksplisit:
  `SELECT u.id_user, u.nama_snapshot AS nama_lengkap, r.kode_role AS role FROM dbo.M_USERS u JOIN dbo.M_ROLES r ON r.id_role = u.id_role`.

---

### [PS-008] Ketersediaan Guard Method `require_any_permission` pada `Secured_Controller`
- **Problem:**
  Pemanggilan `$this->require_any_permission(['APPROVE', 'KELOLA_REKRUTMEN'])` pada controller menghasilkan HTTP 500 (Fatal error: Call to undefined method `Requisitions::require_any_permission()`).
- **Identifikasi:**
  Fungsi `require_any_permission` awalnya hanya didefinisikan sebagai fungsi helper prosedural di `rbac_helper.php`, sementara kelas basis `Secured_Controller` di `MY_Controller.php` hanya mendefinisikan method instance tunggal `require_permission($kode)`.
- **Solusi:**
  Tambahkan method pembungkus di `Secured_Controller` (`web/application/core/MY_Controller.php`):
  1. `protected function require_any_permission(array $kode_list) { require_any_permission($kode_list); }`
  2. `protected function require_all_permissions(array $kode_list) { require_all_permissions($kode_list); }`
  Dengan demikian, pemanggilan method `$this->require_any_permission(...)` dari dalam controller berjalan mulus dan mengembalikan status HTTP 403 yang sah ketika akses ditolak.

---

### [PS-009] Scoping Data Kandidat & Pipeline Multi-Departemen (G4b)
- **Problem:**
  Berdasarkan keputusan HR (2026-09-04), user dengan peran `USER_DEPT` hanya boleh melihat dan memproses kandidat yang melamar pada lowongan (MPR) di departemen miliknya sendiri. Tanpa scoping yang ketat, user departemen yang memiliki izin `LIHAT_KANDIDAT` dan `LIHAT_CV` berpotensi membuka data kandidat, berkas/dokumen, pipeline seleksi, dan data agregat dashboard dari departemen lain (misal user Marketing melihat pelamar Operasional/Outlet atau Accounting).
- **Identifikasi:**
  Entitas `REQUISITIONS` tidak menyimpan kolom `id_departemen` langsung, melainkan berelasi ke `M_POSISI` yang memiliki `id_departemen`. Skema user telah dilengkapi kolom `M_USERS.id_departemen` (migrasi `20260910_1100`) dan helper `current_user_dept()`. Namun controller dan query model belum menerapkan filter scoping ini.
- **Solusi:**
  1. **Pipeline (`Pipeline.php`):**
     Dibuat method helper privat `_get_req_scoped($id_req)` yang memeriksa apakah `current_user_dept() !== NULL && (int)$req['id_departemen'] !== (int)current_user_dept()`. Jika tidak cocok, request langsung ditolak dengan `show_error(..., 403)`. Helper ini dipanggil di `index()` serta seluruh endpoint POST aksi pipeline (`advance`, `contact`, `insert_stage`, `save_interview`, `save_psikotes`, `save_offer`).
  2. **Daftar Requisition (`requisitions/index.php`):**
     Daftar MPR tetap terbuka untuk seluruh peran sesuai kesepakatan G4, namun tautan aksi `[pipeline]` hanya ditampilkan jika requisition berasal dari departemen pemohon (`$can_view_pipeline`).
  3. **Pengajuan MPR (`Requisitions::create`):**
     Daftar dropdown posisi difilter hanya menampilkan posisi di departemen pemohon (`positions($dept)`), dan validasi POST menolak pemilihan posisi dari departemen lain.
  4. **Verifikasi & Checklist Dokumen (`Documents.php` & `Document_model.php`):**
     - `list_docs()` menambahkan klausa `AND pos.id_departemen = ?` saat `current_user_dept()` terisi.
     - `verify()`, `checklist()`, dan `open()` memvalidasi bahwa dokumen/lamaran yang dibuka berasal dari departemen pengguna.
  5. **Dashboard & Trend Funnel (`Dashboard.php` & `Dashboard_model.php`):**
     - Parameter filter `dept` dikunci ke `current_user_dept()` untuk peran departemen.
     - Opsi filter departemen dan posisi di view dikunci hanya untuk departemen user.
     - Agregasi tren historis `funnel_trend(14, $dept)` difilter via join ke `dbo.M_POSISI pos WHERE pos.id_departemen = ?`.
     - Ekspor Excel kandidat (`candidates_export()`) membatasi baris ke departemen user.

---

### [PS-010] Proteksi Data Sensitif PDP & Finansial pada Layar Detail Kandidat (G3) serta Penyesuaian Skema Kolom
- **Problem:**
  1. Pada penggabungan fitur G3 (layar detail kandidat), terdapat data-data pribadi spesifik kandidat:
     - Riwayat Penyakit (UU PDP No. 27/2022 Pasal 4 ayat 2, kategori data spesifik) hanya boleh dilihat oleh pemegang izin `LIHAT_KESEHATAN` (HR Supervisor) dan wajib dicatat di `ACCESS_LOG_SENSITIF`.
     - Gaji Pelamar (`gaji_terakhir`, `gaji_diharapkan`) dan Penawaran Kerja (`gaji_ditawarkan`) memerlukan izin `LIHAT_GAJI` dan wajib dicatat di `ACCESS_LOG_SENSITIF`.
     - Nomor rekening bank kandidat memerlukan izin `LIHAT_FINANSIAL` dan wajib dicatat di `ACCESS_LOG_SENSITIF`.
     - Kandidat yang melamar pada lowongan departemen lain tidak boleh dibuka oleh `USER_DEPT` (HTTP 403 Scoping G4b).
  2. Terjadi ketidaksesuaian nama kolom pada skema basis data:
     - `APPLICATION_STAGES`: kolom PIC adalah `pic_user` (bukan `diproses_oleh`).
     - `APPLICATION_CONTACTS`: kolom PIC user adalah `oleh_user` (bukan `dilakukan_oleh`).
     - `APPLICATION_HISTORY`: kolom waktu adalah `waktu` (bukan `waktu_event`), dan transisi tahap disimpan sebagai `id_stage_dari` & `id_stage_ke` (bukan `tahap_asal` & `tahap_tujuan`), status tersimpan di `status_dari` & `status_ke`.
     - `CANDIDATES`: kolom retensi adalah `c.retensi_sampai` (bukan di tabel `APPLICATIONS`), dan kolom blacklist adalah `is_blacklist` (bukan `is_blacklisted`).
- **Identifikasi:**
  Pengujian runtime dan verifikasi skema DDL terhadap migrasi SQL (`20260908_1100__kandidat_lamaran.sql` & `20260908_1130__import_dokumen_audit.sql`) mengungkap perbedaan penamaan kolom tersebut saat query dieksekusi.
- **Solusi:**
  1. **Controller `Candidates.php`:**
     - Mengimplementasikan guard `require_permission('LIHAT_KANDIDAT')`.
     - Menerapkan scoping G4b: memverifikasi apakah `current_user_dept() !== NULL && $cand['id_departemen'] != current_user_dept()`. Bila melanggar, memicu `show_error(..., 403)`.
     - Melakukan pengecekan granular menggunakan `can_sensitif('KESEHATAN')`, `can_sensitif('GAJI')`, dan `can_sensitif('FINANSIAL')`.
     - Mencatat audit log sensitif secara otomatis via `log_akses_sensitif($jenis, 'DETAIL_KANDIDAT', $id_lamaran, ...)` yang memanggil SP `sp_LogAksesSensitif` dan tabel `ACCESS_LOG_SENSITIF`.
  2. **Model `Candidate_model.php`:**
     - Menyelaraskan seluruh nama kolom dan relasi join:
       - `APPLICATION_STAGES`: `LEFT JOIN dbo.M_USERS u ON u.id_user = aps.pic_user` dengan alias `u.nama_snapshot AS diproses_oleh_nama`.
       - `APPLICATION_CONTACTS`: `LEFT JOIN dbo.M_USERS u ON u.id_user = ac.oleh_user`.
       - `APPLICATION_HISTORY`: Join ke `dbo.M_STAGE s_dari` dan `s_ke`, serta `dbo.M_REMARKS r` untuk menyajikan kronologi pergerakan tahap yang informatif.
  3. **View `views/candidates/detail.php`:**
     - Layout 2 kolom yang informatif: profil personal, status blacklist, tanggal retensi PDP, riwayat karir/gaji, data kesehatan bertanda proteksi gembok, rekening perbankan, berkas dokumen dengan status verifikasi, rekap wawancara & psikotes, status offering, tahapan flow seleksi, dan jejak aktivitas audit trail.
  4. **Tautan Integrasi:**
     - Nama kandidat pada board pipeline vertikal (`pipeline/board.php`) dan daftar dokumen (`documents/index.php`) kini menjadi tautan aktif menuju `candidates/detail/<id_lamaran>`.

---

### [PS-011] Standarisasi Backend & Logika Transaksi Menggunakan Stored Procedure (Database-First)
- **Problem:**
  Muncul pertanyaan mengenai konsistensi pemanggilan logika backend: apakah semua proses autentikasi (login), mutasi data, dan manajemen pengguna sudah terstandarisasi menggunakan Stored Procedure (SP) di database, dan bagaimana implementasi penghapusan pengguna (user deletion) dijalankan.
- **Identifikasi:**
  1. Arsitektur RPG adalah **Database-First** (CLAUDE.md aturan 3 & 4): Logika bisnis, validasi integritas relasional, snapshot data, dan audit trail diletakkan di T-SQL Stored Procedure, dengan CodeIgniter Model sebagai wrapper tipis.
  2. Autentikasi sistem:
     - Login dijalankan via `dbo.sp_Login` yang mengambil kredensial user aktif (`is_aktif = 1` dan `r.is_aktif = 1`).
     - Hak akses diambil via `dbo.sp_GetUserPermissions`.
     - Validasi password hash tetap dieksekusi di layer PHP (`password_verify`) karena algoritma hashing modern (`PASSWORD_DEFAULT` / Argon2id / bcrypt) tidak didukung secara native oleh SQL Server 2008 R2.
  3. Mutasi pengguna (`dbo.M_USERS`):
     - Sebelumnya `User_model.php` masih menggunakan `INSERT` dan `UPDATE` inline.
     - Operasi penghapusan user wajib mengikuti aturan soft delete (`is_aktif = 0`) dan dilarang menggunakan query fisik `DELETE FROM dbo.M_USERS` karena terdapat 16 tabel relasional yang memiliki Foreign Key ke `M_USERS`.
- **Solusi:**
  1. **Stored Procedure Baru Dibuat & Dideploy:**
     - `database/procedures/sp_SaveUser.sql`: Mengelola penambahan (INSERT) dan pembaruan (UPDATE) user, memvalidasi keunikan username, role aktif, departemen, serta otomatis mencatat ke `dbo.sp_AuditLog`.
     - `database/procedures/sp_DeleteUser.sql`: Menjalankan soft delete (`is_aktif = 0`), memvalidasi bahwa user ada, mencegah penghapusan akun `SUPER_ADMIN` terakhir yang aktif, dan mencatat aksi ke `dbo.sp_AuditLog`.
  2. **Refactoring `User_model.php`:**
     - Menghapus raw query `INSERT`/`UPDATE` untuk manipulasi user.
     - Mengarahkan `create_user()`, `update_user()`, dan `toggle_status()` ke `dbo.sp_SaveUser`.
     - Menambahkan fungsi `delete_user($id_user, $oleh_user)` yang memanggil `dbo.sp_DeleteUser`.
  3. **Controller & Antarmuka UI:**
     - Menambahkan method `delete($id_user)` di `Users.php` dengan proteksi akun sendiri dan verifikasi role.
     - Menambahkan tombol form aksi `Hapus` pada tabel `users/index.php` yang memicu konfirmasi pengguna.
     - Memperbarui `tools/test-comprehensive.php` untuk memverifikasi `sp_SaveUser` dan `sp_DeleteUser` (56 skenario pengujian lulus 100%).

