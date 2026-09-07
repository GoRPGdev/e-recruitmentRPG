<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Onboarding_model -- Menangani data formulir onboarding pelamar lanjutan
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
		$q = $this->db->query(
			"SELECT ft.id_token, ft.id_lamaran, ft.tujuan, ft.kadaluarsa_pada, ft.dipakai_pada, ft.is_revoked,
			        a.id_kandidat, a.id_req, a.status_global,
			        c.nama_lengkap, c.nama_panggilan, c.email, c.no_wa_normal, c.tempat_lahir, c.tanggal_lahir,
			        c.jenis_kelamin, c.status_pernikahan, c.kota_domisili, c.alamat_lengkap,
			        c.pendidikan_terakhir, c.nama_sekolah, c.jurusan,
			        c.kontak_darurat_nama, c.kontak_darurat_telp, c.kontak_darurat_hub,
			        c.nik, c.no_sim, c.npwp, c.agama, c.gol_darah, c.tinggi_badan, c.berat_badan,
			        p.nama_posisi, d.nama_departemen, o.nama_outlet, req.tipe_penempatan,
			        ap.perusahaan_terakhir, ap.jabatan_terakhir, ap.periode_kerja, ap.gaji_terakhir, ap.gaji_diharapkan,
			        cb.nama_bank, cb.no_rekening, cb.nama_pemilik AS nama_pemilik_bank,
			        ch.riwayat_penyakit, ch.consent_khusus AS consent_kesehatan
			 FROM dbo.FORM_TOKENS ft
			 JOIN dbo.APPLICATIONS a ON a.id_lamaran = ft.id_lamaran
			 JOIN dbo.CANDIDATES c ON c.id_kandidat = a.id_kandidat
			 JOIN dbo.REQUISITIONS req ON req.id_req = a.id_req
			 JOIN dbo.M_POSISI p ON p.id_posisi = req.id_posisi
			 LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = req.id_departemen
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
		// Cek apakah tabel sudah ada
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
			$sp_sql = "{CALL dbo.sp_SaveOnboardingData(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";
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
				$data['nama_bank'] ?: NULL,
				$data['no_rekening'] ?: NULL,
				$data['nama_pemilik_bank'] ?: NULL,
				$data['riwayat_penyakit'] ?: NULL,
				!empty($data['consent_kesehatan']) ? 1 : 0,
				$data['ip'] ?: '127.0.0.1'
			);

			$res = $this->db->query($sp_sql, $params);
			if ( ! $res) {
				// Fallback langsung update jika SP belum ter-deploy
				$this->_direct_save_onboarding($data);
			}

			// 2. Simpan Riwayat Pekerjaan Multi-Item
			if ( ! empty($data['experiences']) && is_array($data['experiences'])) {
				// Bersihkan data lama untuk lamaran ini
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
				// Bersihkan data keluarga lama untuk kandidat ini
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

		$this->db->query(
			"UPDATE dbo.CANDIDATES
			 SET nama_panggilan = COALESCE(?, nama_panggilan),
			     nik            = COALESCE(?, nik),
			     no_sim         = COALESCE(?, no_sim),
			     npwp           = COALESCE(?, npwp),
			     agama          = COALESCE(?, agama),
			     gol_darah      = COALESCE(?, gol_darah),
			     tinggi_badan   = COALESCE(?, tinggi_badan),
			     berat_badan    = COALESCE(?, berat_badan)
			 WHERE id_kandidat = ?",
			array(
				$data['nama_panggilan'] ?: NULL,
				$data['nik'] ?: NULL,
				$data['no_sim'] ?: NULL,
				$data['npwp'] ?: NULL,
				$data['agama'] ?: NULL,
				$data['gol_darah'] ?: NULL,
				$data['tinggi_badan'] !== NULL ? (int)$data['tinggi_badan'] : NULL,
				$data['berat_badan'] !== NULL ? (int)$data['berat_badan'] : NULL,
				$id_kandidat
			)
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
		// Cek kolom di CANDIDATES
		$cols = array('nik' => 'VARCHAR(30)', 'agama' => 'NVARCHAR(30)', 'gol_darah' => 'VARCHAR(5)', 'no_sim' => 'VARCHAR(30)', 'npwp' => 'VARCHAR(40)', 'nama_panggilan' => 'NVARCHAR(50)', 'tinggi_badan' => 'INT', 'berat_badan' => 'INT');
		foreach ($cols as $col => $type) {
			$c_exist = $this->db->query("SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CANDIDATES') AND name = '{$col}'")->row();
			if ( ! $c_exist) {
				$this->db->query("ALTER TABLE dbo.CANDIDATES ADD {$col} {$type} NULL");
			}
		}

		// Cek tabel CANDIDATE_WORK_EXPERIENCES
		$t1 = $this->db->query("SELECT 1 FROM sys.tables WHERE name = 'CANDIDATE_WORK_EXPERIENCES'")->row();
		if ( ! $t1) {
			$this->db->query(
				"CREATE TABLE dbo.CANDIDATE_WORK_EXPERIENCES (
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
		}

		// Cek tabel CANDIDATE_FAMILY
		$t2 = $this->db->query("SELECT 1 FROM sys.tables WHERE name = 'CANDIDATE_FAMILY'")->row();
		if ( ! $t2) {
			$this->db->query(
				"CREATE TABLE dbo.CANDIDATE_FAMILY (
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
		}

		// Pastikan constraint check FORM_TOKENS mendukung FORM_ONBOARDING
		$chk = $this->db->query("SELECT definition FROM sys.check_constraints WHERE name = 'CK_FT_tujuan'")->row();
		if ($chk && strpos($chk->definition, 'FORM_ONBOARDING') === FALSE) {
			$this->db->query("ALTER TABLE dbo.FORM_TOKENS DROP CONSTRAINT CK_FT_tujuan");
			$this->db->query("ALTER TABLE dbo.FORM_TOKENS ADD CONSTRAINT CK_FT_tujuan CHECK (tujuan IN ('FORM2', 'UPLOAD_DOKUMEN', 'FORM_ONBOARDING'))");
		}
	}
}
