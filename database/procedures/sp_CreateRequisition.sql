/* =========================================================================
   sp_CreateRequisition  --  buat MPR (status Draft)
   E-Recruitment RPG  --  modul Master/Requisition (Kahfi)

   Snapshot nama & departemen pemohon dari M_USERS. Flow diambil dari
   parameter, fallback ke M_POSISI.default_flow. Nomor MPR belum diberikan
   di sini -- diberikan sp_SubmitToBOD (Draft tidak menghabiskan nomor).

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

        DECLARE @flow INT = COALESCE(@id_flow, (SELECT default_flow FROM dbo.M_POSISI WHERE id_posisi = @id_posisi));

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
            @butuh_psikotes, @butuh_interview_bod, @pendidikan_minimal, @pengalaman_minimal_tahun,
            @job_desc, @kualifikasi, @range_gaji_min, @range_gaji_max, @preferensi_internal, 'Draft'
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
