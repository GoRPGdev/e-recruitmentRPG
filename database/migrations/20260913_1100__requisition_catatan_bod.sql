/* =========================================================================
   20260913_1100__requisition_catatan_bod.sql
   E-Recruitment RPG -- Requisition Feedback & Revision BOD
   Menambahkan kolom catatan_bod pada dbo.REQUISITIONS untuk menyimpan
   feedback/arahan revisi dan alasan penolakan langsung dari Direksi (BOD).
   ========================================================================= */

-- Tambah kolom catatan_bod jika belum ada
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('dbo.REQUISITIONS') AND name = 'catatan_bod'
)
BEGIN
    ALTER TABLE dbo.REQUISITIONS
    ADD catatan_bod NVARCHAR(MAX) NULL;
END
GO
