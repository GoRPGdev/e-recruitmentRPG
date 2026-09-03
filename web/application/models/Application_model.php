<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Application_model -- intake lamaran.
 *
 * Untuk sp_SubmitApplication dipakai sqlsrv_query() LANGSUNG di atas
 * koneksi CI3 ($this->db->conn_id): binding parameter asli + OUTPUT param,
 * sesuai pola tools/test-koneksi.php bagian 7. Driver query() CI3 tidak
 * mendukung OUTPUT param.
 */
class Application_model extends CI_Model
{
	/**
	 * Data posting dari slug -- untuk menampilkan "Anda melamar: <posisi>"
	 * dan memvalidasi form masih terbuka.
	 * @return array|null
	 */
	public function get_posting_by_slug($slug)
	{
		$sql = 'SELECT jp.id_posting, jp.id_req, jp.judul_posting, jp.url_slug,
		               jp.form_aktif, jp.form_dibuka, jp.form_ditutup,
		               r.status_req, p.nama_posisi, d.nama AS departemen,
		               CASE WHEN jp.form_aktif = 1
		                    AND (jp.form_dibuka  IS NULL OR jp.form_dibuka  <= GETDATE())
		                    AND (jp.form_ditutup IS NULL OR jp.form_ditutup >= GETDATE())
		                    AND r.status_req NOT IN (\'Draft\',\'Terpenuhi\',\'Dibatalkan\',\'Kadaluarsa\')
		               THEN 1 ELSE 0 END AS terbuka
		        FROM dbo.JOB_POSTINGS jp
		        JOIN dbo.REQUISITIONS r ON r.id_req = jp.id_req
		        JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
		        LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
		        WHERE jp.url_slug = ?';
		$q = $this->db->query($sql, array((string) $slug));
		$row = $q->row_array();
		$q->free_result();
		return $row ? $row : NULL;
	}

	/* ---- rate limit (FORM_SUBMIT_LOG) ---------------------------------- */

	public function count_recent_submits($slug, $ip, $window_min)
	{
		$sql = 'SELECT COUNT(*) AS n FROM dbo.FORM_SUBMIT_LOG
		        WHERE url_slug = ? AND ip = ? AND waktu > DATEADD(MINUTE, ?, GETDATE())';
		$q = $this->db->query($sql, array((string) $slug, (string) $ip, -1 * (int) $window_min));
		$n = (int) $q->row()->n;
		$q->free_result();
		return $n;
	}

	public function log_submit($slug, $ip)
	{
		$this->db->query(
			'INSERT INTO dbo.FORM_SUBMIT_LOG (url_slug, ip, waktu) VALUES (?, ?, GETDATE())',
			array((string) $slug, (string) $ip)
		);
	}

	/**
	 * Panggil dbo.sp_SubmitApplication.
	 *
	 * $in : associative array. Kunci opsional boleh tidak ada -> dikirim NULL.
	 *   url_slug, id_req_manual, id_posting_manual, intake_method, nama_channel,
	 *   nama_lengkap, email, no_wa_raw, tempat_lahir, tanggal_lahir, jenis_kelamin,
	 *   pendidikan_terakhir, nama_sekolah, jurusan, kota_domisili, alamat_lengkap,
	 *   status_pernikahan, kontak_darurat_nama, kontak_darurat_telp, kontak_darurat_hub,
	 *   consent_versi, setuju_talent_pool, riwayat_penyakit, consent_kesehatan,
	 *   perusahaan_terakhir, jabatan_terakhir, periode_kerja, gaji_terakhir,
	 *   gaji_diharapkan, cv_hash, id_import_batch
	 *
	 * @return array  ['id_lamaran','id_kandidat','is_kandidat_baru']
	 * @throws RuntimeException  pesan dari RAISERROR SP
	 */
	public function submit(array $in)
	{
		$conn = $this->db->conn_id;

		// URUTAN HARUS SAMA PERSIS dengan deklarasi parameter sp_SubmitApplication.
		$g = function ($k, $d = NULL) use ($in) { return array_key_exists($k, $in) && $in[$k] !== '' ? $in[$k] : $d; };

		$id_lamaran = 0; $id_kandidat = 0; $is_baru = 0;

		$params = array(
			$g('url_slug'),
			$g('id_req_manual'),
			$g('id_posting_manual'),
			$g('intake_method', 'FORM_PUBLIC'),
			$g('nama_channel', 'Portal Sendiri'),
			$g('nama_lengkap'),
			$g('email'),
			$g('no_wa_raw'),
			$g('tempat_lahir'),
			$g('tanggal_lahir'),
			$g('jenis_kelamin'),
			$g('pendidikan_terakhir'),
			$g('nama_sekolah'),
			$g('jurusan'),
			$g('kota_domisili'),
			$g('alamat_lengkap'),
			$g('status_pernikahan'),
			$g('kontak_darurat_nama'),
			$g('kontak_darurat_telp'),
			$g('kontak_darurat_hub'),
			$g('consent_versi'),
			(int) $g('setuju_talent_pool', 0),
			$g('riwayat_penyakit'),
			(int) $g('consent_kesehatan', 0),
			$g('perusahaan_terakhir'),
			$g('jabatan_terakhir'),
			$g('periode_kerja'),
			$g('gaji_terakhir'),
			$g('gaji_diharapkan'),
			$g('cv_hash'),
			$g('id_import_batch'),
			array(&$id_lamaran,  SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
			array(&$id_kandidat, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
			array(&$is_baru,     SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
		);

		$sql = '{CALL dbo.sp_SubmitApplication(' . implode(',', array_fill(0, count($params), '?')) . ')}';

		$stmt = sqlsrv_query($conn, $sql, $params);
		if ($stmt === FALSE) {
			throw new RuntimeException($this->_sqlsrv_error());
		}
		// habiskan result set supaya OUTPUT param terisi
		do { /* nothing */ } while (sqlsrv_next_result($stmt));
		sqlsrv_free_stmt($stmt);

		return array(
			'id_lamaran'       => (int) $id_lamaran,
			'id_kandidat'      => (int) $id_kandidat,
			'is_kandidat_baru' => (bool) $is_baru,
		);
	}

	/**
	 * Catat file CV yang sudah tersimpan di luar webroot.
	 */
	public function save_cv_document($id_lamaran, $id_dokumen_cv, array $f)
	{
		$this->db->query(
			'INSERT INTO dbo.CANDIDATE_DOCUMENTS
			   (id_lamaran, id_dokumen, path_file, nama_file_asli, hash_sha256,
			    ukuran_byte, mime_type, ip_pengunggah, diunggah_pada)
			 VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE())',
			array(
				(int) $id_lamaran, (int) $id_dokumen_cv, $f['path_file'], $f['nama_asli'],
				$f['hash'], (int) $f['ukuran'], $f['mime'], $f['ip'],
			)
		);
	}

	public function id_dokumen_by_nama($nama)
	{
		$q = $this->db->query('SELECT id_dokumen FROM dbo.M_DOKUMEN WHERE nama_dokumen = ?', array((string) $nama));
		$row = $q->row();
		$q->free_result();
		return $row ? (int) $row->id_dokumen : NULL;
	}

	/**
	 * Requisition yang sedang menerima lamaran (untuk dropdown entry manual / import).
	 * @param string|null $tipe 'HQ' | 'OUTLET' | NULL (semua)
	 */
	public function list_open_requisitions($tipe = NULL)
	{
		$sql = 'SELECT r.id_req, r.no_mpr, r.tipe_penempatan, r.status_req,
		               p.nama_posisi, o.nama_outlet, o.kode_outlet
		        FROM dbo.REQUISITIONS r
		        JOIN dbo.M_POSISI p       ON p.id_posisi = r.id_posisi
		        LEFT JOIN dbo.M_OUTLET o  ON o.id_outlet = r.id_outlet
		        WHERE r.status_req NOT IN (\'Draft\',\'Terpenuhi\',\'Dibatalkan\',\'Kadaluarsa\')';
		$bind = array();
		if ($tipe !== NULL) {
			$sql .= ' AND r.tipe_penempatan = ?';
			$bind[] = (string) $tipe;
		}
		$sql .= ' ORDER BY r.id_req DESC';
		$q = $this->db->query($sql, $bind);
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	public function list_channels()
	{
		$q = $this->db->query('SELECT id_channel, nama_channel FROM dbo.M_CHANNEL WHERE is_aktif = 1 ORDER BY nama_channel');
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	/** Catatan bebas ke timeline lamaran (mis. rencana tanggal join dari entry manual). */
	public function add_note($id_lamaran, $teks, $id_user)
	{
		$this->db->query(
			'INSERT INTO dbo.APPLICATION_HISTORY (id_lamaran, jenis_event, deskripsi, oleh_user)
			 VALUES (?, \'CATATAN\', ?, ?)',
			array((int) $id_lamaran, (string) $teks, (int) $id_user)
		);
	}

	private function _sqlsrv_error()
	{
		$errs = sqlsrv_errors();
		if ( ! $errs) {
			return 'Kesalahan tidak diketahui saat submit.';
		}
		// pesan RAISERROR SP ada di entri terakhir, tanpa prefiks driver
		$last = end($errs);
		return isset($last['message']) ? trim($last['message']) : 'Gagal submit.';
	}
}
