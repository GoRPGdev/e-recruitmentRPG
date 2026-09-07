/* =========================================================================
   20260911_1100__status_mpr_review_hr_dan_ditolak.sql
   Menambahkan status alur MPR real-case:
   - Review_HR: Permintaan tenaga kerja sedang direview & divalidasi oleh HR
   - Ditolak_HR: Permintaan ditolak oleh HR (mis. formasi belum sesuai budget/analisis beban kerja)
   - Ditolak_BOD: Permintaan ditolak oleh Direksi (BOD)
   ========================================================================= */

-- 1. Drop check constraint lama jika ada
IF OBJECT_ID('dbo.CK_REQ_status', 'C') IS NOT NULL
    ALTER TABLE dbo.REQUISITIONS DROP CONSTRAINT CK_REQ_status;
GO

-- 2. Buat check constraint baru yang mencakup seluruh status real-case
ALTER TABLE dbo.REQUISITIONS ADD CONSTRAINT CK_REQ_status CHECK (status_req IN (
    'Draft',
    'Review_HR',
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
