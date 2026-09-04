/* =========================================================================
   20260910_1300__deactivate_other_roles.sql
   Menyisakan hanya 2 Role aktif: USER_DEPT dan SUPER_ADMIN.
   Role lainnya (IT_ADMIN, HR_ADMIN, HR_SPV, BOD, VIEWER) di-soft-delete (is_aktif = 0).
   Sesuai CLAUDE.md Aturan 4: Master data pakai soft delete (is_aktif = 0).
   ========================================================================= */

-- 1. Nonaktifkan semua role selain USER_DEPT dan SUPER_ADMIN
UPDATE dbo.M_ROLES
SET is_aktif = 0
WHERE kode_role NOT IN ('USER_DEPT', 'SUPER_ADMIN');
GO

-- 2. Pastikan USER_DEPT dan SUPER_ADMIN berstatus aktif
UPDATE dbo.M_ROLES
SET is_aktif = 1
WHERE kode_role IN ('USER_DEPT', 'SUPER_ADMIN');
GO

-- 3. Nonaktifkan user yang berada di bawah role nonaktif
UPDATE u
SET u.is_aktif = 0
FROM dbo.M_USERS u
JOIN dbo.M_ROLES r ON r.id_role = u.id_role
WHERE r.kode_role NOT IN ('USER_DEPT', 'SUPER_ADMIN');
GO

-- 4. Pastikan demo_user_dept dan demo_super_admin aktif
UPDATE dbo.M_USERS
SET is_aktif = 1
WHERE username IN ('demo_user_dept', 'demo_super_admin');
GO
