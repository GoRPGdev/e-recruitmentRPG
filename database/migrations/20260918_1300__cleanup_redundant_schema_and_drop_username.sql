/* =========================================================================
   20260918_1300__cleanup_redundant_schema_and_drop_username.sql
   Pembersihan skema database:
   1. M_USERS:
      - Pastikan nik_karyawan terisi pada seluruh baris
      - Jadikan nik_karyawan sebagai primary login identifier (NOT NULL, UNIQUE)
      - Drop kolom: username (beserta constraint UQ_M_USERS_username),
        departemen_snapshot, snapshot_pada
   2. Hapus tabel-tabel mati (dead tables):
      - dbo.IMPORT_TEMPLATE_KOLOM
      - dbo.IMPORT_TEMPLATES
   3. Hapus kolom-kolom redundan bekas M_CHANNEL yang telah dihapus:
      - dbo.APPLICATIONS.id_channel
      - dbo.JOB_POSTINGS.id_channel
      - dbo.IMPORT_BATCHES.id_channel

   Kompatibel dengan SQL Server 2008 R2 & Idempotent.
   ========================================================================= */

-- 1.1 Pastikan nik_karyawan terisi pada semua user sebelum diubah menjadi NOT NULL
UPDATE dbo.M_USERS
SET nik_karyawan = 'EMP-' + RIGHT('000' + CAST(id_user AS VARCHAR(10)), 3)
WHERE nik_karyawan IS NULL OR LTRIM(RTRIM(nik_karyawan)) = '';
GO

-- 1.2 Hapus constraint UNIQUE pada username jika ada
IF EXISTS (
    SELECT 1 FROM sys.key_constraints
    WHERE name = 'UQ_M_USERS_username' AND parent_object_id = OBJECT_ID('dbo.M_USERS')
)
BEGIN
    ALTER TABLE dbo.M_USERS DROP CONSTRAINT UQ_M_USERS_username;
END
GO

IF EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'UQ_M_USERS_username' AND object_id = OBJECT_ID('dbo.M_USERS')
)
BEGIN
    DROP INDEX UQ_M_USERS_username ON dbo.M_USERS;
END
GO

-- 1.3 Ubah nik_karyawan menjadi NOT NULL
ALTER TABLE dbo.M_USERS ALTER COLUMN nik_karyawan VARCHAR(20) NOT NULL;
GO

-- 1.4 Pastikan indeks/constraint UNIQUE pada nik_karyawan ada
IF EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'UQ_M_USERS_nik_karyawan' AND object_id = OBJECT_ID('dbo.M_USERS')
)
BEGIN
    DROP INDEX UQ_M_USERS_nik_karyawan ON dbo.M_USERS;
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.key_constraints
    WHERE name = 'UQ_M_USERS_nik' AND parent_object_id = OBJECT_ID('dbo.M_USERS')
)
BEGIN
    ALTER TABLE dbo.M_USERS ADD CONSTRAINT UQ_M_USERS_nik UNIQUE (nik_karyawan);
END
GO

-- 1.5 Hapus kolom redundan dari M_USERS
IF COL_LENGTH('dbo.M_USERS', 'username') IS NOT NULL
BEGIN
    ALTER TABLE dbo.M_USERS DROP COLUMN username;
END
GO

IF COL_LENGTH('dbo.M_USERS', 'departemen_snapshot') IS NOT NULL
BEGIN
    ALTER TABLE dbo.M_USERS DROP COLUMN departemen_snapshot;
END
GO

IF COL_LENGTH('dbo.M_USERS', 'snapshot_pada') IS NOT NULL
BEGIN
    ALTER TABLE dbo.M_USERS DROP COLUMN snapshot_pada;
END
GO

-- 2. Hapus tabel mati IMPORT_TEMPLATE_KOLOM & IMPORT_TEMPLATES
IF OBJECT_ID('dbo.IMPORT_TEMPLATE_KOLOM') IS NOT NULL
BEGIN
    DROP TABLE dbo.IMPORT_TEMPLATE_KOLOM;
END
GO

IF OBJECT_ID('dbo.IMPORT_TEMPLATES') IS NOT NULL
BEGIN
    DROP TABLE dbo.IMPORT_TEMPLATES;
END
GO

-- 3. Hapus kolom orphan id_channel
IF COL_LENGTH('dbo.APPLICATIONS', 'id_channel') IS NOT NULL
BEGIN
    ALTER TABLE dbo.APPLICATIONS DROP COLUMN id_channel;
END
GO

IF COL_LENGTH('dbo.JOB_POSTINGS', 'id_channel') IS NOT NULL
BEGIN
    ALTER TABLE dbo.JOB_POSTINGS DROP COLUMN id_channel;
END
GO

IF COL_LENGTH('dbo.IMPORT_BATCHES', 'id_channel') IS NOT NULL
BEGIN
    ALTER TABLE dbo.IMPORT_BATCHES DROP COLUMN id_channel;
END
GO
