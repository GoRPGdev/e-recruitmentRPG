/* =========================================================================
   sp_GetUserByNik  --  ambil data user untuk SSO dari Payroll (via NIK)
   E-Recruitment RPG

   Beda dari sp_Login: TIDAK mengembalikan password_hash, dan TIDAK ada
   verifikasi password sama sekali -- kepercayaan datang dari tanda
   tangan HMAC token SSO yang sudah divalidasi di controller Sso.php
   SEBELUM SP ini dipanggil. SP ini murni "apakah NIK ini akun aktif,
   dan siapa dia".

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_GetUserByNik') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetUserByNik;
GO

CREATE PROCEDURE dbo.sp_GetUserByNik
    @nik_karyawan VARCHAR(20)
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        u.id_user,
        u.username,
        u.nama_snapshot,
        u.departemen_snapshot,
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
