/* =========================================================================
   sp_SetRetensi  --  hitung ulang CANDIDATES.retensi_sampai untuk 1 lamaran
   E-Recruitment RPG  --  Fase 4 Keamanan & Kepatuhan (Kiki)

   RENCANA sec.6 Fase 4: 12 bln sejak ditolak, 24 bln jika setuju talent pool.

   Dipanggil setiap kali status_global sebuah lamaran berubah jadi final
   (dari sp_AdvanceStage & sp_LogContact). Idempotent & order-independent:
   menghitung dari kondisi SEMUA lamaran milik kandidat itu.

     - ada 1 lamaran Hired            -> retensi_sampai = NULL (jadi karyawan)
     - status final non-Hired         -> hari_ini + 12 bln
       ... + kandidat setuju talent pool ATAU status Talent_Pool -> 24 bln
     - retensi lama yang lebih jauh   -> tidak diperpendek
     - status belum final             -> retensi_sampai dibiarkan

   Perubahan dicatat ke AUDIT_LOG. Pola savepoint (aman dipanggil bersarang).
   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SetRetensi') IS NOT NULL DROP PROCEDURE dbo.sp_SetRetensi;
GO

CREATE PROCEDURE dbo.sp_SetRetensi
    @id_lamaran INT,
    @oleh_user  INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SetRetensi;

        DECLARE @id_kandidat INT, @st VARCHAR(20);
        SELECT @id_kandidat = id_kandidat, @st = status_global
        FROM dbo.APPLICATIONS WHERE id_lamaran = @id_lamaran;

        IF @id_kandidat IS NULL
            RAISERROR('sp_SetRetensi: lamaran #%d tidak ditemukan.', 16, 1, @id_lamaran);

        DECLARE @lama DATE, @tp BIT;
        SELECT @lama = retensi_sampai, @tp = setuju_talent_pool
        FROM dbo.CANDIDATES WHERE id_kandidat = @id_kandidat;

        DECLARE @baru DATE;

        IF EXISTS (SELECT 1 FROM dbo.APPLICATIONS
                   WHERE id_kandidat = @id_kandidat AND status_global = 'Hired')
            SET @baru = NULL;
        ELSE IF @st IN ('Rejected','Withdrawn','Offer_Declined','No_Show','Talent_Pool')
        BEGIN
            DECLARE @bulan INT = CASE WHEN @tp = 1 OR @st = 'Talent_Pool' THEN 24 ELSE 12 END;
            SET @baru = DATEADD(MONTH, @bulan, CONVERT(DATE, GETDATE()));
            IF @lama IS NOT NULL AND @lama > @baru
                SET @baru = @lama;                       -- jangan perpendek
        END
        ELSE
            SET @baru = @lama;                           -- status belum final

        -- CONVERT style 112 = yyyymmdd; 'x' sebagai penanda NULL biar beda dari tanggal
        IF ISNULL(CONVERT(CHAR(8), @baru, 112), 'x') <> ISNULL(CONVERT(CHAR(8), @lama, 112), 'x')
        BEGIN
            UPDATE dbo.CANDIDATES SET retensi_sampai = @baru WHERE id_kandidat = @id_kandidat;

            -- EXEC tak menerima ekspresi sebagai nilai parameter -> rakit dulu di variabel
            DECLARE @av_lama VARCHAR(200) =
                'retensi_sampai=' + ISNULL(CONVERT(VARCHAR(10), @lama, 23), 'NULL');
            DECLARE @av_baru VARCHAR(400) =
                'retensi_sampai=' + ISNULL(CONVERT(VARCHAR(10), @baru, 23), 'NULL')
                + ' | lamaran #' + CONVERT(VARCHAR(12), @id_lamaran) + ' -> ' + @st;

            EXEC dbo.sp_AuditLog
                @nama_tabel = 'CANDIDATES',
                @id_baris   = @id_kandidat,
                @aksi       = 'UPDATE',
                @nilai_lama = @av_lama,
                @nilai_baru = @av_baru,
                @oleh_user  = @oleh_user;
        END

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SetRetensi;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
