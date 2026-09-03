<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halaman publik lengkapi berkas via token: /berkas/<token>
 * Tanpa login. Token dari FORM_TOKENS (kadaluarsa, sekali pakai, bisa dicabut).
 */
class Berkas extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('posting_model', 'application_model'));
		$this->load->helper(array('form', 'url', 'storage'));
	}

	public function index($token = NULL)
	{
		$t = $token ? $this->posting_model->resolve_token($token) : NULL;

		if ( ! $t) {
			show_404();
		}
		if ( ! $t['valid']) {
			return $this->load->view('layouts/main', array(
				'title'    => 'Tautan tidak berlaku',
				'_content' => 'berkas/kadaluarsa',
				't'        => $t,
			));
		}

		if ($this->input->method() === 'post') {
			return $this->_upload($token, $t);
		}

		$this->load->view('layouts/main', array(
			'title'    => 'Lengkapi berkas',
			'_content' => 'berkas/form',
			't'        => $t,
			'dokumen'  => $this->posting_model->active_documents(),
		));
	}

	private function _upload($token, $t)
	{
		$id_lamaran = (int) $t['id_lamaran'];
		$ip = $this->input->ip_address();

		if (empty($_FILES) || empty($_FILES['berkas']['name'][0])) {
			$this->session->set_flashdata('error', 'Pilih minimal satu file.');
			redirect('berkas/' . $token);
		}

		$dok_map = $this->input->post('id_dokumen'); // paralel dengan $_FILES['berkas']
		$saved = 0; $errs = array();

		foreach ($_FILES['berkas']['name'] as $i => $nama) {
			if ($_FILES['berkas']['error'][$i] === UPLOAD_ERR_NO_FILE) {
				continue;
			}
			$id_dok = isset($dok_map[$i]) ? (int) $dok_map[$i] : 0;
			if ( ! $id_dok) {
				$errs[] = ($nama ?: 'file ' . ($i + 1)) . ': jenis dokumen belum dipilih';
				continue;
			}
			$file = array(
				'name'     => $_FILES['berkas']['name'][$i],
				'type'     => $_FILES['berkas']['type'][$i],
				'tmp_name' => $_FILES['berkas']['tmp_name'][$i],
				'error'    => $_FILES['berkas']['error'][$i],
				'size'     => $_FILES['berkas']['size'][$i],
			);
			try {
				$prefix = 'DOK' . $id_dok;
				$meta = erec_store_upload($file, $id_lamaran, $prefix);
				$this->application_model->save_cv_document($id_lamaran, $id_dok, array(
					'path_file' => $meta['path_file'], 'nama_asli' => $meta['nama_asli'],
					'hash' => $meta['hash'], 'ukuran' => $meta['ukuran'], 'mime' => $meta['mime'], 'ip' => $ip,
				));
				$saved++;
			} catch (RuntimeException $e) {
				$errs[] = ($nama ?: 'file ' . ($i + 1)) . ': ' . $e->getMessage();
			}
		}

		if ($saved > 0) {
			$this->posting_model->mark_token_used($t['id_token']);
			return $this->load->view('layouts/main', array(
				'title'    => 'Berkas terkirim',
				'_content' => 'berkas/sukses',
				't'        => $t,
				'saved'    => $saved,
				'errs'     => $errs,
			));
		}

		$this->session->set_flashdata('error', 'Tidak ada file tersimpan. ' . implode('; ', $errs));
		redirect('berkas/' . $token);
	}
}
