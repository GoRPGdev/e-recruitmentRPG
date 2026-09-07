/* =========================================================================
   20260912_1000__form_onboarding_kandidat.sql
   Menambahkan tabel dan kolom untuk formulir onboarding lanjutan kandidat:
   - Kolom identitas & legalitas di CANDIDATES (nik, agama, gol_darah, no_sim, npwp)
   - Tabel riwayat pengalaman kerja multi-item (CANDIDATE_WORK_EXPERIENCES)
   - Tabel susunan anggota keluarga multi-item (CANDIDATE_FAMILY)
   - Dukungan tujuan token 'FORM_ONBOARDING' pada FORM_TOKENS
   Kompatibel dengan SQL Server 2008 R2.
   ========================================================================= */

-- 1. Tambah kolom identitas lanjutan di dbo.CANDIDATES
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'nik')
    ALTER TABLE dbo.CANDIDATES ADD nik VARCHAR(30) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'agama')
    ALTER TABLE dbo.CANDIDATES ADD agama NVARCHAR(30) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'gol_darah')
    ALTER TABLE dbo.CANDIDATES ADD gol_darah VARCHAR(5) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'no_sim')
    ALTER TABLE dbo.CANDIDATES ADD no_sim VARCHAR(30) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'npwp')
    ALTER TABLE dbo.CANDIDATES ADD npwp VARCHAR(40) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'nama_panggilan')
    ALTER TABLE dbo.CANDIDATES ADD nama_panggilan NVARCHAR(50) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'tinggi_badan')
    ALTER TABLE dbo.CANDIDATES ADD tinggi_badan INT NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'berat_badan')
    ALTER TABLE dbo.CANDIDATES ADD berat_badan INT NULL;
GO

-- 2. Tabel Riwayat Pengalaman Kerja Multi-Item (CANDIDATE_WORK_EXPERIENCES)
IF OBJECT_ID('dbo.CANDIDATE_WORK_EXPERIENCES') IS NULL
BEGIN
    CREATE TABLE dbo.CANDIDATE_WORK_EXPERIENCES (
        id_exp           INT IDENTITY(1,1) NOT NULL,
        id_lamaran       INT               NOT NULL,
        id_kandidat      INT               NOT NULL,
        nama_perusahaan  NVARCHAR(150)     NOT NULL,
        posisi_jabatan   NVARCHAR(150)     NOT NULL,
        periode_kerja    NVARCHAR(100)     NULL,
        gaji_terakhir    DECIMAL(18,2)     NULL,
        alasan_keluar    NVARCHAR(500)     NULL,
        deskripsi_tugas  NVARCHAR(MAX)     NULL,
        urutan           INT               NOT NULL CONSTRAINT DF_CWE_urutan DEFAULT (1),
        created_at       DATETIME          NOT NULL CONSTRAINT DF_CWE_created DEFAULT (GETDATE()),
        CONSTRAINT PK_CANDIDATE_WORK_EXPERIENCES PRIMARY KEY (id_exp),
        CONSTRAINT FK_CWE_lamaran  FOREIGN KEY (id_lamaran)  REFERENCES dbo.APPLICATIONS (id_lamaran),
        CONSTRAINT FK_CWE_kandidat FOREIGN KEY (id_kandidat) REFERENCES dbo.CANDIDATES (id_kandidat)
    );
END
GO

-- 3. Tabel Susunan Anggota Keluarga Multi-Item (CANDIDATE_FAMILY)
IF OBJECT_ID('dbo.CANDIDATE_FAMILY') IS NULL
BEGIN
    CREATE TABLE dbo.CANDIDATE_FAMILY (
        id_family     INT IDENTITY(1,1) NOT NULL,
        id_kandidat   INT               NOT NULL,
        hubungan      NVARCHAR(50)      NOT NULL, -- Ayah, Ibu, Suami, Istri, Anak, Kakak, Adik
        nama_lengkap  NVARCHAR(150)     NOT NULL,
        jenis_kelamin CHAR(1)           NULL,     -- L / P
        usia          INT               NULL,
        pendidikan    NVARCHAR(50)      NULL,
        pekerjaan     NVARCHAR(100)     NULL,
        no_telp       VARCHAR(30)       NULL,
        urutan        INT               NOT NULL CONSTRAINT DF_CFAM_urutan DEFAULT (1),
        created_at    DATETIME          NOT NULL CONSTRAINT DF_CFAM_created DEFAULT (GETDATE()),
        CONSTRAINT PK_CANDIDATE_FAMILY PRIMARY KEY (id_family),
        CONSTRAINT FK_CFAM_kandidat FOREIGN KEY (id_kandidat) REFERENCES dbo.CANDIDATES (id_kandidat)
    );
END
GO

-- 4. Perbarui Check Constraint pada FORM_TOKENS agar mendukung 'FORM_ONBOARDING'
IF EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_FT_tujuan')
BEGIN
    ALTER TABLE dbo.FORM_TOKENS DROP CONSTRAINT CK_FT_tujuan;
    ALTER TABLE dbo.FORM_TOKENS ADD CONSTRAINT CK_FT_tujuan
        CHECK (tujuan IN ('FORM2', 'UPLOAD_DOKUMEN', 'FORM_ONBOARDING'));
END
GO
