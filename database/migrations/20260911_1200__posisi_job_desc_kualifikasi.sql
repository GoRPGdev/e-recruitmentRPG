/* =========================================================================
   20260911_1200__posisi_job_desc_kualifikasi.sql
   Menambahkan master template spesifikasi pekerjaan ke M_POSISI:
   - job_desc (VARCHAR(MAX)): Uraian tugas standar untuk posisi
   - kualifikasi (VARCHAR(MAX)): Persyaratan kualifikasi standar posisi
   - pendidikan_minimal (NVARCHAR(60)): Rekomendasi jenjang pendidikan minimal
   - pengalaman_minimal_tahun (INT): Rekomendasi lama pengalaman minimal
   ========================================================================= */

IF COL_LENGTH('dbo.M_POSISI', 'job_desc') IS NULL
    ALTER TABLE dbo.M_POSISI ADD job_desc VARCHAR(MAX) NULL;
GO

IF COL_LENGTH('dbo.M_POSISI', 'kualifikasi') IS NULL
    ALTER TABLE dbo.M_POSISI ADD kualifikasi VARCHAR(MAX) NULL;
GO

IF COL_LENGTH('dbo.M_POSISI', 'pendidikan_minimal') IS NULL
    ALTER TABLE dbo.M_POSISI ADD pendidikan_minimal NVARCHAR(60) NULL;
GO

IF COL_LENGTH('dbo.M_POSISI', 'pengalaman_minimal_tahun') IS NULL
    ALTER TABLE dbo.M_POSISI ADD pengalaman_minimal_tahun INT NULL;
GO
