<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model Auth_model -- Model Autentikasi Pengguna & Pengecekan Izin RBAC
 *
 * Fungsi:
 * - Mengeksekusi verifikasi kredensial login melalui Stored Procedure T-SQL sp_Login.
 * - Mengambil daftar permission aktif pengguna melalui Stored Procedure T-SQL sp_GetUserPermissions.
 * - Mengembalikan data snapshot profil pengguna untuk disimpan pada sesi login CodeIgniter.
 */
class Auth_model extends CI_Model
{
	/**
	 * @return array|null  baris user aktif (termasuk password_hash) atau null
	 */
	public function get_user_by_username($username)
	{
		$q = $this->db->query('EXEC dbo.sp_Login ?', array((string) $username));
		$row = $q->row_array();
		$q->free_result();
		return $row ? $row : NULL;
	}

	/**
	 * @return string[]  daftar kode permission efektif untuk user
	 */
	public function get_permissions($id_user)
	{
		$q = $this->db->query('EXEC dbo.sp_GetUserPermissions ?', array((int) $id_user));
		$codes = array();
		foreach ($q->result_array() as $r) {
			$codes[] = $r['kode'];
		}
		$q->free_result();
		return $codes;
	}
}
