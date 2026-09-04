/* =========================================================================
   20260910_1000__perm_buat_mpr.sql
   Permission BUAT_MPR -- siapa yang boleh membuat & mengajukan requisition.

   Latar: probe RBAC (docs/MATRIKS_HAK_AKSES.md G6) menemukan
   Requisitions::create/submit terbuka ke SEMUA user login, termasuk VIEWER.
   Keputusan HR (Mas Fachri, 2026-09-04): yang boleh mengajukan MPR =
   User Departemen + HR Admin (+ HR Spv sebagai atasan HR). VIEWER hanya lihat.

   Guard `require_permission('BUAT_MPR')` dipasang di Requisitions::create &
   submit (modul Kahfi) setelah migrasi ini jalan.

   File koreksi -- migrasi lama tidak diedit. Idempotent (WHERE NOT EXISTS).
   ========================================================================= */

INSERT INTO dbo.M_PERMISSIONS (kode, nama)
SELECT 'BUAT_MPR', N'Buat & ajukan requisition (MPR)'
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_PERMISSIONS WHERE kode = 'BUAT_MPR');
GO

INSERT INTO dbo.M_ROLE_PERMISSIONS (id_role, id_permission)
SELECT r.id_role, p.id_permission
FROM (VALUES ('USER_DEPT'), ('HR_ADMIN'), ('HR_SPV')) v(role)
JOIN dbo.M_ROLES       r ON r.kode_role = v.role
JOIN dbo.M_PERMISSIONS p ON p.kode = 'BUAT_MPR'
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.M_ROLE_PERMISSIONS rp
    WHERE rp.id_role = r.id_role AND rp.id_permission = p.id_permission
);
GO
