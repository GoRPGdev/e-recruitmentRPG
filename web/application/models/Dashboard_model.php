<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard_model -- data papan dashboard + trend funnel + export kandidat.
 */
class Dashboard_model extends CI_Model
{
	/**
	 * @return array ['metrik'=>[], 'funnel'=>[], 'aging'=>[], 'waktu'=>[]]
	 */
	public function dashboard(array $f)
	{
		$params = array(
			$f['dari'] ?: NULL, $f['sampai'] ?: NULL,
			! empty($f['dept']) ? (int) $f['dept'] : NULL,
			! empty($f['posisi']) ? (int) $f['posisi'] : NULL,
			! empty($f['outlet']) ? (int) $f['outlet'] : NULL,
			! empty($f['flow']) ? (int) $f['flow'] : NULL,
			$f['tipe_tahap'] ?: NULL,
			$f['status'] ?: NULL,
			! empty($f['channel']) ? (int) $f['channel'] : NULL,
			$f['pic'] ?: NULL,
		);
		$stmt = sqlsrv_query($this->db->conn_id, '{CALL dbo.sp_Dashboard(?,?,?,?,?,?,?,?,?,?)}', $params);
		if ($stmt === FALSE) {
			$e = sqlsrv_errors(); $last = $e ? end($e) : NULL;
			throw new RuntimeException($last ? trim($last['message']) : 'sp_Dashboard gagal.');
		}
		$sets = array();
		do {
			$rows = array();
			while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
				foreach ($r as $k => $v) { if ($v instanceof DateTime) { $r[$k] = $v->format('Y-m-d H:i'); } }
				$rows[] = $r;
			}
			$sets[] = $rows;
		} while (sqlsrv_next_result($stmt));
		sqlsrv_free_stmt($stmt);

		return array(
			'metrik' => isset($sets[0]) ? $sets[0] : array(),
			'funnel' => isset($sets[1]) ? $sets[1] : array(),
			'aging'  => isset($sets[2]) ? $sets[2] : array(),
			'waktu'  => isset($sets[3][0]) ? $sets[3][0] : array('rata_lama_proses_hari' => NULL, 'rata_hari_menunggu_approval' => NULL),
		);
	}

	public function funnel_trend($days = 14, $id_dept = NULL)
	{
		$where = 'WHERE f.tanggal >= DATEADD(DAY, ?, CAST(GETDATE() AS DATE))';
		$params = array(-1 * (int) $days);
		if ($id_dept !== NULL) {
			$where .= ' AND pos.id_departemen = ?';
			$params[] = (int) $id_dept;
		}
		$q = $this->db->query(
			"SELECT f.tanggal, f.tipe_tahap, SUM(f.jumlah) AS jumlah
			 FROM dbo.RPT_FUNNEL_HARIAN f
			 JOIN dbo.REQUISITIONS r ON r.id_req = f.id_req
			 JOIN dbo.M_POSISI pos   ON pos.id_posisi = r.id_posisi
			 $where
			 GROUP BY f.tanggal, f.tipe_tahap ORDER BY f.tanggal, f.tipe_tahap",
			$params);
		$rows = $q->result_array(); $q->free_result();
		foreach ($rows as &$r) { $r['tanggal'] = substr($r['tanggal'], 0, 10); }
		return $rows;
	}

	/* ---- opsi filter ---- */
	public function opt($sql) { return $this->db->query($sql)->result_array(); }
	public function departments() { return $this->opt("SELECT id_departemen, nama FROM dbo.M_DEPARTEMEN WHERE is_aktif=1 ORDER BY nama"); }
	public function positions($id_dept = NULL)
	{
		$where = 'WHERE is_aktif=1';
		$params = array();
		if ($id_dept !== NULL) {
			$where .= ' AND id_departemen = ?';
			$params[] = (int) $id_dept;
		}
		return $this->db->query("SELECT id_posisi, nama_posisi FROM dbo.M_POSISI $where ORDER BY nama_posisi", $params)->result_array();
	}
	public function outlets()     { return $this->opt("SELECT id_outlet, nama_outlet FROM dbo.M_OUTLET WHERE is_aktif=1 ORDER BY nama_outlet"); }
	public function flows()       { return $this->opt("SELECT id_flow, kode_flow FROM dbo.M_FLOW WHERE is_aktif=1 ORDER BY kode_flow"); }
	public function channels()    { return $this->opt("SELECT id_channel, nama_channel FROM dbo.M_CHANNEL WHERE is_aktif=1 ORDER BY nama_channel"); }
	public function roles()       { return $this->opt("SELECT kode_role FROM dbo.M_ROLES WHERE is_aktif=1 ORDER BY kode_role"); }

	/* =================== EXPORT KANDIDAT ========================= */

	/**
	 * Baris kandidat/lamaran untuk export. Kolom sensitif hanya disertakan
	 * bila $perms mengizinkan (RBAC).
	 * @param array $perms daftar kode permission user
	 */
	public function candidates_export(array $f, array $perms)
	{
		$sel = array(
			'a.id_lamaran', 'c.nama_lengkap', 'c.no_wa_normal', 'c.email',
			'c.kota_domisili', 'c.pendidikan_terakhir',
			'pos.nama_posisi', 'r.no_mpr', 'a.status_global', 'a.intake_method',
			'ch.nama_channel', 'sglobal.nama_tahap AS tahap_kini',
			'a.screening_score', 'a.tanggal_lamar',
		);
		if (in_array('LIHAT_GAJI_PELAMAR', $perms, TRUE)) {
			$sel[] = 'ap.gaji_terakhir';
			$sel[] = 'ap.gaji_diharapkan';
		}
		if (in_array('LIHAT_FINANSIAL', $perms, TRUE)) {
			$sel[] = 'cb.no_rekening';
			$sel[] = 'cb.nama_bank';
		}

		$sql = 'SELECT ' . implode(', ', $sel) . '
		        FROM dbo.APPLICATIONS a
		        JOIN dbo.CANDIDATES c    ON c.id_kandidat = a.id_kandidat
		        JOIN dbo.REQUISITIONS r  ON r.id_req = a.id_req
		        JOIN dbo.M_POSISI pos    ON pos.id_posisi = r.id_posisi
		        LEFT JOIN dbo.M_CHANNEL ch ON ch.id_channel = a.id_channel
		        LEFT JOIN dbo.M_STAGE sglobal ON sglobal.id_stage = a.id_stage_sekarang
		        LEFT JOIN dbo.APPLICATION_PROFILE ap ON ap.id_lamaran = a.id_lamaran
		        LEFT JOIN dbo.CANDIDATE_BANK cb ON cb.id_lamaran = a.id_lamaran
		        WHERE 1=1';
		$b = array();
		if ( ! empty($f['dari']))   { $sql .= ' AND a.tanggal_lamar >= ?'; $b[] = $f['dari']; }
		if ( ! empty($f['sampai'])) { $sql .= ' AND a.tanggal_lamar <= ?'; $b[] = $f['sampai']; }
		if ( ! empty($f['posisi'])) { $sql .= ' AND r.id_posisi = ?'; $b[] = (int) $f['posisi']; }
		if ( ! empty($f['status'])) { $sql .= ' AND a.status_global = ?'; $b[] = $f['status']; }
		if ( ! empty($f['flow']))   { $sql .= ' AND a.id_flow = ?'; $b[] = (int) $f['flow']; }
		if ( ! empty($f['dept']))   { $sql .= ' AND pos.id_departemen = ?'; $b[] = (int) $f['dept']; }
		$sql .= ' ORDER BY a.id_lamaran DESC';

		$q = $this->db->query($sql, $b);
		$rows = $q->result_array(); $q->free_result();
		return $rows;
	}
}
