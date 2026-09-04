<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('LIHAT_KANDIDAT');
		$this->load->model('dashboard_model', 'dm');
		$this->load->helper('url');
	}

	public function index()
	{
		$dept = current_user_dept();
		$f = array(
			'dari'       => $this->input->get('dari') ?: NULL,
			'sampai'     => $this->input->get('sampai') ?: NULL,
			'dept'       => $dept !== NULL ? $dept : ($this->input->get('dept') ?: NULL),
			'posisi'     => $this->input->get('posisi') ?: NULL,
			'outlet'     => $this->input->get('outlet') ?: NULL,
			'flow'       => $this->input->get('flow') ?: NULL,
			'tipe_tahap' => $this->input->get('tipe_tahap') ?: NULL,
			'status'     => $this->input->get('status') ?: NULL,
			'channel'    => $this->input->get('channel') ?: NULL,
			'pic'        => $this->input->get('pic') ?: NULL,
		);

		$d = $this->dm->dashboard($f);

		$depts = $this->dm->departments();
		if ($dept !== NULL) {
			$depts = array_values(array_filter($depts, function ($item) use ($dept) {
				return (int) $item['id_departemen'] === (int) $dept;
			}));
		}

		$this->load->view('layouts/main', array(
			'title'    => 'Dashboard',
			'_content' => 'dashboard/index',
			'wide'     => TRUE,
			'f'        => $f,
			'd'        => $d,
			'trend'    => $this->dm->funnel_trend(14, $f['dept']),
			'opt'      => array(
				'dept'    => $depts,
				'posisi'  => $this->dm->positions($dept),
				'outlet'  => $this->dm->outlets(),
				'flow'    => $this->dm->flows(),
				'channel' => $this->dm->channels(),
				'pic'     => $this->dm->roles(),
			),
			'tipe_tahap' => array('SCREENING','KONTAK','FORM','TEST','INTERVIEW','OFFER','ONBOARD'),
			'status_global' => array('In_Progress','On_Hold','Unreachable','Rejected','Withdrawn','Offer_Declined','No_Show','Hired','Talent_Pool'),
		));
	}
}
