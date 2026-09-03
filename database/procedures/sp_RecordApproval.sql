/* =========================================================================
   sp_RecordApproval  --  catat keputusan BOD (dari WhatsApp) ke sistem
   E-Recruitment RPG  --  modul Requisition (Kahfi)

   Mengisi baris REQUISITION_APPROVALS (putaran terbuka) + memindahkan
   status_req:
     Approved / Approved_Sebagian -> Sourcing   (siap terima lamaran)
     Rejected                     -> Draft      (revisi -> putaran baru via sp_SubmitToBOD)

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_RecordApproval') IS NOT NULL DROP PROCEDURE dbo.sp_RecordApproval;
GO

CREATE PROCEDURE dbo.sp_RecordApproval
    @id_approval       INT,
    @keputusan         VARCHAR(20),     -- Approved / Approved_Sebagian / Rejected
    @jumlah_disetujui  INT           = NULL,
    @tanggal_keputusan DATE          = NULL,
    @disetujui_oleh    NVARCHAR(150) = NULL,
    @catatan_bod       VARCHAR(MAX)  = NULL,
    @lampiran_path     VARCHAR(400)  = NULL,
    @oleh_user         INT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION RecApp;

        IF @keputusan NOT IN ('Approved', 'Approved_Sebagian', 'Rejected')
            RAISERROR('Keputusan tidak valid.', 16, 1);

        DECLARE @id_req INT, @kep_lama VARCHAR(20), @dibutuhkan INT;
        SELECT @id_req = ra.id_req, @kep_lama = ra.keputusan, @dibutuhkan = r.jumlah_dibutuhkan
        FROM dbo.REQUISITION_APPROVALS ra
        JOIN dbo.REQUISITIONS r ON r.id_req = ra.id_req
        WHERE ra.id_approval = @id_approval;

        IF @id_req IS NULL
            RAISERROR('Baris approval tidak ditemukan.', 16, 1);
        IF @kep_lama <> 'Pending'
            RAISERROR('Putaran approval ini sudah punya keputusan.', 16, 1);

        DECLARE @setuju INT =
            CASE @keputusan
                WHEN 'Approved'           THEN ISNULL(@jumlah_disetujui, @dibutuhkan)
                WHEN 'Approved_Sebagian'  THEN ISNULL(@jumlah_disetujui, @dibutuhkan)
                ELSE 0
            END;

        UPDATE dbo.REQUISITION_APPROVALS
        SET keputusan = @keputusan,
            jumlah_disetujui = @setuju,
            tanggal_keputusan = ISNULL(@tanggal_keputusan, CAST(GETDATE() AS DATE)),
            disetujui_oleh = @disetujui_oleh,
            catatan_bod = @catatan_bod,
            lampiran_path = @lampiran_path
        WHERE id_approval = @id_approval;

        IF @keputusan = 'Rejected'
            UPDATE dbo.REQUISITIONS SET status_req = 'Draft' WHERE id_req = @id_req;
        ELSE
            UPDATE dbo.REQUISITIONS
            SET status_req = 'Sourcing', jumlah_disetujui = @setuju
            WHERE id_req = @id_req;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @msg VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION RecApp;
        RAISERROR(@msg, 16, 1);
    END CATCH
END
GO
