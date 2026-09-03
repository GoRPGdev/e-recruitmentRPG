<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('auth_model');
		$this->load->library('form_validation');
	}

	public function index()
	{
		$this->login();
	}

	public function login()
	{
		if ($this->session->userdata('logged_in')) {
			redirect('dashboard');
		}

		if ($this->input->method() === 'post') {
			$this->form_validation->set_rules('username', 'Username', 'required|trim');
			$this->form_validation->set_rules('password', 'Password', 'required');

			if ($this->form_validation->run()) {
				$user = $this->auth_model->get_user_by_username($this->input->post('username', TRUE));

				if ($user && password_verify((string) $this->input->post('password'), $user['password_hash'])) {
					$this->session->sess_regenerate(TRUE);
					$this->session->set_userdata(array(
						'logged_in'   => TRUE,
						'auth_user'   => array(
							'id_user'   => (int) $user['id_user'],
							'username'  => $user['username'],
							'nama'      => $user['nama_snapshot'],
							'kode_role' => $user['kode_role'],
						),
						'permissions' => $this->auth_model->get_permissions($user['id_user']),
					));
					redirect('dashboard');
				}

				$this->session->set_flashdata('error', 'Username atau password salah.');
				redirect('auth/login');
			}
		}

		$this->load->view('layouts/main', array(
			'title'    => 'Masuk',
			'_content' => 'auth/login',
		));
	}

	public function logout()
	{
		$this->session->sess_destroy();
		redirect('auth/login');
	}
}
