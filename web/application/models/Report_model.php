<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model Report_model -- Model Agregasi Laporan Analitik & Funnel Rekrutmen
 *
 * Fungsi:
 * - Menghitung tingkat konversi tahapan seleksi (Conversion Rate Funnel) dari pelamar masuk hingga diterima.
 * - Mengukur durasi siklus rekrutmen (Time-to-Hire) per posisi dan departemen.
 * - Menganalisis alasan penolakan kandidat per tahap seleksi.
 * - Mengagregasi metrik pemenuhan formasi Requisition (MPR) aktif vs kuota yang terpenuhi.
 * - Kueri kompatibel penuh dengan batasan T-SQL Microsoft SQL Server 2008 R2.
 */
class Report_model extends CI_Model
{
	/* ---- Helper filter dinamis ---- */
	private function _apply_filter_where($f, &$params, $table_alias = 'a')
	{
		$where = 'WHERE 1=1';
		$dept = current_user_dept();

		if ($dept !== NULL) {
			$where .= ' AND p.id_departemen = ?';
			$params[] = (int) $dept;
		} elseif (!empty($f['dept'])) {
			$where .= ' AND p.id_departemen = ?';
			$params[] = (int) $f['dept'];
		}

		if (!empty($f['posisi'])) {
			$where .= ' AND r.id_posisi = ?';
			$params[] = (int) $f['posisi'];
		}

		if (!empty($f['outlet'])) {
			$where .= ' AND r.id_outlet = ?';
			$params[] = (int) $f['outlet'];
		}

		if (!empty($f['dari'])) {
			$where .= " AND $table_alias.tanggal_lamar >= ?";
			$params[] = (string) $f['dari'];
		}

		if (!empty($f['sampai'])) {
			$where .= " AND $table_alias.tanggal_lamar < DATEADD(DAY, 1, ?)";
			$params[] = (string) $f['sampai'];
		}

		if (!empty($f['status_mpr'])) {
			if ($f['status_mpr'] === 'BUKA') {
				$where .= " AND r.status_req IN ('Sourcing', 'Approved', 'Sourcing_Ulang')";
			} elseif ($f['status_mpr'] === 'TUTUP') {
				$where .= " AND r.status_req IN ('Terpenuhi', 'Terpenuhi_Sebagian', 'Ditolak_HR', 'Ditolak_BOD', 'Dibatalkan', 'Kadaluarsa')";
			}
		}

		return $where;
	}

	/**
	 * KPI Utama: Total pelamar, aktif seleksi, hired, ditolak/batal, waktu rata-rata, dsb.
	 */
	public function get_kpi_summary($f = array())
	{
		$params = array();
		$where = $this->_apply_filter_where($f, $params, 'a');

		$sql = "SELECT
					COUNT(*) AS total_pelamar,
					SUM(CASE WHEN a.status_global = 'In_Progress' THEN 1 ELSE 0 END) AS n_in_progress,
					SUM(CASE WHEN a.status_global = 'Hired' THEN 1 ELSE 0 END) AS n_hired,
					SUM(CASE WHEN a.status_global = 'Rejected' THEN 1 ELSE 0 END) AS n_rejected,
					SUM(CASE WHEN a.status_global IN ('Withdrawn', 'Offer_Declined', 'No_Show') THEN 1 ELSE 0 END) AS n_withdrawn,
					AVG(CASE WHEN a.status_global = 'Hired' AND a.tanggal_lamar IS NOT NULL
					         THEN CAST(DATEDIFF(DAY, a.tanggal_lamar, GETDATE()) AS FLOAT)
					         ELSE NULL END) AS avg_time_to_hire_days
				FROM dbo.APPLICATIONS a
				JOIN dbo.REQUISITIONS r ON r.id_req = a.id_req
				JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
				$where";

		$q = $this->db->query($sql, $params);
		$row = $q->row_array() ?: array();
		$q->free_result();

		// Agregat Formasi MPR
		$mpr_params = array();
		$mpr_where = 'WHERE 1=1';
		$dept = current_user_dept();
		if ($dept !== NULL) {
			$mpr_where .= ' AND p.id_departemen = ?';
			$mpr_params[] = (int) $dept;
		} elseif (!empty($f['dept'])) {
			$mpr_where .= ' AND p.id_departemen = ?';
			$mpr_params[] = (int) $f['dept'];
		}
		if (!empty($f['posisi'])) {
			$mpr_where .= ' AND r.id_posisi = ?';
			$mpr_params[] = (int) $f['posisi'];
		}
		if (!empty($f['outlet'])) {
			$mpr_where .= ' AND r.id_outlet = ?';
			$mpr_params[] = (int) $f['outlet'];
		}
		if (!empty($f['dari'])) {
			$mpr_where .= ' AND r.tanggal_pengajuan >= ?';
			$mpr_params[] = (string) $f['dari'];
		}
		if (!empty($f['sampai'])) {
			$mpr_where .= ' AND r.tanggal_pengajuan < DATEADD(DAY, 1, ?)';
			$mpr_params[] = (string) $f['sampai'];
		}

		$sql_mpr = "SELECT
						COUNT(*) AS total_mpr,
						SUM(ISNULL(r.jumlah_dibutuhkan, 0)) AS total_target_orang,
						SUM(ISNULL(r.jumlah_disetujui, 0)) AS total_disetujui_orang,
						SUM(ISNULL(r.jumlah_terpenuhi, 0)) AS total_terpenuhi_orang,
						SUM(CASE WHEN r.status_req IN ('Sourcing', 'Approved', 'Sourcing_Ulang') THEN 1 ELSE 0 END) AS mpr_aktif,
						SUM(CASE WHEN r.status_req = 'Terpenuhi' THEN 1 ELSE 0 END) AS mpr_selesai
					FROM dbo.REQUISITIONS r
					JOIN dbo.M_POSISI p ON p.id_posisi = r.id_posisi
					$mpr_where";

		$q_mpr = $this->db->query($sql_mpr, $mpr_params);
		$row_mpr = $q_mpr->row_array() ?: array();
		$q_mpr->free_result();

		return array_merge($row, $row_mpr);
	}

	/**
	 * Funnel Rekrutmen berdasarkan 7 Tipe Tahap Tetap:
	 * SCREENING, KONTAK, FORM, TEST, INTERVIEW, OFFER, ONBOARD + HIRED
	 */
	public function get_funnel_stages($f = array())
	{
		$params = array();
		$where = $this->_apply_filter_where($f, $params, 'a');

		$sql = "SELECT
					ISNULL(st.tipe_tahap, 'BELUM_DITENTUKAN') AS tipe_tahap,
					COUNT(a.id_lamaran) AS jumlah_pelamar,
					SUM(CASE WHEN a.status_global = 'Hired' THEN 1 ELSE 0 END) AS n_hired,
					SUM(CASE WHEN a.status_global = 'In_Progress' THEN 1 ELSE 0 END) AS n_active
				FROM dbo.APPLICATIONS a
				JOIN dbo.REQUISITIONS r ON r.id_req = a.id_req
				JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
				LEFT JOIN dbo.M_STAGE st ON st.id_stage = a.id_stage_sekarang
				$where
				GROUP BY st.tipe_tahap";

		$q = $this->db->query($sql, $params);
		$rows = $q->result_array();
		$q->free_result();

		$order = array('SCREENING', 'KONTAK', 'FORM', 'TEST', 'INTERVIEW', 'OFFER', 'ONBOARD');
		$map = array();
		foreach ($rows as $r) {
			$map[$r['tipe_tahap']] = (int) $r['jumlah_pelamar'];
		}

		$result = array();
		foreach ($order as $tipe) {
			$result[] = array(
				'tipe'   => $tipe,
				'jumlah' => $map[$tipe] ?? 0,
			);
		}
		return $result;
	}

	/**
	 * Posisi dengan Peminat / Berkas Pelamar Terbanyak
	 */
	public function get_top_positions($f = array(), $limit = 7)
	{
		$limit = (int) $limit;
		$params = array();
		$where = $this->_apply_filter_where($f, $params, 'a');

		$sql = "SELECT TOP $limit
					p.id_posisi,
					p.nama_posisi,
					d.nama AS nama_departemen,
					COUNT(a.id_lamaran) AS total_pelamar,
					SUM(CASE WHEN a.status_global = 'In_Progress' THEN 1 ELSE 0 END) AS n_aktif,
					SUM(CASE WHEN a.status_global = 'Hired' THEN 1 ELSE 0 END) AS n_hired
				FROM dbo.APPLICATIONS a
				JOIN dbo.REQUISITIONS r ON r.id_req = a.id_req
				JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
				LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
				$where
				GROUP BY p.id_posisi, p.nama_posisi, d.nama
				ORDER BY total_pelamar DESC";

		$q = $this->db->query($sql, $params);
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	/**
	 * Distribusi Status Lamaran Global (Pie / Bar Chart Breakdown)
	 */
	public function get_status_distribution($f = array())
	{
		$params = array();
		$where = $this->_apply_filter_where($f, $params, 'a');

		$sql = "SELECT
					a.status_global,
					COUNT(a.id_lamaran) AS jumlah
				FROM dbo.APPLICATIONS a
				JOIN dbo.REQUISITIONS r ON r.id_req = a.id_req
				JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
				$where
				GROUP BY a.status_global
				ORDER BY jumlah DESC";

		$q = $this->db->query($sql, $params);
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	/**
	 * Tren Pendaftaran Bulanan (6 Bulan Terakhir atau sesuai filter)
	 */
	public function get_monthly_trend($f = array())
	{
		$params = array();
		$where = $this->_apply_filter_where($f, $params, 'a');

		$sql = "SELECT
					CONVERT(VARCHAR(7), a.tanggal_lamar, 120) AS periode_bulan,
					COUNT(a.id_lamaran) AS total_daftar,
					SUM(CASE WHEN a.status_global = 'Hired' THEN 1 ELSE 0 END) AS total_hired
				FROM dbo.APPLICATIONS a
				JOIN dbo.REQUISITIONS r ON r.id_req = a.id_req
				JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
				$where
				GROUP BY CONVERT(VARCHAR(7), a.tanggal_lamar, 120)
				ORDER BY periode_bulan ASC";

		$q = $this->db->query($sql, $params);
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	/**
	 * Rekap Detail Status Pemenuhan MPR (Tabel Requisition vs Target vs Terpenuhi)
	 */
	public function get_mpr_fulfillment_report($f = array())
	{
		$params = array();
		$where = 'WHERE 1=1';
		$dept = current_user_dept();

		if ($dept !== NULL) {
			$where .= ' AND p.id_departemen = ?';
			$params[] = (int) $dept;
		} elseif (!empty($f['dept'])) {
			$where .= ' AND p.id_departemen = ?';
			$params[] = (int) $f['dept'];
		}

		if (!empty($f['posisi'])) {
			$where .= ' AND r.id_posisi = ?';
			$params[] = (int) $f['posisi'];
		}

		if (!empty($f['outlet'])) {
			$where .= ' AND r.id_outlet = ?';
			$params[] = (int) $f['outlet'];
		}

		if (!empty($f['status_mpr'])) {
			if ($f['status_mpr'] === 'BUKA') {
				$where .= " AND r.status_req IN ('Sourcing', 'Approved', 'Sourcing_Ulang')";
			} elseif ($f['status_mpr'] === 'TUTUP') {
				$where .= " AND r.status_req IN ('Terpenuhi', 'Terpenuhi_Sebagian', 'Ditolak_HR', 'Ditolak_BOD', 'Dibatalkan', 'Kadaluarsa')";
			}
		}

		if (!empty($f['dari'])) {
			$where .= ' AND r.tanggal_pengajuan >= ?';
			$params[] = (string) $f['dari'];
		}

		if (!empty($f['sampai'])) {
			$where .= ' AND r.tanggal_pengajuan < DATEADD(DAY, 1, ?)';
			$params[] = (string) $f['sampai'];
		}

		$sql = "SELECT
					r.id_req,
					r.no_mpr,
					r.status_req,
					r.tipe_penempatan,
					r.jumlah_dibutuhkan,
					r.jumlah_disetujui,
					r.jumlah_terpenuhi,
					r.tanggal_pengajuan,
					p.nama_posisi,
					d.nama AS nama_departemen,
					o.nama_outlet,
					(SELECT COUNT(*) FROM dbo.APPLICATIONS a WHERE a.id_req = r.id_req) AS total_kandidat,
					(SELECT COUNT(*) FROM dbo.APPLICATIONS a WHERE a.id_req = r.id_req AND a.status_global = 'In_Progress') AS total_in_progress,
					(SELECT COUNT(*) FROM dbo.APPLICATIONS a WHERE a.id_req = r.id_req AND a.status_global = 'Hired') AS total_hired
				FROM dbo.REQUISITIONS r
				JOIN dbo.M_POSISI p          ON p.id_posisi = r.id_posisi
				LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
				LEFT JOIN dbo.M_OUTLET o     ON o.id_outlet = r.id_outlet
				$where
				ORDER BY r.id_req DESC";

		$q = $this->db->query($sql, $params);
		$rows = $q->result_array();
		$q->free_result();

		foreach ($rows as &$r) {
			if ($r['tanggal_pengajuan'] instanceof DateTime) {
				$r['tanggal_pengajuan'] = $r['tanggal_pengajuan']->format('Y-m-d');
			}
			$target = max(1, (int) ($r['jumlah_disetujui'] ?: $r['jumlah_dibutuhkan']));
			$terpenuhi = (int) $r['jumlah_terpenuhi'];
			$r['persen_terpenuhi'] = min(100, (int) round(($terpenuhi / $target) * 100));
			$r['sisa_kebutuhan']   = max(0, $target - $terpenuhi);
		}

		return $rows;
	}

	/**
	 * Rekap Agregat per Departemen (Tabel Total MPR, Target, Terpenuhi, Total Pelamar)
	 */
	public function get_department_summary($f = array())
	{
		$params = array();
		$where = 'WHERE 1=1';
		$dept = current_user_dept();

		if ($dept !== NULL) {
			$where .= ' AND p.id_departemen = ?';
			$params[] = (int) $dept;
		} elseif (!empty($f['dept'])) {
			$where .= ' AND p.id_departemen = ?';
			$params[] = (int) $f['dept'];
		}

		if (!empty($f['dari'])) {
			$where .= ' AND r.tanggal_pengajuan >= ?';
			$params[] = (string) $f['dari'];
		}
		if (!empty($f['sampai'])) {
			$where .= ' AND r.tanggal_pengajuan < DATEADD(DAY, 1, ?)';
			$params[] = (string) $f['sampai'];
		}

		$sql = "SELECT
					d.id_departemen,
					d.nama AS nama_departemen,
					COUNT(DISTINCT r.id_req) AS total_mpr,
					SUM(ISNULL(r.jumlah_disetujui, r.jumlah_dibutuhkan)) AS total_target,
					SUM(ISNULL(r.jumlah_terpenuhi, 0)) AS total_terpenuhi,
					(SELECT COUNT(*) FROM dbo.APPLICATIONS a
					 JOIN dbo.REQUISITIONS r2 ON r2.id_req = a.id_req
					 JOIN dbo.M_POSISI p2     ON p2.id_posisi = r2.id_posisi
					 WHERE p2.id_departemen = d.id_departemen) AS total_pelamar,
					(SELECT COUNT(*) FROM dbo.APPLICATIONS a
					 JOIN dbo.REQUISITIONS r2 ON r2.id_req = a.id_req
					 JOIN dbo.M_POSISI p2     ON p2.id_posisi = r2.id_posisi
					 WHERE p2.id_departemen = d.id_departemen AND a.status_global = 'Hired') AS total_hired
				FROM dbo.M_DEPARTEMEN d
				LEFT JOIN dbo.M_POSISI p ON p.id_departemen = d.id_departemen
				LEFT JOIN dbo.REQUISITIONS r ON r.id_posisi = p.id_posisi
				$where
				GROUP BY d.id_departemen, d.nama
				ORDER BY total_pelamar DESC";

		$q = $this->db->query($sql, $params);
		$rows = $q->result_array();
		$q->free_result();

		foreach ($rows as &$r) {
			$target = max(1, (int) $r['total_target']);
			$terpenuhi = (int) $r['total_terpenuhi'];
			$r['persen'] = min(100, (int) round(($terpenuhi / $target) * 100));
		}

		return $rows;
	}

	/**
	 * Opsi dropdown filter
	 */
	public function get_departments()
	{
		return $this->db->query('SELECT id_departemen, nama FROM dbo.M_DEPARTEMEN WHERE is_aktif = 1 ORDER BY nama')->result_array();
	}

	public function get_positions($id_dept = NULL)
	{
		$where = 'WHERE is_aktif = 1';
		$p = array();
		if ($id_dept !== NULL) {
			$where .= ' AND id_departemen = ?';
			$p[] = (int) $id_dept;
		}
		return $this->db->query("SELECT id_posisi, nama_posisi FROM dbo.M_POSISI $where ORDER BY nama_posisi", $p)->result_array();
	}

	public function get_outlets()
	{
		return $this->db->query('SELECT id_outlet, nama_outlet FROM dbo.M_OUTLET WHERE is_aktif = 1 ORDER BY nama_outlet')->result_array();
	}
}
