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
  Saat menjalankan Claude Code dalam mode auto (`autoMode`) pada proyek `e-rekruitmenRPG`, eksekusi skrip internal (seperti `php tools/migrate.php`, `php tools/test-koneksi.php`, utilitas Python, dan Git) sering memicu dialog konfirmasi manual keselamatan (*safety confirmation prompt*).
- **Identifikasi:**
  Safety classifier bawaan Claude Code v2.1+ membatasi eksekusi CLI yang berpotensi memodifikasi state kecuali jika tool/CLI tersebut dideklarasikan dalam `autoMode.environment` dan diberikan izin eksplisit dalam `autoMode.allow` serta `permissions.allow`.
- **Solusi:**
  1. Dibuat file konfigurasi lokal [settings.local.json](file:///D:/KAHFI-RPG/e-rekruitmenRPG/.claude/settings.local.json) (yang telah di-ignore di `.gitignore`).
  2. Dikonfigurasikan pattern allowlist deterministik untuk shell Windows (`Bash` dan `PowerShell`):
     - `php *` (mencakup `tools/migrate.php`, `tools/test-koneksi.php`, `tools/seed-*.php`, `tools/build-funnel.php`, dll.)
     - `python *` (mencakup `tools/generate_pdf.py`)
     - `git *` (status, diff, commit, push, checkout)
     - `composer *`, `sqlcmd *`
  3. Didaftarkan aturan classifier `autoMode`:
     - `Org-specific CLIs`: `php`, `python`, `git`, `composer`, `sqlcmd`, `powershell`.
     - `allow`: Aturan bahasa natural yang mengizinkan eksekusi skrip developer di `tools/` dan git commands.
  4. Konfigurasi serupa disinkronkan ke level global `C:\Users\kahfi\.claude\settings.json` dan direktori induk `D:\KAHFI-RPG\.claude\settings.local.json`.
- **Status:** Resolved & Verified via `claude auto-mode config`.

---

### [PS-016] Perbaikan PHP ParseError (T_ENDIF) pada Pipeline Board & Penguatan Desain Tabel Fit-to-Screen Responsif
- **Problem:**
  Muncul galat `Type: ParseError, Message: syntax error, unexpected 'endif' (T_ENDIF)` di file `web/application/views/pipeline/board.php` baris 455 saat pengguna membuka halaman detail pipeline seleksi lamaran. Selain itu, seluruh tabel pada sistem wajib tampil fit 1 layar tanpa scroll menyamping (`no horizontal scrolling`) serta responsif untuk perangkat bergerak.
- **Identifikasi:**
  1. Terjadi duplikasi blok render tombol evaluasi dan penutup alur baris kandidat saat refactoring sebelumnya. Potongan baris 396–455 meninggalkan elemen `<?php endif; ?>` yatim tanpa pasangan `<?php if ($can_aksi): ?>` di dalam iterasi tahap alur.
  2. Tabel dengan konten data pelamar dan formulir inline perlu proporsi kolom persentase yang presisi, `table-layout: fixed`, dan pembatasan pembungkusan kata (`word-break: break-word`) agar tidak memaksa kontainer meluap ke luar lebar layar.
- **Solusi:**
  1. Membersihkan struktur sintaks PHP pada `web/application/views/pipeline/board.php`:
     - Menghapus blok ganda yang tertinggal pada baris kandidat.
     - Memastikan seluruh pasangan struktur kontrol CI3 (`foreach : endforeach;`, `if : endif;`) tertutup secara simetris dan valid.
  2. Memastikan layout tabel fit 1 layar dan responsif:
     - Menggunakan kelas `.table-responsive-fit` yang dikombinasikan dengan aturan CSS global anti-overflow `div[style*="overflow-x:auto"] { overflow-x: hidden !important; width: 100% !important; }`.
     - Menyediakan mode responsive stacking pada mobile breakpoint `@media (max-width: 768px)` agar baris tabel berubah menjadi kartu vertikal yang ergonomis.
  3. Mempertahankan seluruh fitur operasional papan pipeline:
     - Real-time instant search kandidat.
     - Filter SLA aging (>7 hari tertahan).
     - Stage Jump Navigator.
     - Modal dialog evaluasi (Interview, Psikotes, Offering Letter, Log Respon WA).
- **Status:** Resolved & Verified.

