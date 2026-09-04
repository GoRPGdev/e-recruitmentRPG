<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MPR / Requisition. Buat & ajukan: semua user login. Catat approval &
 * posting: KELOLA_REKRUTMEN.
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
		$f = array(
			'status' => $this->input->get('status') ?: NULL,
			'posisi' => $this->input->get('posisi') ?: NULL,
			'dept'   => $this->input->get('dept') ?: NULL,
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
			'positions'=> $this->rm->positions(),
			'depts'    => $this->rm->departments(),
		));
	}

	public function create()
	{
		$this->require_permission('BUAT_MPR');

		if ($this->input->method() === 'post') {
			$this->form_validation->set_rules('id_posisi', 'Posisi', 'required|integer');
			$this->form_validation->set_rules('tipe_penempatan', 'Penempatan', 'required|in_list[HQ,OUTLET]');
			$this->form_validation->set_rules('jumlah_dibutuhkan', 'Jumlah', 'required|integer|greater_than[0]');

			if ($this->form_validation->run()) {
				try {
					$id = $this->rm->create(array(
						'id_posisi'                => $this->input->post('id_posisi'),
						'tipe_penempatan'          => $this->input->post('tipe_penempatan'),
						'id_outlet'                => $this->input->post('id_outlet'),
						'jumlah_dibutuhkan'        => $this->input->post('jumlah_dibutuhkan'),
						'status_karyawan'          => $this->input->post('status_karyawan', TRUE),
						'alasan_permintaan'        => $this->input->post('alasan_permintaan', TRUE),
						'nik_digantikan'           => $this->input->post('nik_digantikan', TRUE),
						'target_tanggal_join'      => $this->input->post('target_tanggal_join', TRUE),
						'urgensi'                  => $this->input->post('urgensi', TRUE),
						'id_flow'                  => $this->input->post('id_flow'),
						'butuh_psikotes'           => $this->input->post('butuh_psikotes'),
						'butuh_interview_bod'      => $this->input->post('butuh_interview_bod'),
						'pendidikan_minimal'       => $this->input->post('pendidikan_minimal', TRUE),
						'pengalaman_minimal_tahun' => $this->input->post('pengalaman_minimal_tahun', TRUE),
						'job_desc'                 => $this->input->post('job_desc', TRUE),
						'kualifikasi'              => $this->input->post('kualifikasi', TRUE),
						'range_gaji_min'           => can_sensitif('GAJI') ? $this->input->post('range_gaji_min', TRUE) : NULL,
						'range_gaji_max'           => can_sensitif('GAJI') ? $this->input->post('range_gaji_max', TRUE) : NULL,
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
			'positions' => $this->rm->positions(),
			'outlets'   => $this->rm->outlets(),
		));
	}

	public function view($id_req = NULL)
	{
		$req = $id_req ? $this->rm->get($id_req) : NULL;
		if ( ! $req) {
			show_404();
		}

		// Catat log jika user berhak melihat data sensitif gaji dan range gaji terisi
		if (can_sensitif('GAJI') && ($req['range_gaji_min'] !== NULL || $req['range_gaji_max'] !== NULL)) {
			log_akses_sensitif('GAJI', (int) $id_req);
		}

		$this->load->model('application_model');
		$this->load->view('layouts/main', array(
			'title'     => 'MPR ' . ($req['no_mpr'] ?: '#' . $req['id_req']),
			'_content'  => 'requisitions/view',
			'wide'      => TRUE,
			'req'       => $req,
			'approvals' => $this->rm->approvals($id_req),
			'open_appr' => $this->rm->open_approval($id_req),
			'channels'  => $this->application_model->list_channels(),
			'can_kelola'=> has_permission('KELOLA_REKRUTMEN'),
		));
	}

	public function submit($id_req = NULL)
	{
		$this->require_permission('BUAT_MPR');

		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		try {
			$this->rm->submit_to_bod($id_req, (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'MPR diajukan ke BOD.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('requisitions/view/' . (int) $id_req);
	}

	public function approve($id_req = NULL)
	{
		$this->require_any_permission(array('APPROVE', 'KELOLA_REKRUTMEN'));
		if ( ! $id_req || $this->input->method() !== 'post') {
			show_404();
		}
		// lampiran screenshot WA (opsional)
		$lampiran = NULL;
		$id_approval = (int) $this->input->post('id_approval');
		if ( ! empty($_FILES['lampiran']['name']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
			$dir = rtrim($this->config->item('erec_storage_path'), '/\\') . DIRECTORY_SEPARATOR . 'approval';
			@mkdir($dir, 0770, TRUE);
			$ext = strtolower(pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION));
			if (in_array($ext, array('jpg', 'jpeg', 'png', 'pdf'), TRUE)) {
				$lampiran = $dir . DIRECTORY_SEPARATOR . $id_approval . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
				if ( ! @move_uploaded_file($_FILES['lampiran']['tmp_name'], $lampiran)) {
					$lampiran = NULL;
				}
			}
		}

		try {
			$this->rm->record_approval(array(
				'id_approval'       => $id_approval,
				'keputusan'         => $this->input->post('keputusan'),
				'jumlah_disetujui'  => $this->input->post('jumlah_disetujui', TRUE),
				'tanggal_keputusan' => $this->input->post('tanggal_keputusan', TRUE),
				'disetujui_oleh'    => $this->input->post('disetujui_oleh', TRUE),
				'catatan_bod'       => $this->input->post('catatan_bod', TRUE),
				'lampiran_path'     => $lampiran,
			), (int) $this->auth_user['id_user']);
			$this->session->set_flashdata('ok', 'Keputusan BOD dicatat.');
		} catch (RuntimeException $e) {
			if ($lampiran) { @unlink($lampiran); }
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
			$id_posting = $this->rm->create_posting(
				$id_req,
				(int) $this->input->post('id_channel'),
				$this->input->post('judul_posting', TRUE) ?: 'Lowongan',
				$this->input->post('job_desc', TRUE),
				$this->input->post('kualifikasi', TRUE)
			);
			$this->session->set_flashdata('ok', 'Job posting dibuat. Atur link form di menu Link Form.');
			redirect('postings/form_settings/' . $id_posting);
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('requisitions/view/' . (int) $id_req);
		}
	}
}
