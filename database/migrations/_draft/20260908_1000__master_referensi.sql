/* =========================================================================
   20260908_1000__master_referensi.sql
   FASE 1 -- Tabel master & referensi.  Acuan: ERD Terkoreksi v1.2 sec.7.1
   + RENCANA_DEVELOPMENT.md sec.3.3.

   >>> DRAF. Belum di jalur migrasi (ada di _draft/). Review berdua dulu,
       lalu  git mv  ke database/migrations/  dan push.  Begitu di-push &
       dijalankan, file ini TIDAK PERNAH diedit lagi (bikin file koreksi).

   Batasan SQL Server 2008 R2 dipatuhi:
     - IDENTITY, bukan SEQUENCE
     - tidak ada tipe JSON / FORMAT / IIF / CONCAT / TRY_CONVERT
     - pemisah batch: GO (dipecah oleh tools/migrate.php)
     - tiap objek dijaga  IF OBJECT_ID(...) IS NULL  (aman kalau up diulang
       setelah gagal di tengah -- runner tidak rollback antar-batch)

   ---------------------------------------------------------------------------
   KEPUTUSAN DI DRAF INI
   ---------------------------------------------------------------------------
   K1  Kolom boolean diseragamkan  is_aktif  (ERD campur is_active/is_aktif).
       Terdampak: M_USERS, M_OUTLET, M_POSISI, M_FLOW (ERD tulis "is_active").
   K2  Kode/enum -> VARCHAR (ASCII).  Teks manusia -> NVARCHAR (UTF-16, aman
       apa pun collation DB).
   K3  [FINAL] Panjang string: mayoritas seperti tebakan; nama_posisi 150,
       M_REMARKS.label 200, nik_karyawan 20. NVARCHAR varlen -> plafon saja.
   K4  status_global TIDAK dibuat tabel -- 9 nilai dikunci di kode (CLAUDE.md).
   K5  is_aktif ditambahkan ke SEMUA tabel master, termasuk yang ERD tidak
       sebut (soft delete, CLAUDE.md aturan 4). Hard delete tidak dipakai.
   K6  CHECK untuk vocabulary tetap: M_STAGE.tipe_tahap (7),
       M_FLOW.tipe_penempatan (HQ/OUTLET), M_POSISI.level_posisi (6),
       M_DOKUMEN.kategori & tingkat_sensitif (3), M_REMARKS.efek_status (9).
   K7  [FINAL] M_REMARKS.efek_status = 9 nilai, CHECK dipasang di sini:
       LANJUT, TOLAK, ON_HOLD, UNREACHABLE, WITHDRAWN, OFFER_DECLINED,
       NO_SHOW, HIRED, TALENT_POOL.  "Unreachable" dipisah dari TOLAK karena
       reversible (ERD sec.8). SP memetakan efek_status -> status_global.
   K8  M_PERMISSIONS dapat kolom  nama  (ERD hanya tunjuk  kode) untuk label UI.
   K9  [FINAL] M_FLOW_STAGE.role_pic tetap VARCHAR(20) + FK ke
       M_ROLES(kode_role). Integritas tanpa kehilangan nilai terbaca.
   K10 [FINAL] M_FLOW.kode_flow UNIQUE. Bump  versi  = update baris di tempat.
       "Simpan sebagai template baru" = baris baru + kode_flow baru +
       id_flow_induk terisi. Tidak ada tabel histori versi -- lamaran
       di-snapshot ke APPLICATION_STAGES.
   K11 M_POSISI.default_flow NULLABLE -- posisi baru boleh belum punya flow.
   K12 [FINAL] M_FLOW_STAGE: UNIQUE(id_flow,id_stage) + UNIQUE(id_flow,urutan).
       Satu stage maksimal sekali per flow; variasi per-lamaran lewat is_sisipan.
   ========================================================================= */


/* ============================ RBAC ====================================== */

IF OBJECT_ID('dbo.M_ROLES') IS NULL
CREATE TABLE dbo.M_ROLES (
    id_role    INT IDENTITY(1,1) NOT NULL,
    kode_role  VARCHAR(20)   NOT NULL,   -- IT_ADMIN, HR_ADMIN, HR_SPV, USER_DEPT, BOD, VIEWER
    nama_role  NVARCHAR(60)  NOT NULL,
    is_aktif   BIT           NOT NULL CONSTRAINT DF_M_ROLES_aktif DEFAULT (1),
    CONSTRAINT PK_M_ROLES PRIMARY KEY (id_role),
    CONSTRAINT UQ_M_ROLES_kode UNIQUE (kode_role)
);
GO

IF OBJECT_ID('dbo.M_PERMISSIONS') IS NULL
CREATE TABLE dbo.M_PERMISSIONS (
    id_permission INT IDENTITY(1,1) NOT NULL,
    kode          VARCHAR(40)   NOT NULL,   -- LIHAT_CV, LIHAT_DOK_IDENTITAS, LIHAT_FINANSIAL,
                                            -- LIHAT_GAJI, LIHAT_GAJI_PELAMAR, APPROVE, EXPORT,
                                            -- EDIT_FLOW_TEMPLATE, ...
    nama          NVARCHAR(100) NOT NULL,   -- K8
    is_aktif      BIT           NOT NULL CONSTRAINT DF_M_PERM_aktif DEFAULT (1),
    CONSTRAINT PK_M_PERMISSIONS PRIMARY KEY (id_permission),
    CONSTRAINT UQ_M_PERMISSIONS_kode UNIQUE (kode)
);
GO

IF OBJECT_ID('dbo.M_ROLE_PERMISSIONS') IS NULL
CREATE TABLE dbo.M_ROLE_PERMISSIONS (
    id_role       INT NOT NULL,
    id_permission INT NOT NULL,
    CONSTRAINT PK_M_ROLE_PERMISSIONS PRIMARY KEY (id_role, id_permission),
    CONSTRAINT FK_M_ROLE_PERMISSIONS_role
        FOREIGN KEY (id_role)       REFERENCES dbo.M_ROLES (id_role),
    CONSTRAINT FK_M_ROLE_PERMISSIONS_perm
        FOREIGN KEY (id_permission) REFERENCES dbo.M_PERMISSIONS (id_permission)
);
GO


/* ============================ Organisasi =============================== */

IF OBJECT_ID('dbo.M_DEPARTEMEN') IS NULL
CREATE TABLE dbo.M_DEPARTEMEN (
    id_departemen INT IDENTITY(1,1) NOT NULL,
    kode          VARCHAR(20)   NOT NULL,
    nama          NVARCHAR(100) NOT NULL,
    is_aktif      BIT           NOT NULL CONSTRAINT DF_M_DEPT_aktif DEFAULT (1),
    CONSTRAINT PK_M_DEPARTEMEN PRIMARY KEY (id_departemen),
    CONSTRAINT UQ_M_DEPARTEMEN_kode UNIQUE (kode)
);
GO

IF OBJECT_ID('dbo.M_USERS') IS NULL
CREATE TABLE dbo.M_USERS (
    id_user             INT IDENTITY(1,1) NOT NULL,
    nik_karyawan        VARCHAR(20)   NULL,        -- K3: snapshot HRIS
    username            VARCHAR(50)   NOT NULL,
    password_hash       VARCHAR(255)  NOT NULL,    -- PHP password_hash(); 255 aman utk argon2id
    nama_snapshot       NVARCHAR(150) NOT NULL,
    departemen_snapshot NVARCHAR(100) NULL,
    snapshot_pada       DATETIME      NULL,
    id_role             INT           NOT NULL,
    is_aktif            BIT           NOT NULL CONSTRAINT DF_M_USERS_aktif DEFAULT (1),  -- K1: ERD "is_active"
    CONSTRAINT PK_M_USERS PRIMARY KEY (id_user),
    CONSTRAINT UQ_M_USERS_username UNIQUE (username),
    CONSTRAINT FK_M_USERS_role FOREIGN KEY (id_role) REFERENCES dbo.M_ROLES (id_role)
);
GO

IF OBJECT_ID('dbo.M_OUTLET') IS NULL
CREATE TABLE dbo.M_OUTLET (
    id_outlet   INT IDENTITY(1,1) NOT NULL,
    kode_outlet VARCHAR(20)   NOT NULL,   -- Z-MKTR, K-LC3, I-TCM
    nama_outlet NVARCHAR(120) NOT NULL,
    brand       NVARCHAR(60)  NULL,       -- Naughty, SOYU, Les Femmes
    region      NVARCHAR(60)  NULL,
    is_aktif    BIT           NOT NULL CONSTRAINT DF_M_OUTLET_aktif DEFAULT (1),  -- K1
    CONSTRAINT PK_M_OUTLET PRIMARY KEY (id_outlet),
    CONSTRAINT UQ_M_OUTLET_kode UNIQUE (kode_outlet)
);
GO


/* ============================ Flow template ============================ */

IF OBJECT_ID('dbo.M_STAGE') IS NULL
CREATE TABLE dbo.M_STAGE (
    id_stage    INT IDENTITY(1,1) NOT NULL,
    kode_stage  VARCHAR(30)   NOT NULL,
    nama_tahap  NVARCHAR(100) NOT NULL,
    tipe_tahap  VARCHAR(20)   NOT NULL,   -- sumbu report -- 7 nilai tetap (CLAUDE.md aturan 9)
    is_terminal BIT NOT NULL CONSTRAINT DF_M_STAGE_term  DEFAULT (0),  -- mencapai tahap ini bisa mengakhiri lamaran (mis. ONBOARD)
    is_sistem   BIT NOT NULL CONSTRAINT DF_M_STAGE_sis   DEFAULT (0),  -- RENCANA sec.3.3: tahap inti -- boleh nonaktif, TIDAK boleh hapus
    is_aktif    BIT NOT NULL CONSTRAINT DF_M_STAGE_aktif DEFAULT (1),  -- RENCANA sec.3.3: soft delete
    CONSTRAINT PK_M_STAGE PRIMARY KEY (id_stage),
    CONSTRAINT UQ_M_STAGE_kode UNIQUE (kode_stage),
    CONSTRAINT CK_M_STAGE_tipe CHECK (tipe_tahap IN
        ('SCREENING','KONTAK','FORM','TEST','INTERVIEW','OFFER','ONBOARD'))
);
GO

IF OBJECT_ID('dbo.M_FLOW') IS NULL
CREATE TABLE dbo.M_FLOW (
    id_flow           INT IDENTITY(1,1) NOT NULL,
    kode_flow         VARCHAR(30)   NOT NULL,   -- HQ_MANAGER, HQ_STAFF, HQ_STAFF_KRUSIAL, MP_OUTLET
    nama_flow         NVARCHAR(100) NOT NULL,
    tipe_penempatan   VARCHAR(10)   NOT NULL,   -- HQ / OUTLET
    maks_upaya_kontak INT           NOT NULL CONSTRAINT DF_M_FLOW_maks DEFAULT (3),  -- dibaca SP, bukan hardcode (CLAUDE.md aturan 6)
    sla_total_hari    INT           NULL,
    versi             INT           NOT NULL CONSTRAINT DF_M_FLOW_versi DEFAULT (1), -- RENCANA sec.3.3 (K10: counter in-place)
    id_flow_induk     INT           NULL,       -- RENCANA sec.3.3: asal "simpan sebagai template baru"
    is_aktif          BIT           NOT NULL CONSTRAINT DF_M_FLOW_aktif DEFAULT (1), -- K1: ERD "is_active"
    CONSTRAINT PK_M_FLOW PRIMARY KEY (id_flow),
    CONSTRAINT UQ_M_FLOW_kode UNIQUE (kode_flow),  -- K10
    CONSTRAINT CK_M_FLOW_penempatan CHECK (tipe_penempatan IN ('HQ','OUTLET')),
    CONSTRAINT FK_M_FLOW_induk FOREIGN KEY (id_flow_induk) REFERENCES dbo.M_FLOW (id_flow)
);
GO

IF OBJECT_ID('dbo.M_POSISI') IS NULL
CREATE TABLE dbo.M_POSISI (
    id_posisi     INT IDENTITY(1,1) NOT NULL,
    nama_posisi   NVARCHAR(150) NOT NULL,   -- K3
    id_departemen INT           NOT NULL,
    level_posisi  VARCHAR(20)   NOT NULL,   -- MP, Staff, Staff_Krusial, Spv, Manager, Senior_Manager
    default_flow  INT           NULL,       -- K11: nullable
    is_aktif      BIT           NOT NULL CONSTRAINT DF_M_POSISI_aktif DEFAULT (1),  -- K1
    CONSTRAINT PK_M_POSISI PRIMARY KEY (id_posisi),
    CONSTRAINT UQ_M_POSISI_nama UNIQUE (nama_posisi),
    CONSTRAINT FK_M_POSISI_departemen
        FOREIGN KEY (id_departemen) REFERENCES dbo.M_DEPARTEMEN (id_departemen),
    CONSTRAINT FK_M_POSISI_flow
        FOREIGN KEY (default_flow)  REFERENCES dbo.M_FLOW (id_flow),
    CONSTRAINT CK_M_POSISI_level CHECK (level_posisi IN
        ('MP','Staff','Staff_Krusial','Spv','Manager','Senior_Manager'))
);
GO

IF OBJECT_ID('dbo.M_FLOW_STAGE') IS NULL
CREATE TABLE dbo.M_FLOW_STAGE (
    id_flow_stage INT IDENTITY(1,1) NOT NULL,
    id_flow       INT NOT NULL,
    id_stage      INT NOT NULL,
    urutan        INT NOT NULL,
    is_wajib      BIT NOT NULL CONSTRAINT DF_M_FS_wajib DEFAULT (1),  -- 0 = tahap bisa dilewati
    sla_hari      INT NULL,
    role_pic      VARCHAR(20) NULL,   -- K9: kode_role yang menjalankan (FK ke M_ROLES.kode_role)
    CONSTRAINT PK_M_FLOW_STAGE PRIMARY KEY (id_flow_stage),
    CONSTRAINT UQ_M_FLOW_STAGE_flow_stage  UNIQUE (id_flow, id_stage),   -- K12
    CONSTRAINT UQ_M_FLOW_STAGE_flow_urutan UNIQUE (id_flow, urutan),     -- K12
    CONSTRAINT FK_M_FLOW_STAGE_flow  FOREIGN KEY (id_flow)  REFERENCES dbo.M_FLOW  (id_flow),
    CONSTRAINT FK_M_FLOW_STAGE_stage FOREIGN KEY (id_stage) REFERENCES dbo.M_STAGE (id_stage),
    CONSTRAINT FK_M_FLOW_STAGE_pic   FOREIGN KEY (role_pic) REFERENCES dbo.M_ROLES (kode_role)  -- K9
);
GO


/* ============================ Dokumen & pilihan ======================= */

IF OBJECT_ID('dbo.M_DOKUMEN') IS NULL
CREATE TABLE dbo.M_DOKUMEN (
    id_dokumen           INT IDENTITY(1,1) NOT NULL,
    nama_dokumen         NVARCHAR(80) NOT NULL,   -- KTP, KK, Ijazah, NPWP, Rekening
    kategori             VARCHAR(20)  NOT NULL,   -- IDENTITAS, PENDIDIKAN, FINANSIAL
    tingkat_sensitif     VARCHAR(20)  NOT NULL,   -- UMUM, IDENTITAS, FINANSIAL  (dasar RBAC dokumen)
    is_mandatory_default BIT NOT NULL CONSTRAINT DF_M_DOK_mand  DEFAULT (0),
    is_aktif             BIT NOT NULL CONSTRAINT DF_M_DOK_aktif DEFAULT (1),
    CONSTRAINT PK_M_DOKUMEN PRIMARY KEY (id_dokumen),
    CONSTRAINT UQ_M_DOKUMEN_nama UNIQUE (nama_dokumen),
    CONSTRAINT CK_M_DOKUMEN_kategori CHECK (kategori IN ('IDENTITAS','PENDIDIKAN','FINANSIAL')),
    CONSTRAINT CK_M_DOKUMEN_sensitif CHECK (tingkat_sensitif IN ('UMUM','IDENTITAS','FINANSIAL'))
);
GO

IF OBJECT_ID('dbo.M_FLOW_STAGE_DOKUMEN') IS NULL
CREATE TABLE dbo.M_FLOW_STAGE_DOKUMEN (
    id_flow_stage INT NOT NULL,
    id_dokumen    INT NOT NULL,
    is_wajib      BIT NOT NULL CONSTRAINT DF_M_FSD_wajib DEFAULT (1),  -- wajib sebelum tahap ini lulus
    CONSTRAINT PK_M_FLOW_STAGE_DOKUMEN PRIMARY KEY (id_flow_stage, id_dokumen),
    CONSTRAINT FK_M_FSD_flowstage FOREIGN KEY (id_flow_stage) REFERENCES dbo.M_FLOW_STAGE (id_flow_stage),
    CONSTRAINT FK_M_FSD_dokumen   FOREIGN KEY (id_dokumen)    REFERENCES dbo.M_DOKUMEN (id_dokumen)
);
GO

IF OBJECT_ID('dbo.M_REMARKS') IS NULL
CREATE TABLE dbo.M_REMARKS (
    id_remark   INT IDENTITY(1,1) NOT NULL,
    id_stage    INT           NOT NULL,   -- opsi muncul sesuai tahap
    kode_remark VARCHAR(40)   NOT NULL,
    label       NVARCHAR(200) NOT NULL,   -- K3
    efek_status VARCHAR(20)   NOT NULL,   -- K7: SP memetakan ke status_global
    urutan      INT           NOT NULL CONSTRAINT DF_M_REMARKS_urut  DEFAULT (0),  -- RENCANA sec.3.3
    is_aktif    BIT           NOT NULL CONSTRAINT DF_M_REMARKS_aktif DEFAULT (1),
    CONSTRAINT PK_M_REMARKS PRIMARY KEY (id_remark),
    CONSTRAINT UQ_M_REMARKS_kode UNIQUE (kode_remark),
    CONSTRAINT FK_M_REMARKS_stage FOREIGN KEY (id_stage) REFERENCES dbo.M_STAGE (id_stage),
    CONSTRAINT CK_M_REMARKS_efek CHECK (efek_status IN            -- K7
        ('LANJUT','TOLAK','ON_HOLD','UNREACHABLE','WITHDRAWN',
         'OFFER_DECLINED','NO_SHOW','HIRED','TALENT_POOL'))
);
GO

IF OBJECT_ID('dbo.M_CHANNEL') IS NULL
CREATE TABLE dbo.M_CHANNEL (
    id_channel   INT IDENTITY(1,1) NOT NULL,
    nama_channel NVARCHAR(60) NOT NULL,   -- JobStreet, Glints, Portal Sendiri, Referral, Walk-in
    is_eksternal BIT NOT NULL CONSTRAINT DF_M_CHANNEL_eks   DEFAULT (0),
    is_aktif     BIT NOT NULL CONSTRAINT DF_M_CHANNEL_aktif DEFAULT (1),
    CONSTRAINT PK_M_CHANNEL PRIMARY KEY (id_channel),
    CONSTRAINT UQ_M_CHANNEL_nama UNIQUE (nama_channel)
);
GO

/* -------------------------------------------------------------------------
   Selesai. Runner (tools/migrate.php) yang mencatat file ini ke
   dbo.SCHEMA_MIGRATIONS -- JANGAN INSERT manual di sini.
   ------------------------------------------------------------------------- */
