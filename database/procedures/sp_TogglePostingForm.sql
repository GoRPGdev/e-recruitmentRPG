/* =========================================================================
   sp_TogglePostingForm  --  buka atau tutup form lowongan publik oleh HR
   E-Recruitment RPG  --  Posting Engine (Kahfi)

   Mengaktifkan (form_aktif = 1) atau menonaktifkan (form_aktif = 0)
   formulir lamaran publik untuk satu posting lowongan.
   Jika form_aktif NULL, akan melakukan toggle (membalik status saat ini).

   Validasi & Efek Status:
   - Jika membuka form, requisition induk tidak boleh berstatus 'Dibatalkan' atau 'Kadaluarsa'.
   - Jika membuka form saat MPR 'Approved', status beralih menjadi 'Sourcing'.
   - Jika membuka kembali form yang sebelumnya sudah kadaluarsa/ditutup,
     perpanjang batas waktu dan set status MPR menjadi 'Sourcing_Ulang'.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_TogglePostingForm') IS NOT NULL DROP PROCEDURE dbo.sp_TogglePostingForm;
GO

CREATE PROCEDURE dbo.sp_TogglePostingForm
    @id_posting     INT,
    @form_aktif     BIT = NULL,
    @durasi_hari    INT = 14,
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

        DECLARE @aktif_saat_ini BIT, @id_req INT, @status_req VARCHAR(25),
                @ditutup_saat_ini DATETIME;
        SELECT @aktif_saat_ini   = jp.form_aktif,
               @ditutup_saat_ini = jp.form_ditutup,
               @id_req           = jp.id_req,
               @status_req       = r.status_req
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

        IF @target_aktif = 1
        BEGIN
            -- Jika form dibuka kembali dan batas waktu sebelumnya sudah lewat atau NULL,
            -- pasang deadline baru
            DECLARE @deadline_baru DATETIME;
            IF @ditutup_saat_ini IS NULL OR @ditutup_saat_ini <= GETDATE()
                SET @deadline_baru = DATEADD(DAY, ISNULL(@durasi_hari, 14), GETDATE());
            ELSE
                SET @deadline_baru = @ditutup_saat_ini;

            UPDATE dbo.JOB_POSTINGS
            SET form_aktif    = 1,
                form_dibuka   = ISNULL(form_dibuka, GETDATE()),
                form_ditutup  = @deadline_baru,
                tanggal_tutup = CAST(@deadline_baru AS DATE)
            WHERE id_posting  = @id_posting;

            -- Efek status MPR:
            -- Jika Approved -> Sourcing
            -- Jika Sourcing tapi sebelumnya sudah tutup/kadaluarsa -> Sourcing_Ulang
            IF @status_req = 'Approved'
            BEGIN
                UPDATE dbo.REQUISITIONS SET status_req = 'Sourcing' WHERE id_req = @id_req;
            END
            ELSE IF @status_req = 'Sourcing' AND (@aktif_saat_ini = 0 OR (@ditutup_saat_ini IS NOT NULL AND @ditutup_saat_ini <= GETDATE()))
            BEGIN
                UPDATE dbo.REQUISITIONS SET status_req = 'Sourcing_Ulang' WHERE id_req = @id_req;
            END
        END
        ELSE
        BEGIN
            -- Menutup form
            UPDATE dbo.JOB_POSTINGS
            SET form_aktif   = 0,
                form_ditutup = GETDATE(),
                tanggal_tutup= CAST(GETDATE() AS DATE)
            WHERE id_posting = @id_posting;
        END

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
