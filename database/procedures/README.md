# Stored Procedures

Beda dari migrasi: file di sini **boleh diedit** dan di-deploy ulang berkali-kali.
Tulis idempotent:

```sql
IF OBJECT_ID('dbo.sp_NamaProsedur') IS NOT NULL
    DROP PROCEDURE dbo.sp_NamaProsedur;
GO
CREATE PROCEDURE dbo.sp_NamaProsedur
    @param INT
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        BEGIN TRAN;
            -- ...
        COMMIT TRAN;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRAN;
        DECLARE @msg VARCHAR(2000); SET @msg = ERROR_MESSAGE();
        RAISERROR(@msg, 16, 1);   -- 2008 R2 tidak punya THROW
    END CATCH
END
GO
```

Deploy: `php tools/migrate.php proc`

Satu file satu prosedur. Nama file = nama prosedur, misal `sp_AdvanceStage.sql`.
