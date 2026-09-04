/* =========================================================================
   sp_AuditLog  --  satu baris ke AUDIT_LOG
   E-Recruitment RPG  --  Fase 4 Keamanan & Kepatuhan (Kiki)

   Helper tipis. AUDIT_LOG write-only (nilai_lama/baru = VARCHAR(MAX), tak
   pernah di-query isinya -> dikecualikan dari aturan anti-JSON CLAUDE.md).
   Tanpa transaksi sendiri: satu INSERT, ikut transaksi caller apa adanya.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_AuditLog') IS NOT NULL DROP PROCEDURE dbo.sp_AuditLog;
GO

CREATE PROCEDURE dbo.sp_AuditLog
    @nama_tabel VARCHAR(60),
    @id_baris   INT,
    @aksi       VARCHAR(10),          -- INSERT / UPDATE / DELETE
    @nilai_lama VARCHAR(MAX) = NULL,
    @nilai_baru VARCHAR(MAX) = NULL,
    @oleh_user  INT          = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF @aksi NOT IN ('INSERT','UPDATE','DELETE')
        RAISERROR('sp_AuditLog: aksi "%s" tidak valid.', 16, 1, @aksi);

    INSERT INTO dbo.AUDIT_LOG (nama_tabel, id_baris, aksi, nilai_lama, nilai_baru, oleh_user)
    VALUES (@nama_tabel, @id_baris, @aksi, @nilai_lama, @nilai_baru, @oleh_user);
END
GO
