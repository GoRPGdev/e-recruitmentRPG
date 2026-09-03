# Seed Data

## Seed SISTEM — di `database/migrations/`, ikut ke produksi

Nilai yang benar-benar dipakai HR (`docs/ERD_Terkoreksi_E-Recruitment_RPG.md` §8),
bukan karangan. Ditulis sebagai migrasi biasa supaya tercatat di
`SCHEMA_MIGRATIONS` dan jalan di semua environment:

- `20260908_1200__seed_sistem.sql` —
  `M_ROLES` (6), `M_PERMISSIONS` (11), `M_ROLE_PERMISSIONS` (25),
  `M_CHANNEL` (5), `M_DOKUMEN` (5),
  `M_STAGE` (10 + tipe_tahap), `M_FLOW` (4), `M_FLOW_STAGE` (29 — urutan tahap),
  `M_REMARKS` (15 + efek_status)

Ini **kondisi awal**. HR tetap bisa ubah urutan tahap / remark / SLA lewat
Flow Builder (Fase 3), tambah/nonaktif dokumen & channel lewat Master Data.

## Data UJI — di folder ini, TIDAK ikut ke produksi

- `dev_organisasi.sql` — `M_DEPARTEMEN`, `M_OUTLET`, `M_POSISI` contoh +
  `role_pic`/`sla_hari` per tahap. Baris karangan (kode outlet dari ERD §7.1).
  **Bukan migrasi.** Dev jalankan manual kalau butuh data untuk dikembangkan:

  ```bash
  sqlcmd -S localhost -U erec_app -P <pwd> -d RPG_EREC_DEV_KIKI -i database\seed\dev_organisasi.sql
  ```

  Produksi: outlet/departemen/posisi asli dimasukkan HR lewat UI Master Data
  (tambah = INSERT, hapus = `is_aktif = 0`), atau lewat migrasi
  `seed_organisasi_prod` khusus menjelang go-live dengan daftar resmi HR.
