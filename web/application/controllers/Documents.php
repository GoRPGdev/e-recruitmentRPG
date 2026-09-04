<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Verifikasi berkas kandidat + konfigurasi dokumen wajib per tahap.
 * Lihat: LIHAT_CV. Buka dokumen IDENTITAS/FINANSIAL butuh permission khusus
 * dan dicatat ke ACCESS_LOG_SENSITIF.
 */
class Documents extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('LIHAT_CV');
		$this->load->model(array('document_model', 'master_model'));
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

	/* ---- konfigurasi dokumen wajib per tahap (M_FLOW_STAGE_DOKUMEN) ---- */

	public function flow_docs($id_flow = NULL)
	{
		$this->require_permission('EDIT_FLOW_TEMPLATE');
		$flows = $this->master_model->list_flow();
		if ( ! $id_flow && $flows) {
			$id_flow = $flows[0]['id_flow'];
		}
		$this->load->view('layouts/main', array(
			'title'    => 'Dokumen wajib per tahap',
			'_content' => 'documents/flow_docs',
			'wide'     => TRUE,
			'flows'    => $flows,
			'id_flow'  => (int) $id_flow,
			'rows'     => $id_flow ? $this->document_model->flow_required_docs($id_flow) : array(),
			'dokumen'  => $this->master_model->list_dokumen(),
		));
	}

	public function set_flow_doc($id_flow = NULL)
	{
		$this->require_permission('EDIT_FLOW_TEMPLATE');
		if ( ! $id_flow || $this->input->method() !== 'post') {
			show_404();
		}
		$this->document_model->set_flow_doc(
			(int) $this->input->post('id_flow_stage'),
			(int) $this->input->post('id_dokumen'),
			$this->input->post('wajib')   // '1' | '0' | 'remove'
		);
		$this->session->set_flashdata('ok', 'Dokumen tahap diperbarui.');
		redirect('documents/flow_docs/' . (int) $id_flow);
	}
}
