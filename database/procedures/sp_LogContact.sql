/* =========================================================================
   sp_LogContact  --  catat upaya kontak kandidat
   E-Recruitment RPG  --  Flow Engine (Kahfi)

   Batas upaya dibaca dari M_FLOW.maks_upaya_kontak (aturan bisnis #4).
   Auto Unreachable saat batas tercapai; reversible saat kandidat merespons
   (aturan bisnis #5).

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_LogContact') IS NOT NULL DROP PROCEDURE dbo.sp_LogContact;
GO

CREATE PROCEDURE dbo.sp_LogContact
    @id_lamaran INT,
    @metode     VARCHAR(10),     -- WA / Telepon / Email
    @hasil      VARCHAR(15),     -- Respon / Tidak_Respon / Nomor_Salah / Menolak
    @catatan    VARCHAR(MAX) = NULL,
    @oleh_user  INT          = NULL,
    @upaya_ke   INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION LogCt;

        IF @metode NOT IN ('WA','Telepon','Email')
            RAISERROR('Metode kontak tidak valid.', 16, 1);
        IF @hasil NOT IN ('Respon','Tidak_Respon','Nomor_Salah','Menolak')
            RAISERROR('Hasil kontak tidak valid.', 16, 1);

        DECLARE @st_global VARCHAR(20), @id_flow INT;
        SELECT @st_global = status_global, @id_flow = id_flow
        FROM dbo.APPLICATIONS WHERE id_lamaran = @id_lamaran;

        IF @st_global IS NULL
            RAISERROR('Lamaran tidak ditemukan.', 16, 1);
        IF @st_global IN ('Hired','Rejected','Withdrawn','Offer_Declined','No_Show','Talent_Pool')
            RAISERROR('Lamaran sudah final.', 16, 1);

        SET @upaya_ke = ISNULL((SELECT MAX(upaya_ke) FROM dbo.APPLICATION_CONTACTS WHERE id_lamaran = @id_lamaran), 0) + 1;

        INSERT INTO dbo.APPLICATION_CONTACTS (id_lamaran, upaya_ke, metode, waktu_kontak, hasil, catatan, oleh_user)
        VALUES (@id_lamaran, @upaya_ke, @metode, GETDATE(), @hasil, @catatan, @oleh_user);

        DECLARE @maks INT = ISNULL((SELECT maks_upaya_kontak FROM dbo.M_FLOW WHERE id_flow = @id_flow), 3);
        DECLARE @st_baru VARCHAR(20) = @st_global;

        IF @hasil = 'Menolak'
            SET @st_baru = 'Withdrawn';
        ELSE IF @hasil = 'Respon' AND @st_global = 'Unreachable'
            SET @st_baru = 'In_Progress';
        ELSE IF @hasil IN ('Tidak_Respon','Nomor_Salah') AND @upaya_ke >= @maks AND @st_global <> 'Unreachable'
            SET @st_baru = 'Unreachable';

        IF @st_baru <> @st_global
        BEGIN
            UPDATE dbo.APPLICATIONS SET status_global = @st_baru WHERE id_lamaran = @id_lamaran;
            INSERT INTO dbo.APPLICATION_HISTORY (id_lamaran, jenis_event, status_dari, status_ke, deskripsi, oleh_user)
            VALUES (@id_lamaran, 'KONTAK', @st_global, @st_baru,
                    'Kontak #' + CONVERT(VARCHAR(10), @upaya_ke) + ' (' + @metode + '/' + @hasil + ')', @oleh_user);
        END
        ELSE
            INSERT INTO dbo.APPLICATION_HISTORY (id_lamaran, jenis_event, deskripsi, oleh_user)
            VALUES (@id_lamaran, 'KONTAK',
                    'Kontak #' + CONVERT(VARCHAR(10), @upaya_ke) + ' (' + @metode + '/' + @hasil + ')', @oleh_user);

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION LogCt;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
