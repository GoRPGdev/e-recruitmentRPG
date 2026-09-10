/* =========================================================================
   sp_UpdateRequisitionCatatanHR  --  Update catatan / feedback HR pada MPR
   E-Recruitment RPG  --  Requisition Engine
   Memperbarui catatan feedback/evaluasi HR tanpa harus mengubah status alur.
   ========================================================================= */
IF OBJECT_ID('dbo.sp_UpdateRequisitionCatatanHR') IS NOT NULL DROP PROCEDURE dbo.sp_UpdateRequisitionCatatanHR;
GO

CREATE PROCEDURE dbo.sp_UpdateRequisitionCatatanHR
    @id_req         INT,
    @catatan_hr     NVARCHAR(MAX) = NULL,
    @oleh_user      INT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION UpdCatatanHR;

        IF @id_req IS NULL
            RAISERROR('ID Requisition harus diisi.', 16, 1);

        DECLARE @catatan_lama NVARCHAR(MAX);
        SELECT @catatan_lama = catatan_hr FROM dbo.REQUISITIONS WHERE id_req = @id_req;

        UPDATE dbo.REQUISITIONS
        SET catatan_hr = @catatan_hr
        WHERE id_req = @id_req;

        DECLARE @n_lama VARCHAR(MAX) = 'catatan_hr=' + ISNULL(SUBSTRING(@catatan_lama, 1, 200), '(null)'),
                @n_baru VARCHAR(MAX) = 'catatan_hr=' + ISNULL(SUBSTRING(@catatan_hr, 1, 200), '(null)');
        EXEC dbo.sp_AuditLog 'REQUISITIONS', @id_req, 'UPDATE', @n_lama, @n_baru, @oleh_user;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION UpdCatatanHR;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
