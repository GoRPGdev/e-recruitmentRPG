/* =========================================================================
   sp_SubmitToBOD  --  ajukan MPR ke BOD
   E-Recruitment RPG  --  modul Requisition (Kahfi)

   Draft / (revisi) -> Menunggu_BOD. Memberi nomor MPR/YYYY/MM/NNN kalau
   belum ada, dan membuka putaran approval baru di REQUISITION_APPROVALS.
   NNN = urut per bulan (dijaga UX_REQ_no_mpr unik + transaksi).

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SubmitToBOD') IS NOT NULL DROP PROCEDURE dbo.sp_SubmitToBOD;
GO

CREATE PROCEDURE dbo.sp_SubmitToBOD
    @id_req      INT,
    @oleh_user   INT,
    @id_approval INT OUTPUT
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
        IF @status NOT IN ('Draft', 'Sourcing_Ulang')
            RAISERROR('Hanya MPR Draft / Sourcing_Ulang yang bisa diajukan.', 16, 1);

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

        DECLARE @putaran INT =
            ISNULL((SELECT MAX(putaran_ke) FROM dbo.REQUISITION_APPROVALS WHERE id_req = @id_req), 0) + 1;

        INSERT INTO dbo.REQUISITION_APPROVALS
            (id_req, putaran_ke, diajukan_ke_bod_pada, keputusan, diinput_oleh, diinput_pada)
        VALUES
            (@id_req, @putaran, CAST(GETDATE() AS DATE), 'Pending', @oleh_user, GETDATE());

        SET @id_approval = SCOPE_IDENTITY();

        UPDATE dbo.REQUISITIONS
        SET status_req = 'Menunggu_BOD',
            tanggal_pengajuan = ISNULL(tanggal_pengajuan, GETDATE())
        WHERE id_req = @id_req;

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
