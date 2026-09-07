/* =========================================================================
   sp_UpdateRequisitionStatus  --  update manual status MPR oleh HR
   E-Recruitment RPG  --  Requisition Engine (Kahfi)

   Mengizinkan HR (KELOLA_REKRUTMEN) untuk memperbarui status MPR secara terkontrol:
   - 'Sourcing': memulai kembali pencarian
   - 'Sourcing_Ulang': membuka putaran/batch pencarian baru
   - 'Kadaluarsa': menutup requisition karena melewati batas waktu

   Jika status diubah ke 'Kadaluarsa', seluruh form posting publik terkait
   otomatis dinonaktifkan (form_aktif = 0).

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_UpdateRequisitionStatus') IS NOT NULL DROP PROCEDURE dbo.sp_UpdateRequisitionStatus;
GO

CREATE PROCEDURE dbo.sp_UpdateRequisitionStatus
    @id_req         INT,
    @status_baru    VARCHAR(25),
    @catatan        VARCHAR(MAX) = NULL,
    @oleh_user      INT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION UpdReqStatus;

        IF @id_req IS NULL
            RAISERROR('ID Requisition harus diisi.', 16, 1);

        DECLARE @status_lama VARCHAR(25);
        SELECT @status_lama = status_req FROM dbo.REQUISITIONS WHERE id_req = @id_req;

        IF @status_lama IS NULL
            RAISERROR('Requisition tidak ditemukan.', 16, 1);

        IF @status_lama = 'Dibatalkan'
            RAISERROR('Requisition yang sudah Dibatalkan tidak dapat diubah statusnya.', 16, 1);

        IF @status_baru NOT IN ('Draft', 'Review_HR', 'Menunggu_BOD', 'Sourcing', 'Sourcing_Ulang', 'Ditolak_HR', 'Ditolak_BOD', 'Kadaluarsa')
            RAISERROR('Status tujuan tidak valid (%s).', 16, 1, @status_baru);

        /* Validasi aturan transisi alur */
        IF @status_baru = 'Ditolak_HR' AND ISNULL(LTRIM(RTRIM(@catatan)), '') = ''
            RAISERROR('Alasan penolakan oleh HR wajib diisi.', 16, 1);

        /* Update status requisition */
        UPDATE dbo.REQUISITIONS
        SET status_req = @status_baru
        WHERE id_req = @id_req;

        /* Jika menjadi Kadaluarsa, Ditolak_HR, atau Ditolak_BOD, tutup seluruh form posting publik */
        IF @status_baru IN ('Kadaluarsa', 'Ditolak_HR', 'Ditolak_BOD')
        BEGIN
            UPDATE dbo.JOB_POSTINGS
            SET form_aktif = 0,
                form_ditutup = ISNULL(form_ditutup, GETDATE())
            WHERE id_req = @id_req AND form_aktif = 1;
        END

        /* Catat audit trail */
        DECLARE @n_lama VARCHAR(MAX) = 'status_req=' + @status_lama,
                @n_baru VARCHAR(MAX) = 'status_req=' + @status_baru + CASE WHEN @catatan IS NOT NULL THEN '; catatan=' + @catatan ELSE '' END;
        EXEC dbo.sp_AuditLog 'REQUISITIONS', @id_req, 'UPDATE', @n_lama, @n_baru, @oleh_user;

        /* Catat ke riwayat approval jika ada */
        DECLARE @putaran INT =
            ISNULL((SELECT MAX(putaran_ke) FROM dbo.REQUISITION_APPROVALS WHERE id_req = @id_req), 0);

        IF @putaran > 0
        BEGIN
            UPDATE dbo.REQUISITION_APPROVALS
            SET catatan_bod = ISNULL(catatan_bod + ' | ', '') + 'Status [' + @status_lama + ' -> ' + @status_baru + ']: ' + ISNULL(@catatan, '-')
            WHERE id_req = @id_req AND putaran_ke = @putaran;
        END

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION UpdReqStatus;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
