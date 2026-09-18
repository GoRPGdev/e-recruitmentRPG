<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Sso -- Jembatan Single Sign-On dari Aplikasi Payroll RPG
 *
 * Fungsi:
 * - masuk(): titik masuk yang diklik user dari menu "E-Recruitment" di
 *   Payroll. Menerima tiket sekali-pakai (NIK + waktu kadaluarsa + tanda
 *   tangan HMAC) yang dibuat Payroll SETELAH user login di sana, lalu
 *   membuat sesi login e-recruitment sendiri -- tanpa form login lagi.
 * - cek_akses(): dipanggil SERVER-KE-SERVER oleh Payroll (bukan browser
 *   user) untuk menentukan apakah menu "E-Recruitment" ditampilkan di
 *   sidebarnya untuk NIK tertentu.
 *
 * PENTING -- batas kepercayaan:
 * - SP di sini TIDAK PERNAH memverifikasi password. Kepercayaan sepenuhnya
 *   berasal dari tanda tangan HMAC yang cuma bisa dibuat oleh pihak yang
 *   punya EREC_SSO_SECRET (Payroll). Kalau secret belum diisi di server ini,
 *   SEMUA percobaan SSO ditolak (fail closed), bukan diam-diam diloloskan.
 * - NIK yang valid tanda tangannya TETAP harus terdaftar aktif di M_USERS.
 *   SSO tidak pernah membuat akun baru secara otomatis -- itu wewenang HR
 *   lewat layar Manajemen Pengguna.
 */
class Sso extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('auth_model');
	}

	/**
	 * URL: /sso/masuk?nik=...&exp=...&sig=...
	 */
	public function masuk()
	{
		$nik = (string) $this->input->get('nik');
		$exp = (int) $this->input->get('exp');
		$sig = (string) $this->input->get('sig');

		if (! $this->_token_valid($nik, $exp, $sig)) {
			log_message('error', 'SSO ditolak: token tidak valid/kadaluarsa (nik=' . $nik . ')');
			$this->_gagal('Tautan masuk sudah kedaluwarsa atau tidak valid. Silakan klik lagi menu E-Recruitment dari Payroll.');
			return;
		}

		$user = $this->auth_model->get_user_by_nik($nik);

		if (! $user) {
			log_message('error', 'SSO ditolak: NIK belum terdaftar aktif di e-recruitment (nik=' . $nik . ')');
			$this->_gagal('Akun Anda belum didaftarkan di e-Recruitment RPG. Hubungi tim HR/IT untuk didaftarkan terlebih dahulu.');
			return;
		}

		$this->session->sess_regenerate(TRUE);
		$this->session->set_userdata(array(
			'logged_in' => TRUE,
			'auth_user' => array(
				'id_user'       => (int) $user['id_user'],
				'username'      => $user['username'],
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

	/**
	 * URL: /sso/cek_akses?nik=...   Header: Authorization: Bearer <secret>
	 * Balasan JSON: {"allowed": true|false}
	 */
	public function cek_akses()
	{
		header('Content-Type: application/json');

		$secret = (string) $this->config->item('erec_sso_secret');
		$auth   = (string) $this->input->get_request_header('Authorization', TRUE);
		$bearer = trim(str_ireplace('Bearer', '', $auth));

		if ($secret === '' || ! hash_equals($secret, $bearer)) {
			http_response_code(401);
			echo json_encode(array('error' => 'unauthorized'));
			return;
		}

		$nik  = (string) $this->input->get('nik');
		$user = $nik !== '' ? $this->auth_model->get_user_by_nik($nik) : NULL;

		echo json_encode(array('allowed' => $user !== NULL));
	}

	private function _gagal($pesan)
	{
		$this->load->view('layouts/main', array(
			'title'    => 'Tidak Bisa Masuk',
			'_content' => 'sso/gagal',
			'pesan'    => $pesan,
		));
	}

	/**
	 * @return bool
	 */
	private function _token_valid($nik, $exp, $sig)
	{
		$secret = (string) $this->config->item('erec_sso_secret');

		if ($secret === '') {
			return FALSE; // belum dikonfigurasi di server ini -> tolak semua, jangan diam-diam diloloskan
		}
		if ($nik === '' || $exp <= 0 || $sig === '') {
			return FALSE;
		}

		$max_age = (int) ($this->config->item('erec_sso_max_age') ?: 90);
		if ($exp < time() || $exp > time() + $max_age) {
			return FALSE; // kadaluarsa, atau tanggal kadaluarsa dibuat terlalu jauh ke depan
		}

		$expected = hash_hmac('sha256', $nik . '|' . $exp, $secret);
		return hash_equals($expected, $sig);
	}
}
