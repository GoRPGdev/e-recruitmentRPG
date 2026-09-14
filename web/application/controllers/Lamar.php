<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Lamar -- Formulir Publik Intake Lamaran Online Pelamar
 *
 * Fungsi:
 * - Menampilkan formulir pendaftaran kerja publik berdasarkan slug tautan lowongan (/lamar/{url_slug}).
 * - Membaca informasi posisi, job description, kualifikasi standar, dan persyaratan lowongan.
 * - Memvalidasi input data dasar kandidat dan menangani upload berkas CV fisik di luar webroot.
 * - Mengeksekusi registrasi lamaran via sp_SubmitApplication dan inisialisasi tahap seleksi awal.
 * - Akses: Terbuka untuk umum / publik (tanpa login).
 */
class Lamar extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('application_model', 'app_m');
		$this->load->library('form_validation');
		$this->load->helper(array('form', 'url'));
	}

	public function index($slug = NULL)
	{
		$posting = $slug ? $this->app_m->get_posting_by_slug($slug) : NULL;

		if ( ! $posting) {
			show_404();
		}

		if ( ! $posting['terbuka']) {
			return $this->load->view('layouts/main', array(
				'title'    => 'Lowongan ditutup',
				'_content' => 'lamar/tertutup',
				'posting'  => $posting,
			));
		}

		if ($this->input->method() === 'post') {
			return $this->_submit($posting);
		}

		$this->load->view('layouts/main', array(
			'title'    => 'Lamar: ' . $posting['nama_posisi'],
			'_content' => 'lamar/form',
			'posting'  => $posting,
		));
	}

	private function _submit($posting)
	{
		$slug = $posting['url_slug'];
		$ip   = $this->input->ip_address();

		// -- rate limit -------------------------------------------------
		$win = (int) $this->config->item('erec_rate_window_min');
		$max = (int) $this->config->item('erec_rate_max');
		if ($this->app_m->count_recent_submits($slug, $ip, $win) >= $max) {
			return $this->_rerender($posting, 'Terlalu banyak percobaan. Coba lagi beberapa menit lagi.');
		}
		$this->app_m->log_submit($slug, $ip);

		// -- validasi field ------------------------------------------
		$this->form_validation->set_rules('nama_lengkap', 'Nama lengkap', 'required|trim|max_length[150]');
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[150]');
		$this->form_validation->set_rules('no_wa', 'Nomor WhatsApp', 'required|trim|max_length[40]');
		$this->form_validation->set_rules('jenis_kelamin', 'Jenis kelamin', 'in_list[L,P]');
		$this->form_validation->set_rules('consent', 'Persetujuan', 'required',
			array('required' => 'Anda harus menyetujui pernyataan persetujuan.'));

		if ($this->form_validation->run() === FALSE) {
			return $this->_rerender($posting, NULL);
		}

		// -- upload CV (staging di luar webroot) --------------------
		try {
			$cv = $this->_stage_cv();
		} catch (RuntimeException $e) {
			return $this->_rerender($posting, $e->getMessage());
		}

		// -- panggil SP --------------------------------------------
		$health = $this->input->post('consent_kesehatan') ? 1 : 0;
		$in = array(
			'url_slug'            => $slug,
			'nama_channel'        => NULL,
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
			'riwayat_penyakit'    => $health ? $this->input->post('riwayat_penyakit', TRUE) : NULL,
			'consent_kesehatan'   => $health,
			'perusahaan_terakhir' => $this->input->post('perusahaan_terakhir', TRUE),
			'jabatan_terakhir'    => $this->input->post('jabatan_terakhir', TRUE),
			'periode_kerja'       => $this->input->post('periode_kerja', TRUE),
			'gaji_terakhir'       => $this->_num($this->input->post('gaji_terakhir')),
			'gaji_diharapkan'     => $this->_num($this->input->post('gaji_diharapkan')),
			'cv_hash'             => $cv['hash'],
		);

		try {
			$res = $this->app_m->submit($in);
		} catch (RuntimeException $e) {
			@unlink($cv['tmp_path']);
			return $this->_rerender($posting, $e->getMessage());
		}

		// -- pindahkan CV ke folder final + catat -------------------
		try {
			$final = $this->_finalize_cv($cv, $res['id_lamaran']);
			$id_dok = $this->app_m->id_dokumen_by_nama('CV') ?: $this->app_m->id_dokumen_by_nama('Ijazah');
			if ($id_dok) {
				$this->app_m->save_cv_document($res['id_lamaran'], $id_dok, array(
					'path_file'  => $final,
					'nama_asli'  => $cv['nama_asli'],
					'hash'       => $cv['hash'],
					'ukuran'     => $cv['ukuran'],
					'mime'       => $cv['mime'],
					'ip'         => $ip,
				));
			}
		} catch (RuntimeException $e) {
			log_message('error', 'CV finalize gagal untuk lamaran ' . $res['id_lamaran'] . ': ' . $e->getMessage());
		}

		$this->load->view('layouts/main', array(
			'title'    => 'Lamaran terkirim',
			'_content' => 'lamar/sukses',
			'posting'  => $posting,
			'res'      => $res,
		));
	}

	/* ---- helpers ----------------------------------------------------- */

	private function _rerender($posting, $flash)
	{
		if ($flash) {
			$this->session->set_flashdata('error', $flash);
		}
		$this->load->view('layouts/main', array(
			'title'    => 'Lamar: ' . $posting['nama_posisi'],
			'_content' => 'lamar/form',
			'posting'  => $posting,
		));
	}

	private function _num($v)
	{
		$v = preg_replace('/[^0-9]/', '', (string) $v);
		return $v === '' ? NULL : $v;
	}

	/**
	 * Simpan file CV ke <storage>/_staging/ dengan nama acak, kembalikan metadata.
	 * @throws RuntimeException
	 */
	private function _stage_cv()
	{
		if (empty($_FILES['cv']['name']) || $_FILES['cv']['error'] === UPLOAD_ERR_NO_FILE) {
			throw new RuntimeException('CV wajib diunggah.');
		}
		if ($_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
			throw new RuntimeException('Upload CV gagal (kode ' . $_FILES['cv']['error'] . ').');
		}

		$max = (int) $this->config->item('erec_cv_max_bytes');
		if ($_FILES['cv']['size'] > $max) {
			throw new RuntimeException('Ukuran CV maksimal ' . round($max / 1048576) . ' MB.');
		}

		$ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
		if ( ! in_array($ext, (array) $this->config->item('erec_cv_ext'), TRUE)) {
			throw new RuntimeException('Format CV harus: ' . implode(', ', $this->config->item('erec_cv_ext')) . '.');
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mime  = $finfo->file($_FILES['cv']['tmp_name']);
		if ( ! in_array($mime, (array) $this->config->item('erec_cv_mime'), TRUE)) {
			throw new RuntimeException('Tipe file CV tidak diizinkan (' . $mime . ').');
		}

		$base = rtrim($this->config->item('erec_storage_path'), '/\\');
		$stage = $base . DIRECTORY_SEPARATOR . '_staging';
		if ( ! is_dir($stage) && ! @mkdir($stage, 0770, TRUE)) {
			throw new RuntimeException('Folder penyimpanan tidak bisa dibuat.');
		}

		$hash = hash_file('sha256', $_FILES['cv']['tmp_name']);
		$tmp  = $stage . DIRECTORY_SEPARATOR . $hash . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
		if ( ! @move_uploaded_file($_FILES['cv']['tmp_name'], $tmp)) {
			throw new RuntimeException('Gagal menyimpan file CV.');
		}

		return array(
			'tmp_path'  => $tmp,
			'hash'      => $hash,
			'ext'       => $ext,
			'mime'      => $mime,
			'ukuran'    => (int) $_FILES['cv']['size'],
			'nama_asli' => $_FILES['cv']['name'],
		);
	}

	private function _finalize_cv(array $cv, $id_lamaran)
	{
		$base = rtrim($this->config->item('erec_storage_path'), '/\\');
		$sharding = date('Y') . DIRECTORY_SEPARATOR . date('m');
		$dir  = $base . DIRECTORY_SEPARATOR . 'lamaran' . DIRECTORY_SEPARATOR . $sharding . DIRECTORY_SEPARATOR . (int) $id_lamaran;
		if ( ! is_dir($dir) && ! @mkdir($dir, 0770, TRUE)) {
			throw new RuntimeException('Folder lamaran tidak bisa dibuat.');
		}
		$dest = $dir . DIRECTORY_SEPARATOR . 'CV_' . $cv['hash'] . '.' . $cv['ext'];
		if ( ! @rename($cv['tmp_path'], $dest)) {
			throw new RuntimeException('Gagal memindahkan CV ke folder lamaran.');
		}
		return $dest;
	}
}
