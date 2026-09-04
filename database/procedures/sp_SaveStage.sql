/* =========================================================================
   sp_SaveStage  --  tambah / ubah master tahap seleksi
   E-Recruitment RPG  --  modul Master Data / Flow Engine (Kahfi, Fase 3)

   @id_stage NULL -> INSERT, selain itu UPDATE.
   tipe_tahap WAJIB dari 7 nilai tetap:
   SCREENING, KONTAK, FORM, TEST, INTERVIEW, OFFER, ONBOARD (CLAUDE.md aturan 9).
   kode_stage unik.
   Tahap sistem (is_sistem = 1): nama_tahap & is_terminal boleh diedit,
   tetapi kode_stage & tipe_tahap terkunci untuk menjaga integritas flow/report.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SaveStage') IS NOT NULL DROP PROCEDURE dbo.sp_SaveStage;
GO

CREATE PROCEDURE dbo.sp_SaveStage
    @id_stage     INT           = NULL,
    @kode_stage   VARCHAR(30),
    @nama_tahap   NVARCHAR(100),
    @tipe_tahap   VARCHAR(20),
    @is_terminal  BIT           = 0,
    @is_aktif     BIT           = 1,
    @id_stage_out INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SaveStg;

        SET @kode_stage = UPPER(LTRIM(RTRIM(ISNULL(@kode_stage, ''))));
        SET @nama_tahap = LTRIM(RTRIM(ISNULL(@nama_tahap, '')));
        SET @tipe_tahap = UPPER(LTRIM(RTRIM(ISNULL(@tipe_tahap, ''))));

        IF @kode_stage = ''
            RAISERROR('kode_stage wajib diisi.', 16, 1);

        IF @nama_tahap = ''
            RAISERROR('nama_tahap wajib diisi.', 16, 1);

        IF @tipe_tahap NOT IN ('SCREENING','KONTAK','FORM','TEST','INTERVIEW','OFFER','ONBOARD')
            RAISERROR('tipe_tahap tidak valid. Wajib salah satu dari 7 tipe: SCREENING, KONTAK, FORM, TEST, INTERVIEW, OFFER, ONBOARD.', 16, 1);

        IF EXISTS (
            SELECT 1 FROM dbo.M_STAGE
            WHERE kode_stage = @kode_stage
              AND (@id_stage IS NULL OR id_stage <> @id_stage)
        )
            RAISERROR('kode_stage sudah digunakan oleh tahap lain.', 16, 1);

        IF @id_stage IS NULL
        BEGIN
            INSERT INTO dbo.M_STAGE (kode_stage, nama_tahap, tipe_tahap, is_terminal, is_sistem, is_aktif)
            VALUES (@kode_stage, @nama_tahap, @tipe_tahap, ISNULL(@is_terminal, 0), 0, ISNULL(@is_aktif, 1));
            SET @id_stage_out = SCOPE_IDENTITY();
        END
        ELSE
        BEGIN
            DECLARE @sis BIT;
            SELECT @sis = is_sistem FROM dbo.M_STAGE WHERE id_stage = @id_stage;
            IF @sis IS NULL
                RAISERROR('Tahap tidak ditemukan.', 16, 1);

            -- Jika tahap sistem: kode_stage dan tipe_tahap dipertahankan
            IF @sis = 1
            BEGIN
                UPDATE dbo.M_STAGE
                SET nama_tahap  = @nama_tahap,
                    is_terminal = ISNULL(@is_terminal, 0),
                    is_aktif    = ISNULL(@is_aktif, 1)
                WHERE id_stage = @id_stage;
            END
            ELSE
            BEGIN
                UPDATE dbo.M_STAGE
                SET kode_stage  = @kode_stage,
                    nama_tahap  = @nama_tahap,
                    tipe_tahap  = @tipe_tahap,
                    is_terminal = ISNULL(@is_terminal, 0),
                    is_aktif    = ISNULL(@is_aktif, 1)
                WHERE id_stage = @id_stage;
            END
            SET @id_stage_out = @id_stage;
        END

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SaveStg;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
