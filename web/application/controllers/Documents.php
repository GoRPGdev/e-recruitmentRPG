<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Verifikasi berkas kandidat. Lihat: LIHAT_CV. Buka dokumen IDENTITAS/
 * FINANSIAL butuh permission khusus dan dicatat ke ACCESS_LOG_SENSITIF.
 * Konfigurasi "dokumen wajib per tahap" pindah ke Flow Builder
 * (flowbuilder/flow_docs, permission EDIT_FLOW_TEMPLATE).
 */
class Documents extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('LIHAT_CV');
		$this->load->model('document_model');
		$this->load->helper(array('form', 'url'));
	}

	public function index()
	{
		$f = array(
			'status'     => $this->input->get('status') !== NULL ? $this->input->get('status') : 'Proses',
			'kategori'   => $this->input->get('kategori') ?: NULL,
			'id_lamaran' => $this->input->get('id_lamaran') ?: NULL,
		);
		$this->load->view('layouts/main', array(
			'title'    => 'Verifikasi Berkas',
			'_content' => 'documents/index',
			'wide'     => TRUE,
			'f'        => $f,
			'rows'     => $this->document_model->list_docs($f),
		));
	}

	public function verify($id_cand_doc = NULL)
	{
		if ( ! $id_cand_doc || $this->input->method() !== 'post') {
			show_404();
		}
		try {
			$this->document_model->verify(
				$id_cand_doc,
				$this->input->post('status'),
				$this->input->post('catatan', TRUE),
				(int) $this->auth_user['id_user']
			);
			$this->session->set_flashdata('ok', 'Verifikasi disimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect($this->input->post('back') ?: 'documents');
	}

	/** Checklist dokumen 1 lamaran (wajib per tahap vs terkumpul). */
	public function checklist($id_lamaran = NULL)
	{
		if ( ! $id_lamaran) {
			show_404();
		}
		$this->load->view('layouts/main', array(
			'title'      => 'Checklist Berkas #' . (int) $id_lamaran,
			'_content'   => 'documents/checklist',
			'wide'       => TRUE,
			'id_lamaran' => (int) $id_lamaran,
			'rows'       => $this->document_model->checklist($id_lamaran),
		));
	}

	/**
	 * Buka file dokumen (streaming dari luar webroot). Dicatat kalau sensitif.
	 */
	public function open($id_cand_doc = NULL)
	{
		$doc = $id_cand_doc ? $this->document_model->get_doc($id_cand_doc) : NULL;
		if ( ! $doc) {
			show_404();
		}

		// RBAC dokumen sensitif -- cek permission + catat ke ACCESS_LOG_SENSITIF
		$peta = array('IDENTITAS' => 'DOK_IDENTITAS', 'FINANSIAL' => 'FINANSIAL');
		if (isset($peta[$doc['tingkat_sensitif']])) {
			gate_sensitif($peta[$doc['tingkat_sensitif']], (int) $doc['id_cand_doc']);
		}

		$path = $doc['path_file'];
		if ( ! is_file($path)) {
			show_error('File tidak ditemukan di storage.', 404);
		}
		$this->output
			->set_content_type($doc['mime_type'] ?: 'application/octet-stream')
			->set_header('Content-Disposition: inline; filename="' . basename($path) . '"')
			->set_header('Content-Length: ' . filesize($path))
			->set_output(file_get_contents($path));
	}
}
