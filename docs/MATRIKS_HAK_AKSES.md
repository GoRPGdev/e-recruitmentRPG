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
| MPR list/create/view/submit/post | `Requisitions::*` | `require_permission('KELOLA_REKRUTMEN')` | — |
| **Catat keputusan BOD** | `Requisitions::approve` | `require_permission('KELOLA_REKRUTMEN')` ⚠️ lihat gap G1 | — |

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
| **G4** | Scoping baris "req sendiri" untuk USER_DEPT & BOD belum diverifikasi menyeluruh (dicek di query, bukan di controller). | USER_DEPT mungkin lihat kandidat dept lain | **Kahfi** (`Requisition_model`, `Dashboard_model`) | Pastikan filter `id_user_pemohon = <user>` / `id_departemen` di semua list untuk peran non-HR. Uji di §4. |
| **G5** | `VIEWER` cuma punya `LIHAT_KANDIDAT` tapi `Dashboard` butuh persis itu → VIEWER bisa buka dashboard operasional penuh. Perlu konfirmasi HR apakah VIEWER memang boleh. | Mungkin over-exposure | konfirmasi HR (Mas Fachri) | Kalau tidak: buat permission `LIHAT_DASHBOARD` terpisah, atau cabut akses `Dashboard` dari VIEWER. |

---

## 4. Checklist uji per peran (manual, sebelum go-live)

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
