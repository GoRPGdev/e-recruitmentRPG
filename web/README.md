# web/ — Aplikasi CodeIgniter 3

CodeIgniter **3.1.13** (framework di `system/`, ikut di-commit — target server lama, tanpa Composer).

## Kerangka Fase 1 (sudah ada)

| Bagian | File |
|---|---|
| Base controller | `application/core/MY_Controller.php` — `MY_Controller` (publik) & `Secured_Controller` (wajib login) |
| Login | `application/controllers/Auth.php` + `models/Auth_model.php` (`sp_Login`, `sp_GetUserPermissions`) |
| Contoh halaman terproteksi | `application/controllers/Dashboard.php` |
| RBAC | `application/helpers/rbac_helper.php` — `has_permission()`, `require_permission()`, `current_user()` |
| Layout + view | `application/views/layouts/main.php`, `views/auth/`, `views/dashboard/` |

Permission efektif diambil sekali saat login lalu disimpan di session.
Scoping "req sendiri" / "yang dia ikuti" ditegakkan di query, bukan di helper.

> Paginasi daftar (kandidat, MPR, dll.) **wajib pola `ROW_NUMBER()` di SP** —
> jangan pakai `$this->db->limit()` (driver sqlsrv CI3 tidak konsisten).
> Lihat `tools/test-koneksi.php` bagian 8.

## Setup di mesin sendiri

```bash
copy application\config\database.sample.php application\config\database.php
copy application\config\config.sample.php   application\config\config.php
```
- `database.php` — isi host/user/password/database (sama seperti `tools/koneksi.local.php`).
- `config.php` — isi `$config['encryption_key']` dengan 32 char hex acak, mis.:
  ```bash
  php -r "echo bin2hex(random_bytes(16)), PHP_EOL;"
  ```
Keduanya **di-gitignore**.

## Bikin user pertama (belum ada halaman registrasi)

```bash
php tools/mkuser.php admin rahasia123 IT_ADMIN
```

## Jalankan (dev)

```bash
php -S localhost:8080 -t web
```
Buka `http://localhost:8080/` → form login → dashboard menampilkan permission efektif.
Produksi: Apache/IIS dengan document root = `web/` (runtime final diputuskan di Fase 0 checklist).
