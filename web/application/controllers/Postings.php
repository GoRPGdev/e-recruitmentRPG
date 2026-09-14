<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Postings -- Manajemen Publikasi Lowongan Kerja & Token Berkas
 *
 * Fungsi:
 * - Mengelola tautan publikasi form lamaran online (URL slug) untuk setiap batch MPR.
 * - Mengatur pembukaan dan penutupan form pendaftaran kandidat.
 * - Mengonfigurasi form settings (pertanyaan kuesioner dan berkas prasyarat tambahan).
 * - Menampilkan metrik submit pelamar pada lowongan terkait.
 * - Proteksi akses: membutuhkan permission 'KELOLA_REKRUTMEN'.
 */
class Postings extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('KELOLA_REKRUTMEN');
		$this->load->model('posting_model', 'pm');
		$this->load->library('form_validation');
		$this->load->helper(array('form', 'url'));
	}

	public function index()
	{
		$per    = 20;
		$page   = max(1, (int) $this->input->get('page'));
		$offset = ($page - 1) * $per + 1;

		$total = $this->pm->count_postings();
		$rows  = $this->pm->list_postings($offset, $per);

		$this->load->view('layouts/main', array(
			'title'    => 'Form Publik & Lowongan',
			'_content' => 'postings/index',
			'wide'     => TRUE,
			'rows'     => $rows,
			'page'     => $page,
			'pages'    => max(1, (int) ceil($total / $per)),
			'total'    => $total,
		));
	}

	public function form_settings($id_posting = NULL)
	{
		$posting = $id_posting ? $this->pm->get_posting($id_posting) : NULL;
		if ( ! $posting) {
			show_404();
		}

		if ($this->input->method() === 'post') {
			$aktif   = $this->input->post('form_aktif') ? 1 : 0;
			$dibuka  = trim((string) $this->input->post('form_dibuka'))  ?: NULL;
			$ditutup = trim((string) $this->input->post('form_ditutup')) ?: NULL;

			if ($dibuka && $ditutup && strtotime($dibuka) !== FALSE && strtotime($ditutup) !== FALSE
			    && strtotime($ditutup) < strtotime($dibuka)) {
				$this->session->set_flashdata('error', 'Tanggal tutup lebih awal dari tanggal buka.');
				redirect('postings/form_settings/' . (int) $id_posting);
			}

			$this->pm->ensure_slug($id_posting);
			$this->pm->update_form_settings($id_posting, $aktif, $dibuka, $ditutup);
			$this->session->set_flashdata('ok', 'Pengaturan link form disimpan.');
			redirect('postings/form_settings/' . (int) $id_posting);
		}

		$slug = $this->pm->ensure_slug($id_posting);
		$this->load->view('layouts/main', array(
			'title'      => 'Link Form: ' . $posting['nama_posisi'],
			'_content'   => 'postings/form_settings',
			'posting'    => $this->pm->get_posting($id_posting),
			'slug'       => $slug,
			'public_url' => site_url('lamar/' . $slug),
		));
	}

	public function toggle($id_posting = NULL)
	{
		if ( ! $id_posting || $this->input->method() !== 'post') {
			show_404();
		}

		try {
			$form_aktif = $this->input->post('form_aktif');
			$aktif_target = ($form_aktif !== NULL && $form_aktif !== '') ? (int) $form_aktif : NULL;

			$status_akhir = $this->pm->toggle_form($id_posting, $aktif_target, (int) $this->auth_user['id_user']);
			$pesan = $status_akhir ? 'Form publik dibuka (menerima lamaran).' : 'Form publik ditutup.';
			$this->session->set_flashdata('ok', $pesan);
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}

		$redirect = $this->input->post('redirect_to', TRUE);
		if ($redirect && strpos($redirect, '://') === FALSE) {
			redirect($redirect);
		}
		redirect('postings');
	}

	public function extend($id_posting = NULL)
	{
		if ( ! $id_posting || $this->input->method() !== 'post') {
			show_404();
		}

		$durasi_hari = max(1, (int) ($this->input->post('durasi_hari') ?: 14));
		try {
			$this->pm->extend_posting($id_posting, $durasi_hari, (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'Batas waktu lowongan berhasil diperpanjang (+' . $durasi_hari . ' hari).');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}

		$redirect = $this->input->post('redirect_to', TRUE);
		if ($redirect && strpos($redirect, '://') === FALSE) {
			redirect($redirect);
		}
		redirect('postings/form_settings/' . (int) $id_posting);
	}

	public function stats($id_posting = NULL)
	{
		$posting = $id_posting ? $this->pm->get_posting($id_posting) : NULL;
		if ( ! $posting) {
			show_404();
		}
		if ($this->input->method() === 'post') {
			$this->pm->save_stats(
				$id_posting,
				(int) $this->input->post('jumlah_pelamar_masuk'),
				(int) $this->input->post('cv_sesuai'),
				(int) $this->input->post('cv_tidak_sesuai'),
				(int) $this->auth_user['id_user']
			);
			$this->session->set_flashdata('ok', 'Statistik posting disimpan.');
			redirect('postings/stats/' . (int) $id_posting);
		}
		$this->load->view('layouts/main', array(
			'title'    => 'Statistik: ' . $posting['nama_posisi'],
			'_content' => 'postings/stats',
			'posting'  => $posting,
			'stats'    => $this->pm->get_stats($id_posting),
		));
	}

	public function tokens($id_lamaran = NULL)
	{
		$id_lamaran = (int) $id_lamaran;
		if ( ! $id_lamaran) {
			show_404();
		}
		$lamaran = $this->pm->get_lamaran($id_lamaran);
		if ( ! $lamaran) {
			show_404();
		}
		$tokens = $this->pm->list_tokens($id_lamaran);
		$this->load->view('layouts/main', array(
			'title'    => 'Token Berkas: ' . $lamaran['nama_lengkap'],
			'_content' => 'postings/tokens',
			'lamaran'  => $lamaran,
			'tokens'   => $tokens,
		));
	}

	public function generate_token($id_lamaran = NULL)
	{
		$id_lamaran = (int) $id_lamaran;
		if ( ! $id_lamaran || $this->input->method() !== 'post') {
			show_404();
		}
		$token = $this->pm->generate_token($id_lamaran, (int) $this->auth_user['id_user']);
		$this->session->set_flashdata('ok', 'Token dibuat: ' . $token);
		redirect('postings/tokens/' . $id_lamaran);
	}
}
