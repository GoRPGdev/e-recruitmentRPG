/* =========================================================================
   20260909_1030__jobposting_slug_nullable.sql
   JOB_POSTINGS.url_slug -> NULLABLE + filtered unique index.

   Alasan: posting bisa dibuat dulu (oleh modul requisition), slug baru
   di-generate saat HR mengaktifkan link form (Postings::ensure_slug).
   UNIQUE biasa menganggap semua NULL sama -> pola sama seperti
   REQUISITIONS.no_mpr / CANDIDATES.no_wa_normal.

   File koreksi -- 20260908_1030 tidak diedit.
   ========================================================================= */

IF EXISTS (SELECT 1 FROM sys.key_constraints WHERE name = 'UQ_JP_slug'
          AND parent_object_id = OBJECT_ID('dbo.JOB_POSTINGS'))
    ALTER TABLE dbo.JOB_POSTINGS DROP CONSTRAINT UQ_JP_slug;
GO

IF EXISTS (SELECT 1 FROM sys.columns
          WHERE object_id = OBJECT_ID('dbo.JOB_POSTINGS')
            AND name = 'url_slug' AND is_nullable = 0)
    ALTER TABLE dbo.JOB_POSTINGS ALTER COLUMN url_slug VARCHAR(100) NULL;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_JP_slug'
              AND object_id = OBJECT_ID('dbo.JOB_POSTINGS'))
    CREATE UNIQUE INDEX UX_JP_slug ON dbo.JOB_POSTINGS (url_slug) WHERE url_slug IS NOT NULL;
GO
