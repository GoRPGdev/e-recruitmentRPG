/* =========================================================================
   sp_ToggleStage  --  soft delete / aktifkan kembali tahap
   E-Recruitment RPG  --  modul Flow Builder (Kahfi, Fase 3)

   Tahap sistem (is_sistem = 1) boleh dinonaktifkan, tidak boleh dihapus.
   Hard delete tidak dipakai (CLAUDE.md aturan 4). Tahap yang dinonaktifkan
   tetap terbaca di lamaran/flow lama; hanya hilang dari dropdown baru.
   Audit trail dicatat via sp_AuditLog.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_ToggleStage') IS NOT NULL DROP PROCEDURE dbo.sp_ToggleStage;
GO

CREATE PROCEDURE dbo.sp_ToggleStage
    @id_stage  INT,
    @is_aktif  BIT,
    @oleh_user INT = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF NOT EXISTS (SELECT 1 FROM dbo.M_STAGE WHERE id_stage = @id_stage)
        RAISERROR('Tahap tidak ditemukan.', 16, 1);
    ELSE
    BEGIN
        DECLARE @old_akt BIT;
        SELECT @old_akt = is_aktif FROM dbo.M_STAGE WHERE id_stage = @id_stage;

        UPDATE dbo.M_STAGE SET is_aktif = @is_aktif WHERE id_stage = @id_stage;

        DECLARE @nl VARCHAR(50) = 'is_aktif=' + CONVERT(VARCHAR(1), @old_akt);
        DECLARE @nb VARCHAR(50) = 'is_aktif=' + CONVERT(VARCHAR(1), @is_aktif);
        EXEC dbo.sp_AuditLog 'M_STAGE', @id_stage, 'UPDATE', @nl, @nb, @oleh_user;
    END
END
GO
