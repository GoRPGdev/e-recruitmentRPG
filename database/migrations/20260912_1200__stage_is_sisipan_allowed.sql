/* =========================================================================
   20260912_1200__stage_is_sisipan_allowed.sql
   E-Recruitment RPG -- Flow Engine & Master Stage
   Menambahkan flag is_sisipan_allowed pada dbo.M_STAGE untuk mengatur tahap
   mana saja yang diizinkan disisipkan (ad-hoc) ke lamaran pelamar.
   ========================================================================= */

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('dbo.M_STAGE') AND name = 'is_sisipan_allowed'
)
BEGIN
    ALTER TABLE dbo.M_STAGE
    ADD is_sisipan_allowed BIT NOT NULL CONSTRAINT DF_M_STAGE_sisipan DEFAULT (1);
END
GO

-- Tahap inti awal dan akhir (SCREENING/SOURCING/ONBOARD) default tidak disisipkan
UPDATE dbo.M_STAGE
SET is_sisipan_allowed = 0
WHERE kode_stage IN ('SOURCING', 'SCREENING_CV', 'ONBOARD');
GO
