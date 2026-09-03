<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kelola link form publik + token berkas. Wajib login + KELOLA_REKRUTMEN.
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
			'title'    => 'Link Form',
			'_content' => 'postings/index',
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

		if ($this->input->method() === 'post') {
			$act = $this->input->post('act');
			if ($act === 'create') {
				$tujuan = in_array($this->input->post('tujuan'), array('FORM2', 'UPLOAD_DOKUMEN'), TRUE)
					? $this->input->post('tujuan') : 'UPLOAD_DOKUMEN';
				$hari = (int) $this->input->post('masa_hari') ?: 7;
				$this->pm->create_token($id_lamaran, $tujuan, $hari, (int) $this->auth_user['id_user']);
				$this->session->set_flashdata('ok', 'Token dibuat.');
			} elseif ($act === 'revoke') {
				$this->pm->revoke_token((int) $this->input->post('id_token'));
				$this->session->set_flashdata('ok', 'Token dicabut.');
			}
			redirect('postings/tokens/' . $id_lamaran);
		}

		$this->load->view('layouts/main', array(
			'title'      => 'Token Berkas #' . $id_lamaran,
			'_content'   => 'postings/tokens',
			'id_lamaran' => $id_lamaran,
			'tokens'     => $this->pm->list_tokens($id_lamaran),
		));
	}
}
