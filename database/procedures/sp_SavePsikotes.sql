/* =========================================================================
   sp_SavePsikotes  --  catat hasil tes psikotes
   E-Recruitment RPG  --  Flow Engine / Seleksi (Kahfi)

   Menangani tabel PSIKOTES_RESULTS. Retake menghasilkan baris baru
   dengan id_app_stage yang sama.
   Audit trail dicatat via sp_AuditLog.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SavePsikotes') IS NOT NULL DROP PROCEDURE dbo.sp_SavePsikotes;
GO

CREATE PROCEDURE dbo.sp_SavePsikotes
    @id_psikotes   INT           = NULL OUTPUT,
    @id_app_stage  INT,
    @vendor_tes    NVARCHAR(100) = NULL,
    @tanggal_tes   DATE          = NULL,
    @skor_total    INT           = NULL,
    @hasil         VARCHAR(15)   = NULL,       -- Lulus, Tidak_Lulus, Perlu_Review
    @rekomendasi   VARCHAR(MAX)  = NULL,
    @oleh_user     INT           = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SavePsi;

        IF NOT EXISTS (SELECT 1 FROM dbo.APPLICATION_STAGES WHERE id_app_stage = @id_app_stage)
            RAISERROR('Tahap lamaran (APPLICATION_STAGES) tidak ditemukan.', 16, 1);

        IF @hasil IS NOT NULL AND @hasil NOT IN ('Lulus','Tidak_Lulus','Perlu_Review')
            RAISERROR('Hasil psikotes tidak valid. Harus Lulus, Tidak_Lulus, atau Perlu_Review.', 16, 1);

        IF @tanggal_tes IS NULL SET @tanggal_tes = CONVERT(DATE, GETDATE());

        IF @id_psikotes IS NULL OR @id_psikotes <= 0
        BEGIN
            INSERT INTO dbo.PSIKOTES_RESULTS (id_app_stage, vendor_tes, tanggal_tes, skor_total, hasil, rekomendasi, dilakukan_oleh)
            VALUES (@id_app_stage, @vendor_tes, @tanggal_tes, @skor_total, @hasil, @rekomendasi, @oleh_user);
            SET @id_psikotes = SCOPE_IDENTITY();

            DECLARE @nb VARCHAR(300) = 'stage=' + CONVERT(VARCHAR(10), @id_app_stage) + ', vendor=' + ISNULL(@vendor_tes,'-') + ', skor=' + ISNULL(CONVERT(VARCHAR(10), @skor_total),'-') + ', hasil=' + ISNULL(@hasil,'-');
            EXEC dbo.sp_AuditLog 'PSIKOTES_RESULTS', @id_psikotes, 'INSERT', NULL, @nb, @oleh_user;
        END
        ELSE
        BEGIN
            UPDATE dbo.PSIKOTES_RESULTS
            SET vendor_tes = ISNULL(@vendor_tes, vendor_tes),
                tanggal_tes = ISNULL(@tanggal_tes, tanggal_tes),
                skor_total = @skor_total,
                hasil = @hasil,
                rekomendasi = @rekomendasi,
                dilakukan_oleh = ISNULL(@oleh_user, dilakukan_oleh)
            WHERE id_psikotes = @id_psikotes;

            DECLARE @nb2 VARCHAR(300) = 'vendor=' + ISNULL(@vendor_tes,'-') + ', skor=' + ISNULL(CONVERT(VARCHAR(10), @skor_total),'-') + ', hasil=' + ISNULL(@hasil,'-');
            EXEC dbo.sp_AuditLog 'PSIKOTES_RESULTS', @id_psikotes, 'UPDATE', NULL, @nb2, @oleh_user;
        END

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SavePsi;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
