<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Manual -- Input Data Pelamar Manual (Walk-in / Outlet)
 *
 * Fungsi:
 * - Menyediakan form pendaftaran pelamar langsung/manual (walk-in, referensi, outlet)
 *   dengan field selengkap form aplikasi publik.
 * - Berelasi langsung dengan MPR yang sedang dibuka/aktif.
 * - Dapat diakses langsung dari menu sidebar (/manual) atau dari dalam pipeline (/manual/{id_req}).
 * - Menangani pengunggahan berkas CV (opsional jika berkas fisik belum ada).
 * - Proteksi akses: membutuhkan permission 'KELOLA_REKRUTMEN' dan scoping departemen.
 */
class Manual extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('KELOLA_REKRUTMEN');
		$this->load->model(array('application_model' => 'app_m', 'requisition_model' => 'req_m'));
		$this->load->library('form_validation');
		$this->load->helper(array('form', 'url', 'storage', 'status'));
	}

	public function index($id_req = NULL)
	{
		if ($id_req === NULL) {
			$id_req = $this->input->get_post('id_req');
		}

		$open_reqs = $this->_get_open_requisitions();
		$req = NULL;

		if ($id_req !== NULL && (int) $id_req > 0) {
			$id_req = (int) $id_req;
			$req = $this->_get_req_scoped($id_req);

			// Validasi status MPR: hanya boleh menambah pelamar jika status aktif menerima intake
			$allowed_status = array('Approved', 'Sourcing', 'Sourcing_Ulang', 'Terpenuhi_Sebagian');
			if ( ! in_array($req['status_req'], $allowed_status, TRUE)) {
				$this->session->set_flashdata('error', 'MPR ' . ($req['no_mpr'] ?: '#' . $req['id_req']) . ' berstatus "' . label_status_req($req['status_req']) . '" dan tidak sedang membuka penerimaan pelamar.');
				redirect('pipeline/index/' . $id_req);
			}
		}

		if ($this->input->method() === 'post') {
			return $this->_save($req, $open_reqs);
		}

		$title = $req
			? 'Tambah Pelamar Manual — ' . ($req['no_mpr'] ?: '#' . $req['id_req'])
			: 'Entry Manual Pelamar';

		$this->load->view('layouts/main', array(
			'title'     => $title,
			'_content'  => 'manual/form',
			'req'       => $req,
			'open_reqs' => $open_reqs,
		));
	}

	private function _get_open_requisitions()
	{
		$sql = "SELECT r.id_req, r.no_mpr, r.tipe_penempatan, r.status_req,
		               p.nama_posisi, d.nama AS departemen, o.nama_outlet
		        FROM dbo.REQUISITIONS r
		        JOIN dbo.M_POSISI p          ON p.id_posisi = r.id_posisi
		        LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
		        LEFT JOIN dbo.M_OUTLET o     ON o.id_outlet = r.id_outlet
		        WHERE r.status_req IN ('Approved', 'Sourcing', 'Sourcing_Ulang', 'Terpenuhi_Sebagian')";
		$bind = array();
		list($scope_sql, $scope_bind) = scope_dept_region_sql('p.id_departemen', 'o.region');
		if ($scope_sql !== NULL) {
			$sql .= " AND $scope_sql";
			$bind = array_merge($bind, $scope_bind);
		}
		$sql .= " ORDER BY r.id_req DESC";
		$q = $this->db->query($sql, $bind);
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	private function _get_req_scoped($id_req)
	{
		$req = $this->req_m->get($id_req);
		if ( ! $req) {
			show_404();
		}

		if ( ! user_can_access_scope($req['id_departemen'] ?? NULL, $req['region'] ?? NULL)) {
			show_error('Akses ditolak: Anda hanya dapat mengakses pipeline kandidat dari departemen/wilayah Anda.', 403, '403 Forbidden');
		}

		return $req;
	}

	private function _save($req, array $open_reqs)
	{
		// Jika MPR belum terkunci dari URL, ambil dari input form
		if ( ! $req) {
			$id_req_post = (int) $this->input->post('id_req');
			if ($id_req_post > 0) {
				$req = $this->_get_req_scoped($id_req_post);
				$allowed_status = array('Approved', 'Sourcing', 'Sourcing_Ulang', 'Terpenuhi_Sebagian');
				if ( ! in_array($req['status_req'], $allowed_status, TRUE)) {
					return $this->_rerender($req, $open_reqs, 'MPR terpilih tidak sedang membuka penerimaan pelamar.');
				}
			}
		}

		$this->form_validation->set_rules('id_req', 'Lowongan / MPR', 'required|integer');
		$this->form_validation->set_rules('nama_lengkap', 'Nama lengkap', 'required|trim|max_length[150]');
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[150]');
		$this->form_validation->set_rules('no_wa', 'Nomor WhatsApp', 'required|trim|max_length[40]');
		$this->form_validation->set_rules('jenis_kelamin', 'Jenis kelamin', 'in_list[L,P]');
		$this->form_validation->set_rules('tempat_lahir', 'Tempat lahir', 'trim|max_length[100]');
		$this->form_validation->set_rules('tanggal_lahir', 'Tanggal lahir', 'trim');
		$this->form_validation->set_rules('status_pernikahan', 'Status pernikahan', 'trim|max_length[30]');
		$this->form_validation->set_rules('kota_domisili', 'Kota domisili', 'trim|max_length[100]');
		$this->form_validation->set_rules('alamat_lengkap', 'Alamat lengkap', 'trim|max_length[255]');
		$this->form_validation->set_rules('pendidikan_terakhir', 'Pendidikan terakhir', 'trim|max_length[50]');
		$this->form_validation->set_rules('nama_sekolah', 'Nama sekolah/kampus', 'trim|max_length[150]');
		$this->form_validation->set_rules('jurusan', 'Jurusan', 'trim|max_length[100]');
		$this->form_validation->set_rules('perusahaan_terakhir', 'Perusahaan terakhir', 'trim|max_length[150]');
		$this->form_validation->set_rules('jabatan_terakhir', 'Jabatan terakhir', 'trim|max_length[100]');
		$this->form_validation->set_rules('periode_kerja', 'Periode kerja', 'trim|max_length[50]');
		$this->form_validation->set_rules('gaji_terakhir', 'Gaji terakhir', 'trim');
		$this->form_validation->set_rules('gaji_diharapkan', 'Gaji diharapkan', 'trim');
		$this->form_validation->set_rules('kd_nama', 'Nama kontak darurat', 'trim|max_length[150]');
		$this->form_validation->set_rules('kd_telp', 'Telepon kontak darurat', 'trim|max_length[40]');
		$this->form_validation->set_rules('kd_hub', 'Hubungan kontak darurat', 'trim|max_length[50]');
		$this->form_validation->set_rules('consent', 'Pernyataan Persetujuan PDP', 'required',
			array('required' => 'Pernyataan kebenaran data & persetujuan PDP wajib dicentang.'));

		if ($this->form_validation->run() === FALSE || ! $req) {
			return $this->_rerender($req, $open_reqs);
		}

		$id_req = (int) $req['id_req'];

		// -- upload CV (opsional untuk entry manual) ----------------
		$cv_file = NULL;
		$cv_hash = NULL;
		if ( ! empty($_FILES['cv']['name']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
			try {
				$cv_file = erec_store_upload($_FILES['cv'], 0, 'CV');
				$cv_hash = $cv_file['hash'];
			} catch (RuntimeException $e) {
				return $this->_rerender($req, $open_reqs, $e->getMessage());
			}
		}

		$in = array(
			'id_req_manual'       => $id_req,
			'intake_method'       => 'MANUAL',
			'nama_channel'        => 'Walk-in / Manual',
			'nama_lengkap'        => $this->input->post('nama_lengkap', TRUE),
			'email'               => $this->input->post('email', TRUE),
			'no_wa_raw'           => $this->input->post('no_wa', TRUE),
			'tempat_lahir'        => $this->input->post('tempat_lahir', TRUE),
			'tanggal_lahir'       => $this->input->post('tanggal_lahir', TRUE) ?: NULL,
			'jenis_kelamin'       => $this->input->post('jenis_kelamin', TRUE) ?: NULL,
			'pendidikan_terakhir' => $this->input->post('pendidikan_terakhir', TRUE),
			'nama_sekolah'        => $this->input->post('nama_sekolah', TRUE),
			'jurusan'             => $this->input->post('jurusan', TRUE),
			'kota_domisili'       => $this->input->post('kota_domisili', TRUE),
			'alamat_lengkap'      => $this->input->post('alamat_lengkap', TRUE),
			'status_pernikahan'   => $this->input->post('status_pernikahan', TRUE),
			'kontak_darurat_nama' => $this->input->post('kd_nama', TRUE),
			'kontak_darurat_telp' => $this->input->post('kd_telp', TRUE),
			'kontak_darurat_hub'  => $this->input->post('kd_hub', TRUE),
			'consent_versi'       => $this->config->item('erec_consent_versi'),
			'riwayat_penyakit'    => NULL,
			'consent_kesehatan'   => 0,
			'perusahaan_terakhir' => $this->input->post('perusahaan_terakhir', TRUE),
			'jabatan_terakhir'    => $this->input->post('jabatan_terakhir', TRUE),
			'periode_kerja'       => $this->input->post('periode_kerja', TRUE),
			'gaji_terakhir'       => $this->_num($this->input->post('gaji_terakhir')),
			'gaji_diharapkan'     => $this->_num($this->input->post('gaji_diharapkan')),
			'cv_hash'             => $cv_hash,
		);

		try {
			$res = $this->app_m->submit($in);
		} catch (RuntimeException $e) {
			if ($cv_file && ! empty($cv_file['path_file'])) {
				@unlink($cv_file['path_file']);
			}
			return $this->_rerender($req, $open_reqs, $e->getMessage());
		}

		// -- pindahkan CV ke folder final lamaran -------------------
		if ($cv_file) {
			$sharding = date('Y') . DIRECTORY_SEPARATOR . date('m');
			$dir = erec_storage_base() . DIRECTORY_SEPARATOR . 'lamaran' . DIRECTORY_SEPARATOR . $sharding . DIRECTORY_SEPARATOR . (int) $res['id_lamaran'];
			@mkdir($dir, 0770, TRUE);
			$dest = $dir . DIRECTORY_SEPARATOR . 'CV_' . $cv_file['hash'] . '.' . $cv_file['ext'];
			if (@rename($cv_file['path_file'], $dest)) {
				$id_dok = $this->app_m->id_dokumen_by_nama('CV');
				if ($id_dok) {
					$this->app_m->save_cv_document($res['id_lamaran'], $id_dok, array(
						'path_file' => $dest,
						'nama_asli' => $cv_file['nama_asli'],
						'hash'      => $cv_file['hash'],
						'ukuran'    => $cv_file['ukuran'],
						'mime'      => $cv_file['mime'],
						'ip'        => $this->input->ip_address(),
					));
				}
			}
		}

		$tj = trim((string) $this->input->post('tanggal_join'));
		if ($tj !== '') {
			$this->app_m->add_note($res['id_lamaran'], 'Rencana tanggal join: ' . $tj, (int) $this->auth_user['id_user']);
		}

		$catatan_tambahan = trim((string) $this->input->post('catatan_internal'));
		if ($catatan_tambahan !== '') {
			$this->app_m->add_note($res['id_lamaran'], 'Catatan Intake Manual: ' . $catatan_tambahan, (int) $this->auth_user['id_user']);
		}

		$this->session->set_flashdata('ok',
			'Pelamar "' . html_escape($in['nama_lengkap']) . '" berhasil didaftarkan ke pipeline ' . ($req['no_mpr'] ?: '#' . $id_req)
			. ($res['is_kandidat_baru'] ? ' (kandidat baru).' : ' (kandidat sudah ada, data diperbarui).'));

		redirect('pipeline/index/' . $id_req);
	}

	private function _rerender($req, array $open_reqs, $flash = NULL)
	{
		if ($flash) {
			$this->session->set_flashdata('error', $flash);
		}
		$title = $req
			? 'Tambah Pelamar Manual — ' . ($req['no_mpr'] ?: '#' . $req['id_req'])
			: 'Entry Manual Pelamar';

		$this->load->view('layouts/main', array(
			'title'     => $title,
			'_content'  => 'manual/form',
			'req'       => $req,
			'open_reqs' => $open_reqs,
		));
	}

	private function _num($v)
	{
		$v = preg_replace('/[^0-9]/', '', (string) $v);
		return $v === '' ? NULL : $v;
	}
}
