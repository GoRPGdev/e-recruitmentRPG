/* =========================================================================
   sp_ToggleStage  --  soft delete / aktifkan kembali tahap
   E-Recruitment RPG  --  modul Master Data (Kahfi, Fase 3)

   Tahap sistem (is_sistem = 1) boleh dinonaktifkan, tetapi tidak boleh dihapus.
   Hard delete tidak dipakai (CLAUDE.md aturan 4).

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_ToggleStage') IS NOT NULL DROP PROCEDURE dbo.sp_ToggleStage;
GO

CREATE PROCEDURE dbo.sp_ToggleStage
    @id_stage INT,
    @is_aktif BIT
AS
BEGIN
    SET NOCOUNT ON;

    IF NOT EXISTS (SELECT 1 FROM dbo.M_STAGE WHERE id_stage = @id_stage)
    BEGIN
        RAISERROR('Tahap tidak ditemukan.', 16, 1);
        RETURN;
    END

    -- Peringatkan bila dinonaktifkan tapi masih dipakai di template flow aktif
    IF @is_aktif = 0 AND EXISTS (
        SELECT 1 FROM dbo.M_FLOW_STAGE fs
        JOIN dbo.M_FLOW f ON f.id_flow = fs.id_flow
        WHERE fs.id_stage = @id_stage AND f.is_aktif = 1
    )
    BEGIN
        RAISERROR('Tahap masih dipakai oleh template flow aktif. Nonaktif tetap dilakukan; dropdown baru tidak menampilkannya.', 10, 1);
    END

    UPDATE dbo.M_STAGE SET is_aktif = @is_aktif WHERE id_stage = @id_stage;
END
GO
