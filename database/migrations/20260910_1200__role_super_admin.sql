/* =========================================================================
   20260910_1200__role_super_admin.sql
   Menambahkan Role SUPER_ADMIN (Super Administrator) yang memiliki
   seluruh hak akses (semua permissions di dbo.M_PERMISSIONS) tanpa
   restriksi departemen (id_departemen = NULL).

   Sesuai batasan T-SQL SQL Server 2008 R2:
   - Tidak menggunakan THROW
   - Menggunakan WHERE NOT EXISTS agar idempotent
   ========================================================================= */

-- 1. Tambah Role SUPER_ADMIN di M_ROLES jika belum ada
IF NOT EXISTS (SELECT 1 FROM dbo.M_ROLES WHERE kode_role = 'SUPER_ADMIN')
BEGIN
    INSERT INTO dbo.M_ROLES (kode_role, nama_role, is_aktif)
    VALUES ('SUPER_ADMIN', N'Super Administrator', 1);
END
GO

-- 2. Berikan SELURUH permission aktif di dbo.M_PERMISSIONS ke SUPER_ADMIN
INSERT INTO dbo.M_ROLE_PERMISSIONS (id_role, id_permission)
SELECT r.id_role, p.id_permission
FROM dbo.M_ROLES r
CROSS JOIN dbo.M_PERMISSIONS p
WHERE r.kode_role = 'SUPER_ADMIN'
  AND NOT EXISTS (
      SELECT 1 FROM dbo.M_ROLE_PERMISSIONS rp
      WHERE rp.id_role = r.id_role AND rp.id_permission = p.id_permission
  );
GO
