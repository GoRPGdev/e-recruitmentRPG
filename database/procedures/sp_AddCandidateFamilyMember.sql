/* =========================================================================
   sp_AddCandidateFamilyMember.sql
   Menyimpan satu baris data susunan anggota keluarga kandidat.
   Kompatibel dengan SQL Server 2008 R2.
   ========================================================================= */
IF OBJECT_ID('dbo.sp_AddCandidateFamilyMember') IS NOT NULL
    DROP PROCEDURE dbo.sp_AddCandidateFamilyMember;
GO

CREATE PROCEDURE dbo.sp_AddCandidateFamilyMember
    @id_kandidat        INT,
    @hubungan           NVARCHAR(50),
    @nama_lengkap       NVARCHAR(150),
    @jenis_kelamin      CHAR(1)       = NULL,
    @usia               INT           = NULL,
    @pendidikan         NVARCHAR(50)  = NULL,
    @pekerjaan          NVARCHAR(100) = NULL,
    @no_telp            VARCHAR(30)   = NULL,
    @urutan             INT           = 1
AS
BEGIN
    SET NOCOUNT ON;

    IF NOT EXISTS (SELECT 1 FROM dbo.CANDIDATES WHERE id_kandidat = @id_kandidat)
        RAISERROR('Data kandidat tidak ditemukan.', 16, 1);

    INSERT INTO dbo.CANDIDATE_FAMILY
        (id_kandidat, hubungan, nama_lengkap, jenis_kelamin, usia, pendidikan, pekerjaan, no_telp, urutan, created_at)
    VALUES
        (@id_kandidat, @hubungan, @nama_lengkap, @jenis_kelamin, @usia, @pendidikan, @pekerjaan, @no_telp, @urutan, GETDATE());
END
GO
