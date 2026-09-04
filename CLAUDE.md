# E-Recruitment Ratu Pertiwi Group

Sistem rekrutmen internal RPG. Dua pipeline: HQ dan MP/outlet.
Dokumen acuan ada di `docs/` — baca `docs/RENCANA_DEVELOPMENT.md` dan
`docs/ERD_Terkoreksi_E-Recruitment_RPG.md` sebelum mengubah skema.

## Stack (terkunci, jangan diusulkan diganti)
CodeIgniter 3 · PHP 7.4 · SQL Server 2008 R2 · Database-First
(logika transaksi di Stored Procedure, model tipis)

**Runtime produksi: Windows** (keputusan Fase 0). Web server IIS/Apache
dipastikan saat Fase 5. Job terjadwal pakai **Windows Task Scheduler**
(`tools/build-funnel.php`, `tools/run-retensi.php`) — bukan SQL Server Agent.

## Batasan T-SQL SQL Server 2008 R2 — JANGAN dipakai

| Tidak ada di 2008 R2   | Pakai ini                       |
|------------------------|---------------------------------|
| `OFFSET ... FETCH NEXT`| `ROW_NUMBER() OVER (...)` di CTE|
| `THROW`                | `RAISERROR(@msg, 16, 1)`        |
| `TRY_CONVERT`/`TRY_CAST`| `ISDATE()` / `ISNUMERIC()` + `CASE` |
| `CONCAT()`, `IIF()`    | `+` dengan `ISNULL()`, `CASE WHEN` |
| Tipe & fungsi `JSON`   | Normalisasi penuh. Jangan simpan JSON di kolom teks |
| `SEQUENCE`             | `IDENTITY`                      |
| `FORMAT()`             | `CONVERT()` dengan style code   |
| `STRING_SPLIT`         | Tabel bantu atau XML split      |
| `DATEFROMPARTS`        | `CONVERT(DATE, ...)`            |

## Aturan wajib

1. Semua query pakai **parameter binding**. Tidak pernah menyambung string ke SQL.
2. Paginasi selalu pola `ROW_NUMBER()` — jangan andalkan `limit()` CI3 driver sqlsrv.
3. Setiap perubahan status menulis `APPLICATION_HISTORY` **di transaksi yang sama**.
4. Master data pakai **soft delete** (`is_aktif = 0`). Tidak pernah `DELETE`.
5. Flow di-**snapshot** ke `APPLICATION_STAGES` saat lamaran dibuat.
   Perubahan `M_FLOW` sesudahnya tidak menyentuh lamaran yang sedang berjalan.
6. Batas upaya kontak dibaca dari `M_FLOW.maks_upaya_kontak`, bukan angka tetap di SP.
7. File fisik disimpan **di luar webroot**; DB hanya simpan path, hash SHA-256, metadata.
8. `status_global` terkunci di 9 nilai. Jangan pernah menambah nilai baru.
9. Tahap baru wajib punya `tipe_tahap` dari 7 nilai tetap
   (SCREENING/KONTAK/FORM/TEST/INTERVIEW/OFFER/ONBOARD) — ini sumbu report.
10. Jangan bikin field dinamis / EAV. Yang perlu dihitung harus kolom bertipe jelas.

## Tingkat akses data

| Data | Siapa |
|---|---|
| Daftar kandidat, CV | HR Admin, HR Spv, User Dept (req sendiri), BOD (req sendiri) |
| KTP, KK, Ijazah, NPWP | HR Admin, HR Spv — dicatat di `ACCESS_LOG_SENSITIF` |
| Gaji terakhir & harapan pelamar (`LIHAT_GAJI_PELAMAR`) | HR Admin ke atas |
| Nomor rekening (`LIHAT_FINANSIAL`) | HR Spv saja |
| Range gaji & offer (`LIHAT_GAJI`) | HR Spv, BOD |
| Riwayat penyakit (`CANDIDATE_HEALTH`) | HR Spv saja — consent terpisah, wajib dicatat di log |
| `EDIT_FLOW_TEMPLATE` | IT Admin (HR Spv dibuka setelah 2 siklus / 2 bulan) |

## Migrasi database

- Lokasi: `database/migrations/`
- Format nama: `YYYYMMDD_HHMM__deskripsi_singkat.sql`
- **File yang sudah di-push TIDAK PERNAH diedit.** Salah? Buat migrasi koreksi baru.
- Setiap skrip mencatat dirinya ke `SCHEMA_MIGRATIONS` di akhir eksekusi.
- Jalankan dengan `php tools/migrate.php` (bukan copy-paste manual ke SSMS).
- Stored procedure di `database/procedures/` — boleh diedit (idempotent,
  pakai pola `IF OBJECT_ID(...) IS NOT NULL DROP PROCEDURE` lalu `CREATE`).

## JANGAN

- **Jangan pernah menyentuh database produksi.** Hanya database dev.
- **Jangan install ODBC Driver 18** — tidak mendukung SQL Server 2008 R2. Pakai 17.4+.
- Jangan commit `database.php`, isi folder `storage/`, atau file kandidat.
- Jangan pakai Composer/framework tambahan tanpa dibahas dulu — target server lama.

## Perintah yang sering dipakai

```bash
php tools/test-koneksi.php     # validasi driver & koneksi (gerbang Fase 0)
php tools/migrate.php status   # lihat migrasi mana yang sudah jalan
php tools/migrate.php up       # jalankan migrasi yang belum
```
