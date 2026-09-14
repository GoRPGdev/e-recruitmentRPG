/* =========================================================================
   sp_InsertAdHocStage  --  sisip tahap ad-hoc ke SATU lamaran
   E-Recruitment RPG  --  Flow Engine (Kahfi)

   Aturan bisnis #2 (ERD sec.9): HR boleh menambah tahap ke satu lamaran
   tanpa mengubah template. Saat menyisip, urutan seluruh baris lamaran itu
   di-renumber dalam SATU transaksi. Tahap sisipan is_sisipan = 1.

   @setelah_urutan : tahap baru diletakkan pada (urutan + 1); baris dengan
   urutan > @setelah_urutan digeser +1 lebih dulu (constraint UNIQUE
   (id_lamaran, urutan) tetap valid karena shift = 1 statement).

   @id_remark      : keputusan/remark lolos untuk menyelesaikan tahap saat ini
                     sebelum kandidat berpindah ke tahap tambahan.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_InsertAdHocStage') IS NOT NULL DROP PROCEDURE dbo.sp_InsertAdHocStage;
GO

CREATE PROCEDURE dbo.sp_InsertAdHocStage
    @id_lamaran     INT,
    @id_stage       INT,
    @setelah_urutan INT,
    @pic_user       INT          = NULL,
    @catatan        VARCHAR(MAX) = NULL,
    @id_remark      INT          = NULL,
    @id_app_stage   INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION AdHoc;

        DECLARE @st_global VARCHAR(20);
        SELECT @st_global = status_global FROM dbo.APPLICATIONS WHERE id_lamaran = @id_lamaran;
        IF @st_global IS NULL
            RAISERROR('Lamaran tidak ditemukan.', 16, 1);
        IF @st_global IN ('Hired','Rejected','Withdrawn','Offer_Declined','No_Show')
            RAISERROR('Lamaran sudah final, tidak bisa disisipi tahap.', 16, 1);
        IF NOT EXISTS (SELECT 1 FROM dbo.M_STAGE WHERE id_stage = @id_stage AND is_aktif = 1)
            RAISERROR('Tahap tidak valid / nonaktif.', 16, 1);
        IF EXISTS (SELECT 1 FROM dbo.APPLICATION_STAGES WHERE id_lamaran = @id_lamaran AND id_stage = @id_stage)
        BEGIN
            DECLARE @nama_stage_duplikat NVARCHAR(100);
            SELECT @nama_stage_duplikat = nama_tahap FROM dbo.M_STAGE WHERE id_stage = @id_stage;
            RAISERROR('Kandidat sudah pernah berada pada tahap "%s". Tahap yang sama tidak dapat disisipkan kembali.', 16, 1, @nama_stage_duplikat);
        END

        -- Validasi @id_remark jika diinput (harus remark dengan efek status Lanjut)
        DECLARE @label_remark VARCHAR(100) = NULL;
        IF @id_remark IS NOT NULL
        BEGIN
            DECLARE @efek_rmk VARCHAR(20);
            SELECT @efek_rmk = efek_status, @label_remark = label
            FROM dbo.M_REMARKS
            WHERE id_remark = @id_remark AND is_aktif = 1;

            IF @efek_rmk IS NULL
                RAISERROR('Remark tidak valid atau nonaktif.', 16, 1);
            IF @efek_rmk <> 'LANJUT' AND @efek_rmk <> 'HIRED'
                RAISERROR('Hanya keputusan/remark dengan status Lanjut yang dapat dipilih untuk menyisipkan tahap.', 16, 1);
        END

        -- Tentukan urutan tahap saat ini milik kandidat
        DECLARE @curr_urutan INT = NULL, @curr_app_stage INT = NULL;
        SELECT TOP 1 @curr_urutan = aps.urutan, @curr_app_stage = aps.id_app_stage
        FROM dbo.APPLICATION_STAGES aps
        JOIN dbo.APPLICATIONS a ON a.id_lamaran = aps.id_lamaran AND a.id_stage_sekarang = aps.id_stage
        WHERE aps.id_lamaran = @id_lamaran;

        IF @curr_urutan IS NULL
        BEGIN
            SELECT TOP 1 @curr_urutan = urutan, @curr_app_stage = id_app_stage
            FROM dbo.APPLICATION_STAGES
            WHERE id_lamaran = @id_lamaran AND status_tahap = 'Berjalan'
            ORDER BY urutan;
        END

        IF @curr_urutan IS NULL SET @curr_urutan = ISNULL(@setelah_urutan, 0);

        DECLARE @urut_baru INT = @curr_urutan + 1;

        -- geser dulu (1 statement -> constraint UNIQUE dicek di akhir statement)
        UPDATE dbo.APPLICATION_STAGES
        SET urutan = urutan + 1
        WHERE id_lamaran = @id_lamaran AND urutan >= @urut_baru;

        -- tandai tahap yg sedang Berjalan jadi Lulus (selesai) dengan id_remark yang dipilih
        UPDATE dbo.APPLICATION_STAGES
        SET status_tahap = 'Lulus',
            id_remark = COALESCE(@id_remark, id_remark),
            tanggal_selesai = GETDATE(),
            catatan = CASE
                WHEN @catatan IS NOT NULL AND catatan IS NOT NULL AND LEN(catatan) > 0
                    THEN catatan + CHAR(13) + CHAR(10) + '[Catatan Sisip Tahap]: ' + @catatan
                WHEN @catatan IS NOT NULL
                    THEN '[Catatan Sisip Tahap]: ' + @catatan
                ELSE catatan
            END
        WHERE id_lamaran = @id_lamaran AND (id_app_stage = @curr_app_stage OR status_tahap = 'Berjalan');

        -- sisip tahap baru langsung aktif (Berjalan) agar muncul di pipeline
        INSERT INTO dbo.APPLICATION_STAGES (id_lamaran, id_stage, urutan, status_tahap, is_sisipan, pic_user, catatan, tanggal_mulai)
        VALUES (@id_lamaran, @id_stage, @urut_baru, 'Berjalan', 1, @pic_user, @catatan, GETDATE());

        SET @id_app_stage = SCOPE_IDENTITY();

        -- pindahkan pointer aktif kandidat ke tahap sisipan dan pastikan status In_Progress
        UPDATE dbo.APPLICATIONS
        SET id_stage_sekarang = @id_stage,
            status_global = 'In_Progress'
        WHERE id_lamaran = @id_lamaran;

        DECLARE @nama_tahap_baru NVARCHAR(100);
        SELECT @nama_tahap_baru = nama_tahap FROM dbo.M_STAGE WHERE id_stage = @id_stage;

        INSERT INTO dbo.APPLICATION_HISTORY (id_lamaran, jenis_event, id_stage_ke, deskripsi, oleh_user)
        VALUES (@id_lamaran, 'STAGE_CHANGE', @id_stage,
                'Tahap sisipan (' + ISNULL(@nama_tahap_baru, 'Tahap #' + CONVERT(VARCHAR(10), @id_stage)) + ') ditambahkan pada urutan ' + CONVERT(VARCHAR(10), @urut_baru)
                + ISNULL(' | Keputusan tahap sebelumnya: ' + @label_remark, '')
                + ISNULL(' | Catatan: ' + @catatan, ''), @pic_user);

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION AdHoc;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
