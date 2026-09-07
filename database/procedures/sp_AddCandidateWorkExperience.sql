/* =========================================================================
   sp_AddCandidateWorkExperience.sql
   Menyimpan satu baris riwayat pekerjaan kandidat.
   Kompatibel dengan SQL Server 2008 R2.
   ========================================================================= */
IF OBJECT_ID('dbo.sp_AddCandidateWorkExperience') IS NOT NULL
    DROP PROCEDURE dbo.sp_AddCandidateWorkExperience;
GO

CREATE PROCEDURE dbo.sp_AddCandidateWorkExperience
    @id_lamaran         INT,
    @nama_perusahaan    NVARCHAR(150),
    @posisi_jabatan     NVARCHAR(150),
    @periode_kerja      NVARCHAR(100) = NULL,
    @gaji_terakhir      DECIMAL(18,2) = NULL,
    @alasan_keluar      NVARCHAR(500) = NULL,
    @deskripsi_tugas    NVARCHAR(MAX) = NULL,
    @urutan             INT           = 1
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @id_kandidat INT;
    SELECT @id_kandidat = id_kandidat FROM dbo.APPLICATIONS WHERE id_lamaran = @id_lamaran;

    IF @id_kandidat IS NULL
        RAISERROR('Data lamaran tidak ditemukan.', 16, 1);

    INSERT INTO dbo.CANDIDATE_WORK_EXPERIENCES
        (id_lamaran, id_kandidat, nama_perusahaan, posisi_jabatan, periode_kerja, gaji_terakhir, alasan_keluar, deskripsi_tugas, urutan, created_at)
    VALUES
        (@id_lamaran, @id_kandidat, @nama_perusahaan, @posisi_jabatan, @periode_kerja, @gaji_terakhir, @alasan_keluar, @deskripsi_tugas, @urutan, GETDATE());
END
GO
