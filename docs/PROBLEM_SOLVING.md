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

