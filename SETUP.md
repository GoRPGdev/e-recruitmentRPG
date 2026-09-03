# Setup Lingkungan Dev — E-Recruitment RPG

Checklist menyiapkan **satu mesin dev dari nol**. Ikuti berurutan.
Selesai kalau `php tools/test-koneksi.php` menampilkan **`GERBANG FASE 0 TERBUKA`**.

> Kombinasi yang **sudah terbukti jalan** (mesin Kiki, 3 Sep 2026):
> PHP 7.4.33 NTS x64 · `sqlsrv`/`pdo_sqlsrv` 5.9.0 · ODBC Driver 17.7 → SQL Server 2008 R2 (10.50.1600).

---

## 0. Prasyarat

- Windows x64
- Git
- Akses ke **SQL Server 2008 R2** — dua opsi:

  | Opsi | Kapan dipakai | Yang perlu diinstal |
  |---|---|---|
  | **A. Instance bersama** | mesin dev ada di LAN kantor yang sama dengan instance yang sudah jalan | tidak perlu install SQL Server — cukup bikin database sendiri di instance itu |
  | **B. Instance lokal** | mesin dev berdiri sendiri / tidak selalu terhubung ke kantor | install SQL Server 2008 R2 (Express/Developer) di mesin sendiri, **wajib Mixed Mode auth** |

  Fase 0 **tidak bisa** divalidasi tanpa SQL Server 2008 R2 asli — versi lain akan meloloskan T-SQL yang seharusnya ditolak.

---

## 1. PHP 7.4 (NTS x64)

PHP 7.4 sudah EOL, ambil dari arsip resmi:

- <https://windows.php.net/downloads/releases/archives/php-7.4.33-nts-Win32-vc15-x64.zip>
- Extract ke `C:\php7.4`

> NTS cukup untuk lolos gerbang lewat CLI. Kalau nanti runtime **produksi** = Apache mod_php, butuh build **TS** — itu keputusan terpisah di Fase 0 (§ "Putuskan runtime produksi" di `docs/RENCANA_DEVELOPMENT.md`).

## 2. Driver `sqlsrv` 5.9 (satu-satunya seri untuk PHP 7.4)

- <https://windows.php.net/downloads/pecl/releases/sqlsrv/5.9.0/php_sqlsrv-5.9.0-7.4-nts-vc15-x64.zip>
- <https://windows.php.net/downloads/pecl/releases/pdo_sqlsrv/5.9.0/php_pdo_sqlsrv-5.9.0-7.4-nts-vc15-x64.zip>

Dari tiap zip ambil `php_sqlsrv.dll` / `php_pdo_sqlsrv.dll` → taruh di `C:\php7.4\ext\`.
(File `.pdb` tidak perlu.)

## 3. Microsoft ODBC Driver 17 for SQL Server (17.4+)

- Cari **"Microsoft ODBC Driver 17 for SQL Server"**, install versi **x64**.
- **JANGAN Driver 18** — tidak mendukung SQL Server 2008 R2, koneksi akan gagal.
- Cek: PowerShell → `Get-OdbcDriver | ? { $_.Name -like "*17*" }`

## 4. `php.ini`

```
copy C:\php7.4\php.ini-development C:\php7.4\php.ini
```

Edit `C:\php7.4\php.ini`:

- hilangkan `;` di depan → `extension_dir = "ext"`
- tambahkan di bagian bawah:

  ```ini
  extension=sqlsrv
  extension=pdo_sqlsrv
  extension=mbstring
  extension=openssl
  extension=curl
  extension=fileinfo
  date.timezone = Asia/Jakarta
  ```

## 5. PATH

Tambahkan `C:\php7.4` ke **User environment variable `Path`**, lalu buka terminal **baru**.

Cek:
```
php -v      -> PHP 7.4.33 ... NTS ... x64
php -m      -> ada "sqlsrv" dan "pdo_sqlsrv"
```

## 6. Clone repo

```
git clone https://github.com/hilqudz/e-recruitmentRPG.git e-recruitment
cd e-recruitment
```

> SSH belum di-set untuk repo ini. Kalau mau SSH: `ssh-keygen -t ed25519 -C "email"`, tambahkan isi `~/.ssh/id_ed25519.pub` ke GitHub → Settings → SSH keys, lalu `git remote set-url origin git@github.com:hilqudz/e-recruitmentRPG.git`.

## 7. Buat database dev sendiri (sekali)

Jalankan sebagai `sa` / anggota `sysadmin`:

```
sqlcmd -S <host> -U sa -P <pass> -i database\bootstrap\create_dev_db_KAHFI.sql
```

- Kiki  → `database/bootstrap/create_dev_db_KIKI.sql`
- Kahfi → `database/bootstrap/create_dev_db_KAHFI.sql`

**Sebelum jalan:** buka file, ganti `MASUKKAN_PASSWORD_KUAT_DI_SINI` dengan password pilihan sendiri. Password yang sama nanti dipakai di `tools/koneksi.local.php`.

Script ini **bukan migrasi** — dijalankan manual, sekali, tidak dicatat di `SCHEMA_MIGRATIONS`. Isinya cuma: database kosong + login `erec_app` + hak DDL di DB itu saja. Detail di `database/bootstrap/README.md`.

**Opsi A (instance bersama):** blok `CREATE LOGIN erec_app` otomatis dilewati kalau login-nya sudah ada. Kalau koneksi remote gagal, di mesin yang punya instance: aktifkan **TCP/IP** di SQL Server Configuration Manager, jalankan service **SQL Server Browser**, buka **port 1433** di firewall, restart service SQL Server.

## 8. Config koneksi lokal

```
copy tools\koneksi.local.sample.php tools\koneksi.local.php
```

| field | Kiki | Kahfi — instance lokal | Kahfi — instance bersama |
|---|---|---|---|
| `host` | `localhost` | `localhost` | `DESKTOP-E34JC3H` atau `192.168.x.x` |
| `database` | `RPG_EREC_DEV_KIKI` | `RPG_EREC_DEV_KAHFI` | `RPG_EREC_DEV_KAHFI` |
| `user` | `erec_app` | `erec_app` | `erec_app` |
| `password` | (dari script bootstrap) | (dari script bootstrap) | (password `erec_app` di instance bersama) |
| `storage` | folder di luar webroot, mis. `D:\erecruitment-storage` | folder sendiri | folder sendiri |

`tools/koneksi.local.php` **tidak ikut Git** (`.gitignore`). **Jangan pernah commit password.**

## 9. Gerbang Fase 0

```
php tools/test-koneksi.php
```

Harus berakhir: **`>>> GERBANG FASE 0 TERBUKA`**.

> Peringatan `Driver ODBC tidak dikenali` dengan `DriverVer: 17.xx` **boleh diabaikan** — skrip membaca `DriverName` yang tidak selalu diisi oleh `sqlsrv` 5.9; yang menentukan adalah `DriverVer`, dan 17.4+ sudah benar.

---

## 10. Loop harian setelah setup

| Langkah | Siapa | Perintah |
|---|---|---|
| Butuh tabel / kolom baru | siapa saja | tulis file baru `database/migrations/YYYYMMDD_HHMM__deskripsi.sql` |
| Uji di DB sendiri | penulis | `php tools/migrate.php up` |
| Bagikan | penulis | `git add` file itu → `git commit` → `git push` |
| Ikut berubah | yang lain | `git pull --rebase origin main` → `php tools/migrate.php up` |
| Cek sinkron | siapa saja | `php tools/migrate.php status` |
| Deploy ulang stored procedure | siapa saja | `php tools/migrate.php proc` |

`migrate.php` mencatat tiap file yang dijalankan ke `SCHEMA_MIGRATIONS` (nama file + hash isi). Efeknya: file yang sudah jalan tidak diulang; file yang **diedit setelah** dijalankan → runner berhenti dan protes.

### Aturan yang menjaga dua DB tetap identik

1. **File migrasi yang sudah di-push TIDAK PERNAH diedit.** Salah? Buat file koreksi baru (`..__perbaiki_xxx.sql`).
2. **Nama file pakai timestamp**, bukan `V001`/`V002` — dua orang bikin migrasi di hari sama, jam beda → tidak tabrakan.
3. **Semua perubahan skema lewat file migrasi.** Prototyping di SSMS boleh, tapi buang lalu tulis ulang sebagai file.
4. **Migrasi master pertama ditulis berdua, satu layar** — semua modul bergantung ke situ.

---

## 11. Alur Git

```
main                          selalu jalan, hanya lewat Pull Request
 ├─ feat/master-requisition    Kahfi
 └─ feat/intake-form           Kiki
```

```
git pull --rebase origin main      # sebelum mulai & sebelum push
git push origin feat/<modul>
```

Branch per modul dipecah **setelah** migrasi master pertama masuk `main` dan kedua DB sudah `migrate.php up`.
