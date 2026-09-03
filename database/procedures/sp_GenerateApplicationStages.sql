/* =========================================================================
   sp_GenerateApplicationStages  --  snapshot flow -> APPLICATION_STAGES
   E-Recruitment RPG

   Aturan bisnis #1 (ERD sec.9 / CLAUDE.md aturan 5): saat lamaran dibuat,
   seluruh tahap di-generate dari M_FLOW_STAGE milik id_flow lamaran itu.
   Perubahan M_FLOW sesudahnya TIDAK menyentuh lamaran yang sudah jalan.

   Idempotent: kalau APPLICATION_STAGES untuk lamaran itu sudah ada -> no-op.
   Transaksi: pola savepoint -- aman dipanggil sendiri ATAU dari dalam
   transaksi caller (mis. sp_SubmitApplication, loop import).

   >>> Komponen Flow Engine (modul Kahfi). Versi ini snapshot dasar;
       sp_AdvanceStage / sp_InsertAdHocStage menyusul.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_GenerateApplicationStages') IS NOT NULL
    DROP PROCEDURE dbo.sp_GenerateApplicationStages;
GO

CREATE PROCEDURE dbo.sp_GenerateApplicationStages
    @id_lamaran INT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION;
        ELSE SAVE TRANSACTION GenStages;

        DECLARE @id_flow INT;
        SELECT @id_flow = id_flow FROM dbo.APPLICATIONS WHERE id_lamaran = @id_lamaran;

        IF @id_flow IS NULL
            RAISERROR('sp_GenerateApplicationStages: lamaran tidak ditemukan atau id_flow kosong.', 16, 1);

        IF NOT EXISTS (SELECT 1 FROM dbo.APPLICATION_STAGES WHERE id_lamaran = @id_lamaran)
        BEGIN
            DECLARE @urutan_awal INT =
                (SELECT MIN(urutan) FROM dbo.M_FLOW_STAGE WHERE id_flow = @id_flow);

            IF @urutan_awal IS NULL
                RAISERROR('sp_GenerateApplicationStages: flow tidak punya tahap (M_FLOW_STAGE kosong).', 16, 1);

            INSERT INTO dbo.APPLICATION_STAGES (id_lamaran, id_stage, urutan, status_tahap, tanggal_mulai)
            SELECT
                @id_lamaran, fs.id_stage, fs.urutan,
                CASE WHEN fs.urutan = @urutan_awal THEN 'Berjalan' ELSE 'Belum' END,
                CASE WHEN fs.urutan = @urutan_awal THEN GETDATE() ELSE NULL END
            FROM dbo.M_FLOW_STAGE fs
            WHERE fs.id_flow = @id_flow;

            UPDATE dbo.APPLICATIONS
            SET id_stage_sekarang =
                (SELECT fs.id_stage FROM dbo.M_FLOW_STAGE fs
                 WHERE fs.id_flow = @id_flow AND fs.urutan = @urutan_awal)
            WHERE id_lamaran = @id_lamaran;

            INSERT INTO dbo.APPLICATION_HISTORY (id_lamaran, jenis_event, id_stage_ke, status_ke, deskripsi)
            SELECT @id_lamaran, 'STAGE_CHANGE',
                   (SELECT fs.id_stage FROM dbo.M_FLOW_STAGE fs
                    WHERE fs.id_flow = @id_flow AND fs.urutan = @urutan_awal),
                   'In_Progress', 'Tahap di-generate dari flow (snapshot).';
        END

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @msg VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0
        BEGIN
            IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        END
        ELSE IF XACT_STATE() = 1
            ROLLBACK TRANSACTION GenStages;
        RAISERROR(@msg, 16, 1);
    END CATCH
END
GO
