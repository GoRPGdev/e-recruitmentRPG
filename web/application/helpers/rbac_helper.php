<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper RBAC. Daftar permission efektif disimpan di session saat login
 * (Auth::login -> Auth_model::get_permissions -> sp_GetUserPermissions).
 */

if ( ! function_exists('current_user')) {
	function current_user()
	{
		$CI =& get_instance();
		return (array) $CI->session->userdata('auth_user');
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
