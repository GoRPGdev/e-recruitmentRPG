/* =========================================================================
   sp_CloneFlow  --  "simpan sebagai template baru"
   E-Recruitment RPG  --  Flow Builder (Kahfi)

   Menyalin M_FLOW + M_FLOW_STAGE (+ M_FLOW_STAGE_DOKUMEN) ke flow baru:
   kode_flow baru, id_flow_induk = sumber, versi = 1, is_aktif = 1.
   RENCANA sec.3 Fase 3.
   Audit trail dicatat via sp_AuditLog.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_CloneFlow') IS NOT NULL DROP PROCEDURE dbo.sp_CloneFlow;
GO

CREATE PROCEDURE dbo.sp_CloneFlow
    @id_flow_sumber INT,
    @kode_flow_baru VARCHAR(30),
    @nama_flow_baru NVARCHAR(100),
    @id_flow_baru   INT OUTPUT,
    @oleh_user      INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION CloneFlow;

        IF NOT EXISTS (SELECT 1 FROM dbo.M_FLOW WHERE id_flow = @id_flow_sumber)
            RAISERROR('Flow sumber tidak ditemukan.', 16, 1);
        IF EXISTS (SELECT 1 FROM dbo.M_FLOW WHERE kode_flow = @kode_flow_baru)
            RAISERROR('kode_flow baru sudah dipakai.', 16, 1);

        INSERT INTO dbo.M_FLOW (kode_flow, nama_flow, tipe_penempatan, maks_upaya_kontak, sla_total_hari,
                                versi, id_flow_induk, is_aktif)
        SELECT @kode_flow_baru, @nama_flow_baru, tipe_penempatan, maks_upaya_kontak, sla_total_hari,
               1, @id_flow_sumber, 1
        FROM dbo.M_FLOW WHERE id_flow = @id_flow_sumber;

        SET @id_flow_baru = SCOPE_IDENTITY();

        INSERT INTO dbo.M_FLOW_STAGE (id_flow, id_stage, urutan, is_wajib, sla_hari, role_pic)
        SELECT @id_flow_baru, id_stage, urutan, is_wajib, sla_hari, role_pic
        FROM dbo.M_FLOW_STAGE WHERE id_flow = @id_flow_sumber;

        INSERT INTO dbo.M_FLOW_STAGE_DOKUMEN (id_flow_stage, id_dokumen, is_wajib)
        SELECT fsb.id_flow_stage, fsd.id_dokumen, fsd.is_wajib
        FROM dbo.M_FLOW_STAGE_DOKUMEN fsd
        JOIN dbo.M_FLOW_STAGE fss ON fss.id_flow_stage = fsd.id_flow_stage AND fss.id_flow = @id_flow_sumber
        JOIN dbo.M_FLOW_STAGE fsb ON fsb.id_flow = @id_flow_baru AND fsb.id_stage = fss.id_stage;

        DECLARE @nl VARCHAR(100) = 'sumber=' + CONVERT(VARCHAR(10), @id_flow_sumber);
        DECLARE @nb VARCHAR(300) = 'kode=' + @kode_flow_baru + ', nama=' + @nama_flow_baru;
        EXEC dbo.sp_AuditLog 'M_FLOW', @id_flow_baru, 'INSERT', @nl, @nb, @oleh_user;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION CloneFlow;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
