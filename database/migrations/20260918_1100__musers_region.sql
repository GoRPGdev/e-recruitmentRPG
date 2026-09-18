/* =========================================================================
   20260918_1100__musers_region.sql
   M_USERS.region -- cakupan Regional Manager / Area Leader (lintas outlet).

   Latar: scoping USER_DEPT yang ada (id_departemen) cuma bisa satu
   departemen per user -- cocok untuk Manager Departemen (HQ), tapi TIDAK
   cukup untuk Regional Manager yang mengawasi banyak outlet sekaligus di
   satu wilayah (konsep yang sama dengan "Area Leader" di Payroll).

   region di sini adalah teks bebas yang HARUS cocok persis dengan nilai
   M_OUTLET.region (tidak ada tabel M_REGION terpisah -- itu memang cuma
   kolom teks di M_OUTLET, mengikuti apa yang sudah ada).

   NULL untuk peran yang tidak dibatasi wilayah. Satu user cuma boleh
   punya SALAH SATU dari id_departemen ATAU region terisi, tidak dua-duanya
   (lihat CK_MUSERS_scope) -- keduanya sekaligus tidak punya arti yang jelas
   di skema scoping saat ini.

   Idempotent. File koreksi -- migrasi lama tidak diedit.
   ========================================================================= */

IF COL_LENGTH('dbo.M_USERS', 'region') IS NULL
    ALTER TABLE dbo.M_USERS ADD region NVARCHAR(60) NULL;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.check_constraints
    WHERE name = 'CK_MUSERS_scope' AND parent_object_id = OBJECT_ID('dbo.M_USERS')
)
    ALTER TABLE dbo.M_USERS
        ADD CONSTRAINT CK_MUSERS_scope
        CHECK (id_departemen IS NULL OR region IS NULL);
GO
