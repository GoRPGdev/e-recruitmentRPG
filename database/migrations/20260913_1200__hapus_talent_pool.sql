/* =========================================================================
   20260913_1200__hapus_talent_pool.sql
   E-Recruitment RPG -- Hapus Fitur & Status Talent Pool Menyeluruh
   1. Selaraskan data eksisting APPLICATIONS yang berstatus 'Talent_Pool' -> 'Rejected'
   2. Perbarui constraint CK_APP_status (hapus 'Talent_Pool')
   3. Hapus kolom setuju_talent_pool dari dbo.CANDIDATES
   4. Bersihkan catatan riwayat status
   ========================================================================= */

-- 1. Selaraskan data lamaran & riwayat yang berstatus Talent_Pool
UPDATE dbo.APPLICATIONS
SET status_global = 'Rejected'
WHERE status_global = 'Talent_Pool';

UPDATE dbo.APPLICATION_HISTORY
SET status_ke = 'Rejected'
WHERE status_ke = 'Talent_Pool';

UPDATE dbo.APPLICATION_HISTORY
SET status_dari = 'Rejected'
WHERE status_dari = 'Talent_Pool';
GO

-- 2. Perbarui Constraint CHECK pada dbo.APPLICATIONS
IF EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_APP_status')
BEGIN
    ALTER TABLE dbo.APPLICATIONS DROP CONSTRAINT CK_APP_status;
END
GO

ALTER TABLE dbo.APPLICATIONS ADD CONSTRAINT CK_APP_status
CHECK (status_global IN
    ('In_Progress','On_Hold','Unreachable','Rejected','Withdrawn',
     'Offer_Declined','No_Show','Hired'));
GO

-- 3. Hapus Default Constraint & Kolom setuju_talent_pool pada dbo.CANDIDATES
IF EXISTS (SELECT 1 FROM sys.default_constraints WHERE name = 'DF_CAND_tp')
BEGIN
    ALTER TABLE dbo.CANDIDATES DROP CONSTRAINT DF_CAND_tp;
END
GO

IF EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'setuju_talent_pool'
)
BEGIN
    ALTER TABLE dbo.CANDIDATES DROP COLUMN setuju_talent_pool;
END
GO
