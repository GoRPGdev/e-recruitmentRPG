/* =========================================================================
   sp_UpdateRequisition  --  edit data MPR selama masih Draft / Revisi_HR
   E-Recruitment RPG  --  Requisition Engine (Kahfi)

   Pemohon hanya diizinkan mengedit MPR jika:
   1. Status masih Draft atau Revisi_HR
   2. Pemohon adalah pemilik MPR (id_user_pemohon = @oleh_user)
      ATAU user punya departemen yang sama (validasi di controller)

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_UpdateRequisition') IS NOT NULL DROP PROCEDURE dbo.sp_UpdateRequisition;
GO

CREATE PROCEDURE dbo.sp_UpdateRequisition
    @id_req                  INT,
    @id_posisi               INT,
    @tipe_penempatan         VARCHAR(10),
    @id_outlet               INT           = NULL,
    @jumlah_dibutuhkan       INT,
    @status_karyawan         VARCHAR(20)   = NULL,
    @alasan_permintaan       VARCHAR(50)   = NULL,
    @nik_digantikan          VARCHAR(20)   = NULL,
    @target_tanggal_join     DATE          = NULL,
    @urgensi                 VARCHAR(20)   = NULL,
    @pendidikan_minimal      NVARCHAR(60)  = NULL,
    @pengalaman_minimal_tahun INT          = NULL,
    @job_desc                VARCHAR(MAX)  = NULL,
    @kualifikasi             VARCHAR(MAX)  = NULL,
    @oleh_user               INT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION UpdReq;

        DECLARE @status VARCHAR(25), @pemilik INT;
        SELECT @status = status_req, @pemilik = id_user_pemohon
        FROM dbo.REQUISITIONS WHERE id_req = @id_req;

        IF @status IS NULL
            RAISERROR('Requisition tidak ditemukan.', 16, 1);

        IF @status NOT IN ('Draft', 'Revisi_HR', 'Revisi_BOD')
            RAISERROR('MPR hanya dapat diedit saat berstatus Draft, Revisi HR, atau Revisi BOD.', 16, 1);

        IF NOT EXISTS (SELECT 1 FROM dbo.M_POSISI WHERE id_posisi = @id_posisi AND is_aktif = 1)
            RAISERROR('Posisi tidak valid.', 16, 1);

        IF @tipe_penempatan = 'OUTLET' AND @id_outlet IS NULL
            RAISERROR('Penempatan OUTLET wajib mengisi outlet.', 16, 1);

        /* Validasi departemen pemohon vs posisi */
        DECLARE @user_dept INT;
        SELECT @user_dept = id_departemen FROM dbo.M_USERS WHERE id_user = @oleh_user;

        IF @user_dept IS NOT NULL
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM dbo.M_POSISI
                WHERE id_posisi = @id_posisi AND id_departemen = @user_dept
            )
                RAISERROR('Posisi yang dipilih tidak sesuai dengan departemen Anda.', 16, 1);
        END

        /* Fallback template posisi jika field kosong */
        DECLARE @pos_jd VARCHAR(MAX), @pos_kual VARCHAR(MAX),
                @pos_pend NVARCHAR(60), @pos_exp INT;
        SELECT @pos_jd   = job_desc,
               @pos_kual = kualifikasi,
               @pos_pend = pendidikan_minimal,
               @pos_exp  = pengalaman_minimal_tahun
        FROM dbo.M_POSISI WHERE id_posisi = @id_posisi;

        DECLARE @final_jd VARCHAR(MAX)  = COALESCE(NULLIF(@job_desc, ''), @pos_jd);
        DECLARE @final_kual VARCHAR(MAX) = COALESCE(NULLIF(@kualifikasi, ''), @pos_kual);
        DECLARE @final_pend NVARCHAR(60) = COALESCE(NULLIF(@pendidikan_minimal, ''), @pos_pend);
        DECLARE @final_exp INT           = COALESCE(@pengalaman_minimal_tahun, @pos_exp);

        UPDATE dbo.REQUISITIONS
        SET id_posisi               = @id_posisi,
            tipe_penempatan         = @tipe_penempatan,
            id_outlet               = @id_outlet,
            jumlah_dibutuhkan       = @jumlah_dibutuhkan,
            status_karyawan         = @status_karyawan,
            alasan_permintaan       = @alasan_permintaan,
            nik_digantikan          = @nik_digantikan,
            target_tanggal_join     = @target_tanggal_join,
            urgensi                 = @urgensi,
            pendidikan_minimal      = @final_pend,
            pengalaman_minimal_tahun= @final_exp,
            job_desc                = @final_jd,
            kualifikasi             = @final_kual
        WHERE id_req = @id_req;

        /* Audit */
        EXEC dbo.sp_AuditLog 'REQUISITIONS', @id_req, 'UPDATE', 'edit_draft', 'edit_draft', @oleh_user;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @msg VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION UpdReq;
        RAISERROR(@msg, 16, 1);
    END CATCH
END
GO
