<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model Document_model -- Model Verifikasi Berkas & Pengelolaan Dokumen Fisik
 *
 * Fungsi:
 * - Menampilkan daftar berkas dokumen yang telah diunggah pelamar beserta status verifikasinya.
 * - Memperbarui status verifikasi berkas (valid / revisi / ditolak) oleh Tim HR.
 * - Mengambil path fisik file dan hash SHA-256 untuk streaming berkas terenkripsi aman.
 */
class Document_model extends CI_Model
{
	public function list_docs($f = array())
	{
		$sql = "SELECT cd.id_cand_doc, cd.id_lamaran, cd.status_verifikasi, cd.nama_file_asli,
		               cd.mime_type, cd.ukuran_byte, cd.hash_sha256, cd.diunggah_pada,
		               cd.catatan_verifikasi, cd.diverifikasi_pada,
		               dk.nama_dokumen, dk.kategori, dk.tingkat_sensitif,
		               c.nama_lengkap, pos.nama_posisi, pos.id_departemen, o.region, r.no_mpr
		        FROM dbo.CANDIDATE_DOCUMENTS cd
		        JOIN dbo.M_DOKUMEN dk    ON dk.id_dokumen = cd.id_dokumen
		        JOIN dbo.APPLICATIONS a  ON a.id_lamaran = cd.id_lamaran
		        JOIN dbo.CANDIDATES c    ON c.id_kandidat = a.id_kandidat
		        JOIN dbo.REQUISITIONS r  ON r.id_req = a.id_req
		        JOIN dbo.M_POSISI pos    ON pos.id_posisi = r.id_posisi
		        LEFT JOIN dbo.M_OUTLET o ON o.id_outlet = r.id_outlet
		        WHERE 1=1";
		$b = array();
		$st = isset($f['status']) ? $f['status'] : 'Proses';
		if ($st !== '') { $sql .= ' AND cd.status_verifikasi = ?'; $b[] = $st; }
		if ( ! empty($f['id_lamaran'])) { $sql .= ' AND cd.id_lamaran = ?'; $b[] = (int) $f['id_lamaran']; }
		if ( ! empty($f['kategori']))   { $sql .= ' AND dk.kategori = ?'; $b[] = $f['kategori']; }
		if ( ! empty($f['dept']))       { $sql .= ' AND pos.id_departemen = ?'; $b[] = (int) $f['dept']; }
		if ( ! empty($f['region']))     { $sql .= ' AND o.region = ?'; $b[] = (string) $f['region']; }
		$sql .= ' ORDER BY cd.diunggah_pada DESC';
		$q = $this->db->query($sql, $b);
		$rows = $q->result_array(); $q->free_result();
		return $rows;
	}

	public function get_doc($id_cand_doc)
	{
		$q = $this->db->query(
			'SELECT cd.*, dk.nama_dokumen, dk.kategori, dk.tingkat_sensitif, a.id_kandidat, pos.id_departemen, o.region
			 FROM dbo.CANDIDATE_DOCUMENTS cd
			 JOIN dbo.M_DOKUMEN dk    ON dk.id_dokumen = cd.id_dokumen
			 JOIN dbo.APPLICATIONS a  ON a.id_lamaran = cd.id_lamaran
			 JOIN dbo.REQUISITIONS r  ON r.id_req = a.id_req
			 JOIN dbo.M_POSISI pos    ON pos.id_posisi = r.id_posisi
			 LEFT JOIN dbo.M_OUTLET o ON o.id_outlet = r.id_outlet
			 WHERE cd.id_cand_doc = ?', array((int) $id_cand_doc));
		$row = $q->row_array(); $q->free_result();
		return $row ? $row : NULL;
	}

	public function get_lamaran_dept($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT pos.id_departemen
			 FROM dbo.APPLICATIONS a
			 JOIN dbo.REQUISITIONS r ON r.id_req = a.id_req
			 JOIN dbo.M_POSISI pos   ON pos.id_posisi = r.id_posisi
			 WHERE a.id_lamaran = ?', array((int) $id_lamaran));
		$row = $q->row_array(); $q->free_result();
		return $row ? (int) $row['id_departemen'] : NULL;
	}

	public function get_lamaran_region($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT o.region
			 FROM dbo.APPLICATIONS a
			 JOIN dbo.REQUISITIONS r  ON r.id_req = a.id_req
			 LEFT JOIN dbo.M_OUTLET o ON o.id_outlet = r.id_outlet
			 WHERE a.id_lamaran = ?', array((int) $id_lamaran));
		$row = $q->row_array(); $q->free_result();
		return $row && $row['region'] !== NULL ? (string) $row['region'] : NULL;
	}

	public function verify($id_cand_doc, $status, $catatan, $id_user)
	{
		$stmt = sqlsrv_query($this->db->conn_id, '{CALL dbo.sp_VerifyDocument(?,?,?,?)}',
			array((int) $id_cand_doc, $status, $catatan ?: NULL, (int) $id_user));
		if ($stmt === FALSE) {
			$e = sqlsrv_errors(); $last = $e ? end($e) : NULL;
			throw new RuntimeException($last ? trim($last['message']) : 'Verifikasi gagal.');
		}
		do { } while (sqlsrv_next_result($stmt));
		sqlsrv_free_stmt($stmt);
	}

	/** Semua dokumen 1 lamaran + status wajib per tahap flow-nya. */
	public function checklist($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT dk.id_dokumen, dk.nama_dokumen, dk.kategori,
			        MAX(CASE WHEN fsd.is_wajib = 1 THEN 1 ELSE 0 END) AS wajib,
			        MAX(cd.status_verifikasi) AS status_verifikasi,
			        MAX(cd.id_cand_doc) AS id_cand_doc
			 FROM dbo.M_DOKUMEN dk
			 LEFT JOIN dbo.M_FLOW_STAGE_DOKUMEN fsd ON fsd.id_dokumen = dk.id_dokumen
			 LEFT JOIN dbo.M_FLOW_STAGE fs ON fs.id_flow_stage = fsd.id_flow_stage
			   AND fs.id_flow = (SELECT id_flow FROM dbo.APPLICATIONS WHERE id_lamaran = ?)
			 LEFT JOIN dbo.CANDIDATE_DOCUMENTS cd ON cd.id_dokumen = dk.id_dokumen AND cd.id_lamaran = ?
			 WHERE dk.is_aktif = 1
			 GROUP BY dk.id_dokumen, dk.nama_dokumen, dk.kategori
			 ORDER BY wajib DESC, dk.nama_dokumen',
			array((int) $id_lamaran, (int) $id_lamaran));
		$rows = $q->result_array(); $q->free_result();
		return $rows;
	}

	/**
	 * Catat pembukaan data sensitif. Dipertahankan untuk pemanggil lama;
	 * jalur baru pakai helper gate_sensitif() / log_akses_sensitif().
	 */
	public function log_sensitive($id_user, $jenis, $id_ref, $ip)
	{
		$stmt = sqlsrv_query($this->db->conn_id, '{CALL dbo.sp_LogAksesSensitif(?,?,?,?)}',
			array((int) $id_user, $jenis, $id_ref !== NULL ? (int) $id_ref : NULL, $ip));
		if ($stmt !== FALSE) { do { } while (sqlsrv_next_result($stmt)); sqlsrv_free_stmt($stmt); }
	}

	/* ---- M_FLOW_STAGE_DOKUMEN config ---- */

	public function flow_required_docs($id_flow)
	{
		return $this->db->query(
			'SELECT fs.id_flow_stage, s.nama_tahap, fs.urutan, dk.id_dokumen, dk.nama_dokumen, fsd.is_wajib
			 FROM dbo.M_FLOW_STAGE fs
			 JOIN dbo.M_STAGE s ON s.id_stage = fs.id_stage
			 LEFT JOIN dbo.M_FLOW_STAGE_DOKUMEN fsd ON fsd.id_flow_stage = fs.id_flow_stage
			 LEFT JOIN dbo.M_DOKUMEN dk ON dk.id_dokumen = fsd.id_dokumen
			 WHERE fs.id_flow = ? ORDER BY fs.urutan, dk.nama_dokumen', array((int) $id_flow))->result_array();
	}

	public function set_flow_doc($id_flow_stage, $id_dokumen, $wajib)
	{
		$ex = $this->db->query('SELECT 1 x FROM dbo.M_FLOW_STAGE_DOKUMEN WHERE id_flow_stage=? AND id_dokumen=?',
			array((int) $id_flow_stage, (int) $id_dokumen))->row();
		if ($wajib === 'remove') {
			$this->db->query('DELETE FROM dbo.M_FLOW_STAGE_DOKUMEN WHERE id_flow_stage=? AND id_dokumen=?',
				array((int) $id_flow_stage, (int) $id_dokumen));
		} elseif ($ex) {
			$this->db->query('UPDATE dbo.M_FLOW_STAGE_DOKUMEN SET is_wajib=? WHERE id_flow_stage=? AND id_dokumen=?',
				array($wajib ? 1 : 0, (int) $id_flow_stage, (int) $id_dokumen));
		} else {
			$this->db->query('INSERT INTO dbo.M_FLOW_STAGE_DOKUMEN (id_flow_stage, id_dokumen, is_wajib) VALUES (?,?,?)',
				array((int) $id_flow_stage, (int) $id_dokumen, $wajib ? 1 : 0));
		}
	}
}
