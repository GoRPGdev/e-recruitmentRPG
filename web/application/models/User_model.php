<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User_model -- Manajemen data pengguna (dbo.M_USERS).
 * Sesuai arsitektur SQL Server 2008 R2:
 * - Parameter binding pada semua query
 * - Paginasi ROW_NUMBER() OVER (...) di CTE
 * - Soft delete (toggle is_aktif), tanpa DELETE fisik
 */
class User_model extends CI_Model
{
	/* ---- Helper eksekusi SP dengan error checking dan parameter binding ---- */
	private function _sp($sql, $params = array())
	{
		$stmt = sqlsrv_query($this->db->conn_id, $sql, $params);
		if ($stmt === FALSE) {
			$e = sqlsrv_errors();
			$last = $e ? end($e) : NULL;
			throw new RuntimeException($last ? trim($last['message']) : 'Stored Procedure gagal dijalankan.');
		}
		do { } while (sqlsrv_next_result($stmt));
		sqlsrv_free_stmt($stmt);
	}

	public function list_users($offset = 0, $per = 25, $filter = array())
	{
		$b = array();
		$where = array('1=1');

		if (!empty($filter['q'])) {
			$where[] = "(u.username LIKE ? OR u.nama_snapshot LIKE ? OR u.nik_karyawan LIKE ?)";
			$term = '%' . $filter['q'] . '%';
			$b[] = $term; $b[] = $term; $b[] = $term;
		}

		if (!empty($filter['role'])) {
			$where[] = "r.kode_role = ?";
			$b[] = (string) $filter['role'];
		}

		if (isset($filter['is_aktif']) && $filter['is_aktif'] !== '') {
			$where[] = "u.is_aktif = ?";
			$b[] = (int) $filter['is_aktif'];
		}

		$whereSql = "WHERE " . implode(' AND ', $where);

		$b[] = (int) $offset;
		$b[] = (int) $offset + (int) $per - 1;

		$sql = "WITH q AS (
		            SELECT u.id_user, u.username, u.nik_karyawan, u.nama_snapshot,
		                   u.departemen_snapshot, u.id_role, u.id_departemen, u.is_aktif,
		                   r.kode_role, r.nama_role, d.nama AS nama_dept,
		                   ROW_NUMBER() OVER (ORDER BY u.is_aktif DESC, u.id_user DESC) AS rn
		            FROM dbo.M_USERS u
		            JOIN dbo.M_ROLES r ON r.id_role = u.id_role
		            LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = u.id_departemen
		            $whereSql
		        )
		        SELECT * FROM q WHERE rn BETWEEN ? AND ? ORDER BY rn";

		$res = $this->db->query($sql, $b);
		$rows = $res->result_array();
		$res->free_result();
		return $rows;
	}

	public function count_users($filter = array())
	{
		$b = array();
		$where = array('1=1');

		if (!empty($filter['q'])) {
			$where[] = "(u.username LIKE ? OR u.nama_snapshot LIKE ? OR u.nik_karyawan LIKE ?)";
			$term = '%' . $filter['q'] . '%';
			$b[] = $term; $b[] = $term; $b[] = $term;
		}

		if (!empty($filter['role'])) {
			$where[] = "r.kode_role = ?";
			$b[] = (string) $filter['role'];
		}

		if (isset($filter['is_aktif']) && $filter['is_aktif'] !== '') {
			$where[] = "u.is_aktif = ?";
			$b[] = (int) $filter['is_aktif'];
		}

		$whereSql = "WHERE " . implode(' AND ', $where);

		$sql = "SELECT COUNT(*) AS total
		        FROM dbo.M_USERS u
		        JOIN dbo.M_ROLES r ON r.id_role = u.id_role
		        $whereSql";

		$res = $this->db->query($sql, $b);
		$row = $res->row_array();
		$res->free_result();
		return $row ? (int) $row['total'] : 0;
	}

	public function get_user($id_user)
	{
		$sql = "SELECT u.id_user, u.username, u.nik_karyawan, u.nama_snapshot,
		               u.departemen_snapshot, u.id_role, u.id_departemen, u.is_aktif,
		               r.kode_role, r.nama_role, d.nama AS nama_dept
		        FROM dbo.M_USERS u
		        JOIN dbo.M_ROLES r ON r.id_role = u.id_role
		        LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = u.id_departemen
		        WHERE u.id_user = ?";
		$res = $this->db->query($sql, array((int) $id_user));
		$row = $res->row_array();
		$res->free_result();
		return $row ? $row : null;
	}

	public function get_roles($only_active = true)
	{
		$where = $only_active ? 'WHERE is_aktif = 1' : '';
		$sql = "SELECT id_role, kode_role, nama_role, is_aktif FROM dbo.M_ROLES $where ORDER BY id_role ASC";
		$res = $this->db->query($sql);
		$rows = $res->result_array();
		$res->free_result();
		return $rows;
	}

	public function get_departemen()
	{
		$sql = "SELECT id_departemen, kode, nama FROM dbo.M_DEPARTEMEN WHERE is_aktif = 1 ORDER BY nama ASC";
		$res = $this->db->query($sql);
		$rows = $res->result_array();
		$res->free_result();
		return $rows;
	}

	public function create_user(array $data, $oleh_user = NULL)
	{
		$pwdHash = password_hash($data['password'], PASSWORD_DEFAULT);
		$id_out = 0;
		$params = array(
			NULL, // @id_user NULL -> INSERT
			(string) $data['username'],
			$pwdHash,
			(string) $data['nama_snapshot'],
			!empty($data['nik_karyawan']) ? (string) $data['nik_karyawan'] : null,
			!empty($data['departemen_snapshot']) ? (string) $data['departemen_snapshot'] : null,
			(int) $data['id_role'],
			!empty($data['id_departemen']) ? (int) $data['id_departemen'] : null,
			isset($data['is_aktif']) ? (int) $data['is_aktif'] : 1,
			$oleh_user ? (int) $oleh_user : null,
			array(&$id_out, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
		);

		$this->_sp('{CALL dbo.sp_SaveUser(?,?,?,?,?,?,?,?,?,?,?)}', $params);
		return (int) $id_out;
	}

	public function update_user($id_user, array $data, $oleh_user = NULL)
	{
		$pwdHash = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
		$id_out = (int) $id_user;
		$params = array(
			(int) $id_user,
			(string) $data['username'],
			$pwdHash,
			(string) $data['nama_snapshot'],
			!empty($data['nik_karyawan']) ? (string) $data['nik_karyawan'] : null,
			!empty($data['departemen_snapshot']) ? (string) $data['departemen_snapshot'] : null,
			(int) $data['id_role'],
			!empty($data['id_departemen']) ? (int) $data['id_departemen'] : null,
			isset($data['is_aktif']) ? (int) $data['is_aktif'] : 1,
			$oleh_user ? (int) $oleh_user : null,
			array(&$id_out, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
		);

		$this->_sp('{CALL dbo.sp_SaveUser(?,?,?,?,?,?,?,?,?,?,?)}', $params);
		return (int) $id_out;
	}

	public function toggle_status($id_user, $is_aktif, $oleh_user = NULL)
	{
		$user = $this->get_user($id_user);
		if (! $user) {
			throw new RuntimeException('Pengguna tidak ditemukan.');
		}

		$id_out = (int) $id_user;
		$params = array(
			(int) $id_user,
			(string) $user['username'],
			null, // pertahankan password
			(string) $user['nama_snapshot'],
			!empty($user['nik_karyawan']) ? (string) $user['nik_karyawan'] : null,
			!empty($user['departemen_snapshot']) ? (string) $user['departemen_snapshot'] : null,
			(int) $user['id_role'],
			!empty($user['id_departemen']) ? (int) $user['id_departemen'] : null,
			(int) $is_aktif,
			$oleh_user ? (int) $oleh_user : null,
			array(&$id_out, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
		);

		$this->_sp('{CALL dbo.sp_SaveUser(?,?,?,?,?,?,?,?,?,?,?)}', $params);
		return true;
	}

	/**
	 * Soft delete user (is_aktif = 0) via Stored Procedure dbo.sp_DeleteUser.
	 * Sesuai arsitektur Database-First RPG (CLAUDE.md aturan 4: soft delete).
	 */
	public function delete_user($id_user, $oleh_user = NULL)
	{
		$params = array(
			(int) $id_user,
			$oleh_user ? (int) $oleh_user : null
		);
		$this->_sp('{CALL dbo.sp_DeleteUser(?,?)}', $params);
		return true;
	}
}
