<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth_model -- membungkus sp_Login & sp_GetUserPermissions.
 *
 * CATATAN parameter binding:
 * CI3 driver sqlsrv menjalankan  ?  sebagai substitusi ter-escape (query()
 * -> compile_binds), bukan prepared statement murni. Aman dari injeksi untuk
 * kasus baca seperti ini. Untuk SP transaksional dengan OUTPUT param
 * (sp_AdvanceStage dkk, Fase 3) pakai sqlsrv_query() langsung -- lihat
 * tools/test-koneksi.php bagian 7.
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
