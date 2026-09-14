<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper storage_helper -- Utilitas Penyimpanan File Fisik Aman di Luar Webroot
 *
 * Fungsi:
 * - Menangani penyimpanan berkas pelamar (CV, Ijazah, KTP, Pas Foto) di direktori aman di luar root web publik.
 * - Memvalidasi ukuran dan ekstensi berkas yang diizinkan.
 * - Menghitung hash SHA-256 berkas fisik untuk menjamin integritas data sesuai standar audit keamanan.
 */

if ( ! function_exists('erec_storage_base')) {
	function erec_storage_base()
	{
		$CI =& get_instance();
		return rtrim($CI->config->item('erec_storage_path'), '/\\');
	}
}

if ( ! function_exists('erec_store_upload')) {
	/**
	 * Validasi + simpan satu file upload ke <storage>/lamaran/<id_lamaran>/.
	 *
	 * @param array  $file        entri dari $_FILES (name, type, tmp_name, error, size)
	 * @param int    $id_lamaran
	 * @param string $prefix      mis. 'CV', 'KTP'
	 * @return array  path_file, nama_asli, hash, ukuran, mime, ext
	 * @throws RuntimeException
	 */
	function erec_store_upload(array $file, $id_lamaran, $prefix)
	{
		$CI =& get_instance();

		if (empty($file['name']) || (isset($file['error']) && $file['error'] === UPLOAD_ERR_NO_FILE)) {
			throw new RuntimeException('Tidak ada file.');
		}
		if ($file['error'] !== UPLOAD_ERR_OK) {
			throw new RuntimeException('Upload gagal (kode ' . $file['error'] . ').');
		}

		$max = (int) $CI->config->item('erec_cv_max_bytes');
		if ($file['size'] > $max) {
			throw new RuntimeException('Ukuran file maksimal ' . round($max / 1048576) . ' MB.');
		}

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		if ( ! in_array($ext, (array) $CI->config->item('erec_cv_ext'), TRUE)) {
			throw new RuntimeException('Format tidak diizinkan: ' . implode(', ', $CI->config->item('erec_cv_ext')) . '.');
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mime  = $finfo->file($file['tmp_name']);
		if ( ! in_array($mime, (array) $CI->config->item('erec_cv_mime'), TRUE)) {
			throw new RuntimeException('Tipe file tidak diizinkan (' . $mime . ').');
		}

		$sharding = date('Y') . DIRECTORY_SEPARATOR . date('m');
		$dir = erec_storage_base() . DIRECTORY_SEPARATOR . 'lamaran' . DIRECTORY_SEPARATOR . $sharding . DIRECTORY_SEPARATOR . (int) $id_lamaran;
		if ( ! is_dir($dir) && ! @mkdir($dir, 0770, TRUE)) {
			throw new RuntimeException('Folder penyimpanan tidak bisa dibuat.');
		}

		$hash = hash_file('sha256', $file['tmp_name']);
		$dest = $dir . DIRECTORY_SEPARATOR . $prefix . '_' . $hash . '.' . $ext;

		$ok = is_uploaded_file($file['tmp_name'])
			? @move_uploaded_file($file['tmp_name'], $dest)
			: @rename($file['tmp_name'], $dest);   // fallback untuk pengujian CLI
		if ( ! $ok) {
			throw new RuntimeException('Gagal menyimpan file.');
		}

		return array(
			'path_file' => $dest,
			'nama_asli' => $file['name'],
			'hash'      => $hash,
			'ukuran'    => (int) $file['size'],
			'mime'      => $mime,
			'ext'       => $ext,
		);
	}
}
