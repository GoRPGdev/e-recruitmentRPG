<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Users -- Manajemen Akun Pengguna Sistem & Hak Akses
 *
 * Fungsi:
 * - Menampilkan daftar akun pengguna internal (Super Admin, HR Admin, HR Spv, User Dept, BOD).
 * - Menambah dan mengedit akun pengguna serta mengaitkan departemen pemohon (scoping akses).
 * - Mengatur reset password terenkripsi standar password_hash bcrypt.
 * - Mengaktifkan / menonaktifkan pengguna dengan prinsip integritas soft delete (is_aktif = 0).
 * - Proteksi akses: membutuhkan permission 'SUPER_ADMIN' atau 'KELOLA_REKRUTMEN'.
 */
class Users extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('user_model');
		$this->load->helper(array('form', 'url', 'html'));
	}

	public function index()
	{
		// Filter dari query string
		$filter = array(
			'q'     => $this->input->get('q', true),
			'role'  => $this->input->get('role', true),
			'is_aktif' => $this->input->get('aktif', true),
		);

		$data = array(
			'title'    => 'Manajemen Pengguna',
			'_content' => 'users/index',
			'wide'     => TRUE,
			'filter'   => $filter,
			'roles'    => $this->user_model->get_roles(TRUE),
			'departemen' => $this->user_model->get_departemen(),
			'users'    => $this->user_model->list_users(0, 50, $filter),
		);

		$this->load->view('layouts/main', $data);
	}

	public function create()
	{
		// Ambil data default untuk form
		$data = array(
			'title'    => 'Buat Pengguna Baru',
			'_content' => 'users/form',
			'wide'     => TRUE,
			'roles'    => $this->user_model->get_roles(TRUE),
			'departemen' => $this->user_model->get_departemen(),
			'user'     => array(),
		);

		$this->load->view('layouts/main', $data);
	}

	public function store()
	{
		if ($this->input->method() !== 'post') {
			show_404();
		}

		$this->form_validation->set_rules('username', 'Username', 'required|trim|is_unique[M_USERS.username]');
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[6]|max_length[72]');
		$this->form_validation->set_rules('nama_snapshot', 'Nama Lengkap', 'required|trim');
		$this->form_validation->set_rules('id_role', 'Role', 'required|numeric');
		$this->form_validation->set_rules('id_departemen', 'Departemen', 'numeric');

		if ($this->form_validation->run()) {
			try {
				$this->user_model->create_user(array(
					'username'        => $this->input->post('username'),
					'password'        => $this->input->post('password'),
					'nama_snapshot'   => $this->input->post('nama_snapshot'),
					'nik_karyawan'    => $this->input->post('nik_karyawan'),
					'departemen_snapshot' => $this->input->post('departemen_snapshot'),
					'id_role'         => (int) $this->input->post('id_role'),
					'id_departemen'   => $this->input->post('id_departemen') !== '' ? (int) $this->input->post('id_departemen') : null,
					'is_aktif'        => $this->input->post('is_aktif') ? 1 : 0,
				), $this->auth_user['id_user'] ?? null);

				redirect('users?success=created');
			} catch (RuntimeException $e) {
				redirect('users/create?error=' . urlencode($e->getMessage()));
			}
		}

		redirect('users/create?error=' . urlencode(validation_errors()));
	}

	public function edit($id_user)
	{
		$user = $this->user_model->get_user((int) $id_user);

		if (! $user) {
			show_404();
		}

		$data = array(
			'title'    => 'Edit Pengguna: ' . html_escape($user['username']),
			'_content' => 'users/form',
			'wide'     => TRUE,
			'roles'    => $this->user_model->get_roles(TRUE),
			'departemen' => $this->user_model->get_departemen(),
			'user'     => $user,
		);

		$this->load->view('layouts/main', $data);
	}

	public function update($id_user)
	{
		if ($this->input->method() !== 'post') {
			show_404();
		}

		$this->form_validation->set_rules('username', 'Username', 'required|trim');
		$this->form_validation->set_rules('nama_snapshot', 'Nama Lengkap', 'required|trim');
		$this->form_validation->set_rules('id_role', 'Role', 'required|numeric');
		$this->form_validation->set_rules('id_departemen', 'Departemen', 'numeric');

		if ($this->form_validation->run()) {
			try {
				$this->user_model->update_user((int) $id_user, array(
					'username'        => $this->input->post('username'),
					'nama_snapshot'   => $this->input->post('nama_snapshot'),
					'nik_karyawan'    => $this->input->post('nik_karyawan'),
					'departemen_snapshot' => $this->input->post('departemen_snapshot'),
					'id_role'         => (int) $this->input->post('id_role'),
					'id_departemen'   => $this->input->post('id_departemen') !== '' ? (int) $this->input->post('id_departemen') : null,
					'is_aktif'        => $this->input->post('is_aktif') ? 1 : 0,
				), $this->auth_user['id_user'] ?? null);

				redirect('users?success=updated');
			} catch (RuntimeException $e) {
				redirect('users/edit/' . (int) $id_user . '?error=' . urlencode($e->getMessage()));
			}
		}

		redirect('users/edit/' . (int) $id_user . '?error=' . urlencode(validation_errors()));
	}

	public function toggle($id_user)
	{
		if ($this->input->method() !== 'post') {
			show_404();
		}

		$user = $this->user_model->get_user((int) $id_user);

		if (! $user) {
			redirect('users');
		}

		// Jangan bisa nonaktifkan diri sendiri
		if ((int) $user['id_user'] === (int) $this->session->userdata('auth_user')['id_user']) {
			redirect('users?error=' . urlencode('Tidak dapat menonaktifkan akun sendiri.'));
		}

		try {
			$this->user_model->toggle_status((int) $id_user, (int) $this->input->post('is_aktif'), $this->auth_user['id_user'] ?? null);
			redirect('users?success=toggled');
		} catch (RuntimeException $e) {
			redirect('users?error=' . urlencode($e->getMessage()));
		}
	}

	public function delete($id_user)
	{
		if ($this->input->method() !== 'post') {
			show_404();
		}

		$user = $this->user_model->get_user((int) $id_user);

		if (! $user) {
			redirect('users');
		}

		// Validasi 1: Tidak bisa menghapus akun sendiri
		$currUserId = (int) ($this->session->userdata('auth_user')['id_user'] ?? 0);
		if ((int) $user['id_user'] === $currUserId) {
			redirect('users?error=' . urlencode('Tidak dapat menghapus akun Anda sendiri.'));
		}

		// Validasi 2: Mencegah penghapusan jika ini satu-satunya SUPER_ADMIN yang aktif
		if ($user['kode_role'] === 'SUPER_ADMIN') {
			$activeSuperCount = $this->user_model->count_users(array('role' => 'SUPER_ADMIN', 'is_aktif' => 1));
			if ($activeSuperCount <= 1) {
				redirect('users?error=' . urlencode('Tidak dapat menghapus Super Admin terakhir yang aktif.'));
			}
		}

		try {
			$this->user_model->delete_user((int) $id_user, $this->auth_user['id_user'] ?? null);
			redirect('users?success=deleted');
		} catch (RuntimeException $e) {
			redirect('users?error=' . urlencode($e->getMessage()));
		}
	}
}