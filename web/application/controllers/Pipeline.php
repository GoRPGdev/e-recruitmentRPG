<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Papan pipeline vertikal per requisition + aksi flow engine.
 * Lihat: LIHAT_KANDIDAT. Aksi (advance/contact): KELOLA_REKRUTMEN.
 */
class Pipeline extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('LIHAT_KANDIDAT');
		$this->load->model(array('requisition_model', 'application_model'));
		$this->load->helper(array('form', 'url'));
	}

	public function index($id_req = NULL)
	{
		$req = $id_req ? $this->requisition_model->get($id_req) : NULL;
		if ( ! $req) {
			show_404();
		}

		$rows = $this->requisition_model->pipeline($id_req);

		// group per tahap (urutan)
		$stages = array();
		foreach ($rows as $r) {
			$key = (int) $r['urutan'];
			if ( ! isset($stages[$key])) {
				$stages[$key] = array(
					'nama'  => $r['nama_tahap'],
					'kode'  => $r['kode_stage'],
					'tipe'  => $r['tipe_tahap'],
					'id_stage' => (int) $r['id_stage'],
					'cards' => array(),
				);
			}
			$stages[$key]['cards'][] = $r;
		}
		ksort($stages);

		// remark per id_stage (untuk dropdown aksi)
		$remarks = array();
		foreach ($stages as $s) {
			$remarks[$s['id_stage']] = $this->requisition_model->remarks_for_stage($s['id_stage']);
		}

		$this->load->view('layouts/main', array(
			'title'      => 'Pipeline — ' . ($req['no_mpr'] ?: '#' . $req['id_req']),
			'_content'   => 'pipeline/board',
			'wide'       => TRUE,
			'req'        => $req,
			'stages'     => $stages,
			'remarks'    => $remarks,
			'all_stages' => $this->requisition_model->active_stages(),
			'can_aksi'   => has_permission('KELOLA_REKRUTMEN'),
		));
	}

	public function advance($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		try {
			$st = $this->requisition_model->advance(
				(int) $this->input->post('id_app_stage'),
				$this->input->post('id_remark'),
				(int) $this->auth_user['id_user'],
				$this->input->post('catatan', TRUE)
			);
			$this->session->set_flashdata('ok', 'Tahap diproses. Status: ' . $st);
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('pipeline/index/' . (int) $id_req);
	}

	public function insert_stage($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		$id_lamaran = (int) $this->input->post('id_lamaran');
		try {
			$this->requisition_model->insert_adhoc(
				$id_lamaran,
				(int) $this->input->post('id_stage'),
				$this->requisition_model->current_urutan($id_lamaran),
				(int) $this->auth_user['id_user'],
				$this->input->post('catatan', TRUE)
			);
			$this->session->set_flashdata('ok', 'Tahap sisipan ditambahkan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('pipeline/index/' . (int) $id_req);
	}

	public function contact($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		try {
			$u = $this->requisition_model->log_contact(
				(int) $this->input->post('id_lamaran'),
				$this->input->post('metode'),
				$this->input->post('hasil'),
				$this->input->post('catatan', TRUE),
				(int) $this->auth_user['id_user']
			);
			$this->session->set_flashdata('ok', 'Kontak upaya ke-' . $u . ' dicatat.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('pipeline/index/' . (int) $id_req);
	}
}
