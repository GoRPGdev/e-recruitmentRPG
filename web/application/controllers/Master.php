<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Master -- Manajemen Data Induk Master Referensi Sistem
 *
 * Fungsi:
 * - Mengelola data master posisi (lengkap dengan job description, kualifikasi standar, pendidikan min, & level organisasi).
 * - Mengelola data master departemen, level organisasi (dengan fitur drag-and-drop urutan), outlet gerai, dan jenis dokumen.
 * - Mengelola master remark hasil evaluasi seleksi dan efek status seleksi kandidat.
 * - Menerapkan aturan integritas soft delete (is_aktif = 0) tanpa pernah menghapus data fisik (hard delete).
 * - Proteksi akses: membutuhkan permission 'KELOLA_REKRUTMEN'.
 */
class Master extends Secured_Controller
{
	private $types = array(
		'posisi'           => array('label' => 'Posisi',           'pk' => 'id_posisi',           'desc' => 'Daftar jabatan, departemen induk, level organisasi, dan flow seleksi default.'),
		'departemen'       => array('label' => 'Departemen',       'pk' => 'id_departemen',       'tabel' => 'M_DEPARTEMEN',       'desc' => 'Unit divisi dan departemen operasional serta back-office RPG.'),
		'level_organisasi' => array('label' => 'Level Organisasi', 'pk' => 'id_level_organisasi', 'tabel' => 'M_LEVEL_ORGANISASI', 'desc' => 'Tingkat jabatan dan struktur jenjang hierarki organisasi RPG.'),
		'outlet'           => array('label' => 'Outlet',           'pk' => 'id_outlet',           'tabel' => 'M_OUTLET',           'desc' => 'Titik cabang, outlet gerai, unit brand, dan wilayah region penempatan.'),
		'tahap'            => array('label' => 'Tahap Seleksi',    'pk' => 'id_stage',            'tabel' => 'M_STAGE',            'desc' => 'Katalog master tahapan seleksi rekrutmen RPG dan konfigurasi izin sisipan ad-hoc.'),
		'dokumen'          => array('label' => 'Dokumen',          'pk' => 'id_dokumen',          'tabel' => 'M_DOKUMEN',          'desc' => 'Katalog berkas persyaratan pelamar, kategori, dan tingkat sensitivitas PDP.', 'hidden' => TRUE),
		'remark'           => array('label' => 'Remark Alur',      'pk' => 'id_remark',           'tabel' => 'M_REMARKS',          'desc' => 'Daftar keputusan/alasan mutasi kandidat pada setiap tahap alur seleksi.'),
	);

	public function __construct()
	{
		parent::__construct();
		$this->require_permission('KELOLA_REKRUTMEN');
		$this->load->model(array('master_model', 'requisition_model'));
		$this->load->helper(array('form', 'url'));
	}

	public function index($t = 'posisi')
	{
		if ( ! isset($this->types[$t])) {
			show_404();
		}
		$data = array(
			'title'    => 'Master Data — ' . $this->types[$t]['label'],
			'_content' => 'master/index',
			'wide'     => TRUE,
			't'        => $t,
			'types'    => $this->types,
			'counts'   => array(
				'posisi'           => (int) $this->db->query("SELECT COUNT(*) AS n FROM dbo.M_POSISI WHERE is_aktif = 1")->row()->n,
				'departemen'       => (int) $this->db->query("SELECT COUNT(*) AS n FROM dbo.M_DEPARTEMEN WHERE is_aktif = 1")->row()->n,
				'level_organisasi' => (int) $this->db->query("SELECT COUNT(*) AS n FROM dbo.M_LEVEL_ORGANISASI WHERE is_aktif = 1")->row()->n,
				'outlet'           => (int) $this->db->query("SELECT COUNT(*) AS n FROM dbo.M_OUTLET WHERE is_aktif = 1")->row()->n,
				'tahap'            => (int) $this->db->query("SELECT COUNT(*) AS n FROM dbo.M_STAGE WHERE is_aktif = 1")->row()->n,
				'dokumen'          => (int) $this->db->query("SELECT COUNT(*) AS n FROM dbo.M_DOKUMEN WHERE is_aktif = 1")->row()->n,
				'remark'           => (int) $this->db->query("SELECT COUNT(*) AS n FROM dbo.M_REMARKS WHERE is_aktif = 1")->row()->n,
			),
		);

		$edit_id = (int) $this->input->get('edit_id');
		$data['edit_row'] = NULL;

		switch ($t) {
			case 'posisi':
				$data['rows']   = $this->master_model->list_posisi();
				$data['depts']  = $this->master_model->list_departemen();
				$data['flows']  = $this->master_model->list_flow();
				$data['levels'] = $this->master_model->list_level_organisasi(TRUE);
				if ($edit_id) {
					$q = $this->db->query('SELECT * FROM dbo.M_POSISI WHERE id_posisi = ?', array($edit_id));
					$data['edit_row'] = $q->row_array() ?: NULL;
				}
				break;

			case 'departemen':
				$data['rows'] = $this->master_model->list_departemen();
				if ($edit_id) {
					$q = $this->db->query('SELECT * FROM dbo.M_DEPARTEMEN WHERE id_departemen = ?', array($edit_id));
					$data['edit_row'] = $q->row_array() ?: NULL;
				}
				break;

			case 'level_organisasi':
				$data['rows'] = $this->master_model->list_level_organisasi();
				if ($edit_id) {
					$q = $this->db->query('SELECT * FROM dbo.M_LEVEL_ORGANISASI WHERE id_level_organisasi = ?', array($edit_id));
					$data['edit_row'] = $q->row_array() ?: NULL;
				}
				break;

			case 'outlet':
				$data['rows'] = $this->master_model->list_outlet();
				if ($edit_id) {
					$q = $this->db->query('SELECT * FROM dbo.M_OUTLET WHERE id_outlet = ?', array($edit_id));
					$data['edit_row'] = $q->row_array() ?: NULL;
				}
				break;

			case 'tahap':
				$data['rows'] = $this->master_model->list_stage();
				$data['tipe_tahap_list'] = array('SCREENING','KONTAK','FORM','TEST','INTERVIEW','OFFER','ONBOARD');
				if ($edit_id) {
					$data['edit_row'] = $this->master_model->get_stage($edit_id);
				}
				break;

			case 'dokumen':
				$data['rows'] = $this->master_model->list_dokumen();
				if ($edit_id) {
					$q = $this->db->query('SELECT * FROM dbo.M_DOKUMEN WHERE id_dokumen = ?', array($edit_id));
					$data['edit_row'] = $q->row_array() ?: NULL;
				}
				break;

			case 'remark':
				$data['rows']       = $this->master_model->remarks();
				$data['all_stages'] = $this->master_model->all_stages();
				$efek_aktif         = $this->master_model->list_efek_status(TRUE);
				$data['efek']       = ! empty($efek_aktif) ? array_column($efek_aktif, 'kode_efek') : array('LANJUT','HIRED','TOLAK');
				if ($edit_id) {
					$q = $this->db->query('SELECT * FROM dbo.M_REMARKS WHERE id_remark = ?', array($edit_id));
					$data['edit_row'] = $q->row_array() ?: NULL;
				}
				break;
		}

		$this->load->view('layouts/main', $data);
	}

	public function save($t = NULL)
	{
		if ( ! isset($this->types[$t]) || $this->input->method() !== 'post') {
			show_404();
		}
		$p = $this->input->post(NULL, TRUE);
		try {
			switch ($t) {
				case 'posisi':           $this->master_model->save_posisi($p, $this->auth_user['id_user']); break;
				case 'departemen':       $this->master_model->save_departemen($p, $this->auth_user['id_user']); break;
				case 'level_organisasi': $this->master_model->save_level_organisasi($p); break;
				case 'outlet':           $this->master_model->save_outlet($p); break;
				case 'tahap':            $this->master_model->save_stage($p, $this->auth_user['id_user']); break;
				case 'dokumen':          $this->master_model->save_dokumen($p); break;
				case 'remark':           $this->master_model->save_remark($p, $this->auth_user['id_user']); break;
			}
			$this->session->set_flashdata('ok', 'Data ' . $this->types[$t]['label'] . ' berhasil disimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('master/index/' . $t);
	}

	public function toggle($t = NULL)
	{
		if ( ! isset($this->types[$t]) || $this->input->method() !== 'post') {
			show_404();
		}
		$id  = (int) $this->input->post('id');
		$akt = (int) $this->input->post('is_aktif');
		try {
			if ($t === 'posisi') {
				$this->master_model->toggle_posisi($id, $akt, $this->auth_user['id_user']);
			} elseif ($t === 'departemen') {
				$this->master_model->toggle_departemen($id, $akt, $this->auth_user['id_user']);
			} elseif ($t === 'remark') {
				$this->master_model->toggle_remark($id, $akt, $this->auth_user['id_user']);
			} elseif ($t === 'level_organisasi') {
				$this->master_model->toggle_level_organisasi($id, $akt);
			} elseif ($t === 'outlet') {
				$this->master_model->toggle_outlet($id, $akt);
			} elseif ($t === 'tahap') {
				$this->master_model->toggle_stage($id, $akt, $this->auth_user['id_user']);
			} elseif ($t === 'dokumen') {
				$this->master_model->toggle_dokumen($id, $akt);
			}
			$this->session->set_flashdata('ok', $akt ? 'Data berhasil diaktifkan kembali.' : 'Data berhasil dinonaktifkan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('master/index/' . $t);
	}

	/**
	 * Endpoint AJAX untuk reorder urutan Level Organisasi (Drag and Drop)
	 */
	public function reorder_level_organisasi()
	{
		if ($this->input->method() !== 'post') {
			show_404();
		}

		$order = $this->input->post('order');
		if ( ! is_array($order) || empty($order)) {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(array('success' => FALSE, 'error' => 'Data urutan kosong.')));
		}

		try {
			$this->master_model->reorder_level_organisasi($order);
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(array('success' => TRUE)));
		} catch (RuntimeException $e) {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(array('success' => FALSE, 'error' => $e->getMessage())));
		}
	}
}
