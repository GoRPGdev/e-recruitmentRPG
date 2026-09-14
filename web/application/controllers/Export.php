<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Export -- Ekspor Data Rekrutmen ke Format Spreadsheet (Excel/CSV)
 *
 * Fungsi:
 * - Menangani ekspor data kandidat dan lamaran ke format file Excel (.xls) dengan streaming chunk langsung ke output buffer.
 * - Mencegah Memory Limit Exhausted pada dataset puluhan ribu baris.
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
			'dept'   => $this->input->get('dept') ?: NULL,
		);
		$perms = (array) $this->session->userdata('permissions');

		// Export yang membawa kolom sensitif -> catat ke ACCESS_LOG_SENSITIF
		if (in_array('LIHAT_GAJI_PELAMAR', $perms, TRUE)) { log_akses_sensitif('GAJI_PELAMAR'); }
		if (in_array('LIHAT_FINANSIAL', $perms, TRUE))    { log_akses_sensitif('FINANSIAL'); }

		// Lepaskan session lock agar request lain tidak terblokir saat streaming data besar
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}

		$fname = 'kandidat_' . date('Ymd_His') . '.xls';

		// Kirim HTTP Header langsung
		header('Content-Type: application/vnd.ms-excel; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $fname . '"');
		header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
		header('Pragma: public');

		// Buka streaming output
		$out = fopen('php://output', 'w');

		// Excel HTML Header dengan deklarasi namespaces untuk format teks (mso-number-format)
		fputs($out, "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">\r\n");
		fputs($out, "<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\r\n");
		fputs($out, "<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Data Pelamar</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->\r\n");
		fputs($out, "<style>th{background:#f0f4f1;color:#1b211c;font-weight:bold;border:0.5pt solid #d0d7d2;} td{border:0.5pt solid #d0d7d2;}</style>\r\n");
		fputs($out, "</head><body>\r\n");
		fputs($out, "<table border=\"1\" cellpadding=\"4\" cellspacing=\"0\">\r\n");

		// Eksekusi kueri langsung via connection sqlsrv untuk fetch per baris tanpa alokasi array raksasa
		$cursor = $this->dm->candidates_export_cursor($f, $perms);
		$first  = TRUE;
		$count  = 0;

		if ($cursor) {
			while ($r = sqlsrv_fetch_array($cursor, SQLSRV_FETCH_ASSOC)) {
				$count++;
				if ($first) {
					fputs($out, "<tr>\r\n");
					foreach (array_keys($r) as $col) {
						$colLabel = strtoupper(str_replace('_', ' ', $col));
						fputs($out, "<th>" . htmlspecialchars($colLabel, ENT_QUOTES, 'UTF-8') . "</th>\r\n");
					}
					fputs($out, "</tr>\r\n");
					$first = FALSE;
				}

				fputs($out, "<tr>\r\n");
				foreach ($r as $val) {
					if ($val instanceof DateTime) {
						$val = $val->format('Y-m-d H:i');
					} elseif ($val === NULL) {
						$val = '';
					}
					// Gunakan style mso-number-format:'\@' agar WA/NIK tidak menjadi scientific notation
					fputs($out, "<td style=\"mso-number-format:'\\@'\">" . htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') . "</td>\r\n");
				}
				fputs($out, "</tr>\r\n");

				// Flush buffer setiap 150 baris untuk menjaga kestabilan memori
				if ($count % 150 === 0) {
					if (ob_get_level() > 0) {
						ob_flush();
					}
					flush();
				}
			}
			sqlsrv_free_stmt($cursor);
		}

		if ($first) {
			fputs($out, "<tr><td>Tidak ada data yang memenuhi kriteria filter.</td></tr>\r\n");
		}

		fputs($out, "</table>\r\n</body></html>\r\n");
		fclose($out);
		exit;
	}
}
