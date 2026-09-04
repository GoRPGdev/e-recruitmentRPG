<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Flow Builder: susun M_FLOW / M_FLOW_STAGE / M_REMARKS.
 * Wajib login + EDIT_FLOW_TEMPLATE (ERD sec.10.1 -- awalnya IT Admin).
 * Perubahan tidak menyentuh lamaran berjalan (di-snapshot).
 */
class Flowbuilder extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('EDIT_FLOW_TEMPLATE');
		$this->load->model('master_model', 'mm');
		$this->load->model('document_model');
		$this->load->helper(array('form', 'url'));
	}

	public function index()
	{
		$this->load->view('layouts/main', array(
			'title'    => 'Flow Builder',
			'_content' => 'flow/index',
			'wide'     => TRUE,
			'flows'    => $this->mm->list_flow(),
		));
	}

	public function edit($id_flow = NULL)
	{
		$flow = $id_flow ? $this->mm->get_flow($id_flow) : NULL;
		if ( ! $flow) {
			show_404();
		}
		$this->load->view('layouts/main', array(
			'title'      => 'Flow: ' . $flow['kode_flow'],
			'_content'   => 'flow/edit',
			'wide'       => TRUE,
			'flow'       => $flow,
			'stages'     => $this->mm->flow_stages($id_flow),
			'all_stages' => $this->mm->all_stages(),
			'roles'      => $this->mm->all_roles(),
		));
	}

	public function save_header()
	{
		if ($this->input->method() !== 'post') { show_404(); }
		try {
			$id = $this->mm->save_flow($this->input->post(NULL, TRUE));
			$this->session->set_flashdata('ok', 'Flow disimpan.');
			redirect('flowbuilder/edit/' . $id);
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('flowbuilder');
		}
	}

	public function stage_action($id_flow = NULL)
	{
		if ( ! $id_flow || $this->input->method() !== 'post') { show_404(); }
		$in = $this->input->post(NULL, TRUE);
		$in['id_flow'] = (int) $id_flow;
		try {
			$this->mm->flow_stage_action($in);
			$this->session->set_flashdata('ok', 'Tahap flow diperbarui (versi naik).');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('flowbuilder/edit/' . (int) $id_flow);
	}

	public function clone_flow()
	{
		if ($this->input->method() !== 'post') { show_404(); }
		try {
			$id = $this->mm->clone_flow(
				(int) $this->input->post('id_flow_sumber'),
				$this->input->post('kode_flow_baru', TRUE),
				$this->input->post('nama_flow_baru', TRUE)
			);
			$this->session->set_flashdata('ok', 'Template baru dibuat dari salinan.');
			redirect('flowbuilder/edit/' . $id);
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('flowbuilder');
		}
	}

	public function toggle($id_flow = NULL)
	{
		if ( ! $id_flow || $this->input->method() !== 'post') { show_404(); }
		$this->mm->toggle_flow($id_flow, (int) $this->input->post('is_aktif'));
		redirect('flowbuilder');
	}

	/* ---- master tahap seleksi (M_STAGE) ---- */

	public function stages()
	{
		$edit_id = (int) $this->input->get('edit');
		$this->load->view('layouts/main', array(
			'title'      => 'Tahap Seleksi',
			'_content'   => 'flow/stages',
			'wide'       => TRUE,
			'rows'       => $this->mm->list_stage(),
			'tipe_tahap' => array('SCREENING','KONTAK','FORM','TEST','INTERVIEW','OFFER','ONBOARD'),
			'edit_row'   => $edit_id ? $this->mm->get_stage($edit_id) : NULL,
		));
	}

	public function save_stage()
	{
		if ($this->input->method() !== 'post') { show_404(); }
		try {
			$this->mm->save_stage($this->input->post(NULL, TRUE));
			$this->session->set_flashdata('ok', 'Tahap seleksi disimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('flowbuilder/stages');
	}

	public function toggle_stage()
	{
		if ($this->input->method() !== 'post') { show_404(); }
		try {
			$this->mm->toggle_stage((int) $this->input->post('id'), (int) $this->input->post('is_aktif'));
			$this->session->set_flashdata('ok', $this->input->post('is_aktif') ? 'Diaktifkan.' : 'Dinonaktifkan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('flowbuilder/stages');
	}

	/* ---- dokumen wajib per tahap (M_FLOW_STAGE_DOKUMEN) ---- */

	public function flow_docs($id_flow = NULL)
	{
		$flows = $this->mm->list_flow();
		if ( ! $id_flow && $flows) {
			$id_flow = $flows[0]['id_flow'];
		}
		$this->load->view('layouts/main', array(
			'title'    => 'Dokumen wajib per tahap',
			'_content' => 'flow/docs',
			'wide'     => TRUE,
			'flows'    => $flows,
			'id_flow'  => (int) $id_flow,
			'rows'     => $id_flow ? $this->document_model->flow_required_docs($id_flow) : array(),
			'dokumen'  => $this->mm->list_dokumen(),
		));
	}

	public function set_flow_doc($id_flow = NULL)
	{
		if ( ! $id_flow || $this->input->method() !== 'post') {
			show_404();
		}
		$this->document_model->set_flow_doc(
			(int) $this->input->post('id_flow_stage'),
			(int) $this->input->post('id_dokumen'),
			$this->input->post('wajib')   // '1' | '0' | 'remove'
		);
		$this->session->set_flashdata('ok', 'Dokumen tahap diperbarui.');
		redirect('flowbuilder/flow_docs/' . (int) $id_flow);
	}

	/* ---- remarks ---- */

	public function remarks($id_stage = NULL)
	{
		$edit_id  = (int) $this->input->get('edit_remark');
		$edit_row = NULL;
		if ($edit_id) {
			$q = $this->db->query('SELECT * FROM dbo.M_REMARKS WHERE id_remark = ?', array($edit_id));
			$edit_row = $q->row_array() ?: NULL;
		}

		$this->load->view('layouts/main', array(
			'title'       => 'Remark',
			'_content'    => 'flow/remarks',
			'wide'        => TRUE,
			'id_stage'    => $id_stage ? (int) $id_stage : NULL,
			'rows'        => $this->mm->remarks($id_stage ? (int) $id_stage : NULL),
			'all_stages'  => $this->mm->all_stages(),
			'efek'        => array('LANJUT','TOLAK','ON_HOLD','UNREACHABLE','WITHDRAWN','OFFER_DECLINED','NO_SHOW','HIRED','TALENT_POOL'),
			'edit_remark' => $edit_row,
		));
	}

	public function save_remark()
	{
		if ($this->input->method() !== 'post') { show_404(); }
		try {
			$this->mm->save_remark($this->input->post(NULL, TRUE));
			$this->session->set_flashdata('ok', 'Remark disimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('flowbuilder/remarks' . ($this->input->post('id_stage') ? '/' . (int) $this->input->post('id_stage') : ''));
	}
}
