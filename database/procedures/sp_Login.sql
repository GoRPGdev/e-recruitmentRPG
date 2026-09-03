/* =========================================================================
   sp_Login  --  ambil data user untuk verifikasi login
   E-Recruitment RPG

   PHP yang memverifikasi password (password_verify terhadap password_hash) --
   bcrypt/argon2 tidak ada di T-SQL. SP ini hanya mengembalikan baris user
   yang aktif; kalau kosong -> username tidak ada / nonaktif.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_Login') IS NOT NULL
    DROP PROCEDURE dbo.sp_Login;
GO

CREATE PROCEDURE dbo.sp_Login
    @username VARCHAR(50)
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        u.id_user,
        u.username,
        u.password_hash,
        u.nama_snapshot,
        u.departemen_snapshot,
        u.id_role,
        r.kode_role,
        r.nama_role
    FROM dbo.M_USERS u
    INNER JOIN dbo.M_ROLES r ON r.id_role = u.id_role
    WHERE u.username = @username
      AND u.is_aktif = 1
      AND r.is_aktif = 1;
END
GO
