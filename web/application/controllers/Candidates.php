<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Candidates -- Layar Detail Profil Kandidat (G3).
 * Proteksi umum: LIHAT_KANDIDAT.
 * Proteksi scoping: USER_DEPT hanya bisa melihat kandidat departemennya (G4b).
 * Proteksi data sensitif:
 *   - Gaji terakhir & harapan: LIHAT_GAJI_PELAMAR -> ACCESS_LOG_SENSITIF (GAJI)
 *   - Riwayat kesehatan: LIHAT_KESEHATAN -> ACCESS_LOG_SENSITIF (KESEHATAN)
 *   - No rekening bank: LIHAT_FINANSIAL -> ACCESS_LOG_SENSITIF (FINANSIAL)
 *   - Range gaji / offer: LIHAT_GAJI -> ACCESS_LOG_SENSITIF (GAJI)
 */
class Candidates extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('LIHAT_KANDIDAT');
		$this->load->model('candidate_model');
		$this->load->helper(array('url', 'form', 'rbac'));
	}

	public function detail($id_lamaran = NULL)
	{
		if ( ! $id_lamaran) {
			show_404();
		}

		$detail = $this->candidate_model->get_detail($id_lamaran);
		if ( ! $detail) {
			show_404();
		}

		// Scoping USER_DEPT (G4b)
		$dept = current_user_dept();
		if ($dept !== NULL && (int) $detail['id_departemen'] !== (int) $dept) {
			show_error('Akses ditolak: Kandidat bukan dari lowongan departemen Anda.', 403, '403 Forbidden');
		}

		// Ambil data pendukung
		$profile   = $this->candidate_model->get_profile($id_lamaran);
		$health    = $this->candidate_model->get_health($detail['id_kandidat']);
		$bank      = $this->candidate_model->get_bank($id_lamaran);
		$stages    = $this->candidate_model->get_stages($id_lamaran);
		$documents = $this->candidate_model->get_documents($id_lamaran);
		$history   = $this->candidate_model->get_history($id_lamaran);
		$contacts  = $this->candidate_model->get_contacts($id_lamaran);
		$interviews= $this->candidate_model->get_interviews($id_lamaran);
		$psikotes  = $this->candidate_model->get_psikotes($id_lamaran);
		$offer     = $this->candidate_model->get_offer($id_lamaran);

		// Evaluasi izin akses data sensitif & pencatatan log
		$can_gaji_pelamar = can_sensitif('GAJI_PELAMAR');
		if ($can_gaji_pelamar && $profile && ($profile['gaji_terakhir'] !== NULL || $profile['gaji_diharapkan'] !== NULL)) {
			log_akses_sensitif('GAJI_PELAMAR', (int) $id_lamaran);
		}

		$can_kesehatan = can_sensitif('KESEHATAN');
		if ($can_kesehatan && $health && ! empty($health['riwayat_penyakit'])) {
			log_akses_sensitif('KESEHATAN', (int) $detail['id_kandidat']);
		}

		$can_finansial = can_sensitif('FINANSIAL');
		if ($can_finansial && $bank && ! empty($bank['no_rekening'])) {
			log_akses_sensitif('FINANSIAL', (int) $detail['id_kandidat']);
		}

		$can_gaji = can_sensitif('GAJI');
		if ($can_gaji && $offer && $offer['gaji_ditawarkan'] !== NULL) {
			log_akses_sensitif('GAJI', (int) $detail['id_req']);
		}

		$this->load->view('layouts/main', array(
			'title'            => 'Profil Kandidat — ' . $detail['nama_lengkap'],
			'_content'         => 'candidates/detail',
			'wide'             => TRUE,
			'c'                => $detail,
			'profile'          => $profile,
			'health'           => $health,
			'bank'             => $bank,
			'stages'           => $stages,
			'documents'        => $documents,
			'history'          => $history,
			'contacts'         => $contacts,
			'interviews'       => $interviews,
			'psikotes'         => $psikotes,
			'offer'            => $offer,
			'can_gaji_pelamar' => $can_gaji_pelamar,
			'can_kesehatan'    => $can_kesehatan,
			'can_finansial'    => $can_finansial,
			'can_gaji'         => $can_gaji,
		));
	}
}
