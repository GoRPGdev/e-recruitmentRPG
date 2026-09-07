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
		$au   = current_user();
		$role = $au['kode_role'] ?? 'SUPER_ADMIN';

		$tgl_single = $this->input->get('tanggal') ?: NULL;
		$f = array(
			'dari'       => $this->input->get('dari') ?: $tgl_single,
			'sampai'     => $this->input->get('sampai') ?: $tgl_single,
			'tanggal'    => $tgl_single,
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
		$pos_stage_funnel = $this->dm->position_stage_funnel($f);

		$depts = $this->dm->departments();
		if ($dept !== NULL) {
			$depts = array_values(array_filter($depts, function ($item) use ($dept) {
				return (int) $item['id_departemen'] === (int) $dept;
			}));
		}

		// Data khusus jika role USER_DEPT
		$dept_mpr        = array();
		$dept_candidates = array();
		$dept_metrics    = array();
		if ($role === 'USER_DEPT' && $dept !== NULL) {
			$dept_mpr        = $this->dm->dept_requisitions($dept, 6);
			$dept_candidates = $this->dm->dept_candidates_active($dept, 8);
			$dept_metrics    = $this->dm->dept_summary_metrics($dept);
		}

		$this->load->view('layouts/main', array(
			'title'           => 'Dashboard — ' . ($role === 'USER_DEPT' ? 'Departemen' : 'Recruitment Ops'),
			'_content'        => 'dashboard/index',
			'wide'            => TRUE,
			'user_role'       => $role,
			'user_nama'       => $au['nama_snapshot'] ?? ($au['nama'] ?? ($au['username'] ?? 'User')),
			'user_dept_nama'  => $au['departemen_snapshot'] ?? '',
			'dept_mpr'        => $dept_mpr,
			'dept_candidates' => $dept_candidates,
			'dept_metrics'     => $dept_metrics,
			'pos_stage_funnel' => $pos_stage_funnel,
			'f'                => $f,
			'd'               => $d,
			'trend'           => $this->dm->funnel_trend(14, $f['dept']),
			'opt'             => array(
				'dept'    => $depts,
				'posisi'  => $this->dm->positions($dept),
				'outlet'  => $this->dm->outlets(),
				'flow'    => $this->dm->flows(),
				'channel' => $this->dm->channels(),
				'pic'     => $this->dm->roles(),
			),
			'tipe_tahap'    => array('SCREENING','KONTAK','FORM','TEST','INTERVIEW','OFFER','ONBOARD'),
			'status_global' => array('In_Progress','On_Hold','Unreachable','Rejected','Withdrawn','Offer_Declined','No_Show','Hired','Talent_Pool'),
		));
	}
}
