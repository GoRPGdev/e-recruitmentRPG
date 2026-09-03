# Bootstrap DB Dev

Script di folder ini dijalankan **manual, sekali**, saat menyiapkan mesin dev baru.

**Ini BUKAN migrasi.** Tidak dicatat di `SCHEMA_MIGRATIONS`, tidak dijalankan oleh
`php tools/migrate.php`. Tugasnya cuma: bikin **database kosong** + **login aplikasi**
`erec_app` + hak DDL di DB itu. Isi tabel datang belakangan dari
`database/migrations/` lewat `php tools/migrate.php up`.

| File | Untuk |
|---|---|
| `create_dev_db.TEMPLATE.sql` | contoh — ganti `<NAMA>` |
| `create_dev_db_KIKI.sql` | database Kiki (`RPG_EREC_DEV_KIKI`) |
| `create_dev_db_KAHFI.sql` | database Kahfi (`RPG_EREC_DEV_KAHFI`) |

## Cara jalan

SSMS (buka file, F5) atau `sqlcmd`, login sebagai `sa` / anggota `sysadmin`
(cukup sekali seumur hidup mesin itu):

```
sqlcmd -S localhost -U sa -P <password_sa> -i database\bootstrap\create_dev_db_KAHFI.sql
```

**Sebelum jalan:** buka file, ganti `MASUKKAN_PASSWORD_KUAT_DI_SINI` dengan password
pilihanmu. Password yang sama dipakai di `tools/koneksi.local.php` (yang di-gitignore).
**Jangan commit password asli ke file bootstrap ini** — biarkan tetap placeholder.

## Kalau dua DB dev berbagi satu instance

Jalankan script orang kedua apa adanya. Blok `CREATE LOGIN erec_app` otomatis
dilewati `IF NOT EXISTS` karena login-nya sudah dibuat script orang pertama —
password login tetap yang dari script pertama.

## Instance harus Mixed Mode

`sqlsrv` konek pakai UID/PWD. SSMS → klik-kanan instance → **Properties → Security**
→ pilih **"SQL Server and Windows Authentication mode"**. Kalau baru diganti,
**restart service SQL Server**.

## Kalau DB dev kacau

Aman di-buang total dan dibangun ulang — semua struktur ada di file migrasi:

```sql
ALTER DATABASE RPG_EREC_DEV_XXX SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
DROP DATABASE RPG_EREC_DEV_XXX;
```
lalu jalankan lagi script bootstrap + `php tools/migrate.php up`.
