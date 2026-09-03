/* =========================================================================
   20260908_1130__import_dokumen_audit.sql
   FASE 1 -- Dokumen kandidat, sisa Import, log akses & audit, tabel report.
   Acuan: ERD Terkoreksi v1.2 sec.7.4 + RENCANA sec.3.2 & sec.3.4.

   Prasyarat: 20260908_1000 + 1030 + 1100 sudah jalan.

   Konvensi lanjutan:
   T1  text (ERD) -> VARCHAR(MAX).
   T7  AUDIT_LOG.nilai_lama/baru = VARCHAR(MAX). Write-only, tak pernah
       di-query isinya -> dikecualikan dari aturan anti-JSON CLAUDE.md.
   T-MAP [KONFIRMASI] IMPORT_TEMPLATES.mapping_kolom (ERD: text blob)
       DINORMALISASI ke IMPORT_TEMPLATE_KOLOM. CLAUDE.md: jangan simpan
       JSON di kolom teks. Deviasi sadar dari ERD sec.7.4.
   RPT PK RPT_FUNNEL_HARIAN persis RENCANA sec.3.4 (tanpa id_flow di PK).
   ========================================================================= */


-- IMPORT_BATCHES sudah dibuat di 20260908_1100 (urutan FK). Di sini turunannya.

IF OBJECT_ID('dbo.IMPORT_BATCH_ROWS') IS NULL
CREATE TABLE dbo.IMPORT_BATCH_ROWS (
    id_row            INT IDENTITY(1,1) NOT NULL,
    id_batch          INT          NOT NULL,
    nomor_baris       INT          NOT NULL,
    payload_mentah    VARCHAR(MAX) NULL,                   -- T1: baris sumber apa adanya
    hasil             VARCHAR(10)  NOT NULL CONSTRAINT DF_IBR_hasil DEFAULT ('OK'),  -- OK, Duplikat, Gagal
    alasan_gagal      NVARCHAR(300) NULL,
    id_lamaran_dibuat INT          NULL,
    CONSTRAINT PK_IMPORT_BATCH_ROWS PRIMARY KEY (id_row),
    CONSTRAINT FK_IBR_batch   FOREIGN KEY (id_batch)          REFERENCES dbo.IMPORT_BATCHES (id_batch),
    CONSTRAINT FK_IBR_lamaran FOREIGN KEY (id_lamaran_dibuat) REFERENCES dbo.APPLICATIONS (id_lamaran),
    CONSTRAINT CK_IBR_hasil CHECK (hasil IN ('OK','Duplikat','Gagal'))
);
GO


IF OBJECT_ID('dbo.IMPORT_TEMPLATES') IS NULL
CREATE TABLE dbo.IMPORT_TEMPLATES (
    id_template  INT IDENTITY(1,1) NOT NULL,
    id_channel   INT          NOT NULL,
    nama_template NVARCHAR(120) NOT NULL,
    dibuat_pada  DATETIME     NOT NULL CONSTRAINT DF_IT_dibuat DEFAULT (GETDATE()),
    CONSTRAINT PK_IMPORT_TEMPLATES PRIMARY KEY (id_template),
    CONSTRAINT FK_IT_channel FOREIGN KEY (id_channel) REFERENCES dbo.M_CHANNEL (id_channel)
);
GO

-- T-MAP: pengganti kolom  mapping_kolom text  di ERD -- satu baris per pemetaan
IF OBJECT_ID('dbo.IMPORT_TEMPLATE_KOLOM') IS NULL
CREATE TABLE dbo.IMPORT_TEMPLATE_KOLOM (
    id_template   INT          NOT NULL,
    kolom_sumber  NVARCHAR(150) NOT NULL,                  -- nama kolom di file portal
    kolom_sistem  VARCHAR(60)  NOT NULL,                   -- nama kolom tujuan di sistem
    urutan        INT          NOT NULL CONSTRAINT DF_ITK_urut DEFAULT (0),
    CONSTRAINT PK_IMPORT_TEMPLATE_KOLOM PRIMARY KEY (id_template, kolom_sistem),
    CONSTRAINT FK_ITK_template FOREIGN KEY (id_template) REFERENCES dbo.IMPORT_TEMPLATES (id_template)
);
GO


IF OBJECT_ID('dbo.CANDIDATE_DOCUMENTS') IS NULL
CREATE TABLE dbo.CANDIDATE_DOCUMENTS (
    id_cand_doc        INT IDENTITY(1,1) NOT NULL,
    id_lamaran         INT          NOT NULL,
    id_dokumen         INT          NOT NULL,              -- FK M_DOKUMEN (jenis)
    path_file          NVARCHAR(400) NOT NULL,             -- di luar webroot
    nama_file_asli     NVARCHAR(260) NULL,
    hash_sha256        CHAR(64)     NULL,                  -- deteksi CV duplikat
    ukuran_byte        INT          NULL,
    mime_type          VARCHAR(100) NULL,
    status_verifikasi  VARCHAR(10)  NOT NULL CONSTRAINT DF_CD_status DEFAULT ('Proses'),  -- Proses, Done, Ditolak
    catatan_verifikasi VARCHAR(MAX) NULL,                  -- T1
    diunggah_pada      DATETIME     NOT NULL CONSTRAINT DF_CD_upload DEFAULT (GETDATE()),
    diunggah_oleh      INT          NULL,
    ip_pengunggah      VARCHAR(45)  NULL,
    diverifikasi_oleh  INT          NULL,
    diverifikasi_pada  DATETIME     NULL,
    CONSTRAINT PK_CANDIDATE_DOCUMENTS PRIMARY KEY (id_cand_doc),
    CONSTRAINT FK_CD_lamaran  FOREIGN KEY (id_lamaran)        REFERENCES dbo.APPLICATIONS (id_lamaran),
    CONSTRAINT FK_CD_dokumen  FOREIGN KEY (id_dokumen)        REFERENCES dbo.M_DOKUMEN (id_dokumen),
    CONSTRAINT FK_CD_upload   FOREIGN KEY (diunggah_oleh)     REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT FK_CD_verifby  FOREIGN KEY (diverifikasi_oleh) REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT CK_CD_status CHECK (status_verifikasi IN ('Proses','Done','Ditolak'))
);
GO


IF OBJECT_ID('dbo.CANDIDATE_BANK') IS NULL
CREATE TABLE dbo.CANDIDATE_BANK (
    id_bank      INT IDENTITY(1,1) NOT NULL,
    id_lamaran   INT          NOT NULL,                    -- 1:1 dengan APPLICATIONS
    nama_bank    NVARCHAR(80)  NULL,
    no_rekening  VARCHAR(40)   NULL,                       -- SENSITIF (LIHAT_FINANSIAL)
    nama_pemilik NVARCHAR(150) NULL,
    diinput_oleh INT          NULL,
    diinput_pada DATETIME     NOT NULL CONSTRAINT DF_CB_input DEFAULT (GETDATE()),
    CONSTRAINT PK_CANDIDATE_BANK PRIMARY KEY (id_bank),
    CONSTRAINT UQ_CB_lamaran UNIQUE (id_lamaran),
    CONSTRAINT FK_CB_lamaran FOREIGN KEY (id_lamaran)   REFERENCES dbo.APPLICATIONS (id_lamaran),
    CONSTRAINT FK_CB_user    FOREIGN KEY (diinput_oleh) REFERENCES dbo.M_USERS (id_user)
);
GO


IF OBJECT_ID('dbo.FORM_TOKENS') IS NULL
CREATE TABLE dbo.FORM_TOKENS (
    id_token       INT IDENTITY(1,1) NOT NULL,
    id_lamaran     INT          NOT NULL,
    token          VARCHAR(80)  NOT NULL,                  -- acak, panjang
    tujuan         VARCHAR(20)  NOT NULL,                  -- FORM2, UPLOAD_DOKUMEN
    kadaluarsa_pada DATETIME    NULL,
    dipakai_pada   DATETIME     NULL,
    dibuat_oleh    INT          NULL,
    is_revoked     BIT          NOT NULL CONSTRAINT DF_FT_revoked DEFAULT (0),
    CONSTRAINT PK_FORM_TOKENS PRIMARY KEY (id_token),
    CONSTRAINT UQ_FT_token UNIQUE (token),
    CONSTRAINT FK_FT_lamaran FOREIGN KEY (id_lamaran)  REFERENCES dbo.APPLICATIONS (id_lamaran),
    CONSTRAINT FK_FT_user    FOREIGN KEY (dibuat_oleh) REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT CK_FT_tujuan CHECK (tujuan IN ('FORM2','UPLOAD_DOKUMEN'))
);
GO


IF OBJECT_ID('dbo.ACCESS_LOG_SENSITIF') IS NULL
CREATE TABLE dbo.ACCESS_LOG_SENSITIF (
    id_log       INT IDENTITY(1,1) NOT NULL,
    id_user      INT          NOT NULL,
    jenis_data   VARCHAR(20)  NOT NULL,                    -- DOK_IDENTITAS, FINANSIAL, GAJI, KESEHATAN
    id_referensi INT          NULL,
    waktu        DATETIME     NOT NULL CONSTRAINT DF_ALS_waktu DEFAULT (GETDATE()),
    ip           VARCHAR(45)  NULL,
    CONSTRAINT PK_ACCESS_LOG_SENSITIF PRIMARY KEY (id_log),
    CONSTRAINT FK_ALS_user FOREIGN KEY (id_user) REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT CK_ALS_jenis CHECK (jenis_data IN ('DOK_IDENTITAS','FINANSIAL','GAJI','KESEHATAN'))
);
GO


IF OBJECT_ID('dbo.AUDIT_LOG') IS NULL
CREATE TABLE dbo.AUDIT_LOG (
    id_audit   INT IDENTITY(1,1) NOT NULL,
    nama_tabel VARCHAR(60)  NOT NULL,
    id_baris   INT          NULL,
    aksi       VARCHAR(10)  NOT NULL,                      -- INSERT, UPDATE, DELETE
    nilai_lama VARCHAR(MAX) NULL,                          -- T7
    nilai_baru VARCHAR(MAX) NULL,                          -- T7
    oleh_user  INT          NULL,
    waktu      DATETIME     NOT NULL CONSTRAINT DF_AUD_waktu DEFAULT (GETDATE()),
    CONSTRAINT PK_AUDIT_LOG PRIMARY KEY (id_audit),
    CONSTRAINT FK_AUD_user FOREIGN KEY (oleh_user) REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT CK_AUD_aksi CHECK (aksi IN ('INSERT','UPDATE','DELETE'))
);
GO


-- RENCANA sec.3.2: rem anti-spam link form publik
IF OBJECT_ID('dbo.FORM_SUBMIT_LOG') IS NULL
CREATE TABLE dbo.FORM_SUBMIT_LOG (
    id_log   INT IDENTITY(1,1) NOT NULL,
    url_slug VARCHAR(100) NOT NULL,
    ip       VARCHAR(45)  NOT NULL,
    waktu    DATETIME     NOT NULL CONSTRAINT DF_FSL_waktu DEFAULT (GETDATE()),
    CONSTRAINT PK_FORM_SUBMIT_LOG PRIMARY KEY (id_log)
);
GO


-- RENCANA sec.3.4: tabel agregat dashboard (diisi job terjadwal, bukan hitung langsung)
IF OBJECT_ID('dbo.RPT_FUNNEL_HARIAN') IS NULL
CREATE TABLE dbo.RPT_FUNNEL_HARIAN (
    tanggal       DATE        NOT NULL,
    id_req        INT         NOT NULL,
    id_flow       INT         NOT NULL,
    tipe_tahap    VARCHAR(20) NOT NULL,
    status_global VARCHAR(20) NOT NULL,
    jumlah        INT         NOT NULL,
    CONSTRAINT PK_RPT_FUNNEL PRIMARY KEY (tanggal, id_req, tipe_tahap, status_global),  -- persis RENCANA sec.3.4
    CONSTRAINT FK_RPT_req  FOREIGN KEY (id_req)  REFERENCES dbo.REQUISITIONS (id_req),
    CONSTRAINT FK_RPT_flow FOREIGN KEY (id_flow) REFERENCES dbo.M_FLOW (id_flow)
);
GO
