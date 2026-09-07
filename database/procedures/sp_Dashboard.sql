/* =========================================================================
   sp_Dashboard  --  agregat untuk dashboard eksekutif / HR
   E-Recruitment RPG  --  modul Dashboard (Kahfi)

   4 result set:
     1. Metrik per status_global (In_Progress, Hired, Rejected, dll)
     2. Funnel: tipe_tahap x status_global
     3. Aging SLA: lamaran aktif yang melampaui sla_hari tahapnya
     4. Rata-rata hari proses & hari menunggu approval BOD

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_Dashboard') IS NOT NULL DROP PROCEDURE dbo.sp_Dashboard;
GO

CREATE PROCEDURE dbo.sp_Dashboard
    @dari          DATE        = NULL,   -- APPLICATIONS.tanggal_lamar >=
    @sampai        DATE        = NULL,   -- APPLICATIONS.tanggal_lamar <=
    @id_departemen INT         = NULL,
    @id_posisi     INT         = NULL,
    @id_outlet     INT         = NULL,
    @id_flow       INT         = NULL,
    @tipe_tahap    VARCHAR(20) = NULL,
    @status_global VARCHAR(20) = NULL,
    @id_channel    INT         = NULL,   -- dipertahankan opsional untuk kompatibilitas
    @role_pic      VARCHAR(20) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    /* himpunan lamaran ter-filter -> tabel sementara dipakai 4 result set */
    IF OBJECT_ID('tempdb..#dash') IS NOT NULL DROP TABLE #dash;

    SELECT
        a.id_lamaran, a.id_req, a.id_flow, a.status_global, a.tanggal_lamar,
        a.id_stage_sekarang,
        s.tipe_tahap,
        aps.id_app_stage, aps.status_tahap, aps.tanggal_mulai,
        fs.sla_hari, fs.role_pic,
        r.tanggal_pengajuan, r.id_posisi, r.id_outlet,
        p.id_departemen
    INTO #dash
    FROM dbo.APPLICATIONS a
    JOIN dbo.REQUISITIONS r        ON r.id_req = a.id_req
    JOIN dbo.M_POSISI p            ON p.id_posisi = r.id_posisi
    LEFT JOIN dbo.APPLICATION_STAGES aps ON aps.id_lamaran = a.id_lamaran AND aps.id_stage = a.id_stage_sekarang
    LEFT JOIN dbo.M_STAGE s        ON s.id_stage = a.id_stage_sekarang
    LEFT JOIN dbo.M_FLOW_STAGE fs  ON fs.id_flow = a.id_flow AND fs.id_stage = a.id_stage_sekarang
    WHERE (@dari          IS NULL OR a.tanggal_lamar >= @dari)
      AND (@sampai        IS NULL OR a.tanggal_lamar <= @sampai)
      AND (@id_departemen IS NULL OR p.id_departemen = @id_departemen)
      AND (@id_posisi     IS NULL OR r.id_posisi = @id_posisi)
      AND (@id_outlet     IS NULL OR r.id_outlet = @id_outlet)
      AND (@id_flow       IS NULL OR a.id_flow = @id_flow)
      AND (@tipe_tahap    IS NULL OR s.tipe_tahap = @tipe_tahap)
      AND (@status_global IS NULL OR a.status_global = @status_global)
      AND (@role_pic      IS NULL OR fs.role_pic = @role_pic);

    /* 1. METRIK */
    SELECT status_global, COUNT(*) AS jumlah
    FROM #dash GROUP BY status_global;

    /* 2. FUNNEL */
    SELECT ISNULL(tipe_tahap, '(tidak ada tahap)') AS tipe_tahap, status_global, COUNT(*) AS jumlah
    FROM #dash
    GROUP BY tipe_tahap, status_global
    ORDER BY tipe_tahap, status_global;

    /* 3. AGING SLA -- tahap berjalan lewat sla_hari */
    SELECT
        d.id_lamaran, d.id_req, d.tipe_tahap, d.sla_hari,
        DATEDIFF(DAY, d.tanggal_mulai, GETDATE()) AS hari_di_tahap,
        c.nama_lengkap, pos.nama_posisi
    FROM #dash d
    JOIN dbo.APPLICATIONS a ON a.id_lamaran = d.id_lamaran
    JOIN dbo.CANDIDATES c   ON c.id_kandidat = a.id_kandidat
    JOIN dbo.REQUISITIONS r ON r.id_req = d.id_req
    JOIN dbo.M_POSISI pos   ON pos.id_posisi = r.id_posisi
    WHERE d.status_tahap = 'Berjalan'
      AND d.sla_hari IS NOT NULL
      AND DATEDIFF(DAY, d.tanggal_mulai, GETDATE()) > d.sla_hari
    ORDER BY hari_di_tahap DESC;

    /* 4. WAKTU -- dua metrik terpisah (ERD sec.9 aturan 7) */
    SELECT
        (SELECT AVG(CAST(DATEDIFF(DAY, tanggal_pengajuan, GETDATE()) AS FLOAT))
         FROM #dash WHERE status_global IN ('In_Progress','On_Hold','Unreachable') AND tanggal_pengajuan IS NOT NULL)
            AS rata_lama_proses_hari,
        (SELECT AVG(CAST(DATEDIFF(DAY, ra.diajukan_ke_bod_pada, ra.tanggal_keputusan) AS FLOAT))
         FROM dbo.REQUISITION_APPROVALS ra
         WHERE ra.tanggal_keputusan IS NOT NULL
           AND ra.id_req IN (SELECT DISTINCT id_req FROM #dash))
            AS rata_hari_menunggu_approval;

    DROP TABLE #dash;
END
GO
