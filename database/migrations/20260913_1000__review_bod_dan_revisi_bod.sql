/* =========================================================================
   20260913_1000__review_bod_dan_revisi_bod.sql
   E-Recruitment RPG -- Alur Review BOD & Revisi BOD

   1. Drop check constraint lama CK_REQ_status
   2. Migrasi data: Menunggu_BOD → Review_BOD
   3. Pasang constraint baru CK_REQ_status: hapus Menunggu_BOD, tambah Review_BOD & Revisi_BOD
   ========================================================================= */

-- 1. Drop Check Constraint lama terlebih dahulu
IF OBJECT_ID('dbo.CK_REQ_status', 'C') IS NOT NULL
    ALTER TABLE dbo.REQUISITIONS DROP CONSTRAINT CK_REQ_status;
GO

-- 2. Migrasi data existing
UPDATE dbo.REQUISITIONS SET status_req = 'Review_BOD' WHERE status_req = 'Menunggu_BOD';
GO

-- 3. Pasang Check Constraint baru (14 status)
ALTER TABLE dbo.REQUISITIONS ADD CONSTRAINT CK_REQ_status CHECK (status_req IN (
    'Draft',
    'Review_HR',
    'Revisi_HR',
    'Review_BOD',
    'Revisi_BOD',
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
