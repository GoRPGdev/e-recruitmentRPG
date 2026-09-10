/* =========================================================================
   20260912_1400__kelengkapan_evaluasi_dan_informasi_umum.sql
   E-Recruitment Ratu Pertiwi Group (RPG)
   Menambahkan butir kelengkapan evaluasi diri (Minat & Konsep Pribadi)
   serta butir Informasi Umum & Kesiapan Operasional sesuai berkas fisik
   FORM PELAMAR_RPG (K).pdf:
   1. alasan_cocok_posisi (Mengapa merasa cocok untuk posisi yang dilamar)
   2. rencana_karir_5thn (Rencana dan sasaran karir dalam 3-5 tahun ke depan)
   3. masalah_tersulit_solusi (Masalah kerja paling sulit & bagaimana menyelesaikannya)
   4. lingkungan_kerja_idaman (Lingkungan kerja yang disukai / dihindari)
   5. riwayat_melamar_rpg (Riwayat pernah melamar di RPG sebelumnya)
   6. kepemilikan_kendaraan (Kendaraan pribadi / SIM untuk operasional)
   ========================================================================= */

-- Pastikan tabel CANDIDATE_QUESTIONNAIRE sudah ada sebelum menambah kolom
IF OBJECT_ID('dbo.CANDIDATE_QUESTIONNAIRE') IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATE_QUESTIONNAIRE') AND name = 'alasan_cocok_posisi')
        EXEC('ALTER TABLE dbo.CANDIDATE_QUESTIONNAIRE ADD alasan_cocok_posisi NVARCHAR(MAX) NULL');

    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATE_QUESTIONNAIRE') AND name = 'rencana_karir_5thn')
        EXEC('ALTER TABLE dbo.CANDIDATE_QUESTIONNAIRE ADD rencana_karir_5thn NVARCHAR(MAX) NULL');

    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATE_QUESTIONNAIRE') AND name = 'masalah_tersulit_solusi')
        EXEC('ALTER TABLE dbo.CANDIDATE_QUESTIONNAIRE ADD masalah_tersulit_solusi NVARCHAR(MAX) NULL');

    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATE_QUESTIONNAIRE') AND name = 'lingkungan_kerja_idaman')
        EXEC('ALTER TABLE dbo.CANDIDATE_QUESTIONNAIRE ADD lingkungan_kerja_idaman NVARCHAR(MAX) NULL');

    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATE_QUESTIONNAIRE') AND name = 'riwayat_melamar_rpg')
        EXEC('ALTER TABLE dbo.CANDIDATE_QUESTIONNAIRE ADD riwayat_melamar_rpg NVARCHAR(500) NULL');

    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATE_QUESTIONNAIRE') AND name = 'kepemilikan_kendaraan')
        EXEC('ALTER TABLE dbo.CANDIDATE_QUESTIONNAIRE ADD kepemilikan_kendaraan NVARCHAR(255) NULL');
END
GO

