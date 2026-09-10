/* =========================================================================
   sp_ExtendPosting  --  perpanjang batas waktu lowongan publik & set Sourcing_Ulang
   E-Recruitment RPG  --  Posting Engine (Kahfi)

   Aturan:
   1. Menambah batas tanggal kadaluarsa (form_ditutup) sejumlah @durasi_hari.
   2. Mengaktifkan kembali form publik (form_aktif = 1).
   3. Mengubah status Requisition induk menjadi 'Sourcing_Ulang'.
   4. Menaikkan nomor batch pencarian (batch_ke = batch_ke + 1).
   5. Mencatat audit trail perubahan.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_ExtendPosting') IS NOT NULL DROP PROCEDURE dbo.sp_ExtendPosting;
GO

CREATE PROCEDURE dbo.sp_ExtendPosting
    @id_posting      INT,
    @durasi_hari     INT = 14,
    @oleh_user       INT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION ExtPost;

        IF @id_posting IS NULL
            RAISERROR('ID Posting harus diisi.', 16, 1);

        DECLARE @id_req INT, @status_req VARCHAR(25), @batch_ke INT,
                @tutup_lama DATETIME, @aktif_lama BIT;

        SELECT @id_req     = jp.id_req,
               @status_req = r.status_req,
               @batch_ke   = jp.batch_ke,
               @tutup_lama = jp.form_ditutup,
               @aktif_lama = jp.form_aktif
        FROM dbo.JOB_POSTINGS jp
        JOIN dbo.REQUISITIONS r ON r.id_req = jp.id_req
        WHERE jp.id_posting = @id_posting;

        IF @id_req IS NULL
            RAISERROR('Job Posting tidak ditemukan.', 16, 1);

        IF @status_req IN ('Dibatalkan', 'Kadaluarsa')
            RAISERROR('Tidak dapat memperpanjang lowongan karena MPR berstatus %s.', 16, 1, @status_req);

        IF @status_req = 'Terpenuhi'
            RAISERROR('Tidak dapat memperpanjang lowongan karena formasi MPR telah Terpenuhi.', 16, 1);

        -- Hitung batas penutupan baru: jika tutup_lama masih di masa depan, tambah dari tutup_lama;
        -- jika sudah lewat atau NULL, tambah dari GETDATE().
        DECLARE @base DATETIME = CASE
            WHEN @tutup_lama IS NOT NULL AND @tutup_lama > GETDATE() THEN @tutup_lama
            ELSE GETDATE()
        END;

        DECLARE @tutup_baru DATETIME = DATEADD(DAY, ISNULL(@durasi_hari, 14), @base);

        -- Perbarui Job Posting: aktifkan form, set deadline baru, naikkan batch
        UPDATE dbo.JOB_POSTINGS
        SET form_aktif    = 1,
            form_ditutup  = @tutup_baru,
            tanggal_tutup = CAST(@tutup_baru AS DATE),
            batch_ke      = ISNULL(batch_ke, 1) + 1
        WHERE id_posting  = @id_posting;

        -- Perbarui status Requisition menjadi Sourcing_Ulang
        UPDATE dbo.REQUISITIONS
        SET status_req = 'Sourcing_Ulang'
        WHERE id_req   = @id_req;

        -- Audit log
        DECLARE @n_lama VARCHAR(MAX) = 'status_req=' + @status_req + '; form_ditutup=' + ISNULL(CONVERT(VARCHAR(20), @tutup_lama, 120), '-');
        DECLARE @n_baru VARCHAR(MAX) = 'status_req=Sourcing_Ulang; form_ditutup=' + CONVERT(VARCHAR(20), @tutup_baru, 120) + '; batch_ke=' + CAST(@batch_ke + 1 AS VARCHAR);
        EXEC dbo.sp_AuditLog 'JOB_POSTINGS', @id_posting, 'UPDATE', @n_lama, @n_baru, @oleh_user;
        EXEC dbo.sp_AuditLog 'REQUISITIONS', @id_req, 'UPDATE', @n_lama, @n_baru, @oleh_user;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION ExtPost;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
