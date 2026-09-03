/* =========================================================================
   sp_GetUserPermissions  --  daftar kode permission efektif untuk 1 user
   E-Recruitment RPG

   Dipakai helper RBAC di CI3 (disimpan di session saat login).
   Scoping "req sendiri" / "yang dia ikuti" ditegakkan di kode, bukan di sini.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_GetUserPermissions') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetUserPermissions;
GO

CREATE PROCEDURE dbo.sp_GetUserPermissions
    @id_user INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT p.kode
    FROM dbo.M_USERS u
    INNER JOIN dbo.M_ROLE_PERMISSIONS rp ON rp.id_role = u.id_role
    INNER JOIN dbo.M_PERMISSIONS p       ON p.id_permission = rp.id_permission
    WHERE u.id_user = @id_user
      AND u.is_aktif = 1
      AND p.is_aktif = 1
    ORDER BY p.kode;
END
GO
