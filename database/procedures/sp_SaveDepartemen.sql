/* =========================================================================
   sp_SaveDepartemen  --  tambah / ubah departemen
   E-Recruitment RPG  --  modul Master Data (Kahfi)

   @id_departemen NULL -> INSERT, selain itu UPDATE.
   Kode departemen unik, nama wajib diisi.
   Soft delete via is_aktif. Mengikuti batasan T-SQL SQL Server 2008 R2.

   Deploy: php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SaveDepartemen') IS NOT NULL DROP PROCEDURE dbo.sp_SaveDepartemen;
GO

CREATE PROCEDURE dbo.sp_SaveDepartemen
    @id_departemen     INT           = NULL,
    @kode              VARCHAR(20),
    @nama              NVARCHAR(100),
    @is_aktif          BIT           = 1,
    @oleh_user         INT           = NULL,
    @id_departemen_out INT           OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SaveDept;

        -- Validasi input
        SET @kode = LTRIM(RTRIM(ISNULL(@kode, '')));
        SET @nama = LTRIM(RTRIM(ISNULL(@nama, '')));

        IF @kode = '' RAISERROR('Kode departemen tidak boleh kosong.', 16, 1);
        IF @nama = '' RAISERROR('Nama departemen tidak boleh kosong.', 16, 1);

        -- Cek duplikasi kode
        IF EXISTS (SELECT 1 FROM dbo.M_DEPARTEMEN
                   WHERE kode = @kode
                     AND (@id_departemen IS NULL OR id_departemen <> @id_departemen))
            RAISERROR('Kode departemen sudah dipakai.', 16, 1);

        DECLARE @aksi VARCHAR(10) = CASE WHEN @id_departemen IS NULL THEN 'INSERT' ELSE 'UPDATE' END;
        DECLARE @nilai_lama VARCHAR(400) = NULL;

        IF @id_departemen IS NULL
        BEGIN
            INSERT INTO dbo.M_DEPARTEMEN (kode, nama, is_aktif)
            VALUES (@kode, @nama, ISNULL(@is_aktif, 1));
            SET @id_departemen_out = SCOPE_IDENTITY();
        END
        ELSE
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM dbo.M_DEPARTEMEN WHERE id_departemen = @id_departemen)
                RAISERROR('Departemen tidak ditemukan.', 16, 1);

            SELECT @nilai_lama = 'kode=' + kode + ' | nama=' + nama + ' | is_aktif=' + CONVERT(VARCHAR(1), is_aktif)
            FROM dbo.M_DEPARTEMEN WHERE id_departemen = @id_departemen;

            UPDATE dbo.M_DEPARTEMEN
            SET kode = @kode,
                nama = @nama,
                is_aktif = ISNULL(@is_aktif, is_aktif)
            WHERE id_departemen = @id_departemen;

            SET @id_departemen_out = @id_departemen;
        END

        DECLARE @nilai_baru VARCHAR(400) = 'kode=' + @kode + ' | nama=' + @nama + ' | is_aktif=' + CONVERT(VARCHAR(1), ISNULL(@is_aktif, 1));
        EXEC dbo.sp_AuditLog @nama_tabel = 'M_DEPARTEMEN', @id_baris = @id_departemen_out,
             @aksi = @aksi, @nilai_lama = @nilai_lama, @nilai_baru = @nilai_baru, @oleh_user = @oleh_user;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @err VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SaveDept;
        RAISERROR(@err, 16, 1);
    END CATCH
END
GO
