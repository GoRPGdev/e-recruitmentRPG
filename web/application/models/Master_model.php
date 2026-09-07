<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Master_model -- CRUD master data + Flow Builder.
 * Posisi & flow lewat SP (logika + FK). Master flat (departemen/outlet/
 * dokumen) lewat query berparameter (tabel lookup, tanpa transaksi).
 */
class Master_model extends CI_Model
{
	/* ---- helper SP dengan OUTPUT ---- */
	private function _sp($sql, $params)
	{
		$stmt = sqlsrv_query($this->db->conn_id, $sql, $params);
		if ($stmt === FALSE) {
			$e = sqlsrv_errors(); $last = $e ? end($e) : NULL;
			throw new RuntimeException($last ? trim($last['message']) : 'SP gagal.');
		}
		do { } while (sqlsrv_next_result($stmt));
		sqlsrv_free_stmt($stmt);
	}

	/* ================= POSISI (SP) ================================= */

	public function list_posisi()
	{
		$q = $this->db->query(
			'SELECT p.id_posisi, p.nama_posisi, p.id_departemen, p.level_posisi, p.default_flow,
			        p.job_desc, p.kualifikasi, p.pendidikan_minimal, p.pengalaman_minimal_tahun, p.is_aktif,
			        d.nama AS departemen, f.kode_flow
			 FROM dbo.M_POSISI p
			 JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
			 LEFT JOIN dbo.M_FLOW f  ON f.id_flow = p.default_flow
			 ORDER BY p.is_aktif DESC, p.nama_posisi');
		return $q->result_array();
	}

	public function save_posisi($in, $oleh_user = NULL)
	{
		$id = 0;
		$id_pos = ! empty($in['id_posisi']) ? (int) $in['id_posisi'] : (! empty($in['id']) ? (int) $in['id'] : NULL);
		$this->_sp('{CALL dbo.sp_SavePosisi(?,?,?,?,?,?,?,?,?,?,?)}', array(
			$id_pos,
			(string) $in['nama_posisi'],
			(int) $in['id_departemen'],
			(string) $in['level_posisi'],
			! empty($in['default_flow']) ? (int) $in['default_flow'] : NULL,
			! empty($in['job_desc']) ? (string) $in['job_desc'] : NULL,
			! empty($in['kualifikasi']) ? (string) $in['kualifikasi'] : NULL,
			! empty($in['pendidikan_minimal']) ? (string) $in['pendidikan_minimal'] : NULL,
			isset($in['pengalaman_minimal_tahun']) && $in['pengalaman_minimal_tahun'] !== '' ? (int) $in['pengalaman_minimal_tahun'] : NULL,
			$oleh_user ? (int) $oleh_user : NULL,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id;
	}

	public function toggle_posisi($id_posisi, $is_aktif, $oleh_user = NULL)
	{
		$this->_sp('{CALL dbo.sp_TogglePosisi(?,?,?)}', array(
			(int) $id_posisi, $is_aktif ? 1 : 0, $oleh_user ? (int) $oleh_user : NULL,
		));
	}

	/* ================= TAHAP SELEKSI (M_STAGE) ===================== */

	public function list_stage()
	{
		return $this->db->query(
			'SELECT s.id_stage, s.kode_stage, s.nama_tahap, s.tipe_tahap,
			        s.is_terminal, s.is_sistem, s.is_aktif,
			        (SELECT COUNT(*) FROM dbo.M_FLOW_STAGE fs WHERE fs.id_stage = s.id_stage) AS n_flow,
			        (SELECT COUNT(*) FROM dbo.M_REMARKS r WHERE r.id_stage = s.id_stage) AS n_remark
			 FROM dbo.M_STAGE s
			 ORDER BY s.is_aktif DESC, s.nama_tahap'
		)->result_array();
	}

	public function get_stage($id_stage)
	{
		return $this->db->query('SELECT * FROM dbo.M_STAGE WHERE id_stage = ?', array((int) $id_stage))->row_array();
	}

	public function save_stage($in, $oleh_user = NULL)
	{
		$id = 0;
		$id_st = ! empty($in['id_stage']) ? (int) $in['id_stage'] : (! empty($in['id']) ? (int) $in['id'] : NULL);
		$this->_sp('{CALL dbo.sp_SaveStage(?,?,?,?,?,?,?)}', array(
			$id_st,
			(string) $in['kode_stage'],
			(string) $in['nama_tahap'],
			(string) $in['tipe_tahap'],
			! empty($in['is_terminal']) ? 1 : 0,
			$oleh_user ? (int) $oleh_user : NULL,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id;
	}

	public function toggle_stage($id_stage, $is_aktif, $oleh_user = NULL)
	{
		$this->_sp('{CALL dbo.sp_ToggleStage(?,?,?)}', array(
			(int) $id_stage, $is_aktif ? 1 : 0, $oleh_user ? (int) $oleh_user : NULL,
		));
	}

	/* ================= DEPARTEMEN ================================== */

	public function list_departemen()
	{
		return $this->db->query(
			'SELECT id_departemen, kode, nama, is_aktif,
			        (SELECT COUNT(*) FROM dbo.M_POSISI p WHERE p.id_departemen = d.id_departemen) AS n_posisi
			 FROM dbo.M_DEPARTEMEN d ORDER BY is_aktif DESC, kode'
		)->result_array();
	}

	public function save_departemen($in, $oleh_user = NULL)
	{
		$id = 0;
		$id_dept = ! empty($in['id_departemen']) ? (int) $in['id_departemen'] : (! empty($in['id']) ? (int) $in['id'] : NULL);
		$this->_sp('{CALL dbo.sp_SaveDepartemen(?,?,?,?,?)}', array(
			$id_dept,
			(string) $in['kode'],
			(string) $in['nama'],
			$oleh_user ? (int) $oleh_user : NULL,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id;
	}

	public function toggle_departemen($id_departemen, $is_aktif, $oleh_user = NULL)
	{
		$this->_sp('{CALL dbo.sp_ToggleDepartemen(?,?,?)}', array(
			(int) $id_departemen, $is_aktif ? 1 : 0, $oleh_user ? (int) $oleh_user : NULL,
		));
	}

	/* ================= OUTLET ====================================== */

	public function list_outlet()
	{
		return $this->db->query(
			'SELECT id_outlet, kode_outlet, nama_outlet, brand, region, is_aktif,
			        (SELECT COUNT(*) FROM dbo.REQUISITIONS r WHERE r.id_outlet = o.id_outlet) AS n_req
			 FROM dbo.M_OUTLET o ORDER BY is_aktif DESC, kode_outlet'
		)->result_array();
	}

	public function save_outlet($in)
	{
		$id_outlet = ! empty($in['id_outlet']) ? (int) $in['id_outlet'] : (! empty($in['id']) ? (int) $in['id'] : NULL);
		if ($id_outlet) {
			$this->db->query(
				'UPDATE dbo.M_OUTLET SET kode_outlet = ?, nama_outlet = ?, brand = ?, region = ?
				 WHERE id_outlet = ?',
				array((string) $in['kode'], (string) $in['nama'], $in['brand'] ?: NULL, $in['region'] ?: NULL, $id_outlet));
		} else {
			$this->db->query(
				'INSERT INTO dbo.M_OUTLET (kode_outlet, nama_outlet, brand, region, is_aktif)
				 VALUES (?, ?, ?, ?, 1)',
				array((string) $in['kode'], (string) $in['nama'], $in['brand'] ?: NULL, $in['region'] ?: NULL));
		}
	}

	public function toggle_outlet($id_outlet, $is_aktif)
	{
		$this->db->query('UPDATE dbo.M_OUTLET SET is_aktif = ? WHERE id_outlet = ?',
			array($is_aktif ? 1 : 0, (int) $id_outlet));
	}

	/* ================= DOKUMEN ===================================== */

	public function list_dokumen()
	{
		return $this->db->query(
			'SELECT id_dokumen, nama_dokumen, kategori, tingkat_sensitif, is_mandatory_default, is_aktif
			 FROM dbo.M_DOKUMEN ORDER BY is_aktif DESC, kategori, nama_dokumen'
		)->result_array();
	}

	public function save_dokumen($in)
	{
		$id_dokumen = ! empty($in['id_dokumen']) ? (int) $in['id_dokumen'] : (! empty($in['id']) ? (int) $in['id'] : NULL);
		$mand = ! empty($in['is_mandatory_default']) ? 1 : 0;
		if ($id_dokumen) {
			$this->db->query(
				'UPDATE dbo.M_DOKUMEN
				 SET nama_dokumen = ?, kategori = ?, tingkat_sensitif = ?, is_mandatory_default = ?
				 WHERE id_dokumen = ?',
				array((string) $in['nama'], (string) $in['kategori'], (string) $in['tingkat_sensitif'], $mand, $id_dokumen));
		} else {
			$this->db->query(
				'INSERT INTO dbo.M_DOKUMEN (nama_dokumen, kategori, tingkat_sensitif, is_mandatory_default, is_aktif)
				 VALUES (?, ?, ?, ?, 1)',
				array((string) $in['nama'], (string) $in['kategori'], (string) $in['tingkat_sensitif'], $mand));
		}
	}

	public function toggle_dokumen($id_dokumen, $is_aktif)
	{
		$this->db->query('UPDATE dbo.M_DOKUMEN SET is_aktif = ? WHERE id_dokumen = ?',
			array($is_aktif ? 1 : 0, (int) $id_dokumen));
	}

	/* ================= REMARKS (M_REMARKS) ========================= */

	public function remarks($id_stage = NULL)
	{
		$sql = 'SELECT r.id_remark, r.id_stage, r.kode_remark, r.label, r.efek_status, r.urutan, r.is_aktif,
		               s.nama_tahap, s.kode_stage
		        FROM dbo.M_REMARKS r
		        JOIN dbo.M_STAGE s ON s.id_stage = r.id_stage';
		$params = array();
		if ($id_stage !== NULL) {
			$sql .= ' WHERE r.id_stage = ?';
			$params[] = (int) $id_stage;
		}
		$sql .= ' ORDER BY s.nama_tahap, r.urutan, r.id_remark';
		$q = $this->db->query($sql, $params);
		return $q->result_array();
	}

	public function all_stages()
	{
		return $this->db->query(
			'SELECT id_stage, kode_stage, nama_tahap, tipe_tahap FROM dbo.M_STAGE WHERE is_aktif = 1 ORDER BY nama_tahap'
		)->result_array();
	}

	public function save_remark($in, $oleh_user = NULL)
	{
		$id = 0;
		$id_rem = ! empty($in['id_remark']) ? (int) $in['id_remark'] : (! empty($in['id']) ? (int) $in['id'] : NULL);
		$this->_sp('{CALL dbo.sp_SaveRemark(?,?,?,?,?,?,?,?)}', array(
			$id_rem,
			(int) $in['id_stage'],
			(string) $in['kode_remark'],
			(string) $in['label'],
			(string) $in['efek_status'],
			! empty($in['urutan']) ? (int) $in['urutan'] : 1,
			$oleh_user ? (int) $oleh_user : NULL,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id;
	}

	public function toggle_remark($id_remark, $is_aktif, $oleh_user = NULL)
	{
		$this->_sp('{CALL dbo.sp_ToggleRemark(?,?,?)}', array(
			(int) $id_remark, $is_aktif ? 1 : 0, $oleh_user ? (int) $oleh_user : NULL,
		));
	}

	/* ================= EFEK STATUS (M_EFEK_STATUS) ================= */

	public function list_efek_status($hanya_aktif = FALSE)
	{
		$sql = 'SELECT id_efek_status, kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif,
		               (SELECT COUNT(*) FROM dbo.M_REMARKS r WHERE r.efek_status = e.kode_efek) AS n_remark
		        FROM dbo.M_EFEK_STATUS e';
		if ($hanya_aktif) {
			$sql .= ' WHERE is_aktif = 1';
		}
		$sql .= ' ORDER BY is_aktif DESC, urutan, kode_efek';
		return $this->db->query($sql)->result_array();
	}

	public function save_efek_status($in)
	{
		$id = ! empty($in['id_efek_status']) ? (int) $in['id_efek_status'] : (! empty($in['id']) ? (int) $in['id'] : NULL);
		$kode = strtoupper(trim((string) $in['kode_efek']));
		$nama = trim((string) $in['nama_efek']);
		$st_tahap = (string) $in['status_tahap'];
		$st_global = (string) $in['status_global_target'];
		$desc = ! empty($in['deskripsi']) ? trim((string) $in['deskripsi']) : NULL;
		$urutan = isset($in['urutan']) && $in['urutan'] !== '' ? (int) $in['urutan'] : 0;

		if ($id) {
			$this->db->query(
				'UPDATE dbo.M_EFEK_STATUS
				 SET kode_efek = ?, nama_efek = ?, status_tahap = ?, status_global_target = ?, deskripsi = ?, urutan = ?
				 WHERE id_efek_status = ?',
				array($kode, $nama, $st_tahap, $st_global, $desc, $urutan, $id)
			);
		} else {
			$this->db->query(
				'INSERT INTO dbo.M_EFEK_STATUS (kode_efek, nama_efek, status_tahap, status_global_target, deskripsi, urutan, is_aktif)
				 VALUES (?, ?, ?, ?, ?, ?, 1)',
				array($kode, $nama, $st_tahap, $st_global, $desc, $urutan)
			);
		}
	}

	public function toggle_efek_status($id, $is_aktif)
	{
		$this->db->query(
			'UPDATE dbo.M_EFEK_STATUS SET is_aktif = ? WHERE id_efek_status = ?',
			array($is_aktif ? 1 : 0, (int) $id)
		);
	}

	/* ================= FLOW BUILDER (M_FLOW + M_FLOW_STAGE) ======= */

	public function list_flow()
	{
		return $this->db->query(
			'SELECT f.id_flow, f.kode_flow, f.nama_flow, f.tipe_penempatan,
			        f.maks_upaya_kontak, f.sla_total_hari, f.versi, f.is_aktif,
			        (SELECT COUNT(*) FROM dbo.M_FLOW_STAGE fs WHERE fs.id_flow = f.id_flow) AS n_tahap,
			        (SELECT COUNT(*) FROM dbo.REQUISITIONS r WHERE r.id_flow = f.id_flow) AS n_mpr
			 FROM dbo.M_FLOW f
			 ORDER BY f.is_aktif DESC, f.tipe_penempatan, f.nama_flow'
		)->result_array();
	}

	public function flow_stages($id_flow)
	{
		return $this->db->query(
			'SELECT fs.id_flow_stage, fs.id_flow, fs.id_stage, fs.urutan, fs.is_wajib, fs.sla_hari, fs.role_pic,
			        s.nama_tahap, s.kode_stage, s.tipe_tahap
			 FROM dbo.M_FLOW_STAGE fs
			 JOIN dbo.M_STAGE s ON s.id_stage = fs.id_stage
			 WHERE fs.id_flow = ?
			 ORDER BY fs.urutan',
			array((int) $id_flow)
		)->result_array();
	}

	public function all_roles()
	{
		return $this->db->query(
			'SELECT kode_role, nama_role FROM dbo.M_ROLES ORDER BY id_role'
		)->result_array();
	}

	public function save_flow($in, $oleh_user = NULL)
	{
		$id = 0;
		$id_flow = ! empty($in['id_flow']) ? (int) $in['id_flow'] : NULL;
		$this->_sp('{CALL dbo.sp_SaveFlow(?,?,?,?,?,?,?,?)}', array(
			$id_flow,
			(string) $in['kode_flow'],
			(string) $in['nama_flow'],
			(string) $in['tipe_penempatan'],
			isset($in['maks_upaya_kontak']) ? (int) $in['maks_upaya_kontak'] : 3,
			! empty($in['sla_total_hari']) ? (int) $in['sla_total_hari'] : NULL,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
			$oleh_user ? (int) $oleh_user : NULL,
		));
		return (int) $id;
	}

	public function flow_stage_action($in, $oleh_user = NULL)
	{
		$aksi = (string) $in['aksi'];
		$id_flow = (int) $in['id_flow'];
		$id_flow_stage = ! empty($in['id_flow_stage']) ? (int) $in['id_flow_stage'] : NULL;
		$id_stage = ! empty($in['id_stage']) ? (int) $in['id_stage'] : NULL;
		$urutan = isset($in['urutan']) && $in['urutan'] !== '' ? (int) $in['urutan'] : NULL;
		$is_wajib = ! empty($in['is_wajib']) ? 1 : 0;
		$sla_hari = isset($in['sla_hari']) && $in['sla_hari'] !== '' ? (int) $in['sla_hari'] : NULL;
		$role_pic = ! empty($in['role_pic']) ? (string) $in['role_pic'] : NULL;

		$this->_sp('{CALL dbo.sp_SaveFlowStage(?,?,?,?,?,?,?,?,?)}', array(
			$aksi,
			$id_flow,
			$id_flow_stage,
			$id_stage,
			$urutan,
			$is_wajib,
			$sla_hari,
			$role_pic,
			$oleh_user ? (int) $oleh_user : NULL,
		));
	}
}
