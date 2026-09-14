<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Export -- Ekspor Data Rekrutmen ke Format Spreadsheet (Excel/CSV)
 *
 * Fungsi:
 * - Menangani ekspor data kandidat dan lamaran ke format file Excel (.xls) tanpa dependensi library berat pihak ketiga.
 * - Memformat nomor kontak WA dan NIK agar tidak berubah menjadi notasi ilmiah pada Microsoft Excel.
 * - Menerapkan audit logging ACCESS_LOG_SENSITIF ketika kolom data sensitif ikut diekspor.
 * - Proteksi akses: membutuhkan permission 'EXPORT'.
 */
class Export extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('EXPORT');
		$this->load->model('dashboard_model', 'dm');
		$this->load->helper('url');
	}

	public function candidates()
	{
		$f = array(
			'dari'   => $this->input->get('dari') ?: NULL,
			'sampai' => $this->input->get('sampai') ?: NULL,
			'posisi' => $this->input->get('posisi') ?: NULL,
			'id_req' => $this->input->get('id_req') ?: NULL,
			'status' => $this->input->get('status') ?: NULL,
			'flow'   => $this->input->get('flow') ?: NULL,
		);
		$perms = (array) $this->session->userdata('permissions');

		// export yang membawa kolom sensitif -> catat ke ACCESS_LOG_SENSITIF (1 baris / export)
		if (in_array('LIHAT_GAJI_PELAMAR', $perms, TRUE)) { log_akses_sensitif('GAJI_PELAMAR'); }
		if (in_array('LIHAT_FINANSIAL', $perms, TRUE))    { log_akses_sensitif('FINANSIAL'); }

		$rows  = $this->dm->candidates_export($f, $perms);

		$fname = 'kandidat_' . date('Ymd_His') . '.xls';
		$this->output
			->set_content_type('application/vnd.ms-excel')
			->set_header('Content-Disposition: attachment; filename="' . $fname . '"')
			->set_header('Cache-Control: no-store');

		if ( ! $rows) {
			$this->output->set_output('<table><tr><td>Tidak ada data.</td></tr></table>');
			return;
		}

		$cols = array_keys($rows[0]);
		$html = '<table border="1"><tr>';
		foreach ($cols as $c) {
			$html .= '<th>' . html_escape($c) . '</th>';
		}
		$html .= '</tr>';
		foreach ($rows as $r) {
			$html .= '<tr>';
			foreach ($cols as $c) {
				$v = $r[$c];
				if ($v instanceof DateTime) { $v = $v->format('Y-m-d'); }
				// paksa teks supaya nomor WA / rekening tidak jadi notasi ilmiah
				$html .= '<td style="mso-number-format:\'\@\'">' . html_escape((string) $v) . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>';

		$this->output->set_output(
			'<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>'
			. $html . '</body></html>'
		);
	}
}
