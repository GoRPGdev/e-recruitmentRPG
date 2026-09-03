<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Entry manual cepat -- untuk MP/outlet & walk-in.
 * Satu layar: requisition, nama, WA, (opsional) tanggal join + CV.
 * Wajib login + KELOLA_REKRUTMEN.
 */
class Manual extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('KELOLA_REKRUTMEN');
		$this->load->model('application_model', 'app_m');
		$this->load->library('form_validation');
		$this->load->helper(array('form', 'url', 'storage'));
	}

	public function index()
	{
		if ($this->input->method() === 'post') {
			return $this->_save();
		}

		$this->load->view('layouts/main', array(
			'title'    => 'Entry Manual',
			'_content' => 'manual/form',
			'reqs'     => $this->app_m->list_open_requisitions(),
			'channels' => $this->app_m->list_channels(),
		));
	}

	private function _save()
	{
		$this->form_validation->set_rules('id_req', 'Requisition', 'required|integer');
		$this->form_validation->set_rules('nama_lengkap', 'Nama', 'required|trim|max_length[150]');
		$this->form_validation->set_rules('no_wa', 'Nomor WhatsApp', 'required|trim|max_length[40]');

		if ($this->form_validation->run() === FALSE) {
			return $this->_rerender();
		}

		// CV opsional untuk entry manual
		$cv_hash = NULL; $cv_file = NULL;
		if ( ! empty($_FILES['cv']['name']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
			try {
				$cv_file = erec_store_upload($_FILES['cv'], 0, 'CV');   // id_lamaran belum ada -> pakai staging via prefix
				$cv_hash = $cv_file['hash'];
			} catch (RuntimeException $e) {
				return $this->_rerender($e->getMessage());
			}
		}

		$chan = $this->input->post('nama_channel', TRUE) ?: 'Walk-in';

		try {
			$res = $this->app_m->submit(array(
				'id_req_manual'  => (int) $this->input->post('id_req'),
				'intake_method'  => 'MANUAL',
				'nama_channel'   => $chan,
				'nama_lengkap'   => $this->input->post('nama_lengkap', TRUE),
				'no_wa_raw'      => $this->input->post('no_wa', TRUE),
				'kota_domisili'  => $this->input->post('kota_domisili', TRUE),
				'consent_versi'  => $this->config->item('erec_consent_versi'),
				'cv_hash'        => $cv_hash,
			));
		} catch (RuntimeException $e) {
			if ($cv_file) { @unlink($cv_file['path_file']); }
			return $this->_rerender($e->getMessage());
		}

		// pindahkan CV ke folder lamaran + catat
		if ($cv_file) {
			$dir = erec_storage_base() . DIRECTORY_SEPARATOR . 'lamaran' . DIRECTORY_SEPARATOR . (int) $res['id_lamaran'];
			@mkdir($dir, 0770, TRUE);
			$dest = $dir . DIRECTORY_SEPARATOR . 'CV_' . $cv_file['hash'] . '.' . $cv_file['ext'];
			if (@rename($cv_file['path_file'], $dest)) {
				$id_dok = $this->app_m->id_dokumen_by_nama('CV');
				if ($id_dok) {
					$this->app_m->save_cv_document($res['id_lamaran'], $id_dok, array(
						'path_file' => $dest, 'nama_asli' => $cv_file['nama_asli'], 'hash' => $cv_file['hash'],
						'ukuran' => $cv_file['ukuran'], 'mime' => $cv_file['mime'], 'ip' => $this->input->ip_address(),
					));
				}
			}
		}

		$tj = trim((string) $this->input->post('tanggal_join'));
		if ($tj !== '') {
			$this->app_m->add_note($res['id_lamaran'], 'Rencana tanggal join: ' . $tj, (int) $this->auth_user['id_user']);
		}

		$this->session->set_flashdata('ok',
			'Lamaran #' . $res['id_lamaran'] . ' dibuat'
			. ($res['is_kandidat_baru'] ? ' (kandidat baru).' : ' (kandidat sudah ada, dipakai ulang).'));
		redirect('manual');
	}

	private function _rerender($flash = NULL)
	{
		if ($flash) {
			$this->session->set_flashdata('error', $flash);
		}
		$this->load->view('layouts/main', array(
			'title'    => 'Entry Manual',
			'_content' => 'manual/form',
			'reqs'     => $this->app_m->list_open_requisitions(),
			'channels' => $this->app_m->list_channels(),
		));
	}
}
