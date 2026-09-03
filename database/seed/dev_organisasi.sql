/* =========================================================================
   dev_organisasi.sql  --  DATA UJI ORGANISASI (dev saja)
   E-Recruitment RPG

   >>> BUKAN migrasi. Tidak dijalankan tools/migrate.php, tidak dicatat di
       SCHEMA_MIGRATIONS. JANGAN dijalankan di database produksi.

   Gunanya: mengisi M_DEPARTEMEN / M_OUTLET / M_POSISI + role_pic & SLA per
   tahap dengan data contoh, supaya modul bisa dikembangkan sebelum HR
   memasukkan data asli.

   Isi di bawah = CONTOH (kode outlet dari ERD sec.7.1, sisanya tebakan).

   Cara jalan (di DB dev sendiri):
       sqlcmd -S localhost -U erec_app -P <pwd> -d RPG_EREC_DEV_KIKI -i database\seed\dev_organisasi.sql

   ---------------------------------------------------------------------------
   Model fleksibilitas (kenapa ini cukup data uji, bukan migrasi):
   - Outlet / Departemen / Posisi  -> HR kelola lewat CRUD Master Data
     (Fase 2). Tambah = INSERT. "Hapus" = is_aktif = 0 (soft delete) supaya
     requisition/lamaran lama tidak yatim.
   - role_pic & SLA & urutan tahap  -> HR kelola lewat Flow Builder (Fase 3,
     permission EDIT_FLOW_TEMPLATE).
   - Produksi: data asli dimasukkan HR lewat UI, atau lewat migrasi
     seed_organisasi_prod khusus yang dibuat menjelang go-live dengan daftar
     resmi dari Mas Fachri.
   Semua INSERT pakai WHERE NOT EXISTS -> aman diulang.
   ========================================================================= */

SET NOCOUNT ON;

/* ---- M_DEPARTEMEN (CONTOH) ------------------------------------------- */
INSERT INTO dbo.M_DEPARTEMEN (kode, nama)
SELECT v.kode, v.nama
FROM (VALUES
    ('HRD',  N'Human Resources'),
    ('MKT',  N'Marketing'),
    ('FIN',  N'Finance & Accounting'),
    ('OPS',  N'Operasional'),
    ('IT',   N'Teknologi Informasi')
) v(kode, nama)
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_DEPARTEMEN d WHERE d.kode = v.kode);
GO

/* ---- M_OUTLET (CONTOH -- ERD sec.7.1 kasih 3 contoh + brand) -------- */
INSERT INTO dbo.M_OUTLET (kode_outlet, nama_outlet, brand, region)
SELECT v.kode, v.nama, v.brand, v.region
FROM (VALUES
    ('Z-MKTR', N'Naughty Menteng',    N'Naughty',    N'Jakarta Pusat'),
    ('K-LC3',  N'Les Femmes Kemang',  N'Les Femmes', N'Jakarta Selatan'),
    ('I-TCM',  N'SOYU Tanjung Duren', N'SOYU',       N'Jakarta Barat')
) v(kode, nama, brand, region)
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_OUTLET o WHERE o.kode_outlet = v.kode);
GO

/* ---- M_POSISI (CONTOH) --------------------------------------------------
   default_flow dipetakan lewat kode_flow. */
INSERT INTO dbo.M_POSISI (nama_posisi, id_departemen, level_posisi, default_flow)
SELECT v.nama, d.id_departemen, v.level, f.id_flow
FROM (VALUES
    (N'Marketing Manager',       'MKT', 'Manager',       'HQ_MANAGER'),
    (N'Marketing Staff',         'MKT', 'Staff',         'HQ_STAFF'),
    (N'Finance Staff (Krusial)', 'FIN', 'Staff_Krusial', 'HQ_STAFF_KRUSIAL'),
    (N'HR Staff',                'HRD', 'Staff',         'HQ_STAFF'),
    (N'Crew Outlet',             'OPS', 'MP',            'MP_OUTLET')
) v(nama, dept, level, flow)
JOIN dbo.M_DEPARTEMEN d ON d.kode      = v.dept
JOIN dbo.M_FLOW       f ON f.kode_flow = v.flow
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_POSISI p WHERE p.nama_posisi = v.nama);
GO

/* ---- role_pic & sla_hari per M_FLOW_STAGE (TEBAKAN) -------------------
   role_pic = kode_role (FK ke M_ROLES.kode_role). Hanya mengisi yang NULL. */
UPDATE fs
SET fs.role_pic = v.role_pic,
    fs.sla_hari = v.sla_hari
FROM dbo.M_FLOW_STAGE fs
JOIN dbo.M_STAGE s ON s.id_stage = fs.id_stage
JOIN (VALUES
    ('SOURCING',     'HR_ADMIN',  3),
    ('SCREENING_CV', 'HR_ADMIN',  2),
    ('KONTAK_WA',    'HR_ADMIN',  2),
    ('INT_HR',       'HR_SPV',    3),
    ('FORM_PELAMAR', 'HR_ADMIN',  2),
    ('PSIKOTES',     'HR_ADMIN',  3),
    ('INT_USER',     'USER_DEPT', 3),
    ('INT_BOD',      'BOD',       5),
    ('OFFER',        'HR_SPV',    3),
    ('ONBOARD',      'HR_ADMIN',  5)
) v(kode_stage, role_pic, sla_hari) ON v.kode_stage = s.kode_stage
WHERE fs.role_pic IS NULL;
GO

PRINT 'dev_organisasi.sql selesai (data uji).';
GO
