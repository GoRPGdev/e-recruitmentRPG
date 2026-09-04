/* =========================================================================
   sp_ToggleDepartemen  --  soft delete / aktifkan kembali departemen
   E-Recruitment RPG  --  modul Master Data (Kahfi)

   Mengubah is_aktif (1/0). Mengikuti CLAUDE.md aturan 5: soft delete.
   Pencatatan audit trail via dbo.sp_AuditLog.

   Deploy: php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_ToggleDepartemen') IS NOT NULL DROP PROCEDURE dbo.sp_ToggleDepartemen;
GO

CREATE PROCEDURE dbo.sp_ToggleDepartemen
    @id_departemen INT,
    @is_aktif      BIT,
    @oleh_user     INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION TogDept;

        IF NOT EXISTS (SELECT 1 FROM dbo.M_DEPARTEMEN WHERE id_departemen = @id_departemen)
            RAISERROR('Departemen tidak ditemukan.', 16, 1);

        DECLARE @old_akt BIT;
        SELECT @old_akt = is_aktif FROM dbo.M_DEPARTEMEN WHERE id_departemen = @id_departemen;

        UPDATE dbo.M_DEPARTEMEN
        SET is_aktif = @is_aktif
        WHERE id_departemen = @id_departemen;

        DECLARE @nl VARCHAR(50) = 'is_aktif=' + CONVERT(VARCHAR(1), @old_akt);
        DECLARE @nb VARCHAR(50) = 'is_aktif=' + CONVERT(VARCHAR(1), @is_aktif);
        EXEC dbo.sp_AuditLog 'M_DEPARTEMEN', @id_departemen, 'UPDATE', @nl, @nb, @oleh_user;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @err VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION TogDept;
        RAISERROR(@err, 16, 1);
    END CATCH
END
GO
