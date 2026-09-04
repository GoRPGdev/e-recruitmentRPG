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
| `BUAT_MPR` (buat & submit requisition) | – | ✅ | ✅ | ✅ | – | – |
| `EXPORT` | – | ✅ | ✅ | – | – | – |
| `KELOLA_REKRUTMEN` (link form, token, entry, import, pipeline, MPR, seleksi) | – | ✅ | ✅ | – | – | – |
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
| Dokumen wajib per tahap | `Flowbuilder::flow_docs/set_flow_doc` | `require_permission('EDIT_FLOW_TEMPLATE')` (via constructor) | — |
| **Export kandidat** | `Export::candidates` | `require_permission('EXPORT')` | ✅ `GAJI` bila kolom gaji ikut (`LIHAT_GAJI_PELAMAR`), `FINANSIAL` bila no. rekening ikut (`LIHAT_FINANSIAL`) — 1 baris / export, `id_referensi = NULL` |
| Flow Builder (flow / **tahap M_STAGE** / remark) | `Flowbuilder::*` (`stages`/`save_stage`/`toggle_stage`, `remarks`, `edit`, …) | `require_permission('EDIT_FLOW_TEMPLATE')` | — |
| Import file portal | `Import::*` | `require_permission('KELOLA_REKRUTMEN')` | — |
| Entry manual | `Manual::*` | `require_permission('KELOLA_REKRUTMEN')` | — |
| Master Data CRUD | `Master::*` | `require_permission('KELOLA_REKRUTMEN')` | — |
| Kelola link form & token | `Postings::*` | `require_permission('KELOLA_REKRUTMEN')` | — |
| Pipeline (lihat) | `Pipeline::index` | `require_permission('LIHAT_KANDIDAT')` | ✅ `GAJI` bila ada data offer & user punya `LIHAT_GAJI` |
| Pipeline aksi (advance/kontak/sisip tahap) | `Pipeline::advance/contact/insert_stage` | `require_permission('KELOLA_REKRUTMEN')` | — |
| **Kelola Seleksi (Interview & Psikotes)** | `Pipeline::save_interview/save_psikotes` | `require_permission('KELOLA_REKRUTMEN')` | — |
| **Kelola Penawaran Kerja (Offer)** | `Pipeline::save_offer` | `require_permission('KELOLA_REKRUTMEN')` | ✅ `GAJI` bila nominal gaji disimpan/diubah (`LIHAT_GAJI`) |
| MPR list | `Requisitions::index` | Terbuka bagi seluruh peran login (G4 ditutup HR) | — |
| MPR view | `Requisitions::view` | Terbuka bagi seluruh peran login | ✅ `GAJI` bila range gaji tampil (`can_sensitif('GAJI')`), tercatat ke `ACCESS_LOG_SENSITIF` |
| **MPR create / submit** | `Requisitions::create/submit` | `require_permission('BUAT_MPR')` (G6) | — |
| MPR post job | `Requisitions::post_job` | `require_permission('KELOLA_REKRUTMEN')` | — |
| **Catat keputusan BOD** | `Requisitions::approve` | `require_any_permission(['APPROVE', 'KELOLA_REKRUTMEN'])` (G1) | — |

Kolom gaji di export dipilih `Dashboard_model::candidates_export($f, $perms)` —
`gaji_terakhir`/`gaji_diharapkan` hanya bila `LIHAT_GAJI_PELAMAR`, `no_rekening`/`nama_bank`
hanya bila `LIHAT_FINANSIAL`. Tanpa permission, kolomnya tidak ikut (bukan kosong).

---

## 3. Status Penyelesaian Gap & Temuan

| # | Temuan | Status | Pemilik | Solusi yang Diterapkan |
|---|---|:---:|---|---|
| **G1** | `Requisitions::approve` semula hanya dijaga `KELOLA_REKRUTMEN` sehingga BOD tertolak. | **✅ Selesai** | **Kahfi** | Diproteksi dengan `$this->require_any_permission(['APPROVE', 'KELOLA_REKRUTMEN'])`. Telah diverifikasi via probe (BOD & HR lolos). |
| **G2** | Range gaji pada view & create MPR belum ber-gate data sensitif. | **✅ Selesai** | **Kahfi** | Ditutup dengan `can_sensitif('GAJI')`. Saat dibuka di `Requisitions::view`, memanggil `log_akses_sensitif('GAJI', id_req)`. Input di `create.php` hanya muncul untuk yang berhak. |
| **G3** | Layar detail kandidat per-orang (kesehatan, gaji pelamar). | 🟡 Menunggu | Kiki / Tahap lanjut | Kolom gaji pelamar & kesehatan saat ini sudah terlindungi di export dan endpoint dokumen. |
| **G4** | Scoping baris daftar MPR. | **✅ Ditutup** | HR / Fachri | Keputusan HR 2026-09-04: Seluruh user login boleh melihat daftar MPR. |
| **G4b** | Scoping kandidat dept untuk USER_DEPT. | **✅ Selesai** | **Kahfi & Kiki** | **Keputusan HR 2026-09-04: USER_DEPT hanya lihat kandidat dari MPR yang `id_departemen` = departemen dia**. **Implementasi Selesai:** Helper `current_user_dept()` diterapkan pada: (1) `Pipeline::_get_req_scoped` (403 untuk req beda dept), (2) `requisitions/index.php` (link pipeline hanya tampil untuk dept pemohon), (3) `Requisitions::create` (filter posisi & validasi submit), (4) `Documents` (list, verify, checklist, open ber-gate dept), (5) `Dashboard` & `funnel_trend` (agregat metrik terkunci ke dept user). |
| **G5** | VIEWER buka dashboard. | **✅ Ditutup** | HR / Fachri | Keputusan HR 2026-09-04: VIEWER diizinkan memantau dashboard. |
| **G6** | Pengajuan MPR (`create`/`submit`) terbuka untuk semua role. | **✅ Selesai** | **Kahfi & Kiki** | Migrasi `20260910_1000__perm_buat_mpr.sql` (Kiki) + Guard `require_permission('BUAT_MPR')` di controller (Kahfi). Probe: VIEWER, BOD, IT_ADMIN tertolak 403. |
| **G7** | `documents/flow_docs` tertolak 403 untuk IT_ADMIN. | **✅ Ditutup** | **Kiki** | `flow_docs`/`set_flow_doc` dipindah ke `Flowbuilder` (`EDIT_FLOW_TEMPLATE`). View `flow/docs.php`. IT_ADMIN lolos 200. |

---

## 3b. Hasil probe otomatis — 2026-09-04 (Terbaru)

Diuji via probe otomatis (`tools/rbac-probe.sh` / `run_rbac_probe.php`) terhadap 6 user demo aktif:

| endpoint \ peran | IT_ADMIN | HR_ADMIN | HR_SPV | USER_DEPT | BOD | VIEWER | Penilaian & Status |
|---|:-:|:-:|:-:|:-:|:-:|:-:|---|
| `dashboard` | 403 | 200 | 200 | 200 | 200 | 200 | ✅ Sesuai keputusan HR (G5 ditutup) |
| `documents` | 403 | 200 | 200 | 200 | 200 | 403 | ✅ VIEWER tertolak (tak punya `LIHAT_CV`) |
| `requisitions` | 200 | 200 | 200 | 200 | 200 | 200 | ✅ Sesuai keputusan HR (G4 ditutup) |
| `requisitions/create` | **403** | **200** | **200** | **200** | **403** | **403** | ✅ **G6 Berhasil** (Hanya `BUAT_MPR`) |
| `requisitions/approve/999` | **403** | **404** | **404** | **403** | **404** | **403** | ✅ **G1 Berhasil** (BOD & HR lolos guard) |
| `pipeline/index/1` | 403 | 200 | 200 | 200 | 200 | 200 | ✅ Lolos bagi pemegang `LIHAT_KANDIDAT` |
| `pipeline/index/2` (Dept Luar) | 403 | 200 | 200 | **403** | 200 | 200 | ✅ **G4b Berhasil** (USER_DEPT 403, BOD/HR 200) |
| `master` | 403 | 200 | 200 | 403 | 403 | 403 | ✅ Khusus HR (`KELOLA_REKRUTMEN`) |
| `import` / `postings` / `export` | 403 | 200 | 200 | 403 | 403 | 403 | ✅ Khusus HR (`KELOLA_REKRUTMEN` / `EXPORT`) |
| `flowbuilder` | 200 | 403 | 403 | 403 | 403 | 403 | ✅ Khusus IT_ADMIN (`EDIT_FLOW_TEMPLATE`) |
| `flowbuilder/flow_docs` | **200** | **403** | **403** | **403** | **403** | **403** | ✅ **G7 Berhasil** (IT_ADMIN 200) |
| `documents/open` KTP / Rekening | 403 | 200 / **403** | 200 / 200 | 403 | 403 | 403 | ✅ `gate_sensitif` + `ACCESS_LOG_SENSITIF` |

**Beres:** G1 (Kahfi), G2 (Kahfi), G4 (ditutup HR), G4b (Kahfi & Kiki), G5 (ditutup HR), G6 (Kahfi & Kiki), G7 (Kiki).
**Menunggu:** G3 (layar detail per orang).

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
- [x] Dashboard & daftar kandidat → hanya menampilkan **requisition/kandidat milik dept-nya** (G4b ✅)
- [x] Akses pipeline/dokumen dept lain → 403 Forbidden (G4b ✅)
- [ ] Buka dokumen IDENTITAS/FINANSIAL → 403
- [ ] Export → 403
- [x] MPR create → bisa buat MPR dept sendiri (G6); posisi dept lain ditolak (G4b)
- [ ] MPR approve, Pipeline aksi → 403

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
