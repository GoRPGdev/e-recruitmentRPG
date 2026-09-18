/* =========================================================================
   20260918_1200__seed_demo_niks.sql
   Isi NIK Karyawan default untuk akun pengguna sistem e-recruitment:
   - demo_super_admin -> EMP-001
   - demo_user_dept   -> EMP-010
   Idempotent.
   ========================================================================= */

UPDATE dbo.M_USERS
SET nik_karyawan = 'EMP-001'
WHERE username = 'demo_super_admin' AND (nik_karyawan IS NULL OR nik_karyawan = '');

UPDATE dbo.M_USERS
SET nik_karyawan = 'EMP-010'
WHERE username = 'demo_user_dept' AND (nik_karyawan IS NULL OR nik_karyawan = '');
GO
