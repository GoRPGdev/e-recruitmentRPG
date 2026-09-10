<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model Candidate_model -- Model Data Kandidat Pelamar & Profil Komprehensif
 *
 * Fungsi:
 * - Mengambil data detail profil pelamar, riwayat seleksi, dan histori tahapan lamaran.
 * - Membaca data pelengkap: riwayat keluarga, pendidikan formal/non-formal, riwayat kerja, data kesehatan, referensi kerja, dan kuesioner onboarding.
 * - Menyediakan fitur pembuatan token onboarding mandiri dan streaming pas foto resmi pelamar.
 * - Menangani scoping departemen dan paginasi daftar kandidat.
 */
class Candidate_model extends CI_Model
{
	public function get_detail($id_lamaran)
	{
		// Cek ketersediaan kolom onboarding & identitas baru di CANDIDATES
		$chk = $this->db->query("SELECT COL_LENGTH('dbo.CANDIDATES', 'nama_panggilan') AS has_panggilan, COL_LENGTH('dbo.CANDIDATES', 'status_tempat_tinggal') AS has_tt, COL_LENGTH('dbo.CANDIDATES', 'foto_path') AS has_foto")->row_array();
		$has_extra = ! empty($chk['has_panggilan']);
		$has_tt    = ! empty($chk['has_tt']);
		$has_foto  = ! empty($chk['has_foto']);

		$extra_cols = $has_extra
			? 'c.nama_panggilan, c.nik, c.no_sim, c.npwp, c.agama, c.gol_darah, c.tinggi_badan, c.berat_badan,'
			: 'NULL AS nama_panggilan, NULL AS nik, NULL AS no_sim, NULL AS npwp, NULL AS agama, NULL AS gol_darah, NULL AS tinggi_badan, NULL AS berat_badan,';
		$extra_cols .= $has_tt
			? ' c.status_tempat_tinggal, c.keahlian_komputer, c.bahasa_asing,'
			: ' NULL AS status_tempat_tinggal, NULL AS keahlian_komputer, NULL AS bahasa_asing,';
		$extra_cols .= $has_foto
			? ' c.foto_path,'
			: ' NULL AS foto_path,';

		$sql = "SELECT a.id_lamaran, a.id_kandidat, a.id_req, a.id_flow, a.status_global, a.tanggal_lamar,
		               a.intake_method, a.screening_score, a.id_stage_sekarang,
		               c.nama_lengkap, {$extra_cols} c.no_wa_normal, c.email, c.tempat_lahir, c.tanggal_lahir,
		               c.jenis_kelamin, c.pendidikan_terakhir, c.nama_sekolah, c.jurusan,
		               c.kota_domisili, c.alamat_lengkap, c.status_pernikahan,
		               c.kontak_darurat_nama, c.kontak_darurat_telp, c.kontak_darurat_hub,
		               c.is_blacklist, c.retensi_sampai, c.created_at AS kandidat_dibuat,
		               r.no_mpr, r.tipe_penempatan, r.status_req,
		               p.id_posisi, p.nama_posisi, p.id_departemen,
		               d.nama AS nama_departemen,
		               o.nama_outlet,
		               f.nama_flow, f.kode_flow,
		               st.nama_tahap AS nama_tahap_kini, st.tipe_tahap AS tipe_tahap_kini,
		               NULL AS nama_channel
		        FROM dbo.APPLICATIONS a
		        JOIN dbo.CANDIDATES c         ON c.id_kandidat = a.id_kandidat
		        JOIN dbo.REQUISITIONS r       ON r.id_req = a.id_req
		        JOIN dbo.M_POSISI p           ON p.id_posisi = r.id_posisi
		        LEFT JOIN dbo.M_DEPARTEMEN d  ON d.id_departemen = p.id_departemen
		        LEFT JOIN dbo.M_OUTLET o      ON o.id_outlet = r.id_outlet
		        LEFT JOIN dbo.M_FLOW f        ON f.id_flow = a.id_flow
		        LEFT JOIN dbo.M_STAGE st      ON st.id_stage = a.id_stage_sekarang
		        /* no channel join */
		        WHERE a.id_lamaran = ?";
		$q = $this->db->query($sql, array((int) $id_lamaran));
		$row = $q->row_array();
		$q->free_result();
		return $row ? $row : NULL;
	}

	public function get_profile($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT id_lamaran, perusahaan_terakhir, jabatan_terakhir, periode_kerja, gaji_terakhir, gaji_diharapkan, diisi_pada
			 FROM dbo.APPLICATION_PROFILE
			 WHERE id_lamaran = ?', array((int) $id_lamaran));
		$row = $q->row_array();
		$q->free_result();
		return $row ? $row : NULL;
	}

	public function get_health($id_kandidat)
	{
		$q = $this->db->query(
			'SELECT id_kandidat, riwayat_penyakit, consent_khusus, consent_pada
			 FROM dbo.CANDIDATE_HEALTH
			 WHERE id_kandidat = ?', array((int) $id_kandidat));
		$row = $q->row_array();
		$q->free_result();
		return $row ? $row : NULL;
	}

	public function get_bank($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT id_bank, id_lamaran, nama_bank, no_rekening, nama_pemilik, diinput_pada
			 FROM dbo.CANDIDATE_BANK
			 WHERE id_lamaran = ?', array((int) $id_lamaran));
		$row = $q->row_array();
		$q->free_result();
		return $row ? $row : NULL;
	}

	public function get_stages($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT aps.id_app_stage, aps.id_stage, aps.urutan, aps.status_tahap, aps.tanggal_mulai, aps.tanggal_selesai,
			        s.kode_stage, s.nama_tahap, s.tipe_tahap,
			        r.label AS label_remark, r.efek_status,
			        u.nama_snapshot AS diproses_oleh_nama
			 FROM dbo.APPLICATION_STAGES aps
			 JOIN dbo.M_STAGE s ON s.id_stage = aps.id_stage
			 LEFT JOIN dbo.M_REMARKS r ON r.id_remark = aps.id_remark
			 LEFT JOIN dbo.M_USERS u ON u.id_user = aps.pic_user
			 WHERE aps.id_lamaran = ?
			 ORDER BY aps.urutan', array((int) $id_lamaran));
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	public function get_documents($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT cd.id_cand_doc, cd.id_dokumen, cd.status_verifikasi, cd.nama_file_asli, cd.ukuran_byte, cd.mime_type,
			        cd.diunggah_pada, cd.catatan_verifikasi, cd.diverifikasi_pada,
			        dk.nama_dokumen, dk.kategori, dk.tingkat_sensitif,
			        u.nama_snapshot AS diverifikasi_oleh_nama
			 FROM dbo.CANDIDATE_DOCUMENTS cd
			 JOIN dbo.M_DOKUMEN dk ON dk.id_dokumen = cd.id_dokumen
			 LEFT JOIN dbo.M_USERS u ON u.id_user = cd.diverifikasi_oleh
			 WHERE cd.id_lamaran = ?
			 ORDER BY cd.diunggah_pada DESC', array((int) $id_lamaran));
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	public function get_history($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT h.id_history, h.jenis_event, h.waktu, h.status_dari, h.status_ke,
			        h.deskripsi,
			        s_dari.nama_tahap AS tahap_asal,
			        s_ke.nama_tahap AS tahap_tujuan,
			        r.label AS remark_label,
			        u.nama_snapshot AS oleh_nama
			 FROM dbo.APPLICATION_HISTORY h
			 LEFT JOIN dbo.M_STAGE s_dari ON s_dari.id_stage = h.id_stage_dari
			 LEFT JOIN dbo.M_STAGE s_ke   ON s_ke.id_stage = h.id_stage_ke
			 LEFT JOIN dbo.M_REMARKS r    ON r.id_remark = h.id_remark
			 LEFT JOIN dbo.M_USERS u      ON u.id_user = h.oleh_user
			 WHERE h.id_lamaran = ?
			 ORDER BY h.id_history DESC', array((int) $id_lamaran));
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	public function get_contacts($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT ac.id_kontak, ac.upaya_ke, ac.metode, ac.hasil, ac.catatan, ac.waktu_kontak,
			        u.nama_snapshot AS oleh_nama
			 FROM dbo.APPLICATION_CONTACTS ac
			 LEFT JOIN dbo.M_USERS u ON u.id_user = ac.oleh_user
			 WHERE ac.id_lamaran = ?
			 ORDER BY ac.upaya_ke DESC', array((int) $id_lamaran));
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	public function get_interviews($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT i.id_interview, i.tipe, i.jadwal, i.lokasi_atau_link, i.hasil, i.skor, i.catatan,
			        s.nama_tahap,
			        ip.peran AS peran_interviewer, u.nama_snapshot AS nama_interviewer
			 FROM dbo.INTERVIEWS i
			 JOIN dbo.APPLICATION_STAGES aps ON aps.id_app_stage = i.id_app_stage
			 JOIN dbo.M_STAGE s ON s.id_stage = aps.id_stage
			 LEFT JOIN dbo.INTERVIEW_PARTICIPANTS ip ON ip.id_interview = i.id_interview
			 LEFT JOIN dbo.M_USERS u ON u.id_user = ip.id_user
			 WHERE aps.id_lamaran = ?
			 ORDER BY i.id_interview DESC', array((int) $id_lamaran));
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	public function get_psikotes($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT p.id_psikotes, p.vendor_tes, p.tanggal_tes, p.skor_total, p.hasil, p.rekomendasi,
			        s.nama_tahap,
			        u.nama_snapshot AS dilakukan_oleh_nama
			 FROM dbo.PSIKOTES_RESULTS p
			 JOIN dbo.APPLICATION_STAGES aps ON aps.id_app_stage = p.id_app_stage
			 JOIN dbo.M_STAGE s ON s.id_stage = aps.id_stage
			 LEFT JOIN dbo.M_USERS u ON u.id_user = p.dilakukan_oleh
			 WHERE aps.id_lamaran = ?
			 ORDER BY p.id_psikotes DESC', array((int) $id_lamaran));
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	public function get_offer($id_lamaran)
	{
		$q = $this->db->query(
			'SELECT o.id_offer, o.gaji_ditawarkan, o.tanggal_penawaran, o.tanggal_join_disepakati, o.tanggal_join_aktual,
			        o.status_offer, o.alasan, u.nama_snapshot AS dibuat_oleh_nama
			 FROM dbo.OFFERS o
			 LEFT JOIN dbo.M_USERS u ON u.id_user = o.dibuat_oleh
			 WHERE o.id_lamaran = ?', array((int) $id_lamaran));
		$row = $q->row_array();
		$q->free_result();
		return $row ? $row : NULL;
	}

	public function remarks_for_stage($id_stage)
	{
		$q = $this->db->query(
			'SELECT id_remark, kode_remark, label, efek_status FROM dbo.M_REMARKS
			 WHERE id_stage = ? AND is_aktif = 1 ORDER BY urutan, id_remark', array((int) $id_stage));
		$r = $q->result_array(); $q->free_result(); return $r;
	}

	/* ================= DATA ONBOARDING LANJUTAN ================= */

	/**
	 * Ambil token formulir onboarding yang aktif / terbaru
	 */
	public function get_onboarding_token($id_lamaran)
	{
		$q = $this->db->query(
			"SELECT TOP 1 id_token, token, tujuan, kadaluarsa_pada, dipakai_pada, is_revoked
			 FROM dbo.FORM_TOKENS
			 WHERE id_lamaran = ? AND tujuan = 'FORM_ONBOARDING'
			 ORDER BY id_token DESC",
			array((int) $id_lamaran)
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
	 * Buat atau perbarui token onboarding baru untuk lamaran ini
	 */
	public function create_onboarding_token($id_lamaran, $id_user, $masa_hari = 14)
	{
		// Defensif: pastikan constraint CK_FT_tujuan mengizinkan 'FORM_ONBOARDING'
		$chk_tujuan = $this->db->query("SELECT definition FROM sys.check_constraints WHERE name = 'CK_FT_tujuan'")->row_array();
		if ( ! empty($chk_tujuan['definition']) && stripos($chk_tujuan['definition'], 'FORM_ONBOARDING') === FALSE) {
			$this->db->query("ALTER TABLE dbo.FORM_TOKENS DROP CONSTRAINT CK_FT_tujuan");
			$this->db->query("ALTER TABLE dbo.FORM_TOKENS ADD CONSTRAINT CK_FT_tujuan CHECK (tujuan IN ('FORM2', 'UPLOAD_DOKUMEN', 'FORM_ONBOARDING'))");
		}

		// Cabut token lama yang belum dipakai
		$this->db->query(
			"UPDATE dbo.FORM_TOKENS SET is_revoked = 1
			 WHERE id_lamaran = ? AND tujuan = 'FORM_ONBOARDING' AND dipakai_pada IS NULL AND is_revoked = 0",
			array((int) $id_lamaran)
		);

		$token = bin2hex(random_bytes(24));
		$exp   = (int) $masa_hari > 0
			? date('Y-m-d H:i:s', time() + (int) $masa_hari * 86400)
			: NULL;

		$user_val = ! empty($id_user) ? (int) $id_user : NULL;

		$this->db->query(
			"INSERT INTO dbo.FORM_TOKENS (id_lamaran, token, tujuan, kadaluarsa_pada, dibuat_oleh)
			 VALUES (?, ?, 'FORM_ONBOARDING', ?, ?)",
			array((int) $id_lamaran, $token, $exp, $user_val)
		);

		return $token;
	}

	/**
	 * Ambil daftar riwayat pengalaman kerja (multi-item)
	 */
	public function get_work_experiences($id_lamaran)
	{
		$tbl_exists = $this->db->query("SELECT 1 AS cnt FROM sys.tables WHERE name = 'CANDIDATE_WORK_EXPERIENCES'")->row();
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
	 * Ambil daftar anggota keluarga (multi-item)
	 */
	public function get_family_members($id_kandidat)
	{
		$tbl_exists = $this->db->query("SELECT 1 AS cnt FROM sys.tables WHERE name = 'CANDIDATE_FAMILY'")->row();
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
		$tbl_exists = $this->db->query("SELECT 1 AS cnt FROM sys.tables WHERE name = 'CANDIDATE_TRAININGS'")->row();
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
		$tbl_exists = $this->db->query("SELECT 1 AS cnt FROM sys.tables WHERE name = 'CANDIDATE_REFERENCES'")->row();
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
	 * Ambil data 12 butir kuesioner evaluasi diri & kesiapan kerja
	 */
	public function get_questionnaire($id_lamaran)
	{
		$tbl_exists = $this->db->query("SELECT 1 AS cnt FROM sys.tables WHERE name = 'CANDIDATE_QUESTIONNAIRE'")->row();
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

	/* ---- List Kandidat Global & Filter ---- */

	private function _list_where($f, &$b)
	{
		$w = array();
		if ( ! empty($f['status'])) {
			if ($f['status'] === 'In_Progress') {
				$w[] = "a.status_global IN ('In_Progress', 'On_Hold', 'Unreachable')";
			} elseif ($f['status'] === 'Hired') {
				$w[] = "a.status_global = 'Hired'";
			} elseif ($f['status'] === 'Rejected') {
				$w[] = "a.status_global IN ('Rejected', 'Withdrawn', 'Offer_Declined', 'No_Show')";
			} else {
				$w[] = 'a.status_global = ?';
				$b[] = (string) $f['status'];
			}
		}
		if ( ! empty($f['posisi'])) {
			$w[] = 'r.id_posisi = ?';
			$b[] = (int) $f['posisi'];
		}
		if ( ! empty($f['dept'])) {
			$w[] = 'p.id_departemen = ?';
			$b[] = (int) $f['dept'];
		}
		if ( ! empty($f['status_mpr'])) {
			if ($f['status_mpr'] === 'BUKA') {
				$w[] = "r.status_req IN ('Sourcing', 'Approved', 'Sourcing_Ulang')";
			} elseif ($f['status_mpr'] === 'TUTUP') {
				$w[] = "r.status_req IN ('Terpenuhi', 'Terpenuhi_Sebagian', 'Ditolak_HR', 'Ditolak_BOD', 'Dibatalkan', 'Kadaluarsa')";
			} else {
				$w[] = 'r.status_req = ?';
				$b[] = (string) $f['status_mpr'];
			}
		}
		if ( ! empty($f['dari'])) {
			$w[] = 'a.tanggal_lamar >= ?';
			$b[] = (string) $f['dari'];
		}
		if ( ! empty($f['sampai'])) {
			$w[] = 'a.tanggal_lamar < DATEADD(DAY, 1, ?)';
			$b[] = (string) $f['sampai'];
		}
		if ( ! empty($f['q'])) {
			$keyword = '%' . trim((string) $f['q']) . '%';
			$w[] = '(c.nama_lengkap LIKE ? OR c.email LIKE ? OR c.no_wa_normal LIKE ? OR r.no_mpr LIKE ?)';
			$b[] = $keyword;
			$b[] = $keyword;
			$b[] = $keyword;
			$b[] = $keyword;
		}

		// Scoping departemen bagi USER_DEPT
		$dept = current_user_dept();
		if ($dept !== NULL) {
			$w[] = 'p.id_departemen = ?';
			$b[] = (int) $dept;
		}

		return $w ? 'WHERE ' . implode(' AND ', $w) : '';
	}

	public function stats_summary($f = array())
	{
		$b = array();
		$where = $this->_list_where((array) $f, $b);
		$sql = "SELECT
		            COUNT(*) AS total,
		            SUM(CASE WHEN a.status_global IN ('In_Progress', 'On_Hold', 'Unreachable') THEN 1 ELSE 0 END) AS n_in_progress,
		            SUM(CASE WHEN a.status_global = 'Hired' THEN 1 ELSE 0 END) AS n_hired,
		            SUM(CASE WHEN a.status_global IN ('Rejected', 'Withdrawn', 'Offer_Declined', 'No_Show') THEN 1 ELSE 0 END) AS n_rejected
		        FROM dbo.APPLICATIONS a
		        JOIN dbo.CANDIDATES c   ON c.id_kandidat = a.id_kandidat
		        JOIN dbo.REQUISITIONS r ON r.id_req = a.id_req
		        JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi $where";
		$q = $this->db->query($sql, $b);
		$row = $q->row_array();
		$q->free_result();
		return $row ?: array('total' => 0, 'n_in_progress' => 0, 'n_hired' => 0, 'n_rejected' => 0);
	}

	public function count_list($f = array())
	{
		$b = array();
		$where = $this->_list_where((array) $f, $b);
		$sql = "SELECT COUNT(*) AS n
		        FROM dbo.APPLICATIONS a
		        JOIN dbo.CANDIDATES c   ON c.id_kandidat = a.id_kandidat
		        JOIN dbo.REQUISITIONS r ON r.id_req = a.id_req
		        JOIN dbo.M_POSISI p     ON p.id_posisi = r.id_posisi
		        $where";
		$q = $this->db->query($sql, $b);
		$res = (int) $q->row()->n;
		$q->free_result();
		return $res;
	}

	public function list_candidates($offset, $per, $f = array())
	{
		$b = array();
		$where = $this->_list_where((array) $f, $b);
		$b[] = (int) $offset;
		$b[] = (int) $offset + (int) $per - 1;

		$sql = "WITH q AS (
		            SELECT a.id_lamaran, a.id_kandidat, a.id_req, a.status_global, a.tanggal_lamar,
		                   a.intake_method, a.screening_score, a.id_stage_sekarang,
		                   c.nama_lengkap, c.no_wa_normal, c.email, c.kota_domisili, c.pendidikan_terakhir,
		                   r.no_mpr, r.tipe_penempatan, r.status_req,
		                   p.id_posisi, p.nama_posisi, p.id_departemen,
		                   d.nama AS nama_departemen,
		                   o.nama_outlet,
		                   st.nama_tahap AS nama_tahap_kini, st.tipe_tahap AS tipe_tahap_kini,
		                   ft.dipakai_pada AS form_dipakai_pada,
		                   CASE WHEN ft.id_token IS NOT NULL THEN 1 ELSE 0 END AS form_token_ada,
		                   ft.is_revoked AS form_revoked,
		                   ROW_NUMBER() OVER (ORDER BY a.id_lamaran DESC) AS rn
		            FROM dbo.APPLICATIONS a
		            JOIN dbo.CANDIDATES c        ON c.id_kandidat = a.id_kandidat
		            JOIN dbo.REQUISITIONS r      ON r.id_req = a.id_req
		            JOIN dbo.M_POSISI p          ON p.id_posisi = r.id_posisi
		            LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
		            LEFT JOIN dbo.M_OUTLET o     ON o.id_outlet = r.id_outlet
		            LEFT JOIN dbo.M_STAGE st     ON st.id_stage = a.id_stage_sekarang
		            LEFT JOIN dbo.FORM_TOKENS ft ON ft.id_token = (
		                SELECT TOP 1 ft2.id_token
		                FROM dbo.FORM_TOKENS ft2
		                WHERE ft2.id_lamaran = a.id_lamaran AND ft2.tujuan = 'FORM_ONBOARDING'
		                ORDER BY ft2.id_token DESC
		            )
		            $where
		        )
		        SELECT * FROM q WHERE rn BETWEEN ? AND ? ORDER BY rn";

		$q = $this->db->query($sql, $b);
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	public function get_positions()
	{
		$dept = current_user_dept();
		$w = 'WHERE is_aktif = 1';
		$p = array();
		if ($dept !== NULL) {
			$w .= ' AND id_departemen = ?';
			$p[] = (int) $dept;
		}
		$q = $this->db->query("SELECT id_posisi, nama_posisi FROM dbo.M_POSISI $w ORDER BY nama_posisi", $p);
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}

	public function get_departments()
	{
		$q = $this->db->query('SELECT id_departemen, nama FROM dbo.M_DEPARTEMEN WHERE is_aktif = 1 ORDER BY nama');
		$rows = $q->result_array();
		$q->free_result();
		return $rows;
	}
}
