/* =========================================================================
   sp_AdvanceStage  --  proses hasil satu tahap lamaran
   E-Recruitment RPG  --  Flow Engine (Kahfi)

   Membaca efek_status dari M_REMARKS lalu menerapkannya (aturan jadi DATA,
   bukan IF bertingkat di SP -- ERD sec.4 & sec.7.1). Menulis APPLICATION_HISTORY
   di transaksi yang sama (aturan bisnis #8). Atomik, pola savepoint.

   @id_remark NULL  -> dianggap "lulus / lanjut" tanpa catatan efek.

   efek_status -> aksi:
     LANJUT         tahap kini Lulus, tahap berikut Berjalan (In_Progress)
     HIRED          tahap Lulus, status Hired, hitung ulang jumlah_terpenuhi
     TOLAK          tahap Tidak_Lulus, status Rejected
     ON_HOLD        tahap tetap Berjalan, status On_Hold
     UNREACHABLE    tahap tetap Berjalan, status Unreachable (reversible)
     WITHDRAWN / OFFER_DECLINED / NO_SHOW / TALENT_POOL
                    tahap Tidak_Lulus, status sesuai

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_AdvanceStage') IS NOT NULL DROP PROCEDURE dbo.sp_AdvanceStage;
GO

CREATE PROCEDURE dbo.sp_AdvanceStage
    @id_app_stage INT,
    @id_remark    INT          = NULL,
    @pic_user     INT          = NULL,
    @catatan      VARCHAR(MAX) = NULL,
    @status_baru  VARCHAR(20) OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION AdvStage;

        DECLARE @id_lamaran INT, @id_stage INT, @urutan INT, @st_tahap VARCHAR(15);
        SELECT @id_lamaran = id_lamaran, @id_stage = id_stage, @urutan = urutan, @st_tahap = status_tahap
        FROM dbo.APPLICATION_STAGES WHERE id_app_stage = @id_app_stage;

        IF @id_lamaran IS NULL
            RAISERROR('Tahap lamaran tidak ditemukan.', 16, 1);

        DECLARE @st_global VARCHAR(20);
        SELECT @st_global = status_global FROM dbo.APPLICATIONS WHERE id_lamaran = @id_lamaran;

        IF @st_global IN ('Hired','Rejected','Withdrawn','Offer_Declined','No_Show','Talent_Pool')
            RAISERROR('Lamaran sudah berstatus final (%s).', 16, 1, @st_global);

        DECLARE @efek VARCHAR(20) = 'LANJUT';
        IF @id_remark IS NOT NULL
        BEGIN
            SELECT @efek = efek_status FROM dbo.M_REMARKS WHERE id_remark = @id_remark AND is_aktif = 1;
            IF @efek IS NULL
                RAISERROR('Remark tidak valid / nonaktif.', 16, 1);
            IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE id_remark = @id_remark AND id_stage = @id_stage)
                RAISERROR('Remark tidak sesuai dengan tahap ini.', 16, 1);
        END

        DECLARE @st_global_baru VARCHAR(20) = @st_global;
        DECLARE @st_tahap_baru  VARCHAR(15) = @st_tahap;
        DECLARE @next_app_stage INT = NULL, @next_id_stage INT = NULL;
        DECLARE @event VARCHAR(20) = 'STATUS_CHANGE';

        IF @efek IN ('LANJUT','HIRED')
        BEGIN
            SET @st_tahap_baru = 'Lulus';

            SELECT TOP 1 @next_app_stage = id_app_stage, @next_id_stage = id_stage
            FROM dbo.APPLICATION_STAGES
            WHERE id_lamaran = @id_lamaran AND urutan > @urutan AND status_tahap = 'Belum'
            ORDER BY urutan;

            IF @efek = 'HIRED'
                SET @st_global_baru = 'Hired';
            ELSE IF @next_app_stage IS NOT NULL
            BEGIN
                SET @st_global_baru = 'In_Progress';   -- keluar dari On_Hold/Unreachable kalau maju
                SET @event = 'STAGE_CHANGE';
            END
        END
        ELSE IF @efek = 'TOLAK'         BEGIN SET @st_tahap_baru = 'Tidak_Lulus'; SET @st_global_baru = 'Rejected'; END
        ELSE IF @efek = 'ON_HOLD'       BEGIN SET @st_global_baru = 'On_Hold'; END
        ELSE IF @efek = 'UNREACHABLE'   BEGIN SET @st_global_baru = 'Unreachable'; END
        ELSE IF @efek = 'WITHDRAWN'     BEGIN SET @st_tahap_baru = 'Tidak_Lulus'; SET @st_global_baru = 'Withdrawn'; END
        ELSE IF @efek = 'OFFER_DECLINED' BEGIN SET @st_tahap_baru = 'Tidak_Lulus'; SET @st_global_baru = 'Offer_Declined'; END
        ELSE IF @efek = 'NO_SHOW'       BEGIN SET @st_tahap_baru = 'Tidak_Lulus'; SET @st_global_baru = 'No_Show'; END
        ELSE IF @efek = 'TALENT_POOL'   BEGIN SET @st_tahap_baru = 'Tidak_Lulus'; SET @st_global_baru = 'Talent_Pool'; END
        ELSE
            RAISERROR('efek_status tidak dikenal: %s', 16, 1, @efek);

        /* -- tulis tahap kini -- */
        UPDATE dbo.APPLICATION_STAGES
        SET status_tahap = @st_tahap_baru,
            id_remark = @id_remark,
            pic_user = COALESCE(@pic_user, pic_user),
            catatan = COALESCE(@catatan, catatan),
            tanggal_selesai = CASE WHEN @st_tahap_baru IN ('Lulus','Tidak_Lulus') THEN GETDATE() ELSE tanggal_selesai END
        WHERE id_app_stage = @id_app_stage;

        /* -- tahap berikut -- */
        IF @next_app_stage IS NOT NULL
        BEGIN
            UPDATE dbo.APPLICATION_STAGES
            SET status_tahap = 'Berjalan', tanggal_mulai = GETDATE()
            WHERE id_app_stage = @next_app_stage;

            UPDATE dbo.APPLICATIONS SET id_stage_sekarang = @next_id_stage WHERE id_lamaran = @id_lamaran;
        END

        /* -- status global lamaran -- */
        UPDATE dbo.APPLICATIONS
        SET status_global = @st_global_baru,
            id_remark_terakhir = COALESCE(@id_remark, id_remark_terakhir)
        WHERE id_lamaran = @id_lamaran;

        /* -- Fase 4 (Kiki): retensi data pelamar saat status jadi final -- */
        IF @st_global_baru <> @st_global
           AND @st_global_baru IN ('Hired','Rejected','Withdrawn','Offer_Declined','No_Show','Talent_Pool')
            EXEC dbo.sp_SetRetensi @id_lamaran = @id_lamaran, @oleh_user = @pic_user;

        /* -- fill rate requisition saat Hired -- */
        IF @efek = 'HIRED'
        BEGIN
            DECLARE @id_req INT = (SELECT id_req FROM dbo.APPLICATIONS WHERE id_lamaran = @id_lamaran);
            DECLARE @hired INT = (SELECT COUNT(*) FROM dbo.APPLICATIONS WHERE id_req = @id_req AND status_global = 'Hired');
            DECLARE @target INT = (SELECT ISNULL(jumlah_disetujui, jumlah_dibutuhkan) FROM dbo.REQUISITIONS WHERE id_req = @id_req);

            UPDATE dbo.REQUISITIONS
            SET jumlah_terpenuhi = @hired,
                status_req = CASE
                    WHEN @hired >= @target THEN 'Terpenuhi'
                    WHEN @hired > 0 AND status_req IN ('Sourcing','Sourcing_Ulang') THEN 'Terpenuhi_Sebagian'
                    ELSE status_req END
            WHERE id_req = @id_req;
        END

        /* -- history -- */
        INSERT INTO dbo.APPLICATION_HISTORY
            (id_lamaran, jenis_event, id_stage_dari, id_stage_ke, status_dari, status_ke, id_remark, deskripsi, oleh_user)
        VALUES
            (@id_lamaran, @event, @id_stage, ISNULL(@next_id_stage, @id_stage), @st_global, @st_global_baru, @id_remark,
             'efek=' + @efek + ISNULL(' | ' + @catatan, ''), @pic_user);

        SET @status_baru = @st_global_baru;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION AdvStage;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
