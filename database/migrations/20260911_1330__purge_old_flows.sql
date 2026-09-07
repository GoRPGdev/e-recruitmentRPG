/* =========================================================================
   20260911_1330__purge_old_flows.sql
   Menghapus referensi dan data alur rekrutmen lama agar hanya menyisakan
   1 alur rekrutmen standar resmi RPG di dbo.M_FLOW.
   ========================================================================= */

DECLARE @std_id INT;
SELECT TOP 1 @std_id = id_flow
FROM dbo.M_FLOW
WHERE is_aktif = 1
ORDER BY CASE WHEN kode_flow = 'STD_RPG' THEN 1 WHEN kode_flow = 'HQ_STAFF' THEN 2 ELSE 3 END, id_flow;

IF @std_id IS NOT NULL
BEGIN
    -- 1. Alihkan sisa referensi RPT_FUNNEL_HARIAN ke alur standar
    UPDATE dbo.RPT_FUNNEL_HARIAN
    SET id_flow = @std_id
    WHERE id_flow <> @std_id;

    -- 2. Alihkan sisa referensi M_POSISI, REQUISITIONS, APPLICATIONS
    UPDATE dbo.M_POSISI SET default_flow = @std_id WHERE default_flow <> @std_id OR default_flow IS NULL;
    UPDATE dbo.REQUISITIONS SET id_flow = @std_id WHERE id_flow <> @std_id OR id_flow IS NULL;
    UPDATE dbo.APPLICATIONS SET id_flow = @std_id WHERE id_flow <> @std_id OR id_flow IS NULL;

    -- 3. Putuskan relasi hierarki antar flow jika ada
    UPDATE dbo.M_FLOW SET id_flow_induk = NULL WHERE id_flow <> @std_id;

    -- 4. Bersihkan dokumen relasi flow stage lama yang bukan flow standar
    DELETE fsd
    FROM dbo.M_FLOW_STAGE_DOKUMEN fsd
    JOIN dbo.M_FLOW_STAGE fs ON fs.id_flow_stage = fsd.id_flow_stage
    WHERE fs.id_flow <> @std_id;

    -- 5. Hapus tahapan flow lama dari M_FLOW_STAGE yang bukan flow standar
    DELETE FROM dbo.M_FLOW_STAGE
    WHERE id_flow <> @std_id;

    -- 6. Hapus flow lama dari M_FLOW
    DELETE FROM dbo.M_FLOW
    WHERE id_flow <> @std_id;
END
GO
