/* =========================================================================
   sp_VerifyDocument  --  verifikasi berkas kandidat
   E-Recruitment RPG  --  modul Dokumen (Kiki)

   status_verifikasi: Proses -> Done / Ditolak. Mencatat pemverifikasi & waktu.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_VerifyDocument') IS NOT NULL DROP PROCEDURE dbo.sp_VerifyDocument;
GO

CREATE PROCEDURE dbo.sp_VerifyDocument
    @id_cand_doc INT,
    @status      VARCHAR(10),     -- Done / Ditolak
    @catatan     VARCHAR(MAX) = NULL,
    @oleh_user   INT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION VerifDoc;

        IF @status NOT IN ('Done','Ditolak')
            RAISERROR('Status verifikasi harus Done / Ditolak.', 16, 1);

        DECLARE @id_lamaran INT;
        SELECT @id_lamaran = id_lamaran FROM dbo.CANDIDATE_DOCUMENTS WHERE id_cand_doc = @id_cand_doc;
        IF @id_lamaran IS NULL
            RAISERROR('Dokumen tidak ditemukan.', 16, 1);

        UPDATE dbo.CANDIDATE_DOCUMENTS
        SET status_verifikasi = @status,
            catatan_verifikasi = @catatan,
            diverifikasi_oleh = @oleh_user,
            diverifikasi_pada = GETDATE()
        WHERE id_cand_doc = @id_cand_doc;

        INSERT INTO dbo.APPLICATION_HISTORY (id_lamaran, jenis_event, deskripsi, oleh_user)
        VALUES (@id_lamaran, 'DOKUMEN',
                'Verifikasi dokumen #' + CONVERT(VARCHAR(10), @id_cand_doc) + ' -> ' + @status
                + ISNULL(' | ' + @catatan, ''), @oleh_user);

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION VerifDoc;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
