/* =========================================================================
   sp_SubmitApplication  --  terima 1 lamaran (form publik / manual / import)
   E-Recruitment RPG  --  modul Intake (Kiki)

   Alur:
     1. Resolusi requisition/posting dari url_slug (FORM_PUBLIC) atau param manual
     2. Validasi form/requisition masih terbuka
     3. Normalisasi WA -> dedupe kandidat:  no_wa_normal -> email -> hash CV
        (kandidat lama dipakai ulang; kalau tidak ketemu -> INSERT CANDIDATES)
     4. Tolak kalau kandidat sudah punya lamaran In_Progress di requisition sama
     5. Snapshot id_flow (dari requisition, fallback default_flow posisi) + versi
     6. INSERT APPLICATIONS (+ APPLICATION_PROFILE, + CANDIDATE_HEALTH bila diisi)
     7. APPLICATION_HISTORY (STATUS_CHANGE)
     8. EXEC sp_GenerateApplicationStages  (snapshot tahap)
     9. JOB_POSTINGS.jumlah_submit++ bila lewat posting
   Semua dalam 1 transaksi.

   Parameter binding wajib dari pemanggil (PHP: sqlsrv_query dengan params).
   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_SubmitApplication') IS NOT NULL
    DROP PROCEDURE dbo.sp_SubmitApplication;
GO

CREATE PROCEDURE dbo.sp_SubmitApplication
    -- rute
    @url_slug            VARCHAR(100) = NULL,   -- FORM_PUBLIC: link /lamar/<slug>
    @id_req_manual       INT          = NULL,   -- MANUAL / IMPORT: kalau slug NULL
    @id_posting_manual   INT          = NULL,
    @intake_method       VARCHAR(15)  = 'FORM_PUBLIC',
    @nama_channel        NVARCHAR(60) = N'Portal Sendiri',
    -- kandidat (orang)
    @nama_lengkap        NVARCHAR(150),
    @email               VARCHAR(150) = NULL,
    @no_wa_raw           VARCHAR(40)  = NULL,
    @tempat_lahir        NVARCHAR(100) = NULL,
    @tanggal_lahir       DATE         = NULL,
    @jenis_kelamin       CHAR(1)      = NULL,
    @pendidikan_terakhir NVARCHAR(60) = NULL,
    @nama_sekolah        NVARCHAR(150) = NULL,
    @jurusan             NVARCHAR(100) = NULL,
    @kota_domisili       NVARCHAR(100) = NULL,
    @alamat_lengkap      NVARCHAR(500) = NULL,
    @status_pernikahan   VARCHAR(20)  = NULL,
    @kontak_darurat_nama NVARCHAR(150) = NULL,
    @kontak_darurat_telp VARCHAR(30)  = NULL,
    @kontak_darurat_hub  VARCHAR(30)  = NULL,
    -- consent umum
    @consent_versi       VARCHAR(20)  = NULL,
    @setuju_talent_pool  BIT          = 0,
    -- kesehatan (consent terpisah; hanya ditulis kalau consent_kesehatan = 1)
    @riwayat_penyakit    NVARCHAR(500) = NULL,
    @consent_kesehatan   BIT          = 0,
    -- profil lamaran (pekerjaan terakhir)
    @perusahaan_terakhir NVARCHAR(150) = NULL,
    @jabatan_terakhir    NVARCHAR(150) = NULL,
    @periode_kerja       NVARCHAR(100) = NULL,
    @gaji_terakhir       DECIMAL(18,2) = NULL,
    @gaji_diharapkan     DECIMAL(18,2) = NULL,
    -- dedupe CV
    @cv_hash             CHAR(64)     = NULL,
    -- output
    @id_lamaran          INT OUTPUT,
    @id_kandidat         INT OUTPUT,
    @is_kandidat_baru    BIT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION;
        ELSE SAVE TRANSACTION SubmitApp;

            /* -- 1. resolusi requisition / posting ------------------------ */
            DECLARE @id_req INT, @id_posting INT;

            IF @url_slug IS NOT NULL
            BEGIN
                SELECT @id_posting = jp.id_posting, @id_req = jp.id_req
                FROM dbo.JOB_POSTINGS jp
                WHERE jp.url_slug = @url_slug;

                IF @id_req IS NULL
                    RAISERROR('Link lamaran tidak dikenal.', 16, 1);

                IF NOT EXISTS (
                    SELECT 1 FROM dbo.JOB_POSTINGS jp
                    WHERE jp.id_posting = @id_posting
                      AND jp.form_aktif = 1
                      AND (jp.form_dibuka  IS NULL OR jp.form_dibuka  <= GETDATE())
                      AND (jp.form_ditutup IS NULL OR jp.form_ditutup >= GETDATE()))
                    RAISERROR('Lowongan sudah ditutup.', 16, 1);
            END
            ELSE
            BEGIN
                SET @id_req     = @id_req_manual;
                SET @id_posting = @id_posting_manual;
                IF @id_req IS NULL
                    RAISERROR('id_req wajib untuk intake non-FORM_PUBLIC.', 16, 1);
            END

            IF NOT EXISTS (
                SELECT 1 FROM dbo.REQUISITIONS
                WHERE id_req = @id_req
                  AND status_req NOT IN ('Draft','Terpenuhi','Dibatalkan','Kadaluarsa'))
                RAISERROR('Requisition tidak menerima lamaran saat ini.', 16, 1);

            /* -- 2. channel --------------------------------------------- */
            DECLARE @id_channel INT =
                (SELECT TOP 1 id_channel FROM dbo.M_CHANNEL
                 WHERE nama_channel = @nama_channel AND is_aktif = 1);

            /* -- 3. dedupe kandidat ----------------------------------- */
            DECLARE @wa VARCHAR(20) = dbo.fn_NormalisasiWA(@no_wa_raw);
            SET @id_kandidat = NULL;

            IF @wa IS NOT NULL
                SELECT @id_kandidat = id_kandidat
                FROM dbo.CANDIDATES WHERE no_wa_normal = @wa;

            IF @id_kandidat IS NULL AND @email IS NOT NULL
                SELECT TOP 1 @id_kandidat = id_kandidat
                FROM dbo.CANDIDATES WHERE email = @email
                ORDER BY id_kandidat;

            IF @id_kandidat IS NULL AND @cv_hash IS NOT NULL
                SELECT TOP 1 @id_kandidat = a.id_kandidat
                FROM dbo.CANDIDATE_DOCUMENTS cd
                JOIN dbo.APPLICATIONS a ON a.id_lamaran = cd.id_lamaran
                WHERE cd.hash_sha256 = @cv_hash
                ORDER BY cd.diunggah_pada DESC;

            IF @id_kandidat IS NULL
            BEGIN
                INSERT INTO dbo.CANDIDATES
                    (nama_lengkap, email, no_wa_raw, no_wa_normal, tanggal_lahir,
                     pendidikan_terakhir, kota_domisili, consent_pada, consent_versi,
                     setuju_talent_pool, tempat_lahir, jenis_kelamin, nama_sekolah,
                     jurusan, alamat_lengkap, status_pernikahan, kontak_darurat_nama,
                     kontak_darurat_telp, kontak_darurat_hub)
                VALUES
                    (@nama_lengkap, @email, @no_wa_raw, @wa, @tanggal_lahir,
                     @pendidikan_terakhir, @kota_domisili,
                     CASE WHEN @consent_versi IS NULL THEN NULL ELSE GETDATE() END, @consent_versi,
                     @setuju_talent_pool, @tempat_lahir, @jenis_kelamin, @nama_sekolah,
                     @jurusan, @alamat_lengkap, @status_pernikahan, @kontak_darurat_nama,
                     @kontak_darurat_telp, @kontak_darurat_hub);

                SET @id_kandidat = SCOPE_IDENTITY();
                SET @is_kandidat_baru = 1;
            END
            ELSE
            BEGIN
                SET @is_kandidat_baru = 0;
                -- lengkapi kolom yang masih kosong, jangan timpa yang sudah ada
                UPDATE dbo.CANDIDATES
                SET email               = ISNULL(email, @email),
                    no_wa_normal        = ISNULL(no_wa_normal, @wa),
                    no_wa_raw           = ISNULL(no_wa_raw, @no_wa_raw),
                    tanggal_lahir       = ISNULL(tanggal_lahir, @tanggal_lahir),
                    pendidikan_terakhir = ISNULL(pendidikan_terakhir, @pendidikan_terakhir),
                    kota_domisili       = ISNULL(kota_domisili, @kota_domisili),
                    setuju_talent_pool  = CASE WHEN @setuju_talent_pool = 1 THEN 1 ELSE setuju_talent_pool END
                WHERE id_kandidat = @id_kandidat;
            END

            /* -- 4. tolak lamaran dobel yang masih jalan -------------- */
            IF EXISTS (
                SELECT 1 FROM dbo.APPLICATIONS
                WHERE id_kandidat = @id_kandidat
                  AND id_req = @id_req
                  AND status_global = 'In_Progress')
                RAISERROR('Kandidat ini sudah punya lamaran aktif untuk posisi tersebut.', 16, 1);

            /* -- 5. snapshot flow ------------------------------------- */
            DECLARE @id_flow INT, @flow_versi INT;
            SELECT @id_flow = COALESCE(r.id_flow, p.default_flow)
            FROM dbo.REQUISITIONS r
            LEFT JOIN dbo.M_POSISI p ON p.id_posisi = r.id_posisi
            WHERE r.id_req = @id_req;

            IF @id_flow IS NULL
                RAISERROR('Requisition/posisi belum punya flow. Tetapkan flow dulu.', 16, 1);

            SELECT @flow_versi = versi FROM dbo.M_FLOW WHERE id_flow = @id_flow;

            /* -- 6. APPLICATIONS ------------------------------------- */
            INSERT INTO dbo.APPLICATIONS
                (id_kandidat, id_req, id_posting, id_flow, flow_versi, status_global,
                 intake_method, id_channel, tanggal_lamar)
            VALUES
                (@id_kandidat, @id_req, @id_posting, @id_flow, @flow_versi, 'In_Progress',
                 @intake_method, @id_channel, CAST(GETDATE() AS DATE));

            SET @id_lamaran = SCOPE_IDENTITY();

            IF @perusahaan_terakhir IS NOT NULL OR @jabatan_terakhir IS NOT NULL
               OR @gaji_terakhir IS NOT NULL OR @gaji_diharapkan IS NOT NULL
                INSERT INTO dbo.APPLICATION_PROFILE
                    (id_lamaran, perusahaan_terakhir, jabatan_terakhir, periode_kerja,
                     gaji_terakhir, gaji_diharapkan, diisi_pada)
                VALUES
                    (@id_lamaran, @perusahaan_terakhir, @jabatan_terakhir, @periode_kerja,
                     @gaji_terakhir, @gaji_diharapkan, GETDATE());

            IF @consent_kesehatan = 1
            BEGIN
                IF EXISTS (SELECT 1 FROM dbo.CANDIDATE_HEALTH WHERE id_kandidat = @id_kandidat)
                    UPDATE dbo.CANDIDATE_HEALTH
                    SET riwayat_penyakit = ISNULL(@riwayat_penyakit, riwayat_penyakit),
                        consent_khusus = 1, consent_pada = GETDATE()
                    WHERE id_kandidat = @id_kandidat;
                ELSE
                    INSERT INTO dbo.CANDIDATE_HEALTH (id_kandidat, riwayat_penyakit, consent_khusus, consent_pada)
                    VALUES (@id_kandidat, @riwayat_penyakit, 1, GETDATE());
            END

            /* -- 7. history ----------------------------------------- */
            INSERT INTO dbo.APPLICATION_HISTORY (id_lamaran, jenis_event, status_ke, deskripsi)
            VALUES (@id_lamaran, 'STATUS_CHANGE', 'In_Progress',
                    'Lamaran dibuat via ' + @intake_method + '.');

            /* -- 8. generate tahap (snapshot flow) ------------------ */
            EXEC dbo.sp_GenerateApplicationStages @id_lamaran;

            /* -- 9. hitung submit posting -------------------------- */
            IF @id_posting IS NOT NULL
                UPDATE dbo.JOB_POSTINGS SET jumlah_submit = jumlah_submit + 1
                WHERE id_posting = @id_posting;

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @msg VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0
        BEGIN
            IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        END
        ELSE IF XACT_STATE() = 1
            ROLLBACK TRANSACTION SubmitApp;
        RAISERROR(@msg, 16, 1);
    END CATCH
END
GO
