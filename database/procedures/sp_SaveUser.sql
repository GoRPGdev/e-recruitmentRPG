/* =========================================================================
   sp_SaveUser  --  Tambah atau update data pengguna (dbo.M_USERS) via NIK
   E-Recruitment RPG -- Arsitektur Database-First

   @id_user NULL -> INSERT, selain itu UPDATE.
   Password di-hash di PHP sebelum dikirim ke SP (@password_hash).
   Jika update dan @password_hash NULL, password lama dipertahankan.

   @id_departemen dan @region: hanya salah satu boleh diisi (lihat
   CK_MUSERS_scope). @id_departemen -> Manager Departemen (satu dept).
   @region -> Regional Manager / Area Leader (lintas outlet satu wilayah).

   Deploy: php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SaveUser') IS NOT NULL DROP PROCEDURE dbo.sp_SaveUser;
GO

CREATE PROCEDURE dbo.sp_SaveUser
    @id_user             INT           = NULL,
    @nik_karyawan        VARCHAR(20),
    @password_hash       VARCHAR(255)  = NULL,
    @nama_snapshot       NVARCHAR(150),
    @id_role             INT,
    @id_departemen       INT           = NULL,
    @region              NVARCHAR(60)  = NULL,
    @is_aktif            BIT           = 1,
    @oleh_user           INT           = NULL,
    @id_user_out         INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SaveUsr;

        -- Normalisasi string kosong -> NULL (form HTML kirim '' bukan NULL)
        IF @region IS NOT NULL AND LTRIM(RTRIM(@region)) = '' SET @region = NULL;

        -- Validasi input dasar
        IF @nik_karyawan IS NULL OR LTRIM(RTRIM(@nik_karyawan)) = ''
            RAISERROR('NIK Karyawan wajib diisi.', 16, 1);
        IF @nama_snapshot IS NULL OR LTRIM(RTRIM(@nama_snapshot)) = ''
            RAISERROR('Nama lengkap wajib diisi.', 16, 1);
        IF NOT EXISTS (SELECT 1 FROM dbo.M_ROLES WHERE id_role = @id_role AND is_aktif = 1)
            RAISERROR('Role tidak valid atau nonaktif.', 16, 1);
        IF @id_departemen IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.M_DEPARTEMEN WHERE id_departemen = @id_departemen)
            RAISERROR('Departemen tidak valid.', 16, 1);

        -- Cek keunikan NIK Karyawan
        IF @id_user IS NULL
        BEGIN
            IF EXISTS (SELECT 1 FROM dbo.M_USERS WHERE nik_karyawan = @nik_karyawan)
                RAISERROR('NIK Karyawan sudah terdaftar.', 16, 1);
        END
        ELSE
        BEGIN
            IF EXISTS (SELECT 1 FROM dbo.M_USERS WHERE nik_karyawan = @nik_karyawan AND id_user <> @id_user)
                RAISERROR('NIK Karyawan sudah digunakan oleh pengguna lain.', 16, 1);
        END

        DECLARE @aksi VARCHAR(10) = CASE WHEN @id_user IS NULL THEN 'INSERT' ELSE 'UPDATE' END;

        IF @id_user IS NULL
        BEGIN
            IF @password_hash IS NULL OR LTRIM(RTRIM(@password_hash)) = ''
                RAISERROR('Password wajib diisi untuk pengguna baru.', 16, 1);

            INSERT INTO dbo.M_USERS (
                nik_karyawan, password_hash, nama_snapshot,
                id_role, id_departemen, region, is_aktif
            ) VALUES (
                @nik_karyawan, @password_hash, @nama_snapshot,
                @id_role, @id_departemen, @region, @is_aktif
            );
            SET @id_user_out = SCOPE_IDENTITY();
        END
        ELSE
        BEGIN
            IF @password_hash IS NOT NULL AND LTRIM(RTRIM(@password_hash)) <> ''
            BEGIN
                UPDATE dbo.M_USERS
                SET nik_karyawan = @nik_karyawan,
                    password_hash = @password_hash,
                    nama_snapshot = @nama_snapshot,
                    id_role = @id_role,
                    id_departemen = @id_departemen,
                    region = @region,
                    is_aktif = @is_aktif
                WHERE id_user = @id_user;
            END
            ELSE
            BEGIN
                UPDATE dbo.M_USERS
                SET nik_karyawan = @nik_karyawan,
                    nama_snapshot = @nama_snapshot,
                    id_role = @id_role,
                    id_departemen = @id_departemen,
                    region = @region,
                    is_aktif = @is_aktif
                WHERE id_user = @id_user;
            END
            SET @id_user_out = @id_user;
        END

        -- Audit Log
        DECLARE @auditMsg VARCHAR(200) = @aksi + ' user: nik=' + @nik_karyawan + ' | nama=' + @nama_snapshot;
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
