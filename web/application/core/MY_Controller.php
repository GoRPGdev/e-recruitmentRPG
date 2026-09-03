<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller untuk semua controller aplikasi.
 * MY_Controller           -> publik (form lamar, dsb.)
 * Secured_Controller      -> wajib login; menyiapkan $this->auth_user & $this->permissions
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
		if ( ! has_permission($kode)) {
			show_error('Butuh permission: ' . html_escape($kode), 403, 'Akses ditolak');
		}
	}
}
