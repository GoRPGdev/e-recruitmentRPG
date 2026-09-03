<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Import file portal (CSV): upload -> preview + dedupe -> commit / discard.
 * Wajib login + KELOLA_REKRUTMEN.
 *
 * Commit menjalankan sp_SubmitApplication per baris dalam SATU transaksi
 * (sqlsrv_begin_transaction). SP memakai SAVE TRAN -> satu baris gagal
 * tidak membatalkan seluruh batch.
 */
class Import extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('KELOLA_REKRUTMEN');
		$this->load->model(array('import_model', 'application_model'));
		$this->load->helper(array('form', 'url'));
	}

	public function index()
	{
		if ($this->input->method() === 'post') {
			return $this->_upload();
		}
		$this->load->view('layouts/main', array(
			'title'    => 'Import File',
			'_content' => 'import/index',
			'reqs'     => $this->application_model->list_open_requisitions(),
			'channels' => $this->application_model->list_channels(),
			'recent'   => $this->import_model->list_recent(),
		));
	}

	private function _upload()
	{
		$id_req     = (int) $this->input->post('id_req');
		$id_channel = (int) $this->input->post('id_channel');
		if ( ! $id_req || ! $id_channel) {
			$this->session->set_flashdata('error', 'Pilih requisition dan channel.');
			redirect('import');
		}
		if (empty($_FILES['file']['name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
			$this->session->set_flashdata('error', 'File CSV wajib diunggah.');
			redirect('import');
		}
		if (strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION)) !== 'csv') {
			$this->session->set_flashdata('error', 'Hanya file .csv (Excel: Save As -> CSV).');
			redirect('import');
		}

		try {
			$parsed = $this->_parse_csv($_FILES['file']['tmp_name']);
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('import');
		}
		if ( ! $parsed) {
			$this->session->set_flashdata('error', 'Tidak ada baris data di CSV.');
			redirect('import');
		}

		$id_batch = $this->import_model->create_batch($id_req, $id_channel, $_FILES['file']['name'],
			(int) $this->auth_user['id_user']);

		// simpan file mentah
		$dir = rtrim($this->config->item('erec_storage_path'), '/\\') . DIRECTORY_SEPARATOR . 'import';
		@mkdir($dir, 0770, TRUE);
		@move_uploaded_file($_FILES['file']['tmp_name'],
			$dir . DIRECTORY_SEPARATOR . $id_batch . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $_FILES['file']['name']));

		$this->import_model->add_rows($id_batch, $parsed);
		redirect('import/preview/' . $id_batch);
	}

	public function preview($id_batch = NULL)
	{
		$batch = $id_batch ? $this->import_model->get_batch($id_batch) : NULL;
		if ( ! $batch) {
			show_404();
		}
		$this->load->view('layouts/main', array(
			'title'    => 'Preview Import #' . (int) $id_batch,
			'_content' => 'import/preview',
			'wide'     => TRUE,
			'batch'    => $batch,
			'rows'     => $this->import_model->list_rows($id_batch),
		));
	}

	public function commit($id_batch = NULL)
	{
		$batch = $id_batch ? $this->import_model->get_batch($id_batch) : NULL;
		if ( ! $batch || $this->input->method() !== 'post') {
			show_404();
		}
		if ($batch['status'] !== 'Preview') {
			$this->session->set_flashdata('error', 'Batch ini bukan status Preview.');
			redirect('import/preview/' . (int) $id_batch);
		}

		$include = array_map('intval', (array) $this->input->post('include'));
		$rows    = $this->import_model->list_rows($id_batch);
		$chan    = $batch['nama_channel'] ?: 'JobStreet';
		$conn    = $this->db->conn_id;

		$ok = 0; $gagal = 0; $dup = 0;

		sqlsrv_begin_transaction($conn);
		try {
			foreach ($rows as $r) {
				if ( ! in_array((int) $r['id_row'], $include, TRUE)) {
					continue;
				}
				$d = $r['data'];
				try {
					$res = $this->application_model->submit(array(
						'id_req_manual'       => (int) $batch['id_req'],
						'intake_method'       => 'IMPORT_FILE',
						'nama_channel'        => $chan,
						'id_import_batch'     => (int) $id_batch,
						'nama_lengkap'        => isset($d['nama']) ? $d['nama'] : '',
						'email'               => isset($d['email']) ? $d['email'] : NULL,
						'no_wa_raw'           => isset($d['no_wa']) ? $d['no_wa'] : NULL,
						'pendidikan_terakhir' => isset($d['pendidikan']) ? $d['pendidikan'] : NULL,
						'kota_domisili'       => isset($d['kota']) ? $d['kota'] : NULL,
						'perusahaan_terakhir' => isset($d['perusahaan_terakhir']) ? $d['perusahaan_terakhir'] : NULL,
						'gaji_diharapkan'     => isset($d['gaji_diharapkan']) ? preg_replace('/[^0-9]/', '', $d['gaji_diharapkan']) : NULL,
					));
					$this->import_model->update_row($r['id_row'], 'OK', NULL, $res['id_lamaran']);
					$ok++;
				} catch (RuntimeException $e) {
					$msg = $e->getMessage();
					if (stripos($msg, 'sudah punya lamaran aktif') !== FALSE) {
						$this->import_model->update_row($r['id_row'], 'Duplikat', $msg, NULL);
						$dup++;
					} else {
						$this->import_model->update_row($r['id_row'], 'Gagal', $msg, NULL);
						$gagal++;
					}
				}
			}
			sqlsrv_commit($conn);
		} catch (Exception $ex) {
			if (function_exists('sqlsrv_rollback')) { @sqlsrv_rollback($conn); }
			$this->import_model->finish_batch($id_batch, 0, 0, 0, 'Rolled_Back');
			$this->session->set_flashdata('error', 'Commit gagal total, dibatalkan: ' . $ex->getMessage());
			redirect('import/preview/' . (int) $id_batch);
		}

		$this->import_model->finish_batch($id_batch, $ok, $gagal, $dup, 'Committed');
		$this->session->set_flashdata('ok', "Import selesai: $ok berhasil, $dup duplikat, $gagal gagal.");
		redirect('import/preview/' . (int) $id_batch);
	}

	public function discard($id_batch = NULL)
	{
		if ( ! $id_batch || $this->input->method() !== 'post') {
			show_404();
		}
		$this->import_model->discard($id_batch);
		$this->session->set_flashdata('ok', 'Batch dibatalkan (belum ada lamaran dibuat).');
		redirect('import');
	}

	/* ---- CSV -------------------------------------------------------- */

	private function _parse_csv($path)
	{
		$fh = fopen($path, 'r');
		if ( ! $fh) {
			throw new RuntimeException('File tidak bisa dibaca.');
		}
		$head = fgetcsv($fh);
		if ( ! $head) {
			fclose($fh);
			throw new RuntimeException('CSV kosong.');
		}
		// normalisasi header
		$head = array_map(function ($h) {
			return strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $h)));
		}, $head);

		foreach (Import_model::$HEADERS_WAJIB as $need) {
			if ( ! in_array($need, $head, TRUE)) {
				fclose($fh);
				throw new RuntimeException('Kolom wajib "' . $need . '" tidak ada. Header: ' . implode(', ', $head));
			}
		}

		$out = array();
		while (($line = fgetcsv($fh)) !== FALSE) {
			if (count(array_filter($line, 'strlen')) === 0) {
				continue;
			}
			$row = array();
			foreach ($head as $i => $col) {
				$row[$col] = isset($line[$i]) ? trim((string) $line[$i]) : '';
			}
			$out[] = $row;
			if (count($out) > 2000) {
				break;   // batas aman
			}
		}
		fclose($fh);
		return $out;
	}
}
