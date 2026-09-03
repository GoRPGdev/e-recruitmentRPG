<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Requisition_model -- MPR + approval + posting + flow engine (advance / contact / pipeline).
 * SP dengan OUTPUT param dipanggil via sqlsrv_query() langsung.
 */
class Requisition_model extends CI_Model
{
	private function _call($sql, $params, array $out_names = array())
	{
		$stmt = sqlsrv_query($this->db->conn_id, $sql, $params);
		if ($stmt === FALSE) {
			$e = sqlsrv_errors();
			$last = $e ? end($e) : NULL;
			throw new RuntimeException($last ? trim($last['message']) : 'Query gagal.');
		}
		do { /* nothing */ } while (sqlsrv_next_result($stmt));
		sqlsrv_free_stmt($stmt);
	}

	/* ================= list / lihat ================================= */

	private function _list_where($f, &$b)
	{
		$w = array();
		if ( ! empty($f['status']))  { $w[] = 'r.status_req = ?';   $b[] = $f['status']; }
		if ( ! empty($f['posisi']))  { $w[] = 'r.id_posisi = ?';    $b[] = (int) $f['posisi']; }
		if ( ! empty($f['dept']))    { $w[] = 'p.id_departemen = ?'; $b[] = (int) $f['dept']; }
		if ( ! empty($f['dari']))    { $w[] = 'r.created_at >= ?';   $b[] = $f['dari']; }
		if ( ! empty($f['sampai']))  { $w[] = 'r.created_at < DATEADD(DAY,1,?)'; $b[] = $f['sampai']; }
		return $w ? 'WHERE ' . implode(' AND ', $w) : '';
	}

	public function count_list($f = array())
	{
		$b = array();
		$where = $this->_list_where((array) $f, $b);
		return (int) $this->db->query(
			"SELECT COUNT(*) AS n FROM dbo.REQUISITIONS r JOIN dbo.M_POSISI p ON p.id_posisi = r.id_posisi $where", $b
		)->row()->n;
	}

	public function list_mpr($offset, $per, $f = array())
	{
		$b = array();
		$where = $this->_list_where((array) $f, $b);
		$b[] = (int) $offset; $b[] = (int) $offset + (int) $per - 1;
		$sql = "WITH q AS (
		            SELECT r.id_req, r.no_mpr, r.status_req, r.tipe_penempatan, r.jumlah_dibutuhkan,
		                   r.jumlah_disetujui, r.jumlah_terpenuhi, r.tanggal_pengajuan,
		                   p.nama_posisi, o.nama_outlet, u.nama_snapshot AS pemohon,
		                   ROW_NUMBER() OVER (ORDER BY r.id_req DESC) AS rn
		            FROM dbo.REQUISITIONS r
		            JOIN dbo.M_POSISI p      ON p.id_posisi = r.id_posisi
		            LEFT JOIN dbo.M_OUTLET o ON o.id_outlet = r.id_outlet
		            JOIN dbo.M_USERS u       ON u.id_user = r.id_user_pemohon
		            $where
		        )
		        SELECT * FROM q WHERE rn BETWEEN ? AND ? ORDER BY rn";
		$q = $this->db->query($sql, $b);
		$rows = $q->result_array(); $q->free_result();
		return $rows;
	}

	public function get($id_req)
	{
		$q = $this->db->query(
			'SELECT r.*, p.nama_posisi, o.nama_outlet, d.nama AS departemen, u.nama_snapshot AS pemohon,
			        f.kode_flow, f.nama_flow
			 FROM dbo.REQUISITIONS r
			 JOIN dbo.M_POSISI p       ON p.id_posisi = r.id_posisi
			 LEFT JOIN dbo.M_OUTLET o  ON o.id_outlet = r.id_outlet
			 LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
			 JOIN dbo.M_USERS u        ON u.id_user = r.id_user_pemohon
			 LEFT JOIN dbo.M_FLOW f    ON f.id_flow = r.id_flow
			 WHERE r.id_req = ?', array((int) $id_req));
		$row = $q->row_array(); $q->free_result();
		return $row ? $row : NULL;
	}

	public function approvals($id_req)
	{
		$q = $this->db->query(
			'SELECT ra.*, u.nama_snapshot AS diinput
			 FROM dbo.REQUISITION_APPROVALS ra
			 LEFT JOIN dbo.M_USERS u ON u.id_user = ra.diinput_oleh
			 WHERE ra.id_req = ? ORDER BY ra.putaran_ke', array((int) $id_req));
		$rows = $q->result_array(); $q->free_result();
		return $rows;
	}

	public function open_approval($id_req)
	{
		$q = $this->db->query(
			"SELECT id_approval, putaran_ke FROM dbo.REQUISITION_APPROVALS
			 WHERE id_req = ? AND keputusan = 'Pending' ORDER BY putaran_ke DESC", array((int) $id_req));
		$row = $q->row_array(); $q->free_result();
		return $row ? $row : NULL;
	}

	public function positions()
	{
		$q = $this->db->query('SELECT id_posisi, nama_posisi, level_posisi, default_flow FROM dbo.M_POSISI WHERE is_aktif = 1 ORDER BY nama_posisi');
		$r = $q->result_array(); $q->free_result(); return $r;
	}

	public function outlets()
	{
		$q = $this->db->query('SELECT id_outlet, nama_outlet FROM dbo.M_OUTLET WHERE is_aktif = 1 ORDER BY nama_outlet');
		$r = $q->result_array(); $q->free_result(); return $r;
	}

	public function departments()
	{
		$q = $this->db->query('SELECT id_departemen, nama FROM dbo.M_DEPARTEMEN WHERE is_aktif = 1 ORDER BY nama');
		$r = $q->result_array(); $q->free_result(); return $r;
	}

	/* ================= SP: requisition ============================== */

	public function create($in, $id_user_pemohon)
	{
		$id_req = 0;
		$this->_call(
			'{CALL dbo.sp_CreateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}',
			array(
				(int) $id_user_pemohon,
				(int) $in['id_posisi'],
				$in['tipe_penempatan'],
				! empty($in['id_outlet']) ? (int) $in['id_outlet'] : NULL,
				(int) $in['jumlah_dibutuhkan'],
				$in['status_karyawan'] ?: NULL,
				$in['alasan_permintaan'] ?: NULL,
				$in['nik_digantikan'] ?: NULL,
				$in['target_tanggal_join'] ?: NULL,
				$in['urgensi'] ?: NULL,
				! empty($in['id_flow']) ? (int) $in['id_flow'] : NULL,
				! empty($in['butuh_psikotes']) ? 1 : 0,
				! empty($in['butuh_interview_bod']) ? 1 : 0,
				$in['pendidikan_minimal'] ?: NULL,
				$in['pengalaman_minimal_tahun'] !== '' ? (int) $in['pengalaman_minimal_tahun'] : NULL,
				$in['job_desc'] ?: NULL,
				$in['kualifikasi'] ?: NULL,
				$in['range_gaji_min'] !== '' ? $in['range_gaji_min'] : NULL,
				$in['range_gaji_max'] !== '' ? $in['range_gaji_max'] : NULL,
				$in['preferensi_internal'] ?: NULL,
				array(&$id_req, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
			)
		);
		return (int) $id_req;
	}

	public function submit_to_bod($id_req, $oleh_user)
	{
		$id_app = 0;
		$this->_call('{CALL dbo.sp_SubmitToBOD(?,?,?)}', array(
			(int) $id_req, (int) $oleh_user,
			array(&$id_app, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id_app;
	}

	public function record_approval($in, $oleh_user)
	{
		$this->_call('{CALL dbo.sp_RecordApproval(?,?,?,?,?,?,?,?)}', array(
			(int) $in['id_approval'],
			$in['keputusan'],
			$in['jumlah_disetujui'] !== '' ? (int) $in['jumlah_disetujui'] : NULL,
			$in['tanggal_keputusan'] ?: NULL,
			$in['disetujui_oleh'] ?: NULL,
			$in['catatan_bod'] ?: NULL,
			$in['lampiran_path'] ?: NULL,
			(int) $oleh_user,
		));
	}

	public function create_posting($id_req, $id_channel, $judul, $job_desc, $kualifikasi)
	{
		$id_posting = 0;
		$this->_call('{CALL dbo.sp_CreatePosting(?,?,?,?,?,?,?)}', array(
			(int) $id_req, (int) $id_channel, (string) $judul,
			$job_desc ?: NULL, $kualifikasi ?: NULL, 1,
			array(&$id_posting, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id_posting;
	}

	/* ================= SP: flow engine ============================== */

	public function pipeline($id_req)
	{
		$stmt = sqlsrv_query($this->db->conn_id, '{CALL dbo.sp_GetPipeline(?)}', array((int) $id_req));
		if ($stmt === FALSE) { throw new RuntimeException('sp_GetPipeline gagal.'); }
		$rows = array();
		while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			foreach ($r as $k => $v) { if ($v instanceof DateTime) { $r[$k] = $v->format('Y-m-d H:i'); } }
			$rows[] = $r;
		}
		sqlsrv_free_stmt($stmt);
		return $rows;
	}

	public function advance($id_app_stage, $id_remark, $pic_user, $catatan)
	{
		$st = '';
		$this->_call('{CALL dbo.sp_AdvanceStage(?,?,?,?,?)}', array(
			(int) $id_app_stage,
			$id_remark !== '' && $id_remark !== NULL ? (int) $id_remark : NULL,
			(int) $pic_user,
			$catatan ?: NULL,
			array(&$st, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR)),
		));
		return $st;
	}

	public function log_contact($id_lamaran, $metode, $hasil, $catatan, $oleh_user)
	{
		$u = 0;
		$this->_call('{CALL dbo.sp_LogContact(?,?,?,?,?,?)}', array(
			(int) $id_lamaran, $metode, $hasil, $catatan ?: NULL, (int) $oleh_user,
			array(&$u, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $u;
	}

	public function remarks_for_stage($id_stage)
	{
		$q = $this->db->query(
			'SELECT id_remark, kode_remark, label, efek_status FROM dbo.M_REMARKS
			 WHERE id_stage = ? AND is_aktif = 1 ORDER BY urutan, id_remark', array((int) $id_stage));
		$r = $q->result_array(); $q->free_result(); return $r;
	}

	public function insert_adhoc($id_lamaran, $id_stage, $setelah_urutan, $pic_user, $catatan)
	{
		$id = 0;
		$this->_call('{CALL dbo.sp_InsertAdHocStage(?,?,?,?,?,?)}', array(
			(int) $id_lamaran, (int) $id_stage, (int) $setelah_urutan, (int) $pic_user, $catatan ?: NULL,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (int) $id;
	}

	public function active_stages()
	{
		$q = $this->db->query("SELECT id_stage, nama_tahap, tipe_tahap FROM dbo.M_STAGE WHERE is_aktif = 1 ORDER BY nama_tahap");
		$r = $q->result_array(); $q->free_result(); return $r;
	}

	/* current urutan tahap 'Berjalan' untuk 1 lamaran -- titik sisip ad-hoc */
	public function current_urutan($id_lamaran)
	{
		$q = $this->db->query("SELECT TOP 1 urutan FROM dbo.APPLICATION_STAGES
		                       WHERE id_lamaran = ? AND status_tahap = 'Berjalan'", array((int) $id_lamaran));
		$row = $q->row(); $q->free_result();
		return $row ? (int) $row->urutan : 0;
	}

	public function save_lampiran_approval($id_approval, $path)
	{
		$this->db->query('UPDATE dbo.REQUISITION_APPROVALS SET lampiran_path = ? WHERE id_approval = ?',
			array($path, (int) $id_approval));
	}
}
