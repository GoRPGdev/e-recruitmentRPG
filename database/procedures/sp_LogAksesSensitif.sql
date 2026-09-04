/* =========================================================================
   sp_LogAksesSensitif  --  satu baris ke ACCESS_LOG_SENSITIF
   E-Recruitment RPG  --  Fase 4 Keamanan & Kepatuhan (Kiki)

   Dicatat setiap kali user MEMBUKA data pribadi spesifik:
   KTP/KK/Ijazah/NPWP (DOK_IDENTITAS), nomor rekening (FINANSIAL),
   range gaji / gaji pelamar / offer (GAJI), riwayat kesehatan (KESEHATAN).
   CLAUDE.md "Tingkat akses data".

   Tanpa transaksi sendiri: satu INSERT, ikut transaksi caller apa adanya.
   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.sp_LogAksesSensitif') IS NOT NULL DROP PROCEDURE dbo.sp_LogAksesSensitif;
GO

CREATE PROCEDURE dbo.sp_LogAksesSensitif
    @id_user      INT,
    @jenis_data   VARCHAR(20),          -- DOK_IDENTITAS / FINANSIAL / GAJI / KESEHATAN
    @id_referensi INT          = NULL,
    @ip           VARCHAR(45)  = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF @jenis_data NOT IN ('DOK_IDENTITAS','FINANSIAL','GAJI','KESEHATAN')
        RAISERROR('sp_LogAksesSensitif: jenis_data "%s" tidak valid.', 16, 1, @jenis_data);

    IF @id_user IS NULL
        RAISERROR('sp_LogAksesSensitif: id_user wajib.', 16, 1);

    INSERT INTO dbo.ACCESS_LOG_SENSITIF (id_user, jenis_data, id_referensi, waktu, ip)
    VALUES (@id_user, @jenis_data, @id_referensi, GETDATE(), @ip);
END
GO
