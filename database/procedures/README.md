# Stored Procedures

Beda dari migrasi: file di sini **boleh diedit** dan di-deploy ulang berkali-kali.

Deploy: `php tools/migrate.php proc`
Satu file satu objek. Nama file = nama objek (`sp_AdvanceStage.sql`, `fn_NormalisasiWA.sql`).

## Pola wajib SP transaksional — savepoint

SP yang menulis harus **aman dipanggil sendiri ATAU dari dalam transaksi caller**
(loop import memanggil `sp_SubmitApplication` per baris di satu transaksi —
satu baris gagal tidak boleh me-rollback seluruh batch).

```sql
IF OBJECT_ID('dbo.sp_Nama') IS NOT NULL DROP PROCEDURE dbo.sp_Nama;
GO
CREATE PROCEDURE dbo.sp_Nama
    @param INT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @outer INT = @@TRANCOUNT;

    BEGIN TRY
        IF @outer = 0 BEGIN TRANSACTION;
        ELSE SAVE TRANSACTION Nama;          -- nama savepoint <= 32 char, tanpa spasi

        -- ... kerja; RAISERROR(msg, 16, 1) untuk membatalkan (2008 R2 tak punya THROW)

        IF @outer = 0 COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        DECLARE @msg VARCHAR(2000) = ERROR_MESSAGE();
        IF @outer = 0
        BEGIN
            IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;   -- kita yang mulai -> rollback penuh
        END
        ELSE IF XACT_STATE() = 1
            ROLLBACK TRANSACTION Nama;                 -- dipanggil bersarang -> batalkan bagian kita saja
        -- XACT_STATE() = -1 (transaksi doomed): biarkan caller yang rollback penuh
        RAISERROR(@msg, 16, 1);
    END CATCH
END
GO
```

- **Jangan** `ROLLBACK TRAN` polos di CATCH — itu membunuh transaksi caller.
- SP read-only (`sp_Login`, `sp_GetUserPermissions`) cukup `SET NOCOUNT ON` + `SELECT`, tanpa transaksi.
- Idempotent untuk objek non-transaksional: `IF OBJECT_ID(...) IS NOT NULL DROP ...` lalu `CREATE`.

## QUOTED_IDENTIFIER

Tabel dengan **filtered index** (`REQUISITIONS.no_mpr`, `CANDIDATES.no_wa_normal`)
menolak INSERT/UPDATE kalau sesi `SET QUOTED_IDENTIFIER OFF`.
- PHP `sqlsrv` & SSMS: ON (default) — aman.
- `sqlcmd` / `bcp`: OFF (default) — tambahkan `SET QUOTED_IDENTIFIER ON;` di skrip.
- SP menangkap setелан QUOTED_IDENTIFIER saat `CREATE` dan memaksanya saat `EXEC`,
  jadi deploy SP **harus** lewat `migrate.php proc` (sqlsrv, ON), bukan sqlcmd.

## Daftar SP saat ini

| Objek | Modul | Catatan |
|---|---|---|
| `sp_Login` | Fondasi | read-only; PHP verifikasi `password_hash` |
| `sp_GetUserPermissions` | Fondasi | read-only; hasil disimpan di session |
| `fn_NormalisasiWA` | Intake | scalar UDF; `0812…`/`+62…`/`62 …` → `62812…` |
| `sp_GenerateApplicationStages` | Flow Engine | snapshot `M_FLOW_STAGE` → `APPLICATION_STAGES` (idempotent) |
| `sp_SubmitApplication` | Intake | dedupe WA→email→hash CV, snapshot flow, buat lamaran + tahap |
