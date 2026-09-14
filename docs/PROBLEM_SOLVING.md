			  3. Skrip ini reusable untuk dokumen dokumentasi markdown lainnya di proyek E-Recruitment RPG.

---

### [PS-014] Implementasi Enam Peningkatan Arsitektur & UI: CRUD Master (Remark & Departemen), Pipeline Downward Table, Penyatuan Form Publik, dan Flow Standar Tunggal
- **Problem:**
  Kebutuhan untuk menyelesaikan enam item peningkatan yang diajukan dalam roadmap pengembangan sistem:
  1. Master Remark Alur membutuhkan kemampuan CRUD lengkap (Insert, Update inline, Soft-delete `is_aktif`).
  2. Master Departemen membutuhkan kemampuan CRUD lengkap terpusat dengan Stored Procedure dan audit logging.
  3. Form Publik perlu terintegrasi harmonis dalam struktur sidebar navigasi ketika diakses pengguna sistem.
  4. Tampilan Pipeline seleksi lamaran membutuhkan mode tabel ke bawah (downward table view) agar pelamar bervolume tinggi dapat dipantau dan diproses secara cepat.
  5. Menu "Import Portal" perlu disembunyikan sementara waktu dari sidebar navigasi.
  6. Flow Builder perlu difokuskan pada 1 Alur Standar Baku Rekrutmen RPG (Universal Standard Flow).
- **Identifikasi:**
  1. Master data `M_DEPARTEMEN` dan `M_REMARKS` wajib mematuhi aturan CLAUDE.md: transaksi mutasi wajib di Stored Procedure T-SQL (kompatibel SQL Server 2008 R2), audit trail via `dbo.sp_AuditLog`, dan soft delete (`is_aktif = 0`).
  2. Pada papan seleksi lamaran (`pipeline/board.php`), representasi kartu kanban horizontal sangat memakan ruang ketika jumlah pelamar puluhan orang. Diperlukan alternatif tabel ke bawah dengan filter dan tombol aksi langsung (geser remark, jadwalkan interview, catat psikotes, buat offering).
  3. Navigasi sidebar perlu menyatukan menu "Form Publik" (`postings` & `lamar`) dan menyembunyikan item "Import Portal".
  4. Flow Builder perlu menyorot alur standar perusahaan (`HQ_STAFF`) sebagai flow acuan baku, menyembunyikan kompleksitas multi-template bagi operasional HR harian.
- **Solusi:**
  1. **Stored Procedures T-SQL:**
     - `dbo.sp_SaveRemark` & `dbo.sp_ToggleRemark`: Menangani insert/update remark alur beserta audit logging.
     - `dbo.sp_SaveDepartemen` & `dbo.sp_ToggleDepartemen`: Menangani validasi keunikan kode departemen, mutasi, dan pencatatan audit.
  2. **Model & Controller:**
     - `Master_model.php`: Menambahkan wrapper `save_departemen()`, `toggle_departemen()`, `save_remark()`, `toggle_remark()`.
     - `Master.php`: Menangani parameter `edit_id` untuk form edit departemen dan remark serta routing aksi save/toggle.
  3. **Tampilan Pipeline ke Bawah (`pipeline/board.php`):**
     - Menambahkan View Switcher cepat di header: "Papan Kartu" dan "Tabel ke Bawah".
     - Menyimpan preferensi tampilan user di browser via `localStorage` (`rpg_pipeline_view_mode`).
     - Render tabel ke bawah lengkap dengan kolom nomor, nama, kontak WA, aging SLA (lama hari di tahap), status global, hasil asesmen, dan dropdown eksekusi alur langsung.
  4. **Penyatuan Menu & Sidebar (`layouts/main.php`):**
     - Menu "Import Portal" disembunyikan secara aman.
     - Menu "Form Publik" disatukan dengan pencocokan segment URI `postings` dan `lamar`.
  5. **Simplifikasi Flow Builder (`flow/index.php`):**
     - Halaman muka menampilkan kartu sorotan khusus "1 Flow Standar Resmi RPG" dengan akses langsung kelola tahapan.
     - Template tambahan dan duplikasi alur dilipat rapi ke dalam opsi accordion arsip.

---

### [PS-015] Konfigurasi Otomatisasi Perizinan Command Claude Code Auto Mode untuk E-Recruitment RPG
- **Problem:**
  Pemberitahuan permission dialog yang berulang kali muncul untuk command dasar CLI/Git/PHP.
- **Solusi:**
  Konfigurasi perizinan diatur sesuai format harness.

---

### [PS-026] Kontrol Status Requisition oleh HR & Toggle Akses Form Lowongan Publik
- **Problem:**
  1. Kebutuhan HR untuk memperbarui status MPR secara mandiri (misal beralih ke `Sourcing_Ulang` saat batch pelamar sebelumnya habis, atau menutup ke `Kadaluarsa`).
  2. Kebutuhan HR untuk menutup atau membuka kembali akses formulir lamaran online publik (`JOB_POSTINGS.form_aktif = 0 / 1`) sewaktu-waktu.
- **Identifikasi:**
  1. Status MPR berada di `dbo.REQUISITIONS.status_req`. Transisi status harus mematuhi integritas bisnis dan tercatat di audit trail.
  2. Form publik diatur oleh flag `dbo.JOB_POSTINGS.form_aktif`. Jika MPR berstatus `Kadaluarsa` atau `Dibatalkan`, form publik harus otomatis tertutup dan tidak boleh dibuka kembali sebelum MPR aktif.
- **Solusi:**
  1. Stored Procedure T-SQL:
     - `dbo.sp_UpdateRequisitionStatus`: Memungkinkan HR mengupdate status MPR ke `Sourcing`, `Sourcing_Ulang`, atau `Kadaluarsa`. Jika diubah ke `Kadaluarsa`, semua posting di bawahnya otomatis di-set `form_aktif = 0`.
     - `dbo.sp_TogglePostingForm`: Mengubah status `form_aktif` pada job posting secara aman dengan validasi status MPR induk.
  2. Model:
     - `Requisition_model.php`: menambahkan `update_status()` dan `toggle_posting_form()`.
     - `Posting_model.php`: menambahkan `toggle_form()`.
  3. Controller:
     - `Requisitions.php`: menambahkan aksi `update_status($id_req)` dan `toggle_posting($id_req, $id_posting)`.
     - `Postings.php`: menambahkan aksi `toggle($id_posting)`.
  4. Penyempurnaan Antarmuka:
     - Di `requisitions/view.php`: menyematkan dropdown "Ubah Status MPR" untuk HR, tabel daftar lowongan publik per MPR lengkap dengan status form, tombol toggle 1-klik `[Buka Form] / [Tutup Form]`, serta fitur salin tautan publik.
     - Di `postings/index.php`: menyematkan tombol 1-klik `Buka` / `Tutup` pada kolom aksi tabel daftar form publik.
  5. Pengujian terotomasi:
     - Menulis skrip uji CLI `tools/test-mpr-status-toggle.php` untuk memvalidasi transisi status MPR dan buka-tutup form posting.
- **Status:** Resolved & Documented.

---

### [PS-027] Isolasi Tampilan Publik & Penyempurnaan Detail Lowongan Kerja di Form Publik
- **Problem:**
  1. **Kebocoran Sidebar Admin di Form Publik:** Ketika pengguna yang memiliki sesi login internal (HR/Admin) membuka atau mengetes tautan formulir lamaran online publik (`/lamar/<slug>`), sistem sebelumnya menggunakan layout shell internal yang memunculkan sidebar navigasi admin lengkap dengan menu-menunya.
  2. **Kurangnya Informasi Pekerjaan di Halaman Publik:** Pada form publik sebelumnya, pelamar hanya melihat judul posisi dan formulir data diri tanpa adanya penjelasan lengkap mengenai deskripsi pekerjaan (*job description*), persyaratan/kualifikasi pelamar, kriteria pendidikan minimal, pengalaman minimal, serta lokasi penempatan yang spesifik.
  3. **Tampilan Sukses & Tertutup Kurang Informatif:** Halaman pemberitahuan lamaran sukses dan penutupan lowongan masih menggunakan tampilan dasar tanpa identitas resmi brand RPG.
- **Identifikasi:**
  1. **Layout Shell Route Isolation (`layouts/main.php`):**
     - Variabel `$seg1 = $this->uri->segment(1);` diperiksa terhadap rute publik: `$is_public_route = in_array($seg1, array('lamar', 'auth'));`.
     - Kondisi pembungkus utama diubah menjadi:
       `if ($this->session->userdata('logged_in') && ! $is_public_route):`
       Dengan demikian, rute publik `/lamar/*` dan `/auth/*` dijamin selalu memakai kontainer `.wrap-public` tanpa merender sidebar internal, topbar, maupun kontrol navigasi admin sama sekali.
  2. **Enriched Job Specifications Intake (`Application_model.php`):**
     - Memperluas query `get_posting_by_slug()` untuk menggabungkan data `dbo.JOB_POSTINGS`, `dbo.REQUISITIONS`, `dbo.M_POSISI`, `dbo.M_DEPARTEMEN`, dan `dbo.M_OUTLET`.
     - Mengambil field: `job_desc` (prioritas posting over req via `COALESCE`), `kualifikasi`, `tipe_penempatan`, `status_karyawan`, `pendidikan_minimal`, `pengalaman_minimal_tahun`, `nama_outlet`, dan `kota_outlet`.
  3. **Redesain Antarmuka Form Publik (`lamar/form.php`):**
     - Menyematkan header identitas resmi Karir Ratu Pertiwi Group.
     - Menyematkan Kartu Detail Lowongan Kerja di bagian atas form:
       - Judul posisi, nama departemen, dan tag status "Terbuka".
       - Badges spesifikasi: Lokasi Penempatan (HQ / Outlet & Kota), Ikatan Kerja (PKWT/PKWTT), Pendidikan Minimal, dan Pengalaman Minimal (Tahun atau Fresh Graduate Terbuka).
       - Blok **Deskripsi Pekerjaan (Job Description)** berlatar permukaan kontras dengan `white-space: pre-line`.
       - Blok **Kualifikasi & Persyaratan (Job Requirements)** berlatar permukaan kontras.
     - Menyusun formulir data diri pelamar yang ergonomis, terstruktur dalam 6 section (Data Pribadi, Pendidikan Terakhir, Pengalaman Kerja Terakhir, Kontak Darurat, Unggah Berkas CV, dan Persetujuan Perlindungan Data Pribadi / PDP).
  4. **Pembaruan Halaman Sukses & Lowongan Ditutup (`lamar/sukses.php` & `lamar/tertutup.php`):**
     - Diberikan logo brand RPG, ikon status grafis, nomor registrasi lamaran `#ID`, dan instruksi tindak lanjut yang jelas bagi pelamar.
- **Status:** Resolved & Verified.

---

### [PS-028] Perbaikan Notice Undefined Index created_at pada Daftar Posting di Detail Requisition
- **Problem:**
  Muncul PHP Notice `Severity: Notice Message: Undefined index: created_at Filename: requisitions/view.php Line: 147` saat membuka detail Requisition (`/requisitions/view/<id>`).
- **Identifikasi:**
  Skema tabel `dbo.JOB_POSTINGS` tidak memiliki kolom bernama `created_at`, melainkan `tanggal_posting`. Pada method `Requisition_model::postings_for_req()`, query mengembalikan kolom `tanggal_posting`. Namun di `requisitions/view.php` baris 147, view mencoba mengakses `$p['created_at']`.
- **Solusi:**
  Mengubah pemanggilan field di `web/application/views/requisitions/view.php` menjadi:
  `!empty($p['tanggal_posting']) ? html_escape(substr($p['tanggal_posting'], 0, 10)) : ''`
- **Status:** Resolved & Verified.

---

### [PS-029] Perbaikan Notice Undefined Index id_req pada Daftar Form Publik (Postings Index)
- **Problem:**
  Muncul PHP Notice:
  `Severity: Notice Message: Undefined index: id_req Filename: postings/index.php Line Number: 38`
  sehingga link No. MPR mengarah ke `http://localhost:8080/requisitions/view/0`.
- **Identifikasi:**
  Pada method `Posting_model::list_postings()`, query CTE `q` tidak menyertakan kolom `jp.id_req` dalam klausa `SELECT`. Akibatnya, array `$r` hasil fetch query di view `postings/index.php` baris 38 tidak memiliki key `id_req` (`$r['id_req']` menjadi null/0).
- **Solusi:**
  1. Di `web/application/models/Posting_model.php`: Menambahkan `jp.id_req` ke daftar kolom `SELECT` pada CTE query `list_postings()`.
  2. Di `web/application/views/postings/index.php`: Menambahkan pengecekan defensif `!empty($r['no_mpr']) && !empty($r['id_req'])` sebelum membentuk link `requisitions/view/<id_req>`.
- **Status:** Resolved & Verified.

---

### [PS-030] Perbaikan Invalid Column Name 'kota' pada Query Form Publik Lamar Slug
- **Problem:**
  Saat membuka halaman lowongan publik `/lamar/<slug>`, muncul Database Error:
  `[Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Invalid column name 'kota'.`
  dari query `get_posting_by_slug()` di `Application_model.php`.
- **Identifikasi:**
  Pada tabel referensi `dbo.M_OUTLET` (berdasarkan migrasi `20260908_1000__master_referensi.sql`), kolom wilayah outlet bernama `region`, bukan `kota` (`id_outlet, kode_outlet, nama_outlet, brand, region, is_aktif`). Query sebelumnya mencoba melakukan `SELECT o.kota AS kota_outlet`.
- **Solusi:**
  1. Di `web/application/models/Application_model.php`:
     Mengganti `o.kota AS kota_outlet` menjadi `o.region AS region_outlet`.
  2. Di `web/application/views/lamar/form.php`:
     Menyesuaikan badge penempatan dengan `region_outlet`:
     `!empty($posting['region_outlet']) ? ' (' . html_escape($posting['region_outlet']) . ')' : ''`
  3. Verifikasi via skrip eksekusi query SQL Server langsung: berhasil mengambil data posting aktif (`Visual Merchandising Specialist`) dengan status form terbuka.
- **Status:** Resolved & Verified.

---

### [PS-031] Sentralisasi Standar Spesifikasi Pekerjaan (Job Description & Kualifikasi) pada Master Posisi oleh HR
- **Problem:**
  Sebelumnya, deskripsi pekerjaan (*job description*), persyaratan kualifikasi, pendidikan minimal, dan pengalaman minimal diinput secara manual dan berulang setiap kali mengajukan MPR atau membuat Job Posting. Hal ini menyebabkan inkonsistensi standar persyaratan jabatan antar departemen serta membebani pemohon lowongan. Kebutuhan operasional menginginkan agar seluruh data standar jabatan (*job description*, kualifikasi, dll) dikelola dan ditambahkan secara terpusat oleh HR pada Master Posisi (`M_POSISI`).
- **Identifikasi:**
  1. Skema tabel `dbo.M_POSISI` belum memiliki kolom penyimpanan spesifikasi jabatan standar.
  2. Mutasi posisi melalui Stored Procedure `dbo.sp_SavePosisi` perlu diperluas untuk menerima parameter template spesifikasi jabatan dengan tetap mencatat riwayat audit (`dbo.sp_AuditLog`).
  3. Prosedur pembuatan MPR `dbo.sp_CreateRequisition` harus memiliki mekanisme *fallback* otomatis: jika pemohon tidak mengisi atau mengosongkan *job desc* / kualifikasi, sistem otomatis mewarisi (*inherit*) nilai dari template `dbo.M_POSISI`.
  4. Antarmuka Master Posisi (`master/index.php`) harus menyediakan input formulir modal (Job Desc textarea, Kualifikasi textarea, dropdown Pendidikan Minimal, dan angka Pengalaman Minimal) serta menyematkan indikator status kelengkapan template di tabel data.
  5. Antarmuka Buat MPR (`requisitions/create.php`) harus mendukung *auto-fill* dinamis saat user memilih posisi kerja.
- **Solusi:**
  1. **Migrasi Database:**
     - Menambahkan kolom `job_desc` (VARCHAR(MAX)), `kualifikasi` (VARCHAR(MAX)), `pendidikan_minimal` (NVARCHAR(60)), dan `pengalaman_minimal_tahun` (INT) pada tabel `dbo.M_POSISI` via migrasi `database/migrations/20260911_1200__posisi_job_desc_kualifikasi.sql`.
  2. **Stored Procedure Updates (SQL Server 2008 R2 Compliant):**
     - `dbo.sp_SavePosisi`: Memperbarui signature dan query INSERT/UPDATE untuk menyimpan data spesifikasi jabatan standar beserta audit log.
     - `dbo.sp_CreateRequisition`: Menambahkan logika fallback menggunakan `COALESCE(NULLIF(@job_desc, ''), @pos_jd)` dan kualifikasi standar sehingga requisition otomatis lengkap terstandarisasi.
  3. **Model Backend (CI3):**
     - `Master_model.php`: Mengambil 4 kolom baru pada `list_posisi()` dan mengikat parameternya pada panggilan `dbo.sp_SavePosisi`.
     - `Requisition_model.php`: Mengambil 4 kolom template posisi pada method `positions()`.
  4. **Antarmuka Pengguna (UI/UX):**
     - `views/master/index.php`:
       - Menambahkan kolom "Template Jabatan (HR)" di tabel Master Posisi dengan badge status visual (`Lengkap`, `Parsial`, atau `Belum diatur`).
       - Memperlebar modal dialog pop-up (`max-width:640px`) dan menambahkan section khusus "Template Standar Jabatan (Dikelola HR)" berisi input Pendidikan Minimal, Pengalaman Minimal, Job Description textarea, dan Kualifikasi textarea.
       - Memperbarui fungsi JavaScript `openEditModal(data)` untuk mempopulasikan nilai template ke dalam modal form saat mengedit posisi.
     - `views/requisitions/create.php`:
       - Menyediakan section "Spesifikasi Jabatan & Standar Posisi" yang otomatis terisi (*auto-populate*) via JavaScript saat posisi dipilih dari dropdown.
- **Status:** Resolved & Verified.

---

### [PS-032] Penyelarasan Alur Status MPR Sesuai Real-Case Korporat: Review HR dan Penolakan Eksplisit (Ditolak HR & Ditolak BOD)
- **Problem:**
  Siklus hidup pengajuan tenaga kerja (MPR) sebelumnya langsung diarahkan dari pemohon ke Direksi (`Menunggu_BOD`) tanpa melalui peninjauan formasi dan budget oleh HR terlebih dahulu. Selain itu, penolakan oleh BOD sebelumnya mereset status MPR kembali ke `Draft` tanpa rekam status eksplisit. Pengguna meminta alur MPR diselaraskan dengan operasional riil korporat:
  1. Permintaan tenaga kerja yang dibuat departemen diajukan ke HR untuk ditinjau (`Review_HR`).
  2. HR dapat menyetujui dan meneruskan pengajuan ke Direksi (`Menunggu_BOD`), atau menolak permintaan tersebut (`Ditolak_HR`) dengan catatan alasan wajib (misalnya analisis beban workload belum memadai atau belum ada alokasi budget).
  3. Jika ditolak oleh Direksi, status dicatat secara eksplisit sebagai `Ditolak_BOD` (bukan langsung reset ke Draft tanpa jejak).
  4. Pemohon dapat memperbaiki dokumen dan mengajukan ulang ke HR setelah status `Ditolak_HR` atau `Ditolak_BOD`.
- **Identifikasi:**
  1. **Database Constraint:** Tabel `dbo.REQUISITIONS` dibatasi oleh check constraint `CK_REQ_status`. Nilai status baru (`Review_HR`, `Ditolak_HR`, `Ditolak_BOD`) perlu ditambahkan melalui migrasi skema.
  2. **Stored Procedures T-SQL:**
     - Pembuatan prosedur baru `dbo.sp_SubmitToHR`: Memindahkan status ke `Review_HR`, meng-generate nomor resmi `MPR/YYYY/MM/NNN` jika belum ada, dan mencatat audit trail.
     - Penyesuaian `dbo.sp_SubmitToBOD`: Mengizinkan pengajuan dari status `Review_HR` (selain `Draft` dan `Sourcing_Ulang`).
     - Penyesuaian `dbo.sp_RecordApproval`: Mengubah status menjadi `Ditolak_BOD` saat keputusan BOD adalah `Rejected`.
     - Penyesuaian `dbo.sp_UpdateRequisitionStatus`: Menambahkan validasi transisi untuk status baru dan kewajiban mengisi catatan/alasan penolakan jika status diubah ke `Ditolak_HR`. Menonaktifkan form posting publik jika ditolak.
  3. **Antarmuka Pengguna & Model:**
     - Model `Requisition_model.php`: Menambahkan method `submit_to_hr()`.
     - Controller `Requisitions.php`: Menambahkan aksi `submit_hr()` dengan permission `BUAT_MPR`.
     - View `requisitions/index.php`: Menambahkan opsi filter dropdown dan badge status warna (`Review_HR`, `Ditolak_HR`, `Ditolak_BOD`).
     - View `requisitions/view.php`:
       - Menampilkan banner aksi pemohon untuk mengajukan ke HR saat status `Draft`, `Ditolak_HR`, atau `Ditolak_BOD`.
       - Menampilkan panel kontrol khusus HR saat status `Review_HR` (tombol "Setujui & Teruskan ke BOD" serta tombol & dialog modal "Tolak Permintaan (Ditolak HR)" dengan alasan wajib).
       - Memperbarui kontrol dropdown status manual oleh HR.
- **Solusi:**
  1. DDL Migration `database/migrations/20260911_1100__status_mpr_review_hr_dan_ditolak.sql` dijalankan via `php tools/migrate.php up`.
  2. Stored procedures `sp_SubmitToHR.sql`, `sp_SubmitToBOD.sql`, `sp_RecordApproval.sql`, dan `sp_UpdateRequisitionStatus.sql` dideploy via `php tools/migrate.php proc`.
  3. Integrasi model, controller, dan view detail MPR selesai diimplementasikan dan diverifikasi.
- **Status:** Resolved & Verified.

---

### [PS-033] Penerapan Alur Rekrutmen Tunggal Baku (Universal Flow Default) & Penghapusan Opsi Pemilihan Flow
- **Problem:**
  Sebelumnya sistem memiliki beberapa alur seleksi berbeda (`HQ_MANAGER`, `HQ_STAFF_KRUSIAL`, `MP_OUTLET`, `HQ_STAFF`). Hal ini menimbulkan kebingungan bagi pemohon formasi MPR dan pengelola rekrutmen mengenai pemilihan alur. Pengguna menginstruksikan:
  *"untuk flow pipeline itu cuma 1 dan anda buat begitu saja dan flow2 yang lama itu hapus saja dan tidak ada pilihan milih flow jadi sudah otomatis pilih flow yang default itu"*.
- **Identifikasi:**
  1. **Database Skema & Data:**
     - Menyatukan seluruh alur rekrutmen menjadi 1 alur standar baku (`STD_RPG` - `Alur Standar Rekrutmen RPG`) di tabel `dbo.M_FLOW`.
     - Mengalihkan seluruh relasi foreign key dari `dbo.M_POSISI`, `dbo.REQUISITIONS`, `dbo.APPLICATIONS`, dan `dbo.RPT_FUNNEL_HARIAN` ke `id_flow` standar tunggal tersebut.
     - Menghapus flow lama serta tahapan relasi (`M_FLOW_STAGE_DOKUMEN`, `M_FLOW_STAGE`, dan baris `M_FLOW` selain `STD_RPG`).
  2. **Stored Procedures:**
     - `dbo.sp_CreateRequisition`: Secara otomatis menetapkan `id_flow` ke alur seleksi tunggal aktif tanpa input manual dari pemohon formasi MPR.
     - `dbo.sp_SavePosisi`: Menetapkan `default_flow` ke alur seleksi tunggal aktif secara otomatis.
     - `dbo.sp_SubmitApplication`: Melakukan snapshot otomatis ke alur seleksi standar tunggal yang aktif.
  3. **Antarmuka & Controller Web:**
     - `views/master/index.php`: Menghapus kolom tabel 'Flow Default' dan input dropdown `<select name="default_flow">` pada modal form Master Posisi, menggantikannya dengan penanda otomatis bahwa seluruh posisi menggunakan alur seleksi standar perusahaan.
     - `views/requisitions/create.php`: Memastikan form pengajuan MPR tidak membebani pemohon dengan pilihan alur seleksi manual.
     - `controllers/Flowbuilder.php`: Menjadikan Flowbuilder sebagai pengelola tunggal untuk 1 alur standar baku RPG, termasuk pengeditan tahapan, SLA, PIC role, dan dokumen persyaratan.
- **Solusi:**
  1. Dibuat dan dijalankan migrasi `database/migrations/20260911_1300__flow_tunggal_standar_rpg.sql` dan `database/migrations/20260911_1330__purge_old_flows.sql`.
  2. Deploy seluruh Stored Procedure T-SQL via `php tools/migrate.php proc`.
  3. Pembersihan tampilan kolom dan form input pemilihan flow di `web/application/views/master/index.php`.
  4. Menambahkan method pendukung Flow Builder pada `web/application/models/Master_model.php` (`flow_stages()`, `all_roles()`, `save_flow()`, dan `flow_stage_action()`) yang memanggil SP `dbo.sp_SaveFlow` dan `dbo.sp_SaveFlowStage`.
- **Status:** Resolved & Verified.

---

### [PS-034] Standarisasi Template Spesifikasi Jabatan HR & Desain Ergonomis Modal Pop-up Master Data
- **Problem:**
  1. Spesifikasi pekerjaan (Job Description, Kualifikasi, Pendidikan Minimal, dan Pengalaman Kerja Minimal) sebelumnya dimasukkan secara sporadis dan bebas pada pembuatan MPR atau posting publik. HR membutuhkan kontrol sentral untuk menentukan standar resmi jabatan perusahaan langsung pada Master Posisi (`dbo.M_POSISI`).
  2. Tampilan modal pop-up dialog master data sebelumnya berantakan, menggunakan inline styles yang berbenturan dengan native `<dialog>` API, serta tombol aksi simpan/batal tenggelam jika form memiliki isian panjang (memaksa user scroll ke paling bawah form).
- **Identifikasi:**
  1. **Sentralisasi Job Spec di M_POSISI:**
     - Penambahan kolom `job_desc` (VARCHAR MAX), `kualifikasi` (VARCHAR MAX), `pendidikan_minimal` (NVARCHAR 60), dan `pengalaman_minimal_tahun` (INT) pada `dbo.M_POSISI`.
     - Stored Procedure `dbo.sp_SavePosisi` diperbarui untuk menerima 4 parameter template standar tersebut.
     - Form MPR (`requisitions/create.php`) membaca data posisi via AJAX/JSON data attribute dan otomatis mengisi form jika user belum mengisi kustomisasi.
     - Stored Procedure `dbo.sp_CreateRequisition` dan `dbo.sp_CreatePosting` menerapkan fallback `COALESCE(NULLIF(..., ''), @pos_default)`.
  2. **Perbaikan Tampilan Modal Dialog Master:**
     - Modal pop-up `<dialog id="dlg-master">` dirombak menggunakan struktur CSS modern flex container (`#dlg-master[open] { display:flex; flex-direction:column; }`).
     - Header dialog dibuat statis di atas (`.modal-header-bar` dengan tombol close & judul kontekstual).
     - Formulir isian ditempatkan di dalam kontainer scroll mandiri (`.modal-form-scroll` dengan scrollbar kustom halus).
     - Tombol aksi (Batal & Simpan) dibuat docked/sticky di bagian bawah (`.modal-footer-dock`) sehingga selalu terlihat dan nyaman diakses tanpa perlu menggulir ke dasar isian.
- **Solusi:**
  1. Memperbarui `database/procedures/sp_SavePosisi.sql` dan `web/application/models/Master_model.php`.
  2. Memperbarui `web/application/views/master/index.php` untuk styling modal, layout fixed header + scroll body + docked footer, serta form isian template posisi.
  3. Memperbarui `web/application/views/requisitions/create.php` dengan script auto-populate spesifikasi posisi.
- **Status:** Resolved & Verified.

---

### [PS-035] Modernisasi dan Penataan Tampilan Antarmuka Form Publik & Lowongan Kerja
- **Problem:**
  1. Halaman admin "Form Publik & Lowongan" (`postings/index.php`) memiliki tata letak tabel yang sempit, data penempatan outlet dan departemen belum tampil, serta belum ada visualisasi ringkasan metrik lowongan (terbuka vs ditutup, jumlah pelamar masuk).
  2. Sub-halaman pendukung seperti Pengaturan Form (`postings/form_settings.php`), Statistik Portal Funnel Sourcing Eksternal (`postings/stats.php`), dan Manajemen Token Dokumen Pribadi (`postings/tokens.php`) masih menggunakan tata letak form satu kolom standar yang tidak memanfaatkan layar kerja secara optimal.
- **Identifikasi:**
  1. Pengaktifan mode tampilan lebar (`'wide' => TRUE`) pada `Postings::index` agar selaras dengan standar tata letak viewport admin RPG.
  2. Pengayaan query di `Posting_model::list_postings` dan `Posting_model::get_posting` menggunakan `LEFT JOIN dbo.M_DEPARTEMEN` dan `LEFT JOIN dbo.M_OUTLET` untuk menampilkan konteks posisi dan penempatan kerja tanpa query tambahan.
  3. Pembaruan tabel utama dengan alokasi lebar kolom proporsional, tag status form lowongan, tombol copy link publik instan (`navigator.clipboard.writeText`) dengan umpan balik visual ("Tersalin!"), dan tombol aksi titik 3 (kebab menu &#8942;) yang memuat opsi Kelola Form, Statistik Portal, Buka Pipeline, serta Toggle Buka/Tutup Form secara terorganisir.
  4. Penataan sub-halaman konfigurasi jadwal, statistik portal, dan token berkas kandidat menjadi antarmuka 2-kolom responsif (formulir aksi di sisi kiri, kartu informasi/tautan/riwayat di sisi kanan).
- **Solusi:**
  1. Memperbarui `web/application/controllers/Postings.php` untuk mengaktifkan layout lebar (`wide => TRUE`).
  2. Memperbarui `web/application/models/Posting_model.php` dengan join departemen dan outlet.
  3. Mendesain ulang `web/application/views/postings/index.php` lengkap dengan 4 quick stat cards, tabel fixed-layout, copy link instan, dan kebab menu titik 3 (`.mpr-menu-btn` & `.mpr-dropdown`).
  4. Mendesain ulang `web/application/views/postings/form_settings.php`, `web/application/views/postings/stats.php`, dan `web/application/views/postings/tokens.php`.
- **Status:** Resolved & Verified.

---

### [PS-036] Perbaikan Error Undefined Method `postings_for_req()` & `toggle_posting_form()` pada Detail MPR
- **Problem:**
  Membuka halaman detail MPR (`requisitions/view/<id>`) menghasilkan fatal error:
  `Call to undefined method Requisition_model::postings_for_req()` pada `controllers/Requisitions.php:118`.
- **Identifikasi:**
  Controller `Requisitions.php` memanggil `$this->rm->postings_for_req($id_req)` untuk merender daftar link form lowongan publik terkait MPR tersebut, serta `$this->rm->toggle_posting_form(...)` pada endpoint toggle posting. Namun kedua method tersebut belum didefinisikan di dalam `Requisition_model.php`.
- **Solusi:**
  1. Menambahkan method `postings_for_req($id_req)` pada `web/application/models/Requisition_model.php` untuk mengambil daftar job postings dari `dbo.JOB_POSTINGS` beserta jumlah pelamar yang melamar (`COUNT(*) FROM dbo.APPLICATIONS`).
  2. Menambahkan method `toggle_posting_form($id_posting, $form_aktif, $oleh_user)` pada `Requisition_model.php` yang mengeksekusi Stored Procedure T-SQL `dbo.sp_TogglePostingForm`.
- **Status:** Resolved & Verified.

---

### [PS-037] Perbaikan Error Undefined Method `final_candidates()` pada Kanban Pipeline
- **Problem:**
  Membuka halaman papan Kanban rekrutmen (`pipeline/board/<id>`) menghasilkan fatal error:
  `Call to undefined method Requisition_model::final_candidates()` pada `controllers/Pipeline.php:82`.
- **Identifikasi:**
  Controller `Pipeline.php` membutuhkan daftar pelamar berstatus akhir/terminal (`Hired`, `Rejected`, `Withdrawn`, `Offer_Declined`, `No_Show`, `Talent_Pool`) untuk ditampilkan pada drawer arsip kandidat final di bagian bawah board Kanban (`pipeline/board.php`), namun method `final_candidates($id_req)` belum diimplementasikan di `Requisition_model.php`.
- **Solusi:**
  Menambahkan method `final_candidates($id_req)` pada `web/application/models/Requisition_model.php` yang mengambil seluruh pelamar berstatus final dari `dbo.APPLICATIONS` dengan join ke `dbo.CANDIDATES`, `dbo.M_STAGE`, dan `dbo.M_REMARKS` beserta nama lengkap, no WhatsApp, email, tahap terakhir, dan label remark penolakan/alasan terakhir.
- **Status:** Resolved & Verified.

---

### [PS-038] Redesign Tampilan Pipeline Board, Standardisasi Vektor Icon, dan Eliminasi Bug Overlay
- **Problem:**
  Tampilan antarmuka pipeline seleksi pelamar (`pipeline/board.php`) sebelumnya memiliki beberapa kendala UI/UX:
  1. Penggunaan emoji teks mentah yang berantakan (🎤, 🧠, 💼, 💬, ✏️, &#128451;, Doc) yang tidak konsisten dengan tema desain sistem RPG.
  2. Elemen popup dropdown ad-hoc dan log kontak menggunakan tag `<details>` dengan posisi absolut yang kerap terpotong (`overflow:hidden` clipping) atau menimpa tabel di sekitarnya tanpa backdrop dismissal.
  3. Dialog popup evaluasi (`<dialog>`) belum memiliki struktur fixed-header, scrollable-body, dan docked-footer, sehingga form evaluasi panjang sulit dinavigasi.
- **Identifikasi:**
  1. Mengganti seluruh emoji teks dengan icon vektor SVG inline yang bersih dan konsisten dengan antarmuka RPG.
  2. Mengonversi tombol sisip tahap ad-hoc dan log kontak menjadi modal dialog standar RPG (`#dlg-adhoc` dan `#dlg-contact`), menghilangkan sepenuhnya bug overlay dan clipping CSS.
  3. Memperbarui seluruh modal evaluasi (Interview, Psikotes, Offering, Kontak, dan Ad-Hoc) dengan template standar RPG (`.rpg-modal`, `.rpg-modal-header`, `.rpg-modal-body`, `.rpg-modal-footer`, dan backdrop blur).
  4. Merapikan form inline advance alur dan action pill buttons agar tidak menyebabkan pergeseran baris tabel saat input catatan dibuka.
- **Solusi:**
  Memperbarui `web/application/views/pipeline/board.php` dengan desain terstandarisasi, responsif, bebas glitch overlay, dan dilengkapi indikator metrik yang proporsional.
- **Status:** Resolved & Verified.

---

### [PS-039] Redesign UI/UX Dashboard Berbasis Role Spesifik (SUPER_ADMIN & USER_DEPT)
- **Problem:**
  Sebelumnya dashboard sistem bersifat generik satu tampilan untuk semua pengguna:
  1. Pengguna `USER_DEPT` (Kepala Departemen/Hiring Manager) disajikan filter makro dan metrik audit yang tidak relevan dengan kebutuhan manajerial tim mereka, serta tidak dapat melihat langsung progres permintaan penambahan karyawan (MPR) dan daftar kandidat tim yang sedang diseleksi.
  2. Tampilan dashboard belum memanfaatkan pemisahan peran antara pengambil keputusan operasional rekrutmen perusahaan (`SUPER_ADMIN`) dan pimpinan unit/departemen pemohon (`USER_DEPT`).
- **Identifikasi:**
  1. Melakukan adaptasi antarmuka berbasis peran pengguna (`$user_role` & `current_user_dept()`):
     - **`USER_DEPT`**: Portal terfokus pada kuota permohonan karyawan departemen (Dibutuhkan vs Terpenuhi), status pengajuan MPR terkini (Draft, Review HR, Approval BOD, Sourcing, Terpenuhi) dengan aksi cepat menuju detail/pipeline, antrean kandidat departemen yang sedang diseleksi beserta tahap seleksi saat ini, serta shortcut tombol pengajuan MPR baru.
     - **`SUPER_ADMIN`**: Command center operasional rekrutmen korporat yang memuat filter multi-dimensi (periode, departemen, posisi, outlet, status global), grid KPI 8 status lamaran, matriks funnel konversi 7 tahap standar seleksi, widget time-to-hire & BOD approval turnaround, peringatan dini aging SLA yang melebihi batas toleransi `M_STAGE`, dan tren agregat harian.
  2. Menambahkan method query pendukung di `Dashboard_model.php`:
     - `dept_requisitions($id_dept, $limit)`: daftar permohonan penambahan karyawan khusus departemen terkait.
     - `dept_candidates_active($id_dept, $limit)`: pelamar aktif yang sedang diproses untuk posisi di bawah departemen pemohon.
     - `dept_summary_metrics($id_dept)`: agregasi total MPR, jumlah kebutuhan vs pemenuhan, status pending/aktif/selesai departemen.
  3. Menyesuaikan controller `Dashboard.php` untuk menginjeksi konteks pengguna, role, dan data spesifik departemen ke view.
- **Solusi:**
  1. Menambahkan method pendukung departemen pada `web/application/models/Dashboard_model.php`.
  2. Memperbarui `web/application/controllers/Dashboard.php` untuk menyediakan parameter role-specific.
  3. Mendesain ulang `web/application/views/dashboard/index.php` menjadi 2 tata letak modular terpisah dan optimal untuk masing-masing role.
- **Status:** Resolved & Verified.

---

### [PS-040] Transformasi Funnel Dashboard Menjadi Matriks Dinamis Posisi x Tahap Berbasis Filter Tanggal
- **Problem:**
  Tampilan "Funnel Konversi Tahapan" pada dashboard sebelumnya berbentuk tabel agregasi statis 7 tipe tahap standar RPG (`SCREENING` s/d `ONBOARD`) yang hanya mengelompokkan status global tanpa memperlihatkan distribusi posisi lowongan kerja yang sedang dibuka. Pengguna membutuhkan matriks operasional dinamis:
  1. Baris (vertikal ke bawah): Posisi lowongan yang dibuka (`M_POSISI`).
  2. Kolom (horizontal ke kanan): Proses atau tahapan seleksi aktif (`M_STAGE`).
  3. Bersifat dinamis: Posisi maupun tahapan yang tidak memiliki kandidat atau aktivitas pembaruan pada filter waktu tidak ditampilkan.
  4. Mendukung filter tanggal spesifik ("tanggal saja") maupun rentang tanggal ("range tanggal dari-sampai").
- **Identifikasi:**
  1. Pada `Dashboard_model.php`, dibuat method `position_stage_funnel(array $f)` yang melakukan agregasi relasional pelamar aktif berdasarkan kriteria tanggal multi-titik:
     - `a.tanggal_lamar` (tanggal pengajuan berkas)
     - `aps.tanggal_mulai` dan `aps.tanggal_selesai` (waktu pelaksanaan tahap kandidat)
     - Log aktivitas transisi pada `dbo.APPLICATION_HISTORY.waktu`
     Jika filter tanggal tunggal diisi (`tanggal`), rentang `dari` dan `sampai` disamakan ke tanggal tersebut.
  2. Query SQL Server 2008 R2 kompatibel mengelompokkan data per `id_posisi`, `nama_posisi`, `nama_departemen`, `id_stage`, `nama_tahap`, `tipe_tahap`, dan urutan alur, lalu dipivot secara dinamis di level model PHP menjadi matriks `$matrix[$pos_id][$stage_id]`. Posisi dan tahapan tanpa aktivitas secara otomatis tereliminasi dari set data.
  3. Tahapan diurutkan berdasarkan urutan alur (`urutan` / `id_stage`) dan posisi diurutkan secara alfabetis.
  4. Pada controller `Dashboard.php`, parameter filter `tanggal`, `dari`, `sampai` diproses dan diteruskan ke model, lalu `$pos_stage_funnel` dikirimkan ke view.
  5. Pada view `dashboard/index.php`:
     - Menambahkan input tanggal spesifik pada filter bar dashboard.
     - Mengganti tabel statis dengan tabel matriks dinamis `Posisi Lowongan x Tahapan Seleksi`, lengkap dengan sticky column, badge tipe tahap, shortcut langsung ke pipeline posisi terkait, badge jumlah pelamar per perpotongan sel, dan baris total kalkulasi keseluruhan.
     - Menampilkan kondisi kosong (empty state) yang rapi dan informatif bila tidak terdapat pergerakan data pada tanggal yang dipilih.
- **Solusi:**
  1. Menambahkan method `position_stage_funnel(array $f)` di `web/application/models/Dashboard_model.php`.
  2. Menyesuaikan pemrosesan filter dan view binding pada `web/application/controllers/Dashboard.php`.
  3. Memperbarui tampilan matriks dan filter bar pada `web/application/views/dashboard/index.php`.
- **Status:** Resolved & Verified.

---

### [PS-041] Redesign Tata Letak Dashboard RPG Menjadi Single-Screen Viewport (Fit 1 Layar Penuh)
- **Problem:**
  Sebelumnya tampilan dashboard memanjang ke bawah (vertical scrolling panjang) karena tumpukan elemen filter form besar, grid KPI tebal, tabel matriks funnel, card metrik SLA, card audit UU PDP, card peringatan aging kandidat, dan tabel tren 14 hari. Hal ini mengharuskan recruiter/manajemen melakukan scroll berkali-kali untuk melihat status menyeluruh.
- **Identifikasi:**
  1. Mengatur container dashboard dengan CSS flexbox `height: calc(100vh - 46px)` dan `overflow: hidden` pada container view, sehingga dashboard tampil pas dalam 1 layar tanpa memicu scrollbar browser utama.
  2. Meringkas header dan filter bar menjadi satu baris horizontal kompak (`.dash-filter-strip`) yang memuat seluruh input filter (periode dari/sampai, tanggal tunggal, dropdown master, status, dan tombol aksi).
  3. Mengubah 8 status KPI pelamar menjadi strip horizontal chip 8 kolom (`.dash-kpi-strip`) yang ramping dengan indikator warna tepi.
  4. Membagi area kerja utama (mengisi sisa tinggi layar viewport) menjadi layout 2 kolom responsif:
     - **Kolom Kiri (2.2fr)**: Matriks Funnel Dinamis (Posisi x Tahapan) dengan fitur internal scrolling, header baris pertama sticky (atas), dan kolom posisi lowongan sticky (kiri) sehingga nama posisi dan tahapan tetap terbaca saat tabel digeser.
     - **Kolom Kanan (1fr)**: Panel tab terpadu (`.dash-tabs-panel`) dengan navigasi tab instan:
       - *Tab 1: SLA & Metrik* (Waktu pemenuhan lowongan, durasi persetujuan BOD, dan compliance audit UU PDP).
       - *Tab 2: Aging SLA* (Daftar kandidat yang melampaui batas SLA M_STAGE beserta badge jumlah kandidat).
       - *Tab 3: Tren 14 Hari* (Volume kandidat harian per tahapan seleksi).
  5. Untuk role `USER_DEPT`, tata letak disesuaikan dengan 4 KPI ringkas di bagian atas serta 2 panel sejajar (MPR Terbaru dan Antrean Kandidat) yang memiliki scroll internal mandiri.
- **Solusi:**
  Memperbarui `web/application/views/dashboard/index.php` dengan arsitektur CSS 1-layar, komponen tab interaktif ringan, dan sticky matrix scrolling.
- **Status:** Resolved & Verified.

---

### [PS-042] Implementasi Formulir Data Pelamar Formal A-I dan Generator Tautan Onboarding WhatsApp
- **Problem:**
  Pelamar yang melamar via link publik Google Forms hanya mengisi data ringkas (21 field), padahal proses interview tatap muka dan onboarding resmi RPG mensyaratkan dokumen resmi *Formulir Data Pelamar RPG (Bagian A-I)* yang memuat data susunan keluarga, riwayat pendidikan & kursus, rekap pekerjaan lengkap, kuesioner evaluasi diri, riwayat penyakit, kontak darurat, dan pas foto resmi.
- **Identifikasi:**
  1. Skema database diperluas melalui migrasi `20260912_1000`, `20260912_1300`, `20260912_1400`, dan `20260912_1500` mencakup:
     - `dbo.APPLICATION_PROFILE_EXTENDED` (biodata lengkap, evaluasi diri, minat & konsep pribadi, informasi umum).
     - `dbo.CANDIDATE_FAMILY` (susunan keluarga: orang tua, saudara kandung, pasangan, anak).
     - `dbo.CANDIDATE_EDUCATION` & `dbo.CANDIDATE_COURSES` (pendidikan formal & non-formal).
     - `dbo.CANDIDATE_WORK_EXPERIENCE` (riwayat pekerjaan terinci: gaji, tugas, alasan berhenti).
     - `dbo.CANDIDATE_EMERGENCY_CONTACTS` (kontak darurat keluarga).
  2. Dibuat Stored Procedure `dbo.sp_SaveOnboardingData`, `dbo.sp_AddCandidateFamilyMember`, dan `dbo.sp_AddCandidateWorkExperience`.
  3. Dibangun halaman pengisian form pelamar formal publik bertoken (`onboarding/form.php`) yang identik dengan dokumen PDF cetak resmi RPG, dilengkapi validasi interaktif dan upload pas foto.
  4. Pada papan pipeline (`pipeline/board.php`), ditambahkan menu kebab (⋮) dengan generator link onboarding bertoken (`FORM_TOKENS`) yang dilengkapi tombol salin tautan instan dan tombol share WhatsApp otomatis (`wa.me`) serta opsi cetak dokumen formulir fisik A-I.
- **Solusi:**
  Membuat modul onboarding terintegrasi pada `controllers/Onboarding.php`, `models/Candidate_model.php`, dan `views/onboarding/form.php` serta dialog popup token di `views/pipeline/board.php`.
- **Status:** Resolved & Verified.

---

### [PS-043] Alur Pengajuan MPR 2-Putaran Evaluasi (Review HR & Review BOD) dengan Arahan Catatan Revisi
- **Problem:**
  Sebelumnya alur pengajuan penambahan karyawan (MPR) hanya memiliki 1 tahap approval BOD langsung tanpa evaluasi beban kerja oleh Tim HR. HR membutuhkan mekanisme verifikasi awal, dan pemohon membutuhkan transparansi alasan revisi jika formasi dikembalikan oleh HR maupun BOD.
- **Identifikasi:**
  1. Skema status MPR diperluas melalui migrasi `20260911_1100`, `20260912_1600`, `20260913_1000`, dan `20260913_1100`:
     - Status bertahap: `Draft` ➔ `Review_HR` ➔ `Review_BOD` ➔ `Approved`.
     - Kemungkinan revisi: `Revisi_HR` (dengan kolom `catatan_hr`) dan `Revisi_BOD` (dengan kolom `catatan_bod`).
     - Status penolakan terpisah: `Ditolak_HR` dan `Ditolak_BOD`.
  2. Dibuat Stored Procedure `dbo.sp_SubmitToHR`, `dbo.sp_SubmitToBOD`, dan `dbo.sp_UpdateRequisitionCatatanHR`.
  3. Tampilan detail MPR (`requisitions/view.php`) dan edit form (`requisitions/edit.php`) diperkaya banner peringatan visual arahan revisi (`catatan_hr` / `catatan_bod`) agar pemohon mengetahui poin-poin yang harus diperbaiki sebelum mengajukan ulang.
  4. Seluruh label status dan teks tampilan yang memuat kata "Direksi" distandarisasikan menjadi "BOD" (*Review BOD*, *Revisi dari BOD*, *Ditolak BOD*).
- **Solusi:**
  Memperbarui `controllers/Requisitions.php`, `models/Requisition_model.php`, `helpers/status_helper.php`, dan `views/requisitions/`.
- **Status:** Resolved & Verified.

---

### [PS-044] Filter Rekap No. MPR Dinamis pada Daftar Pelamar & Dashboard Analitik Rekrutmen
- **Problem:**
  Tampilan daftar pelamar (`candidates/index.php`) dan analitik dashboard (`dashboard/index.php`) sebelumnya mencampur seluruh pelamar tanpa opsi filter berdasarkan dokumen nomor MPR lowongan terkait, serta belum membedakan antara MPR yang masih aktif dibuka vs MPR yang sudah selesai/ditutup.
- **Identifikasi:**
  1. Pada `Candidate_model.php` dan `Dashboard_model.php`, dibuat method `requisitions()` yang mengelompokkan MPR berdasarkan status aktif:
     - `is_aktif_mpr = 1`: status `Sourcing`, `Approved`, `Sourcing_Ulang`.
     - `is_aktif_mpr = 0`: status `Terpenuhi`, `Ditolak_HR`, `Ditolak_BOD`, `Dibatalkan`, `Kadaluarsa`.
  2. Pada controller `Candidates.php`, parameter filter `id_req` ditangkap dan diterapkan ke query `candidates_list` dan counter data pelamar.
  3. Pada controller `Dashboard.php` dan `Export.php`, parameter `id_req` diintegrasikan ke `dashboard()` (panggilan 11-param SP `sp_Dashboard`), matriks funnel posisi x tahap `position_stage_funnel()`, distribusi remark `applicant_remarks_summary()`, tren 14 hari `funnel_trend()`, dan ekspor spreadsheet kandidat `candidates_export()`.
  4. Pada view `candidates/index.php` dan `dashboard/index.php`, dropdown filter No. MPR dirender dengan `<optgroup label="MPR Aktif / Dibuka">` dan `<optgroup label="MPR Selesai / Ditutup">`.
- **Solusi:**
  Memperbarui `database/procedures/sp_Dashboard.sql`, `models/Dashboard_model.php`, `models/Candidate_model.php`, `controllers/Dashboard.php`, `controllers/Candidates.php`, `controllers/Export.php`, dan views terkait.
- **Status:** Resolved & Verified.

---

### [PS-045] Penataan Ulang Alur Tahap Sisipan (Ad-Hoc Stage): Wajib Remark Lanjut, Auto-Pindah Tahap, dan Eliminasi Opsi Duplikat
- **Problem:**
  1. Pada modal sisip tahap di papan seleksi (`pipeline/board.php`), tahap aktif saat ini sebelumnya selesai otomatis tanpa mengikat kode remark/keputusan kelulusan, sehingga laporan histori kelolosan menjadi kosong (`id_remark = NULL`).
  2. Kandidat tidak langsung otomatis berpindah ke tahap sisipan tersebut setelah form disimpan, melainkan tertahan di tahap lama dan harus diproses manual lagi.
  3. Pilihan dropdown tahap tambahan masih memunculkan tahap yang sudah pernah dilalui kandidat (misal: tes koding sudah pernah disisipkan, namun tetap muncul kembali di dropdown).
  4. Pengelompokan baris kandidat di `Pipeline.php` sebelumnya memakai integer `$key = (int) $r['urutan']`, sehingga tahap sisipan yang urutannya bertepatan dengan tahap lain tertimpa di view.
- **Identifikasi:**
  1. Modal sisip tahap diperbarui untuk mewajibkan pemilihan remark untuk tahap saat ini khusus yang berstatus `LANJUT` (opsi tolak disembunyikan otomatis).
  2. SP `dbo.sp_InsertAdHocStage` diperbarui dengan parameter `@id_remark`:
     - Tahap aktif saat ini ditandai `status_tahap = 'Lulus'` dengan mengikat `@id_remark` dan mencatat waktu selesai.
     - Tahap tambahan baru langsung diaktifkan (`status_tahap = 'Berjalan'`, `is_sisipan = 1`).
     - Pointer `id_stage_sekarang` di tabel `dbo.APPLICATIONS` otomatis dipindahkan ke tahap tambahan baru tersebut, dan status global dipastikan `In_Progress`.
  3. Driver multi-statement di `Requisition_model::insert_adhoc` dilengkapi `do { ... } while (sqlsrv_next_result($stmt))` agar transaksi commit SQL Server tuntas dieksekusi.
  4. Pada controller `Pipeline.php`, diambil data seluruh tahap yang pernah dimiliki tiap kandidat (`get_stages_for_lamaran`), lalu JavaScript modal menyaring dropdown secara dinamis: opsi tahap yang sudah pernah ada pada kandidat otomatis dihilangkan dari dropdown.
  5. Pengelompokan papan seleksi diubah menjadi per `id_stage` unik dengan lencana khusus *"Tahap Tambahan"*.
- **Solusi:**
  Memperbarui `database/procedures/sp_InsertAdHocStage.sql`, `models/Requisition_model.php`, `controllers/Pipeline.php`, dan `views/pipeline/board.php`.
- **Status:** Resolved & Verified.

---

### [PS-046] Penghapusan Tombol Cepat Pengujian pada Halaman Login Portal Internal RPG
- **Problem:**
  Halaman login portal internal RPG (`views/auth/login.php`) sebelumnya menampilkan kotak tombol "Akses Cepat Pengujian" untuk akun demo Super Admin dan User Dept. Untuk kesiapan operasional rilis dan profesionalitas tampilan, pengguna harus memasukkan kredensial autentikasi sendiri secara mandiri.
- **Identifikasi:**
  Menghilangkan kontainer tombol pintasan pengujian dan fungsi JavaScript `fillLogin()`, menyisakan form login standar yang bersih, aman, dan profesional.
- **Solusi:**
  Memperbarui `web/application/views/auth/login.php`.
- **Status:** Resolved & Verified.



