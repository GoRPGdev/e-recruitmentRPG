<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper status_helper — Terjemahan label status DB ke bahasa ramah pengguna
 *
 * Pola: value DB tetap dikirim ke backend, label tampil diterjemahkan.
 * Panggil label_status() atau label_status_req() di view manapun.
 */

/**
 * Label ramah untuk status_global kandidat (APPLICATIONS.status_global)
 */
function label_status($db_val)
{
	static $map = array(
		'In_Progress'    => 'Sedang Proses',
		'Hired'          => 'Diterima',
		'Rejected'       => 'Ditolak',
		'Withdrawn'      => 'Mengundurkan Diri',
		'Offer_Declined' => 'Menolak Penawaran',
		'No_Show'        => 'Tidak Hadir',
		'On_Hold'        => 'Ditahan',
		'Unreachable'    => 'Tidak Dapat Dihubungi',
	);
	return isset($map[$db_val]) ? $map[$db_val] : $db_val;
}

/**
 * Label ramah untuk status_req MPR (REQUISITIONS.status_req)
 */
function label_status_req($db_val)
{
	static $map = array(
		'Draft'              => 'Draft',
		'Review_HR'          => 'Evaluasi HR',
		'Revisi_HR'          => 'Revisi dari HR',
		'Review_BOD'         => 'Review BOD',
		'Revisi_BOD'         => 'Revisi dari BOD',
		'Approved'           => 'Disetujui',
		'Sourcing'           => 'On Progress',
		'Sourcing_Ulang'     => 'On Progress',
		'Terpenuhi_Sebagian' => 'Terpenuhi Sebagian',
		'Terpenuhi'          => 'Terpenuhi',
		'Ditolak_HR'         => 'Ditolak HR',
		'Ditolak_BOD'        => 'Ditolak BOD',
		'Dibatalkan'         => 'Dibatalkan',
		'Kadaluarsa'         => 'Kadaluarsa',
	);
	return isset($map[$db_val]) ? $map[$db_val] : $db_val;
}

/**
 * Label ramah untuk intake_method (APPLICATIONS.intake_method)
 */
function label_intake($db_val)
{
	static $map = array(
		'FORM_PUBLIC' => 'Form Publik',
		'IMPORT_FILE' => 'Impor File',
		'MANUAL'      => 'Input Manual',
	);
	return isset($map[$db_val]) ? $map[$db_val] : $db_val;
}
