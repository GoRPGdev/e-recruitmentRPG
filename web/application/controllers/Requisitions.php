<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Requisitions -- Manajemen Permintaan Tenaga Kerja (MPR)
 *
 * Fungsi:
 * - Pembuatan dokumen MPR baru oleh Pemohon (User Dept / HR).
 * - Pengajuan peninjauan dokumen MPR ke Tim HR (Review HR) dan BOD (Review BOD).
 * - Manajemen status alur: Draft, Review_HR, Revisi_HR, Review_BOD, Revisi_BOD, Approved, Sourcing, Sourcing_Ulang, Ditolak_HR, Ditolak_BOD, Kadaluarsa, Dibatalkan.
 * - Penyampaian umpan balik dan catatan arahan revisi (catatan_hr / catatan_bod) untuk Pemohon.
 */
class Requisitions extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('requisition_model', 'rm');
		$this->load->library('form_validation');
		$this->load->helper(array('form', 'url'));
	}

	public function index()
	{
		$per  = 20;
		$page = max(1, (int) $this->input->get('page'));
		$dept = current_user_dept();

		$f = array(
			'status' => $this->input->get('status') ?: NULL,
			'posisi' => $this->input->get('posisi') ?: NULL,
			'dept'   => $dept !== NULL ? $dept : ($this->input->get('dept') ?: NULL),
			'dari'   => $this->input->get('dari') ?: NULL,
			'sampai' => $this->input->get('sampai') ?: NULL,
		);
		$offset = ($page - 1) * $per + 1;
		$total  = $this->rm->count_list($f);

		$this->load->view('layouts/main', array(
			'title'    => 'MPR',
			'_content' => 'requisitions/index',
			'wide'     => TRUE,
			'rows'     => $this->rm->list_mpr($offset, $per, $f),
			'page'     => $page,
			'pages'    => max(1, (int) ceil($total / $per)),
			'f'        => $f,
			'total'    => $total,
			'stats'    => $this->rm->stats_summary($f),
			'positions'=> $this->rm->positions($dept),
			'depts'    => $this->rm->departments(),
		));
	}

	public function create()
	{
		$this->require_permission('BUAT_MPR');

		$role = $this->auth_user['kode_role'] ?? '';
		$dept = current_user_dept();
		if ($role === 'USER_DEPT' && $dept === NULL) {
			$this->session->set_flashdata('error', 'Akun Anda belum dikaitkan dengan departemen. Hubungi Administrator.');
			redirect('requisitions');
			return;
		}

		if ($this->input->method() === 'post') {
			$this->form_validation->set_rules('id_posisi', 'Posisi', 'required|integer');
			$this->form_validation->set_rules('tipe_penempatan', 'Penempatan', 'required|in_list[HQ,OUTLET]');
			$this->form_validation->set_rules('jumlah_dibutuhkan', 'Jumlah', 'required|integer|greater_than[0]');

			if ($this->form_validation->run()) {
				$id_posisi = (int) $this->input->post('id_posisi');
				if ($dept !== NULL) {
					$pos_list = $this->rm->positions($dept);
					$valid_ids = array_map(function($p) { return (int)$p['id_posisi']; }, $pos_list);
					if ( ! in_array($id_posisi, $valid_ids, TRUE)) {
						$this->session->set_flashdata('error', 'Posisi yang dipilih tidak sesuai dengan departemen Anda.');
						redirect('requisitions/create');
						return;
					}
				}
				try {
					$id = $this->rm->create(array(
						'id_posisi'                => $id_posisi,
						'tipe_penempatan'          => $this->input->post('tipe_penempatan'),
						'id_outlet'                => $this->input->post('id_outlet'),
						'jumlah_dibutuhkan'        => $this->input->post('jumlah_dibutuhkan'),
						'status_karyawan'          => $this->input->post('status_karyawan', TRUE),
						'alasan_permintaan'        => $this->input->post('alasan_permintaan', TRUE),
						'nik_digantikan'           => $this->input->post('nik_digantikan', TRUE),
						'target_tanggal_join'      => $this->input->post('target_tanggal_join', TRUE),
						'urgensi'                  => $this->input->post('urgensi', TRUE),
						'id_flow'                  => $this->input->post('id_flow'),
						'butuh_psikotes'           => 0,
						'butuh_interview_bod'      => 0,
						'pendidikan_minimal'       => $this->input->post('pendidikan_minimal', TRUE),
						'pengalaman_minimal_tahun' => $this->input->post('pengalaman_minimal_tahun', TRUE),
						'job_desc'                 => $this->input->post('job_desc', TRUE),
						'kualifikasi'              => $this->input->post('kualifikasi', TRUE),
						'range_gaji_min'           => NULL,
						'range_gaji_max'           => NULL,
						'preferensi_internal'      => $this->input->post('preferensi_internal', TRUE),
					), (int) $this->auth_user['id_user']);
					$this->session->set_flashdata('ok', 'MPR draft dibuat.');
					redirect('requisitions/view/' . $id);
				} catch (RuntimeException $e) {
					$this->session->set_flashdata('error', $e->getMessage());
				}
			}
		}

		$this->load->view('layouts/main', array(
			'title'     => 'Buat MPR',
			'_content'  => 'requisitions/create',
			'positions' => $this->rm->positions($dept),
			'outlets'   => $this->rm->outlets(),
		));
	}

	public function edit($id_req = NULL)
	{
		$this->require_permission('BUAT_MPR');

		$req = $id_req ? $this->rm->get($id_req) : NULL;
		if ( ! $req) {
			show_404();
		}

		// Hanya Draft, Revisi_HR, atau Revisi_BOD yang bisa diedit
		if ( ! in_array($req['status_req'], array('Draft', 'Revisi_HR', 'Revisi_BOD'))) {
			$this->session->set_flashdata('error', 'MPR hanya dapat diedit saat berstatus Draft, Revisi HR, atau Revisi BOD.');
			redirect('requisitions/view/' . (int) $id_req);
			return;
		}

		// Scoping departemen
		$dept = current_user_dept();
		if ($dept !== NULL && (int) $req['id_departemen'] !== (int) $dept) {
			show_error('Akses ditolak: Anda hanya dapat mengedit MPR dari departemen Anda.', 403, '403 Forbidden');
		}

		if ($this->input->method() === 'post') {
			$this->form_validation->set_rules('id_posisi', 'Posisi', 'required|integer');
			$this->form_validation->set_rules('tipe_penempatan', 'Penempatan', 'required|in_list[HQ,OUTLET]');
			$this->form_validation->set_rules('jumlah_dibutuhkan', 'Jumlah', 'required|integer|greater_than[0]');

			if ($this->form_validation->run()) {
				$id_posisi = (int) $this->input->post('id_posisi');
				if ($dept !== NULL) {
					$pos_list = $this->rm->positions($dept);
					$valid_ids = array_map(function($p) { return (int)$p['id_posisi']; }, $pos_list);
					if ( ! in_array($id_posisi, $valid_ids, TRUE)) {
						$this->session->set_flashdata('error', 'Posisi yang dipilih tidak sesuai dengan departemen Anda.');
						redirect('requisitions/edit/' . (int) $id_req);
						return;
					}
				}
				try {
					$this->rm->update_requisition($id_req, array(
						'id_posisi'                => $id_posisi,
						'tipe_penempatan'          => $this->input->post('tipe_penempatan'),
						'id_outlet'                => $this->input->post('id_outlet'),
						'jumlah_dibutuhkan'        => $this->input->post('jumlah_dibutuhkan'),
						'status_karyawan'          => $this->input->post('status_karyawan', TRUE),
						'alasan_permintaan'        => $this->input->post('alasan_permintaan', TRUE),
						'nik_digantikan'           => $this->input->post('nik_digantikan', TRUE),
						'target_tanggal_join'      => $this->input->post('target_tanggal_join', TRUE),
						'urgensi'                  => $this->input->post('urgensi', TRUE),
						'pendidikan_minimal'       => $this->input->post('pendidikan_minimal', TRUE),
						'pengalaman_minimal_tahun' => $this->input->post('pengalaman_minimal_tahun', TRUE),
						'job_desc'                 => $this->input->post('job_desc', TRUE),
						'kualifikasi'              => $this->input->post('kualifikasi', TRUE),
					), (int) $this->auth_user['id_user']);
					$this->session->set_flashdata('ok', 'Data MPR berhasil diperbarui.');
					redirect('requisitions/view/' . (int) $id_req);
					return;
				} catch (RuntimeException $e) {
					$this->session->set_flashdata('error', $e->getMessage());
				}
			}
		}

		$this->load->view('layouts/main', array(
			'title'     => 'Edit MPR ' . ($req['no_mpr'] ?: '#' . $req['id_req']),
			'_content'  => 'requisitions/edit',
			'req'       => $req,
			'positions' => $this->rm->positions($dept),
			'outlets'   => $this->rm->outlets(),
		));
	}

	public function view($id_req = NULL)
	{
		$req = $id_req ? $this->rm->get($id_req) : NULL;
		if ( ! $req) {
			show_404();
		}

		// Scoping departemen untuk USER_DEPT
		$dept = current_user_dept();
		if ($dept !== NULL && (int) $req['id_departemen'] !== (int) $dept) {
			show_error('Akses ditolak: Anda hanya dapat melihat MPR dari departemen Anda.', 403, '403 Forbidden');
		}

		// Catat log jika user berhak melihat data sensitif gaji dan range gaji terisi
		if (can_sensitif('GAJI') && ($req['range_gaji_min'] !== NULL || $req['range_gaji_max'] !== NULL)) {
			log_akses_sensitif('GAJI', (int) $id_req);
		}

		$postings = $this->rm->postings_for_req($id_req);

		$this->load->view('layouts/main', array(
			'title'     => 'MPR ' . ($req['no_mpr'] ?: '#' . $req['id_req']),
			'_content'  => 'requisitions/view',
			'wide'      => TRUE,
			'req'       => $req,
			'approvals' => $this->rm->approvals($id_req),
			'open_appr' => NULL,
			'can_kelola'=> has_permission('KELOLA_REKRUTMEN'),
			'postings'  => $postings,
		));
	}

	public function submit_hr($id_req = NULL)
	{
		$this->require_permission('BUAT_MPR');

		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}

		$req = $this->rm->get($id_req);
		if ( ! $req) {
			show_404();
		}

		$dept = current_user_dept();
		if ($dept !== NULL && (int) $req['id_departemen'] !== (int) $dept) {
			show_error('Akses ditolak: Anda hanya dapat mengajukan MPR dari departemen Anda.', 403, '403 Forbidden');
		}

		try {
			$this->rm->submit_to_hr($id_req, (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'Permintaan MPR berhasil diajukan untuk Review HR.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('requisitions/view/' . (int) $id_req);
	}

	public function submit($id_req = NULL)
	{
		$this->require_permission('BUAT_MPR');

		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}

		$req = $this->rm->get($id_req);
		if ( ! $req) {
			show_404();
		}

		$dept = current_user_dept();
		if ($dept !== NULL && (int) $req['id_departemen'] !== (int) $dept) {
			show_error('Akses ditolak: Anda hanya dapat mengajukan MPR dari departemen Anda.', 403, '403 Forbidden');
		}

		try {
			$this->rm->submit_to_bod($id_req, (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'MPR berhasil diteruskan ke tahap Review BOD.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('requisitions/view/' . (int) $id_req);
	}

	public function post_job($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		try {
			$durasi_hari = max(1, (int) ($this->input->post('durasi_hari') ?: 14));
			$id_posting = $this->rm->create_posting(
				$id_req,
				NULL,
				$this->input->post('judul_posting', TRUE) ?: 'Lowongan',
				$this->input->post('job_desc', TRUE),
				$this->input->post('kualifikasi', TRUE),
				1,
				$durasi_hari
			);

			// Pastikan url_slug ter-generate agar link langsung aktif
			$this->load->model('posting_model', 'pm');
			$this->pm->ensure_slug($id_posting);

			$this->session->set_flashdata('ok', 'Link form lowongan publik berhasil dibuat (Batas waktu: ' . $durasi_hari . ' hari) dan status MPR beralih ke Sourcing.');
			redirect('requisitions/view/' . (int) $id_req);
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('requisitions/view/' . (int) $id_req);
		}
	}

	public function update_status($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}

		$status_baru = $this->input->post('status_baru', TRUE);
		$catatan     = $this->input->post('catatan', TRUE);

		try {
			$this->rm->update_status($id_req, $status_baru, $catatan, (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'Status MPR berhasil diperbarui menjadi: ' . html_escape($status_baru));
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('requisitions/view/' . (int) $id_req);
	}

	public function update_catatan_hr($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}

		$catatan_hr = $this->input->post('catatan_hr', TRUE);

		try {
			$this->rm->update_catatan_hr($id_req, $catatan_hr, (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'Catatan / Feedback HR berhasil disimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('requisitions/view/' . (int) $id_req);
	}

	public function toggle_posting($id_req = NULL, $id_posting = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || ! $id_posting || $this->input->method() !== 'post') {
			show_404();
		}

		try {
			$form_aktif   = $this->input->post('form_aktif');
			$durasi_hari  = max(1, (int) ($this->input->post('durasi_hari') ?: 14));
			$aktif_target = ($form_aktif !== NULL && $form_aktif !== '') ? (int) $form_aktif : NULL;

			$status_akhir = $this->rm->toggle_posting_form($id_posting, $aktif_target, (int) $this->auth_user['id_user'], $durasi_hari);
			$pesan = $status_akhir ? 'Form publik berhasil dibuka (menerima lamaran).' : 'Form publik berhasil ditutup.';
			$this->session->set_flashdata('ok', $pesan);
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('requisitions/view/' . (int) $id_req);
	}

	public function extend_posting($id_req = NULL, $id_posting = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || ! $id_posting || $this->input->method() !== 'post') {
			show_404();
		}

		$durasi_hari = max(1, (int) ($this->input->post('durasi_hari') ?: 14));
		try {
			$this->rm->extend_posting($id_posting, $durasi_hari, (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'Batas waktu lowongan berhasil diperpanjang (+' . $durasi_hari . ' hari).');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('requisitions/view/' . (int) $id_req);
	}

	public function cancel($id_req = NULL)
	{
		$this->require_permission('KELOLA_REKRUTMEN');
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}

		$req = $this->rm->get($id_req);
		if ( ! $req) {
			show_404();
		}

		$alasan = $this->input->post('alasan_batal', TRUE);
		try {
			$this->rm->cancel_requisition($id_req, $alasan, (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'Requisition / MPR berhasil dibatalkan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('requisitions/view/' . (int) $id_req);
	}
}
