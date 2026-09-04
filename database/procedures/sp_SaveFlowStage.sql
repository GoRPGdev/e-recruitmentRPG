/* =========================================================================
   sp_SaveFlowStage  --  tambah / ubah / hapus tahap dalam sebuah flow
   E-Recruitment RPG  --  Flow Builder (Kahfi)

   @aksi:
     ADD     tambah id_stage di urutan @urutan (geser yang >= +1)
     UPDATE  ubah is_wajib / sla_hari / role_pic untuk id_flow_stage
     REMOVE  hapus id_flow_stage (rapatkan urutan)
     MOVE    pindah id_flow_stage ke @urutan (renumber)

   Selalu menaikkan M_FLOW.versi. Lamaran berjalan tidak terpengaruh
   (di-snapshot ke APPLICATION_STAGES).
   Audit trail dicatat via sp_AuditLog.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SaveFlowStage') IS NOT NULL DROP PROCEDURE dbo.sp_SaveFlowStage;
GO

CREATE PROCEDURE dbo.sp_SaveFlowStage
    @aksi          VARCHAR(10),
    @id_flow       INT,
    @id_flow_stage INT          = NULL,
    @id_stage      INT          = NULL,
    @urutan        INT          = NULL,
    @is_wajib      BIT          = 1,
    @sla_hari      INT          = NULL,
    @role_pic      VARCHAR(20)  = NULL,
    @oleh_user     INT          = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SaveFS;

        IF NOT EXISTS (SELECT 1 FROM dbo.M_FLOW WHERE id_flow = @id_flow)
            RAISERROR('Flow tidak ditemukan.', 16, 1);

        IF @aksi = 'ADD'
        BEGIN
            IF @id_stage IS NULL OR @urutan IS NULL
                RAISERROR('ADD butuh id_stage & urutan.', 16, 1);
            IF EXISTS (SELECT 1 FROM dbo.M_FLOW_STAGE WHERE id_flow = @id_flow AND id_stage = @id_stage)
                RAISERROR('Tahap sudah ada di flow ini.', 16, 1);

            UPDATE dbo.M_FLOW_STAGE SET urutan = urutan + 1 WHERE id_flow = @id_flow AND urutan >= @urutan;
            INSERT INTO dbo.M_FLOW_STAGE (id_flow, id_stage, urutan, is_wajib, sla_hari, role_pic)
            VALUES (@id_flow, @id_stage, @urutan, @is_wajib, @sla_hari, @role_pic);
            DECLARE @new_fs INT = SCOPE_IDENTITY();

            DECLARE @nb VARCHAR(300) = 'flow=' + CONVERT(VARCHAR(10), @id_flow) + ', stage=' + CONVERT(VARCHAR(10), @id_stage) + ', urut=' + CONVERT(VARCHAR(10), @urutan);
            EXEC dbo.sp_AuditLog 'M_FLOW_STAGE', @new_fs, 'INSERT', NULL, @nb, @oleh_user;
        END
        ELSE IF @aksi = 'UPDATE'
        BEGIN
            DECLARE @old_wajib BIT, @old_sla INT, @old_pic VARCHAR(20);
            SELECT @old_wajib = is_wajib, @old_sla = sla_hari, @old_pic = role_pic
            FROM dbo.M_FLOW_STAGE WHERE id_flow_stage = @id_flow_stage AND id_flow = @id_flow;

            UPDATE dbo.M_FLOW_STAGE
            SET is_wajib = @is_wajib, sla_hari = @sla_hari, role_pic = @role_pic
            WHERE id_flow_stage = @id_flow_stage AND id_flow = @id_flow;
            IF @@ROWCOUNT = 0 RAISERROR('Baris flow-stage tidak ditemukan.', 16, 1);

            DECLARE @nl VARCHAR(300) = 'wajib=' + CONVERT(VARCHAR(1), @old_wajib) + ', sla=' + ISNULL(CONVERT(VARCHAR(10), @old_sla),'NULL') + ', pic=' + ISNULL(@old_pic,'-');
            DECLARE @nb2 VARCHAR(300) = 'wajib=' + CONVERT(VARCHAR(1), @is_wajib) + ', sla=' + ISNULL(CONVERT(VARCHAR(10), @sla_hari),'NULL') + ', pic=' + ISNULL(@role_pic,'-');
            EXEC dbo.sp_AuditLog 'M_FLOW_STAGE', @id_flow_stage, 'UPDATE', @nl, @nb2, @oleh_user;
        END
        ELSE IF @aksi = 'REMOVE'
        BEGIN
            DECLARE @u INT = (SELECT urutan FROM dbo.M_FLOW_STAGE WHERE id_flow_stage = @id_flow_stage AND id_flow = @id_flow);
            IF @u IS NULL RAISERROR('Baris flow-stage tidak ditemukan.', 16, 1);
            DELETE FROM dbo.M_FLOW_STAGE WHERE id_flow_stage = @id_flow_stage;
            UPDATE dbo.M_FLOW_STAGE SET urutan = urutan - 1 WHERE id_flow = @id_flow AND urutan > @u;

            DECLARE @nl_rm VARCHAR(300) = 'flow=' + CONVERT(VARCHAR(10), @id_flow) + ', urut=' + CONVERT(VARCHAR(10), @u);
            EXEC dbo.sp_AuditLog 'M_FLOW_STAGE', @id_flow_stage, 'DELETE', @nl_rm, NULL, @oleh_user;
        END
        ELSE IF @aksi = 'MOVE'
        BEGIN
            DECLARE @lama INT = (SELECT urutan FROM dbo.M_FLOW_STAGE WHERE id_flow_stage = @id_flow_stage AND id_flow = @id_flow);
            IF @lama IS NULL OR @urutan IS NULL RAISERROR('MOVE butuh id_flow_stage & urutan.', 16, 1);
            IF @urutan <> @lama
            BEGIN
                -- keluarkan sementara
                UPDATE dbo.M_FLOW_STAGE SET urutan = -1 WHERE id_flow_stage = @id_flow_stage;
                IF @urutan < @lama
                    UPDATE dbo.M_FLOW_STAGE SET urutan = urutan + 1 WHERE id_flow = @id_flow AND urutan >= @urutan AND urutan < @lama;
                ELSE
                    UPDATE dbo.M_FLOW_STAGE SET urutan = urutan - 1 WHERE id_flow = @id_flow AND urutan <= @urutan AND urutan > @lama;
                UPDATE dbo.M_FLOW_STAGE SET urutan = @urutan WHERE id_flow_stage = @id_flow_stage;

                DECLARE @nl_mv VARCHAR(100) = 'urutan=' + CONVERT(VARCHAR(10), @lama);
                DECLARE @nb_mv VARCHAR(100) = 'urutan=' + CONVERT(VARCHAR(10), @urutan);
                EXEC dbo.sp_AuditLog 'M_FLOW_STAGE', @id_flow_stage, 'UPDATE', @nl_mv, @nb_mv, @oleh_user;
            END
        END
        ELSE
            RAISERROR('aksi tidak dikenal.', 16, 1);

        UPDATE dbo.M_FLOW SET versi = versi + 1 WHERE id_flow = @id_flow;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SaveFS;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
