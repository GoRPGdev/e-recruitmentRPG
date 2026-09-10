<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Candidates -- Manajemen Data & Detail Profil Kandidat Pelamar
 *
 * Fungsi:
 * - Menampilkan daftar master kandidat dan riwayat lamaran (dengan filter status & pencarian).
 * - Menampilkan profil lengkap pelamar (identitas, riwayat kerja, pendidikan, keluarga, dsb).
 * - Menyediakan fitur cetak / export PDF Formulir Aplikasi Calon Karyawan (print_form).
 * - Menghasilkan token & tautan formulir onboarding mandiri untuk kandidat lolos seleksi.
 * - Proteksi akses berbasis hak akses (RBAC) & kepatuhan privasi data sensitif (UU PDP).
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

	public function index()
	{
		$per  = (int) $this->input->get('per') ?: 10;
		if ($per < 5 || $per > 100) {
			$per = 10;
		}
		$page = max(1, (int) $this->input->get('page'));

		$f = array(
			'q'          => $this->input->get('q') ?: NULL,
			'status'     => $this->input->get('status') ?: NULL,
			'posisi'     => $this->input->get('posisi') ?: NULL,
			'dept'       => $this->input->get('dept') ?: NULL,
			'status_mpr' => $this->input->get('status_mpr') ?: NULL,
			'dari'       => $this->input->get('dari') ?: NULL,
			'sampai'     => $this->input->get('sampai') ?: NULL,
			'per'        => $per !== 10 ? $per : NULL,
		);

		$offset = ($page - 1) * $per + 1;
		$total  = $this->candidate_model->count_list($f);
		$rows   = $this->candidate_model->list_candidates($offset, $per, $f);

		$this->load->view('layouts/main', array(
			'title'       => 'Daftar Pelamar & Kandidat',
			'_content'    => 'candidates/index',
			'wide'        => TRUE,
			'rows'        => $rows,
			'page'        => $page,
			'per'         => $per,
			'pages'       => max(1, (int) ceil($total / $per)),
			'total'       => $total,
			'stats'       => $this->candidate_model->stats_summary($f),
			'f'           => $f,
			'positions'   => $this->candidate_model->get_positions(),
			'departments' => $this->candidate_model->get_departments(),
		));
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

		// Data kelengkapan onboarding
		$onboarding_token = $this->candidate_model->get_onboarding_token($id_lamaran);
		$experiences      = $this->candidate_model->get_work_experiences($id_lamaran);
		$families         = $this->candidate_model->get_family_members($detail['id_kandidat']);
		$trainings        = $this->candidate_model->get_trainings($detail['id_kandidat']);
		$references       = $this->candidate_model->get_references($detail['id_kandidat']);
		$questionnaire    = $this->candidate_model->get_questionnaire($id_lamaran);

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

		$active_stage = NULL;
		$remarks      = array();
		if (!empty($stages)) {
			foreach ($stages as $st) {
				if ($st['status_tahap'] === 'Berjalan') {
					$active_stage = $st;
					break;
				}
			}
		}
		if ($active_stage) {
			$remarks = $this->candidate_model->remarks_for_stage((int) $active_stage['id_stage']);
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
			'active_stage'     => $active_stage,
			'remarks'          => $remarks,
			'can_kelola'       => has_permission('KELOLA_REKRUTMEN'),
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
			'onboarding_token' => $onboarding_token,
			'experiences'      => $experiences,
			'families'         => $families,
			'trainings'        => $trainings,
			'references'       => $references,
			'questionnaire'    => $questionnaire,
		));
	}

	/**
	 * Generate / perbarui link form onboarding khusus kandidat
	 */
	public function generate_onboarding_link($id_lamaran = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');

		if ( ! $id_lamaran) {
			show_404();
		}

		$detail = $this->candidate_model->get_detail($id_lamaran);
		if ( ! $detail) {
			show_404();
		}

		$au = $this->session->userdata('auth_user');
		$uid = ! empty($au['id_user']) ? (int) $au['id_user'] : (! empty($this->auth_user['id_user']) ? (int) $this->auth_user['id_user'] : NULL);

		$force_new = (bool) $this->input->get('force_new');
		$existing_token = $this->candidate_model->get_onboarding_token((int) $id_lamaran);

		$token = NULL;
		$is_existing = FALSE;
		$kadaluarsa_text = '';

		// Jika sudah ada token aktif dan user tidak meminta buat ulang paksa, pakai token yang ada
		if ( ! $force_new && $existing_token && ! empty($existing_token['valid'])) {
			$token = $existing_token['token'];
			$is_existing = TRUE;
			$kadaluarsa_text = ! empty($existing_token['kadaluarsa_pada'])
				? date('d M Y', strtotime($existing_token['kadaluarsa_pada']))
				: 'Tanpa batas waktu';
			$msg = 'Tautan formulir pelamar aktif ditemukan (berlaku s/d ' . $kadaluarsa_text . ').';
		} else {
			try {
				$token = $this->candidate_model->create_onboarding_token((int) $id_lamaran, $uid, 14);
				$kadaluarsa_text = date('d M Y', time() + 14 * 86400);
				$msg = 'Tautan formulir pelamar baru berhasil dibuat (masa aktif 14 hari).';
			} catch (Exception $e) {
				if ($this->input->is_ajax_request() || $this->input->get('format') === 'json') {
					return $this->output
						->set_content_type('application/json')
						->set_status_header(500)
						->set_output(json_encode(array(
							'success' => FALSE,
							'message' => 'Gagal membuat tautan formulir: ' . $e->getMessage()
						)));
				}
				$this->session->set_flashdata('error', 'Gagal membuat tautan formulir: ' . $e->getMessage());
				redirect('candidates/detail/' . (int) $id_lamaran);
			}
		}

		$this->session->set_flashdata('ok', $msg);

		$onboarding_url = site_url('onboarding/' . $token);
		$no_wa = preg_replace('/[^0-9]/', '', (string) ($detail['no_wa_normal'] ?? ''));
		$wa_msg = "Halo " . ($detail['nama_lengkap'] ?? 'Kandidat') . ", terima kasih telah melamar di Ratu Pertiwi Group! Mohon untuk melengkapi formulir data pelamar Anda melalui tautan resmi berikut: " . $onboarding_url . " . Terima kasih.";
		$wa_link = $no_wa ? 'https://wa.me/' . $no_wa . '?text=' . rawurlencode($wa_msg) : '';

		if ($this->input->is_ajax_request() || $this->input->get('format') === 'json') {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(array(
					'success'        => TRUE,
					'token'          => $token,
					'is_existing'    => $is_existing,
					'onboarding_url' => $onboarding_url,
					'nama_lengkap'   => $detail['nama_lengkap'],
					'no_wa'          => $detail['no_wa_normal'],
					'wa_link'        => $wa_link,
					'kadaluarsa'     => $kadaluarsa_text,
					'message'        => $msg
				)));
		}

		$redirect_to = $this->input->get('redirect_to');
		if ($redirect_to) {
			redirect($redirect_to);
		}
		redirect('candidates/detail/' . (int) $id_lamaran);
	}

	/**
	 * Streaming Pas Foto pelamar dari storage luar webroot
	 */
	public function photo($id_lamaran = NULL)
	{
		if ( ! $id_lamaran) {
			show_404();
		}
		$detail = $this->candidate_model->get_detail($id_lamaran);
		if ( ! $detail || empty($detail['foto_path']) || ! is_file($detail['foto_path'])) {
			show_404();
		}
		$dept = current_user_dept();
		if ($dept !== NULL && (int) $detail['id_departemen'] !== (int) $dept) {
			show_error('Akses ditolak: Kandidat bukan dari lowongan departemen Anda.', 403, '403 Forbidden');
		}

		$mime = 'image/jpeg';
		if (function_exists('finfo_open')) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$mime = $finfo->file($detail['foto_path']);
		}
		$this->output
			->set_content_type($mime)
			->set_header('Content-Disposition: inline; filename="foto_' . (int) $id_lamaran . '.' . pathinfo($detail['foto_path'], PATHINFO_EXTENSION) . '"')
			->set_header('Content-Length: ' . filesize($detail['foto_path']))
			->set_output(file_get_contents($detail['foto_path']));
	}

	/**
	 * Shortcut: buka CV pelamar langsung dari id_lamaran
	 * Dipakai oleh pipeline board & daftar pelamar agar tidak perlu buka detail dulu.
	 */
	public function cv($id_lamaran = NULL)
	{
		if ( ! $id_lamaran) {
			show_404();
		}
		$detail = $this->candidate_model->get_detail($id_lamaran);
		if ( ! $detail) {
			show_404();
		}
		$dept = current_user_dept();
		if ($dept !== NULL && (int) $detail['id_departemen'] !== (int) $dept) {
			show_error('Akses ditolak.', 403, '403 Forbidden');
		}
		$docs = $this->candidate_model->get_documents($id_lamaran);
		$cv = NULL;
		foreach ($docs as $d) {
			if (strtoupper($d['nama_dokumen'] ?? '') === 'CV' || stripos($d['nama_dokumen'] ?? '', 'cv') !== false) {
				$cv = $d;
				break;
			}
		}
		if ( ! $cv) {
			/* ponytail: fallback ke halaman detail jika CV belum terdaftar */
			$this->session->set_flashdata('error', 'Dokumen CV belum diunggah untuk pelamar ini.');
			redirect('candidates/detail/' . (int) $id_lamaran);
			return;
		}
		redirect('documents/open/' . (int) $cv['id_cand_doc']);
	}

	/**
	 * Cetak / Export PDF Formulir Pelamar Lengkap (Format Dokumen Resmi RPG A-I)
	 */
	public function print_form($id_lamaran = NULL)
	{
		if ( ! $id_lamaran) {
			show_404();
		}

		$detail = $this->candidate_model->get_detail($id_lamaran);
		if ( ! $detail) {
			show_404();
		}

		// Scoping USER_DEPT
		$dept = current_user_dept();
		if ($dept !== NULL && (int) $detail['id_departemen'] !== (int) $dept) {
			show_error('Akses ditolak: Kandidat bukan dari lowongan departemen Anda.', 403, '403 Forbidden');
		}

		$profile       = $this->candidate_model->get_profile($id_lamaran);
		$health        = $this->candidate_model->get_health($detail['id_kandidat']);
		$bank          = $this->candidate_model->get_bank($id_lamaran);
		$experiences   = $this->candidate_model->get_work_experiences($id_lamaran);
		$families      = $this->candidate_model->get_family_members($detail['id_kandidat']);
		$trainings     = $this->candidate_model->get_trainings($detail['id_kandidat']);
		$references    = $this->candidate_model->get_references($detail['id_kandidat']);
		$questionnaire = $this->candidate_model->get_questionnaire($id_lamaran);

		// Log akses data sensitif saat mencetak
		if (can_sensitif('GAJI_PELAMAR')) {
			log_akses_sensitif('GAJI_PELAMAR', (int) $id_lamaran);
		}
		if (can_sensitif('FINANSIAL')) {
			log_akses_sensitif('FINANSIAL', (int) $detail['id_kandidat']);
		}
		if (can_sensitif('KESEHATAN')) {
			log_akses_sensitif('KESEHATAN', (int) $detail['id_kandidat']);
		}

		$this->load->view('candidates/print_form', array(
			'title'         => 'Formulir Aplikasi Calon Karyawan — ' . $detail['nama_lengkap'],
			'c'             => $detail,
			'profile'       => $profile,
			'health'        => $health,
			'bank'          => $bank,
			'experiences'   => $experiences,
			'families'      => $families,
			'trainings'     => $trainings,
			'references'    => $references,
			'questionnaire' => $questionnaire,
		));
	}
}
