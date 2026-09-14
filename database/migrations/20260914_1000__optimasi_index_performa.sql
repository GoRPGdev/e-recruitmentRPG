/* =========================================================================
   20260914_1000__optimasi_index_performa.sql
   Optimalisasi indeks database kueri transaksi tinggi:
   - APPLICATIONS (id_req, status_global) & (id_kandidat)
   - APPLICATION_STAGES (id_lamaran, status_tahap)
   - APPLICATION_HISTORY (id_lamaran, waktu)
   - CANDIDATE_DOCUMENTS (id_lamaran)
   - REQUISITIONS (status_req, id_posisi)

   Idempotent: aman dijalankan berulang.
   ========================================================================= */

-- 1. Index untuk kueri Pipeline dan Dashboard per lowongan
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_APP_req_status' AND object_id = OBJECT_ID('dbo.APPLICATIONS')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_APP_req_status
    ON dbo.APPLICATIONS (id_req, status_global)
    INCLUDE (id_kandidat, id_stage_sekarang, id_remark_terakhir, tanggal_lamar);
END;
GO

-- 2. Index untuk relasi pencarian pelamar per kandidat
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_APP_kandidat' AND object_id = OBJECT_ID('dbo.APPLICATIONS')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_APP_kandidat
    ON dbo.APPLICATIONS (id_kandidat, tanggal_lamar DESC)
    INCLUDE (id_req, status_global);
END;
GO

-- 3. Index untuk tahapan seleksi aktif dan urutan alur per lamaran
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_APP_STAGES_lamaran_status' AND object_id = OBJECT_ID('dbo.APPLICATION_STAGES')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_APP_STAGES_lamaran_status
    ON dbo.APPLICATION_STAGES (id_lamaran, status_tahap)
    INCLUDE (id_stage, urutan, tanggal_mulai, id_remark, catatan);
END;
GO

-- 4. Index untuk riwayat log audit dan timeline per lamaran
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_APP_HIST_lamaran_waktu' AND object_id = OBJECT_ID('dbo.APPLICATION_HISTORY')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_APP_HIST_lamaran_waktu
    ON dbo.APPLICATION_HISTORY (id_lamaran, waktu DESC)
    INCLUDE (jenis_event, id_stage_dari, id_stage_ke, id_remark, oleh_user);
END;
GO

-- 5. Index pencarian berkas / dokumen per lamaran
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_CAND_DOC_lamaran' AND object_id = OBJECT_ID('dbo.CANDIDATE_DOCUMENTS')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_CAND_DOC_lamaran
    ON dbo.CANDIDATE_DOCUMENTS (id_lamaran, id_dokumen);
END;
GO

-- 6. Index pencarian requisition / MPR berdasarkan status dan posisi
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_REQ_status_posisi' AND object_id = OBJECT_ID('dbo.REQUISITIONS')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_REQ_status_posisi
    ON dbo.REQUISITIONS (status_req, id_posisi)
    INCLUDE (id_outlet, id_user_pemohon, jumlah_dibutuhkan, jumlah_terpenuhi, created_at);
END;
GO
