/* =========================================================================
   20260918_1000__unique_nik_karyawan.sql
   M_USERS.nik_karyawan -- jamin satu NIK cuma terhubung ke satu akun.

   Latar: rencana SSO token-bridge dari aplikasi Payroll RPG. Payroll
   mengirim NIK karyawan yang sedang login; e-recruitment mencocokkan
   ke M_USERS.nik_karyawan untuk menentukan siapa dia & role-nya di sini.
   Kalau satu NIK bisa dobel ke 2 baris, pencocokan jadi ambigu -- harus
   dicegah di level skema, bukan cuma di validasi PHP.

   Filtered unique index (bukan UNIQUE CONSTRAINT biasa): kolom ini
   "Opsional" (banyak user lama/demo tanpa NIK). Di SQL Server, UNIQUE
   CONSTRAINT biasa cuma mengizinkan SATU NULL total di seluruh tabel --
   filtered index WHERE nik_karyawan IS NOT NULL mengizinkan banyak NULL,
   tapi tetap menolak nilai NIK yang sama persis dipakai 2 baris.

   Idempotent. File koreksi -- migrasi lama tidak diedit.
   ========================================================================= */

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'UQ_M_USERS_nik_karyawan' AND object_id = OBJECT_ID('dbo.M_USERS')
)
    CREATE UNIQUE INDEX UQ_M_USERS_nik_karyawan
        ON dbo.M_USERS (nik_karyawan)
        WHERE nik_karyawan IS NOT NULL;
GO
