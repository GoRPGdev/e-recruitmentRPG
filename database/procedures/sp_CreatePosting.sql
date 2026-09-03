/* =========================================================================
   sp_CreatePosting  --  buat job posting untuk requisition
   E-Recruitment RPG  --  modul Requisition (Kahfi)

   url_slug dibiarkan NULL -- di-generate saat HR mengaktifkan link form
   (Postings::ensure_slug). form_aktif default 0 (belum disebar).

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_CreatePosting') IS NOT NULL DROP PROCEDURE dbo.sp_CreatePosting;
GO

CREATE PROCEDURE dbo.sp_CreatePosting
    @id_req       INT,
    @id_channel   INT,
    @judul_posting NVARCHAR(200),
    @job_desc     VARCHAR(MAX) = NULL,
    @kualifikasi  VARCHAR(MAX) = NULL,
    @batch_ke     INT          = 1,
    @id_posting   INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION CrPost;

        DECLARE @status VARCHAR(25) = (SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = @id_req);
        IF @status IS NULL
            RAISERROR('Requisition tidak ditemukan.', 16, 1);
        IF @status NOT IN ('Approved','Sourcing','Sourcing_Ulang','Terpenuhi_Sebagian')
            RAISERROR('Requisition belum siap di-posting (status %s).', 16, 1, @status);
        IF NOT EXISTS (SELECT 1 FROM dbo.M_CHANNEL WHERE id_channel = @id_channel AND is_aktif = 1)
            RAISERROR('Channel tidak valid.', 16, 1);

        INSERT INTO dbo.JOB_POSTINGS
            (id_req, id_channel, batch_ke, judul_posting, job_desc, kualifikasi, tanggal_posting, form_aktif)
        VALUES
            (@id_req, @id_channel, @batch_ke, @judul_posting, @job_desc, @kualifikasi, CAST(GETDATE() AS DATE), 0);

        SET @id_posting = SCOPE_IDENTITY();

        IF @status = 'Approved'
            UPDATE dbo.REQUISITIONS SET status_req = 'Sourcing' WHERE id_req = @id_req;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION CrPost;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
