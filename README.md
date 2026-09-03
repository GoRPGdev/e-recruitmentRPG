# E-Recruitment Ratu Pertiwi Group

Sistem rekrutmen terintegrasi untuk HQ dan MP/outlet.
CodeIgniter 3 · PHP 7.4 · SQL Server 2008 R2 · Database-First.

## Mulai dari mana

1. **Baca dulu** — `CLAUDE.md` (aturan main & batasan T-SQL) dan
   `docs/RENCANA_DEVELOPMENT.md` (fase, checklist, pembagian tugas).
2. **Siapkan koneksi**
   ```bash
   cp tools/koneksi.local.sample.php tools/koneksi.local.php
   # isi host / database / user / password
   ```
3. **Buka gerbang Fase 0** — jangan menulis Stored Procedure sebelum ini hijau:
   ```bash
   php tools/test-koneksi.php
   ```
4. **Jalankan migrasi**
   ```bash
   php tools/migrate.php status
   php tools/migrate.php up
   ```

## Struktur

```
CLAUDE.md                 Aturan main untuk Claude Code & developer
docs/                     ERD, rencana development, formulir, preview UI
database/migrations/      Migrasi berurut (tidak pernah diedit setelah dijalankan)
database/procedures/      Stored procedure (idempotent, boleh di-deploy ulang)
database/seed/            Data contoh untuk pengujian
tools/test-koneksi.php    Validasi driver & koneksi — gerbang Fase 0
tools/migrate.php         Runner migrasi (mencatat ke SCHEMA_MIGRATIONS)
web/                      Aplikasi CodeIgniter 3
```

## Lingkungan

| Komponen | Versi | Catatan |
|---|---|---|
| PHP | 7.4.x | TS atau NTS mengikuti Apache |
| sqlsrv / pdo_sqlsrv | 5.9 | Satu-satunya seri yang mendukung PHP 7.4 |
| ODBC Driver | **17.4+** | **JANGAN 18** — tidak mendukung SQL Server 2008 R2 |
| SQL Server | 2008 R2 (10.50.x) | |

## Database dev

Masing-masing punya database sendiri, sinkron lewat file migrasi di Git —
bukan lewat backup/restore.

| Orang | Database |
|---|---|
| Kiki | `RPG_EREC_DEV_KIKI` |
| Kahfi | `RPG_EREC_DEV_KAHFI` |

## Alur Git

```
main                          selalu jalan, lewat Pull Request
 ├─ feat/master-requisition    Kahfi
 └─ feat/intake-form           Kiki
```

```bash
git pull --rebase origin main      # sebelum mulai & sebelum push
git push origin feat/<modul>
```

Migrasi pakai timestamp supaya tidak tabrakan nomor.
File migrasi yang sudah di-push tidak pernah diedit.

## Preview UI

`docs/preview.html` — prototipe klik-able dengan data dummy yang sudah
divalidasi ke HR. Buka langsung di browser. CSS-nya dipakai ulang untuk
layout aplikasi di Fase 1.
