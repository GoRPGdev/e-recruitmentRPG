/* =========================================================================
   sp_TogglePostingForm  --  buka atau tutup form lowongan publik oleh HR
   E-Recruitment RPG  --  Posting Engine (Kahfi)

   Mengaktifkan (form_aktif = 1) atau menonaktifkan (form_aktif = 0)
   formulir lamaran publik untuk satu posting lowongan.
   Jika form_aktif NULL, akan melakukan toggle (membalik status saat ini).

   Validasi:
   Jika membuka form, requisition induk tidak boleh berstatus 'Dibatalkan' atau 'Kadaluarsa'.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_TogglePostingForm') IS NOT NULL DROP PROCEDURE dbo.sp_TogglePostingForm;
GO

CREATE PROCEDURE dbo.sp_TogglePostingForm
    @id_posting     INT,
    @form_aktif     BIT = NULL,
    @oleh_user      INT = NULL,
    @status_akhir   BIT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION ToggleForm;

        IF @id_posting IS NULL
            RAISERROR('ID Posting harus diisi.', 16, 1);

        DECLARE @aktif_saat_ini BIT, @id_req INT, @status_req VARCHAR(25);
        SELECT @aktif_saat_ini = jp.form_aktif, @id_req = jp.id_req, @status_req = r.status_req
        FROM dbo.JOB_POSTINGS jp
        JOIN dbo.REQUISITIONS r ON r.id_req = jp.id_req
        WHERE jp.id_posting = @id_posting;

        IF @aktif_saat_ini IS NULL
            RAISERROR('Job Posting tidak ditemukan.', 16, 1);

        DECLARE @target_aktif BIT = CASE
            WHEN @form_aktif IS NOT NULL THEN @form_aktif
            WHEN @aktif_saat_ini = 1 THEN 0
            ELSE 1
        END;

        IF @target_aktif = 1 AND @status_req IN ('Dibatalkan', 'Kadaluarsa')
            RAISERROR('Tidak dapat membuka form lowongan karena MPR berstatus %s.', 16, 1, @status_req);

        UPDATE dbo.JOB_POSTINGS
        SET form_aktif = @target_aktif,
            form_dibuka = CASE WHEN @target_aktif = 1 THEN GETDATE() ELSE form_dibuka END,
            form_ditutup = CASE WHEN @target_aktif = 0 THEN GETDATE() ELSE NULL END
        WHERE id_posting = @id_posting;

        SET @status_akhir = @target_aktif;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION ToggleForm;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
