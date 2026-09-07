/* =========================================================================
   20260911_1000__drop_channel.sql
   Hapus fitur, data, dan tabel Channel / Saluran (M_CHANNEL):
   - Drop FK dari JOB_POSTINGS, IMPORT_BATCHES, APPLICATIONS ke M_CHANNEL
   - Ubah kolom id_channel di tabel terkait menjadi nullable / lepas relasi
   - Hapus tabel dbo.M_CHANNEL
   ========================================================================= */

-- 1. Hapus Foreign Key yang merujuk ke M_CHANNEL
IF OBJECT_ID('dbo.FK_JP_channel', 'F') IS NOT NULL
    ALTER TABLE dbo.JOB_POSTINGS DROP CONSTRAINT FK_JP_channel;
GO

IF OBJECT_ID('dbo.FK_IB_channel', 'F') IS NOT NULL
    ALTER TABLE dbo.IMPORT_BATCHES DROP CONSTRAINT FK_IB_channel;
GO

IF OBJECT_ID('dbo.FK_APP_channel', 'F') IS NOT NULL
    ALTER TABLE dbo.APPLICATIONS DROP CONSTRAINT FK_APP_channel;
GO

IF OBJECT_ID('dbo.FK_IT_channel', 'F') IS NOT NULL
    ALTER TABLE dbo.IMPORT_TEMPLATES DROP CONSTRAINT FK_IT_channel;
GO

-- 2. Buat kolom id_channel di JOB_POSTINGS, IMPORT_BATCHES, dan IMPORT_TEMPLATES menjadi NULLABLE jika sebelumnya NOT NULL
IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.JOB_POSTINGS') AND name = 'id_channel' AND is_nullable = 0)
BEGIN
    ALTER TABLE dbo.JOB_POSTINGS ALTER COLUMN id_channel INT NULL;
END
GO

IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.IMPORT_BATCHES') AND name = 'id_channel' AND is_nullable = 0)
BEGIN
    ALTER TABLE dbo.IMPORT_BATCHES ALTER COLUMN id_channel INT NULL;
END
GO

IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.IMPORT_TEMPLATES') AND name = 'id_channel' AND is_nullable = 0)
BEGIN
    ALTER TABLE dbo.IMPORT_TEMPLATES ALTER COLUMN id_channel INT NULL;
END
GO

-- 3. Set nilai id_channel ke NULL di seluruh transaksi
IF OBJECT_ID('dbo.JOB_POSTINGS') IS NOT NULL
BEGIN
    UPDATE dbo.JOB_POSTINGS SET id_channel = NULL WHERE id_channel IS NOT NULL;
END
GO

IF OBJECT_ID('dbo.IMPORT_BATCHES') IS NOT NULL
BEGIN
    UPDATE dbo.IMPORT_BATCHES SET id_channel = NULL WHERE id_channel IS NOT NULL;
END
GO

IF OBJECT_ID('dbo.IMPORT_TEMPLATES') IS NOT NULL
BEGIN
    UPDATE dbo.IMPORT_TEMPLATES SET id_channel = NULL WHERE id_channel IS NOT NULL;
END
GO

IF OBJECT_ID('dbo.APPLICATIONS') IS NOT NULL
BEGIN
    UPDATE dbo.APPLICATIONS SET id_channel = NULL WHERE id_channel IS NOT NULL;
END
GO

-- 4. Hapus tabel M_CHANNEL
IF OBJECT_ID('dbo.M_CHANNEL', 'U') IS NOT NULL
    DROP TABLE dbo.M_CHANNEL;
GO
