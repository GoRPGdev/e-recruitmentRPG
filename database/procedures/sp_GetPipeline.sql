/* =========================================================================
   sp_GetPipeline  --  semua kandidat + posisinya di flow, untuk 1 requisition
   E-Recruitment RPG  --  Flow Engine (Kahfi)

   SATU query untuk seluruh tahap (ERD sec.3 Fase 3: jangan 1 query per tahap).
   PHP yang meng-group per id_stage untuk papan pipeline vertikal.
   Read-only -> tanpa transaksi.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_GetPipeline') IS NOT NULL DROP PROCEDURE dbo.sp_GetPipeline;
GO

CREATE PROCEDURE dbo.sp_GetPipeline
    @id_req INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        a.id_lamaran,
        c.nama_lengkap,
        c.no_wa_normal,
        a.status_global,
        a.screening_score,
        a.intake_method,
        aps.id_app_stage,
        aps.id_stage,
        aps.urutan,
        aps.status_tahap,
        aps.tanggal_mulai,
        aps.catatan,
        aps.id_remark,
        rm.label AS label_remark,
        s.kode_stage,
        s.nama_tahap,
        s.tipe_tahap,
        fs.role_pic,
        fs.sla_hari,
        DATEDIFF(DAY, aps.tanggal_mulai, GETDATE()) AS hari_di_tahap,
        ft.dipakai_pada  AS form_dipakai_pada,
        ft.token         AS form_token,
        ft.kadaluarsa_pada AS form_kadaluarsa,
        ft.is_revoked    AS form_revoked
    FROM dbo.APPLICATIONS a
    JOIN dbo.CANDIDATES c        ON c.id_kandidat = a.id_kandidat
    JOIN dbo.APPLICATION_STAGES aps ON aps.id_lamaran = a.id_lamaran AND aps.id_stage = a.id_stage_sekarang
    JOIN dbo.M_STAGE s          ON s.id_stage = aps.id_stage
    LEFT JOIN dbo.M_REMARKS rm  ON rm.id_remark = aps.id_remark
    LEFT JOIN dbo.M_FLOW_STAGE fs ON fs.id_flow = a.id_flow AND fs.id_stage = aps.id_stage
    /* ponytail: latest onboarding token per lamaran via correlated subquery;
       upgrade to OUTER APPLY if perf degrades on large datasets */
    LEFT JOIN dbo.FORM_TOKENS ft ON ft.id_token = (
        SELECT TOP 1 ft2.id_token
        FROM dbo.FORM_TOKENS ft2
        WHERE ft2.id_lamaran = a.id_lamaran AND ft2.tujuan = 'FORM_ONBOARDING'
        ORDER BY ft2.id_token DESC
    )
    WHERE a.id_req = @id_req
      AND a.status_global NOT IN ('Hired','Rejected','Withdrawn','Offer_Declined','No_Show')
    ORDER BY aps.urutan, a.id_lamaran;
END
GO
