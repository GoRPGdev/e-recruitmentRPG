/* =========================================================================
   20260911_1500__master_efek_status.sql
   Menambahkan tabel master dbo.M_EFEK_STATUS untuk mengelola efek status seleksi.
   Kompatibel dengan SQL Server 2008 R2.
   ========================================================================= */

IF OBJECT_ID('dbo.M_EFEK_STATUS') IS NULL
BEGIN
    CREATE TABLE dbo.M_EFEK_STATUS (
        id_efek_status       INT IDENTITY(1,1) NOT NULL,
        kode_efek            VARCHAR(20)       NOT NULL,
        nama_efek            NVARCHAR(100)     NOT NULL,
        status_tahap         VARCHAR(15)       NOT NULL,  -- Lulus / Tidak_Lulus / Berjalan
        status_global_target VARCHAR(20)       NOT NULL,  -- In_Progress / On_Hold / Unreachable / Rejected / Withdrawn / Offer_Declined / No_Show / Hired / Talent_Pool
        deskripsi            NVARCHAR(255)     NULL,
        urutan               INT               NOT NULL CONSTRAINT DF_M_EFEK_urut  DEFAULT (0),
        is_aktif             BIT               NOT NULL CONSTRAINT DF_M_EFEK_aktif DEFAULT (1),
        CONSTRAINT PK_M_EFEK_STATUS PRIMARY KEY (id_efek_status),
        CONSTRAINT UQ_M_EFEK_kode UNIQUE (kode_efek)
    );
END
GO

-- Seed 9 Efek Status Baku Sistem
IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'LANJUT')
    INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
    VALUES ('LANJUT', N'Lanjut ke Tahap Berikutnya', 'Lulus', 'In_Progress', N'Kandidat lulus tahap ini dan otomatis berlanjut ke tahap berikutnya di pipeline.', 1, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'TOLAK')
    INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
    VALUES ('TOLAK', N'Tidak Lolos / Ditolak', 'Tidak_Lulus', 'Rejected', N'Kandidat gugur/tidak memenuhi kualifikasi seleksi pada tahap ini.', 2, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'ON_HOLD')
    INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
    VALUES ('ON_HOLD', N'Dipertimbangkan / On Hold', 'Berjalan', 'On_Hold', N'Keputusan ditunda sementara menunggu pembanding kandidat lain.', 3, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'UNREACHABLE')
    INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
    VALUES ('UNREACHABLE', N'Tidak Dapat Dihubungi', 'Berjalan', 'Unreachable', N'Kandidat tidak merespons setelah upaya kontak maksimal tercapai.', 4, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'WITHDRAWN')
    INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
    VALUES ('WITHDRAWN', N'Mengundurkan Diri', 'Tidak_Lulus', 'Withdrawn', N'Kandidat menyatakan mundur dari proses seleksi.', 5, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'OFFER_DECLINED')
    INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
    VALUES ('OFFER_DECLINED', N'Menolak Penawaran Kerja', 'Tidak_Lulus', 'Offer_Declined', N'Kandidat menolak offering letter yang telah diberikan.', 6, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'NO_SHOW')
    INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
    VALUES ('NO_SHOW', N'Tidak Hadir (No Show)', 'Tidak_Lulus', 'No_Show', N'Kandidat tidak hadir pada jadwal onboarding / hari pertama kerja.', 7, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'HIRED')
    INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
    VALUES ('HIRED', N'Diterima Bekerja (Hired)', 'Lulus', 'Hired', N'Kandidat resmi bergabung dan kuota pemenuhan MPR terhitung.', 8, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'TALENT_POOL')
    INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
    VALUES ('TALENT_POOL', N'Disimpan di Talent Pool', 'Tidak_Lulus', 'Talent_Pool', N'Kandidat berpotensi baik namun disimpan untuk kebutuhan posisi lain di masa depan.', 9, 1);
GO
