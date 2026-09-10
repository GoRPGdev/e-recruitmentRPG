/* =========================================================================
   sp_SaveOnboardingData.sql
   Menyimpan data kelengkapan formulir onboarding kandidat:
   - Data identitas & demografi tambahan di CANDIDATES
   - Status tempat tinggal, keahlian komputer, & bahasa asing
   - Data rekening bank di CANDIDATE_BANK
   - Data riwayat penyakit di CANDIDATE_HEALTH (bila consent)
   - Catatan riwayat di APPLICATION_HISTORY
   - Update status token menjadi terpakai
   Kompatibel dengan SQL Server 2008 R2.
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SaveOnboardingData') IS NOT NULL
    DROP PROCEDURE dbo.sp_SaveOnboardingData;
GO

CREATE PROCEDURE dbo.sp_SaveOnboardingData
    @id_lamaran            INT,
    @token                 VARCHAR(80),
    -- Data Identitas & Pribadi
    @nama_panggilan        NVARCHAR(50)  = NULL,
    @nik                   VARCHAR(30)   = NULL,
    @no_sim                VARCHAR(30)   = NULL,
    @npwp                  VARCHAR(40)   = NULL,
    @agama                 NVARCHAR(30)  = NULL,
    @gol_darah             VARCHAR(5)    = NULL,
    @tinggi_badan          INT           = NULL,
    @berat_badan           INT           = NULL,
    @status_tempat_tinggal NVARCHAR(50)  = NULL,
    @keahlian_komputer     NVARCHAR(255) = NULL,
    @bahasa_asing          NVARCHAR(255) = NULL,
    -- Rekening Bank
    @nama_bank             NVARCHAR(80)  = NULL,
    @no_rekening           VARCHAR(40)   = NULL,
    @nama_pemilik_bank     NVARCHAR(150) = NULL,
    -- Kesehatan (opsional)
    @riwayat_penyakit      NVARCHAR(500) = NULL,
    @consent_kesehatan     BIT           = 0,
    -- Audit & User
    @ip_pengunggah         VARCHAR(45)   = NULL,
    -- Status Simpan (0 = Draft / Progres Sementara, 1 = Final Kirim)
    @is_final              BIT           = 1
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION;
        ELSE SAVE TRANSACTION SaveOnboarding;

        -- 1. Validasi Token & Lamaran
        DECLARE @id_token INT, @id_kandidat INT, @is_revoked BIT, @dipakai_pada DATETIME, @kadaluarsa_pada DATETIME;

        SELECT @id_token = ft.id_token,
               @id_kandidat = a.id_kandidat,
               @is_revoked = ft.is_revoked,
               @dipakai_pada = ft.dipakai_pada,
               @kadaluarsa_pada = ft.kadaluarsa_pada
        FROM dbo.FORM_TOKENS ft
        JOIN dbo.APPLICATIONS a ON a.id_lamaran = ft.id_lamaran
        WHERE ft.token = @token AND ft.id_lamaran = @id_lamaran;

        IF @id_token IS NULL
            RAISERROR('Tautan onboarding tidak valid atau tidak ditemukan.', 16, 1);

        IF @is_revoked = 1
            RAISERROR('Tautan formulir ini telah dicabut oleh tim HR.', 16, 1);

        IF @dipakai_pada IS NOT NULL
            RAISERROR('Tautan formulir onboarding ini sudah pernah digunakan.', 16, 1);

        IF @kadaluarsa_pada IS NOT NULL AND @kadaluarsa_pada < GETDATE()
            RAISERROR('Masa berlaku tautan formulir onboarding ini sudah kedaluwarsa.', 16, 1);

        -- 2. Update Data Identitas Tambahan di dbo.CANDIDATES
        UPDATE dbo.CANDIDATES
        SET nama_panggilan        = COALESCE(@nama_panggilan, nama_panggilan),
            nik                   = COALESCE(@nik, nik),
            no_sim                = COALESCE(@no_sim, no_sim),
            npwp                  = COALESCE(@npwp, npwp),
            agama                 = COALESCE(@agama, agama),
            gol_darah             = COALESCE(@gol_darah, gol_darah),
            tinggi_badan          = COALESCE(@tinggi_badan, tinggi_badan),
            berat_badan           = COALESCE(@berat_badan, berat_badan),
            status_tempat_tinggal = COALESCE(@status_tempat_tinggal, status_tempat_tinggal),
            keahlian_komputer     = COALESCE(@keahlian_komputer, keahlian_komputer),
            bahasa_asing          = COALESCE(@bahasa_asing, bahasa_asing)
        WHERE id_kandidat = @id_kandidat;

        -- 3. Simpan / Update Data Rekening Bank di dbo.CANDIDATE_BANK
        IF @no_rekening IS NOT NULL AND RTRIM(@no_rekening) <> ''
        BEGIN
            IF EXISTS (SELECT 1 FROM dbo.CANDIDATE_BANK WHERE id_lamaran = @id_lamaran)
            BEGIN
                UPDATE dbo.CANDIDATE_BANK
                SET nama_bank    = @nama_bank,
                    no_rekening  = @no_rekening,
                    nama_pemilik = @nama_pemilik_bank,
                    diinput_pada = GETDATE()
                WHERE id_lamaran = @id_lamaran;
            END
            ELSE
            BEGIN
                INSERT INTO dbo.CANDIDATE_BANK (id_lamaran, nama_bank, no_rekening, nama_pemilik, diinput_oleh, diinput_pada)
                VALUES (@id_lamaran, @nama_bank, @no_rekening, @nama_pemilik_bank, NULL, GETDATE());
            END
        END

        -- 4. Simpan / Update Data Kesehatan di dbo.CANDIDATE_HEALTH (Bila ada consent)
        IF @consent_kesehatan = 1 AND @riwayat_penyakit IS NOT NULL AND RTRIM(@riwayat_penyakit) <> ''
        BEGIN
            IF EXISTS (SELECT 1 FROM dbo.CANDIDATE_HEALTH WHERE id_kandidat = @id_kandidat)
            BEGIN
                UPDATE dbo.CANDIDATE_HEALTH
                SET riwayat_penyakit = @riwayat_penyakit,
                    consent_khusus   = 1,
                    consent_pada     = GETDATE()
                WHERE id_kandidat = @id_kandidat;
            END
            ELSE
            BEGIN
                INSERT INTO dbo.CANDIDATE_HEALTH (id_kandidat, riwayat_penyakit, consent_khusus, consent_pada)
                VALUES (@id_kandidat, @riwayat_penyakit, 1, GETDATE());
            END
        END

        -- 5. Catat riwayat di APPLICATION_HISTORY
        IF @is_final = 1
        BEGIN
            INSERT INTO dbo.APPLICATION_HISTORY (id_lamaran, jenis_event, deskripsi, oleh_user, waktu)
            VALUES (@id_lamaran, 'DOKUMEN', N'Kandidat telah melengkapi formulir onboarding mandiri via web portal.', NULL, GETDATE());

            -- 6. Tandai Token Sebagai Sudah Terpakai (Hanya jika final)
            UPDATE dbo.FORM_TOKENS
            SET dipakai_pada = GETDATE()
            WHERE id_token = @id_token;
        END

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @outer = 0 ROLLBACK TRANSACTION;
        ELSE ROLLBACK TRANSACTION SaveOnboarding;

        DECLARE @err_msg NVARCHAR(2048) = ERROR_MESSAGE();
        RAISERROR(@err_msg, 16, 1);
    END CATCH
END
GO
