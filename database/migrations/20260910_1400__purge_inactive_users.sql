/* =========================================================================
   20260910_1400__purge_inactive_users.sql
   Hapus permanen user-user nonaktif (is_aktif = 0) dari dbo.M_USERS.
   Menangani referensi Foreign Key di tabel-tabel terkait:
   - Mereassign referensi FK (INTERVIEW_PARTICIPANTS, ACCESS_LOG_SENSITIF,
     APPLICATION_STAGES, APPLICATION_CONTACTS, APPLICATION_HISTORY,
     AUDIT_LOG, OFFERS, PSIKOTES_RESULTS) ke user aktif demo_super_admin.
   - Menghapus user nonaktif dari dbo.M_USERS secara permanen.
   ========================================================================= */

DECLARE @id_super_admin INT;
SELECT @id_super_admin = id_user FROM dbo.M_USERS WHERE username = 'demo_super_admin';

IF @id_super_admin IS NOT NULL
BEGIN
    -- 1. Reassign INTERVIEW_PARTICIPANTS (NOT NULL FK)
    -- Periksa agar tidak terjadi duplikasi (id_interview, id_user) bila sudah ada
    UPDATE ip
    SET ip.id_user = @id_super_admin
    FROM dbo.INTERVIEW_PARTICIPANTS ip
    JOIN dbo.M_USERS u ON u.id_user = ip.id_user
    WHERE u.is_aktif = 0
      AND NOT EXISTS (
          SELECT 1 FROM dbo.INTERVIEW_PARTICIPANTS ip2
          WHERE ip2.id_interview = ip.id_interview AND ip2.id_user = @id_super_admin
      );

    -- Hapus peserta duplikat jika ada interview yang sudah memiliki super_admin
    DELETE ip
    FROM dbo.INTERVIEW_PARTICIPANTS ip
    JOIN dbo.M_USERS u ON u.id_user = ip.id_user
    WHERE u.is_aktif = 0;

    -- 2. Reassign ACCESS_LOG_SENSITIF (NOT NULL FK)
    UPDATE als
    SET als.id_user = @id_super_admin
    FROM dbo.ACCESS_LOG_SENSITIF als
    JOIN dbo.M_USERS u ON u.id_user = als.id_user
    WHERE u.is_aktif = 0;

    -- 3. Reassign APPLICATION_STAGES.pic_user
    UPDATE aps
    SET aps.pic_user = @id_super_admin
    FROM dbo.APPLICATION_STAGES aps
    JOIN dbo.M_USERS u ON u.id_user = aps.pic_user
    WHERE u.is_aktif = 0;

    -- 4. Reassign APPLICATION_CONTACTS.oleh_user
    UPDATE ac
    SET ac.oleh_user = @id_super_admin
    FROM dbo.APPLICATION_CONTACTS ac
    JOIN dbo.M_USERS u ON u.id_user = ac.oleh_user
    WHERE u.is_aktif = 0;

    -- 5. Reassign APPLICATION_HISTORY.oleh_user
    UPDATE ah
    SET ah.oleh_user = @id_super_admin
    FROM dbo.APPLICATION_HISTORY ah
    JOIN dbo.M_USERS u ON u.id_user = ah.oleh_user
    WHERE u.is_aktif = 0;

    -- 6. Reassign AUDIT_LOG.oleh_user
    UPDATE al
    SET al.oleh_user = @id_super_admin
    FROM dbo.AUDIT_LOG al
    JOIN dbo.M_USERS u ON u.id_user = al.oleh_user
    WHERE u.is_aktif = 0;

    -- 7. Reassign OFFERS.dibuat_oleh
    UPDATE o
    SET o.dibuat_oleh = @id_super_admin
    FROM dbo.OFFERS o
    JOIN dbo.M_USERS u ON u.id_user = o.dibuat_oleh
    WHERE u.is_aktif = 0;

    -- 8. Reassign PSIKOTES_RESULTS.dilakukan_oleh
    UPDATE pr
    SET pr.dilakukan_oleh = @id_super_admin
    FROM dbo.PSIKOTES_RESULTS pr
    JOIN dbo.M_USERS u ON u.id_user = pr.dilakukan_oleh
    WHERE u.is_aktif = 0;

    -- 9. Reassign REQUISITIONS.id_user_pemohon (jika ada)
    UPDATE r
    SET r.id_user_pemohon = @id_super_admin
    FROM dbo.REQUISITIONS r
    JOIN dbo.M_USERS u ON u.id_user = r.id_user_pemohon
    WHERE u.is_aktif = 0;

    -- 10. Reassign REQUISITION_APPROVALS.diinput_oleh (jika ada)
    UPDATE ra
    SET ra.diinput_oleh = @id_super_admin
    FROM dbo.REQUISITION_APPROVALS ra
    JOIN dbo.M_USERS u ON u.id_user = ra.diinput_oleh
    WHERE u.is_aktif = 0;

    -- 11. Hapus permanen seluruh user nonaktif dari dbo.M_USERS
    DELETE FROM dbo.M_USERS
    WHERE is_aktif = 0;
END
GO
