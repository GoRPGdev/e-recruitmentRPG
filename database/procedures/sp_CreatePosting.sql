/* =========================================================================
   sp_CreatePosting  --  buat job posting untuk requisition
   E-Recruitment RPG  --  modul Requisition (Kahfi)

   url_slug di-generate saat posting dibuat / diatur.
   Dukungan batas waktu (durasi_hari): default 14 hari.
   Jika requisition berstatus 'Approved', status beralih menjadi 'Sourcing'.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_CreatePosting') IS NOT NULL DROP PROCEDURE dbo.sp_CreatePosting;
GO

CREATE PROCEDURE dbo.sp_CreatePosting
    @id_req        INT,
    @id_channel    INT          = NULL,
    @judul_posting NVARCHAR(200),
    @job_desc      VARCHAR(MAX) = NULL,
    @kualifikasi   VARCHAR(MAX) = NULL,
    @batch_ke      INT          = 1,
    @durasi_hari   INT          = 14,
    @id_posting    INT OUTPUT
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

        DECLARE @now DATETIME = GETDATE();
        DECLARE @tgl_tutup DATETIME = CASE
            WHEN @durasi_hari IS NOT NULL AND @durasi_hari > 0 THEN DATEADD(DAY, @durasi_hari, @now)
            ELSE NULL
        END;

        INSERT INTO dbo.JOB_POSTINGS
            (id_req, id_channel, batch_ke, judul_posting, job_desc, kualifikasi,
             tanggal_posting, tanggal_tutup, form_aktif, form_dibuka, form_ditutup)
        VALUES
            (@id_req, NULL, @batch_ke, @judul_posting, @job_desc, @kualifikasi,
             CAST(@now AS DATE), CAST(@tgl_tutup AS DATE), 1, @now, @tgl_tutup);

        SET @id_posting = SCOPE_IDENTITY();

        -- Pastikan url_slug otomatis terisi saat posting dibuat
        DECLARE @slug_posisi VARCHAR(100);
        SELECT @slug_posisi = LOWER(REPLACE(REPLACE(REPLACE(p.nama_posisi, ' ', '-'), '/', '-'), '&', 'dan'))
          FROM dbo.REQUISITIONS r
          JOIN dbo.M_POSISI p ON p.id_posisi = r.id_posisi
         WHERE r.id_req = @id_req;

        IF @slug_posisi IS NULL OR LTRIM(RTRIM(@slug_posisi)) = ''
            SET @slug_posisi = 'lowongan';

        UPDATE dbo.JOB_POSTINGS
           SET url_slug = SUBSTRING(@slug_posisi, 1, 70) + '-' + CAST(@id_posting AS VARCHAR(20))
         WHERE id_posting = @id_posting AND (url_slug IS NULL OR url_slug = '');

        -- Sourcing pertama saat disetujui
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
