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
			NULL, // id_channel dilepas
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
	public function channels()    { return array(); }
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
			'NULL AS nama_channel', 'sglobal.nama_tahap AS tahap_kini',
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

	/* =================== KHUSUS ROLE: USER_DEPT ========================= */

	/**
	 * Mengambil daftar pengajuan MPR terbaru milik departemen tertentu
	 */
	public function dept_requisitions($id_dept, $limit = 6)
	{
		$limit = (int) $limit;
		$sql = "SELECT TOP $limit r.id_req, r.no_mpr, r.status_req, r.tipe_penempatan,
		               r.jumlah_dibutuhkan, r.jumlah_disetujui, r.jumlah_terpenuhi,
		               r.tanggal_pengajuan, p.nama_posisi, o.nama_outlet
		        FROM dbo.REQUISITIONS r
		        JOIN dbo.M_POSISI p      ON p.id_posisi = r.id_posisi
		        LEFT JOIN dbo.M_OUTLET o ON o.id_outlet = r.id_outlet
		        WHERE p.id_departemen = ?
		        ORDER BY r.id_req DESC";
		$q = $this->db->query($sql, array((int) $id_dept));
		$rows = $q->result_array();
		$q->free_result();
		foreach ($rows as &$r) {
			if ($r['tanggal_pengajuan'] instanceof DateTime) {
				$r['tanggal_pengajuan'] = $r['tanggal_pengajuan']->format('Y-m-d');
			}
		}
		return $rows;
	}

	/**
	 * Mengambil kandidat aktif yang sedang dalam proses seleksi untuk departemen tertentu
	 */
	public function dept_candidates_active($id_dept, $limit = 8)
	{
		$limit = (int) $limit;
		$sql = "SELECT TOP $limit a.id_lamaran, a.id_req, a.status_global, a.tanggal_lamar,
		               c.nama_lengkap, c.no_wa_normal, p.nama_posisi,
		               st.nama_tahap AS tahap_kini, st.tipe_tahap
		        FROM dbo.APPLICATIONS a
		        JOIN dbo.CANDIDATES c   ON c.id_kandidat = a.id_kandidat
		        JOIN dbo.REQUISITIONS r ON r.id_req = a.id_req
		        JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
		        LEFT JOIN dbo.M_STAGE st ON st.id_stage = a.id_stage_sekarang
		        WHERE p.id_departemen = ?
		          AND a.status_global IN ('In_Progress', 'On_Hold')
		        ORDER BY a.id_lamaran DESC";
		$q = $this->db->query($sql, array((int) $id_dept));
		$rows = $q->result_array();
		$q->free_result();
		foreach ($rows as &$r) {
			if ($r['tanggal_lamar'] instanceof DateTime) {
				$r['tanggal_lamar'] = $r['tanggal_lamar']->format('Y-m-d');
			}
		}
		return $rows;
	}

	/**
	 * Mengambil ringkasan metrik kuota dan pemenuhan untuk departemen
	 */
	public function dept_summary_metrics($id_dept)
	{
		$sql = "SELECT
		            COUNT(*) AS total_mpr,
		            ISNULL(SUM(jumlah_dibutuhkan), 0) AS total_dibutuhkan,
		            ISNULL(SUM(jumlah_terpenuhi), 0) AS total_terpenuhi,
		            ISNULL(SUM(CASE WHEN status_req IN ('Draft','Review_HR','Menunggu_BOD') THEN 1 ELSE 0 END), 0) AS mpr_pending,
		            ISNULL(SUM(CASE WHEN status_req IN ('Approved','Sourcing') THEN 1 ELSE 0 END), 0) AS mpr_aktif,
		            ISNULL(SUM(CASE WHEN status_req = 'Terpenuhi' THEN 1 ELSE 0 END), 0) AS mpr_selesai
		        FROM dbo.REQUISITIONS r
		        JOIN dbo.M_POSISI p ON p.id_posisi = r.id_posisi
		        WHERE p.id_departemen = ?";
		$q = $this->db->query($sql, array((int) $id_dept));
		$row = $q->row_array();
		$q->free_result();
		return $row ?: array(
			'total_mpr' => 0, 'total_dibutuhkan' => 0, 'total_terpenuhi' => 0,
			'mpr_pending' => 0, 'mpr_aktif' => 0, 'mpr_selesai' => 0
		);
	}

	/**
	 * Matriks Funnel Dinamis: Posisi yang Dibuka (vertikal) x Tahapan Seleksi (horizontal).
	 * Hanya posisi dan tahapan yang memiliki pembaruan/kandidat pada filter tanggal yang dimasukkan.
	 * Mendukung filter tanggal tunggal ("sesuai tanggal saja") maupun rentang tanggal ("range tanggal").
	 */
	public function position_stage_funnel(array $f)
	{
		$dari   = ! empty($f['dari']) ? $f['dari'] : NULL;
		$sampai = ! empty($f['sampai']) ? $f['sampai'] : NULL;

		// Jika pengguna hanya memilih 1 tanggal pada 'dari', jadikan filter tanggal tunggal
		if ($dari !== NULL && $sampai === NULL) {
			$sampai = $dari;
		} elseif ($dari === NULL && $sampai !== NULL) {
			$dari = $sampai;
		}

		$where = "WHERE r.status_req NOT IN ('Dibatalkan')";
		$params = array();

		// Filter tanggal (tanggal lamar, awal/akhir tahap, atau jejak riwayat mutasi)
		if ($dari !== NULL && $sampai !== NULL) {
			$where .= " AND (
				(a.tanggal_lamar >= ? AND a.tanggal_lamar <= ?)
				OR (aps.tanggal_mulai IS NOT NULL AND CAST(aps.tanggal_mulai AS DATE) >= ? AND CAST(aps.tanggal_mulai AS DATE) <= ?)
				OR (aps.tanggal_selesai IS NOT NULL AND CAST(aps.tanggal_selesai AS DATE) >= ? AND CAST(aps.tanggal_selesai AS DATE) <= ?)
				OR EXISTS (
					SELECT 1 FROM dbo.APPLICATION_HISTORY ah
					WHERE ah.id_lamaran = a.id_lamaran
					  AND CAST(ah.waktu AS DATE) >= ? AND CAST(ah.waktu AS DATE) <= ?
				)
			)";
			$params[] = $dari; $params[] = $sampai;
			$params[] = $dari; $params[] = $sampai;
			$params[] = $dari; $params[] = $sampai;
			$params[] = $dari; $params[] = $sampai;
		}

		if ( ! empty($f['dept'])) {
			$where .= " AND p.id_departemen = ?";
			$params[] = (int) $f['dept'];
		}
		if ( ! empty($f['posisi'])) {
			$where .= " AND p.id_posisi = ?";
			$params[] = (int) $f['posisi'];
		}
		if ( ! empty($f['outlet'])) {
			$where .= " AND r.id_outlet = ?";
			$params[] = (int) $f['outlet'];
		}
		if ( ! empty($f['status'])) {
			$where .= " AND a.status_global = ?";
			$params[] = $f['status'];
		}

		$sql = "SELECT
					p.id_posisi,
					p.nama_posisi,
					d.nama AS nama_departemen,
					MAX(r.id_req) AS id_req,
					s.id_stage,
					s.nama_tahap,
					s.tipe_tahap,
					MIN(COALESCE(fs.urutan, s.id_stage)) AS urutan,
					a.status_global,
					COUNT(DISTINCT a.id_lamaran) AS jumlah
				FROM dbo.APPLICATIONS a
				JOIN dbo.REQUISITIONS r        ON r.id_req = a.id_req
				JOIN dbo.M_POSISI p            ON p.id_posisi = r.id_posisi
				LEFT JOIN dbo.M_DEPARTEMEN d   ON d.id_departemen = p.id_departemen
				LEFT JOIN dbo.APPLICATION_STAGES aps ON aps.id_lamaran = a.id_lamaran AND aps.id_stage = a.id_stage_sekarang
				JOIN dbo.M_STAGE s             ON s.id_stage = a.id_stage_sekarang
				LEFT JOIN dbo.M_FLOW_STAGE fs  ON fs.id_flow = a.id_flow AND fs.id_stage = s.id_stage
				$where
				GROUP BY p.id_posisi, p.nama_posisi, d.nama, s.id_stage, s.nama_tahap, s.tipe_tahap, a.status_global
				ORDER BY p.nama_posisi, urutan";

		$q = $this->db->query($sql, $params);
		$rows = $q->result_array();
		$q->free_result();

		$positions = array();
		$stages    = array();
		$matrix    = array();
		$total_all = 0;

		foreach ($rows as $r) {
			$pos_id   = (int) $r['id_posisi'];
			$stage_id = (int) $r['id_stage'];
			$jml      = (int) $r['jumlah'];
			$st_glob  = $r['status_global'];

			if ( ! isset($positions[$pos_id])) {
				$positions[$pos_id] = array(
					'id_posisi'   => $pos_id,
					'nama_posisi' => $r['nama_posisi'],
					'id_req'      => (int) $r['id_req'],
					'departemen'  => $r['nama_departemen'] ?: '',
					'total'       => 0,
				);
			}
			$positions[$pos_id]['total'] += $jml;

			if ( ! isset($stages[$stage_id])) {
				$stages[$stage_id] = array(
					'id_stage'   => $stage_id,
					'nama_tahap' => $r['nama_tahap'],
					'tipe_tahap' => $r['tipe_tahap'],
					'urutan'     => (int) $r['urutan'],
					'total'      => 0,
				);
			}
			$stages[$stage_id]['total'] += $jml;

			if ( ! isset($matrix[$pos_id][$stage_id])) {
				$matrix[$pos_id][$stage_id] = array(
					'total'    => 0,
					'statuses' => array(),
				);
			}
			$matrix[$pos_id][$stage_id]['total'] += $jml;
			$matrix[$pos_id][$stage_id]['statuses'][$st_glob] =
				($matrix[$pos_id][$stage_id]['statuses'][$st_glob] ?? 0) + $jml;

			$total_all += $jml;
		}

		// Urutkan kolom tahapan berdasarkan urutan flow / id_stage
		uasort($stages, function ($a, $b) {
			if ($a['urutan'] === $b['urutan']) {
				return $a['id_stage'] <=> $b['id_stage'];
			}
			return $a['urutan'] <=> $b['urutan'];
		});

		// Urutkan baris posisi berdasarkan nama_posisi
		uasort($positions, function ($a, $b) {
			return strcasecmp($a['nama_posisi'], $b['nama_posisi']);
		});

		return array(
			'positions' => $positions,
			'stages'    => $stages,
			'matrix'    => $matrix,
			'total_all' => $total_all,
			'dari'      => $dari,
			'sampai'    => $sampai,
		);
	}
}
