/* =========================================================================
   sp_BuildFunnelHarian  --  isi RPT_FUNNEL_HARIAN untuk 1 tanggal
   E-Recruitment RPG  --  Report (Kiki) -- dipanggil job Windows Task Scheduler

   Snapshot posisi funnel HARI INI: jumlah lamaran per
   (requisition, flow, tipe_tahap tahap-kini, status_global).
   Dashboard baca dari sini, bukan join berlapis langsung (ERD sec.2 & sec.7.2).

   Idempotent per tanggal: hapus baris tanggal itu lalu isi ulang.

   Deploy:  php tools/migrate.php proc
   Jadwalkan harian:  php tools/build-funnel.php   (lihat tools/)
   ========================================================================= */
IF OBJECT_ID('dbo.sp_BuildFunnelHarian') IS NOT NULL DROP PROCEDURE dbo.sp_BuildFunnelHarian;
GO

CREATE PROCEDURE dbo.sp_BuildFunnelHarian
    @tanggal DATE = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;
    IF @tanggal IS NULL SET @tanggal = CAST(GETDATE() AS DATE);

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION BuildFunnel;

        DELETE FROM dbo.RPT_FUNNEL_HARIAN WHERE tanggal = @tanggal;

        INSERT INTO dbo.RPT_FUNNEL_HARIAN (tanggal, id_req, id_flow, tipe_tahap, status_global, jumlah)
        SELECT
            @tanggal,
            a.id_req,
            MAX(a.id_flow),
            s.tipe_tahap,
            a.status_global,
            COUNT(DISTINCT a.id_lamaran)
        FROM dbo.APPLICATIONS a
        JOIN dbo.APPLICATION_STAGES aps
             ON aps.id_lamaran = a.id_lamaran AND aps.id_stage = a.id_stage_sekarang
        JOIN dbo.M_STAGE s ON s.id_stage = aps.id_stage
        GROUP BY a.id_req, s.tipe_tahap, a.status_global;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION BuildFunnel;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
