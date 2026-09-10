/* =========================================================================
   sp_SubmitToBOD  --  ajukan MPR ke BOD (Review BOD)
   E-Recruitment RPG  --  modul Requisition (Kahfi)

   Review_HR -> Review_BOD. Memberi nomor MPR/YYYY/MM/NNN kalau
   belum ada. Tidak lagi mencatat ke REQUISITION_APPROVALS.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SubmitToBOD') IS NOT NULL DROP PROCEDURE dbo.sp_SubmitToBOD;
GO

CREATE PROCEDURE dbo.sp_SubmitToBOD
    @id_req      INT,
    @oleh_user   INT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SubmitBOD;

        DECLARE @status VARCHAR(25), @no_mpr VARCHAR(30);
        SELECT @status = status_req, @no_mpr = no_mpr FROM dbo.REQUISITIONS WHERE id_req = @id_req;

        IF @status IS NULL
            RAISERROR('Requisition tidak ditemukan.', 16, 1);
        IF @status NOT IN ('Review_HR', 'Sourcing_Ulang')
            RAISERROR('Hanya MPR berstatus Review_HR atau Sourcing_Ulang yang bisa diteruskan ke BOD.', 16, 1);

        /* nomor MPR kalau belum ada */
        IF @no_mpr IS NULL
        BEGIN
            DECLARE @thn CHAR(4) = CONVERT(CHAR(4), YEAR(GETDATE())),
                    @bln CHAR(2) = RIGHT('0' + CONVERT(VARCHAR(2), MONTH(GETDATE())), 2);
            DECLARE @prefix VARCHAR(20) = 'MPR/' + @thn + '/' + @bln + '/';
            DECLARE @seq INT =
                ISNULL((SELECT MAX(CONVERT(INT, RIGHT(no_mpr, 3)))
                        FROM dbo.REQUISITIONS
                        WHERE no_mpr LIKE @prefix + '[0-9][0-9][0-9]'), 0) + 1;
            SET @no_mpr = @prefix + RIGHT('00' + CONVERT(VARCHAR(3), @seq), 3);

            UPDATE dbo.REQUISITIONS SET no_mpr = @no_mpr WHERE id_req = @id_req;
        END

        UPDATE dbo.REQUISITIONS
        SET status_req = 'Review_BOD',
            tanggal_pengajuan = ISNULL(tanggal_pengajuan, GETDATE())
        WHERE id_req = @id_req;

        /* Audit log */
        DECLARE @n_lama VARCHAR(MAX) = 'status_req=' + @status,
                @n_baru VARCHAR(MAX) = 'status_req=Review_BOD';
        EXEC dbo.sp_AuditLog 'REQUISITIONS', @id_req, 'UPDATE', @n_lama, @n_baru, @oleh_user;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @msg VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SubmitBOD;
        RAISERROR(@msg, 16, 1);
    END CATCH
END
GO
