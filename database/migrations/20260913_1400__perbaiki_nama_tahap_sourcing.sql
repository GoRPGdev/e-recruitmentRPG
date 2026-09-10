/* =========================================================================
   20260913_1400__perbaiki_nama_tahap_sourcing.sql
   Perbaikan data: dbo.M_STAGE untuk kode_stage 'SOURCING' punya
   nama_tahap = 'x' (placeholder yang ikut ke alur STD_RPG dan tampil
   sebagai kolom "X" di matriks funnel dashboard).

   Set nama_tahap yang benar HANYA jika masih berisi placeholder / kosong,
   supaya idempotent dan tidak menimpa penyesuaian manual.
   ========================================================================= */

UPDATE dbo.M_STAGE
SET nama_tahap = N'Sourcing'
WHERE kode_stage = 'SOURCING'
  AND (nama_tahap IS NULL OR LTRIM(RTRIM(nama_tahap)) IN ('', 'x', 'X'));
GO
