/* =========================================================================
   sp_DeleteUser  --  Soft delete akun pengguna (dbo.M_USERS)
   E-Recruitment RPG -- Arsitektur Database-First

   Mengubah status is_aktif = 0 (CLAUDE.md aturan 4: soft delete).
   Validasi:
   - User harus ada di database
   - Mencegah penghapusan Super Admin terakhir yang aktif
   - Mencatat ke sp_AuditLog

   Deploy: php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_DeleteUser') IS NOT NULL DROP PROCEDURE dbo.sp_DeleteUser;
GO

CREATE PROCEDURE dbo.sp_DeleteUser
    @id_user   INT,
    @oleh_user INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION DelUsr;

        IF NOT EXISTS (SELECT 1 FROM dbo.M_USERS WHERE id_user = @id_user)
            RAISERROR('Pengguna tidak ditemukan.', 16, 1);

        -- Cek apakah user yang akan dihapus adalah SUPER_ADMIN
        DECLARE @kode_role VARCHAR(50);
        SELECT @kode_role = r.kode_role
        FROM dbo.M_USERS u
        JOIN dbo.M_ROLES r ON r.id_role = u.id_role
        WHERE u.id_user = @id_user;

        IF @kode_role = 'SUPER_ADMIN'
        BEGIN
            DECLARE @activeSuperCount INT;
            SELECT @activeSuperCount = COUNT(*)
            FROM dbo.M_USERS u
            JOIN dbo.M_ROLES r ON r.id_role = u.id_role
            WHERE r.kode_role = 'SUPER_ADMIN' AND u.is_aktif = 1;

            IF @activeSuperCount <= 1
                RAISERROR('Tidak dapat menghapus Super Admin terakhir yang aktif.', 16, 1);
        END

        -- Soft delete
        UPDATE dbo.M_USERS
        SET is_aktif = 0
        WHERE id_user = @id_user;

        -- Audit Log
        DECLARE @username VARCHAR(50);
        SELECT @username = username FROM dbo.M_USERS WHERE id_user = @id_user;

        DECLARE @auditMsg VARCHAR(200) = 'Soft delete user: username=' + ISNULL(@username, '') + ' | is_aktif=0';
        EXEC dbo.sp_AuditLog @nama_tabel = 'M_USERS', @id_baris = @id_user,
             @aksi = 'DELETE', @nilai_baru = @auditMsg, @oleh_user = @oleh_user;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @err VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION DelUsr;
        RAISERROR(@err, 16, 1);
    END CATCH
END
GO
