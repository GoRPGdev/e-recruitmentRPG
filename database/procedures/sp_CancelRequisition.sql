/* =========================================================================
   sp_CancelRequisition  --  batalkan Job Requisition (MPR)
   E-Recruitment RPG  --  modul Requisition (Kahfi)

   Aturan:
     - Hanya MPR berstatus Draft, Menunggu_BOD, Sourcing, Sourcing_Ulang
       yang dapat dibatalkan.
     - MPR Terpenuhi tidak dapat dibatalkan.
     - Jika posting aktif ada, otomatis dinonaktifkan (form_aktif = 0).
     - Mencatat audit trail ke AUDIT_LOG via sp_AuditLog.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_CancelRequisition') IS NOT NULL DROP PROCEDURE dbo.sp_CancelRequisition;
GO

CREATE PROCEDURE dbo.sp_CancelRequisition
    @id_req    INT,
    @alasan    VARCHAR(500) = NULL,
    @oleh_user INT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION CancelReq;

        DECLARE @status VARCHAR(25), @no_mpr VARCHAR(30);
        SELECT @status = status_req, @no_mpr = no_mpr FROM dbo.REQUISITIONS WHERE id_req = @id_req;

        IF @status IS NULL
            RAISERROR('Requisition tidak ditemukan.', 16, 1);
        IF @status = 'Dibatalkan'
            RAISERROR('Requisition sudah berstatus Dibatalkan.', 16, 1);
        IF @status = 'Terpenuhi'
            RAISERROR('Requisition yang sudah Terpenuhi tidak dapat dibatalkan.', 16, 1);

        -- Update status MPR
        UPDATE dbo.REQUISITIONS
        SET status_req = 'Dibatalkan'
        WHERE id_req = @id_req;

        -- Tutup seluruh posting form publik terkait
        UPDATE dbo.JOB_POSTINGS
        SET form_aktif = 0,
            form_ditutup = ISNULL(form_ditutup, GETDATE())
        WHERE id_req = @id_req AND form_aktif = 1;

        -- Audit log
        DECLARE @detail VARCHAR(1000) = 'Batal MPR ' + ISNULL(@no_mpr, '#' + CAST(@id_req AS VARCHAR(10)))
                                      + ' (status awal: ' + @status + '). Alasan: ' + ISNULL(@alasan, '-');
        EXEC dbo.sp_AuditLog
            @tabel = 'REQUISITIONS',
            @id_baris = @id_req,
            @aksi = 'BATAL_MPR',
            @id_user = @oleh_user,
            @detail = @detail;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @msg VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION CancelReq;
        RAISERROR(@msg, 16, 1);
    END CATCH
END
GO
