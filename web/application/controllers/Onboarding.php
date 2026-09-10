<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Onboarding -- Formulir Digital Kelengkapan Data Karyawan Baru RPG
 *
 * Fungsi:
 * - Menangani pengisian formulir aplikasi data pelamar komprehensif resmi RPG (Section A sampai J).
 * - Mendukung penyimpanan otomatis (auto-save progres draft) agar kandidat tidak kehilangan data yang telah diketik.
 * - Mengelola data profil identitas, susunan keluarga, riwayat pelatihan, pengalaman kerja, fisik/kesehatan, referensi kerja, konsep pribadi/kuesioner, dan rekening payroll.
 * - Menangani upload pas foto resmi berlatar belakang rapi.
 * - Akses: Akses publik terkontrol melalui token aman unik per lamaran (/onboarding/{token}).
 */
class Onboarding extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('onboarding_model');
		$this->load->helper(array('form', 'url', 'security'));
	}

	public function index($token = NULL)
	{
		if ( ! $token) {
			show_404();
		}

		$t = $this->onboarding_model->resolve_token($token);
		if ( ! $t) {
			show_404();
		}

		// Validasi token
		if ( ! $t['valid']) {
			return $this->load->view('layouts/main', array(
				'title'    => 'Tautan Formulir Tidak Berlaku',
				'_content' => 'onboarding/kadaluarsa',
				't'        => $t,
			));
		}

		if ($this->input->method() === 'post') {
			return $this->_submit($token, $t);
		}

		// Ambil riwayat pengalaman, keluarga, pelatihan, referensi, & kuesioner jika sudah ada
		$experiences   = $this->onboarding_model->get_work_experiences($t['id_lamaran']);
		$families      = $this->onboarding_model->get_family_members($t['id_kandidat']);
		$trainings     = $this->onboarding_model->get_trainings($t['id_kandidat']);
		$references    = $this->onboarding_model->get_references($t['id_kandidat']);
		$questionnaire = $this->onboarding_model->get_questionnaire($t['id_lamaran']);

		$this->load->view('layouts/main', array(
			'title'         => 'Formulir Pelamar (Lanjutan) — ' . $t['nama_lengkap'],
			'_content'      => 'onboarding/form',
			't'             => $t,
			'experiences'   => $experiences,
			'families'      => $families,
			'trainings'     => $trainings,
			'references'    => $references,
			'questionnaire' => $questionnaire,
		));
	}

	private function _submit($token, $t)
	{
		$post_exp = $this->input->post('exp');
		$post_fam = $this->input->post('fam');
		$post_trn = $this->input->post('trn');
		$post_ref = $this->input->post('ref');
		$post_qst = $this->input->post('quest');

		// 1. Parse Pengalaman Kerja
		$experiences = array();
		if (!empty($post_exp) && is_array($post_exp)) {
			foreach ($post_exp as $row) {
				if (!empty($row['nama_perusahaan']) && !empty($row['posisi_jabatan'])) {
					$experiences[] = array(
						'nama_perusahaan' => trim($row['nama_perusahaan']),
						'posisi_jabatan'  => trim($row['posisi_jabatan']),
						'periode_kerja'   => trim($row['periode_kerja'] ?? ''),
						'gaji_terakhir'   => trim($row['gaji_terakhir'] ?? ''),
						'alasan_keluar'   => trim($row['alasan_keluar'] ?? ''),
						'deskripsi_tugas' => trim($row['deskripsi_tugas'] ?? ''),
					);
				}
			}
		}

		// 2. Parse Susunan Keluarga
		$family = array();
		if (!empty($post_fam) && is_array($post_fam)) {
			foreach ($post_fam as $f) {
				if (!empty($f['hubungan']) && !empty($f['nama_lengkap'])) {
					$family[] = array(
						'hubungan'      => trim($f['hubungan']),
						'nama_lengkap'  => trim($f['nama_lengkap']),
						'jenis_kelamin' => in_array($f['jenis_kelamin'] ?? '', array('L', 'P')) ? $f['jenis_kelamin'] : NULL,
						'usia'          => !empty($f['usia']) ? (int) $f['usia'] : NULL,
						'pendidikan'    => trim($f['pendidikan'] ?? ''),
						'pekerjaan'     => trim($f['pekerjaan'] ?? ''),
						'no_telp'       => trim($f['no_telp'] ?? ''),
					);
				}
			}
		}

		// 3. Parse Pelatihan / Kursus / Non-Formal
		$trainings = array();
		if (!empty($post_trn) && is_array($post_trn)) {
			foreach ($post_trn as $tr) {
				if (!empty($tr['nama_pelatihan'])) {
					$trainings[] = array(
						'nama_pelatihan' => trim($tr['nama_pelatihan']),
						'penyelenggara'  => trim($tr['penyelenggara'] ?? ''),
						'tahun'          => trim($tr['tahun'] ?? ''),
						'keterangan'     => trim($tr['keterangan'] ?? ''),
					);
				}
			}
		}

		// 4. Parse Referensi Kerja Profesional
		$references = array();
		if (!empty($post_ref) && is_array($post_ref)) {
			foreach ($post_ref as $rf) {
				if (!empty($rf['nama_referensi'])) {
					$references[] = array(
						'nama_referensi' => trim($rf['nama_referensi']),
						'perusahaan'     => trim($rf['perusahaan'] ?? ''),
						'jabatan'        => trim($rf['jabatan'] ?? ''),
						'no_telp'        => trim($rf['no_telp'] ?? ''),
						'hubungan'       => trim($rf['hubungan'] ?? ''),
					);
				}
			}
		}

		// 5. Parse Butir Kuesioner Evaluasi Diri (Minat & Konsep Pribadi) & Informasi Umum RPG
		$quest = array();
		if (!empty($post_qst) && is_array($post_qst)) {
			$quest = array(
				// Evaluasi Diri & Minat / Konsep Pribadi
				'alasan_melamar'         => trim($post_qst['alasan_melamar'] ?? ''),
				'alasan_cocok_posisi'    => trim($post_qst['alasan_cocok_posisi'] ?? ''),
				'pengetahuan_rpg'        => trim($post_qst['pengetahuan_rpg'] ?? ''),
				'kelebihan_diri'         => trim($post_qst['kelebihan_diri'] ?? ''),
				'kekurangan_diri'        => trim($post_qst['kekurangan_diri'] ?? ''),
				'prestasi_terbesar'      => trim($post_qst['prestasi_terbesar'] ?? ''),
				'rencana_karir_5thn'     => trim($post_qst['rencana_karir_5thn'] ?? ''),
				'masalah_tersulit_solusi'=> trim($post_qst['masalah_tersulit_solusi'] ?? ''),
				'lingkungan_kerja_idaman'=> trim($post_qst['lingkungan_kerja_idaman'] ?? ''),

				// Informasi Umum & Kesiapan Operasional
				'harapan_gaji_fasilitas' => trim($post_qst['harapan_gaji_fasilitas'] ?? ''),
				'ketersediaan_mulai'     => trim($post_qst['ketersediaan_mulai'] ?? ''),
				'bersedia_shift_lembur'  => trim($post_qst['bersedia_shift_lembur'] ?? ''),
				'bersedia_luar_kota'     => trim($post_qst['bersedia_luar_kota'] ?? ''),
				'punya_bisnis_sampingan' => trim($post_qst['punya_bisnis_sampingan'] ?? ''),
				'relasi_keluarga_rpg'    => trim($post_qst['relasi_keluarga_rpg'] ?? ''),
				'riwayat_tindak_pidana'  => trim($post_qst['riwayat_tindak_pidana'] ?? ''),
				'riwayat_melamar_rpg'    => trim($post_qst['riwayat_melamar_rpg'] ?? ''),
				'kepemilikan_kendaraan'  => trim($post_qst['kepemilikan_kendaraan'] ?? ''),
			);
		}

		// Handle upload pas foto (jika ada file diunggah)
		$foto_info = NULL;
		if (!empty($_FILES['pas_foto']['name']) && $_FILES['pas_foto']['error'] === UPLOAD_ERR_OK) {
			try {
				$foto_info = $this->_stage_photo((int) $t['id_lamaran']);
			} catch (RuntimeException $e) {
				$this->session->set_flashdata('error', $e->getMessage());
				redirect('onboarding/' . $token);
			}
		}

		// Status Tempat Tinggal (pilihan 'Lainnya' menyimpan teks khusus yang diisi)
		$stt_pilihan = $this->input->post('status_tempat_tinggal', TRUE);
		$stt_lainnya = trim($this->input->post('status_tempat_tinggal_lainnya', TRUE) ?? '');
		$status_tempat_tinggal = ($stt_pilihan === 'Lainnya' && $stt_lainnya !== '') ? $stt_lainnya : $stt_pilihan;

		// Mode Simpan: 'draft' (simpan sementara) atau 'final' (kirim permanen)
		$action_mode = $this->input->post('action_mode', TRUE);
		$is_final = ($action_mode === 'final') ? 1 : 0;

		$in = array(
			'id_lamaran'            => (int) $t['id_lamaran'],
			'id_kandidat'           => (int) $t['id_kandidat'],
			'token'                 => $token,
			'foto_path'             => $foto_info ? $foto_info['path_file'] : NULL,
			'nama_panggilan'        => $this->input->post('nama_panggilan', TRUE),
			'nik'                   => $this->input->post('nik', TRUE),
			'no_sim'                => $this->input->post('no_sim', TRUE),
			'npwp'                  => $this->input->post('npwp', TRUE),
			'agama'                 => $this->input->post('agama', TRUE),
			'gol_darah'             => $this->input->post('gol_darah', TRUE),
			'tinggi_badan'          => $this->input->post('tinggi_badan') ? (int) $this->input->post('tinggi_badan') : NULL,
			'berat_badan'           => $this->input->post('berat_badan') ? (int) $this->input->post('berat_badan') : NULL,
			'status_tempat_tinggal' => $status_tempat_tinggal,
			'keahlian_komputer'     => $this->input->post('keahlian_komputer', TRUE),
			'bahasa_asing'          => $this->input->post('bahasa_asing', TRUE),
			'nama_bank'             => $this->input->post('nama_bank', TRUE),
			'no_rekening'           => $this->input->post('no_rekening', TRUE),
			'nama_pemilik_bank'     => $this->input->post('nama_pemilik_bank', TRUE),
			'riwayat_penyakit'      => $this->input->post('riwayat_penyakit', TRUE),
			'consent_kesehatan'     => $this->input->post('consent_kesehatan') ? 1 : 0,
			'experiences'           => $experiences,
			'family'                => $family,
			'trainings'             => $trainings,
			'references'            => $references,
			'quest'                 => $quest,
			'ip'                    => $this->input->ip_address(),
			'is_final'              => $is_final,
		);

		try {
			$this->onboarding_model->save_onboarding($in);

			if ($foto_info) {
				$foto_info['ip'] = $this->input->ip_address();
				$this->onboarding_model->save_photo((int) $t['id_lamaran'], (int) $t['id_kandidat'], $foto_info);
			}

			if ( ! $is_final) {
				if ($this->input->is_ajax_request() || $this->input->get('format') === 'json' || $this->input->post('is_ajax') === '1') {
					return $this->output
						->set_content_type('application/json')
						->set_output(json_encode(array(
							'success'  => TRUE,
							'message'  => 'Progres tersimpan otomatis',
							'saved_at' => date('H:i:s'),
							'step'     => (int) ($this->input->post('current_step', TRUE) ?: 1)
						)));
				}
				$step_from = (int) ($this->input->post('current_step', TRUE) ?: 1);
				$this->session->set_flashdata('success', 'Progres formulir Anda berhasil disimpan. Anda dapat keluar dan melanjutkan pengisian kapan saja melalui tautan ini sebelum batas waktu.');
				redirect('onboarding/' . $token . '?step=' . $step_from);
			}

			return $this->load->view('layouts/main', array(
				'title'    => 'Formulir Pelamar Terkirim',
				'_content' => 'onboarding/sukses',
				't'        => $t,
			));
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('onboarding/' . $token);
		}
	}

	/**
	 * Upload dan validasi pas foto kandidat (JPG, JPEG, PNG, max 3MB)
	 * Simpan ke D:/erecruitment-storage/lamaran/<id_lamaran>/FOTO_<hash>.<ext>
	 */
	private function _stage_photo($id_lamaran)
	{
		$max = 3 * 1024 * 1024; // 3 MB
		if ($_FILES['pas_foto']['size'] > $max) {
			throw new RuntimeException('Ukuran pas foto maksimal 3 MB.');
		}

		$ext = strtolower(pathinfo($_FILES['pas_foto']['name'], PATHINFO_EXTENSION));
		if (!in_array($ext, array('jpg', 'jpeg', 'png'), TRUE)) {
			throw new RuntimeException('Format pas foto harus berupa JPG, JPEG, atau PNG.');
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mime  = $finfo->file($_FILES['pas_foto']['tmp_name']);
		if (!in_array($mime, array('image/jpeg', 'image/png'), TRUE)) {
			throw new RuntimeException('Tipe file bukan gambar yang valid (' . $mime . ').');
		}

		$base = rtrim($this->config->item('erec_storage_path') ?: 'D:/erecruitment-storage', '/\\');
		$dir  = $base . DIRECTORY_SEPARATOR . 'lamaran' . DIRECTORY_SEPARATOR . (int) $id_lamaran;
		if (!is_dir($dir) && !@mkdir($dir, 0770, TRUE)) {
			throw new RuntimeException('Folder penyimpanan foto tidak bisa dibuat.');
		}

		$hash = hash_file('sha256', $_FILES['pas_foto']['tmp_name']);
		$dest = $dir . DIRECTORY_SEPARATOR . 'FOTO_' . substr($hash, 0, 16) . '.' . $ext;
		if (!@move_uploaded_file($_FILES['pas_foto']['tmp_name'], $dest)) {
			throw new RuntimeException('Gagal menyimpan file pas foto.');
		}

		return array(
			'path_file' => $dest,
			'hash'      => $hash,
			'ext'       => $ext,
			'mime'      => $mime,
			'ukuran'    => (int) $_FILES['pas_foto']['size'],
			'nama_asli' => $_FILES['pas_foto']['name'],
		);
	}

	/**
	 * Streaming Pas Foto pelamar untuk form onboarding (dengan token valid)
	 */
	public function photo($token = NULL)
	{
		if ( ! $token) {
			show_404();
		}
		$t = $this->onboarding_model->resolve_token($token);
		if ( ! $t || empty($t['foto_path']) || ! is_file($t['foto_path'])) {
			show_404();
		}

		$mime = 'image/jpeg';
		if (function_exists('finfo_open')) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$mime = $finfo->file($t['foto_path']);
		}
		$this->output
			->set_content_type($mime)
			->set_header('Content-Disposition: inline; filename="foto_' . (int) $t['id_lamaran'] . '.' . pathinfo($t['foto_path'], PATHINFO_EXTENSION) . '"')
			->set_header('Content-Length: ' . filesize($t['foto_path']))
			->set_output(file_get_contents($t['foto_path']));
	}
}
