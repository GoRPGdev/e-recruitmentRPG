<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Papan pipeline vertikal per requisition + aksi flow engine.
 * Lihat: LIHAT_KANDIDAT. Aksi (advance/contact): KELOLA_REKRUTMEN.
 */
class Pipeline extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('LIHAT_KANDIDAT');
		$this->load->model(array('requisition_model', 'application_model'));
		$this->load->helper(array('form', 'url'));
	}

	public function index($id_req = NULL)
	{
		$req = $id_req ? $this->requisition_model->get($id_req) : NULL;
		if ( ! $req) {
			show_404();
		}

		$rows = $this->requisition_model->pipeline($id_req);

		// group per tahap (urutan)
		$stages = array();
		foreach ($rows as $r) {
			$key = (int) $r['urutan'];
			if ( ! isset($stages[$key])) {
				$stages[$key] = array(
					'nama'  => $r['nama_tahap'],
					'kode'  => $r['kode_stage'],
					'tipe'  => $r['tipe_tahap'],
					'id_stage' => (int) $r['id_stage'],
					'cards' => array(),
				);
			}
			$stages[$key]['cards'][] = $r;
		}
		ksort($stages);

		// remark per id_stage (untuk dropdown aksi)
		$remarks = array();
		foreach ($stages as $s) {
			$remarks[$s['id_stage']] = $this->requisition_model->remarks_for_stage($s['id_stage']);
		}

		// Ambil data seleksi lanjutan: interview, psikotes, offer
		$app_stage_ids = array();
		$lamaran_ids   = array();
		foreach ($rows as $r) {
			$app_stage_ids[] = (int) $r['id_app_stage'];
			$lamaran_ids[]   = (int) $r['id_lamaran'];
		}
		$app_stage_ids = array_values(array_unique($app_stage_ids));
		$lamaran_ids   = array_values(array_unique($lamaran_ids));

		$interviews   = $this->requisition_model->get_interviews_for_stages($app_stage_ids);
		$psikotes     = $this->requisition_model->get_psikotes_for_stages($app_stage_ids);
		$offers       = $this->requisition_model->get_offers_for_lamaran($lamaran_ids);
		$interviewers = $this->requisition_model->get_interviewers();

		// Jika ada data offer dan user berhak melihat gaji, catat log akses sensitif
		$can_gaji = can_sensitif('GAJI');
		if ($can_gaji && ! empty($offers)) {
			log_akses_sensitif('GAJI', (int) $id_req);
		}

		$this->load->view('layouts/main', array(
			'title'        => 'Pipeline — ' . ($req['no_mpr'] ?: '#' . $req['id_req']),
			'_content'     => 'pipeline/board',
			'wide'         => TRUE,
			'req'          => $req,
			'stages'       => $stages,
			'remarks'      => $remarks,
			'all_stages'   => $this->requisition_model->active_stages(),
			'can_aksi'     => has_permission('KELOLA_REKRUTMEN'),
			'interviews'   => $interviews,
			'psikotes'     => $psikotes,
			'offers'       => $offers,
			'interviewers' => $interviewers,
			'can_gaji'     => $can_gaji,
		));
	}

	public function advance($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		try {
			$st = $this->requisition_model->advance(
				(int) $this->input->post('id_app_stage'),
				$this->input->post('id_remark'),
				(int) $this->auth_user['id_user'],
				$this->input->post('catatan', TRUE)
			);
			$this->session->set_flashdata('ok', 'Tahap diproses. Status: ' . $st);
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('pipeline/index/' . (int) $id_req);
	}

	public function insert_stage($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		$id_lamaran = (int) $this->input->post('id_lamaran');
		try {
			$this->requisition_model->insert_adhoc(
				$id_lamaran,
				(int) $this->input->post('id_stage'),
				$this->requisition_model->current_urutan($id_lamaran),
				(int) $this->auth_user['id_user'],
				$this->input->post('catatan', TRUE)
			);
			$this->session->set_flashdata('ok', 'Tahap sisipan ditambahkan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('pipeline/index/' . (int) $id_req);
	}

	public function contact($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		try {
			$u = $this->requisition_model->log_contact(
				(int) $this->input->post('id_lamaran'),
				$this->input->post('metode'),
				$this->input->post('hasil'),
				$this->input->post('catatan', TRUE),
				(int) $this->auth_user['id_user']
			);
			$this->session->set_flashdata('ok', 'Kontak upaya ke-' . $u . ' dicatat.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('pipeline/index/' . (int) $id_req);
	}

	public function save_interview($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		try {
			$in = array(
				'id_interview'      => $this->input->post('id_interview'),
				'id_app_stage'      => (int) $this->input->post('id_app_stage'),
				'tipe'              => $this->input->post('tipe'),
				'jadwal'            => $this->input->post('jadwal'),
				'lokasi_atau_link'  => $this->input->post('lokasi_atau_link', TRUE),
				'hasil'             => $this->input->post('hasil'),
				'skor'              => $this->input->post('skor'),
				'catatan'           => $this->input->post('catatan', TRUE),
				'id_interviewer'    => $this->input->post('id_interviewer'),
				'peran_interviewer' => $this->input->post('peran_interviewer'),
			);
			$this->requisition_model->save_interview($in, (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'Data interview berhasil disimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('pipeline/index/' . (int) $id_req);
	}

	public function save_psikotes($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		try {
			$in = array(
				'id_psikotes'  => $this->input->post('id_psikotes'),
				'id_app_stage' => (int) $this->input->post('id_app_stage'),
				'vendor_tes'   => $this->input->post('vendor_tes', TRUE),
				'tanggal_tes'  => $this->input->post('tanggal_tes'),
				'skor_total'   => $this->input->post('skor_total'),
				'hasil'        => $this->input->post('hasil'),
				'rekomendasi'  => $this->input->post('rekomendasi', TRUE),
			);
			$this->requisition_model->save_psikotes($in, (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'Hasil psikotes berhasil disimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('pipeline/index/' . (int) $id_req);
	}

	public function save_offer($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		try {
			$gaji = $this->input->post('gaji_ditawarkan');
			$gaji_val = NULL;
			if (can_sensitif('GAJI')) {
				if ($gaji !== '' && $gaji !== NULL) {
					$gaji_val = (float) str_replace(array('.', ','), array('', '.'), $gaji);
					log_akses_sensitif('GAJI', (int) $id_req);
				}
			}
			$in = array(
				'id_offer'                => $this->input->post('id_offer'),
				'id_lamaran'              => (int) $this->input->post('id_lamaran'),
				'gaji_ditawarkan'         => $gaji_val,
				'tanggal_penawaran'       => $this->input->post('tanggal_penawaran'),
				'tanggal_join_disepakati' => $this->input->post('tanggal_join_disepakati'),
				'tanggal_join_aktual'     => $this->input->post('tanggal_join_aktual'),
				'status_offer'            => $this->input->post('status_offer'),
				'alasan'                  => $this->input->post('alasan', TRUE),
			);
			$this->requisition_model->save_offer($in, (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'Data penawaran kerja (offer) berhasil disimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('pipeline/index/' . (int) $id_req);
	}
}
