/* =========================================================================
   Bootstrap database dev  --  KIKI
   ---------------------------------------------------------------------------
   Jalankan SEKALI di SSMS / sqlcmd sebagai sa atau anggota sysadmin.
   Ganti MASUKKAN_PASSWORD_KUAT_DI_SINI dulu -- password yang sama dipakai
   di tools/koneksi.local.php. JANGAN commit password asli ke file ini.
   Ini BUKAN file migrasi -- tidak dicatat di SCHEMA_MIGRATIONS.

   Catatan: database Kiki di PC kantor (DESKTOP-E34JC3H) sudah dibuat manual
   3 Sep 2026. File ini disimpan supaya langkahnya bisa direproduksi /
   dibangun ulang kalau DB dev perlu di-DROP.
   ========================================================================= */

SET NOCOUNT ON;

/* 1. Database kosong ---------------------------------------------------------- */
IF DB_ID('RPG_EREC_DEV_KIKI') IS NULL
    CREATE DATABASE RPG_EREC_DEV_KIKI;
GO

/* 2. Kunci ke perilaku SQL Server 2008 R2 + recovery ringan (DB dev) --------- */
ALTER DATABASE RPG_EREC_DEV_KIKI SET COMPATIBILITY_LEVEL = 100;
ALTER DATABASE RPG_EREC_DEV_KIKI SET RECOVERY SIMPLE;
GO

/* 3. Login aplikasi --------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM sys.server_principals WHERE name = 'erec_app')
    CREATE LOGIN erec_app
        WITH PASSWORD        = 'MASUKKAN_PASSWORD_KUAT_DI_SINI',
             CHECK_POLICY    = OFF,
             DEFAULT_DATABASE = RPG_EREC_DEV_KIKI;
GO

/* 4. User + hak DDL, HANYA di DB dev ini ------------------------------------- */
USE RPG_EREC_DEV_KIKI;
GO

IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name = 'erec_app')
    CREATE USER erec_app FOR LOGIN erec_app;
GO

IF NOT EXISTS (
        SELECT 1
        FROM sys.database_role_members rm
        JOIN sys.database_principals r ON r.principal_id = rm.role_principal_id
        JOIN sys.database_principals m ON m.principal_id = rm.member_principal_id
        WHERE r.name = 'db_owner' AND m.name = 'erec_app')
    EXEC sp_addrolemember 'db_owner', 'erec_app';
GO

PRINT 'RPG_EREC_DEV_KIKI siap.';
PRINT 'Lanjut: isi tools/koneksi.local.php lalu  php tools/test-koneksi.php';
GO
