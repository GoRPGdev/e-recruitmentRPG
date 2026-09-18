/* =========================================================================
   sp_Login  --  ambil data user untuk verifikasi login via NIK Karyawan
   E-Recruitment RPG

   PHP memverifikasi password (password_verify terhadap password_hash).
   SP ini hanya mengembalikan baris user yang aktif; kalau kosong -> NIK tidak ada / nonaktif.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_Login') IS NOT NULL
    DROP PROCEDURE dbo.sp_Login;
GO

CREATE PROCEDURE dbo.sp_Login
    @nik_karyawan VARCHAR(20)
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        u.id_user,
        u.nik_karyawan,
        u.password_hash,
        u.nama_snapshot,
        u.id_departemen,
        u.region,
        u.id_role,
        r.kode_role,
        r.nama_role
    FROM dbo.M_USERS u
    INNER JOIN dbo.M_ROLES r ON r.id_role = u.id_role
    WHERE u.nik_karyawan = @nik_karyawan
      AND u.is_aktif = 1
      AND r.is_aktif = 1;
END
GO
