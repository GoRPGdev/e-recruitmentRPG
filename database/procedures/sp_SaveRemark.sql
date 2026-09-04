/* =========================================================================
   sp_SaveRemark  --  tambah / ubah opsi remark (dropdown per tahap)
   E-Recruitment RPG  --  Flow Builder (Kahfi)

   @id_remark NULL -> INSERT, selain itu UPDATE. kode_remark unik.
   efek_status divalidasi CK_M_REMARKS_efek. Nonaktif = soft delete
   (lamaran lama yang memakai remark ini tetap terbaca).
   Audit trail dicatat via sp_AuditLog.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SaveRemark') IS NOT NULL DROP PROCEDURE dbo.sp_SaveRemark;
GO

CREATE PROCEDURE dbo.sp_SaveRemark
    @id_remark     INT           = NULL,
    @id_stage      INT,
    @kode_remark   VARCHAR(40),
    @label         NVARCHAR(200),
    @efek_status   VARCHAR(20),
    @urutan        INT           = 0,
    @is_aktif      BIT           = 1,
    @id_remark_out INT OUTPUT,
    @oleh_user     INT           = NULL
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

            DECLARE @nb VARCHAR(400) = 'stage=' + CONVERT(VARCHAR(10), @id_stage) + ', kode=' + @kode_remark + ', label=' + @label + ', efek=' + @efek_status;
            EXEC dbo.sp_AuditLog 'M_REMARKS', @id_remark_out, 'INSERT', NULL, @nb, @oleh_user;
        END
        ELSE
        BEGIN
            DECLARE @old_kode VARCHAR(40), @old_label NVARCHAR(200), @old_efek VARCHAR(20), @old_akt BIT;
            SELECT @old_kode = kode_remark, @old_label = label, @old_efek = efek_status, @old_akt = is_aktif
            FROM dbo.M_REMARKS WHERE id_remark = @id_remark;

            UPDATE dbo.M_REMARKS
            SET id_stage = @id_stage, kode_remark = @kode_remark, label = @label,
                efek_status = @efek_status, urutan = @urutan, is_aktif = @is_aktif
            WHERE id_remark = @id_remark;
            SET @id_remark_out = @id_remark;

            DECLARE @nl VARCHAR(400) = 'kode=' + ISNULL(@old_kode,'') + ', label=' + ISNULL(@old_label,'') + ', efek=' + ISNULL(@old_efek,'') + ', aktif=' + CONVERT(VARCHAR(1), @old_akt);
            DECLARE @nb2 VARCHAR(400) = 'kode=' + @kode_remark + ', label=' + @label + ', efek=' + @efek_status + ', aktif=' + CONVERT(VARCHAR(1), @is_aktif);
            EXEC dbo.sp_AuditLog 'M_REMARKS', @id_remark, 'UPDATE', @nl, @nb2, @oleh_user;
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
