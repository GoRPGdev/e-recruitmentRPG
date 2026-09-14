<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model Posting_model -- Model Publikasi Lowongan Kerja & Manajemen Token Berkas
 *
 * Fungsi:
 * - Menangani pembuatan dan pengaturan lowongan form publik berbasis URL slug via sp_CreatePosting.
 * - Mengatur aktivasi buka/tutup form lamaran online per batch lowongan.
 * - Mengelola penerbitan dan resolusi token pengunggahan berkas formulir pelamar.
 * - Menerapkan paginasi berbasis CTE ROW_NUMBER() sesuai standar SQL Server 2008 R2.
 */
class Posting_model extends CI_Model
{
	/* ================= JOB_POSTINGS / link form ===================== */

	public function count_postings()
	{
		return (int) $this->db->query('SELECT COUNT(*) AS n FROM dbo.JOB_POSTINGS')->row()->n;
	}

	/**
	 * @param int $offset baris ke- (1-based) awal
	 * @param int $per    jumlah per halaman
	 */
	public function list_postings($offset, $per)
	{
		$sql = 'WITH q AS (
		            SELECT jp.id_posting, jp.id_req, jp.url_slug, jp.judul_posting, jp.is_aktif,
		                   jp.form_aktif, jp.form_dibuka, jp.form_ditutup, jp.jumlah_submit, jp.batch_ke,
			                   CASE WHEN jp.form_aktif = 1 AND jp.form_ditutup IS NOT NULL AND jp.form_ditutup < GETDATE() THEN 1 ELSE 0 END AS is_kadaluarsa,
		                   p.nama_posisi, d.nama AS departemen, o.nama_outlet, r.no_mpr, r.status_req, r.tipe_penempatan,
		                   (SELECT COUNT(*) FROM dbo.APPLICATIONS a WHERE a.id_posting = jp.id_posting) AS n_lamaran,
		                   ROW_NUMBER() OVER (ORDER BY jp.id_posting DESC) AS rn
		            FROM dbo.JOB_POSTINGS jp
		            JOIN dbo.REQUISITIONS r ON r.id_req = jp.id_req
		            JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
		            LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
		            LEFT JOIN dbo.M_OUTLET o     ON o.id_outlet = r.id_outlet
		        )
		        SELECT * FROM q WHERE rn BETWEEN ? AND ? ORDER BY rn';
		$q = $this->db->query($sql, array((int) $offset, (int) $offset + (int) $per - 1));
		$rows = $q->result_array();
		$q->free_result();

		if ( ! empty($rows)) {
			foreach ($rows as &$row) {
				if (empty($row['url_slug']) && ! empty($row['id_posting'])) {
					$row['url_slug'] = $this->ensure_slug((int) $row['id_posting']);
				}
			}
			unset($row);
		}

		return $rows;
	}

	public function get_posting($id_posting)
	{
		$q = $this->db->query(
			'SELECT jp.*, p.nama_posisi, d.nama AS departemen, o.nama_outlet, r.no_mpr, r.status_req, r.tipe_penempatan
			 FROM dbo.JOB_POSTINGS jp
			 JOIN dbo.REQUISITIONS r ON r.id_req = jp.id_req
			 JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
			 LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
			 LEFT JOIN dbo.M_OUTLET o     ON o.id_outlet = r.id_outlet
			 WHERE jp.id_posting = ?', array((int) $id_posting));
		$row = $q->row_array();
		$q->free_result();
		return $row ? $row : NULL;
	}

	/**
	 * @param string|null $dibuka  'YYYY-MM-DD HH:MM' atau NULL
	 * @param string|null $ditutup
	 */
	public function update_form_settings($id_posting, $form_aktif, $dibuka, $ditutup)
	{
		$this->db->query(
			'UPDATE dbo.JOB_POSTINGS
			    SET form_aktif = ?, form_dibuka = ?, form_ditutup = ?
			  WHERE id_posting = ?',
			array((int) $form_aktif ? 1 : 0, $dibuka ?: NULL, $ditutup ?: NULL, (int) $id_posting)
		);
		return $this->db->affected_rows() >= 0;
	}

	public function toggle_form($id_posting, $form_aktif = NULL, $oleh_user = NULL, $durasi_hari = 14)
	{
		$status_akhir = 0;
		$stmt = sqlsrv_query(
			$this->db->conn_id,
			'{CALL dbo.sp_TogglePostingForm(?,?,?,?,?)}',
			array(
				(int) $id_posting,
				$form_aktif !== NULL ? ($form_aktif ? 1 : 0) : NULL,
				(int) $durasi_hari,
				(int) $oleh_user,
				array(&$status_akhir, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
			)
		);
		if ($stmt === FALSE) {
			$e = sqlsrv_errors();
			$last = $e ? end($e) : NULL;
			throw new RuntimeException($last ? trim($last['message']) : 'Gagal mengubah status form.');
		}
		do { /* nothing */ } while (sqlsrv_next_result($stmt));
		sqlsrv_free_stmt($stmt);
		return (bool) $status_akhir;
	}

	public function extend_posting($id_posting, $durasi_hari = 14, $oleh_user = NULL)
	{
		$stmt = sqlsrv_query(
			$this->db->conn_id,
			'{CALL dbo.sp_ExtendPosting(?,?,?)}',
			array(
				(int) $id_posting,
				(int) $durasi_hari,
				(int) $oleh_user
			)
		);
		if ($stmt === FALSE) {
			$e = sqlsrv_errors();
			$last = $e ? end($e) : NULL;
			throw new RuntimeException($last ? trim($last['message']) : 'Gagal memperpanjang lowongan.');
		}
		do { /* nothing */ } while (sqlsrv_next_result($stmt));
		sqlsrv_free_stmt($stmt);
		return TRUE;
	}

	/** Buat url_slug kalau belum ada. Slug = <posisi-slug>-<id_posting>. */
	public function ensure_slug($id_posting)
	{
		$p = $this->get_posting($id_posting);
		if ( ! $p) {
			return NULL;
		}
		if ( ! empty($p['url_slug'])) {
			return $p['url_slug'];
		}
		$base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $p['nama_posisi']));
		$base = trim($base, '-');
		$slug = substr($base, 0, 80) . '-' . (int) $id_posting;
		$this->db->query('UPDATE dbo.JOB_POSTINGS SET url_slug = ? WHERE id_posting = ?',
			array($slug, (int) $id_posting));
		return $slug;
	}

	/* ================= FORM_TOKENS ================================= */

	public function get_lamaran($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT a.id_lamaran, a.id_req, a.status_global, a.tanggal_lamar,
			        c.id_kandidat, c.nama_lengkap, c.email, c.no_wa_normal,
			        pos.nama_posisi, r.no_mpr, d.nama AS nama_departemen, o.nama_outlet
			 FROM dbo.APPLICATIONS a
			 JOIN dbo.CANDIDATES c   ON c.id_kandidat = a.id_kandidat
			 JOIN dbo.REQUISITIONS r ON r.id_req = a.id_req
			 JOIN dbo.M_POSISI pos   ON pos.id_posisi = r.id_posisi
			 LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = pos.id_departemen
			 LEFT JOIN dbo.M_OUTLET o ON o.id_outlet = r.id_outlet
			 WHERE a.id_lamaran = ?',
			array((int) $id_lamaran)
		);
		$row = $q->row_array();
		$q->free_result();
		return $row ?: NULL;
	}

	public function list_tokens($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT ft.id_token, ft.token, ft.tujuan, ft.kadaluarsa_pada, ft.dipakai_pada,
			        ft.is_revoked, u.nama_snapshot AS dibuat_oleh
			 FROM dbo.FORM_TOKENS ft
			 LEFT JOIN dbo.M_USERS u ON u.id_user = ft.dibuat_oleh
			 WHERE ft.id_lamaran = ? ORDER BY ft.id_token DESC',
			array((int) $id_lamaran));
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	/**
	 * Buat token baru. Token acak dibuat di PHP (random_bytes).
	 * Cabut token lama yang belum dipakai untuk (lamaran, tujuan) yang sama.
	 * @return string token
	 */
	public function create_token($id_lamaran, $tujuan, $masa_hari, $id_user)
	{
		$this->db->query(
			'UPDATE dbo.FORM_TOKENS SET is_revoked = 1
			  WHERE id_lamaran = ? AND tujuan = ? AND dipakai_pada IS NULL AND is_revoked = 0',
			array((int) $id_lamaran, (string) $tujuan));

		$token = bin2hex(random_bytes(24));
		$exp   = (int) $masa_hari > 0
			? date('Y-m-d H:i:s', time() + (int) $masa_hari * 86400)
			: NULL;

		$this->db->query(
			'INSERT INTO dbo.FORM_TOKENS (id_lamaran, token, tujuan, kadaluarsa_pada, dibuat_oleh)
			 VALUES (?, ?, ?, ?, ?)',
			array((int) $id_lamaran, $token, (string) $tujuan, $exp, (int) $id_user)
		);
		return $token;
	}

	public function revoke_token($id_token)
	{
		$this->db->query('UPDATE dbo.FORM_TOKENS SET is_revoked = 1 WHERE id_token = ?', array((int) $id_token));
	}

	/**
	 * Validasi token untuk halaman publik /berkas/<token>.
	 * @return array|null  info lamaran + tujuan bila token valid & belum dipakai
	 */
	public function resolve_token($token)
	{
		$q = $this->db->query(
			'SELECT ft.id_token, ft.id_lamaran, ft.tujuan, ft.kadaluarsa_pada, ft.dipakai_pada, ft.is_revoked,
			        c.nama_lengkap, p.nama_posisi
			 FROM dbo.FORM_TOKENS ft
			 JOIN dbo.APPLICATIONS a ON a.id_lamaran = ft.id_lamaran
			 JOIN dbo.CANDIDATES  c ON c.id_kandidat = a.id_kandidat
			 JOIN dbo.REQUISITIONS r ON r.id_req = a.id_req
			 JOIN dbo.M_POSISI    p ON p.id_posisi = r.id_posisi
			 WHERE ft.token = ?', array((string) $token));
		$row = $q->row_array();
		$q->free_result();
		if ( ! $row) {
			return NULL;
		}
		$row['valid'] = ( ! $row['is_revoked']
			&& $row['dipakai_pada'] === NULL
			&& ($row['kadaluarsa_pada'] === NULL || strtotime($row['kadaluarsa_pada']) > time()));
		return $row;
	}

	public function mark_token_used($id_token)
	{
		$this->db->query('UPDATE dbo.FORM_TOKENS SET dipakai_pada = GETDATE() WHERE id_token = ?', array((int) $id_token));
	}

	/* ================= JOB_POSTING_STATS ========================== */

	public function get_stats($id_posting)
	{
		$q = $this->db->query('SELECT * FROM dbo.JOB_POSTING_STATS WHERE id_posting = ?', array((int) $id_posting));
		$row = $q->row_array(); $q->free_result();
		return $row ? $row : NULL;
	}

	public function save_stats($id_posting, $masuk, $sesuai, $tidak, $id_user)
	{
		$exists = $this->get_stats($id_posting);
		if ($exists) {
			$this->db->query(
				'UPDATE dbo.JOB_POSTING_STATS
				 SET jumlah_pelamar_masuk = ?, cv_sesuai = ?, cv_tidak_sesuai = ?,
				     diperbarui_pada = GETDATE(), diperbarui_oleh = ?
				 WHERE id_posting = ?',
				array((int) $masuk, (int) $sesuai, (int) $tidak, (int) $id_user, (int) $id_posting));
		} else {
			$this->db->query(
				'INSERT INTO dbo.JOB_POSTING_STATS
				   (id_posting, jumlah_pelamar_masuk, cv_sesuai, cv_tidak_sesuai, diperbarui_pada, diperbarui_oleh)
				 VALUES (?, ?, ?, ?, GETDATE(), ?)',
				array((int) $id_posting, (int) $masuk, (int) $sesuai, (int) $tidak, (int) $id_user));
		}
	}

	public function active_documents()
	{
		$q = $this->db->query("SELECT id_dokumen, nama_dokumen, tingkat_sensitif
		                       FROM dbo.M_DOKUMEN WHERE is_aktif = 1 ORDER BY nama_dokumen");
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}
}
