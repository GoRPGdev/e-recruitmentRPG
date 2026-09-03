/* =========================================================================
   20260908_1100__kandidat_lamaran.sql
   FASE 1 -- Kandidat, Lamaran & Proses Seleksi.
   Acuan: ERD Terkoreksi v1.2 sec.7.3 + RENCANA sec.3.1.

   Prasyarat: 20260908_1000 + 20260908_1030 sudah jalan.

   Konvensi lanjutan:
   T1  text (ERD) -> VARCHAR(MAX).
   T2  [KONFIRMASI] APPLICATIONS.id_stage_sekarang -> FK ke M_STAGE
       (pointer tahap kini / sumbu papan pipeline). Bisa juga dimaksud
       ke APPLICATION_STAGES.
   T2c no_wa_normal nullable + unik -> filtered unique index
       WHERE no_wa_normal IS NOT NULL (dedupe: WA -> email -> hash CV;
       baris import tanpa WA tetap boleh masuk).
   T5  IMPORT_BATCHES dibuat DI SINI (sebelum APPLICATIONS) supaya FK
       APPLICATIONS.id_import_batch tidak circular. IMPORT_BATCH_ROWS
       (yang menunjuk balik ke APPLICATIONS) ada di file berikutnya.
   K-INT  INTERVIEWS & PSIKOTES_RESULTS menempel ke APPLICATION_STAGES,
          TANPA id_lamaran redundan (ikut rekomendasi ERD sec.7.3 baris 518).
   ========================================================================= */


IF OBJECT_ID('dbo.CANDIDATES') IS NULL
CREATE TABLE dbo.CANDIDATES (
    id_kandidat          INT IDENTITY(1,1) NOT NULL,
    nama_lengkap         NVARCHAR(150) NOT NULL,
    email                VARCHAR(150)  NULL,               -- dedupe sekunder; TIDAK di-unique (SP yang dedupe)
    no_wa_raw            VARCHAR(40)   NULL,               -- apa adanya dari input
    no_wa_normal         VARCHAR(20)   NULL,               -- 628xxx hasil normalisasi; unik via filtered index
    tanggal_lahir        DATE          NULL,
    pendidikan_terakhir  NVARCHAR(60)  NULL,
    kota_domisili        NVARCHAR(100) NULL,
    is_blacklist         BIT           NOT NULL CONSTRAINT DF_CAND_bl DEFAULT (0),
    catatan_blacklist    VARCHAR(MAX)  NULL,               -- T1
    consent_pada         DATETIME      NULL,
    consent_versi        VARCHAR(20)   NULL,
    setuju_talent_pool   BIT           NOT NULL CONSTRAINT DF_CAND_tp DEFAULT (0),
    retensi_sampai       DATE          NULL,               -- 12 bln sejak ditolak / 24 bln jika talent pool
    created_at           DATETIME      NOT NULL CONSTRAINT DF_CAND_created DEFAULT (GETDATE()),
    -- RENCANA sec.3.1 (di-fold): data milik ORANG, jarang berubah antar lamaran
    tempat_lahir         NVARCHAR(100) NULL,
    jenis_kelamin        CHAR(1)       NULL,               -- 'L' / 'P' (disimpan, TIDAK jadi filter)
    nama_sekolah         NVARCHAR(150) NULL,
    jurusan              NVARCHAR(100) NULL,
    alamat_lengkap       NVARCHAR(500) NULL,
    status_pernikahan    VARCHAR(20)   NULL,               -- T3: Belum_Menikah / Menikah / Cerai
    kontak_darurat_nama  NVARCHAR(150) NULL,
    kontak_darurat_telp  VARCHAR(30)   NULL,
    kontak_darurat_hub   VARCHAR(30)   NULL,               -- T3: Ayah/Ibu/Kakak_Adik/...
    CONSTRAINT PK_CANDIDATES PRIMARY KEY (id_kandidat),
    CONSTRAINT CK_CAND_gender CHECK (jenis_kelamin IS NULL OR jenis_kelamin IN ('L','P'))
);
GO

IF OBJECT_ID('dbo.CANDIDATES') IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_CAND_wa' AND object_id = OBJECT_ID('dbo.CANDIDATES'))
CREATE UNIQUE INDEX UX_CAND_wa ON dbo.CANDIDATES (no_wa_normal) WHERE no_wa_normal IS NOT NULL;
GO


-- T5: IMPORT_BATCHES di sini (butuh REQUISITIONS, JOB_POSTINGS, M_CHANNEL saja)
IF OBJECT_ID('dbo.IMPORT_BATCHES') IS NULL
CREATE TABLE dbo.IMPORT_BATCHES (
    id_batch      INT IDENTITY(1,1) NOT NULL,
    id_req        INT          NOT NULL,
    id_posting    INT          NULL,
    id_channel    INT          NOT NULL,
    nama_file     NVARCHAR(260) NULL,
    jumlah_baris  INT          NOT NULL CONSTRAINT DF_IB_baris DEFAULT (0),
    berhasil      INT          NOT NULL CONSTRAINT DF_IB_ok    DEFAULT (0),
    gagal         INT          NOT NULL CONSTRAINT DF_IB_gagal DEFAULT (0),
    duplikat      INT          NOT NULL CONSTRAINT DF_IB_dup   DEFAULT (0),
    status        VARCHAR(15)  NOT NULL CONSTRAINT DF_IB_status DEFAULT ('Preview'),
    diimpor_oleh  INT          NOT NULL,
    diimpor_pada  DATETIME     NOT NULL CONSTRAINT DF_IB_pada DEFAULT (GETDATE()),
    CONSTRAINT PK_IMPORT_BATCHES PRIMARY KEY (id_batch),
    CONSTRAINT FK_IB_req     FOREIGN KEY (id_req)      REFERENCES dbo.REQUISITIONS (id_req),
    CONSTRAINT FK_IB_posting FOREIGN KEY (id_posting)  REFERENCES dbo.JOB_POSTINGS (id_posting),
    CONSTRAINT FK_IB_channel FOREIGN KEY (id_channel)  REFERENCES dbo.M_CHANNEL (id_channel),
    CONSTRAINT FK_IB_user    FOREIGN KEY (diimpor_oleh) REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT CK_IB_status CHECK (status IN ('Preview','Committed','Rolled_Back'))
);
GO


IF OBJECT_ID('dbo.APPLICATIONS') IS NULL
CREATE TABLE dbo.APPLICATIONS (
    id_lamaran          INT IDENTITY(1,1) NOT NULL,
    id_kandidat         INT          NOT NULL,
    id_req              INT          NOT NULL,             -- WAJIB
    id_posting          INT          NULL,                 -- NULL untuk MP
    id_flow             INT          NOT NULL,             -- SNAPSHOT saat lamaran dibuat
    flow_versi          INT          NULL,                 -- RENCANA sec.3.3: versi template saat snapshot
    id_stage_sekarang   INT          NULL,                 -- T2: FK ke M_STAGE
    status_global       VARCHAR(20)  NOT NULL CONSTRAINT DF_APP_status DEFAULT ('In_Progress'),
    intake_method       VARCHAR(15)  NOT NULL,             -- FORM_PUBLIC, IMPORT_FILE, MANUAL, BULK_CV
    id_channel          INT          NULL,
    id_import_batch     INT          NULL,
    screening_score     INT          NULL,                 -- dari n8n
    screening_method    VARCHAR(10)  NULL,                 -- AI / MANUAL
    screening_notes     VARCHAR(MAX) NULL,                 -- T1
    id_remark_terakhir  INT          NULL,
    tanggal_lamar       DATE         NULL,
    created_at          DATETIME     NOT NULL CONSTRAINT DF_APP_created DEFAULT (GETDATE()),
    CONSTRAINT PK_APPLICATIONS PRIMARY KEY (id_lamaran),
    CONSTRAINT FK_APP_kandidat FOREIGN KEY (id_kandidat)        REFERENCES dbo.CANDIDATES (id_kandidat),
    CONSTRAINT FK_APP_req      FOREIGN KEY (id_req)             REFERENCES dbo.REQUISITIONS (id_req),
    CONSTRAINT FK_APP_posting  FOREIGN KEY (id_posting)         REFERENCES dbo.JOB_POSTINGS (id_posting),
    CONSTRAINT FK_APP_flow     FOREIGN KEY (id_flow)            REFERENCES dbo.M_FLOW (id_flow),
    CONSTRAINT FK_APP_stage    FOREIGN KEY (id_stage_sekarang)  REFERENCES dbo.M_STAGE (id_stage),
    CONSTRAINT FK_APP_channel  FOREIGN KEY (id_channel)         REFERENCES dbo.M_CHANNEL (id_channel),
    CONSTRAINT FK_APP_batch    FOREIGN KEY (id_import_batch)    REFERENCES dbo.IMPORT_BATCHES (id_batch),
    CONSTRAINT FK_APP_remark   FOREIGN KEY (id_remark_terakhir) REFERENCES dbo.M_REMARKS (id_remark),
    CONSTRAINT CK_APP_status CHECK (status_global IN
        ('In_Progress','On_Hold','Unreachable','Rejected','Withdrawn',
         'Offer_Declined','No_Show','Hired','Talent_Pool')),
    CONSTRAINT CK_APP_intake CHECK (intake_method IN ('FORM_PUBLIC','IMPORT_FILE','MANUAL','BULK_CV')),
    CONSTRAINT CK_APP_screening CHECK (screening_method IS NULL OR screening_method IN ('AI','MANUAL'))
);
GO


IF OBJECT_ID('dbo.APPLICATION_STAGES') IS NULL
CREATE TABLE dbo.APPLICATION_STAGES (
    id_app_stage   INT IDENTITY(1,1) NOT NULL,
    id_lamaran     INT          NOT NULL,
    id_stage       INT          NOT NULL,                  -- FK M_STAGE (di-snapshot dari flow)
    urutan         INT          NOT NULL,
    status_tahap   VARCHAR(15)  NOT NULL CONSTRAINT DF_AS_status DEFAULT ('Belum'),
    tanggal_mulai  DATETIME     NULL,
    tanggal_selesai DATETIME    NULL,
    pic_user       INT          NULL,
    id_remark      INT          NULL,
    catatan        VARCHAR(MAX) NULL,                      -- T1
    is_sisipan     BIT          NOT NULL CONSTRAINT DF_AS_sisip DEFAULT (0),  -- true = ditambah ad-hoc di tengah
    CONSTRAINT PK_APPLICATION_STAGES PRIMARY KEY (id_app_stage),
    CONSTRAINT UQ_AS_lamaran_urutan UNIQUE (id_lamaran, urutan),  -- SP sisipan: shift urutan dulu, lalu INSERT
    CONSTRAINT FK_AS_lamaran FOREIGN KEY (id_lamaran) REFERENCES dbo.APPLICATIONS (id_lamaran),
    CONSTRAINT FK_AS_stage   FOREIGN KEY (id_stage)   REFERENCES dbo.M_STAGE (id_stage),
    CONSTRAINT FK_AS_pic     FOREIGN KEY (pic_user)   REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT FK_AS_remark  FOREIGN KEY (id_remark)  REFERENCES dbo.M_REMARKS (id_remark),
    CONSTRAINT CK_AS_status CHECK (status_tahap IN ('Belum','Berjalan','Lulus','Tidak_Lulus','Dilewati'))
);
GO


IF OBJECT_ID('dbo.APPLICATION_CONTACTS') IS NULL
CREATE TABLE dbo.APPLICATION_CONTACTS (
    id_kontak    INT IDENTITY(1,1) NOT NULL,
    id_lamaran   INT          NOT NULL,
    upaya_ke     INT          NOT NULL,
    metode       VARCHAR(10)  NOT NULL,                    -- WA, Telepon, Email
    waktu_kontak DATETIME     NOT NULL CONSTRAINT DF_AC_waktu DEFAULT (GETDATE()),
    hasil        VARCHAR(15)  NOT NULL,                    -- Respon, Tidak_Respon, Nomor_Salah, Menolak
    catatan      VARCHAR(MAX) NULL,                        -- T1
    oleh_user    INT          NULL,
    CONSTRAINT PK_APPLICATION_CONTACTS PRIMARY KEY (id_kontak),
    CONSTRAINT FK_AC_lamaran FOREIGN KEY (id_lamaran) REFERENCES dbo.APPLICATIONS (id_lamaran),
    CONSTRAINT FK_AC_user    FOREIGN KEY (oleh_user)  REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT CK_AC_metode CHECK (metode IN ('WA','Telepon','Email')),
    CONSTRAINT CK_AC_hasil  CHECK (hasil IN ('Respon','Tidak_Respon','Nomor_Salah','Menolak'))
);
GO


IF OBJECT_ID('dbo.APPLICATION_HISTORY') IS NULL
CREATE TABLE dbo.APPLICATION_HISTORY (
    id_history    INT IDENTITY(1,1) NOT NULL,
    id_lamaran    INT          NOT NULL,
    waktu         DATETIME     NOT NULL CONSTRAINT DF_AH_waktu DEFAULT (GETDATE()),
    jenis_event   VARCHAR(20)  NOT NULL,                   -- STAGE_CHANGE, KONTAK, DOKUMEN, OFFER, STATUS_CHANGE, CATATAN
    id_stage_dari INT          NULL,
    id_stage_ke   INT          NULL,
    status_dari   VARCHAR(20)  NULL,
    status_ke     VARCHAR(20)  NULL,
    id_remark     INT          NULL,
    deskripsi     VARCHAR(MAX) NULL,                       -- T1
    oleh_user     INT          NULL,
    CONSTRAINT PK_APPLICATION_HISTORY PRIMARY KEY (id_history),
    CONSTRAINT FK_AH_lamaran   FOREIGN KEY (id_lamaran)    REFERENCES dbo.APPLICATIONS (id_lamaran),
    CONSTRAINT FK_AH_stagedari FOREIGN KEY (id_stage_dari) REFERENCES dbo.M_STAGE (id_stage),
    CONSTRAINT FK_AH_stageke   FOREIGN KEY (id_stage_ke)   REFERENCES dbo.M_STAGE (id_stage),
    CONSTRAINT FK_AH_remark    FOREIGN KEY (id_remark)     REFERENCES dbo.M_REMARKS (id_remark),
    CONSTRAINT FK_AH_user      FOREIGN KEY (oleh_user)     REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT CK_AH_event CHECK (jenis_event IN
        ('STAGE_CHANGE','KONTAK','DOKUMEN','OFFER','STATUS_CHANGE','CATATAN'))
);
GO


-- RENCANA sec.3.1: data milik LAMARAN (bisa beda tiap kali orang sama melamar lagi)
IF OBJECT_ID('dbo.APPLICATION_PROFILE') IS NULL
CREATE TABLE dbo.APPLICATION_PROFILE (
    id_lamaran          INT           NOT NULL,            -- 1:1 dengan APPLICATIONS
    perusahaan_terakhir NVARCHAR(150) NULL,
    jabatan_terakhir    NVARCHAR(150) NULL,
    periode_kerja       NVARCHAR(100) NULL,
    gaji_terakhir       DECIMAL(18,2) NULL,                -- akses HR Admin ke atas (LIHAT_GAJI_PELAMAR)
    gaji_diharapkan     DECIMAL(18,2) NULL,
    diisi_pada          DATETIME      NULL,
    CONSTRAINT PK_APPLICATION_PROFILE PRIMARY KEY (id_lamaran),
    CONSTRAINT FK_APROF_app FOREIGN KEY (id_lamaran) REFERENCES dbo.APPLICATIONS (id_lamaran)
);
GO


-- RENCANA sec.3.1: data kesehatan -- tabel sendiri, consent terpisah, akses HR Spv saja
IF OBJECT_ID('dbo.CANDIDATE_HEALTH') IS NULL
CREATE TABLE dbo.CANDIDATE_HEALTH (
    id_kandidat      INT           NOT NULL,               -- 1:1 dengan CANDIDATES
    riwayat_penyakit NVARCHAR(500) NULL,
    consent_khusus   BIT           NOT NULL CONSTRAINT DF_CH_consent DEFAULT (0),
    consent_pada     DATETIME      NULL,
    CONSTRAINT PK_CANDIDATE_HEALTH PRIMARY KEY (id_kandidat),
    CONSTRAINT FK_CHEALTH_cand FOREIGN KEY (id_kandidat) REFERENCES dbo.CANDIDATES (id_kandidat)
);
GO


IF OBJECT_ID('dbo.INTERVIEWS') IS NULL
CREATE TABLE dbo.INTERVIEWS (
    id_interview     INT IDENTITY(1,1) NOT NULL,
    id_app_stage     INT          NOT NULL,                -- K-INT: menempel ke APPLICATION_STAGES, tanpa id_lamaran
    tipe             VARCHAR(10)  NULL,                    -- Online, Offline
    jadwal           DATETIME     NULL,
    lokasi_atau_link NVARCHAR(300) NULL,
    hasil            VARCHAR(15)  NULL,                    -- Lulus, Tidak_Lulus, Dipertimbangkan, Reschedule, No_Show
    skor             INT          NULL,
    catatan          VARCHAR(MAX) NULL,                    -- T1
    selesai_pada     DATETIME     NULL,
    CONSTRAINT PK_INTERVIEWS PRIMARY KEY (id_interview),
    CONSTRAINT FK_INT_appstage FOREIGN KEY (id_app_stage) REFERENCES dbo.APPLICATION_STAGES (id_app_stage),
    CONSTRAINT CK_INT_tipe  CHECK (tipe IS NULL OR tipe IN ('Online','Offline')),
    CONSTRAINT CK_INT_hasil CHECK (hasil IS NULL OR hasil IN
        ('Lulus','Tidak_Lulus','Dipertimbangkan','Reschedule','No_Show'))
);
GO


IF OBJECT_ID('dbo.INTERVIEW_PARTICIPANTS') IS NULL
CREATE TABLE dbo.INTERVIEW_PARTICIPANTS (
    id_interview INT         NOT NULL,
    id_user      INT         NOT NULL,
    peran        VARCHAR(10) NOT NULL,                     -- HR, User, BOD
    CONSTRAINT PK_INTERVIEW_PARTICIPANTS PRIMARY KEY (id_interview, id_user),
    CONSTRAINT FK_IP_interview FOREIGN KEY (id_interview) REFERENCES dbo.INTERVIEWS (id_interview),
    CONSTRAINT FK_IP_user      FOREIGN KEY (id_user)      REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT CK_IP_peran CHECK (peran IN ('HR','User','BOD'))
);
GO


IF OBJECT_ID('dbo.PSIKOTES_RESULTS') IS NULL
CREATE TABLE dbo.PSIKOTES_RESULTS (
    id_psikotes   INT IDENTITY(1,1) NOT NULL,
    id_app_stage  INT          NOT NULL,                   -- retake = baris baru, id_app_stage sama
    vendor_tes    NVARCHAR(100) NULL,                      -- DISC, Papi Kostick, dll
    tanggal_tes   DATE         NULL,
    skor_total    INT          NULL,                       -- kolom biasa, reportable
    hasil         VARCHAR(15)  NULL,                       -- Lulus, Tidak_Lulus, Perlu_Review
    detail_skor   XML          NULL,                       -- breakdown sub-tes; XML (2008 R2 tidak punya JSON)
    rekomendasi   VARCHAR(MAX) NULL,                       -- T1
    dilakukan_oleh INT         NULL,
    dibuat_pada   DATETIME     NOT NULL CONSTRAINT DF_PSI_dibuat DEFAULT (GETDATE()),
    CONSTRAINT PK_PSIKOTES_RESULTS PRIMARY KEY (id_psikotes),
    CONSTRAINT FK_PSI_appstage FOREIGN KEY (id_app_stage)  REFERENCES dbo.APPLICATION_STAGES (id_app_stage),
    CONSTRAINT FK_PSI_user     FOREIGN KEY (dilakukan_oleh) REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT CK_PSI_hasil CHECK (hasil IS NULL OR hasil IN ('Lulus','Tidak_Lulus','Perlu_Review'))
);
GO


IF OBJECT_ID('dbo.OFFERS') IS NULL
CREATE TABLE dbo.OFFERS (
    id_offer                INT IDENTITY(1,1) NOT NULL,
    id_lamaran              INT          NOT NULL,          -- 1:1 dengan APPLICATIONS
    gaji_ditawarkan         DECIMAL(18,2) NULL,             -- SENSITIF (LIHAT_GAJI)
    tanggal_penawaran       DATE         NULL,
    tanggal_join_disepakati DATE         NULL,
    tanggal_join_aktual     DATE         NULL,
    status_offer            VARCHAR(10)  NOT NULL CONSTRAINT DF_OFF_status DEFAULT ('Nego'),
    alasan                  NVARCHAR(300) NULL,
    dibuat_oleh             INT          NULL,
    CONSTRAINT PK_OFFERS PRIMARY KEY (id_offer),
    CONSTRAINT UQ_OFF_lamaran UNIQUE (id_lamaran),
    CONSTRAINT FK_OFF_lamaran FOREIGN KEY (id_lamaran)  REFERENCES dbo.APPLICATIONS (id_lamaran),
    CONSTRAINT FK_OFF_user    FOREIGN KEY (dibuat_oleh) REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT CK_OFF_status CHECK (status_offer IN ('Nego','Diterima','Ditolak','Batal'))
);
GO
