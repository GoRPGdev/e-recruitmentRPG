<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Master Data: posisi, departemen, outlet, channel, dokumen, remark.
 * Wajib login + KELOLA_REKRUTMEN. Soft delete (toggle is_aktif), tanpa hard delete.
 */
class Master extends Secured_Controller
{
	private $types = array(
		'posisi'     => array('label' => 'Posisi',     'pk' => 'id_posisi'),
		'departemen' => array('label' => 'Departemen', 'pk' => 'id_departemen', 'tabel' => 'M_DEPARTEMEN'),
		'outlet'     => array('label' => 'Outlet',     'pk' => 'id_outlet',     'tabel' => 'M_OUTLET'),
		'channel'    => array('label' => 'Channel',    'pk' => 'id_channel',    'tabel' => 'M_CHANNEL'),
		'dokumen'    => array('label' => 'Dokumen',    'pk' => 'id_dokumen',    'tabel' => 'M_DOKUMEN'),
		'remark'     => array('label' => 'Remark Alur', 'pk' => 'id_remark',    'tabel' => 'M_REMARKS'),
	);

	public function __construct()
	{
		parent::__construct();
		$this->require_permission('KELOLA_REKRUTMEN');
		$this->load->model(array('master_model', 'requisition_model'));
		$this->load->helper(array('form', 'url'));
	}

	public function index($t = 'posisi')
	{
		if ( ! isset($this->types[$t])) {
			show_404();
		}
		$data = array(
			'title'    => 'Master: ' . $this->types[$t]['label'],
			'_content' => 'master/index',
			'wide'     => TRUE,
			't'        => $t,
			'types'    => $this->types,
		);
		switch ($t) {
			case 'posisi':
				$data['rows']    = $this->master_model->list_posisi();
				$data['depts']   = $this->master_model->list_departemen();
				$data['flows']   = $this->master_model->list_flow();
				break;
			case 'departemen':
				$data['rows'] = $this->master_model->list_departemen();
				$edit_id = (int) $this->input->get('edit_id');
				$data['edit_row'] = NULL;
				if ($edit_id) {
					$q = $this->db->query('SELECT * FROM dbo.M_DEPARTEMEN WHERE id_departemen = ?', array($edit_id));
					$data['edit_row'] = $q->row_array() ?: NULL;
				}
				break;
			case 'outlet':     $data['rows'] = $this->master_model->list_outlet(); break;
			case 'channel':    $data['rows'] = $this->master_model->list_channel(); break;
			case 'dokumen':    $data['rows'] = $this->master_model->list_dokumen(); break;
			case 'remark':
				$data['rows']       = $this->master_model->remarks();
				$data['all_stages'] = $this->master_model->all_stages();
				$data['efek']       = array('LANJUT','TOLAK','ON_HOLD','UNREACHABLE','WITHDRAWN','OFFER_DECLINED','NO_SHOW','HIRED','TALENT_POOL');
				$edit_id = (int) $this->input->get('edit_id');
				$data['edit_row'] = NULL;
				if ($edit_id) {
					$q = $this->db->query('SELECT * FROM dbo.M_REMARKS WHERE id_remark = ?', array($edit_id));
					$data['edit_row'] = $q->row_array() ?: NULL;
				}
				break;
		}
		$this->load->view('layouts/main', $data);
	}

	public function save($t = NULL)
	{
		if ( ! isset($this->types[$t]) || $this->input->method() !== 'post') {
			show_404();
		}
		$p = $this->input->post(NULL, TRUE);
		try {
			switch ($t) {
				case 'posisi':     $this->master_model->save_posisi($p, $this->auth_user['id_user']); break;
				case 'departemen': $this->master_model->save_departemen($p, $this->auth_user['id_user']); break;
				case 'outlet':     $this->master_model->save_outlet($p); break;
				case 'channel':    $this->master_model->save_channel($p); break;
				case 'dokumen':    $this->master_model->save_dokumen($p); break;
				case 'remark':     $this->master_model->save_remark($p, $this->auth_user['id_user']); break;
			}
			$this->session->set_flashdata('ok', 'Tersimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('master/index/' . $t);
	}

	public function toggle($t = NULL)
	{
		if ( ! isset($this->types[$t]) || $this->input->method() !== 'post') {
			show_404();
		}
		$id  = (int) $this->input->post('id');
		$akt = (int) $this->input->post('is_aktif');
		try {
			if ($t === 'posisi') {
				$this->master_model->toggle_posisi($id, $akt, $this->auth_user['id_user']);
			} elseif ($t === 'departemen') {
				$this->master_model->toggle_departemen($id, $akt, $this->auth_user['id_user']);
			} elseif ($t === 'remark') {
				$this->master_model->toggle_remark($id, $akt, $this->auth_user['id_user']);
			} else {
				$this->master_model->toggle_flat($this->types[$t]['tabel'], $this->types[$t]['pk'], $id, $akt);
			}
			$this->session->set_flashdata('ok', $akt ? 'Diaktifkan.' : 'Dinonaktifkan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('master/index/' . $t);
	}
}
