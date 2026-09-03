/* =========================================================================
   sp_SaveFlow  --  tambah / ubah header flow template
   E-Recruitment RPG  --  Flow Builder (Kahfi)

   @id_flow NULL -> INSERT (versi 1). kode_flow unik.
   Perubahan susunan tahap ditangani terpisah (edit M_FLOW_STAGE +
   sp_BumpFlowVersion). Lamaran berjalan tidak terpengaruh (di-snapshot).

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SaveFlow') IS NOT NULL DROP PROCEDURE dbo.sp_SaveFlow;
GO

CREATE PROCEDURE dbo.sp_SaveFlow
    @id_flow           INT           = NULL,
    @kode_flow         VARCHAR(30),
    @nama_flow         NVARCHAR(100),
    @tipe_penempatan   VARCHAR(10),
    @maks_upaya_kontak INT           = 3,
    @sla_total_hari    INT           = NULL,
    @id_flow_out       INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SaveFlow;

        IF @tipe_penempatan NOT IN ('HQ','OUTLET')
            RAISERROR('tipe_penempatan harus HQ / OUTLET.', 16, 1);
        IF EXISTS (SELECT 1 FROM dbo.M_FLOW WHERE kode_flow = @kode_flow
                   AND (@id_flow IS NULL OR id_flow <> @id_flow))
            RAISERROR('kode_flow sudah dipakai.', 16, 1);

        IF @id_flow IS NULL
        BEGIN
            INSERT INTO dbo.M_FLOW (kode_flow, nama_flow, tipe_penempatan, maks_upaya_kontak, sla_total_hari, versi, is_aktif)
            VALUES (@kode_flow, @nama_flow, @tipe_penempatan, @maks_upaya_kontak, @sla_total_hari, 1, 1);
            SET @id_flow_out = SCOPE_IDENTITY();
        END
        ELSE
        BEGIN
            UPDATE dbo.M_FLOW
            SET kode_flow = @kode_flow, nama_flow = @nama_flow, tipe_penempatan = @tipe_penempatan,
                maks_upaya_kontak = @maks_upaya_kontak, sla_total_hari = @sla_total_hari
            WHERE id_flow = @id_flow;
            SET @id_flow_out = @id_flow;
        END

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SaveFlow;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
