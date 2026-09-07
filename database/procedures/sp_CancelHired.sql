/* =========================================================================
   sp_CancelHired  --  batalkan status Hired kandidat (misal mengundurkan diri)
   E-Recruitment RPG  --  Flow Engine (Kahfi)

   Mengembalikan kandidat dari status 'Hired' ke status final lain ('Withdrawn',
   'Offer_Declined', atau 'Rejected').
   Menyesuaikan kembali jumlah_terpenuhi pada REQUISITIONS, mengembalikan status
   requisition ke 'Sourcing' atau 'Terpenuhi_Sebagian', dan dapat mengaktifkan
   kembali formulir job posting publik jika dibuka kembali.
   Menulis catatan audit ke APPLICATION_HISTORY secara atomik.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_CancelHired') IS NOT NULL DROP PROCEDURE dbo.sp_CancelHired;
GO

CREATE PROCEDURE dbo.sp_CancelHired
    @id_lamaran             INT,
    @status_tujuan          VARCHAR(20)  = 'Withdrawn',
    @alasan                 VARCHAR(MAX) = NULL,
    @pic_user               INT          = NULL,
    @buka_kembali_posting   BIT          = 1,
    @status_baru            VARCHAR(20)  OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION CancelHired;

        IF @id_lamaran IS NULL
            RAISERROR('ID Lamaran harus diisi.', 16, 1);

        DECLARE @st_global VARCHAR(20), @id_req INT, @id_stage_sekarang INT;
        SELECT @st_global = status_global, @id_req = id_req, @id_stage_sekarang = id_stage_sekarang
        FROM dbo.APPLICATIONS
        WHERE id_lamaran = @id_lamaran;

        IF @st_global IS NULL
            RAISERROR('Lamaran tidak ditemukan.', 16, 1);

        IF @st_global <> 'Hired'
            RAISERROR('Hanya pelamar dengan status Hired yang dapat dibatalkan (status saat ini: %s).', 16, 1, @st_global);

        IF @status_tujuan NOT IN ('Withdrawn', 'Offer_Declined', 'Rejected')
            RAISERROR('Status tujuan pembatalan tidak valid (%s). Harus Withdrawn, Offer_Declined, atau Rejected.', 16, 1, @status_tujuan);

        /* 1. Update tahap terakhir yang Lulus menjadi Tidak_Lulus */
        DECLARE @last_app_stage INT = NULL, @last_stage INT = NULL;
        SELECT TOP 1 @last_app_stage = id_app_stage, @last_stage = id_stage
        FROM dbo.APPLICATION_STAGES
        WHERE id_lamaran = @id_lamaran AND status_tahap = 'Lulus'
        ORDER BY urutan DESC;

        IF @last_app_stage IS NOT NULL
        BEGIN
            UPDATE dbo.APPLICATION_STAGES
            SET status_tahap = 'Tidak_Lulus',
                catatan = ISNULL(catatan + ' | ', '') + 'Batal Hired: ' + ISNULL(@alasan, '-')
            WHERE id_app_stage = @last_app_stage;
        END

        /* 2. Update status lamaran */
        UPDATE dbo.APPLICATIONS
        SET status_global = @status_tujuan
        WHERE id_lamaran = @id_lamaran;

        /* 3. Hitung ulang pemenuhan kuota pada REQUISITIONS */
        DECLARE @hired INT;
        SELECT @hired = COUNT(*)
        FROM dbo.APPLICATIONS
        WHERE id_req = @id_req AND status_global = 'Hired';

        DECLARE @target INT, @status_req_saat_ini VARCHAR(30);
        SELECT @target = ISNULL(jumlah_disetujui, jumlah_dibutuhkan),
               @status_req_saat_ini = status_req
        FROM dbo.REQUISITIONS
        WHERE id_req = @id_req;

        DECLARE @status_req_baru VARCHAR(30) = @status_req_saat_ini;
        IF @hired >= @target
            SET @status_req_baru = 'Terpenuhi';
        ELSE IF @hired > 0
            SET @status_req_baru = 'Terpenuhi_Sebagian';
        ELSE IF @status_req_saat_ini IN ('Terpenuhi', 'Terpenuhi_Sebagian')
            SET @status_req_baru = 'Sourcing';

        UPDATE dbo.REQUISITIONS
        SET jumlah_terpenuhi = @hired,
            status_req = @status_req_baru
        WHERE id_req = @id_req;

        /* 4. Jika dibuka kembali dan kuota belum penuh, aktifkan kembali posting publik */
        IF @buka_kembali_posting = 1 AND @hired < @target
        BEGIN
            UPDATE dbo.JOB_POSTINGS
            SET form_aktif = 1
            WHERE id_req = @id_req AND is_aktif = 1;
        END

        /* 5. Catat audit history */
        INSERT INTO dbo.APPLICATION_HISTORY
            (id_lamaran, jenis_event, id_stage_dari, id_stage_ke, status_dari, status_ke, deskripsi, oleh_user)
        VALUES
            (@id_lamaran, 'STATUS_CHANGE', ISNULL(@last_stage, @id_stage_sekarang), ISNULL(@last_stage, @id_stage_sekarang),
             'Hired', @status_tujuan, 'Pembatalan status Hired | ' + ISNULL(@alasan, '-'), @pic_user);

        SET @status_baru = @status_tujuan;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION CancelHired;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
