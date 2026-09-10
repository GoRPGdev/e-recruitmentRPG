<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Reports -- Laporan Analitik & Ringkasan Eksekutif Rekrutmen RPG
 *
 * Fungsi:
 * - Menyajikan visualisasi data analitik: rasio konversi funnel, time-to-hire per posisi, dan performa pemenuhan formasi MPR.
 * - Menyajikan ringkasan alasan penolakan/kegagalan kandidat pada setiap tahapan seleksi.
 * - Mendukung ekspor data laporan analitik ke format spreadsheet.
 * - Proteksi akses: membutuhkan permission 'LIHAT_KANDIDAT' (melihat) dan 'EXPORT' (mengunduh).
 */
class Reports extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('LIHAT_KANDIDAT');
		$this->load->model('report_model');
		$this->load->helper(array('url', 'form', 'rbac'));
	}

	public function index()
	{
		$dept = current_user_dept();

		$f = array(
			'dari'       => $this->input->get('dari') ?: NULL,
			'sampai'     => $this->input->get('sampai') ?: NULL,
			'dept'       => $dept !== NULL ? $dept : ($this->input->get('dept') ?: NULL),
			'posisi'     => $this->input->get('posisi') ?: NULL,
			'outlet'     => $this->input->get('outlet') ?: NULL,
			'status_mpr' => $this->input->get('status_mpr') ?: NULL,
		);

		// Agregat data
		$kpi           = $this->report_model->get_kpi_summary($f);
		$funnel        = $this->report_model->get_funnel_stages($f);
		$top_positions = $this->report_model->get_top_positions($f, 6);
		$statuses      = $this->report_model->get_status_distribution($f);
		$trends        = $this->report_model->get_monthly_trend($f);
		$mpr_rows      = $this->report_model->get_mpr_fulfillment_report($f);
		$dept_summary  = $this->report_model->get_department_summary($f);

		// Opsi filter
		$departments   = $this->report_model->get_departments();
		if ($dept !== NULL) {
			$departments = array_values(array_filter($departments, function ($d) use ($dept) {
				return (int) $d['id_departemen'] === (int) $dept;
			}));
		}

		$this->load->view('layouts/main', array(
			'title'         => 'Laporan & Ringkasan Rekrutmen',
			'_content'      => 'reports/index',
			'wide'          => TRUE,
			'f'             => $f,
			'kpi'           => $kpi,
			'funnel'        => $funnel,
			'top_positions' => $top_positions,
			'statuses'      => $statuses,
			'trends'        => $trends,
			'mpr_rows'      => $mpr_rows,
			'dept_summary'  => $dept_summary,
			'departments'   => $departments,
			'positions'     => $this->report_model->get_positions($dept),
			'outlets'       => $this->report_model->get_outlets(),
			'can_export'    => has_permission('EXPORT'),
		));
	}

	/**
	 * Export Laporan Rekap Pemenuhan Formasi MPR ke Excel (.xls)
	 */
	public function export_mpr()
	{
		if ( ! has_permission('EXPORT')) {
			show_error('Akses ditolak: Anda tidak memiliki hak akses untuk ekspor data.', 403, '403 Forbidden');
		}

		$dept = current_user_dept();
		$f = array(
			'dari'       => $this->input->get('dari') ?: NULL,
			'sampai'     => $this->input->get('sampai') ?: NULL,
			'dept'       => $dept !== NULL ? $dept : ($this->input->get('dept') ?: NULL),
			'posisi'     => $this->input->get('posisi') ?: NULL,
			'outlet'     => $this->input->get('outlet') ?: NULL,
			'status_mpr' => $this->input->get('status_mpr') ?: NULL,
		);

		$rows = $this->report_model->get_mpr_fulfillment_report($f);
		$fname = 'rekap_pemenuhan_mpr_' . date('Ymd_His') . '.xls';

		$this->output
			->set_content_type('application/vnd.ms-excel')
			->set_header('Content-Disposition: attachment; filename="' . $fname . '"')
			->set_header('Cache-Control: no-store');

		if (empty($rows)) {
			$this->output->set_output('<table><tr><td>Tidak ada data pemenuhan MPR yang sesuai filter.</td></tr></table>');
			return;
		}

		$html = '<table border="1" cellpadding="5" cellspacing="0">';
		$html .= '<tr style="background:#e8ede9; font-weight:bold;">';
		$html .= '<th>No. MPR</th>';
		$html .= '<th>Posisi Lowongan</th>';
		$html .= '<th>Departemen</th>';
		$html .= '<th>Penempatan</th>';
		$html .= '<th>Tgl Pengajuan</th>';
		$html .= '<th>Target Kebutuhan</th>';
		$html .= '<th>Disetujui</th>';
		$html .= '<th>Terpenuhi (Hired)</th>';
		$html .= '<th>Sisa Formasi</th>';
		$html .= '<th>% Pemenuhan</th>';
		$html .= '<th>Kandidat Aktif</th>';
		$html .= '<th>Status MPR</th>';
		$html .= '</tr>';

		foreach ($rows as $r) {
			$html .= '<tr>';
			$html .= '<td style="mso-number-format:\'\@\'">' . html_escape($r['no_mpr']) . '</td>';
			$html .= '<td>' . html_escape($r['nama_posisi']) . '</td>';
			$html .= '<td>' . html_escape($r['nama_departemen'] ?: '-') . '</td>';
			$html .= '<td>' . html_escape($r['tipe_penempatan'] . ($r['nama_outlet'] ? ' - ' . $r['nama_outlet'] : '')) . '</td>';
			$html .= '<td style="mso-number-format:\'\@\'">' . html_escape($r['tanggal_pengajuan'] ?: '-') . '</td>';
			$html .= '<td style="text-align:right">' . (int) $r['jumlah_dibutuhkan'] . '</td>';
			$html .= '<td style="text-align:right">' . (int) $r['jumlah_disetujui'] . '</td>';
			$html .= '<td style="text-align:right; font-weight:bold">' . (int) $r['jumlah_terpenuhi'] . '</td>';
			$html .= '<td style="text-align:right">' . (int) $r['sisa_kebutuhan'] . '</td>';
			$html .= '<td style="text-align:right">' . (int) $r['persen_terpenuhi'] . '%</td>';
			$html .= '<td style="text-align:right">' . (int) $r['total_in_progress'] . '</td>';
			$html .= '<td>' . html_escape($r['status_req']) . '</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';

		$this->output->set_output(
			'<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>'
			. '<h2>Rekap Pemenuhan Manpower Requisition (MPR) - Ratu Pertiwi Group</h2>'
			. '<p>Dicetak pada: ' . date('d/m/Y H:i') . '</p>'
			. $html . '</body></html>'
		);
	}

	/**
	 * Export Ringkasan Eksekutif & KPI Rekrutmen ke Excel (.xls)
	 */
	public function export_summary()
	{
		if ( ! has_permission('EXPORT')) {
			show_error('Akses ditolak: Anda tidak memiliki hak akses untuk ekspor data.', 403, '403 Forbidden');
		}

		$dept = current_user_dept();
		$f = array(
			'dari'       => $this->input->get('dari') ?: NULL,
			'sampai'     => $this->input->get('sampai') ?: NULL,
			'dept'       => $dept !== NULL ? $dept : ($this->input->get('dept') ?: NULL),
			'posisi'     => $this->input->get('posisi') ?: NULL,
			'outlet'     => $this->input->get('outlet') ?: NULL,
			'status_mpr' => $this->input->get('status_mpr') ?: NULL,
		);

		$kpi          = $this->report_model->get_kpi_summary($f);
		$top_positions= $this->report_model->get_top_positions($f, 15);
		$dept_summary = $this->report_model->get_department_summary($f);
		$funnel       = $this->report_model->get_funnel_stages($f);

		$fname = 'ringkasan_eksekutif_rekrutmen_' . date('Ymd_His') . '.xls';

		$this->output
			->set_content_type('application/vnd.ms-excel')
			->set_header('Content-Disposition: attachment; filename="' . $fname . '"')
			->set_header('Cache-Control: no-store');

		$html = '<h2>RINGKASAN EKSEKUTIF & KPI REKRUTMEN - RATU PERTIWI GROUP</h2>';
		$html .= '<p>Tanggal Cetak: ' . date('d/m/Y H:i') . '</p>';

		// Tabel KPI Utama
		$html .= '<h3>1. Key Performance Indicator (KPI) Utama</h3>';
		$html .= '<table border="1" cellpadding="5" cellspacing="0">';
		$html .= '<tr style="background:#e8ede9; font-weight:bold;"><th>Indikator</th><th>Nilai</th><th>Keterangan</th></tr>';
		$html .= '<tr><td>Total Berkas Pelamar Masuk</td><td style="text-align:right"><b>' . (int)($kpi['total_pelamar'] ?? 0) . '</b></td><td>Semua lamaran yang masuk</td></tr>';
		$html .= '<tr><td>Kandidat Dalam Proses (Pipeline)</td><td style="text-align:right"><b>' . (int)($kpi['n_in_progress'] ?? 0) . '</b></td><td>Sedang aktif di tahapan seleksi</td></tr>';
		$html .= '<tr><td>Total Berhasil Diterima (Hired)</td><td style="text-align:right"><b>' . (int)($kpi['n_hired'] ?? 0) . '</b></td><td>Kandidat lolos dan bergabung</td></tr>';
		$html .= '<tr><td>Tidak Lolos / Ditolak</td><td style="text-align:right">' . (int)($kpi['n_rejected'] ?? 0) . '</td><td>Hasil evaluasi seleksi</td></tr>';
		$html .= '<tr><td>Mengundurkan Diri / Batal</td><td style="text-align:right">' . (int)($kpi['n_withdrawn'] ?? 0) . '</td><td>Withdrawn, Offer Declined, No Show</td></tr>';
		$avg_days = isset($kpi['avg_time_to_hire_days']) && $kpi['avg_time_to_hire_days'] !== NULL ? round($kpi['avg_time_to_hire_days'], 1) : '-';
		$html .= '<tr><td>Rata-rata Durasi Proses (Time to Hire)</td><td style="text-align:right"><b>' . $avg_days . ' hari</b></td><td>Dari tanggal daftar sampai diterima</td></tr>';
		$html .= '<tr><td>Total Dokumen MPR</td><td style="text-align:right">' . (int)($kpi['total_mpr'] ?? 0) . '</td><td>Permintaan tenaga kerja</td></tr>';
		$html .= '<tr><td>Total Target Formasi (Orang)</td><td style="text-align:right">' . (int)($kpi['total_target_orang'] ?? 0) . '</td><td>Kebutuhan personil</td></tr>';
		$html .= '<tr><td>Total Formasi Terpenuhi (Orang)</td><td style="text-align:right"><b>' . (int)($kpi['total_terpenuhi_orang'] ?? 0) . '</b></td><td>Personil yang sudah terisi</td></tr>';
		$html .= '</table><br>';

		// Tabel Funnel Tahapan
		$html .= '<h3>2. Funnel Konversi Tahapan Seleksi</h3>';
		$html .= '<table border="1" cellpadding="5" cellspacing="0">';
		$html .= '<tr style="background:#e8ede9; font-weight:bold;"><th>Tahapan Seleksi</th><th>Jumlah Kandidat</th></tr>';
		foreach ($funnel as $fn) {
			$html .= '<tr><td>' . html_escape($fn['tipe']) . '</td><td style="text-align:right">' . (int) $fn['jumlah'] . '</td></tr>';
		}
		$html .= '</table><br>';

		// Tabel Rekap Departemen
		$html .= '<h3>3. Rekap Kebutuhan & Pemenuhan per Departemen</h3>';
		$html .= '<table border="1" cellpadding="5" cellspacing="0">';
		$html .= '<tr style="background:#e8ede9; font-weight:bold;"><th>Departemen</th><th>Total MPR</th><th>Target Orang</th><th>Terpenuhi</th><th>% Terpenuhi</th><th>Total Pelamar</th><th>Hired</th></tr>';
		foreach ($dept_summary as $ds) {
			$html .= '<tr>';
			$html .= '<td>' . html_escape($ds['nama_departemen'] ?: 'Umum') . '</td>';
			$html .= '<td style="text-align:right">' . (int) $ds['total_mpr'] . '</td>';
			$html .= '<td style="text-align:right">' . (int) $ds['total_target'] . '</td>';
			$html .= '<td style="text-align:right; font-weight:bold">' . (int) $ds['total_terpenuhi'] . '</td>';
			$html .= '<td style="text-align:right">' . (int) $ds['persen'] . '%</td>';
			$html .= '<td style="text-align:right">' . (int) $ds['total_pelamar'] . '</td>';
			$html .= '<td style="text-align:right; font-weight:bold">' . (int) $ds['total_hired'] . '</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';

		$this->output->set_output(
			'<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>'
			. $html . '</body></html>'
		);
	}
}
