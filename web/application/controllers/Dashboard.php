<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Secured_Controller
{
	public function index()
	{
		$this->load->view('layouts/main', array(
			'title'       => 'Dashboard',
			'_content'    => 'dashboard/index',
			'auth_user'   => $this->auth_user,
			'permissions' => $this->permissions,
		));
	}
}
