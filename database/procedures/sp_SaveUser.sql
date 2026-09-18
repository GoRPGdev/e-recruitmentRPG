/* =========================================================================
   sp_SaveUser  --  Tambah atau update data pengguna (dbo.M_USERS)
   E-Recruitment RPG -- Arsitektur Database-First

   @id_user NULL -> INSERT, selain itu UPDATE.
   Password di-hash di PHP sebelum dikirim ke SP (@password_hash).
   Jika update dan @password_hash NULL, password lama dipertahankan.

   Deploy: php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SaveUser') IS NOT NULL DROP PROCEDURE dbo.sp_SaveUser;
GO

CREATE PROCEDURE dbo.sp_SaveUser
    @id_user             INT           = NULL,
    @username            VARCHAR(50),
    @password_hash       VARCHAR(255)  = NULL,
    @nama_snapshot       NVARCHAR(150),
    @nik_karyawan        VARCHAR(20)   = NULL,
    @departemen_snapshot NVARCHAR(100) = NULL,
    @id_role             INT,
    @id_departemen       INT           = NULL,
    @is_aktif            BIT           = 1,
    @oleh_user           INT           = NULL,
    @id_user_out         INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SaveUsr;

        -- Validasi input dasar
        IF @username IS NULL OR LTRIM(RTRIM(@username)) = ''
            RAISERROR('Username wajib diisi.', 16, 1);
        IF @nama_snapshot IS NULL OR LTRIM(RTRIM(@nama_snapshot)) = ''
            RAISERROR('Nama lengkap wajib diisi.', 16, 1);
        IF NOT EXISTS (SELECT 1 FROM dbo.M_ROLES WHERE id_role = @id_role AND is_aktif = 1)
            RAISERROR('Role tidak valid atau nonaktif.', 16, 1);
        IF @id_departemen IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.M_DEPARTEMEN WHERE id_departemen = @id_departemen)
            RAISERROR('Departemen tidak valid.', 16, 1);

        -- Validasi keunikan username
        IF EXISTS (SELECT 1 FROM dbo.M_USERS WHERE username = @username AND (@id_user IS NULL OR id_user <> @id_user))
            RAISERROR('Username sudah digunakan.', 16, 1);

        -- Validasi keunikan NIK karyawan (kalau diisi) -- kunci pencocokan SSO dari Payroll
        IF @nik_karyawan IS NOT NULL AND LTRIM(RTRIM(@nik_karyawan)) <> ''
           AND EXISTS (SELECT 1 FROM dbo.M_USERS WHERE nik_karyawan = @nik_karyawan AND (@id_user IS NULL OR id_user <> @id_user))
            RAISERROR('NIK karyawan ini sudah terhubung ke akun lain.', 16, 1);

        DECLARE @aksi VARCHAR(10) = CASE WHEN @id_user IS NULL THEN 'INSERT' ELSE 'UPDATE' END;

        IF @id_user IS NULL
        BEGIN
            IF @password_hash IS NULL OR LTRIM(RTRIM(@password_hash)) = ''
                RAISERROR('Password wajib diisi untuk pengguna baru.', 16, 1);

            INSERT INTO dbo.M_USERS (
                username, password_hash, nama_snapshot, nik_karyawan,
                departemen_snapshot, id_role, id_departemen, is_aktif
            ) VALUES (
                @username, @password_hash, @nama_snapshot, @nik_karyawan,
                @departemen_snapshot, @id_role, @id_departemen, @is_aktif
            );
            SET @id_user_out = SCOPE_IDENTITY();
        END
        ELSE
        BEGIN
            IF @password_hash IS NOT NULL AND LTRIM(RTRIM(@password_hash)) <> ''
            BEGIN
                UPDATE dbo.M_USERS
                SET username = @username,
                    password_hash = @password_hash,
                    nama_snapshot = @nama_snapshot,
                    nik_karyawan = @nik_karyawan,
                    departemen_snapshot = @departemen_snapshot,
                    id_role = @id_role,
                    id_departemen = @id_departemen,
                    is_aktif = @is_aktif
                WHERE id_user = @id_user;
            END
            ELSE
            BEGIN
                UPDATE dbo.M_USERS
                SET username = @username,
                    nama_snapshot = @nama_snapshot,
                    nik_karyawan = @nik_karyawan,
                    departemen_snapshot = @departemen_snapshot,
                    id_role = @id_role,
                    id_departemen = @id_departemen,
                    is_aktif = @is_aktif
                WHERE id_user = @id_user;
            END
            SET @id_user_out = @id_user;
        END

        DECLARE @auditMsg VARCHAR(400) = 'username=' + @username
            + ' | nama=' + @nama_snapshot
            + ' | role=' + CONVERT(VARCHAR(12), @id_role)
            + ' | dept=' + ISNULL(CONVERT(VARCHAR(12), @id_departemen), '-')
            + ' | aktif=' + CONVERT(VARCHAR(2), @is_aktif);

        EXEC dbo.sp_AuditLog @nama_tabel = 'M_USERS', @id_baris = @id_user_out,
             @aksi = @aksi, @nilai_baru = @auditMsg, @oleh_user = @oleh_user;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @err VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SaveUsr;
        RAISERROR(@err, 16, 1);
    END CATCH
END
GO
