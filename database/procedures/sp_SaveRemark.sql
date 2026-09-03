/* =========================================================================
   sp_SaveRemark  --  tambah / ubah opsi remark (dropdown per tahap)
   E-Recruitment RPG  --  Flow Builder (Kahfi)

   @id_remark NULL -> INSERT, selain itu UPDATE. kode_remark unik.
   efek_status divalidasi CK_M_REMARKS_efek. Nonaktif = soft delete
   (lamaran lama yang memakai remark ini tetap terbaca).

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SaveRemark') IS NOT NULL DROP PROCEDURE dbo.sp_SaveRemark;
GO

CREATE PROCEDURE dbo.sp_SaveRemark
    @id_remark    INT           = NULL,
    @id_stage     INT,
    @kode_remark  VARCHAR(40),
    @label        NVARCHAR(200),
    @efek_status  VARCHAR(20),
    @urutan       INT           = 0,
    @is_aktif     BIT           = 1,
    @id_remark_out INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SaveRmk;

        IF NOT EXISTS (SELECT 1 FROM dbo.M_STAGE WHERE id_stage = @id_stage)
            RAISERROR('Tahap tidak valid.', 16, 1);
        IF EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = @kode_remark
                   AND (@id_remark IS NULL OR id_remark <> @id_remark))
            RAISERROR('kode_remark sudah dipakai.', 16, 1);

        IF @id_remark IS NULL
        BEGIN
            INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
            VALUES (@id_stage, @kode_remark, @label, @efek_status, @urutan, @is_aktif);
            SET @id_remark_out = SCOPE_IDENTITY();
        END
        ELSE
        BEGIN
            UPDATE dbo.M_REMARKS
            SET id_stage = @id_stage, kode_remark = @kode_remark, label = @label,
                efek_status = @efek_status, urutan = @urutan, is_aktif = @is_aktif
            WHERE id_remark = @id_remark;
            SET @id_remark_out = @id_remark;
        END

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SaveRmk;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
