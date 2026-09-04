# Matriks Hak Akses — E-Recruitment RPG

**Fase 4 · Keamanan & Kepatuhan.** Acuan: `CLAUDE.md` bagian "Tingkat akses data",
ERD §10, RENCANA §1.1. Dokumen ini = satu sumber kebenaran untuk RBAC + apa yang
wajib dicatat ke `ACCESS_LOG_SENSITIF`.

Diperbarui: 2026-09-04 (Kiki, `feat/rbac-akses`).

---

## 1. Peran & permission (kondisi `main` sekarang)

Sumber: `M_ROLE_PERMISSIONS` (seed `20260908_1200` + `20260909_1000`).

| Permission | IT_ADMIN | HR_ADMIN | HR_SPV | USER_DEPT | BOD | VIEWER |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| `LIHAT_KANDIDAT` | – | ✅ | ✅ | ✅ | ✅ | ✅ |
| `LIHAT_CV` | – | ✅ | ✅ | ✅ | ✅ | – |
| `LIHAT_INTERVIEW` | – | ✅ | ✅ | ✅ | ✅ | – |
| `LIHAT_DOK_IDENTITAS` (KTP/KK/Ijazah/NPWP) | – | ✅ | ✅ | – | – | – |
| `LIHAT_GAJI_PELAMAR` (gaji terakhir & harapan) | – | ✅ | ✅ | – | – | – |
| `LIHAT_FINANSIAL` (no. rekening) | – | – | ✅ | – | – | – |
| `LIHAT_GAJI` (range gaji & offer) | – | – | ✅ | – | ✅ | – |
| `LIHAT_KESEHATAN` (riwayat penyakit) | – | – | ✅ | – | – | – |
| `APPROVE` (keputusan BOD) | – | – | – | – | ✅ | – |
| `EXPORT` | – | ✅ | ✅ | – | – | – |
| `KELOLA_REKRUTMEN` (link form, token, entry, import, pipeline, MPR) | – | ✅ | ✅ | – | – | – |
| `EDIT_FLOW_TEMPLATE` (flow/stage/remark/dok wajib) | ✅ | – | –¹ | – | – | – |

¹ `EDIT_FLOW_TEMPLATE` untuk HR_SPV **sengaja belum di-grant** — dibuka setelah 2 siklus / 2 bulan (ERD §10.1). Cukup 1 INSERT ke `M_ROLE_PERMISSIONS` nanti.

**Tiga tingkat data pribadi** (CLAUDE.md):
1. **Umum** — daftar kandidat, CV, interview → `LIHAT_KANDIDAT` / `LIHAT_CV` / `LIHAT_INTERVIEW`. Tidak dicatat per-akses.
2. **Dokumen identitas** — KTP/KK/Ijazah/NPWP → `LIHAT_DOK_IDENTITAS`. **Dicatat** `ACCESS_LOG_SENSITIF` jenis `DOK_IDENTITAS`.
3. **Finansial & khusus** — rekening (`LIHAT_FINANSIAL`), gaji (`LIHAT_GAJI` / `LIHAT_GAJI_PELAMAR`), kesehatan (`LIHAT_KESEHATAN`). **Dicatat** jenis `FINANSIAL` / `GAJI` / `KESEHATAN`.

---

## 2. Titik penegakan di kode

`Secured_Controller` menaruh `permissions` dari session (diisi `sp_GetUserPermissions`
saat login). Helper: `has_permission()`, `has_any_permission()`, `require_permission()`,
`require_any_permission()`, `require_all_permissions()`, dan untuk data sensitif
`can_sensitif()` / `gate_sensitif()` / `log_akses_sensitif()` (`helpers/rbac_helper.php`).

| Layar / aksi | Controller | Penjaga | Data sensitif → log |
|---|---|---|---|
| Dashboard, funnel, aging | `Dashboard::*` | `require_permission('LIHAT_KANDIDAT')` | — (agregat, tak ada identitas) |
| Daftar & verifikasi berkas | `Documents::index/verify/checklist` | `require_permission('LIHAT_CV')` | — |
| **Buka file dokumen** | `Documents::open` | `gate_sensitif('DOK_IDENTITAS'\|'FINANSIAL')` sesuai `M_DOKUMEN.tingkat_sensitif` | ✅ `DOK_IDENTITAS` / `FINANSIAL`, `id_referensi = id_cand_doc` |
| Atur dokumen wajib per tahap | `Documents::flow_docs/set_flow_doc` | `require_permission('EDIT_FLOW_TEMPLATE')` | — |
| **Export kandidat** | `Export::candidates` | `require_permission('EXPORT')` | ✅ `GAJI` bila kolom gaji ikut (`LIHAT_GAJI_PELAMAR`), `FINANSIAL` bila no. rekening ikut (`LIHAT_FINANSIAL`) — 1 baris / export, `id_referensi = NULL` |
| Flow Builder (flow/stage/remark) | `Flowbuilder::*` | `require_permission('EDIT_FLOW_TEMPLATE')` | — |
| Import file portal | `Import::*` | `require_permission('KELOLA_REKRUTMEN')` | — |
| Entry manual | `Manual::*` | `require_permission('KELOLA_REKRUTMEN')` | — |
| Master Data CRUD | `Master::*` | `require_permission('KELOLA_REKRUTMEN')` | — |
| Kelola link form & token | `Postings::*` | `require_permission('KELOLA_REKRUTMEN')` | — |
| Pipeline (lihat) | `Pipeline::index` | `require_permission('LIHAT_KANDIDAT')` | — |
| Pipeline aksi (advance/kontak/sisip tahap) | `Pipeline::advance/contact/insert_stage` | `require_permission('KELOLA_REKRUTMEN')` | — |
| MPR list / view | `Requisitions::index/view` | **tak ada penjaga** — semua user login (by design: "buat & ajukan semua user"), TAPI tak di-scope "req sendiri" ⚠️ G4 | — |
| MPR create / submit | `Requisitions::create/submit` | **tak ada penjaga** — termasuk VIEWER ⚠️ G6 | — |
| MPR post job | `Requisitions::post_job` | `require_permission('KELOLA_REKRUTMEN')` | — |
| **Catat keputusan BOD** | `Requisitions::approve` | `require_permission('KELOLA_REKRUTMEN')` ⚠️ G1 (harusnya `APPROVE`) | — |

Kolom gaji di export dipilih `Dashboard_model::candidates_export($f, $perms)` —
`gaji_terakhir`/`gaji_diharapkan` hanya bila `LIHAT_GAJI_PELAMAR`, `no_rekening`/`nama_bank`
hanya bila `LIHAT_FINANSIAL`. Tanpa permission, kolomnya tidak ikut (bukan kosong).

---

## 3. Gap / temuan yang harus dibereskan

| # | Temuan | Dampak | Pemilik | Rencana |
|---|---|---|---|---|
| **G1** | `Requisitions::approve` dijaga `KELOLA_REKRUTMEN`, **bukan `APPROVE`**. Akibatnya HR (punya `KELOLA_REKRUTMEN`) bisa mencatat keputusan BOD, dan **BOD sendiri tidak bisa** (tak punya `KELOLA_REKRUTMEN`). Terbalik. | Kontrol approval bocor | **Kahfi** | Ganti jadi `require_permission('APPROVE')`. `APPROVE` saat ini tak dipakai di mana pun. |
| **G2** | `range_gaji_min/max` di `requisitions/view.php` & `create.php` tampil tanpa cek `LIHAT_GAJI`. | USER_DEPT/HR_ADMIN lihat range gaji padahal tak berhak | **Kahfi** | Bungkus tampilan range gaji dengan `can_sensitif('GAJI')`; saat dibuka panggil `log_akses_sensitif('GAJI', id_req)`. |
| **G3** | Belum ada layar detail kandidat/lamaran. `riwayat_penyakit` (`CANDIDATE_HEALTH`), gaji pelamar, rekening **hanya keluar lewat export** — belum pernah tampil per-kandidat di layar. | `LIHAT_KESEHATAN` & sebagian `LIHAT_GAJI_PELAMAR` belum teruji di jalur layar | Kiki (nanti) / sesuai kebutuhan HR | Kalau layar detail dibuat: panel kesehatan dibungkus `gate_sensitif('KESEHATAN', id_kandidat)`, panel gaji pelamar `gate_sensitif('GAJI_PELAMAR', id_lamaran)`. |
| **G4** | **Terkonfirmasi (probe).** `/requisitions` menampilkan **SEMUA MPR ke semua peran**. | — | — | **✅ Ditutup (HR 2026-09-04): "BOD lihat aja", "VIEWER boleh lihat MPR".** Daftar MPR memang terbuka untuk semua peran login — tak perlu scope. Scoping baris kandidat/CV (`LIHAT_KANDIDAT` "req sendiri" di ERD) tetap terpisah & belum diverifikasi — lihat G4b. |
| **G4b** | Daftar **kandidat & CV** belum di-scope "req sendiri" untuk USER_DEPT. | USER_DEPT mungkin lihat kandidat dept lain | **Kahfi** (`Requisition_model`), **Kiki** (`Dashboard_model`) | Konfirmasi HR apakah USER_DEPT hanya boleh lihat kandidat dari MPR dept-nya. Kalau ya, filter `id_departemen` di list kandidat & funnel dashboard. |
| **G5** | **Terkonfirmasi (probe).** `/dashboard` → 200 untuk USER_DEPT, BOD, VIEWER. | — | — | **✅ Ditutup (HR 2026-09-04): "VIEWER boleh lihat dashboard".** Akses dashboard dengan `LIHAT_KANDIDAT` memang disengaja. |
| **G6** | **Terkonfirmasi (probe).** `requisitions/create` & `submit` → 200 untuk **VIEWER & BOD & IT_ADMIN**. | Peran non-pengaju bisa buat MPR | **Kahfi** (guard) + **Kiki** (migrasi permission) | **Keputusan HR 2026-09-04: yang boleh mengajukan MPR = USER_DEPT + HR_ADMIN (+ HR_SPV).** Rencana: permission baru `BUAT_MPR`, di-grant ke `USER_DEPT`, `HR_ADMIN`, `HR_SPV`; `Requisitions::create`/`submit` → `require_permission('BUAT_MPR')`. |
| **G7** | **Baru (probe).** `documents/flow_docs` (atur dokumen wajib per tahap = tugas `EDIT_FLOW_TEMPLATE`) → **403 untuk IT_ADMIN**, karena constructor `Documents` minta `LIHAT_CV`. | IT Admin tak bisa atur dokumen wajib per tahap | **Kiki** (`Documents.php`) | Pindah `flow_docs`/`set_flow_doc` ke `Flowbuilder`, atau angkat penjaga `LIHAT_CV` dari constructor ke per-method. |

### Keputusan HR — Mas Fachri, 2026-09-04
1. **BOD** cukup **lihat** MPR (tidak di-scope, tidak buat/ubah). → G4 ditutup.
2. **VIEWER** **boleh** buka dashboard. → G5 ditutup.
3. **Yang mengajukan MPR: USER_DEPT & HR_ADMIN** (HR_SPV ikut karena atasan HR). VIEWER hanya lihat. → G6 = tambah permission `BUAT_MPR`.

---

## 3b. Hasil probe otomatis — 2026-09-04

`bash tools/rbac-probe.sh` (server `php -S 127.0.0.1:8899`, user `uji_<role>`). GET saja.

| endpoint \ peran | IT_ADMIN | HR_ADMIN | HR_SPV | USER_DEPT | BOD | VIEWER | penilaian |
|---|:-:|:-:|:-:|:-:|:-:|:-:|---|
| `dashboard` | 403 | 200 | 200 | 200 | 200 | 200 | ⚠️ G5 (USER_DEPT/BOD/VIEWER) |
| `documents` | 403 | 200 | 200 | 200 | 200 | 403 | ✅ (VIEWER 403 = tak ada `LIHAT_CV`) |
| `requisitions` | 200 | 200 | 200 | 200 | 200 | 200 | ⚠️ G4 (tak di-scope) |
| `requisitions/create` | 200 | 200 | 200 | 200 | 200 | 200 | ⚠️ G6 (VIEWER bisa) |
| `requisitions/approve/999` | 403 | 404 | 404 | 403 | **403** | 403 | ⚠️ G1 (BOD 403, HR lolos) |
| `master` | 403 | 200 | 200 | 403 | 403 | 403 | ✅ |
| `import` / `postings` / `export/candidates` | 403 | 200 | 200 | 403 | 403 | 403 | ✅ |
| `flowbuilder` | 200 | 403 | 403 | 403 | 403 | 403 | ✅ |
| `documents/flow_docs` | **403** | 403 | 403 | 403 | 403 | 403 | ⚠️ G7 (IT_ADMIN harusnya 200) |
| `pipeline` (tanpa id) | 403 | 404 | 404 | 404 | 404 | 404 | ✅ penjaga jalan (IT_ADMIN 403; sisanya lolos lalu 404 karena butuh `id_req`) |
| `documents/open` KTP / Rekening | 403 | 200 / **403** | 200 / 200 | 403 | 403 | 403 | ✅ `gate_sensitif` + `ACCESS_LOG_SENSITIF` (diuji terpisah) |

**Beres:** semua penjaga `KELOLA_REKRUTMEN` / `EDIT_FLOW_TEMPLATE` / `EXPORT` / `LIHAT_CV` + gerbang dokumen sensitif.
**Ditutup oleh keputusan HR:** G4 (BOD lihat aja), G5 (VIEWER boleh dashboard).
**Perlu tindakan:** G1 (Kahfi), G2 (Kahfi), G6 (`BUAT_MPR` — Kiki migrasi + Kahfi guard), G7 (Kiki), G4b (perlu konfirmasi HR).

---

## 4. Checklist uji per peran (manual, sebelum go-live)

Status kode HTTP per endpoint sudah dicek otomatis di §3b (`bash tools/rbac-probe.sh`).
Checklist di bawah untuk hal yang butuh mata manusia: isi kolom, isi log, scoping baris.

Buat 1 user per peran: `php tools/mkuser.php uji_<role> <pwd> <KODE_ROLE>`
(role: `IT_ADMIN`, `HR_ADMIN`, `HR_SPV`, `USER_DEPT`, `BOD`, `VIEWER`).
Login sebagai masing-masing, jalankan tiap baris, catat **Sesuai / Tidak**.

### IT_ADMIN
- [ ] Flow Builder terbuka; bisa tambah/ubah stage & remark
- [ ] Dashboard / MPR / Pipeline / Berkas → **403** (tak punya `LIHAT_KANDIDAT` dst.)
- [ ] `documents/open` dokumen apa pun → 403

### HR_ADMIN
- [ ] Dashboard, MPR, Pipeline, Import, Entry Manual, Master Data → terbuka
- [ ] Buka dokumen **IDENTITAS** → tampil, muncul 1 baris `ACCESS_LOG_SENSITIF` jenis `DOK_IDENTITAS`
- [ ] Buka dokumen **FINANSIAL** → **403** (`LIHAT_FINANSIAL` tak ada)
- [ ] Export kandidat → file jadi, kolom **gaji terakhir/harapan ikut**, kolom **no. rekening TIDAK ikut**; muncul 1 baris log jenis `GAJI`
- [ ] Flow Builder → 403
- [ ] Catat keputusan BOD (`requisitions/approve`) → **seharusnya 403 setelah G1 dibetulkan** (sekarang masih lolos)

### HR_SPV
- [ ] Semua layar HR_ADMIN terbuka
- [ ] Buka dokumen **FINANSIAL** → tampil + log jenis `FINANSIAL`
- [ ] Export → kolom **no. rekening & gaji ikut**; muncul log `GAJI` **dan** `FINANSIAL`
- [ ] (bila layar kesehatan sudah ada) buka riwayat penyakit → tampil + log `KESEHATAN`
- [ ] Flow Builder → 403 (sampai grant `EDIT_FLOW_TEMPLATE` dibuka)

### USER_DEPT
- [ ] Dashboard & daftar kandidat → hanya menampilkan **requisition milik dept-nya** (G4)
- [ ] Buka dokumen IDENTITAS/FINANSIAL → 403
- [ ] Export → 403
- [ ] MPR create/approve, Pipeline aksi → 403

### BOD
- [ ] Daftar kandidat & CV requisition **miliknya** → terbuka (G4)
- [ ] `requisitions/approve` → terbuka **setelah G1** (sekarang malah 403)
- [ ] Range gaji & offer → tampil (`LIHAT_GAJI`)
- [ ] Dokumen IDENTITAS/FINANSIAL, Export, Flow Builder, Master → 403

### VIEWER
- [ ] Daftar kandidat → terbuka
- [ ] CV, dokumen, export, semua aksi → 403
- [ ] Dashboard → **konfirmasi ke HR** apakah boleh (G5)

### Cek log
- [ ] `SELECT jenis_data, COUNT(*) FROM ACCESS_LOG_SENSITIF GROUP BY jenis_data` — angka masuk akal
- [ ] Tidak ada baris `ACCESS_LOG_SENSITIF` dari peran yang seharusnya 403
