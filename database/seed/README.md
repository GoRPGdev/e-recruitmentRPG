# Seed Data

Data awal yang diambil dari nilai yang benar-benar dipakai HR
(lihat `docs/ERD_Terkoreksi_E-Recruitment_RPG.md` §8), bukan karangan:

- `M_STAGE` — 10 tahap inti + tipe_tahap
- `M_FLOW` + `M_FLOW_STAGE` — HQ_MANAGER, HQ_STAFF, HQ_STAFF_KRUSIAL, MP_OUTLET
- `M_REMARKS` — opsi dropdown per tahap + efek_status
- `M_ROLES`, `M_PERMISSIONS`, `M_ROLE_PERMISSIONS`
- `M_OUTLET`, `M_DEPARTEMEN`, `M_POSISI`, `M_DOKUMEN`, `M_CHANNEL`

Seed ditulis sebagai file migrasi biasa di `database/migrations/`
supaya ikut tercatat di `SCHEMA_MIGRATIONS`. Folder ini untuk
data contoh/uji yang TIDAK ikut ke produksi.
