# Migrasi Database

## Format nama file
```
YYYYMMDD_HHMM__deskripsi_singkat.sql
20260908_1000__master_referensi.sql
```
Pakai timestamp, **bukan** nomor urut (V001, V002) — supaya Kiki dan Kahfi
tidak tabrakan nomor kalau membuat migrasi di hari yang sama.

## Aturan
1. **File yang sudah di-push TIDAK PERNAH diedit.** Runner akan menolak dan
   memberi tahu kalau isi file berubah setelah dijalankan. Salah? Buat file
   migrasi koreksi baru.
2. Satu migrasi = satu perubahan yang masuk akal berdiri sendiri.
3. Pakai `GO` sebagai pemisah batch — runner memecahnya otomatis.
4. Tulis idempotent kalau memungkinkan:
   `IF OBJECT_ID('dbo.NAMA') IS NULL CREATE TABLE ...`

## Menjalankan
```bash
php tools/migrate.php status   # lihat mana yang sudah/belum
php tools/migrate.php up       # jalankan yang belum
```

## Ingat batasan SQL Server 2008 R2
Tidak ada `OFFSET/FETCH`, `THROW`, `TRY_CONVERT`, `CONCAT`, `IIF`, `FORMAT`,
`SEQUENCE`, `STRING_SPLIT`, dan tipe `JSON`. Daftar lengkap + penggantinya
ada di `CLAUDE.md` di root repo.
