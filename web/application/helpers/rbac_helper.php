<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper rbac_helper -- Utilitas Role-Based Access Control (RBAC) & Privasi Data
 *
 * Fungsi:
 * - Membaca data pengguna aktif (current_user) dan scoping departemen (current_user_dept).
 * - Memeriksa izin hak akses pengguna (has_permission, has_any_permission).
 * - Memeriksa izin melihat data sensitif (can_sensitif) dan mencatat log audit akses (log_akses_sensitif) ke ACCESS_LOG_SENSITIF.
 */

if ( ! function_exists('current_user')) {
	function current_user()
	{
		$CI =& get_instance();
		return (array) $CI->session->userdata('auth_user');
	}
}

if ( ! function_exists('current_user_dept')) {
	/**
	 * id_departemen user login, atau NULL untuk peran non-departemen
	 * (IT_ADMIN / HR_ADMIN / HR_SPV / BOD / VIEWER). Dipakai untuk scoping
	 * "req sendiri": USER_DEPT hanya lihat kandidat dari MPR dept-nya (G4b).
	 */
	function current_user_dept()
	{
		$au = current_user();
		return isset($au['id_departemen']) && $au['id_departemen'] !== NULL
			? (int) $au['id_departemen'] : NULL;
	}
}

if ( ! function_exists('current_user_region')) {
	/**
	 * region user login (M_USERS.region), atau NULL kalau tidak dibatasi
	 * wilayah. Dipakai Regional Manager / Area Leader USER_DEPT yang
	 * mengawasi banyak outlet sekaligus dalam satu wilayah -- pasangan dari
	 * current_user_dept() tapi lewat M_OUTLET.region, bukan id_departemen.
	 * Satu user cuma punya salah satu (lihat CK_MUSERS_scope di migrasi).
	 */
	function current_user_region()
	{
		$au = current_user();
		return isset($au['region']) && $au['region'] !== NULL && $au['region'] !== ''
			? (string) $au['region'] : NULL;
	}
}

if ( ! function_exists('scope_dept_region_sql')) {
	/**
	 * Fragmen WHERE + binding untuk scoping "dept ATAU region" -- dipakai
	 * di WHERE-builder yang sudah JOIN ke kolom departemen & (LEFT JOIN)
	 * M_OUTLET. Kembalikan array [string|NULL $sql, array $binds].
	 * $sql NULL kalau user tidak dibatasi (SUPER_ADMIN) -- jangan ditambah
	 * ke WHERE sama sekali.
	 *
	 * @param string $dept_col   mis. 'p.id_departemen'
	 * @param string $region_col mis. 'o.region' -- kolom ini HARUS sudah
	 *                           bisa diakses lewat LEFT JOIN dbo.M_OUTLET
	 *                           di query pemanggil.
	 */
	function scope_dept_region_sql($dept_col, $region_col)
	{
		$dept = current_user_dept();
		if ($dept !== NULL) {
			return array("$dept_col = ?", array((int) $dept));
		}
		$region = current_user_region();
		if ($region !== NULL) {
			return array("$region_col = ?", array((string) $region));
		}
		return array(NULL, array());
	}
}

if ( ! function_exists('user_can_access_scope')) {
	/**
	 * Versi PHP (bukan SQL) dari scope_dept_region_sql() -- untuk
	 * controller yang sudah fetch satu baris (requisition/kandidat/dokumen)
	 * lalu membandingkan id_departemen/region baris itu terhadap user login.
	 *
	 * @param int|null    $row_dept   id_departemen milik baris data
	 * @param string|null $row_region region outlet milik baris data
	 * @return bool  TRUE kalau boleh akses (termasuk SUPER_ADMIN/unscoped)
	 */
	function user_can_access_scope($row_dept, $row_region)
	{
		$dept = current_user_dept();
		if ($dept !== NULL) {
			return $row_dept !== NULL && (int) $row_dept === (int) $dept;
		}
		$region = current_user_region();
		if ($region !== NULL) {
			return $row_region !== NULL && (string) $row_region === (string) $region;
		}
		return TRUE; // tidak dibatasi dept maupun region
	}
}

if ( ! function_exists('has_permission')) {
	/**
	 * @param string $kode  mis. 'LIHAT_CV', 'EDIT_FLOW_TEMPLATE'
	 */
	function has_permission($kode)
	{
		$CI =& get_instance();
		$perms = (array) $CI->session->userdata('permissions');
		return in_array($kode, $perms, TRUE);
	}
}

if ( ! function_exists('has_any_permission')) {
	function has_any_permission(array $kode_list)
	{
		foreach ($kode_list as $k) {
			if (has_permission($k)) {
				return TRUE;
			}
		}
		return FALSE;
	}
}

if ( ! function_exists('require_permission')) {
	/** Hentikan request dengan 403 kalau tidak punya permission. */
	function require_permission($kode)
	{
		if ( ! has_permission($kode)) {
			show_error('Butuh permission: ' . html_escape($kode), 403, 'Akses ditolak');
		}
	}
}

if ( ! function_exists('require_any_permission')) {
	/** 403 kalau user tidak punya SATU PUN dari daftar permission. */
	function require_any_permission(array $kode_list)
	{
		if ( ! has_any_permission($kode_list)) {
			show_error('Butuh salah satu permission: ' . html_escape(implode(', ', $kode_list)),
				403, 'Akses ditolak');
		}
	}
}

if ( ! function_exists('require_all_permissions')) {
	/** 403 kalau ada satu saja permission di daftar yang tidak dimiliki. */
	function require_all_permissions(array $kode_list)
	{
		foreach ($kode_list as $k) {
			if ( ! has_permission($k)) {
				show_error('Butuh permission: ' . html_escape($k), 403, 'Akses ditolak');
			}
		}
	}
}

/* =========================================================================
   Data pribadi spesifik -- RBAC tiga tingkat + ACCESS_LOG_SENSITIF.
   CLAUDE.md "Tingkat akses data". Tiap jenis dipetakan ke permission
   yang wajib DAN dicatat begitu benar-benar dibuka.

     jenis (ACCESS_LOG)  permission wajib        contoh data
     ------------------  ---------------------   -----------------------------
     DOK_IDENTITAS       LIHAT_DOK_IDENTITAS     KTP, KK, Ijazah, NPWP
     FINANSIAL           LIHAT_FINANSIAL         nomor rekening
     GAJI                LIHAT_GAJI              range gaji requisition, offer
     GAJI_PELAMAR        LIHAT_GAJI_PELAMAR      gaji terakhir & harapan pelamar
                                                (dicatat sebagai jenis 'GAJI')
     KESEHATAN           LIHAT_KESEHATAN         riwayat penyakit
   ========================================================================= */

if ( ! function_exists('sensitif_permission')) {
	/** Permission yang mengizinkan sebuah jenis data sensitif. */
	function sensitif_permission($jenis)
	{
		$map = array(
			'DOK_IDENTITAS' => 'LIHAT_DOK_IDENTITAS',
			'FINANSIAL'     => 'LIHAT_FINANSIAL',
			'GAJI'          => 'LIHAT_GAJI',
			'GAJI_PELAMAR'  => 'LIHAT_GAJI_PELAMAR',
			'KESEHATAN'     => 'LIHAT_KESEHATAN',
		);
		return isset($map[$jenis]) ? $map[$jenis] : NULL;
	}
}

if ( ! function_exists('can_sensitif')) {
	/** Boleh lihat jenis data sensitif ini? (tanpa mencatat / menghentikan). */
	function can_sensitif($jenis)
	{
		$perm = sensitif_permission($jenis);
		return $perm !== NULL && has_permission($perm);
	}
}

if ( ! function_exists('log_akses_sensitif')) {
	/**
	 * Catat satu pembukaan data sensitif ke ACCESS_LOG_SENSITIF.
	 * jenis 'GAJI_PELAMAR' disimpan sebagai 'GAJI' (CHECK kolom hanya 4 nilai).
	 */
	function log_akses_sensitif($jenis, $id_referensi = NULL)
	{
		$CI =& get_instance();
		$au  = (array) $CI->session->userdata('auth_user');
		$uid = isset($au['id_user']) ? (int) $au['id_user'] : 0;
		if ($uid <= 0) { return; }
		$jenis_log = ($jenis === 'GAJI_PELAMAR') ? 'GAJI' : $jenis;
		$CI->load->database();
		$stmt = sqlsrv_query($CI->db->conn_id, '{CALL dbo.sp_LogAksesSensitif(?,?,?,?)}', array(
			$uid, $jenis_log,
			$id_referensi !== NULL ? (int) $id_referensi : NULL,
			$CI->input->ip_address(),
		));
		if ($stmt !== FALSE) { do {} while (sqlsrv_next_result($stmt)); sqlsrv_free_stmt($stmt); }
	}
}

if ( ! function_exists('gate_sensitif')) {
	/**
	 * Gerbang tunggal untuk membuka data sensitif: 403 kalau tak berhak,
	 * kalau berhak -> catat ke ACCESS_LOG_SENSITIF lalu lanjut.
	 */
	function gate_sensitif($jenis, $id_referensi = NULL)
	{
		$perm = sensitif_permission($jenis);
		if ($perm === NULL) {
			show_error('Jenis data sensitif tidak dikenal: ' . html_escape($jenis), 500);
		}
		if ( ! has_permission($perm)) {
			show_error('Butuh permission: ' . html_escape($perm), 403, 'Akses ditolak');
		}
		log_akses_sensitif($jenis, $id_referensi);
	}
}
