/* =========================================================================
   20260911_1300__flow_tunggal_standar_rpg.sql
   Menjadikan Flow Pipeline hanya 1 Alur Standar Tunggal Resmi RPG:
   1. Menunjuk flow standar utama (HQ_STAFF / id_flow terkecil yang aktif) sebagai
      satu-satunya alur rekrutmen RPG ('STD_RPG' - 'Alur Standar Rekrutmen RPG').
   2. Mengalihkan seluruh referensi M_POSISI, REQUISITIONS, dan APPLICATIONS
      ke id_flow standar tunggal tersebut.
   3. Menghapus / menonaktifkan flow-flow lama lainnya dari M_FLOW & M_FLOW_STAGE.
   ========================================================================= */

-- 1. Identifikasi id_flow standar (prioritaskan 'HQ_STAFF', jika tidak ada ambil yang aktif terkecil)
DECLARE @std_id INT;
SELECT TOP 1 @std_id = id_flow
FROM dbo.M_FLOW
WHERE is_aktif = 1
ORDER BY CASE WHEN kode_flow = 'HQ_STAFF' THEN 1 WHEN kode_flow = 'STD_RPG' THEN 2 ELSE 3 END, id_flow;

IF @std_id IS NOT NULL
BEGIN
    -- Update identitas flow standar menjadi alur tunggal RPG
    UPDATE dbo.M_FLOW
    SET kode_flow = 'STD_RPG',
        nama_flow = N'Alur Standar Rekrutmen RPG',
        tipe_penempatan = 'HQ',
        maks_upaya_kontak = 3,
        is_aktif = 1
    WHERE id_flow = @std_id;

    -- 2. Arahkan semua default_flow di M_POSISI ke flow standar tunggal
    UPDATE dbo.M_POSISI
    SET default_flow = @std_id;

    -- 3. Arahkan semua id_flow di REQUISITIONS ke flow standar tunggal
    UPDATE dbo.REQUISITIONS
    SET id_flow = @std_id;

    -- 4. Arahkan semua id_flow di APPLICATIONS ke flow standar tunggal
    UPDATE dbo.APPLICATIONS
    SET id_flow = @std_id;

    -- 5. Bersihkan dokumen relasi flow stage lama yang bukan flow standar
    DELETE fsd
    FROM dbo.M_FLOW_STAGE_DOKUMEN fsd
    JOIN dbo.M_FLOW_STAGE fs ON fs.id_flow_stage = fsd.id_flow_stage
    WHERE fs.id_flow <> @std_id;

    -- 6. Hapus tahapan flow lama dari M_FLOW_STAGE yang bukan flow standar
    DELETE FROM dbo.M_FLOW_STAGE
    WHERE id_flow <> @std_id;

    -- 7. Hapus / nonaktifkan flow lama dari M_FLOW
    -- Pertama putuskan relasi id_flow_induk jika ada
    UPDATE dbo.M_FLOW
    SET id_flow_induk = NULL
    WHERE id_flow <> @std_id;

    DELETE FROM dbo.M_FLOW
    WHERE id_flow <> @std_id;
END
GO
