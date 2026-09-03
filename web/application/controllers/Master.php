<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Master Data: posisi, departemen, outlet, channel, dokumen.
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
			case 'departemen': $data['rows'] = $this->master_model->list_departemen(); break;
			case 'outlet':     $data['rows'] = $this->master_model->list_outlet(); break;
			case 'channel':    $data['rows'] = $this->master_model->list_channel(); break;
			case 'dokumen':    $data['rows'] = $this->master_model->list_dokumen(); break;
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
				case 'posisi':     $this->master_model->save_posisi($p); break;
				case 'departemen': $this->master_model->save_departemen($p); break;
				case 'outlet':     $this->master_model->save_outlet($p); break;
				case 'channel':    $this->master_model->save_channel($p); break;
				case 'dokumen':    $this->master_model->save_dokumen($p); break;
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
				$this->master_model->toggle_posisi($id, $akt);
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
