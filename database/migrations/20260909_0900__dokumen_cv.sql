/* =========================================================================
   20260909_0900__dokumen_cv.sql
   Tambah jenis dokumen "CV" ke M_DOKUMEN.

   CANDIDATE_DOCUMENTS.id_dokumen (NOT NULL FK) mensyaratkan baris M_DOKUMEN
   untuk CV -- form publik menyimpan CV + hash_sha256 (deteksi CV duplikat,
   ERD sec.7.4).

   "CV" bukan IDENTITAS/PENDIDIKAN/FINANSIAL -> kategori baru 'LAMARAN'.
   tingkat_sensitif = 'UMUM' (boleh dilihat semua role yang punya LIHAT_CV).
   File koreksi baru -- 20260908_1000 tidak diedit.
   ========================================================================= */

IF NOT EXISTS (
    SELECT 1 FROM sys.check_constraints
    WHERE name = 'CK_M_DOKUMEN_kategori'
      AND OBJECT_NAME(parent_object_id) = 'M_DOKUMEN'
      AND definition LIKE '%LAMARAN%')
BEGIN
    ALTER TABLE dbo.M_DOKUMEN DROP CONSTRAINT CK_M_DOKUMEN_kategori;
    ALTER TABLE dbo.M_DOKUMEN ADD CONSTRAINT CK_M_DOKUMEN_kategori
        CHECK (kategori IN ('IDENTITAS','PENDIDIKAN','FINANSIAL','LAMARAN'));
END
GO

INSERT INTO dbo.M_DOKUMEN (nama_dokumen, kategori, tingkat_sensitif, is_mandatory_default)
SELECT N'CV', 'LAMARAN', 'UMUM', 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_DOKUMEN WHERE nama_dokumen = N'CV');
GO
