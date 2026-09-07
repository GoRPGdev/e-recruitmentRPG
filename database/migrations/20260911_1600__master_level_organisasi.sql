/* =========================================================================
   20260911_1600__master_level_organisasi.sql
   Menambahkan tabel master dbo.M_LEVEL_ORGANISASI untuk mengelola
   level organisasi posisi secara dinamis.
   Kompatibel dengan SQL Server 2008 R2.
   ========================================================================= */

IF OBJECT_ID('dbo.M_LEVEL_ORGANISASI') IS NULL
BEGIN
    CREATE TABLE dbo.M_LEVEL_ORGANISASI (
        id_level_organisasi  INT IDENTITY(1,1) NOT NULL,
        kode_level           VARCHAR(30)       NOT NULL,
        nama_level           NVARCHAR(100)     NOT NULL,
        urutan               INT               NOT NULL CONSTRAINT DF_M_LEVEL_urut  DEFAULT (0),
        keterangan           NVARCHAR(255)     NULL,
        is_aktif             BIT               NOT NULL CONSTRAINT DF_M_LEVEL_aktif DEFAULT (1),
        CONSTRAINT PK_M_LEVEL_ORGANISASI PRIMARY KEY (id_level_organisasi),
        CONSTRAINT UQ_M_LEVEL_kode UNIQUE (kode_level)
    );
END
GO

-- Seed 6 level organisasi standar awal RPG
IF NOT EXISTS (SELECT 1 FROM dbo.M_LEVEL_ORGANISASI WHERE kode_level = 'MP')
    INSERT INTO dbo.M_LEVEL_ORGANISASI (kode_level, nama_level, urutan, keterangan, is_aktif)
    VALUES ('MP', N'Man Power (Outlet / Lapangan)', 1, N'Karyawan lini operasional gerai / outlet', 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_LEVEL_ORGANISASI WHERE kode_level = 'Staff')
    INSERT INTO dbo.M_LEVEL_ORGANISASI (kode_level, nama_level, urutan, keterangan, is_aktif)
    VALUES ('Staff', N'Staff (Headquarters / Back-Office)', 2, N'Tenaga kerja staf umum kantor pusat', 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_LEVEL_ORGANISASI WHERE kode_level = 'Staff_Krusial')
    INSERT INTO dbo.M_LEVEL_ORGANISASI (kode_level, nama_level, urutan, keterangan, is_aktif)
    VALUES ('Staff_Krusial', N'Staff Krusial (Specialist / Core)', 3, N'Staf posisi spesialisasi teknis atau posisi dengan dampak krusial', 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_LEVEL_ORGANISASI WHERE kode_level = 'Spv')
    INSERT INTO dbo.M_LEVEL_ORGANISASI (kode_level, nama_level, urutan, keterangan, is_aktif)
    VALUES ('Spv', N'Supervisor', 4, N'Pengawas dan koordinator tim operasional', 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_LEVEL_ORGANISASI WHERE kode_level = 'Manager')
    INSERT INTO dbo.M_LEVEL_ORGANISASI (kode_level, nama_level, urutan, keterangan, is_aktif)
    VALUES ('Manager', N'Manager', 5, N'Pimpinan departemen / fungsi bisnis', 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_LEVEL_ORGANISASI WHERE kode_level = 'Senior_Manager')
    INSERT INTO dbo.M_LEVEL_ORGANISASI (kode_level, nama_level, urutan, keterangan, is_aktif)
    VALUES ('Senior_Manager', N'Senior Manager / Head of Division', 6, N'Pimpinan divisi senior', 1);
GO

-- Lepas check constraint statis CK_M_POSISI_level jika ada, agar dinamis membaca data M_LEVEL_ORGANISASI
IF EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_M_POSISI_level')
BEGIN
    ALTER TABLE dbo.M_POSISI DROP CONSTRAINT CK_M_POSISI_level;
END
GO
