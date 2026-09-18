<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model Import_model -- Model Impor Massal Data Pelamar CSV
 *
 * Fungsi:
 * - Menangani parsing struktur file CSV dan pencatatan ke IMPORT_BATCHES & IMPORT_BATCH_ROWS.
 * - Melakukan normalisasi nomor telepon/WhatsApp dan deduplikasi berbasis no_wa serta email.
 * - Mengeksekusi commit batch ke tabel lamaran melalui sp_SubmitApplication dengan pola savepoint transaksi.
 */
class Import_model extends CI_Model
{
	public static $HEADERS_WAJIB = array('nama', 'no_wa');

	public function create_batch($id_req, $id_channel = NULL, $nama_file = '', $id_user = 0)
	{
		$this->db->query(
			'INSERT INTO dbo.IMPORT_BATCHES (id_req, nama_file, status, diimpor_oleh)
			 VALUES (?, ?, \'Preview\', ?)',
			array((int) $id_req, (string) $nama_file, (int) $id_user)
		);
		return (int) $this->db->query('SELECT CAST(SCOPE_IDENTITY() AS INT) AS id')->row()->id;
	}

	/**
	 * $parsed : array of assoc row. Simpan payload + precek dedup.
	 * @return array ['baris'=>N, 'dup'=>N]
	 */
	public function add_rows($id_batch, array $parsed)
	{
		$n = 0; $dup = 0;
		foreach ($parsed as $i => $row) {
			$wa = isset($row['no_wa']) ? $row['no_wa'] : NULL;
			$email = isset($row['email']) ? $row['email'] : NULL;

			$hit = $this->db->query(
				'SELECT TOP 1 1 AS x FROM dbo.CANDIDATES
				 WHERE (? IS NOT NULL AND no_wa_normal = dbo.fn_NormalisasiWA(?))
				    OR (? IS NOT NULL AND ? <> \'\' AND email = ?)',
				array($wa, $wa, $email, $email, $email)
			)->row();

			$hasil = $hit ? 'Duplikat' : 'OK';
			if ($hit) { $dup++; }

			$this->db->query(
				'INSERT INTO dbo.IMPORT_BATCH_ROWS (id_batch, nomor_baris, payload_mentah, hasil)
				 VALUES (?, ?, ?, ?)',
				array((int) $id_batch, $i + 1, json_encode($row, JSON_UNESCAPED_UNICODE), $hasil)
			);
			$n++;
		}
		$this->db->query('UPDATE dbo.IMPORT_BATCHES SET jumlah_baris = ?, duplikat = ? WHERE id_batch = ?',
			array($n, $dup, (int) $id_batch));
		return array('baris' => $n, 'dup' => $dup);
	}

	public function get_batch($id_batch)
	{
		$q = $this->db->query(
			'SELECT b.*, p.nama_posisi, NULL AS nama_channel, r.no_mpr
			 FROM dbo.IMPORT_BATCHES b
			 LEFT JOIN dbo.REQUISITIONS r ON r.id_req = b.id_req
			 LEFT JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
			 WHERE b.id_batch = ?', array((int) $id_batch));
		$row = $q->row_array();
		$q->free_result();
		return $row ? $row : NULL;
	}

	public function list_rows($id_batch)
	{
		$q = $this->db->query(
			'SELECT id_row, nomor_baris, payload_mentah, hasil, alasan_gagal, id_lamaran_dibuat
			 FROM dbo.IMPORT_BATCH_ROWS WHERE id_batch = ? ORDER BY nomor_baris',
			array((int) $id_batch));
		$rows = $q->result_array();
		$q->free_result();
		foreach ($rows as &$r) {
			$r['data'] = json_decode($r['payload_mentah'], TRUE) ?: array();
		}
		return $rows;
	}

	public function list_recent($limit = 15)
	{
		$q = $this->db->query(
			'SELECT TOP (' . (int) $limit . ') b.id_batch, b.nama_file, b.status, b.jumlah_baris,
			        b.berhasil, b.gagal, b.duplikat, b.diimpor_pada, p.nama_posisi
			 FROM dbo.IMPORT_BATCHES b
			 LEFT JOIN dbo.REQUISITIONS r ON r.id_req = b.id_req
			 LEFT JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
			 ORDER BY b.id_batch DESC');
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	public function update_row($id_row, $hasil, $alasan, $id_lamaran)
	{
		$this->db->query(
			'UPDATE dbo.IMPORT_BATCH_ROWS SET hasil = ?, alasan_gagal = ?, id_lamaran_dibuat = ? WHERE id_row = ?',
			array((string) $hasil, $alasan, $id_lamaran, (int) $id_row));
	}

	public function finish_batch($id_batch, $ok, $gagal, $dup, $status)
	{
		$this->db->query(
			'UPDATE dbo.IMPORT_BATCHES SET berhasil = ?, gagal = ?, duplikat = ?, status = ? WHERE id_batch = ?',
			array((int) $ok, (int) $gagal, (int) $dup, (string) $status, (int) $id_batch));
	}

	public function discard($id_batch)
	{
		$this->db->query("UPDATE dbo.IMPORT_BATCHES SET status = 'Rolled_Back' WHERE id_batch = ? AND status = 'Preview'",
			array((int) $id_batch));
		return $this->db->affected_rows() > 0;
	}
}
