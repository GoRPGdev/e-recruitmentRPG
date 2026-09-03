/* =========================================================================
   fn_NormalisasiWA  --  normalisasi nomor WhatsApp ke bentuk 62xxxxxxxxxx
   E-Recruitment RPG

   Aturan (RENCANA_DEVELOPMENT.md sec.9.3):
     0812...      -> 62812...
     62 812...    -> 62812...      (spasi / tanda baca dibuang)
     +62812...    -> 62812...
     812...       -> 62812...
   NULL / tanpa digit -> NULL.

   Scalar UDF -- dipakai sp_SubmitApplication untuk dedupe. Deterministik.
   2008 R2: tanpa STRING_SPLIT / REPLACE massal -- ambil digit satu per satu.

   Deploy:  php tools/migrate.php proc
   ========================================================================= */
IF OBJECT_ID('dbo.fn_NormalisasiWA') IS NOT NULL
    DROP FUNCTION dbo.fn_NormalisasiWA;
GO

CREATE FUNCTION dbo.fn_NormalisasiWA (@raw VARCHAR(40))
RETURNS VARCHAR(20)
WITH SCHEMABINDING
AS
BEGIN
    IF @raw IS NULL
        RETURN NULL;

    DECLARE @d  VARCHAR(40) = '';
    DECLARE @i  INT = 1;
    DECLARE @n  INT = LEN(@raw + 'x') - 1;   -- LEN aman terhadap trailing space
    DECLARE @c  CHAR(1);

    WHILE @i <= @n
    BEGIN
        SET @c = SUBSTRING(@raw, @i, 1);
        IF @c LIKE '[0-9]'
            SET @d = @d + @c;
        SET @i = @i + 1;
    END

    IF @d = ''
        RETURN NULL;

    IF LEFT(@d, 2) = '62'
        SET @d = @d;
    ELSE IF LEFT(@d, 1) = '0'
        SET @d = '62' + SUBSTRING(@d, 2, 40);
    ELSE IF LEFT(@d, 1) = '8'
        SET @d = '62' + @d;
    -- bentuk lain (mis. nomor luar negeri) dibiarkan apa adanya

    RETURN LEFT(@d, 20);
END
GO
