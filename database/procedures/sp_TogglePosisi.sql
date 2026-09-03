/* =========================================================================
   sp_TogglePosisi  --  soft delete / aktifkan kembali posisi
   E-Recruitment RPG  --  modul Master Data (Kahfi)

   Hard delete tidak dipakai (CLAUDE.md aturan 4). Menonaktifkan posisi
   membuatnya hilang dari dropdown; requisition/lamaran lama tetap terbaca.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_TogglePosisi') IS NOT NULL DROP PROCEDURE dbo.sp_TogglePosisi;
GO

CREATE PROCEDURE dbo.sp_TogglePosisi
    @id_posisi INT,
    @is_aktif  BIT
AS
BEGIN
    SET NOCOUNT ON;

    IF NOT EXISTS (SELECT 1 FROM dbo.M_POSISI WHERE id_posisi = @id_posisi)
    BEGIN
        RAISERROR('Posisi tidak ditemukan.', 16, 1);
        RETURN;
    END

    -- kalau menonaktifkan: peringatkan bila masih dipakai requisition aktif
    IF @is_aktif = 0 AND EXISTS (
        SELECT 1 FROM dbo.REQUISITIONS
        WHERE id_posisi = @id_posisi
          AND status_req IN ('Draft','Diajukan','Menunggu_BOD','Approved','Sourcing','Sourcing_Ulang','Terpenuhi_Sebagian'))
        RAISERROR('Posisi masih dipakai requisition yang berjalan. Nonaktif tetap dilakukan; dropdown baru tidak menampilkannya.', 10, 1);

    UPDATE dbo.M_POSISI SET is_aktif = @is_aktif WHERE id_posisi = @id_posisi;
END
GO
