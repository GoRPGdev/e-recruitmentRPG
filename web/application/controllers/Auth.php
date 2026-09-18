<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Auth -- Manajemen Autentikasi Sistem E-Recruitment RPG
 *
 * Fungsi:
 * - Menangani proses otentikasi login pengguna berbasis role & departemen.
 * - Memuat session, data snapshot user, dan perizinan akses (permissions RBAC).
 * - Menangani proses logout dan pengalihan sesi aman.
 */
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
			$au = (array) $this->session->userdata('auth_user');
			if (($au['kode_role'] ?? '') === 'USER_DEPT') {
				redirect('requisitions');
			}
			redirect('dashboard');
		}

		if ($this->input->method() === 'post') {
			$this->form_validation->set_rules('nik', 'NIK Karyawan', 'required|trim');
			$this->form_validation->set_rules('password', 'Password', 'required');

			if ($this->form_validation->run()) {
				$nik = trim((string) $this->input->post('nik', TRUE));
				$user = $this->auth_model->get_user_by_nik($nik);

				if ($user && password_verify((string) $this->input->post('password'), $user['password_hash'])) {
					$this->session->sess_regenerate(TRUE);
					$this->session->set_userdata(array(
						'logged_in'   => TRUE,
						'login_time'  => time(),
						'auth_user'   => array(
							'id_user'       => (int) $user['id_user'],
							'nik_karyawan'  => $user['nik_karyawan'] ?? $nik,
							'nama'          => $user['nama_snapshot'],
							'kode_role'     => $user['kode_role'],
							'id_departemen' => isset($user['id_departemen']) && $user['id_departemen'] !== NULL
								? (int) $user['id_departemen'] : NULL,
							'region'        => isset($user['region']) && $user['region'] !== NULL && $user['region'] !== ''
								? (string) $user['region'] : NULL,
						),
						'permissions' => $this->auth_model->get_permissions($user['id_user']),
					));
					if (($user['kode_role'] ?? '') === 'USER_DEPT') {
						redirect('requisitions');
					} else {
						redirect('dashboard');
					}
				}

				$this->session->set_flashdata('error', 'NIK Karyawan atau password salah.');
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
