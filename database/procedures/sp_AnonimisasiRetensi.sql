/* =========================================================================
   sp_AnonimisasiRetensi  --  scrub PII kandidat yang masa retensinya habis
   E-Recruitment RPG  --  Fase 4 Keamanan & Kepatuhan (Kiki)

   RENCANA sec.6 Fase 4: "job penghapusan/anonimisasi lewat Task Scheduler".
   Dijalankan berkala oleh tools/run-retensi.php.

   Sasaran: CANDIDATES dengan retensi_sampai < hari ini, is_anonim = 0, DAN
   tidak punya lamaran yang masih berjalan (In_Progress/On_Hold/Unreachable).

   Yang di-scrub: identitas orang di CANDIDATES, CANDIDATE_HEALTH (dihapus),
   CANDIDATE_BANK (dihapus), kolom bebas di APPLICATION_PROFILE, path & metadata
   file di CANDIDATE_DOCUMENTS. Baris lamaran + tahap + history TETAP (angka
   agregat untuk report; sudah tak mengandung identitas).

   Result set:
     1) file fisik yang harus dihapus runner  (id_cand_doc, id_lamaran, path_file)
     2) ringkasan                              (jumlah_dianonim)

   @simulasi = 1  -> hanya kembalikan result set 1 + hitungan, tanpa mengubah.

   Pola savepoint. Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_AnonimisasiRetensi') IS NOT NULL DROP PROCEDURE dbo.sp_AnonimisasiRetensi;
GO

CREATE PROCEDURE dbo.sp_AnonimisasiRetensi
    @batch_size INT = 200,
    @oleh_user  INT = NULL,
    @simulasi   BIT = 0
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION; ELSE SAVE TRANSACTION Anonim;

        IF @batch_size IS NULL OR @batch_size < 1 SET @batch_size = 200;
        DECLARE @hari_ini DATE = CONVERT(DATE, GETDATE());

        DECLARE @target TABLE (id_kandidat INT PRIMARY KEY);
        INSERT INTO @target (id_kandidat)
        SELECT TOP (@batch_size) c.id_kandidat
        FROM dbo.CANDIDATES c
        WHERE c.retensi_sampai IS NOT NULL
          AND c.retensi_sampai < @hari_ini
          AND c.is_anonim = 0
          AND NOT EXISTS (
                SELECT 1 FROM dbo.APPLICATIONS a
                WHERE a.id_kandidat = c.id_kandidat
                  AND a.status_global IN ('In_Progress','On_Hold','Unreachable'))
        ORDER BY c.retensi_sampai;

        DECLARE @n INT = (SELECT COUNT(*) FROM @target);

        /* result set 1 -- file fisik untuk dihapus runner (path masih valid di sini) */
        SELECT d.id_cand_doc, d.id_lamaran, d.path_file
        FROM dbo.CANDIDATE_DOCUMENTS d
        JOIN dbo.APPLICATIONS a ON a.id_lamaran = d.id_lamaran
        JOIN @target t          ON t.id_kandidat = a.id_kandidat
        WHERE d.path_file <> N'[dihapus-retensi]';

        IF @simulasi = 1
        BEGIN
            SELECT @n AS jumlah_dianonim;               -- result set 2
            IF @outer = 0 ROLLBACK TRANSACTION;
            ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION Anonim;
            RETURN;
        END

        /* audit ringkas per kandidat (sebelum scrub) */
        INSERT INTO dbo.AUDIT_LOG (nama_tabel, id_baris, aksi, nilai_lama, nilai_baru, oleh_user)
        SELECT 'CANDIDATES', c.id_kandidat, 'UPDATE',
               'retensi habis ' + CONVERT(VARCHAR(10), c.retensi_sampai, 23),
               'PII di-scrub, is_anonim=1', @oleh_user
        FROM dbo.CANDIDATES c JOIN @target t ON t.id_kandidat = c.id_kandidat;

        /* identitas orang */
        UPDATE c
        SET nama_lengkap        = N'[dihapus #' + CONVERT(NVARCHAR(12), c.id_kandidat) + N']',
            email               = NULL,
            no_wa_raw           = NULL,
            no_wa_normal        = NULL,
            tanggal_lahir       = NULL,
            kota_domisili       = NULL,
            tempat_lahir        = NULL,
            jenis_kelamin       = NULL,
            nama_sekolah        = NULL,
            jurusan             = NULL,
            alamat_lengkap      = NULL,
            status_pernikahan   = NULL,
            kontak_darurat_nama = NULL,
            kontak_darurat_telp = NULL,
            kontak_darurat_hub  = NULL,
            is_anonim           = 1,
            anonim_pada         = GETDATE()
        FROM dbo.CANDIDATES c JOIN @target t ON t.id_kandidat = c.id_kandidat;

        /* data kesehatan & rekening -- hapus baris */
        DELETE h FROM dbo.CANDIDATE_HEALTH h JOIN @target t ON t.id_kandidat = h.id_kandidat;

        DELETE b FROM dbo.CANDIDATE_BANK b
        JOIN dbo.APPLICATIONS a ON a.id_lamaran = b.id_lamaran
        JOIN @target t          ON t.id_kandidat = a.id_kandidat;

        /* profil lamaran -- kosongkan kolom bebas + gaji */
        UPDATE p
        SET perusahaan_terakhir = NULL,
            jabatan_terakhir    = NULL,
            periode_kerja       = NULL,
            gaji_terakhir       = NULL,
            gaji_diharapkan     = NULL
        FROM dbo.APPLICATION_PROFILE p
        JOIN dbo.APPLICATIONS a ON a.id_lamaran = p.id_lamaran
        JOIN @target t          ON t.id_kandidat = a.id_kandidat;

        /* dokumen -- lepas path (NOT NULL -> tombstone) + metadata file */
        UPDATE d
        SET path_file          = N'[dihapus-retensi]',
            nama_file_asli     = NULL,
            hash_sha256        = NULL,
            mime_type          = NULL,
            catatan_verifikasi = LEFT(ISNULL(d.catatan_verifikasi + ' | ', '') + 'file dihapus (retensi '
                                 + CONVERT(VARCHAR(10), @hari_ini, 23) + ')', 8000)
        FROM dbo.CANDIDATE_DOCUMENTS d
        JOIN dbo.APPLICATIONS a ON a.id_lamaran = d.id_lamaran
        JOIN @target t          ON t.id_kandidat = a.id_kandidat;

        SELECT @n AS jumlah_dianonim;                   -- result set 2

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @m VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0 BEGIN IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; END
        ELSE IF XACT_STATE() = 1 ROLLBACK TRANSACTION Anonim;
        RAISERROR(@m, 16, 1);
    END CATCH
END
GO
