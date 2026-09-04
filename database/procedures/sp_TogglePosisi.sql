/* =========================================================================
   sp_TogglePosisi  --  soft delete / aktifkan kembali posisi
   E-Recruitment RPG  --  modul Master Data (Kahfi)

   Hard delete tidak dipakai (CLAUDE.md aturan 4). Menonaktifkan posisi
   membuatnya hilang dari dropdown; requisition/lamaran lama tetap terbaca.
   Perubahan dicatat ke AUDIT_LOG.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_TogglePosisi') IS NOT NULL DROP PROCEDURE dbo.sp_TogglePosisi;
GO

CREATE PROCEDURE dbo.sp_TogglePosisi
    @id_posisi INT,
    @is_aktif  BIT,
    @oleh_user INT = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF NOT EXISTS (SELECT 1 FROM dbo.M_POSISI WHERE id_posisi = @id_posisi)
        RAISERROR('Posisi tidak ditemukan.', 16, 1);
    ELSE
    BEGIN
        UPDATE dbo.M_POSISI SET is_aktif = @is_aktif WHERE id_posisi = @id_posisi;

        DECLARE @av VARCHAR(40) = 'is_aktif=' + CONVERT(VARCHAR(1), @is_aktif);
        EXEC dbo.sp_AuditLog @nama_tabel = 'M_POSISI', @id_baris = @id_posisi,
             @aksi = 'UPDATE', @nilai_baru = @av, @oleh_user = @oleh_user;
    END
END
GO
