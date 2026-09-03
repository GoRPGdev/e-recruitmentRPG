/* =========================================================================
   20260908_1030__requisition.sql
   FASE 1 -- Requisition & Posting.  Acuan: ERD Terkoreksi v1.2 sec.7.2
   + RENCANA_DEVELOPMENT.md sec.3.2.

   Prasyarat: 20260908_1000__master_referensi.sql sudah jalan
   (butuh M_USERS, M_POSISI, M_OUTLET, M_FLOW, M_CHANNEL).

   Konvensi lanjutan (selain K1-K12 di master_referensi):
   T1  text (ERD) -> VARCHAR(MAX).
   T3  [KONFIRMASI] urgensi, alasan_permintaan = VARCHAR tanpa CHECK
       (nilai belum dipatok di dokumen).
   T2b [KONFIRMASI] no_mpr nullable + UNIQUE -> filtered unique index
       WHERE no_mpr IS NOT NULL (Draft belum punya nomor; UNIQUE biasa
       anggap semua NULL sama -> hanya boleh 1 Draft).
   ========================================================================= */


IF OBJECT_ID('dbo.REQUISITIONS') IS NULL
CREATE TABLE dbo.REQUISITIONS (
    id_req                       INT IDENTITY(1,1) NOT NULL,
    no_mpr                       VARCHAR(30)  NULL,          -- MPR/2026/08/001; diisi sp_CreateRequisition; NULL saat Draft
    tanggal_pengajuan            DATETIME     NULL,
    id_user_pemohon              INT          NOT NULL,
    nama_pemohon_snapshot        NVARCHAR(150) NOT NULL,
    departemen_pemohon_snapshot  NVARCHAR(100) NULL,
    id_posisi                    INT          NOT NULL,
    tipe_penempatan              VARCHAR(10)  NOT NULL,      -- HQ / OUTLET
    id_outlet                    INT          NULL,          -- NULL jika HQ
    jumlah_dibutuhkan            INT          NOT NULL,
    jumlah_disetujui             INT          NULL,          -- bisa lebih kecil; NULL sebelum keputusan BOD
    jumlah_terpenuhi             INT          NOT NULL CONSTRAINT DF_REQ_terpenuhi DEFAULT (0),  -- dihitung dari Hired
    status_karyawan              VARCHAR(20)  NULL,          -- Tetap, Kontrak, Harian, Magang
    alasan_permintaan            VARCHAR(50)  NULL,          -- T3: dropdown, nilai belum dipatok
    nik_digantikan               VARCHAR(20)  NULL,          -- jika penggantian
    target_tanggal_join          DATE         NULL,
    urgensi                      VARCHAR(20)  NULL,          -- T3
    id_flow                      INT          NULL,          -- default dari posisi, bisa override
    butuh_psikotes               BIT          NOT NULL CONSTRAINT DF_REQ_psi DEFAULT (0),
    butuh_interview_bod          BIT          NOT NULL CONSTRAINT DF_REQ_bod DEFAULT (0),
    pendidikan_minimal           NVARCHAR(60) NULL,
    pengalaman_minimal_tahun     INT          NULL,
    job_desc                     VARCHAR(MAX) NULL,          -- T1
    kualifikasi                  VARCHAR(MAX) NULL,          -- T1
    range_gaji_min               DECIMAL(18,2) NULL,         -- SENSITIF (LIHAT_GAJI)
    range_gaji_max               DECIMAL(18,2) NULL,         -- SENSITIF
    preferensi_internal          VARCHAR(MAX) NULL,          -- T1; tidak pernah tampil publik
    status_req                   VARCHAR(25)  NOT NULL CONSTRAINT DF_REQ_status DEFAULT ('Draft'),
    created_at                   DATETIME     NOT NULL CONSTRAINT DF_REQ_created DEFAULT (GETDATE()),
    CONSTRAINT PK_REQUISITIONS PRIMARY KEY (id_req),
    CONSTRAINT FK_REQ_pemohon FOREIGN KEY (id_user_pemohon) REFERENCES dbo.M_USERS  (id_user),
    CONSTRAINT FK_REQ_posisi  FOREIGN KEY (id_posisi)       REFERENCES dbo.M_POSISI (id_posisi),
    CONSTRAINT FK_REQ_outlet  FOREIGN KEY (id_outlet)       REFERENCES dbo.M_OUTLET (id_outlet),
    CONSTRAINT FK_REQ_flow    FOREIGN KEY (id_flow)         REFERENCES dbo.M_FLOW   (id_flow),
    CONSTRAINT CK_REQ_penempatan CHECK (tipe_penempatan IN ('HQ','OUTLET')),
    CONSTRAINT CK_REQ_outlet_hq  CHECK (tipe_penempatan = 'HQ' OR id_outlet IS NOT NULL),
    CONSTRAINT CK_REQ_status CHECK (status_req IN
        ('Draft','Diajukan','Menunggu_BOD','Approved','Sourcing','Sourcing_Ulang',
         'Terpenuhi_Sebagian','Terpenuhi','Dibatalkan','Kadaluarsa')),
    CONSTRAINT CK_REQ_status_karyawan CHECK (status_karyawan IS NULL OR status_karyawan IN
        ('Tetap','Kontrak','Harian','Magang'))
);
GO

-- T2b: no_mpr unik hanya kalau terisi
IF OBJECT_ID('dbo.REQUISITIONS') IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_REQ_no_mpr' AND object_id = OBJECT_ID('dbo.REQUISITIONS'))
CREATE UNIQUE INDEX UX_REQ_no_mpr ON dbo.REQUISITIONS (no_mpr) WHERE no_mpr IS NOT NULL;
GO


IF OBJECT_ID('dbo.REQUISITION_APPROVALS') IS NULL
CREATE TABLE dbo.REQUISITION_APPROVALS (
    id_approval          INT IDENTITY(1,1) NOT NULL,
    id_req               INT          NOT NULL,
    putaran_ke           INT          NOT NULL,             -- revisi = putaran baru
    diajukan_ke_bod_pada DATE         NULL,
    keputusan            VARCHAR(20)  NOT NULL CONSTRAINT DF_RA_keputusan DEFAULT ('Pending'),
    jumlah_disetujui     INT          NULL,
    tanggal_keputusan    DATE         NULL,                 -- tanggal BOD menjawab
    disetujui_oleh       NVARCHAR(150) NULL,                -- nama BOD (string -- BOD bukan user sistem)
    catatan_bod          VARCHAR(MAX) NULL,                 -- T1
    lampiran_path        VARCHAR(400) NULL,                 -- screenshot WA, opsional
    diinput_oleh         INT          NOT NULL,
    diinput_pada         DATETIME     NOT NULL CONSTRAINT DF_RA_input DEFAULT (GETDATE()),
    CONSTRAINT PK_REQUISITION_APPROVALS PRIMARY KEY (id_approval),
    CONSTRAINT UQ_RA_req_putaran UNIQUE (id_req, putaran_ke),
    CONSTRAINT FK_RA_req    FOREIGN KEY (id_req)       REFERENCES dbo.REQUISITIONS (id_req),
    CONSTRAINT FK_RA_input  FOREIGN KEY (diinput_oleh) REFERENCES dbo.M_USERS (id_user),
    CONSTRAINT CK_RA_keputusan CHECK (keputusan IN ('Pending','Approved','Approved_Sebagian','Rejected'))
);
GO


IF OBJECT_ID('dbo.JOB_POSTINGS') IS NULL
CREATE TABLE dbo.JOB_POSTINGS (
    id_posting     INT IDENTITY(1,1) NOT NULL,
    id_req         INT          NOT NULL,
    id_channel     INT          NOT NULL,
    batch_ke       INT          NOT NULL CONSTRAINT DF_JP_batch DEFAULT (1),  -- 1 = awal, 2 = sourcing ulang
    judul_posting  NVARCHAR(200) NOT NULL,
    job_desc       VARCHAR(MAX) NULL,                       -- T1
    kualifikasi    VARCHAR(MAX) NULL,                       -- T1
    url_slug       VARCHAR(100) NOT NULL,                   -- link form publik
    url_eksternal  VARCHAR(400) NULL,                       -- link posting di portal
    tanggal_posting DATE        NULL,
    tanggal_tutup  DATE         NULL,
    is_aktif       BIT          NOT NULL CONSTRAINT DF_JP_aktif DEFAULT (1),  -- K1: ERD "is_active"
    -- RENCANA sec.3.2 (di-fold, bukan ALTER terpisah):
    form_aktif     BIT          NOT NULL CONSTRAINT DF_JP_form DEFAULT (1),
    form_dibuka    DATETIME     NULL,
    form_ditutup   DATETIME     NULL,
    jumlah_submit  INT          NOT NULL CONSTRAINT DF_JP_submit DEFAULT (0),
    CONSTRAINT PK_JOB_POSTINGS PRIMARY KEY (id_posting),
    CONSTRAINT UQ_JP_slug UNIQUE (url_slug),
    CONSTRAINT FK_JP_req     FOREIGN KEY (id_req)     REFERENCES dbo.REQUISITIONS (id_req),
    CONSTRAINT FK_JP_channel FOREIGN KEY (id_channel) REFERENCES dbo.M_CHANNEL (id_channel)
);
GO
-- Catatan: link "umum" walk-in/talent pool (RENCANA sec.3.2, slug tanpa id_posting)
-- BUKAN baris di sini -- ditangani mekanisme terpisah (tabel/route kecil) di Fase 3.


IF OBJECT_ID('dbo.JOB_POSTING_STATS') IS NULL
CREATE TABLE dbo.JOB_POSTING_STATS (
    id_posting           INT          NOT NULL,             -- 1:1 dengan JOB_POSTINGS
    jumlah_pelamar_masuk INT          NOT NULL CONSTRAINT DF_JPS_masuk DEFAULT (0),  -- input manual/agregat
    cv_sesuai            INT          NOT NULL CONSTRAINT DF_JPS_sesuai DEFAULT (0),
    cv_tidak_sesuai      INT          NOT NULL CONSTRAINT DF_JPS_tidak DEFAULT (0),
    diperbarui_pada      DATETIME     NULL,
    diperbarui_oleh      INT          NULL,
    CONSTRAINT PK_JOB_POSTING_STATS PRIMARY KEY (id_posting),
    CONSTRAINT FK_JPS_posting FOREIGN KEY (id_posting)      REFERENCES dbo.JOB_POSTINGS (id_posting),
    CONSTRAINT FK_JPS_user    FOREIGN KEY (diperbarui_oleh) REFERENCES dbo.M_USERS (id_user)
);
GO
