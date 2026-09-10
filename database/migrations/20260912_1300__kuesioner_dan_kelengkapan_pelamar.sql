/* =========================================================================
   20260912_1300__kuesioner_dan_kelengkapan_pelamar.sql
   Menambahkan tabel dan kolom kelengkapan formulir pelamar lanjutan
   berdasarkan berkas fisik FORM PELAMAR_RPG (K).pdf:
   1. Kolom status tempat tinggal & keahlian/bahasa di dbo.CANDIDATES
   2. Tabel Pelatihan / Kursus / Non-Formal (dbo.CANDIDATE_TRAININGS)
   3. Tabel Referensi Kerja (dbo.CANDIDATE_REFERENCES)
   4. Tabel 12 Butir Kuesioner Evaluasi Diri & Kesiapan Kerja (dbo.CANDIDATE_QUESTIONNAIRE)
   Kompatibel penuh dengan SQL Server 2008 R2 (Normalisasi penuh, no JSON).
   ========================================================================= */

-- 1. Penambahan kolom identitas tempat tinggal & keahlian di dbo.CANDIDATES
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'status_tempat_tinggal')
    ALTER TABLE dbo.CANDIDATES ADD status_tempat_tinggal NVARCHAR(50) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'keahlian_komputer')
    ALTER TABLE dbo.CANDIDATES ADD keahlian_komputer NVARCHAR(255) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = 'bahasa_asing')
    ALTER TABLE dbo.CANDIDATES ADD bahasa_asing NVARCHAR(255) NULL;
GO

-- 2. Tabel Pendidikan Non-Formal / Pelatihan / Kursus / Sertifikasi
IF OBJECT_ID('dbo.CANDIDATE_TRAININGS') IS NULL
BEGIN
    CREATE TABLE dbo.CANDIDATE_TRAININGS (
        id_training      INT IDENTITY(1,1) NOT NULL,
        id_kandidat      INT               NOT NULL,
        id_lamaran       INT               NULL,
        nama_pelatihan   NVARCHAR(150)     NOT NULL,
        penyelenggara    NVARCHAR(150)     NULL,
        tahun            VARCHAR(10)       NULL,
        keterangan       NVARCHAR(255)     NULL,
        urutan           INT               NOT NULL CONSTRAINT DF_CTRN_urutan DEFAULT (1),
        created_at       DATETIME          NOT NULL CONSTRAINT DF_CTRN_created DEFAULT (GETDATE()),
        CONSTRAINT PK_CANDIDATE_TRAININGS PRIMARY KEY (id_training),
        CONSTRAINT FK_CTRN_kandidat FOREIGN KEY (id_kandidat) REFERENCES dbo.CANDIDATES (id_kandidat)
    );
END
GO

-- 3. Tabel Referensi Kerja / Kontak Profesional
IF OBJECT_ID('dbo.CANDIDATE_REFERENCES') IS NULL
BEGIN
    CREATE TABLE dbo.CANDIDATE_REFERENCES (
        id_ref           INT IDENTITY(1,1) NOT NULL,
        id_kandidat      INT               NOT NULL,
        id_lamaran       INT               NULL,
        nama_referensi   NVARCHAR(150)     NOT NULL,
        perusahaan       NVARCHAR(150)     NULL,
        jabatan          NVARCHAR(100)     NULL,
        no_telp          VARCHAR(40)       NULL,
        hubungan         NVARCHAR(100)     NULL,
        urutan           INT               NOT NULL CONSTRAINT DF_CREF_urutan DEFAULT (1),
        created_at       DATETIME          NOT NULL CONSTRAINT DF_CREF_created DEFAULT (GETDATE()),
        CONSTRAINT PK_CANDIDATE_REFERENCES PRIMARY KEY (id_ref),
        CONSTRAINT FK_CREF_kandidat FOREIGN KEY (id_kandidat) REFERENCES dbo.CANDIDATES (id_kandidat)
    );
END
GO

-- 4. Tabel Kuesioner Evaluasi Diri & Kesiapan Kerja (12 Butir Acuan RPG)
IF OBJECT_ID('dbo.CANDIDATE_QUESTIONNAIRE') IS NULL
BEGIN
    CREATE TABLE dbo.CANDIDATE_QUESTIONNAIRE (
        id_quest             INT IDENTITY(1,1) NOT NULL,
        id_lamaran           INT               NOT NULL,
        id_kandidat          INT               NOT NULL,
        alasan_melamar       NVARCHAR(MAX)     NULL, -- Motivasi melamar di RPG
        pengetahuan_rpg      NVARCHAR(MAX)     NULL, -- Pengetahuan tentang bisnis/brand RPG
        kelebihan_diri       NVARCHAR(MAX)     NULL, -- Strengths
        kekurangan_diri      NVARCHAR(MAX)     NULL, -- Weaknesses & cara mengatasi
        prestasi_terbesar    NVARCHAR(MAX)     NULL, -- Prestasi membanggakan
        harapan_gaji_fasilitas NVARCHAR(500)   NULL, -- Ekspektasi gaji & fasilitas
        ketersediaan_mulai   NVARCHAR(100)     NULL, -- Kapan siap mulai / notice period
        bersedia_shift_lembur NVARCHAR(50)     NULL, -- Ya / Tidak (+ keterangan)
        bersedia_luar_kota   NVARCHAR(50)      NULL, -- Bersedia penempatan/mutasi cabang
        punya_bisnis_sampingan NVARCHAR(500)   NULL, -- Keterangan bisnis sampingan
        relasi_keluarga_rpg  NVARCHAR(500)     NULL, -- Kerabat / keluarga di RPG
        riwayat_tindak_pidana NVARCHAR(500)    NULL, -- Catatan kasus hukum / pidana
        diisi_pada           DATETIME          NOT NULL CONSTRAINT DF_CQUEST_diisi DEFAULT (GETDATE()),
        CONSTRAINT PK_CANDIDATE_QUESTIONNAIRE PRIMARY KEY (id_quest),
        CONSTRAINT FK_CQUEST_lamaran  FOREIGN KEY (id_lamaran)  REFERENCES dbo.APPLICATIONS (id_lamaran),
        CONSTRAINT FK_CQUEST_kandidat FOREIGN KEY (id_kandidat) REFERENCES dbo.CANDIDATES (id_kandidat)
    );
END
GO
