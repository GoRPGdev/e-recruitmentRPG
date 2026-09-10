<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Pipeline -- Papan Seleksi Interaktif Pelamar per Requisition (MPR)
 *
 * Fungsi:
 * - Menampilkan kandidat yang berada di setiap tahapan seleksi dalam bentuk papan alur terstruktur.
 * - Menangani transisi pelamar ke tahap berikutnya (Advance Stage) berdasarkan remark evaluasi dan efek status.
 * - Menangani pencatatan log kontak WhatsApp/Telepon pelamar dan penjadwalan sesi wawancara (interview).
 * - Menangani penyisipan tahap ad-hoc yang diizinkan untuk kandidat tertentu.
 * - Proteksi akses: membutuhkan permission 'LIHAT_KANDIDAT' (melihat) dan 'KELOLA_REKRUTMEN' (eksekusi mutasi).
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

	private function _get_req_scoped($id_req)
	{
		$req = $id_req ? $this->requisition_model->get($id_req) : NULL;
		if ( ! $req) {
			show_404();
		}
		$dept = current_user_dept();
		if ($dept !== NULL && (int) $req['id_departemen'] !== (int) $dept) {
			show_error('Akses ditolak: Anda hanya dapat mengakses pipeline kandidat dari departemen Anda.', 403, '403 Forbidden');
		}
		return $req;
	}

	public function index($id_req = NULL)
	{
		$req = $this->_get_req_scoped($id_req);

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

		// Ambil kandidat dengan status final (Rejected, Hired, Withdrawn, dll.)
		$final_candidates = $this->requisition_model->final_candidates($id_req);

		$this->load->view('layouts/main', array(
			'title'            => 'Pipeline — ' . ($req['no_mpr'] ?: '#' . $req['id_req']),
			'_content'         => 'pipeline/board',
			'wide'             => TRUE,
			'req'              => $req,
			'stages'           => $stages,
			'remarks'          => $remarks,
			'all_stages'       => $this->requisition_model->active_stages(),
			'can_aksi'         => has_permission('KELOLA_REKRUTMEN'),
			'interviews'       => $interviews,
			'psikotes'         => $psikotes,
			'offers'           => $offers,
			'interviewers'     => $interviewers,
			'can_gaji'         => $can_gaji,
			'final_candidates' => $final_candidates,
		));
	}

	public function advance($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		$this->_get_req_scoped($id_req);
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

		$redirect = $this->input->post('redirect_to', TRUE);
		if ($redirect && strpos($redirect, '://') === FALSE) {
			redirect($redirect);
		}
		redirect('pipeline/index/' . (int) $id_req);
	}

	public function insert_stage($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		$this->_get_req_scoped($id_req);
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
		$this->_get_req_scoped($id_req);
		try {
			$u = $this->requisition_model->log_contact(
				(int) $this->input->post('id_lamaran'),
				$this->input->post('metode') ?: 'WA',
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

	public function log_contact($id_req = NULL)
	{
		return $this->contact($id_req);
	}

	public function save_interview($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		$this->_get_req_scoped($id_req);
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
		$this->_get_req_scoped($id_req);
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
		$this->_get_req_scoped($id_req);
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

	public function cancel_hired($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		$this->_get_req_scoped($id_req);
		try {
			$id_lamaran    = (int) $this->input->post('id_lamaran');
			$status_tujuan = $this->input->post('status_tujuan') ?: 'Withdrawn';
			$alasan        = $this->input->post('alasan', TRUE);
			$buka_posting  = $this->input->post('buka_posting') ? 1 : 0;

			$st = $this->requisition_model->cancel_hired(
				$id_lamaran,
				$status_tujuan,
				$alasan,
				(int) $this->auth_user['id_user'],
				$buka_posting
			);
			$this->session->set_flashdata('ok', 'Status Hired berhasil dibatalkan. Status pelamar kini: ' . $st . '. Kuota dan status lowongan telah diperbarui.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}

		$redirect = $this->input->post('redirect_to', TRUE);
		if ($redirect && strpos($redirect, '://') === FALSE) {
			redirect($redirect);
		}
		redirect('pipeline/index/' . (int) $id_req);
	}
}
