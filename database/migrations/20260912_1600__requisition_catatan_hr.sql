/* =========================================================================
   20260912_1600__requisition_catatan_hr.sql
   E-Recruitment RPG -- Requisition Feedback & Revision
   1. Menambahkan kolom catatan_hr pada dbo.REQUISITIONS untuk menyimpan feedback/arahan revisi dari HR.
   2. Menambahkan status 'Revisi_HR' pada constraint CK_REQ_status.
   ========================================================================= */

-- 1. Tambah kolom catatan_hr jika belum ada
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('dbo.REQUISITIONS') AND name = 'catatan_hr'
)
BEGIN
    ALTER TABLE dbo.REQUISITIONS
    ADD catatan_hr NVARCHAR(MAX) NULL;
END
GO

-- 2. Update Check Constraint CK_REQ_status untuk mendukung 'Revisi_HR'
IF OBJECT_ID('dbo.CK_REQ_status', 'C') IS NOT NULL
    ALTER TABLE dbo.REQUISITIONS DROP CONSTRAINT CK_REQ_status;
GO

ALTER TABLE dbo.REQUISITIONS ADD CONSTRAINT CK_REQ_status CHECK (status_req IN (
    'Draft',
    'Review_HR',
    'Revisi_HR',
    'Menunggu_BOD',
    'Approved',
    'Sourcing',
    'Sourcing_Ulang',
    'Terpenuhi_Sebagian',
    'Terpenuhi',
    'Ditolak_HR',
    'Ditolak_BOD',
    'Dibatalkan',
    'Kadaluarsa'
));
GO
