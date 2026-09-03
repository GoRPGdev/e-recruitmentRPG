/* =========================================================================
   Bootstrap database dev  --  TEMPLATE
   ---------------------------------------------------------------------------
   Ganti <NAMA> dengan nama orang (KIKI / KAHFI).
   Jalankan SEKALI di SSMS / sqlcmd sebagai sa atau anggota sysadmin.
   Ini BUKAN file migrasi -- tidak dicatat di SCHEMA_MIGRATIONS.
   ========================================================================= */

SET NOCOUNT ON;

/* 1. Database kosong ---------------------------------------------------------- */
IF DB_ID('RPG_EREC_DEV_<NAMA>') IS NULL
    CREATE DATABASE RPG_EREC_DEV_<NAMA>;
GO

/* 2. Kunci ke perilaku SQL Server 2008 R2 + recovery ringan (DB dev) --------- */
ALTER DATABASE RPG_EREC_DEV_<NAMA> SET COMPATIBILITY_LEVEL = 100;
ALTER DATABASE RPG_EREC_DEV_<NAMA> SET RECOVERY SIMPLE;
GO

/* 3. Login aplikasi -------------------------------------------------------------
   Dilewati kalau sudah ada (mis. instance dipakai bareng dengan DB dev lain).
   GANTI placeholder password sebelum menjalankan. Jangan commit password asli. */
IF NOT EXISTS (SELECT 1 FROM sys.server_principals WHERE name = 'erec_app')
    CREATE LOGIN erec_app
        WITH PASSWORD      = 'MASUKKAN_PASSWORD_KUAT_DI_SINI',
             CHECK_POLICY  = OFF,
             DEFAULT_DATABASE = RPG_EREC_DEV_<NAMA>;
GO

/* 4. User + hak DDL, HANYA di DB dev ini (bukan sa, bukan server-wide) ------- */
USE RPG_EREC_DEV_<NAMA>;
GO

IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name = 'erec_app')
    CREATE USER erec_app FOR LOGIN erec_app;
GO

/* db_owner: migrate.php butuh CREATE TABLE / CREATE PROCEDURE. Idempotent. */
IF NOT EXISTS (
        SELECT 1
        FROM sys.database_role_members rm
        JOIN sys.database_principals r ON r.principal_id = rm.role_principal_id
        JOIN sys.database_principals m ON m.principal_id = rm.member_principal_id
        WHERE r.name = 'db_owner' AND m.name = 'erec_app')
    EXEC sp_addrolemember 'db_owner', 'erec_app';   -- sintaks 2008 R2, BUKAN ALTER ROLE
GO

PRINT 'RPG_EREC_DEV_<NAMA> siap.';
PRINT 'Lanjut: isi tools/koneksi.local.php lalu  php tools/test-koneksi.php';
GO
