/* =========================================================================
   sp_InsertAdHocStage  --  sisip tahap ad-hoc ke SATU lamaran
   E-Recruitment RPG  --  Flow Engine (Kahfi)

   Aturan bisnis #2 (ERD sec.9): HR boleh menambah tahap ke satu lamaran
   tanpa mengubah template. Saat menyisip, urutan seluruh baris lamaran itu
   di-renumber dalam SATU transaksi. Tahap sisipan is_sisipan = 1.

   @setelah_urutan : tahap baru diletakkan pada (urutan + 1); baris dengan
   urutan > @setelah_urutan digeser +1 lebih dulu (constraint UNIQUE
   (id_lamaran, urutan) tetap valid karena shift = 1 statement).

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
            RAISERROR('Tahap ini sudah ada di lamaran tersebut.', 16, 1);

        DECLARE @urut_baru INT = @setelah_urutan + 1;

        -- geser dulu (1 statement -> constraint UNIQUE dicek di akhir statement)
        UPDATE dbo.APPLICATION_STAGES
        SET urutan = urutan + 1
        WHERE id_lamaran = @id_lamaran AND urutan >= @urut_baru;

        INSERT INTO dbo.APPLICATION_STAGES (id_lamaran, id_stage, urutan, status_tahap, is_sisipan, pic_user, catatan)
        VALUES (@id_lamaran, @id_stage, @urut_baru, 'Belum', 1, @pic_user, @catatan);

        SET @id_app_stage = SCOPE_IDENTITY();

        INSERT INTO dbo.APPLICATION_HISTORY (id_lamaran, jenis_event, id_stage_ke, deskripsi, oleh_user)
        VALUES (@id_lamaran, 'STAGE_CHANGE', @id_stage,
                'Tahap sisipan ditambahkan pada urutan ' + CONVERT(VARCHAR(10), @urut_baru)
                + ISNULL(' | ' + @catatan, ''), @pic_user);

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
