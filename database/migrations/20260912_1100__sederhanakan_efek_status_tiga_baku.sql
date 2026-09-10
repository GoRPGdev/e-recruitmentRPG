/* =========================================================================
   20260912_1100__sederhanakan_efek_status_tiga_baku.sql
   Penyederhanaan Efek Status Baku Menjadi 3 Kondisi:
     1. LANJUT (Lulus tahap ini, In_Progress ke tahap selanjutnya)
     2. HIRED  (Lulus tahap akhir, Hired & penuhi kuota MPR)
     3. TOLAK  (Tidak Lulus, Ditolak / Gugur)

   Data efek lama di M_REMARKS diselaraskan ke 3 efek baku:
     - ON_HOLD, UNREACHABLE                       -> LANJUT (masih proses)
     - WITHDRAWN, OFFER_DECLINED, NO_SHOW, TALENT_POOL -> TOLAK (gugur)

   Membersihkan M_EFEK_STATUS dan memperbarui Constraint CK_M_REMARKS_efek.
   Kompatibel dengan SQL Server 2008 R2.
   ========================================================================= */

-- 1. Selaraskan data M_REMARKS yang masih menggunakan efek status non-standar
UPDATE dbo.M_REMARKS
SET efek_status = 'LANJUT'
WHERE efek_status IN ('ON_HOLD', 'UNREACHABLE');

UPDATE dbo.M_REMARKS
SET efek_status = 'TOLAK'
WHERE efek_status IN ('WITHDRAWN', 'OFFER_DECLINED', 'NO_SHOW', 'TALENT_POOL');
GO

-- 2. Perbarui Constraint CHECK pada tabel dbo.M_REMARKS
IF EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_M_REMARKS_efek')
BEGIN
    ALTER TABLE dbo.M_REMARKS DROP CONSTRAINT CK_M_REMARKS_efek;
END
GO

ALTER TABLE dbo.M_REMARKS ADD CONSTRAINT CK_M_REMARKS_efek
CHECK (efek_status IN ('LANJUT', 'HIRED', 'TOLAK'));
GO

-- 3. Bersihkan tabel master dbo.M_EFEK_STATUS (Hapus yang 6 efek lama dan pertahankan 3 baku)
IF OBJECT_ID('dbo.M_EFEK_STATUS') IS NOT NULL
BEGIN
    -- Hapus efek status yang bukan bagian dari 3 baku
    DELETE FROM dbo.M_EFEK_STATUS
    WHERE kode_efek NOT IN ('LANJUT', 'HIRED', 'TOLAK');

    -- Pastikan 3 efek baku terdaftar rapi dengan urutan yang tepat
    IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'LANJUT')
        INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
        VALUES ('LANJUT', N'Lolos / Lanjut Tahap', 'Lulus', 'In_Progress', N'Kandidat lolos pada tahap ini dan melanjutkan ke tahap berikutnya.', 1, 1);
    ELSE
        UPDATE dbo.M_EFEK_STATUS
        SET nama_efek = N'Lolos / Lanjut Tahap',
            status_tahap = 'Lulus',
            status_global_target = 'In_Progress',
            deskripsi = N'Kandidat lolos pada tahap ini dan melanjutkan ke tahap berikutnya.',
            urutan = 1,
            is_aktif = 1
        WHERE kode_efek = 'LANJUT';

    IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'HIRED')
        INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
        VALUES ('HIRED', N'Diterima (Hired)', 'Lulus', 'Hired', N'Kandidat diterima bekerja dan kuota pemenuhan MPR terhitung bertambah.', 2, 1);
    ELSE
        UPDATE dbo.M_EFEK_STATUS
        SET nama_efek = N'Diterima (Hired)',
            status_tahap = 'Lulus',
            status_global_target = 'Hired',
            deskripsi = N'Kandidat diterima bekerja dan kuota pemenuhan MPR terhitung bertambah.',
            urutan = 2,
            is_aktif = 1
        WHERE kode_efek = 'HIRED';

    IF NOT EXISTS (SELECT 1 FROM dbo.M_EFEK_STATUS WHERE kode_efek = 'TOLAK')
        INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
        VALUES ('TOLAK', N'Ditolak / Gugur', 'Tidak_Lulus', 'Rejected', N'Kandidat tidak lolos / gugur pada proses seleksi.', 3, 1);
    ELSE
        UPDATE dbo.M_EFEK_STATUS
        SET nama_efek = N'Ditolak / Gugur',
            status_tahap = 'Tidak_Lulus',
            status_global_target = 'Rejected',
            deskripsi = N'Kandidat tidak lolos / gugur pada proses seleksi.',
            urutan = 3,
            is_aktif = 1
        WHERE kode_efek = 'TOLAK';
END
GO
