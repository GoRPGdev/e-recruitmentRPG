/* =========================================================================
   20260908_1200__seed_sistem.sql
   FASE 1 -- Seed data SISTEM (yang sudah pasti dari ERD sec.8 / sec.10).
   Acuan: ERD Terkoreksi v1.2 sec.8 & sec.10.

   Prasyarat: 20260908_1000 sudah jalan.

   TIDAK termasuk di sini (menunggu data HR -- lihat
   _draft/20260908_1230__seed_organisasi.sql):
     - M_DEPARTEMEN, M_OUTLET, M_POSISI baris riil
     - role_pic & sla_hari per M_FLOW_STAGE
     - matriks permission final kalau HR mau ubah

   Semua INSERT pakai  WHERE NOT EXISTS  -> aman kalau dijalankan ulang.
   [KONFIRMASI] daftar M_PERMISSIONS & matriks M_ROLE_PERMISSIONS di bawah
   diturunkan dari ERD sec.10 -- cek sekali lagi sebelum push.
   ========================================================================= */


/* ---- M_ROLES (ERD sec.7.1) ------------------------------------------------ */
INSERT INTO dbo.M_ROLES (kode_role, nama_role)
SELECT v.kode, v.nama
FROM (VALUES
    ('IT_ADMIN',  N'IT Admin'),
    ('HR_ADMIN',  N'HR Admin'),
    ('HR_SPV',    N'HR Supervisor'),
    ('USER_DEPT', N'User Departemen'),
    ('BOD',       N'Board of Directors'),
    ('VIEWER',    N'Viewer')
) v(kode, nama)
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_ROLES r WHERE r.kode_role = v.kode);
GO

/* ---- M_PERMISSIONS (ERD sec.10) ---------------------------------------- */
INSERT INTO dbo.M_PERMISSIONS (kode, nama)
SELECT v.kode, v.nama
FROM (VALUES
    ('LIHAT_KANDIDAT',     N'Lihat daftar & ringkasan kandidat'),
    ('LIHAT_CV',           N'Lihat CV'),
    ('LIHAT_INTERVIEW',    N'Lihat hasil & catatan interview'),
    ('LIHAT_DOK_IDENTITAS',N'Lihat KTP / KK / Ijazah / NPWP'),
    ('LIHAT_FINANSIAL',    N'Lihat nomor rekening'),
    ('LIHAT_GAJI',         N'Lihat range gaji & offer'),
    ('LIHAT_GAJI_PELAMAR', N'Lihat gaji terakhir & harapan pelamar'),
    ('LIHAT_KESEHATAN',    N'Lihat riwayat kesehatan kandidat'),
    ('APPROVE',            N'Catat keputusan approval BOD'),
    ('EXPORT',             N'Export data'),
    ('EDIT_FLOW_TEMPLATE', N'Ubah template flow / stage / remark')
) v(kode, nama)
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_PERMISSIONS p WHERE p.kode = v.kode);
GO

/* ---- M_ROLE_PERMISSIONS (ERD sec.10 -- matriks) ---------------------------
   EDIT_FLOW_TEMPLATE untuk HR_SPV SENGAJA belum di-grant (ERD sec.10.1:
   dibuka setelah 2 siklus / 2 bulan, cukup INSERT 1 baris nanti). */
INSERT INTO dbo.M_ROLE_PERMISSIONS (id_role, id_permission)
SELECT r.id_role, p.id_permission
FROM (VALUES
    ('IT_ADMIN', 'EDIT_FLOW_TEMPLATE'),

    ('HR_ADMIN', 'LIHAT_KANDIDAT'),
    ('HR_ADMIN', 'LIHAT_CV'),
    ('HR_ADMIN', 'LIHAT_INTERVIEW'),
    ('HR_ADMIN', 'LIHAT_DOK_IDENTITAS'),
    ('HR_ADMIN', 'LIHAT_GAJI_PELAMAR'),
    ('HR_ADMIN', 'EXPORT'),

    ('HR_SPV',   'LIHAT_KANDIDAT'),
    ('HR_SPV',   'LIHAT_CV'),
    ('HR_SPV',   'LIHAT_INTERVIEW'),
    ('HR_SPV',   'LIHAT_DOK_IDENTITAS'),
    ('HR_SPV',   'LIHAT_GAJI_PELAMAR'),
    ('HR_SPV',   'LIHAT_FINANSIAL'),
    ('HR_SPV',   'LIHAT_GAJI'),
    ('HR_SPV',   'LIHAT_KESEHATAN'),
    ('HR_SPV',   'EXPORT'),

    ('USER_DEPT','LIHAT_KANDIDAT'),
    ('USER_DEPT','LIHAT_CV'),
    ('USER_DEPT','LIHAT_INTERVIEW'),

    ('BOD',      'LIHAT_KANDIDAT'),
    ('BOD',      'LIHAT_CV'),
    ('BOD',      'LIHAT_INTERVIEW'),
    ('BOD',      'LIHAT_GAJI'),
    ('BOD',      'APPROVE'),

    ('VIEWER',   'LIHAT_KANDIDAT')
) v(role, perm)
JOIN dbo.M_ROLES       r ON r.kode_role = v.role
JOIN dbo.M_PERMISSIONS p ON p.kode      = v.perm
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.M_ROLE_PERMISSIONS rp
    WHERE rp.id_role = r.id_role AND rp.id_permission = p.id_permission
);
GO

/* ---- M_CHANNEL (ERD sec.7.1) ------------------------------------------- */
INSERT INTO dbo.M_CHANNEL (nama_channel, is_eksternal)
SELECT v.nama, v.eks
FROM (VALUES
    (N'JobStreet', 1),
    (N'Glints', 1),
    (N'Portal Sendiri', 0),
    (N'Referral', 0),
    (N'Walk-in', 0)
) v(nama, eks)
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_CHANNEL c WHERE c.nama_channel = v.nama);
GO

/* ---- M_DOKUMEN (ERD sec.7.1 + sec.10) -------------------------------------
   [KONFIRMASI] is_mandatory_default: KTP/KK/Ijazah = 1, NPWP/Rekening = 0. */
INSERT INTO dbo.M_DOKUMEN (nama_dokumen, kategori, tingkat_sensitif, is_mandatory_default)
SELECT v.nama, v.kat, v.sens, v.wajib
FROM (VALUES
    (N'KTP',     'IDENTITAS',  'IDENTITAS', 1),
    (N'KK',      'IDENTITAS',  'IDENTITAS', 1),
    (N'Ijazah',  'PENDIDIKAN', 'IDENTITAS', 1),
    (N'NPWP',    'FINANSIAL',  'IDENTITAS', 0),
    (N'Rekening','FINANSIAL',  'FINANSIAL', 0)
) v(nama, kat, sens, wajib)
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_DOKUMEN d WHERE d.nama_dokumen = v.nama);
GO

/* ---- M_STAGE (ERD sec.8) ------------------------------------------------
   is_sistem = 1 semua (tahap inti: boleh dinonaktifkan, tak boleh dihapus).
   is_terminal = 1 hanya ONBOARD. [KONFIRMASI] */
INSERT INTO dbo.M_STAGE (kode_stage, nama_tahap, tipe_tahap, is_sistem, is_terminal)
SELECT v.kode, v.nama, v.tipe, 1, v.term
FROM (VALUES
    ('SOURCING',    N'Sourcing',                  'SCREENING', 0),
    ('SCREENING_CV',N'Screening CV',              'SCREENING', 0),
    ('KONTAK_WA',   N'Kontak Kandidat',           'KONTAK',    0),
    ('INT_HR',      N'Interview HR',              'INTERVIEW', 0),
    ('FORM_PELAMAR',N'Pengisian Form Pelamar',    'FORM',      0),
    ('PSIKOTES',    N'Psikotes',                  'TEST',      0),
    ('INT_USER',    N'Interview User',            'INTERVIEW', 0),
    ('INT_BOD',     N'Interview BOD',             'INTERVIEW', 0),
    ('OFFER',       N'Penawaran & Negosiasi',     'OFFER',     0),
    ('ONBOARD',     N'Onboarding & Pemberkasan',  'ONBOARD',   1)
) v(kode, nama, tipe, term)
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_STAGE s WHERE s.kode_stage = v.kode);
GO

/* ---- M_FLOW (ERD sec.8) ----------------------------------------------- */
INSERT INTO dbo.M_FLOW (kode_flow, nama_flow, tipe_penempatan, maks_upaya_kontak)
SELECT v.kode, v.nama, v.tipe, v.maks
FROM (VALUES
    ('HQ_MANAGER',       N'Alur HQ - Manager',       'HQ',     3),
    ('HQ_STAFF',         N'Alur HQ - Staff',         'HQ',     3),
    ('HQ_STAFF_KRUSIAL', N'Alur HQ - Staff Krusial', 'HQ',     3),
    ('MP_OUTLET',        N'Alur MP / Outlet',        'OUTLET', 2)
) v(kode, nama, tipe, maks)
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_FLOW f WHERE f.kode_flow = v.kode);
GO

/* ---- M_FLOW_STAGE (ERD sec.8 -- urutan tahap per flow) -------------------
   role_pic & sla_hari NULL -> diisi lewat migrasi koreksi setelah HR konfirmasi
   (lihat _draft/20260908_1230__seed_organisasi.sql). PSIKOTES di HQ_MANAGER
   opsional (is_wajib = 0). */
INSERT INTO dbo.M_FLOW_STAGE (id_flow, id_stage, urutan, is_wajib)
SELECT f.id_flow, s.id_stage, v.urutan, v.wajib
FROM (VALUES
    ('HQ_MANAGER','SOURCING',     1, 1),
    ('HQ_MANAGER','SCREENING_CV', 2, 1),
    ('HQ_MANAGER','KONTAK_WA',    3, 1),
    ('HQ_MANAGER','INT_HR',       4, 1),
    ('HQ_MANAGER','FORM_PELAMAR', 5, 1),
    ('HQ_MANAGER','PSIKOTES',     6, 0),
    ('HQ_MANAGER','INT_USER',     7, 1),
    ('HQ_MANAGER','INT_BOD',      8, 1),
    ('HQ_MANAGER','OFFER',        9, 1),
    ('HQ_MANAGER','ONBOARD',     10, 1),

    ('HQ_STAFF','SOURCING',     1, 1),
    ('HQ_STAFF','SCREENING_CV', 2, 1),
    ('HQ_STAFF','KONTAK_WA',    3, 1),
    ('HQ_STAFF','INT_HR',       4, 1),
    ('HQ_STAFF','FORM_PELAMAR', 5, 1),
    ('HQ_STAFF','INT_USER',     6, 1),
    ('HQ_STAFF','OFFER',        7, 1),
    ('HQ_STAFF','ONBOARD',      8, 1),

    ('HQ_STAFF_KRUSIAL','SOURCING',     1, 1),
    ('HQ_STAFF_KRUSIAL','SCREENING_CV', 2, 1),
    ('HQ_STAFF_KRUSIAL','KONTAK_WA',    3, 1),
    ('HQ_STAFF_KRUSIAL','INT_HR',       4, 1),
    ('HQ_STAFF_KRUSIAL','FORM_PELAMAR', 5, 1),
    ('HQ_STAFF_KRUSIAL','INT_USER',     6, 1),
    ('HQ_STAFF_KRUSIAL','INT_BOD',      7, 1),
    ('HQ_STAFF_KRUSIAL','OFFER',        8, 1),
    ('HQ_STAFF_KRUSIAL','ONBOARD',      9, 1),

    ('MP_OUTLET','KONTAK_WA', 1, 1),
    ('MP_OUTLET','ONBOARD',   2, 1)
) v(flow, stage, urutan, wajib)
JOIN dbo.M_FLOW  f ON f.kode_flow  = v.flow
JOIN dbo.M_STAGE s ON s.kode_stage = v.stage
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.M_FLOW_STAGE fs
    WHERE fs.id_flow = f.id_flow AND fs.id_stage = s.id_stage
);
GO

/* ---- M_REMARKS (ERD sec.8 -- contoh per tahap) --------------------------
   "TOLAK (Unreachable)" di ERD -> efek_status = 'UNREACHABLE' (K7). */
INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan)
SELECT s.id_stage, v.kode, v.label, v.efek, v.urutan
FROM (VALUES
    ('SCREENING_CV','SCV_KUALIFIKASI', N'Kualifikasi tidak sesuai',            'TOLAK',          1),
    ('SCREENING_CV','SCV_PENGALAMAN',  N'Pengalaman kurang',                   'TOLAK',          2),
    ('SCREENING_CV','SCV_LOLOS',       N'Lolos screening',                     'LANJUT',         3),
    ('KONTAK_WA',   'KWA_NORESP',      N'Tidak respons setelah batas upaya',   'UNREACHABLE',    1),
    ('KONTAK_WA',   'KWA_NOMORMATI',   N'Nomor tidak aktif',                   'UNREACHABLE',    2),
    ('KONTAK_WA',   'KWA_MENOLAK',     N'Kandidat menolak proses',             'WITHDRAWN',      3),
    ('INT_HR',      'IHR_LULUS',       N'Lulus interview HR',                  'LANJUT',         1),
    ('INT_HR',      'IHR_HOLD',        N'Dipertimbangkan / cari pembanding',   'ON_HOLD',        2),
    ('INT_HR',      'IHR_TIDAKLULUS',  N'Tidak lulus',                        'TOLAK',          3),
    ('INT_HR',      'IHR_GAJI',        N'Ekspektasi gaji tidak sesuai',        'TOLAK',          4),
    ('OFFER',       'OFF_TERIMA',      N'Menerima offer',                      'LANJUT',         1),
    ('OFFER',       'OFF_LAIN',        N'Dapat offering perusahaan lain',      'OFFER_DECLINED', 2),
    ('OFFER',       'OFF_NEGO',        N'Nego tidak sepakat',                  'TOLAK',          3),
    ('ONBOARD',     'ONB_JOIN',        N'Join sesuai jadwal',                  'HIRED',          1),
    ('ONBOARD',     'ONB_NOSHOW',      N'Tidak hadir di hari join',            'NO_SHOW',        2)
) v(stage, kode, label, efek, urutan)
JOIN dbo.M_STAGE s ON s.kode_stage = v.stage
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS m WHERE m.kode_remark = v.kode);
GO
