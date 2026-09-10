<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Documents -- Manajemen & Verifikasi Dokumen Berkas Pelamar
 *
 * Fungsi:
 * - Menampilkan daftar dokumen yang telah diunggah pelamar (KTP, CV, Ijazah, Transkrip, Pas Foto, Surat Sehat, dsb).
 * - Menangani verifikasi berkas oleh Tim HR (status valid / revisi).
 * - Streaming file fisik secara aman dari penyimpanan di luar webroot.
 * - Proteksi akses data sensitif (KTP/KK/NPWP via ACCESS_LOG_SENSITIF) sesuai regulasi UU PDP.
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
			'dept'       => current_user_dept(),
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
		$doc = $this->document_model->get_doc($id_cand_doc);
		if ( ! $doc) {
			show_404();
		}
		$dept = current_user_dept();
		if ($dept !== NULL && (int) $doc['id_departemen'] !== (int) $dept) {
			show_error('Akses ditolak: Dokumen bukan milik kandidat departemen Anda.', 403, '403 Forbidden');
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
		$dept = current_user_dept();
		if ($dept !== NULL) {
			$lamaran_dept = $this->document_model->get_lamaran_dept($id_lamaran);
			if ($lamaran_dept === NULL || (int) $lamaran_dept !== (int) $dept) {
				show_error('Akses ditolak: Lamaran bukan dari departemen Anda.', 403, '403 Forbidden');
			}
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

		$dept = current_user_dept();
		if ($dept !== NULL && (int) $doc['id_departemen'] !== (int) $dept) {
			show_error('Akses ditolak: Dokumen bukan milik kandidat departemen Anda.', 403, '403 Forbidden');
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
