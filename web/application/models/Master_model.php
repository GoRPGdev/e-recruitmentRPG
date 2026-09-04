<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Master_model -- CRUD master data + Flow Builder.
 * Posisi & flow lewat SP (logika + FK). Master flat (departemen/outlet/
 * channel/dokumen) lewat query berparameter (tabel lookup, tanpa transaksi).
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
			'SELECT p.id_posisi, p.nama_posisi, p.level_posisi, p.is_aktif,
			        d.nama AS departemen, f.kode_flow
			 FROM dbo.M_POSISI p
			 JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
			 LEFT JOIN dbo.M_FLOW f  ON f.id_flow = p.default_flow
			 ORDER BY p.is_aktif DESC, p.nama_posisi');
		return $q->result_array();
	}

	public function save_posisi($in)
	{
		$id = 0;
		$this->_sp('{CALL dbo.sp_SavePosisi(?,?,?,?,?,?)}', array(
			! empty($in['id_posisi']) ? (int) $in['id_posisi'] : NULL,
			(string) $in['nama_posisi'],
			(int) $in['id_departemen'],
			(string) $in['level_posisi'],
			! empty($in['default_flow']) ? (int) $in['default_flow'] : NULL,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id;
	}

	public function toggle_posisi($id_posisi, $is_aktif)
	{
		$this->_sp('{CALL dbo.sp_TogglePosisi(?,?)}', array((int) $id_posisi, $is_aktif ? 1 : 0));
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
		$q = $this->db->query('SELECT * FROM dbo.M_STAGE WHERE id_stage = ?', array((int) $id_stage));
		return $q->row_array() ?: NULL;
	}

	public function save_stage($in)
	{
		$id = 0;
		$this->_sp('{CALL dbo.sp_SaveStage(?,?,?,?,?,?,?)}', array(
			! empty($in['id_stage']) ? (int) $in['id_stage'] : NULL,
			(string) $in['kode_stage'],
			(string) $in['nama_tahap'],
			(string) $in['tipe_tahap'],
			! empty($in['is_terminal']) ? 1 : 0,
			isset($in['is_aktif']) ? ($in['is_aktif'] ? 1 : 0) : 1,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id;
	}

	public function toggle_stage($id_stage, $is_aktif)
	{
		$this->_sp('{CALL dbo.sp_ToggleStage(?,?)}', array((int) $id_stage, $is_aktif ? 1 : 0));
	}

	/* ================= MASTER FLAT ================================ */

	private function _flat_list($tabel, $cols)
	{
		return $this->db->query("SELECT $cols FROM dbo.$tabel ORDER BY is_aktif DESC, 2")->result_array();
	}

	public function list_departemen() { return $this->_flat_list('M_DEPARTEMEN', 'id_departemen, nama, kode, is_aktif'); }
	public function list_outlet()     { return $this->_flat_list('M_OUTLET', 'id_outlet, nama_outlet, kode_outlet, brand, region, is_aktif'); }
	public function list_channel()    { return $this->_flat_list('M_CHANNEL', 'id_channel, nama_channel, is_eksternal, is_aktif'); }
	public function list_dokumen()    { return $this->_flat_list('M_DOKUMEN', 'id_dokumen, nama_dokumen, kategori, tingkat_sensitif, is_mandatory_default, is_aktif'); }

	public function save_departemen($in)
	{
		if ( ! empty($in['id'])) {
			$this->db->query('UPDATE dbo.M_DEPARTEMEN SET kode=?, nama=? WHERE id_departemen=?',
				array($in['kode'], $in['nama'], (int) $in['id']));
		} else {
			$this->db->query('INSERT INTO dbo.M_DEPARTEMEN (kode, nama, is_aktif) VALUES (?,?,1)',
				array($in['kode'], $in['nama']));
		}
	}

	public function save_outlet($in)
	{
		if ( ! empty($in['id'])) {
			$this->db->query('UPDATE dbo.M_OUTLET SET kode_outlet=?, nama_outlet=?, brand=?, region=? WHERE id_outlet=?',
				array($in['kode'], $in['nama'], $in['brand'] ?: NULL, $in['region'] ?: NULL, (int) $in['id']));
		} else {
			$this->db->query('INSERT INTO dbo.M_OUTLET (kode_outlet, nama_outlet, brand, region, is_aktif) VALUES (?,?,?,?,1)',
				array($in['kode'], $in['nama'], $in['brand'] ?: NULL, $in['region'] ?: NULL));
		}
	}

	public function save_channel($in)
	{
		$eks = ! empty($in['is_eksternal']) ? 1 : 0;
		if ( ! empty($in['id'])) {
			$this->db->query('UPDATE dbo.M_CHANNEL SET nama_channel=?, is_eksternal=? WHERE id_channel=?',
				array($in['nama'], $eks, (int) $in['id']));
		} else {
			$this->db->query('INSERT INTO dbo.M_CHANNEL (nama_channel, is_eksternal, is_aktif) VALUES (?,?,1)',
				array($in['nama'], $eks));
		}
	}

	public function save_dokumen($in)
	{
		$mand = ! empty($in['is_mandatory_default']) ? 1 : 0;
		if ( ! empty($in['id'])) {
			$this->db->query('UPDATE dbo.M_DOKUMEN SET nama_dokumen=?, kategori=?, tingkat_sensitif=?, is_mandatory_default=? WHERE id_dokumen=?',
				array($in['nama'], $in['kategori'], $in['tingkat_sensitif'], $mand, (int) $in['id']));
		} else {
			$this->db->query('INSERT INTO dbo.M_DOKUMEN (nama_dokumen, kategori, tingkat_sensitif, is_mandatory_default, is_aktif) VALUES (?,?,?,?,1)',
				array($in['nama'], $in['kategori'], $in['tingkat_sensitif'], $mand));
		}
	}

	public function toggle_flat($tabel, $pk, $id, $is_aktif)
	{
		$allow = array('M_DEPARTEMEN' => 'id_departemen', 'M_OUTLET' => 'id_outlet',
		               'M_CHANNEL' => 'id_channel', 'M_DOKUMEN' => 'id_dokumen');
		if ( ! isset($allow[$tabel]) || $allow[$tabel] !== $pk) {
			throw new RuntimeException('Tabel tidak diizinkan.');
		}
		$this->db->query("UPDATE dbo.$tabel SET is_aktif = ? WHERE $pk = ?", array($is_aktif ? 1 : 0, (int) $id));
	}

	/* ================= FLOW BUILDER =============================== */

	public function list_flow()
	{
		return $this->db->query(
			'SELECT f.id_flow, f.kode_flow, f.nama_flow, f.tipe_penempatan, f.versi, f.maks_upaya_kontak,
			        f.is_aktif, fi.kode_flow AS induk,
			        (SELECT COUNT(*) FROM dbo.M_FLOW_STAGE fs WHERE fs.id_flow = f.id_flow) AS n_stage
			 FROM dbo.M_FLOW f LEFT JOIN dbo.M_FLOW fi ON fi.id_flow = f.id_flow_induk
			 ORDER BY f.is_aktif DESC, f.kode_flow')->result_array();
	}

	public function get_flow($id_flow)
	{
		$q = $this->db->query('SELECT * FROM dbo.M_FLOW WHERE id_flow = ?', array((int) $id_flow));
		return $q->row_array() ?: NULL;
	}

	public function flow_stages($id_flow)
	{
		return $this->db->query(
			'SELECT fs.id_flow_stage, fs.id_stage, fs.urutan, fs.is_wajib, fs.sla_hari, fs.role_pic,
			        s.kode_stage, s.nama_tahap, s.tipe_tahap
			 FROM dbo.M_FLOW_STAGE fs JOIN dbo.M_STAGE s ON s.id_stage = fs.id_stage
			 WHERE fs.id_flow = ? ORDER BY fs.urutan', array((int) $id_flow))->result_array();
	}

	public function all_stages()
	{
		return $this->db->query("SELECT id_stage, kode_stage, nama_tahap, tipe_tahap FROM dbo.M_STAGE WHERE is_aktif = 1 ORDER BY nama_tahap")->result_array();
	}

	public function all_roles()
	{
		return $this->db->query("SELECT kode_role, nama_role FROM dbo.M_ROLES WHERE is_aktif = 1 ORDER BY kode_role")->result_array();
	}

	public function save_flow($in)
	{
		$id = 0;
		$this->_sp('{CALL dbo.sp_SaveFlow(?,?,?,?,?,?,?)}', array(
			! empty($in['id_flow']) ? (int) $in['id_flow'] : NULL,
			(string) $in['kode_flow'], (string) $in['nama_flow'], (string) $in['tipe_penempatan'],
			(int) ($in['maks_upaya_kontak'] ?: 3),
			$in['sla_total_hari'] !== '' ? (int) $in['sla_total_hari'] : NULL,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id;
	}

	public function flow_stage_action($in)
	{
		$this->_sp('{CALL dbo.sp_SaveFlowStage(?,?,?,?,?,?,?,?)}', array(
			(string) $in['aksi'],
			(int) $in['id_flow'],
			! empty($in['id_flow_stage']) ? (int) $in['id_flow_stage'] : NULL,
			! empty($in['id_stage']) ? (int) $in['id_stage'] : NULL,
			isset($in['urutan']) && $in['urutan'] !== '' ? (int) $in['urutan'] : NULL,
			! empty($in['is_wajib']) ? 1 : 0,
			$in['sla_hari'] !== '' ? (int) $in['sla_hari'] : NULL,
			$in['role_pic'] ?: NULL,
		));
	}

	public function clone_flow($id_sumber, $kode_baru, $nama_baru)
	{
		$id = 0;
		$this->_sp('{CALL dbo.sp_CloneFlow(?,?,?,?)}', array(
			(int) $id_sumber, (string) $kode_baru, (string) $nama_baru,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id;
	}

	public function toggle_flow($id_flow, $is_aktif)
	{
		$this->db->query('UPDATE dbo.M_FLOW SET is_aktif = ? WHERE id_flow = ?', array($is_aktif ? 1 : 0, (int) $id_flow));
	}

	/* ---- remarks ---- */

	public function remarks($id_stage = NULL)
	{
		$sql = 'SELECT r.id_remark, r.id_stage, r.kode_remark, r.label, r.efek_status, r.urutan, r.is_aktif,
		               s.nama_tahap FROM dbo.M_REMARKS r JOIN dbo.M_STAGE s ON s.id_stage = r.id_stage';
		$b = array();
		if ($id_stage) { $sql .= ' WHERE r.id_stage = ?'; $b[] = (int) $id_stage; }
		$sql .= ' ORDER BY s.nama_tahap, r.urutan, r.id_remark';
		return $this->db->query($sql, $b)->result_array();
	}

	public function save_remark($in)
	{
		$id = 0;
		$this->_sp('{CALL dbo.sp_SaveRemark(?,?,?,?,?,?,?,?)}', array(
			! empty($in['id_remark']) ? (int) $in['id_remark'] : NULL,
			(int) $in['id_stage'],
			(string) $in['kode_remark'], (string) $in['label'], (string) $in['efek_status'],
			(int) ($in['urutan'] ?: 0),
			isset($in['is_aktif']) ? ($in['is_aktif'] ? 1 : 0) : 1,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id;
	}
}
