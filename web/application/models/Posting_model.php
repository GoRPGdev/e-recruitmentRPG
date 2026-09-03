<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Posting_model -- kelola link form publik + token berkas personal.
 * Paginasi pakai pola ROW_NUMBER() (CLAUDE.md aturan 2), bukan limit().
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
		            SELECT jp.id_posting, jp.url_slug, jp.judul_posting, jp.is_aktif,
		                   jp.form_aktif, jp.form_dibuka, jp.form_ditutup, jp.jumlah_submit,
		                   p.nama_posisi, r.no_mpr, r.status_req,
		                   (SELECT COUNT(*) FROM dbo.APPLICATIONS a WHERE a.id_posting = jp.id_posting) AS n_lamaran,
		                   ROW_NUMBER() OVER (ORDER BY jp.id_posting DESC) AS rn
		            FROM dbo.JOB_POSTINGS jp
		            JOIN dbo.REQUISITIONS r ON r.id_req = jp.id_req
		            JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
		        )
		        SELECT * FROM q WHERE rn BETWEEN ? AND ? ORDER BY rn';
		$q = $this->db->query($sql, array((int) $offset, (int) $offset + (int) $per - 1));
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	public function get_posting($id_posting)
	{
		$q = $this->db->query(
			'SELECT jp.*, p.nama_posisi, r.no_mpr
			 FROM dbo.JOB_POSTINGS jp
			 JOIN dbo.REQUISITIONS r ON r.id_req = jp.id_req
			 JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
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

	/* ================= dokumen aktif (untuk dropdown upload) ======= */

	public function active_documents()
	{
		$q = $this->db->query("SELECT id_dokumen, nama_dokumen, tingkat_sensitif
		                       FROM dbo.M_DOKUMEN WHERE is_aktif = 1 ORDER BY nama_dokumen");
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}
}
