/* =========================================================================
   sp_SaveInterview  --  jadwalkan atau catat hasil interview
   E-Recruitment RPG  --  Flow Engine / Seleksi (Kahfi)

   Menangani tabel INTERVIEWS dan INTERVIEW_PARTICIPANTS.
   @id_interview NULL -> INSERT (Jadwal baru), selain itu UPDATE (Ubah jadwal / Hasil).
   Audit trail dicatat via sp_AuditLog.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SaveInterview') IS NOT NULL DROP PROCEDURE dbo.sp_SaveInterview;
GO

CREATE PROCEDURE dbo.sp_SaveInterview
    @id_interview      INT           = NULL OUTPUT,
    @id_app_stage      INT,
    @tipe              VARCHAR(10)   = NULL,       -- Online / Offline
    @jadwal            DATETIME      = NULL,
    @lokasi_atau_link  NVARCHAR(300) = NULL,
    @hasil             VARCHAR(15)   = NULL,       -- Lulus, Tidak_Lulus, Dipertimbangkan, Reschedule, No_Show
    @skor              INT           = NULL,
    @catatan           VARCHAR(MAX)  = NULL,
    @id_interviewer    INT           = NULL,       -- M_USERS.id_user
    @peran_interviewer VARCHAR(10)   = 'HR',       -- HR / User / BOD
    @oleh_user         INT           = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SaveIvw;

        IF NOT EXISTS (SELECT 1 FROM dbo.APPLICATION_STAGES WHERE id_app_stage = @id_app_stage)
            RAISERROR('Tahap lamaran (APPLICATION_STAGES) tidak ditemukan.', 16, 1);

        IF @tipe IS NOT NULL AND @tipe NOT IN ('Online','Offline')
            RAISERROR('Tipe interview harus Online atau Offline.', 16, 1);

        IF @hasil IS NOT NULL AND @hasil NOT IN ('Lulus','Tidak_Lulus','Dipertimbangkan','Reschedule','No_Show')
            RAISERROR('Hasil interview tidak valid.', 16, 1);

        IF @id_interview IS NULL OR @id_interview <= 0
        BEGIN
            INSERT INTO dbo.INTERVIEWS (id_app_stage, tipe, jadwal, lokasi_atau_link, hasil, skor, catatan, selesai_pada)
            VALUES (@id_app_stage, @tipe, @jadwal, @lokasi_atau_link, @hasil, @skor, @catatan,
                    CASE WHEN @hasil IS NOT NULL AND @hasil IN ('Lulus','Tidak_Lulus','Dipertimbangkan','No_Show') THEN GETDATE() ELSE NULL END);
            SET @id_interview = SCOPE_IDENTITY();

            IF @id_interviewer IS NOT NULL AND EXISTS (SELECT 1 FROM dbo.M_USERS WHERE id_user = @id_interviewer)
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM dbo.INTERVIEW_PARTICIPANTS WHERE id_interview = @id_interview AND id_user = @id_interviewer)
                BEGIN
                    INSERT INTO dbo.INTERVIEW_PARTICIPANTS (id_interview, id_user, peran)
                    VALUES (@id_interview, @id_interviewer, ISNULL(@peran_interviewer, 'HR'));
                END
            END

            DECLARE @nb VARCHAR(400) = 'stage=' + CONVERT(VARCHAR(10), @id_app_stage) + ', tipe=' + ISNULL(@tipe,'-') + ', hasil=' + ISNULL(@hasil,'Scheduled');
            EXEC dbo.sp_AuditLog 'INTERVIEWS', @id_interview, 'INSERT', NULL, @nb, @oleh_user;
        END
        ELSE
        BEGIN
            DECLARE @old_hasil VARCHAR(15);
            SELECT @old_hasil = hasil FROM dbo.INTERVIEWS WHERE id_interview = @id_interview;

            UPDATE dbo.INTERVIEWS
            SET tipe = ISNULL(@tipe, tipe),
                jadwal = ISNULL(@jadwal, jadwal),
                lokasi_atau_link = ISNULL(@lokasi_atau_link, lokasi_atau_link),
                hasil = @hasil,
                skor = @skor,
                catatan = @catatan,
                selesai_pada = CASE WHEN @hasil IS NOT NULL AND @hasil IN ('Lulus','Tidak_Lulus','Dipertimbangkan','No_Show') THEN ISNULL(selesai_pada, GETDATE()) ELSE selesai_pada END
            WHERE id_interview = @id_interview;

            IF @id_interviewer IS NOT NULL AND EXISTS (SELECT 1 FROM dbo.M_USERS WHERE id_user = @id_interviewer)
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM dbo.INTERVIEW_PARTICIPANTS WHERE id_interview = @id_interview AND id_user = @id_interviewer)
                BEGIN
                    INSERT INTO dbo.INTERVIEW_PARTICIPANTS (id_interview, id_user, peran)
                    VALUES (@id_interview, @id_interviewer, ISNULL(@peran_interviewer, 'HR'));
                END
                ELSE
                BEGIN
                    UPDATE dbo.INTERVIEW_PARTICIPANTS SET peran = ISNULL(@peran_interviewer, peran)
                    WHERE id_interview = @id_interview AND id_user = @id_interviewer;
                END
            END

            DECLARE @nl VARCHAR(100) = 'hasil=' + ISNULL(@old_hasil, '-');
            DECLARE @nb2 VARCHAR(200) = 'hasil=' + ISNULL(@hasil, '-') + ', skor=' + ISNULL(CONVERT(VARCHAR(10), @skor), '-');
            EXEC dbo.sp_AuditLog 'INTERVIEWS', @id_interview, 'UPDATE', @nl, @nb2, @oleh_user;
        END

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SaveIvw;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
