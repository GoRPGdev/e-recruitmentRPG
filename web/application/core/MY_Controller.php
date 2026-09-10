<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Core Base Controllers -- E-Recruitment Ratu Pertiwi Group (RPG)
 *
 * Terdiri dari 2 kelas dasar pengendali:
 * 1. MY_Controller: Controller dasar untuk halaman publik tanpa otentikasi (misal: formulir pendaftaran /lamar dan /onboarding).
 * 2. Secured_Controller: Controller terproteksi untuk area internal yang mewajibkan sesi login aktif,
 *    menginisialisasi data pengguna ($auth_user), serta memvalidasi hak akses RBAC ($permissions).
 */
class MY_Controller extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
}

class Secured_Controller extends MY_Controller
{
	/** @var array id_user, username, nama, kode_role */
	protected $auth_user = array();

	/** @var string[] daftar kode permission efektif */
	protected $permissions = array();

	public function __construct()
	{
		parent::__construct();

		if ( ! $this->session->userdata('logged_in')) {
			redirect('auth/login');
		}

		$this->auth_user   = (array) $this->session->userdata('auth_user');
		$this->permissions = (array) $this->session->userdata('permissions');
	}

	/**
	 * Hentikan request dengan 403 kalau user tidak punya permission.
	 * Scoping "req sendiri" / "yang dia ikuti" tetap dicek terpisah di query.
	 */
	protected function require_permission($kode)
	{
		require_permission($kode);
	}

	protected function require_any_permission(array $kode_list)
	{
		require_any_permission($kode_list);
	}

	protected function require_all_permissions(array $kode_list)
	{
		require_all_permissions($kode_list);
	}
}
