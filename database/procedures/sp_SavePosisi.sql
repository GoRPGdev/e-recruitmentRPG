/* =========================================================================
   sp_SavePosisi  --  tambah / ubah posisi
   E-Recruitment RPG  --  modul Master Data (Kahfi)

   @id_posisi NULL -> INSERT, selain itu UPDATE. Nama posisi unik.
   default_flow opsional (posisi baru boleh belum punya flow).
   job_desc, kualifikasi, pendidikan_minimal, pengalaman_minimal_tahun (Template Standar Jabatan HR)

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SavePosisi') IS NOT NULL DROP PROCEDURE dbo.sp_SavePosisi;
GO

CREATE PROCEDURE dbo.sp_SavePosisi
    @id_posisi                 INT           = NULL,
    @nama_posisi               NVARCHAR(150),
    @id_departemen             INT,
    @level_posisi              VARCHAR(20),
    @default_flow              INT           = NULL,
    @job_desc                  VARCHAR(MAX)  = NULL,
    @kualifikasi               VARCHAR(MAX)  = NULL,
    @pendidikan_minimal        NVARCHAR(60)  = NULL,
    @pengalaman_minimal_tahun  INT           = NULL,
    @oleh_user                 INT           = NULL,
    @id_posisi_out             INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SavePos;

        IF NOT EXISTS (SELECT 1 FROM dbo.M_LEVEL_ORGANISASI WHERE kode_level = @level_posisi AND is_aktif = 1)
           AND @level_posisi NOT IN ('MP','Staff','Staff_Krusial','Spv','Manager','Senior_Manager')
            RAISERROR('level_posisi tidak valid.', 16, 1);
        IF NOT EXISTS (SELECT 1 FROM dbo.M_DEPARTEMEN WHERE id_departemen = @id_departemen)
            RAISERROR('Departemen tidak valid.', 16, 1);
        IF @default_flow IS NULL
            SELECT TOP 1 @default_flow = id_flow FROM dbo.M_FLOW WHERE is_aktif = 1 ORDER BY id_flow;

        IF @default_flow IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.M_FLOW WHERE id_flow = @default_flow)
            RAISERROR('Flow tidak valid.', 16, 1);
        IF EXISTS (SELECT 1 FROM dbo.M_POSISI WHERE nama_posisi = @nama_posisi
                   AND (@id_posisi IS NULL OR id_posisi <> @id_posisi))
            RAISERROR('Nama posisi sudah dipakai.', 16, 1);

        DECLARE @aksi VARCHAR(10) = CASE WHEN @id_posisi IS NULL THEN 'INSERT' ELSE 'UPDATE' END;

        IF @id_posisi IS NULL
        BEGIN
            INSERT INTO dbo.M_POSISI
                (nama_posisi, id_departemen, level_posisi, default_flow,
                 job_desc, kualifikasi, pendidikan_minimal, pengalaman_minimal_tahun, is_aktif)
            VALUES
                (@nama_posisi, @id_departemen, @level_posisi, @default_flow,
                 @job_desc, @kualifikasi, @pendidikan_minimal, @pengalaman_minimal_tahun, 1);
            SET @id_posisi_out = SCOPE_IDENTITY();
        END
        ELSE
        BEGIN
            UPDATE dbo.M_POSISI
            SET nama_posisi = @nama_posisi,
                id_departemen = @id_departemen,
                level_posisi = @level_posisi,
                default_flow = @default_flow,
                job_desc = @job_desc,
                kualifikasi = @kualifikasi,
                pendidikan_minimal = @pendidikan_minimal,
                pengalaman_minimal_tahun = @pengalaman_minimal_tahun
            WHERE id_posisi = @id_posisi;
            SET @id_posisi_out = @id_posisi;
        END

        DECLARE @av VARCHAR(400) = 'nama=' + @nama_posisi
            + ' | dept=' + CONVERT(VARCHAR(12), @id_departemen)
            + ' | level=' + @level_posisi
            + ' | flow=' + ISNULL(CONVERT(VARCHAR(12), @default_flow), '-');
        EXEC dbo.sp_AuditLog @nama_tabel = 'M_POSISI', @id_baris = @id_posisi_out,
             @aksi = @aksi, @nilai_baru = @av, @oleh_user = @oleh_user;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SavePos;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
