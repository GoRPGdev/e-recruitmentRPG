/* =========================================================================
   sp_CreateRequisition  --  buat MPR (status Draft)
   E-Recruitment RPG  --  modul Master/Requisition (Kahfi)

   Snapshot nama & departemen pemohon dari M_USERS.
   Flow, job_desc, kualifikasi, pendidikan_minimal, dan pengalaman_minimal
   diambil dari parameter input, dan otomatis fallback ke default template M_POSISI
   jika tidak diisi secara spesifik oleh pemohon.
   Nomor MPR belum diberikan di sini -- diberikan sp_SubmitToBOD.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_CreateRequisition') IS NOT NULL DROP PROCEDURE dbo.sp_CreateRequisition;
GO

CREATE PROCEDURE dbo.sp_CreateRequisition
    @id_user_pemohon         INT,
    @id_posisi               INT,
    @tipe_penempatan         VARCHAR(10),
    @id_outlet               INT           = NULL,
    @jumlah_dibutuhkan       INT,
    @status_karyawan         VARCHAR(20)   = NULL,
    @alasan_permintaan       VARCHAR(50)   = NULL,
    @nik_digantikan          VARCHAR(20)   = NULL,
    @target_tanggal_join     DATE          = NULL,
    @urgensi                 VARCHAR(20)   = NULL,
    @id_flow                 INT           = NULL,
    @butuh_psikotes          BIT           = 0,
    @butuh_interview_bod     BIT           = 0,
    @pendidikan_minimal      NVARCHAR(60)  = NULL,
    @pengalaman_minimal_tahun INT          = NULL,
    @job_desc                VARCHAR(MAX)  = NULL,
    @kualifikasi             VARCHAR(MAX)  = NULL,
    @range_gaji_min          DECIMAL(18,2) = NULL,
    @range_gaji_max          DECIMAL(18,2) = NULL,
    @preferensi_internal     VARCHAR(MAX)  = NULL,
    @id_req                  INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION CreateReq;

        IF NOT EXISTS (SELECT 1 FROM dbo.M_USERS WHERE id_user = @id_user_pemohon AND is_aktif = 1)
            RAISERROR('Pemohon tidak valid.', 16, 1);
        IF NOT EXISTS (SELECT 1 FROM dbo.M_POSISI WHERE id_posisi = @id_posisi AND is_aktif = 1)
            RAISERROR('Posisi tidak valid.', 16, 1);
        IF @tipe_penempatan = 'OUTLET' AND @id_outlet IS NULL
            RAISERROR('Penempatan OUTLET wajib mengisi outlet.', 16, 1);

        -- Validasi departemen pemohon jika user terafiliasi dengan departemen spesifik (USER_DEPT)
        DECLARE @user_dept INT;
        SELECT @user_dept = id_departemen FROM dbo.M_USERS WHERE id_user = @id_user_pemohon;

        IF @user_dept IS NOT NULL
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM dbo.M_POSISI
                WHERE id_posisi = @id_posisi AND id_departemen = @user_dept
            )
                RAISERROR('Posisi yang dipilih tidak sesuai dengan departemen pemohon.', 16, 1);
        END

        -- Ambil default template posisi dari M_POSISI jika parameter kosong
        DECLARE @pos_flow INT, @pos_jd VARCHAR(MAX), @pos_kual VARCHAR(MAX),
                @pos_pend NVARCHAR(60), @pos_exp INT;
        SELECT @pos_flow = default_flow,
               @pos_jd   = job_desc,
               @pos_kual = kualifikasi,
               @pos_pend = pendidikan_minimal,
               @pos_exp  = pengalaman_minimal_tahun
        FROM dbo.M_POSISI WHERE id_posisi = @id_posisi;

        -- Flow otomatis ke alur standar tunggal RPG
        DECLARE @default_std_flow INT = (SELECT TOP 1 id_flow FROM dbo.M_FLOW WHERE is_aktif = 1 ORDER BY id_flow);
        DECLARE @flow INT = COALESCE(@id_flow, @pos_flow, @default_std_flow);
        DECLARE @final_jd VARCHAR(MAX) = COALESCE(NULLIF(@job_desc, ''), @pos_jd);
        DECLARE @final_kual VARCHAR(MAX) = COALESCE(NULLIF(@kualifikasi, ''), @pos_kual);
        DECLARE @final_pend NVARCHAR(60) = COALESCE(NULLIF(@pendidikan_minimal, ''), @pos_pend);
        DECLARE @final_exp INT = COALESCE(@pengalaman_minimal_tahun, @pos_exp);

        INSERT INTO dbo.REQUISITIONS
            (tanggal_pengajuan, id_user_pemohon, nama_pemohon_snapshot, departemen_pemohon_snapshot,
             id_posisi, tipe_penempatan, id_outlet, jumlah_dibutuhkan, status_karyawan,
             alasan_permintaan, nik_digantikan, target_tanggal_join, urgensi, id_flow,
             butuh_psikotes, butuh_interview_bod, pendidikan_minimal, pengalaman_minimal_tahun,
             job_desc, kualifikasi, range_gaji_min, range_gaji_max, preferensi_internal, status_req)
        SELECT
            NULL, @id_user_pemohon, u.nama_snapshot, u.departemen_snapshot,
            @id_posisi, @tipe_penempatan, @id_outlet, @jumlah_dibutuhkan, @status_karyawan,
            @alasan_permintaan, @nik_digantikan, @target_tanggal_join, @urgensi, @flow,
            @butuh_psikotes, @butuh_interview_bod, @final_pend, @final_exp,
            @final_jd, @final_kual, @range_gaji_min, @range_gaji_max, @preferensi_internal, 'Draft'
        FROM dbo.M_USERS u WHERE u.id_user = @id_user_pemohon;

        SET @id_req = SCOPE_IDENTITY();

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @msg VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION CreateReq;
        RAISERROR(@msg, 16, 1);
    END CATCH
END
GO
