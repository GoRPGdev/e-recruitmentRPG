<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model Requisition_model -- Model Permintaan Tenaga Kerja (MPR) & Flow Engine Seleksi
 *
 * Fungsi:
 * - Mengelola siklus hidup dokumen MPR: pengajuan, review HR, persetujuan BOD, dan pembatalan.
 * - Menangani pembaruan status MPR dan catatan/feedback evaluasi HR via Stored Procedure.
 * - Menggerakkan flow engine seleksi: transisi tahap pelamar (advance), pencatatan log kontak, dan penjadwalan wawancara.
 * - Menangani eksekusi SP dengan parameter output T-SQL menggunakan driver sqlsrv native.
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

	private function _has_catatan_hr()
	{
		static $has = NULL;
		if ($has === NULL) {
			// ponytail: fallback metadata check jika migrasi 20260912_1600 belum dieksekusi di database
			$r = $this->db->query("SELECT COL_LENGTH('dbo.REQUISITIONS', 'catatan_hr') AS l")->row();
			$has = ! empty($r->l);
		}
		return $has;
	}

	private function _list_where($f, &$b)
	{
		$w = array();
		if ( ! empty($f['status']))  { $w[] = 'r.status_req = ?';   $b[] = $f['status']; }
		if ( ! empty($f['posisi']))  { $w[] = 'r.id_posisi = ?';    $b[] = (int) $f['posisi']; }
		if ( ! empty($f['dept']))    { $w[] = 'p.id_departemen = ?'; $b[] = (int) $f['dept']; }
		if ( ! empty($f['dari']))    { $w[] = 'r.created_at >= ?';   $b[] = $f['dari']; }
		if ( ! empty($f['sampai']))  { $w[] = 'r.created_at < DATEADD(DAY,1,?)'; $b[] = $f['sampai']; }

		$dept = current_user_dept();
		if ($dept !== NULL) {
			$w[] = 'p.id_departemen = ?';
			$b[] = (int) $dept;
		}

		return $w ? 'WHERE ' . implode(' AND ', $w) : '';
	}

	public function stats_summary($f = array())
	{
		$b = array();
		$where = $this->_list_where((array) $f, $b);
		$sql = "SELECT 
		            COUNT(*) AS total,
		            SUM(CASE WHEN r.status_req IN ('Sourcing', 'Approved', 'Sourcing_Ulang') THEN 1 ELSE 0 END) AS n_aktif,
		            SUM(CASE WHEN r.status_req IN ('Review_HR', 'Revisi_HR', 'Review_BOD', 'Revisi_BOD', 'Draft') THEN 1 ELSE 0 END) AS n_review,
		            SUM(CASE WHEN r.status_req IN ('Terpenuhi', 'Terpenuhi_Sebagian') THEN 1 ELSE 0 END) AS n_terpenuhi
		        FROM dbo.REQUISITIONS r
		        JOIN dbo.M_POSISI p ON p.id_posisi = r.id_posisi $where";
		$q = $this->db->query($sql, $b);
		$row = $q->row_array();
		$q->free_result();
		return $row ?: array('total' => 0, 'n_aktif' => 0, 'n_review' => 0, 'n_terpenuhi' => 0);
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
		$col_catatan_hr = $this->_has_catatan_hr() ? 'r.catatan_hr' : 'NULL AS catatan_hr';
		$sql = "WITH q AS (
		            SELECT r.id_req, r.no_mpr, r.status_req, r.tipe_penempatan, r.jumlah_dibutuhkan,
		                   r.jumlah_disetujui, r.jumlah_terpenuhi, r.tanggal_pengajuan, {$col_catatan_hr},
		                   p.nama_posisi, p.id_departemen, o.nama_outlet, u.nama_snapshot AS pemohon,
		                   ISNULL(r.catatan_bod, bod_rej.catatan_bod) AS catatan_bod, bod_rej.disetujui_oleh AS penolak_bod,
		                   ROW_NUMBER() OVER (ORDER BY r.id_req DESC) AS rn
		            FROM dbo.REQUISITIONS r
		            JOIN dbo.M_POSISI p      ON p.id_posisi = r.id_posisi
		            LEFT JOIN dbo.M_OUTLET o ON o.id_outlet = r.id_outlet
		            JOIN dbo.M_USERS u       ON u.id_user = r.id_user_pemohon
		            OUTER APPLY (
		                SELECT TOP 1 ra.catatan_bod, ra.disetujui_oleh
		                FROM dbo.REQUISITION_APPROVALS ra
		                WHERE ra.id_req = r.id_req AND ra.keputusan = 'Rejected'
		                ORDER BY ra.putaran_ke DESC
		            ) bod_rej
		            $where
		        )
		        SELECT * FROM q WHERE rn BETWEEN ? AND ? ORDER BY rn";
		$q = $this->db->query($sql, $b);
		$rows = $q->result_array(); $q->free_result();
		return $rows;
	}

	public function get($id_req)
	{
		$col_catatan_hr = $this->_has_catatan_hr() ? '' : ', NULL AS catatan_hr';
		$q = $this->db->query(
			"SELECT r.*{$col_catatan_hr}, p.nama_posisi, p.id_departemen, o.nama_outlet, d.nama AS departemen, u.nama_snapshot AS pemohon,
			        f.kode_flow, f.nama_flow,
			        ISNULL(r.catatan_bod, bod_rej.catatan_bod) AS catatan_bod, bod_rej.disetujui_oleh AS penolak_bod
			 FROM dbo.REQUISITIONS r
			 JOIN dbo.M_POSISI p       ON p.id_posisi = r.id_posisi
			 LEFT JOIN dbo.M_OUTLET o  ON o.id_outlet = r.id_outlet
			 LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
			 JOIN dbo.M_USERS u        ON u.id_user = r.id_user_pemohon
			 LEFT JOIN dbo.M_FLOW f    ON f.id_flow = r.id_flow
			 OUTER APPLY (
			     SELECT TOP 1 ra.catatan_bod, ra.disetujui_oleh
			     FROM dbo.REQUISITION_APPROVALS ra
			     WHERE ra.id_req = r.id_req AND ra.keputusan = 'Rejected'
			     ORDER BY ra.putaran_ke DESC
			 ) bod_rej
			 WHERE r.id_req = ?", array((int) $id_req));
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

	public function positions($id_dept = NULL)
	{
		$where = 'WHERE is_aktif = 1';
		$params = array();
		if ($id_dept !== NULL) {
			$where .= ' AND id_departemen = ?';
			$params[] = (int) $id_dept;
		}
		$q = $this->db->query("SELECT id_posisi, nama_posisi, level_posisi, default_flow, id_departemen,
		                              job_desc, kualifikasi, pendidikan_minimal, pengalaman_minimal_tahun
		                       FROM dbo.M_POSISI $where ORDER BY nama_posisi", $params);
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

	public function update_requisition($id_req, $in, $oleh_user)
	{
		$this->_call(
			'{CALL dbo.sp_UpdateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}',
			array(
				(int) $id_req,
				(int) $in['id_posisi'],
				$in['tipe_penempatan'],
				! empty($in['id_outlet']) ? (int) $in['id_outlet'] : NULL,
				(int) $in['jumlah_dibutuhkan'],
				$in['status_karyawan'] ?: NULL,
				$in['alasan_permintaan'] ?: NULL,
				$in['nik_digantikan'] ?: NULL,
				$in['target_tanggal_join'] ?: NULL,
				$in['urgensi'] ?: NULL,
				$in['pendidikan_minimal'] ?: NULL,
				$in['pengalaman_minimal_tahun'] !== '' ? (int) $in['pengalaman_minimal_tahun'] : NULL,
				$in['job_desc'] ?: NULL,
				$in['kualifikasi'] ?: NULL,
				(int) $oleh_user,
			)
		);
	}

	public function submit_to_hr($id_req, $oleh_user)
	{
		$this->_call('{CALL dbo.sp_SubmitToHR(?,?)}', array(
			(int) $id_req, (int) $oleh_user,
		));
	}

	public function submit_to_bod($id_req, $oleh_user)
	{
		$this->_call('{CALL dbo.sp_SubmitToBOD(?,?)}', array(
			(int) $id_req, (int) $oleh_user,
		));
	}

	public function create_posting($id_req, $id_channel, $judul, $job_desc, $kualifikasi, $batch_ke = 1, $durasi_hari = 14)
	{
		$id_posting = 0;
		$this->_call('{CALL dbo.sp_CreatePosting(?,?,?,?,?,?,?,?)}', array(
			(int) $id_req,
			$id_channel !== NULL ? (int) $id_channel : NULL,
			(string) $judul,
			$job_desc ?: NULL,
			$kualifikasi ?: NULL,
			(int) $batch_ke,
			(int) $durasi_hari,
			array(&$id_posting, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		$id_posting = (int) $id_posting;
		if ($id_posting > 0) {
			$this->load->model('posting_model', 'pm');
			$this->pm->ensure_slug($id_posting);
		}
		return $id_posting;
	}

	public function postings_for_req($id_req)
	{
		$q = $this->db->query(
			'SELECT jp.*,
			        (SELECT COUNT(*) FROM dbo.APPLICATIONS a WHERE a.id_posting = jp.id_posting) AS n_lamaran,
			        CASE WHEN jp.form_aktif = 1 AND jp.form_ditutup IS NOT NULL AND jp.form_ditutup < GETDATE() THEN 1 ELSE 0 END AS is_kadaluarsa
			 FROM dbo.JOB_POSTINGS jp
			 WHERE jp.id_req = ?
			 ORDER BY jp.id_posting DESC',
			array((int) $id_req)
		);
		$rows = $q->result_array();
		$q->free_result();

		if ( ! empty($rows)) {
			$this->load->model('posting_model', 'pm');
			foreach ($rows as &$row) {
				if (empty($row['url_slug']) && ! empty($row['id_posting'])) {
					$row['url_slug'] = $this->pm->ensure_slug((int) $row['id_posting']);
				}
			}
			unset($row);
		}

		return $rows;
	}

	public function toggle_posting_form($id_posting, $form_aktif = NULL, $oleh_user = NULL, $durasi_hari = 14)
	{
		$status_akhir = 0;
		$this->_call('{CALL dbo.sp_TogglePostingForm(?,?,?,?,?)}', array(
			(int) $id_posting,
			$form_aktif !== NULL ? ($form_aktif ? 1 : 0) : NULL,
			(int) $durasi_hari,
			(int) $oleh_user,
			array(&$status_akhir, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		));
		return (bool) $status_akhir;
	}

	public function extend_posting($id_posting, $durasi_hari = 14, $oleh_user = NULL)
	{
		$this->_call('{CALL dbo.sp_ExtendPosting(?,?,?)}', array(
			(int) $id_posting,
			(int) $durasi_hari,
			(int) $oleh_user,
		));
		return TRUE;
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

	public function insert_adhoc($id_lamaran, $id_stage, $setelah_urutan, $pic_user, $catatan, $id_remark = NULL)
	{
		$id = 0;
		$params7 = array(
			(int) $id_lamaran,
			(int) $id_stage,
			(int) $setelah_urutan,
			! empty($pic_user) ? (int) $pic_user : NULL,
			$catatan ?: NULL,
			! empty($id_remark) ? (int) $id_remark : NULL,
			array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		);
		$stmt = @sqlsrv_query($this->db->conn_id, '{CALL dbo.sp_InsertAdHocStage(?,?,?,?,?,?,?)}', $params7);
		if ($stmt === FALSE) {
			// Fallback ke 6 parameter jika SP di database belum dideploy ulang
			$params6 = array(
				(int) $id_lamaran,
				(int) $id_stage,
				(int) $setelah_urutan,
				! empty($pic_user) ? (int) $pic_user : NULL,
				$catatan ?: NULL,
				array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
			);
			$stmt = sqlsrv_query($this->db->conn_id, '{CALL dbo.sp_InsertAdHocStage(?,?,?,?,?,?)}', $params6);
		}

		if ($stmt === FALSE) {
			$e = sqlsrv_errors();
			$last = $e ? end($e) : NULL;
			throw new RuntimeException($last ? trim($last['message']) : 'sp_InsertAdHocStage gagal.');
		}

		// WAJIB drain semua result set agar seluruh statement (UPDATE, INSERT, COMMIT) tuntas dieksekusi di SQL Server
		do { /* nothing */ } while (sqlsrv_next_result($stmt));

		$e = sqlsrv_errors();
		if ($e) {
			$last = end($e);
			if (isset($last['code']) && $last['code'] != 0) {
				sqlsrv_free_stmt($stmt);
				throw new RuntimeException(trim($last['message']));
			}
		}
		sqlsrv_free_stmt($stmt);

		return (int) $id;
	}

	public function active_stages()
	{
		$chk = $this->db->query("SELECT COL_LENGTH('dbo.M_STAGE', 'is_sisipan_allowed') AS col_len")->row_array();
		$where_sisip = ! empty($chk['col_len']) ? ' AND is_sisipan_allowed = 1' : '';

		$q = $this->db->query("SELECT id_stage, kode_stage, nama_tahap, tipe_tahap
		                       FROM dbo.M_STAGE
		                       WHERE is_aktif = 1
		                         {$where_sisip}
		                       ORDER BY nama_tahap");
		$r = $q->result_array(); $q->free_result(); return $r;
	}

	public function get_stages_for_lamaran(array $lamaran_ids)
	{
		if (empty($lamaran_ids)) return array();
		$lamaran_ids = array_values(array_map('intval', $lamaran_ids));
		$placeholders = implode(',', array_fill(0, count($lamaran_ids), '?'));
		$q = $this->db->query(
			"SELECT id_lamaran, id_stage
			 FROM dbo.APPLICATION_STAGES
			 WHERE id_lamaran IN ($placeholders)",
			$lamaran_ids
		);
		$rows = $q->result_array();
		$q->free_result();
		$map = array();
		foreach ($rows as $r) {
			$lid = (int) $r['id_lamaran'];
			$sid = (int) $r['id_stage'];
			if ( ! isset($map[$lid])) {
				$map[$lid] = array();
			}
			$map[$lid][] = $sid;
		}
		return $map;
	}

	public function has_stage($id_lamaran, $id_stage)
	{
		$q = $this->db->query(
			'SELECT TOP 1 1 AS ada FROM dbo.APPLICATION_STAGES WHERE id_lamaran = ? AND id_stage = ?',
			array((int) $id_lamaran, (int) $id_stage)
		);
		$has = (bool) $q->num_rows();
		$q->free_result();
		return $has;
	}

	public function get_stage_info($id_stage)
	{
		$q = $this->db->query(
			'SELECT id_stage, kode_stage, nama_tahap, tipe_tahap FROM dbo.M_STAGE WHERE id_stage = ?',
			array((int) $id_stage)
		);
		$row = $q->row_array();
		$q->free_result();
		return $row;
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

	/* ================= SP: Seleksi Lanjutan (Interview, Psikotes, Offer) ============================== */

	public function save_interview(array $in, $oleh_user)
	{
		$id_interview = ! empty($in['id_interview']) ? (int) $in['id_interview'] : NULL;
		$this->_call('{CALL dbo.sp_SaveInterview(?,?,?,?,?,?,?,?,?,?,?)}', array(
			array(&$id_interview, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT),
			(int) $in['id_app_stage'],
			! empty($in['tipe']) ? $in['tipe'] : NULL,
			! empty($in['jadwal']) ? $in['jadwal'] : NULL,
			! empty($in['lokasi_atau_link']) ? $in['lokasi_atau_link'] : NULL,
			! empty($in['hasil']) ? $in['hasil'] : NULL,
			isset($in['skor']) && $in['skor'] !== '' ? (int) $in['skor'] : NULL,
			! empty($in['catatan']) ? $in['catatan'] : NULL,
			! empty($in['id_interviewer']) ? (int) $in['id_interviewer'] : NULL,
			! empty($in['peran_interviewer']) ? $in['peran_interviewer'] : 'HR',
			(int) $oleh_user,
		));
		return (int) $id_interview;
	}

	public function save_psikotes(array $in, $oleh_user)
	{
		$id_psikotes = ! empty($in['id_psikotes']) ? (int) $in['id_psikotes'] : NULL;
		$this->_call('{CALL dbo.sp_SavePsikotes(?,?,?,?,?,?,?,?)}', array(
			array(&$id_psikotes, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT),
			(int) $in['id_app_stage'],
			! empty($in['vendor_tes']) ? $in['vendor_tes'] : NULL,
			! empty($in['tanggal_tes']) ? $in['tanggal_tes'] : NULL,
			isset($in['skor_total']) && $in['skor_total'] !== '' ? (int) $in['skor_total'] : NULL,
			! empty($in['hasil']) ? $in['hasil'] : NULL,
			! empty($in['rekomendasi']) ? $in['rekomendasi'] : NULL,
			(int) $oleh_user,
		));
		return (int) $id_psikotes;
	}

	public function save_offer(array $in, $oleh_user)
	{
		$id_offer = ! empty($in['id_offer']) ? (int) $in['id_offer'] : NULL;
		$this->_call('{CALL dbo.sp_SaveOffer(?,?,?,?,?,?,?,?,?)}', array(
			array(&$id_offer, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT),
			(int) $in['id_lamaran'],
			isset($in['gaji_ditawarkan']) && $in['gaji_ditawarkan'] !== '' ? (float) $in['gaji_ditawarkan'] : NULL,
			! empty($in['tanggal_penawaran']) ? $in['tanggal_penawaran'] : NULL,
			! empty($in['tanggal_join_disepakati']) ? $in['tanggal_join_disepakati'] : NULL,
			! empty($in['tanggal_join_aktual']) ? $in['tanggal_join_aktual'] : NULL,
			! empty($in['status_offer']) ? $in['status_offer'] : 'Nego',
			! empty($in['alasan']) ? $in['alasan'] : NULL,
			(int) $oleh_user,
		));
		return (int) $id_offer;
	}

	public function get_interviewers()
	{
		$q = $this->db->query("SELECT u.id_user, u.nama_snapshot AS nama_lengkap, r.kode_role AS role
		                       FROM dbo.M_USERS u
		                       JOIN dbo.M_ROLES r ON r.id_role = u.id_role
		                       WHERE u.is_aktif = 1
		                       ORDER BY u.nama_snapshot");
		$r = $q->result_array();
		$q->free_result();
		return $r;
	}

	public function get_interviews_for_stages(array $app_stage_ids)
	{
		if (empty($app_stage_ids)) return array();
		$app_stage_ids = array_values(array_map('intval', $app_stage_ids));
		$placeholders = implode(',', array_fill(0, count($app_stage_ids), '?'));
		$sql = "SELECT i.id_interview, i.id_app_stage, i.tipe, i.jadwal, i.lokasi_atau_link, i.hasil, i.skor, i.catatan,
		               ip.id_user AS id_interviewer, ip.peran AS peran_interviewer, u.nama_snapshot AS nama_interviewer
		        FROM dbo.INTERVIEWS i
		        LEFT JOIN dbo.INTERVIEW_PARTICIPANTS ip ON ip.id_interview = i.id_interview
		        LEFT JOIN dbo.M_USERS u ON u.id_user = ip.id_user
		        WHERE i.id_app_stage IN ($placeholders)
		        ORDER BY i.id_interview DESC";
		$q = $this->db->query($sql, $app_stage_ids);
		$rows = $q->result_array();
		$q->free_result();
		$by_stage = array();
		foreach ($rows as $r) {
			foreach ($r as $k => $v) {
				if ($v instanceof DateTime) { $r[$k] = $v->format('Y-m-d H:i'); }
			}
			if ( ! isset($by_stage[$r['id_app_stage']])) {
				$by_stage[$r['id_app_stage']] = array();
			}
			$by_stage[$r['id_app_stage']][] = $r;
		}
		return $by_stage;
	}

	public function get_psikotes_for_stages(array $app_stage_ids)
	{
		if (empty($app_stage_ids)) return array();
		$app_stage_ids = array_values(array_map('intval', $app_stage_ids));
		$placeholders = implode(',', array_fill(0, count($app_stage_ids), '?'));
		$sql = "SELECT p.id_psikotes, p.id_app_stage, p.vendor_tes, p.tanggal_tes, p.skor_total, p.hasil, p.rekomendasi,
		               u.nama_snapshot AS dilakukan_oleh_nama
		        FROM dbo.PSIKOTES_RESULTS p
		        LEFT JOIN dbo.M_USERS u ON u.id_user = p.dilakukan_oleh
		        WHERE p.id_app_stage IN ($placeholders)
		        ORDER BY p.id_psikotes DESC";
		$q = $this->db->query($sql, $app_stage_ids);
		$rows = $q->result_array();
		$q->free_result();
		$by_stage = array();
		foreach ($rows as $r) {
			foreach ($r as $k => $v) {
				if ($v instanceof DateTime) { $r[$k] = $v->format('Y-m-d'); }
			}
			if ( ! isset($by_stage[$r['id_app_stage']])) {
				$by_stage[$r['id_app_stage']] = array();
			}
			$by_stage[$r['id_app_stage']][] = $r;
		}
		return $by_stage;
	}

	public function get_offers_for_lamaran(array $lamaran_ids)
	{
		if (empty($lamaran_ids)) return array();
		$lamaran_ids = array_values(array_map('intval', $lamaran_ids));
		$placeholders = implode(',', array_fill(0, count($lamaran_ids), '?'));
		$sql = "SELECT o.id_offer, o.id_lamaran, o.gaji_ditawarkan, o.tanggal_penawaran, o.tanggal_join_disepakati,
		               o.tanggal_join_aktual, o.status_offer, o.alasan, u.nama_snapshot AS dibuat_oleh_nama
		        FROM dbo.OFFERS o
		        LEFT JOIN dbo.M_USERS u ON u.id_user = o.dibuat_oleh
		        WHERE o.id_lamaran IN ($placeholders)";
		$q = $this->db->query($sql, $lamaran_ids);
		$rows = $q->result_array();
		$q->free_result();
		$by_lamaran = array();
		foreach ($rows as $r) {
			foreach ($r as $k => $v) {
				if ($v instanceof DateTime) { $r[$k] = $v->format('Y-m-d'); }
			}
			$by_lamaran[$r['id_lamaran']] = $r;
		}
		return $by_lamaran;
	}

	public function update_status($id_req, $status_baru, $catatan, $oleh_user)
	{
		$this->_call('{CALL dbo.sp_UpdateRequisitionStatus(?,?,?,?)}', array(
			(int) $id_req, (string) $status_baru, $catatan ?: NULL, (int) $oleh_user
		));
	}

	public function update_catatan_hr($id_req, $catatan_hr, $oleh_user)
	{
		$this->_call('{CALL dbo.sp_UpdateRequisitionCatatanHR(?,?,?)}', array(
			(int) $id_req, $catatan_hr !== '' ? (string) $catatan_hr : NULL, (int) $oleh_user
		));
	}

	public function cancel_requisition($id_req, $alasan, $oleh_user)
	{
		$this->_call('{CALL dbo.sp_CancelRequisition(?,?,?)}', array(
			(int) $id_req, $alasan ?: NULL, (int) $oleh_user
		));
	}

	/**
	 * Batalkan status Hired kandidat — kembalikan ke Withdrawn/Offer_Declined/Rejected.
	 * @return string status_baru setelah pembatalan
	 */
	public function cancel_hired($id_lamaran, $status_tujuan, $alasan, $pic_user, $buka_posting = 1)
	{
		$status_baru = '';
		$stmt = sqlsrv_query(
			$this->db->conn_id,
			'{CALL dbo.sp_CancelHired(?,?,?,?,?,?)}',
			array(
				(int) $id_lamaran,
				$status_tujuan ?: 'Withdrawn',
				$alasan ?: NULL,
				(int) $pic_user,
				$buka_posting ? 1 : 0,
				array(&$status_baru, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR))
			)
		);
		if ($stmt === FALSE) {
			$e = sqlsrv_errors();
			$last = $e ? end($e) : NULL;
			throw new RuntimeException($last ? trim($last['message']) : 'Gagal membatalkan status Hired.');
		}
		do { /* nothing */ } while (sqlsrv_next_result($stmt));
		sqlsrv_free_stmt($stmt);
		return $status_baru ?: $status_tujuan;
	}

	/**
	 * Mengambil kandidat berstatus final (Rejected, Hired, Withdrawn, Offer_Declined, No_Show)
	 * untuk satu requisition / MPR.
	 */
	public function final_candidates($id_req)
	{
		$sql = "SELECT a.id_lamaran, a.id_kandidat, a.id_req, a.status_global, a.tanggal_lamar,
		               c.nama_lengkap, c.no_wa_normal, c.email,
		               s.nama_tahap AS tahap_terakhir,
		               rm.label AS label_remark_terakhir
		        FROM dbo.APPLICATIONS a
		        JOIN dbo.CANDIDATES c        ON c.id_kandidat = a.id_kandidat
		        LEFT JOIN dbo.M_STAGE s      ON s.id_stage = a.id_stage_sekarang
		        LEFT JOIN dbo.M_REMARKS rm   ON rm.id_remark = a.id_remark_terakhir
		        WHERE a.id_req = ?
		          AND a.status_global IN ('Hired','Rejected','Withdrawn','Offer_Declined','No_Show')
		        ORDER BY a.id_lamaran DESC";
		$q = $this->db->query($sql, array((int) $id_req));
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}
}
