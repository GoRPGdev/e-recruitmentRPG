<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model Onboarding_model -- Model Pengelolaan Formulir Komprehensif Onboarding RPG
 *
 * Fungsi:
 * - Validasi token akses onboarding dan penyimpanan progres draft (auto-save).
 * - Menangani penyimpanan data identitas, foto diri, susunan keluarga, dan pendidikan formal/non-formal.
 * - Menangani riwayat pengalaman kerja bertingkat, kondisi kesehatan, referensi profesional, serta kuesioner minat.
 * - Menyimpan data nomor rekening payroll dan persetujuan pemrosesan data pribadi (Consent PDP).
 */
class Onboarding_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * Resolusi token dan data lamaran terkait
	 */
	public function resolve_token($token)
	{
		// Cek ketersediaan kolom identitas baru di CANDIDATES
		$chk_tt = $this->db->query("SELECT COL_LENGTH('dbo.CANDIDATES', 'status_tempat_tinggal') AS has_tt, COL_LENGTH('dbo.CANDIDATES', 'foto_path') AS has_foto")->row_array();
		$extra_identitas = ! empty($chk_tt['has_tt'])
			? 'c.status_tempat_tinggal, c.keahlian_komputer, c.bahasa_asing,'
			: 'NULL AS status_tempat_tinggal, NULL AS keahlian_komputer, NULL AS bahasa_asing,';
		$extra_identitas .= ! empty($chk_tt['has_foto'])
			? ' c.foto_path,'
			: ' NULL AS foto_path,';

		$q = $this->db->query(
			"SELECT ft.id_token, ft.id_lamaran, ft.tujuan, ft.kadaluarsa_pada, ft.dipakai_pada, ft.is_revoked,
			        a.id_kandidat, a.id_req, a.status_global,
			        c.nama_lengkap, c.nama_panggilan, c.email, c.no_wa_normal, c.tempat_lahir, c.tanggal_lahir,
			        c.jenis_kelamin, c.status_pernikahan, c.kota_domisili, c.alamat_lengkap,
			        c.pendidikan_terakhir, c.nama_sekolah, c.jurusan,
			        c.kontak_darurat_nama, c.kontak_darurat_telp, c.kontak_darurat_hub,
			        c.nik, c.no_sim, c.npwp, c.agama, c.gol_darah, c.tinggi_badan, c.berat_badan,
			        {$extra_identitas}
			        p.nama_posisi, d.nama AS nama_departemen, o.nama_outlet, req.tipe_penempatan,
			        ap.perusahaan_terakhir, ap.jabatan_terakhir, ap.periode_kerja, ap.gaji_terakhir, ap.gaji_diharapkan,
			        cb.nama_bank, cb.no_rekening, cb.nama_pemilik AS nama_pemilik_bank,
			        ch.riwayat_penyakit, ch.consent_khusus AS consent_kesehatan
			 FROM dbo.FORM_TOKENS ft
			 JOIN dbo.APPLICATIONS a ON a.id_lamaran = ft.id_lamaran
			 JOIN dbo.CANDIDATES c ON c.id_kandidat = a.id_kandidat
			 JOIN dbo.REQUISITIONS req ON req.id_req = a.id_req
			 JOIN dbo.M_POSISI p ON p.id_posisi = req.id_posisi
			 LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
			 LEFT JOIN dbo.M_OUTLET o ON o.id_outlet = req.id_outlet
			 LEFT JOIN dbo.APPLICATION_PROFILE ap ON ap.id_lamaran = a.id_lamaran
			 LEFT JOIN dbo.CANDIDATE_BANK cb ON cb.id_lamaran = a.id_lamaran
			 LEFT JOIN dbo.CANDIDATE_HEALTH ch ON ch.id_kandidat = c.id_kandidat
			 WHERE ft.token = ?",
			array((string) $token)
		);
		$row = $q->row_array();
		$q->free_result();

		if ( ! $row) {
			return NULL;
		}

		$row['valid'] = ( ! $row['is_revoked']
			&& $row['dipakai_pada'] === NULL
			&& ($row['kadaluarsa_pada'] === NULL || strtotime($row['kadaluarsa_pada']) > time()));

		return $row;
	}

	/**
	 * Ambil riwayat pengalaman kerja yang sudah tersimpan (bila ada)
	 */
	public function get_work_experiences($id_lamaran)
	{
		$tbl_exists = $this->db->query("SELECT 1 FROM sys.tables WHERE name = 'CANDIDATE_WORK_EXPERIENCES'")->row();
		if ( ! $tbl_exists) {
			return array();
		}

		$q = $this->db->query(
			"SELECT * FROM dbo.CANDIDATE_WORK_EXPERIENCES WHERE id_lamaran = ? ORDER BY urutan ASC, id_exp ASC",
			array((int) $id_lamaran)
		);
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	/**
	 * Ambil susunan keluarga yang sudah tersimpan (bila ada)
	 */
	public function get_family_members($id_kandidat)
	{
		$tbl_exists = $this->db->query("SELECT 1 FROM sys.tables WHERE name = 'CANDIDATE_FAMILY'")->row();
		if ( ! $tbl_exists) {
			return array();
		}

		$q = $this->db->query(
			"SELECT * FROM dbo.CANDIDATE_FAMILY WHERE id_kandidat = ? ORDER BY urutan ASC, id_family ASC",
			array((int) $id_kandidat)
		);
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	/**
	 * Ambil riwayat pendidikan non-formal / pelatihan / kursus
	 */
	public function get_trainings($id_kandidat)
	{
		$tbl_exists = $this->db->query("SELECT 1 FROM sys.tables WHERE name = 'CANDIDATE_TRAININGS'")->row();
		if ( ! $tbl_exists) {
			return array();
		}

		$q = $this->db->query(
			"SELECT * FROM dbo.CANDIDATE_TRAININGS WHERE id_kandidat = ? ORDER BY urutan ASC, id_training ASC",
			array((int) $id_kandidat)
		);
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	/**
	 * Ambil daftar referensi kerja profesional
	 */
	public function get_references($id_kandidat)
	{
		$tbl_exists = $this->db->query("SELECT 1 FROM sys.tables WHERE name = 'CANDIDATE_REFERENCES'")->row();
		if ( ! $tbl_exists) {
			return array();
		}

		$q = $this->db->query(
			"SELECT * FROM dbo.CANDIDATE_REFERENCES WHERE id_kandidat = ? ORDER BY urutan ASC, id_ref ASC",
			array((int) $id_kandidat)
		);
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	/**
	 * Ambil data kuesioner evaluasi diri & kesiapan kerja
	 */
	/**
	 * Catat dokumen pas foto di CANDIDATE_DOCUMENTS dan perbarui CANDIDATES.foto_path
	 */
	public function save_photo($id_lamaran, $id_kandidat, array $file_info)
	{
		// 1. Ambil id_dokumen untuk 'Pas Foto'
		$q_dok = $this->db->query("SELECT id_dokumen FROM dbo.M_DOKUMEN WHERE nama_dokumen = N'Pas Foto'");
		$dok = $q_dok->row_array();
		$id_dokumen = !empty($dok['id_dokumen']) ? (int) $dok['id_dokumen'] : NULL;

		if ($id_dokumen) {
			// Nonaktifkan / update catatan dokumen pas foto lama jika ada
			$this->db->query(
				"INSERT INTO dbo.CANDIDATE_DOCUMENTS
				   (id_lamaran, id_dokumen, path_file, nama_file_asli, hash_sha256,
				    ukuran_byte, mime_type, status_verifikasi, diunggah_pada, ip_pengunggah)
				 VALUES (?, ?, ?, ?, ?, ?, ?, 'Proses', GETDATE(), ?)",
				array(
					(int) $id_lamaran,
					$id_dokumen,
					$file_info['path_file'],
					$file_info['nama_asli'],
					$file_info['hash'],
					(int) $file_info['ukuran'],
					$file_info['mime'],
					$file_info['ip'] ?? NULL
				)
			);
		}

		// 2. Perbarui kolom foto_path pada CANDIDATES
		$this->db->query(
			"UPDATE dbo.CANDIDATES SET foto_path = ? WHERE id_kandidat = ?",
			array($file_info['path_file'], (int) $id_kandidat)
		);
	}

	public function get_questionnaire($id_lamaran)
	{
		$tbl_exists = $this->db->query("SELECT 1 FROM sys.tables WHERE name = 'CANDIDATE_QUESTIONNAIRE'")->row();
		if ( ! $tbl_exists) {
			return NULL;
		}

		$q = $this->db->query(
			"SELECT TOP 1 * FROM dbo.CANDIDATE_QUESTIONNAIRE WHERE id_lamaran = ? ORDER BY id_quest DESC",
			array((int) $id_lamaran)
		);
		$row = $q->row_array();
		$q->free_result();
		return $row ? $row : NULL;
	}

	/**
	 * Simpan data onboarding via Stored Procedure & Query Terstruktur
	 */
	public function save_onboarding($data)
	{
		$id_lamaran = (int) $data['id_lamaran'];
		$id_kandidat = (int) $data['id_kandidat'];
		$token = (string) $data['token'];

		// Pastikan migrasi tabel tambahan sudah jalan di database
		$this->_ensure_tables_exist();

		// Mulai Transaksi
		$this->db->trans_begin();

		try {
			// 1. Eksekusi Stored Procedure simpan identitas, rekening, & kesehatan
			$is_final = !empty($data['is_final']) ? 1 : 0;
			$sp_sql = "{CALL dbo.sp_SaveOnboardingData(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";
			$params = array(
				$id_lamaran,
				$token,
				$data['nama_panggilan'] ?: NULL,
				$data['nik'] ?: NULL,
				$data['no_sim'] ?: NULL,
				$data['npwp'] ?: NULL,
				$data['agama'] ?: NULL,
				$data['gol_darah'] ?: NULL,
				$data['tinggi_badan'] !== NULL ? (int)$data['tinggi_badan'] : NULL,
				$data['berat_badan'] !== NULL ? (int)$data['berat_badan'] : NULL,
				!empty($data['status_tempat_tinggal']) ? trim($data['status_tempat_tinggal']) : NULL,
				!empty($data['keahlian_komputer']) ? trim($data['keahlian_komputer']) : NULL,
				!empty($data['bahasa_asing']) ? trim($data['bahasa_asing']) : NULL,
				$data['nama_bank'] ?: NULL,
				$data['no_rekening'] ?: NULL,
				$data['nama_pemilik_bank'] ?: NULL,
				$data['riwayat_penyakit'] ?: NULL,
				!empty($data['consent_kesehatan']) ? 1 : 0,
				$data['ip'] ?: '127.0.0.1',
				$is_final
			);

			$res = $this->db->query($sp_sql, $params);
			if ( ! $res) {
				// Fallback langsung update jika SP belum ter-deploy
				$this->_direct_save_onboarding($data);
			}

			// 2. Simpan Riwayat Pekerjaan Multi-Item
			if ( ! empty($data['experiences']) && is_array($data['experiences'])) {
				$this->db->query("DELETE FROM dbo.CANDIDATE_WORK_EXPERIENCES WHERE id_lamaran = ?", array($id_lamaran));

				$urut = 1;
				foreach ($data['experiences'] as $exp) {
					$pt = trim($exp['nama_perusahaan'] ?? '');
					$jab = trim($exp['posisi_jabatan'] ?? '');
					if ($pt === '' || $jab === '') {
						continue;
					}

					$gaji = isset($exp['gaji_terakhir']) && $exp['gaji_terakhir'] !== ''
						? (float) preg_replace('/[^0-9.]/', '', $exp['gaji_terakhir'])
						: NULL;

					$this->db->query(
						"INSERT INTO dbo.CANDIDATE_WORK_EXPERIENCES
						   (id_lamaran, id_kandidat, nama_perusahaan, posisi_jabatan, periode_kerja, gaji_terakhir, alasan_keluar, deskripsi_tugas, urutan, created_at)
						 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())",
						array(
							$id_lamaran,
							$id_kandidat,
							$pt,
							$jab,
							trim($exp['periode_kerja'] ?? '') ?: NULL,
							$gaji,
							trim($exp['alasan_keluar'] ?? '') ?: NULL,
							trim($exp['deskripsi_tugas'] ?? '') ?: NULL,
							$urut++
						)
					);
				}
			}

			// 3. Simpan Susunan Keluarga Multi-Item
			if ( ! empty($data['family']) && is_array($data['family'])) {
				$this->db->query("DELETE FROM dbo.CANDIDATE_FAMILY WHERE id_kandidat = ?", array($id_kandidat));

				$urut_fam = 1;
				foreach ($data['family'] as $fam) {
					$hub = trim($fam['hubungan'] ?? '');
					$nama_kel = trim($fam['nama_lengkap'] ?? '');
					if ($hub === '' || $nama_kel === '') {
						continue;
					}

					$usia = isset($fam['usia']) && $fam['usia'] !== '' ? (int) $fam['usia'] : NULL;

					$this->db->query(
						"INSERT INTO dbo.CANDIDATE_FAMILY
						   (id_kandidat, hubungan, nama_lengkap, jenis_kelamin, usia, pendidikan, pekerjaan, no_telp, urutan, created_at)
						 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())",
						array(
							$id_kandidat,
							$hub,
							$nama_kel,
							in_array($fam['jenis_kelamin'] ?? '', array('L', 'P')) ? $fam['jenis_kelamin'] : NULL,
							$usia,
							trim($fam['pendidikan'] ?? '') ?: NULL,
							trim($fam['pekerjaan'] ?? '') ?: NULL,
							trim($fam['no_telp'] ?? '') ?: NULL,
							$urut_fam++
						)
					);
				}
			}

			// 4. Simpan Riwayat Pendidikan Non-Formal / Pelatihan / Kursus Multi-Item
			if (isset($data['trainings']) && is_array($data['trainings'])) {
				$this->db->query("DELETE FROM dbo.CANDIDATE_TRAININGS WHERE id_kandidat = ?", array($id_kandidat));

				$urut_trn = 1;
				foreach ($data['trainings'] as $trn) {
					$nama_trn = trim($trn['nama_pelatihan'] ?? '');
					if ($nama_trn === '') {
						continue;
					}

					$this->db->query(
						"INSERT INTO dbo.CANDIDATE_TRAININGS
						   (id_kandidat, id_lamaran, nama_pelatihan, penyelenggara, tahun, keterangan, urutan, created_at)
						 VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE())",
						array(
							$id_kandidat,
							$id_lamaran,
							$nama_trn,
							trim($trn['penyelenggara'] ?? '') ?: NULL,
							trim($trn['tahun'] ?? '') ?: NULL,
							trim($trn['keterangan'] ?? '') ?: NULL,
							$urut_trn++
						)
					);
				}
			}

			// 5. Simpan Referensi Kerja Profesional Multi-Item
			if (isset($data['references']) && is_array($data['references'])) {
				$this->db->query("DELETE FROM dbo.CANDIDATE_REFERENCES WHERE id_kandidat = ?", array($id_kandidat));

				$urut_ref = 1;
				foreach ($data['references'] as $ref) {
					$nama_ref = trim($ref['nama_referensi'] ?? '');
					if ($nama_ref === '') {
						continue;
					}

					$this->db->query(
						"INSERT INTO dbo.CANDIDATE_REFERENCES
						   (id_kandidat, id_lamaran, nama_referensi, perusahaan, jabatan, no_telp, hubungan, urutan, created_at)
						 VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE())",
						array(
							$id_kandidat,
							$id_lamaran,
							$nama_ref,
							trim($ref['perusahaan'] ?? '') ?: NULL,
							trim($ref['jabatan'] ?? '') ?: NULL,
							trim($ref['no_telp'] ?? '') ?: NULL,
							trim($ref['hubungan'] ?? '') ?: NULL,
							$urut_ref++
						)
					);
				}
			}

			// 6. Simpan Butir Kuesioner Evaluasi Diri (Minat & Konsep Pribadi) & Informasi Umum RPG
			if (!empty($data['quest']) && is_array($data['quest'])) {
				$q_data = $data['quest'];
				$this->db->query("DELETE FROM dbo.CANDIDATE_QUESTIONNAIRE WHERE id_lamaran = ?", array($id_lamaran));

				// Cek ketersediaan kolom tambahan kuesioner sebelum insert
				$chk_cols = $this->db->query(
					"SELECT COL_LENGTH('dbo.CANDIDATE_QUESTIONNAIRE', 'alasan_cocok_posisi') AS has_cocok,
					        COL_LENGTH('dbo.CANDIDATE_QUESTIONNAIRE', 'riwayat_melamar_rpg') AS has_melamar"
				)->row_array();

				if (!empty($chk_cols['has_cocok'])) {
					$this->db->query(
						"INSERT INTO dbo.CANDIDATE_QUESTIONNAIRE
						   (id_lamaran, id_kandidat, alasan_melamar, alasan_cocok_posisi, pengetahuan_rpg, kelebihan_diri, kekurangan_diri,
						    prestasi_terbesar, rencana_karir_5thn, masalah_tersulit_solusi, lingkungan_kerja_idaman,
						    harapan_gaji_fasilitas, ketersediaan_mulai, bersedia_shift_lembur,
						    bersedia_luar_kota, punya_bisnis_sampingan, relasi_keluarga_rpg, riwayat_tindak_pidana,
						    riwayat_melamar_rpg, kepemilikan_kendaraan, diisi_pada)
						 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())",
						array(
							$id_lamaran,
							$id_kandidat,
							trim($q_data['alasan_melamar'] ?? '') ?: NULL,
							trim($q_data['alasan_cocok_posisi'] ?? '') ?: NULL,
							trim($q_data['pengetahuan_rpg'] ?? '') ?: NULL,
							trim($q_data['kelebihan_diri'] ?? '') ?: NULL,
							trim($q_data['kekurangan_diri'] ?? '') ?: NULL,
							trim($q_data['prestasi_terbesar'] ?? '') ?: NULL,
							trim($q_data['rencana_karir_5thn'] ?? '') ?: NULL,
							trim($q_data['masalah_tersulit_solusi'] ?? '') ?: NULL,
							trim($q_data['lingkungan_kerja_idaman'] ?? '') ?: NULL,
							trim($q_data['harapan_gaji_fasilitas'] ?? '') ?: NULL,
							trim($q_data['ketersediaan_mulai'] ?? '') ?: NULL,
							trim($q_data['bersedia_shift_lembur'] ?? '') ?: NULL,
							trim($q_data['bersedia_luar_kota'] ?? '') ?: NULL,
							trim($q_data['punya_bisnis_sampingan'] ?? '') ?: NULL,
							trim($q_data['relasi_keluarga_rpg'] ?? '') ?: NULL,
							trim($q_data['riwayat_tindak_pidana'] ?? '') ?: NULL,
							trim($q_data['riwayat_melamar_rpg'] ?? '') ?: NULL,
							trim($q_data['kepemilikan_kendaraan'] ?? '') ?: NULL,
						)
					);
				} else {
					$this->db->query(
						"INSERT INTO dbo.CANDIDATE_QUESTIONNAIRE
						   (id_lamaran, id_kandidat, alasan_melamar, pengetahuan_rpg, kelebihan_diri, kekurangan_diri,
						    prestasi_terbesar, harapan_gaji_fasilitas, ketersediaan_mulai, bersedia_shift_lembur,
						    bersedia_luar_kota, punya_bisnis_sampingan, relasi_keluarga_rpg, riwayat_tindak_pidana, diisi_pada)
						 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())",
						array(
							$id_lamaran,
							$id_kandidat,
							trim($q_data['alasan_melamar'] ?? '') ?: NULL,
							trim($q_data['pengetahuan_rpg'] ?? '') ?: NULL,
							trim($q_data['kelebihan_diri'] ?? '') ?: NULL,
							trim($q_data['kekurangan_diri'] ?? '') ?: NULL,
							trim($q_data['prestasi_terbesar'] ?? '') ?: NULL,
							trim($q_data['harapan_gaji_fasilitas'] ?? '') ?: NULL,
							trim($q_data['ketersediaan_mulai'] ?? '') ?: NULL,
							trim($q_data['bersedia_shift_lembur'] ?? '') ?: NULL,
							trim($q_data['bersedia_luar_kota'] ?? '') ?: NULL,
							trim($q_data['punya_bisnis_sampingan'] ?? '') ?: NULL,
							trim($q_data['relasi_keluarga_rpg'] ?? '') ?: NULL,
							trim($q_data['riwayat_tindak_pidana'] ?? '') ?: NULL,
						)
					);
				}
			}

			// 7. Update langsung kolom identitas tempat tinggal & keahlian jika ada
			$this->db->query(
				"UPDATE dbo.CANDIDATES
				 SET status_tempat_tinggal = COALESCE(?, status_tempat_tinggal),
				     keahlian_komputer     = COALESCE(?, keahlian_komputer),
				     bahasa_asing          = COALESCE(?, bahasa_asing)
				 WHERE id_kandidat = ?",
				array(
					!empty($data['status_tempat_tinggal']) ? trim($data['status_tempat_tinggal']) : NULL,
					!empty($data['keahlian_komputer']) ? trim($data['keahlian_komputer']) : NULL,
					!empty($data['bahasa_asing']) ? trim($data['bahasa_asing']) : NULL,
					$id_kandidat
				)
			);

			if ($this->db->trans_status() === FALSE) {
				$this->db->trans_rollback();
				throw new RuntimeException('Gagal menyimpan formulir onboarding.');
			}

			$this->db->trans_commit();
			return TRUE;
		} catch (Exception $e) {
			$this->db->trans_rollback();
			throw new RuntimeException($e->getMessage());
		}
	}

	/**
	 * Fallback save jika SP belum ter-deploy
	 */
	private function _direct_save_onboarding($data)
	{
		$id_lamaran = (int) $data['id_lamaran'];
		$id_kandidat = (int) $data['id_kandidat'];

		$foto_sql = !empty($data['foto_path']) ? ", foto_path = ?" : "";
		$params_cand = array(
			$data['nama_panggilan'] ?: NULL,
			$data['nik'] ?: NULL,
			$data['no_sim'] ?: NULL,
			$data['npwp'] ?: NULL,
			$data['agama'] ?: NULL,
			$data['gol_darah'] ?: NULL,
			$data['tinggi_badan'] !== NULL ? (int)$data['tinggi_badan'] : NULL,
			$data['berat_badan'] !== NULL ? (int)$data['berat_badan'] : NULL,
			!empty($data['status_tempat_tinggal']) ? trim($data['status_tempat_tinggal']) : NULL,
			!empty($data['keahlian_komputer']) ? trim($data['keahlian_komputer']) : NULL,
			!empty($data['bahasa_asing']) ? trim($data['bahasa_asing']) : NULL,
		);
		if (!empty($data['foto_path'])) {
			$params_cand[] = $data['foto_path'];
		}
		$params_cand[] = $id_kandidat;

		$this->db->query(
			"UPDATE dbo.CANDIDATES
			 SET nama_panggilan        = COALESCE(?, nama_panggilan),
			     nik                   = COALESCE(?, nik),
			     no_sim                = COALESCE(?, no_sim),
			     npwp                  = COALESCE(?, npwp),
			     agama                 = COALESCE(?, agama),
			     gol_darah             = COALESCE(?, gol_darah),
			     tinggi_badan          = COALESCE(?, tinggi_badan),
			     berat_badan           = COALESCE(?, berat_badan),
			     status_tempat_tinggal = COALESCE(?, status_tempat_tinggal),
			     keahlian_komputer     = COALESCE(?, keahlian_komputer),
			     bahasa_asing          = COALESCE(?, bahasa_asing)
			     {$foto_sql}
			 WHERE id_kandidat = ?",
			$params_cand
		);

		if (!empty($data['no_rekening'])) {
			$bank_exists = $this->db->query("SELECT 1 FROM dbo.CANDIDATE_BANK WHERE id_lamaran = ?", array($id_lamaran))->row();
			if ($bank_exists) {
				$this->db->query(
					"UPDATE dbo.CANDIDATE_BANK SET nama_bank = ?, no_rekening = ?, nama_pemilik = ?, diinput_pada = GETDATE() WHERE id_lamaran = ?",
					array($data['nama_bank'], $data['no_rekening'], $data['nama_pemilik_bank'], $id_lamaran)
				);
			} else {
				$this->db->query(
					"INSERT INTO dbo.CANDIDATE_BANK (id_lamaran, nama_bank, no_rekening, nama_pemilik, diinput_pada) VALUES (?, ?, ?, ?, GETDATE())",
					array($id_lamaran, $data['nama_bank'], $data['no_rekening'], $data['nama_pemilik_bank'])
				);
			}
		}

		if (!empty($data['consent_kesehatan']) && !empty($data['riwayat_penyakit'])) {
			$health_exists = $this->db->query("SELECT 1 FROM dbo.CANDIDATE_HEALTH WHERE id_kandidat = ?", array($id_kandidat))->row();
			if ($health_exists) {
				$this->db->query(
					"UPDATE dbo.CANDIDATE_HEALTH SET riwayat_penyakit = ?, consent_khusus = 1, consent_pada = GETDATE() WHERE id_kandidat = ?",
					array($data['riwayat_penyakit'], $id_kandidat)
				);
			} else {
				$this->db->query(
					"INSERT INTO dbo.CANDIDATE_HEALTH (id_kandidat, riwayat_penyakit, consent_khusus, consent_pada) VALUES (?, ?, 1, GETDATE())",
					array($id_kandidat, $data['riwayat_penyakit'])
				);
			}
		}

		$this->db->query(
			"INSERT INTO dbo.APPLICATION_HISTORY (id_lamaran, jenis_event, catatan, waktu) VALUES (?, 'DOKUMEN', N'Kandidat melengkapi formulir onboarding.', GETDATE())",
			array($id_lamaran)
		);

		$this->db->query(
			"UPDATE dbo.FORM_TOKENS SET dipakai_pada = GETDATE() WHERE token = ?",
			array($data['token'])
		);
	}

	/**
	 * Self-check & idempotent table/column creation
	 */
	private function _ensure_tables_exist()
	{
		// 1. Cek kolom di CANDIDATES menggunakan dynamic SQL EXEC agar kebal error kompilasi batch 2705 SQL Server
		$cols = array(
			'nik'                   => 'VARCHAR(30)',
			'agama'                 => 'NVARCHAR(30)',
			'gol_darah'             => 'VARCHAR(5)',
			'no_sim'                => 'VARCHAR(30)',
			'npwp'                  => 'VARCHAR(40)',
			'nama_panggilan'        => 'NVARCHAR(50)',
			'tinggi_badan'          => 'INT',
			'berat_badan'           => 'INT',
			'status_tempat_tinggal' => 'NVARCHAR(50)',
			'keahlian_komputer'     => 'NVARCHAR(255)',
			'bahasa_asing'          => 'NVARCHAR(255)'
		);
		foreach ($cols as $col => $type) {
			$this->db->query(
				"IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = '{$col}')
				    EXEC('ALTER TABLE dbo.CANDIDATES ADD {$col} {$type} NULL')"
			);
		}

		// 2. Cek tabel CANDIDATE_WORK_EXPERIENCES
		$this->db->query(
			"IF OBJECT_ID('dbo.CANDIDATE_WORK_EXPERIENCES') IS NULL
			CREATE TABLE dbo.CANDIDATE_WORK_EXPERIENCES (
				id_exp           INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
				id_lamaran       INT NOT NULL,
				id_kandidat      INT NOT NULL,
				nama_perusahaan  NVARCHAR(150) NOT NULL,
				posisi_jabatan   NVARCHAR(150) NOT NULL,
				periode_kerja    NVARCHAR(100) NULL,
				gaji_terakhir    DECIMAL(18,2) NULL,
				alasan_keluar    NVARCHAR(500) NULL,
				deskripsi_tugas  NVARCHAR(MAX) NULL,
				urutan           INT NOT NULL DEFAULT 1,
				created_at       DATETIME NOT NULL DEFAULT GETDATE()
			)"
		);

		// 3. Cek tabel CANDIDATE_FAMILY
		$this->db->query(
			"IF OBJECT_ID('dbo.CANDIDATE_FAMILY') IS NULL
			CREATE TABLE dbo.CANDIDATE_FAMILY (
				id_family     INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
				id_kandidat   INT NOT NULL,
				hubungan      NVARCHAR(50) NOT NULL,
				nama_lengkap  NVARCHAR(150) NOT NULL,
				jenis_kelamin CHAR(1) NULL,
				usia          INT NULL,
				pendidikan    NVARCHAR(50) NULL,
				pekerjaan     NVARCHAR(100) NULL,
				no_telp       VARCHAR(30) NULL,
				urutan        INT NOT NULL DEFAULT 1,
				created_at    DATETIME NOT NULL DEFAULT GETDATE()
			)"
		);

		// 4. Cek tabel CANDIDATE_TRAININGS
		$this->db->query(
			"IF OBJECT_ID('dbo.CANDIDATE_TRAININGS') IS NULL
			CREATE TABLE dbo.CANDIDATE_TRAININGS (
				id_training      INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
				id_kandidat      INT NOT NULL,
				id_lamaran       INT NULL,
				nama_pelatihan   NVARCHAR(150) NOT NULL,
				penyelenggara    NVARCHAR(150) NULL,
				tahun            VARCHAR(10) NULL,
				keterangan       NVARCHAR(255) NULL,
				urutan           INT NOT NULL DEFAULT 1,
				created_at       DATETIME NOT NULL DEFAULT GETDATE()
			)"
		);

		// 5. Cek tabel CANDIDATE_REFERENCES
		$this->db->query(
			"IF OBJECT_ID('dbo.CANDIDATE_REFERENCES') IS NULL
			CREATE TABLE dbo.CANDIDATE_REFERENCES (
				id_ref           INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
				id_kandidat      INT NOT NULL,
				id_lamaran       INT NULL,
				nama_referensi   NVARCHAR(150) NOT NULL,
				perusahaan       NVARCHAR(150) NULL,
				jabatan          NVARCHAR(100) NULL,
				no_telp          VARCHAR(40) NULL,
				hubungan         NVARCHAR(100) NULL,
				urutan           INT NOT NULL DEFAULT 1,
				created_at       DATETIME NOT NULL DEFAULT GETDATE()
			)"
		);

		// 6. Cek tabel CANDIDATE_QUESTIONNAIRE & kolom-kolomnya
		$this->db->query(
			"IF OBJECT_ID('dbo.CANDIDATE_QUESTIONNAIRE') IS NULL
			CREATE TABLE dbo.CANDIDATE_QUESTIONNAIRE (
				id_quest             INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
				id_lamaran           INT NOT NULL,
				id_kandidat          INT NOT NULL,
				alasan_melamar       NVARCHAR(MAX) NULL,
				pengetahuan_rpg      NVARCHAR(MAX) NULL,
				kelebihan_diri       NVARCHAR(MAX) NULL,
				kekurangan_diri      NVARCHAR(MAX) NULL,
				prestasi_terbesar    NVARCHAR(MAX) NULL,
				harapan_gaji_fasilitas NVARCHAR(500) NULL,
				ketersediaan_mulai   NVARCHAR(100) NULL,
				bersedia_shift_lembur NVARCHAR(50) NULL,
				bersedia_luar_kota   NVARCHAR(50) NULL,
				punya_bisnis_sampingan NVARCHAR(500) NULL,
				relasi_keluarga_rpg  NVARCHAR(500) NULL,
				riwayat_tindak_pidana NVARCHAR(500) NULL,
				diisi_pada           DATETIME NOT NULL DEFAULT GETDATE()
			)"
		);

		// Defensif: pastikan kolom baru evaluasi minat & info umum tersedia
		$q_extra_cols = array(
			'alasan_cocok_posisi'    => 'NVARCHAR(MAX)',
			'rencana_karir_5thn'     => 'NVARCHAR(MAX)',
			'masalah_tersulit_solusi'=> 'NVARCHAR(MAX)',
			'lingkungan_kerja_idaman'=> 'NVARCHAR(MAX)',
			'riwayat_melamar_rpg'    => 'NVARCHAR(500)',
			'kepemilikan_kendaraan'  => 'NVARCHAR(255)',
		);
		foreach ($q_extra_cols as $qcol => $qtype) {
			$this->db->query(
				"IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATE_QUESTIONNAIRE') AND name = '{$qcol}')
				    EXEC('ALTER TABLE dbo.CANDIDATE_QUESTIONNAIRE ADD {$qcol} {$qtype} NULL')"
			);
		}
	}
}
