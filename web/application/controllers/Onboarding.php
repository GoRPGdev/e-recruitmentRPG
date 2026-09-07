<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Onboarding Controller -- Halaman publik pengisian kelengkapan data karyawan baru
 * Akses: /onboarding/<token> (tanpa login, aman via token unik)
 */
class Onboarding extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('onboarding_model');
		$this->load->helper(array('form', 'url', 'security'));
	}

	public function index($token = NULL)
	{
		if ( ! $token) {
			show_404();
		}

		$t = $this->onboarding_model->resolve_token($token);
		if ( ! $t) {
			show_404();
		}

		// Validasi token
		if ( ! $t['valid']) {
			return $this->load->view('layouts/main', array(
				'title'    => 'Tautan Onboarding Tidak Berlaku',
				'_content' => 'onboarding/kadaluarsa',
				't'        => $t,
			));
		}

		if ($this->input->method() === 'post') {
			return $this->_submit($token, $t);
		}

		// Ambil riwayat pengalaman & keluarga jika sudah ada
		$experiences = $this->onboarding_model->get_work_experiences($t['id_lamaran']);
		$families    = $this->onboarding_model->get_family_members($t['id_kandidat']);

		$this->load->view('layouts/main', array(
			'title'       => 'Formulir Onboarding Karyawan Baru — ' . $t['nama_lengkap'],
			'_content'    => 'onboarding/form',
			't'           => $t,
			'experiences' => $experiences,
			'families'    => $families,
		));
	}

	private function _submit($token, $t)
	{
		$post_exp = $this->input->post('exp');
		$post_fam = $this->input->post('fam');

		$experiences = array();
		if (!empty($post_exp) && is_array($post_exp)) {
			foreach ($post_exp as $row) {
				if (!empty($row['nama_perusahaan']) && !empty($row['posisi_jabatan'])) {
					$experiences[] = array(
						'nama_perusahaan' => trim($row['nama_perusahaan']),
						'posisi_jabatan'  => trim($row['posisi_jabatan']),
						'periode_kerja'   => trim($row['periode_kerja'] ?? ''),
						'gaji_terakhir'   => trim($row['gaji_terakhir'] ?? ''),
						'alasan_keluar'   => trim($row['alasan_keluar'] ?? ''),
						'deskripsi_tugas' => trim($row['deskripsi_tugas'] ?? ''),
					);
				}
			}
		}

		$family = array();
		if (!empty($post_fam) && is_array($post_fam)) {
			foreach ($post_fam as $f) {
				if (!empty($f['hubungan']) && !empty($f['nama_lengkap'])) {
					$family[] = array(
						'hubungan'      => trim($f['hubungan']),
						'nama_lengkap'  => trim($f['nama_lengkap']),
						'jenis_kelamin' => in_array($f['jenis_kelamin'] ?? '', array('L', 'P')) ? $f['jenis_kelamin'] : NULL,
						'usia'          => !empty($f['usia']) ? (int) $f['usia'] : NULL,
						'pendidikan'    => trim($f['pendidikan'] ?? ''),
						'pekerjaan'     => trim($f['pekerjaan'] ?? ''),
						'no_telp'       => trim($f['no_telp'] ?? ''),
					);
				}
			}
		}

		$in = array(
			'id_lamaran'         => (int) $t['id_lamaran'],
			'id_kandidat'        => (int) $t['id_kandidat'],
			'token'              => $token,
			'nama_panggilan'     => $this->input->post('nama_panggilan', TRUE),
			'nik'                => $this->input->post('nik', TRUE),
			'no_sim'             => $this->input->post('no_sim', TRUE),
			'npwp'               => $this->input->post('npwp', TRUE),
			'agama'              => $this->input->post('agama', TRUE),
			'gol_darah'          => $this->input->post('gol_darah', TRUE),
			'tinggi_badan'       => $this->input->post('tinggi_badan') ? (int) $this->input->post('tinggi_badan') : NULL,
			'berat_badan'        => $this->input->post('berat_badan') ? (int) $this->input->post('berat_badan') : NULL,
			'nama_bank'          => $this->input->post('nama_bank', TRUE),
			'no_rekening'        => $this->input->post('no_rekening', TRUE),
			'nama_pemilik_bank'  => $this->input->post('nama_pemilik_bank', TRUE),
			'riwayat_penyakit'   => $this->input->post('riwayat_penyakit', TRUE),
			'consent_kesehatan'  => $this->input->post('consent_kesehatan') ? 1 : 0,
			'experiences'        => $experiences,
			'family'             => $family,
			'ip'                 => $this->input->ip_address(),
		);

		try {
			$this->onboarding_model->save_onboarding($in);

			return $this->load->view('layouts/main', array(
				'title'    => 'Formulir Onboarding Terkirim',
				'_content' => 'onboarding/sukses',
				't'        => $t,
			));
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('onboarding/' . $token);
		}
	}
}
