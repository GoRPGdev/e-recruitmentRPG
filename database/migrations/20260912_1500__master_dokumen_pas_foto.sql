/* =========================================================================
   20260912_1500__master_dokumen_pas_foto.sql
   E-Recruitment Ratu Pertiwi Group (RPG)
   1. Menambahkan jenis dokumen "Pas Foto" ke M_DOKUMEN
   2. Menambahkan kolom foto_path NVARCHAR(400) NULL pada dbo.CANDIDATES
      sebagai pointer instan foto profil pelamar di luar webroot
   ========================================================================= */

-- 1. Tambah jenis dokumen Pas Foto di M_DOKUMEN
IF NOT EXISTS (SELECT 1 FROM dbo.M_DOKUMEN WHERE nama_dokumen = N'Pas Foto')
BEGIN
    INSERT INTO dbo.M_DOKUMEN (nama_dokumen, kategori, tingkat_sensitif, is_mandatory_default, is_aktif)
    VALUES (N'Pas Foto', 'IDENTITAS', 'UMUM', 0, 1);
END
GO

-- 2. Tambah kolom foto_path di dbo.CANDIDATES jika belum ada
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'foto_path'
)
BEGIN
    ALTER TABLE dbo.CANDIDATES ADD foto_path NVARCHAR(400) NULL;
END
GO
