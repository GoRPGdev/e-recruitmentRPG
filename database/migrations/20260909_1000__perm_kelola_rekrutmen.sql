/* =========================================================================
   20260909_1000__perm_kelola_rekrutmen.sql
   Permission operasional intake: KELOLA_REKRUTMEN.

   Menutupi: kelola link form publik (aktif/nonaktif, tanggal buka-tutup),
   token berkas personal (FORM_TOKENS), entry manual, import file portal.
   Beda dari EDIT_FLOW_TEMPLATE (struktur flow, IT Admin) dan LIHAT_* (akses data).

   Grant awal: HR_ADMIN, HR_SPV. File koreksi -- migrasi lama tidak diedit.
   ========================================================================= */

INSERT INTO dbo.M_PERMISSIONS (kode, nama)
SELECT 'KELOLA_REKRUTMEN', N'Kelola link form, token berkas, entry manual & import'
WHERE NOT EXISTS (SELECT 1 FROM dbo.M_PERMISSIONS WHERE kode = 'KELOLA_REKRUTMEN');
GO

INSERT INTO dbo.M_ROLE_PERMISSIONS (id_role, id_permission)
SELECT r.id_role, p.id_permission
FROM (VALUES ('HR_ADMIN'), ('HR_SPV')) v(role)
JOIN dbo.M_ROLES       r ON r.kode_role = v.role
JOIN dbo.M_PERMISSIONS p ON p.kode = 'KELOLA_REKRUTMEN'
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.M_ROLE_PERMISSIONS rp
    WHERE rp.id_role = r.id_role AND rp.id_permission = p.id_permission
);
GO
