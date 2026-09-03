/* =========================================================================
   sp_SavePosisi  --  tambah / ubah posisi
   E-Recruitment RPG  --  modul Master Data (Kahfi)

   @id_posisi NULL -> INSERT, selain itu UPDATE. Nama posisi unik.
   default_flow opsional (posisi baru boleh belum punya flow).

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SavePosisi') IS NOT NULL DROP PROCEDURE dbo.sp_SavePosisi;
GO

CREATE PROCEDURE dbo.sp_SavePosisi
    @id_posisi     INT           = NULL,
    @nama_posisi   NVARCHAR(150),
    @id_departemen INT,
    @level_posisi  VARCHAR(20),
    @default_flow  INT           = NULL,
    @id_posisi_out INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SavePos;

        IF @level_posisi NOT IN ('MP','Staff','Staff_Krusial','Spv','Manager','Senior_Manager')
            RAISERROR('level_posisi tidak valid.', 16, 1);
        IF NOT EXISTS (SELECT 1 FROM dbo.M_DEPARTEMEN WHERE id_departemen = @id_departemen)
            RAISERROR('Departemen tidak valid.', 16, 1);
        IF @default_flow IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.M_FLOW WHERE id_flow = @default_flow)
            RAISERROR('Flow tidak valid.', 16, 1);
        IF EXISTS (SELECT 1 FROM dbo.M_POSISI WHERE nama_posisi = @nama_posisi
                   AND (@id_posisi IS NULL OR id_posisi <> @id_posisi))
            RAISERROR('Nama posisi sudah dipakai.', 16, 1);

        IF @id_posisi IS NULL
        BEGIN
            INSERT INTO dbo.M_POSISI (nama_posisi, id_departemen, level_posisi, default_flow, is_aktif)
            VALUES (@nama_posisi, @id_departemen, @level_posisi, @default_flow, 1);
            SET @id_posisi_out = SCOPE_IDENTITY();
        END
        ELSE
        BEGIN
            UPDATE dbo.M_POSISI
            SET nama_posisi = @nama_posisi, id_departemen = @id_departemen,
                level_posisi = @level_posisi, default_flow = @default_flow
            WHERE id_posisi = @id_posisi;
            SET @id_posisi_out = @id_posisi;
        END

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
