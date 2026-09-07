/* =========================================================================
   20260911_1400__seed_missing_stage_remarks.sql
   Menambahkan remark keputusan default untuk tahap yang belum memiliki remark
   (Sourcing, Form Pelamar, Interview User, Psikotes, Interview BOD).
   ========================================================================= */

-- 1. SOURCING (id_stage = 1)
IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'SRC_LOLOS')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (1, 'SRC_LOLOS', N'Lolos sourcing ke screening', 'LANJUT', 1, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'SRC_TIDAK_SESUAI')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (1, 'SRC_TIDAK_SESUAI', N'Profil tidak sesuai kriteria sourcing', 'TOLAK', 2, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'SRC_TALENT')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (1, 'SRC_TALENT', N'Simpan untuk talent pool posisi lain', 'TALENT_POOL', 3, 1);

-- 2. FORM PELAMAR (id_stage = 5)
IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'FRM_LENGKAP')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (5, 'FRM_LENGKAP', N'Formulir & berkas lengkap', 'LANJUT', 1, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'FRM_TIDAK_RESPON')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (5, 'FRM_TIDAK_RESPON', N'Tidak melengkapi formulir hingga batas waktu', 'WITHDRAWN', 2, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'FRM_DISKUALIFIKASI')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (5, 'FRM_DISKUALIFIKASI', N'Data formulir tidak valid / tidak sesuai', 'TOLAK', 3, 1);

-- 3. PSIKOTES (id_stage = 6)
IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'PSI_DISARANKAN')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (6, 'PSI_DISARANKAN', N'Hasil tes disarankan', 'LANJUT', 1, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'PSI_DIPERTIMBANGKAN')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (6, 'PSI_DIPERTIMBANGKAN', N'Hasil tes dipertimbangkan', 'ON_HOLD', 2, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'PSI_TIDAK_DISARANKAN')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (6, 'PSI_TIDAK_DISARANKAN', N'Hasil tes tidak disarankan', 'TOLAK', 3, 1);

-- 4. INTERVIEW USER (id_stage = 7)
IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'IUSR_LULUS')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (7, 'IUSR_LULUS', N'Lolos interview user', 'LANJUT', 1, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'IUSR_HOLD')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (7, 'IUSR_HOLD', N'Dipertimbangkan / bandingkan kandidat lain', 'ON_HOLD', 2, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'IUSR_TIDAK_LULUS')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (7, 'IUSR_TIDAK_LULUS', N'Tidak memenuhi ekspektasi user', 'TOLAK', 3, 1);

-- 5. INTERVIEW BOD (id_stage = 8)
IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'IBOD_LULUS')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (8, 'IBOD_LULUS', N'Disetujui BOD', 'LANJUT', 1, 1);

IF NOT EXISTS (SELECT 1 FROM dbo.M_REMARKS WHERE kode_remark = 'IBOD_TOLAK')
    INSERT INTO dbo.M_REMARKS (id_stage, kode_remark, label, efek_status, urutan, is_aktif)
    VALUES (8, 'IBOD_TOLAK', N'Tidak disetujui BOD', 'TOLAK', 2, 1);
GO
