/* =========================================================================
   sp_SaveFlow  --  tambah / ubah header flow template
   E-Recruitment RPG  --  Flow Builder (Kahfi)

   @id_flow NULL -> INSERT (versi 1). kode_flow unik.
   Perubahan susunan tahap ditangani terpisah (edit M_FLOW_STAGE +
   sp_BumpFlowVersion). Lamaran berjalan tidak terpengaruh (di-snapshot).
   Audit trail dicatat via sp_AuditLog.

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
    @id_flow_out       INT OUTPUT,
    @oleh_user         INT           = NULL
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

            DECLARE @nb VARCHAR(400) = 'kode=' + @kode_flow + ', nama=' + @nama_flow + ', tipe=' + @tipe_penempatan;
            EXEC dbo.sp_AuditLog 'M_FLOW', @id_flow_out, 'INSERT', NULL, @nb, @oleh_user;
        END
        ELSE
        BEGIN
            DECLARE @old_kode VARCHAR(30), @old_nama NVARCHAR(100), @old_tipe VARCHAR(10);
            SELECT @old_kode = kode_flow, @old_nama = nama_flow, @old_tipe = tipe_penempatan
            FROM dbo.M_FLOW WHERE id_flow = @id_flow;

            UPDATE dbo.M_FLOW
            SET kode_flow = @kode_flow, nama_flow = @nama_flow, tipe_penempatan = @tipe_penempatan,
                maks_upaya_kontak = @maks_upaya_kontak, sla_total_hari = @sla_total_hari
            WHERE id_flow = @id_flow;
            SET @id_flow_out = @id_flow;

            DECLARE @nl VARCHAR(400) = 'kode=' + ISNULL(@old_kode,'') + ', nama=' + ISNULL(@old_nama,'') + ', tipe=' + ISNULL(@old_tipe,'');
            DECLARE @nb2 VARCHAR(400) = 'kode=' + @kode_flow + ', nama=' + @nama_flow + ', tipe=' + @tipe_penempatan;
            EXEC dbo.sp_AuditLog 'M_FLOW', @id_flow, 'UPDATE', @nl, @nb2, @oleh_user;
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
