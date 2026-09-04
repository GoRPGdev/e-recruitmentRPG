/* =========================================================================
   20260910_1100__musers_id_departemen.sql
   M_USERS.id_departemen -- departemen milik user (untuk scoping USER_DEPT).

   Latar: G4b (docs/MATRIKS_HAK_AKSES.md). Keputusan HR 2026-09-04 --
   USER_DEPT hanya boleh melihat kandidat dari MPR yang id_departemen-nya
   = departemen user (via id_posisi -> M_POSISI.id_departemen).
   Selama ini M_USERS cuma punya departemen_snapshot (teks) -- tak bisa
   dipakai untuk JOIN yang tegas.

   NULL untuk peran non-departemen (IT_ADMIN, HR_ADMIN, HR_SPV, BOD, VIEWER).
   sp_Login mengembalikan kolom ini; disimpan di sesi saat login.

   Idempotent. File koreksi -- migrasi lama tidak diedit.
   ========================================================================= */

IF COL_LENGTH('dbo.M_USERS', 'id_departemen') IS NULL
    ALTER TABLE dbo.M_USERS ADD id_departemen INT NULL;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.foreign_keys
    WHERE name = 'FK_MUSERS_dept' AND parent_object_id = OBJECT_ID('dbo.M_USERS')
)
    ALTER TABLE dbo.M_USERS
        ADD CONSTRAINT FK_MUSERS_dept FOREIGN KEY (id_departemen)
            REFERENCES dbo.M_DEPARTEMEN (id_departemen);
GO
