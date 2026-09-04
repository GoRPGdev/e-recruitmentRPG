/* =========================================================================
   sp_ToggleRemark  --  soft delete / aktifkan kembali opsi remark
   E-Recruitment RPG  --  modul Flow Builder & Master Data

   Mengubah is_aktif (1/0). Mengikuti CLAUDE.md aturan 5: soft delete.
   Pencatatan audit trail via dbo.sp_AuditLog.

   Deploy: php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_ToggleRemark') IS NOT NULL DROP PROCEDURE dbo.sp_ToggleRemark;
GO

CREATE PROCEDURE dbo.sp_ToggleRemark
    @id_remark INT,
    @is_aktif  BIT,
    @oleh_user INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION TogRmk;

        IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE id_remark = @id_remark)
            RAISERROR('Remark tidak ditemukan.', 16, 1);

        DECLARE @old_akt BIT;
        SELECT @old_akt = is_aktif FROM dbo.M_REMARKS WHERE id_remark = @id_remark;

        UPDATE dbo.M_REMARKS
        SET is_aktif = @is_aktif
        WHERE id_remark = @id_remark;

        DECLARE @nl VARCHAR(50) = 'is_aktif=' + CONVERT(VARCHAR(1), @old_akt);
        DECLARE @nb VARCHAR(50) = 'is_aktif=' + CONVERT(VARCHAR(1), @is_aktif);
        EXEC dbo.sp_AuditLog 'M_REMARKS', @id_remark, 'UPDATE', @nl, @nb, @oleh_user;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @err VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION TogRmk;
        RAISERROR(@err, 16, 1);
    END CATCH
END
GO
