/* =========================================================================
   sp_SaveOffer  --  buat / perbarui penawaran kerja (Offer)
   E-Recruitment RPG  --  Flow Engine / Seleksi (Kahfi)

   Menangani tabel OFFERS (1:1 dengan APPLICATIONS).
   gaji_ditawarkan bersifat SENSITIF (LIHAT_GAJI).
   status_offer: 'Nego','Diterima','Ditolak','Batal'.
   Audit trail dicatat via sp_AuditLog.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SaveOffer') IS NOT NULL DROP PROCEDURE dbo.sp_SaveOffer;
GO

CREATE PROCEDURE dbo.sp_SaveOffer
    @id_offer                INT            = NULL OUTPUT,
    @id_lamaran              INT,
    @gaji_ditawarkan         DECIMAL(18,2)  = NULL,
    @tanggal_penawaran       DATE           = NULL,
    @tanggal_join_disepakati DATE           = NULL,
    @tanggal_join_aktual     DATE           = NULL,
    @status_offer            VARCHAR(10)    = 'Nego',
    @alasan                  NVARCHAR(300)  = NULL,
    @oleh_user               INT            = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION SaveOff;

        IF NOT EXISTS (SELECT 1 FROM dbo.APPLICATIONS WHERE id_lamaran = @id_lamaran)
            RAISERROR('Lamaran (APPLICATIONS) tidak ditemukan.', 16, 1);

        IF @status_offer NOT IN ('Nego','Diterima','Ditolak','Batal')
            RAISERROR('Status offer tidak valid. Harus Nego, Diterima, Ditolak, atau Batal.', 16, 1);

        IF @tanggal_penawaran IS NULL SET @tanggal_penawaran = CONVERT(DATE, GETDATE());

        DECLARE @existing_id INT;
        SELECT @existing_id = id_offer FROM dbo.OFFERS WHERE id_lamaran = @id_lamaran;

        IF @existing_id IS NULL
        BEGIN
            INSERT INTO dbo.OFFERS (id_lamaran, gaji_ditawarkan, tanggal_penawaran, tanggal_join_disepakati, tanggal_join_aktual, status_offer, alasan, dibuat_oleh)
            VALUES (@id_lamaran, @gaji_ditawarkan, @tanggal_penawaran, @tanggal_join_disepakati, @tanggal_join_aktual, @status_offer, @alasan, @oleh_user);
            SET @id_offer = SCOPE_IDENTITY();

            DECLARE @nb VARCHAR(300) = 'lamaran=' + CONVERT(VARCHAR(10), @id_lamaran) + ', status=' + @status_offer + ', join=' + ISNULL(CONVERT(VARCHAR(10), @tanggal_join_disepakati, 23), '-');
            EXEC dbo.sp_AuditLog 'OFFERS', @id_offer, 'INSERT', NULL, @nb, @oleh_user;
        END
        ELSE
        BEGIN
            DECLARE @old_st VARCHAR(10);
            SELECT @old_st = status_offer FROM dbo.OFFERS WHERE id_offer = @existing_id;

            UPDATE dbo.OFFERS
            SET gaji_ditawarkan = ISNULL(@gaji_ditawarkan, gaji_ditawarkan),
                tanggal_penawaran = ISNULL(@tanggal_penawaran, tanggal_penawaran),
                tanggal_join_disepakati = ISNULL(@tanggal_join_disepakati, tanggal_join_disepakati),
                tanggal_join_aktual = ISNULL(@tanggal_join_aktual, tanggal_join_aktual),
                status_offer = @status_offer,
                alasan = @alasan,
                dibuat_oleh = ISNULL(@oleh_user, dibuat_oleh)
            WHERE id_offer = @existing_id;
            SET @id_offer = @existing_id;

            DECLARE @nl VARCHAR(50) = 'status=' + ISNULL(@old_st, '-');
            DECLARE @nb2 VARCHAR(300) = 'status=' + @status_offer + ', join=' + ISNULL(CONVERT(VARCHAR(10), @tanggal_join_disepakati, 23), '-');
            EXEC dbo.sp_AuditLog 'OFFERS', @id_offer, 'UPDATE', @nl, @nb2, @oleh_user;
        END

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION SaveOff;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
