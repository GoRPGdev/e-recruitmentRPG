/* =========================================================================
   20260910_0900__retensi_anonim.sql
   Fase 4 (Kiki) — retensi data pelamar + jejak anonimisasi.

   RENCANA sec.3.1 / sec.6 Fase 4:
     - retensi_sampai: 12 bln sejak ditolak, 24 bln jika setuju talent pool
       (kolom SUDAH ADA di CANDIDATES sejak 20260908_1100 — tak di-ALTER lagi)
     - job penghapusan/anonimisasi lewat Task Scheduler

   File ini menambah PENANDA hasil anonimisasi + indeks untuk sapuan job:
     CANDIDATES.is_anonim     BIT       -- 1 = PII sudah di-scrub
     CANDIDATES.anonim_pada   DATETIME  -- kapan di-scrub
     IX_CAND_retensi          filtered  -- (retensi_sampai) WHERE belum anonim

   Idempotent: aman dijalankan ulang.
   ========================================================================= */

IF COL_LENGTH('dbo.CANDIDATES', 'is_anonim') IS NULL
    ALTER TABLE dbo.CANDIDATES
        ADD is_anonim BIT NOT NULL CONSTRAINT DF_CAND_anonim DEFAULT (0);
GO

IF COL_LENGTH('dbo.CANDIDATES', 'anonim_pada') IS NULL
    ALTER TABLE dbo.CANDIDATES
        ADD anonim_pada DATETIME NULL;
GO

/* Sapuan job: "kandidat yang retensinya lewat dan belum di-anonim".
   Filtered index -> perlu QUOTED_IDENTIFIER ON (migrate.php pakai sqlsrv, ON). */
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_CAND_retensi' AND object_id = OBJECT_ID('dbo.CANDIDATES')
)
    CREATE INDEX IX_CAND_retensi
        ON dbo.CANDIDATES (retensi_sampai)
        WHERE retensi_sampai IS NOT NULL AND is_anonim = 0;
GO
