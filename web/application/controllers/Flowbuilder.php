<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Flowbuilder -- Konfigurasi Alur Seleksi & Tahapan Rekrutmen RPG
 *
 * Fungsi:
 * - Mengelola susunan urutan tahapan alur seleksi standar RPG dengan antarmuka drag-and-drop.
 * - Mengatur remark keputusan dan efek status seleksi pada tiap tahapan.
 * - Menentukan pengaturan dokumen wajib yang harus diunggah pelamar pada tahapan tertentu.
 * - Menetapkan batas upaya kontak dan hak izin sisipan ad-hoc tahapan.
 * - Proteksi akses: membutuhkan permission 'EDIT_FLOW_TEMPLATE'.
 */
class Flowbuilder extends Secured_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_permission('EDIT_FLOW_TEMPLATE');
		$this->load->model('master_model', 'mm');
		$this->load->model('document_model');
		$this->load->helper(array('form', 'url'));
	}

	/**
	 * Helper untuk mendapatkan 1 Flow Standar Baku RPG
	 */
	private function _get_standard_flow()
	{
		$flows = $this->mm->list_flow();
		$std_flow = NULL;
		foreach ($flows as $f) {
			if ($f['kode_flow'] === 'HQ_STAFF' && $f['is_aktif']) {
				$std_flow = $f;
				break;
			}
		}
		if ( ! $std_flow) {
			foreach ($flows as $f) {
				if ($f['is_aktif']) {
					$std_flow = $f;
					break;
				}
			}
		}
		if ( ! $std_flow && ! empty($flows)) {
			$std_flow = $flows[0];
		}
		return $std_flow;
	}

	/**
	 * Halaman Utama: Editor 1 Alur Seleksi Standar RPG
	 */
	public function index()
	{
		$flow = $this->_get_standard_flow();
		if ( ! $flow) {
			show_error('Belum ada data flow seleksi yang terkonfigurasi di database.', 500);
		}

		$id_flow = (int) $flow['id_flow'];
		$stages = $this->mm->flow_stages($id_flow);
		$all_stages_raw = $this->mm->all_stages();

		// Saring tahap: tahap yang sudah ada di flow tidak boleh muncul lagi di dropdown "+ Tambah Tahap"
		$existing_stage_ids = array_column($stages, 'id_stage');
		$all_stages = array_values(array_filter($all_stages_raw, function($st) use ($existing_stage_ids) {
			return !in_array((int) $st['id_stage'], $existing_stage_ids);
		}));

		$roles = $this->mm->all_roles();
		$flow_docs_raw = $this->document_model->flow_required_docs($id_flow);
		$all_docs = $this->mm->list_dokumen();

		// Pemetaan dokumen per id_flow_stage
		$docs_by_stage = array();
		foreach ($flow_docs_raw as $fd) {
			$fs_id = (int) $fd['id_flow_stage'];
			if ( ! isset($docs_by_stage[$fs_id])) {
				$docs_by_stage[$fs_id] = array();
			}
			if ($fd['id_dokumen']) {
				$docs_by_stage[$fs_id][] = array(
					'id_dokumen'   => (int) $fd['id_dokumen'],
					'nama_dokumen' => $fd['nama_dokumen'],
					'is_wajib'     => (bool) $fd['is_wajib'],
				);
			}
		}

		$this->load->view('layouts/main', array(
			'title'         => 'Alur Seleksi Rekrutmen (1 Flow Standar)',
			'_content'      => 'flow/index',
			'wide'          => TRUE,
			'flow'          => $flow,
			'stages'        => $stages,
			'all_stages'    => $all_stages,
			'roles'         => $roles,
			'docs_by_stage' => $docs_by_stage,
			'all_docs'      => $all_docs,
		));
	}

	/**
	 * Redirect edit/{id} ke halaman utama alur tunggal
	 */
	public function edit($id_flow = NULL)
	{
		redirect('flowbuilder');
	}

	/**
	 * Simpan konfigurasi header alur standar RPG
	 */
	public function save_header()
	{
		if ($this->input->method() !== 'post') { show_404(); }
		$uid = (int) ($this->auth_user['id_user'] ?? 0);
		try {
			$this->mm->save_flow($this->input->post(NULL, TRUE), $uid);
			$this->session->set_flashdata('ok', 'Konfigurasi Alur Rekrutmen Standar RPG berhasil disimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('flowbuilder');
	}

	/**
	 * Aksi mutasi tahapan alur (ADD, UPDATE, MOVE, REMOVE)
	 */
	public function stage_action($id_flow = NULL)
	{
		if ($this->input->method() !== 'post') { show_404(); }
		if ( ! $id_flow) {
			$flow = $this->_get_standard_flow();
			$id_flow = $flow ? $flow['id_flow'] : NULL;
		}
		if ( ! $id_flow) { show_404(); }

		$uid = (int) ($this->auth_user['id_user'] ?? 0);
		$in = $this->input->post(NULL, TRUE);
		$in['id_flow'] = (int) $id_flow;
		$aksi = $in['aksi'] ?? '';

		try {
			$this->mm->flow_stage_action($in, $uid);
			if ($aksi === 'ADD') {
				$this->session->set_flashdata('ok', 'Tahap baru berhasil ditambahkan ke alur seleksi (versi naik).');
			} elseif ($aksi === 'MOVE') {
				$this->session->set_flashdata('ok', 'Urutan tahap seleksi berhasil diperbarui (versi naik).');
			} elseif ($aksi === 'REMOVE') {
				$this->session->set_flashdata('ok', 'Tahap seleksi berhasil dihapus dari alur (versi naik).');
			} else {
				$this->session->set_flashdata('ok', 'Perubahan tahap alur berhasil disimpan (versi naik).');
			}
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('flowbuilder');
	}

	/**
	 * Endpoint AJAX untuk reorder urutan tahapan alur (Drag and Drop)
	 */
	public function reorder($id_flow = NULL)
	{
		if ($this->input->method() !== 'post') { show_404(); }
		if ( ! $id_flow) {
			$flow = $this->_get_standard_flow();
			$id_flow = $flow ? $flow['id_flow'] : NULL;
		}
		if ( ! $id_flow) {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(array('success' => FALSE, 'error' => 'Flow tidak ditemukan.')));
		}

		$order = $this->input->post('order');
		if ( ! is_array($order) || empty($order)) {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(array('success' => FALSE, 'error' => 'Data urutan kosong.')));
		}

		$uid = (int) ($this->auth_user['id_user'] ?? 0);
		try {
			$this->mm->reorder_flow_stages((int) $id_flow, $order, $uid);
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(array('success' => TRUE)));
		} catch (RuntimeException $e) {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(array('success' => FALSE, 'error' => $e->getMessage())));
		}
	}

	/**
	 * Clone flow dinonaktifkan karena perusahaan menggunakan 1 flow tunggal
	 */
	public function clone_flow()
	{
		$this->session->set_flashdata('error', 'Sistem rekrutmen RPG menggunakan 1 alur standar tunggal. Fitur clone template dinonaktifkan.');
		redirect('flowbuilder');
	}

	public function toggle($id_flow = NULL)
	{
		redirect('flowbuilder');
	}

	/* ---- Master Tahap Seleksi (M_STAGE) ---- */

	public function stages()
	{
		$edit_id = (int) $this->input->get('edit');
		$this->load->view('layouts/main', array(
			'title'      => 'Katalog Tahap Seleksi',
			'_content'   => 'flow/stages',
			'wide'       => TRUE,
			'rows'       => $this->mm->list_stage(),
			'tipe_tahap' => array('SCREENING','KONTAK','FORM','TEST','INTERVIEW','OFFER','ONBOARD'),
			'edit_row'   => $edit_id ? $this->mm->get_stage($edit_id) : NULL,
		));
	}

	public function save_stage()
	{
		if ($this->input->method() !== 'post') { show_404(); }
		$uid = (int) ($this->auth_user['id_user'] ?? 0);
		try {
			$this->mm->save_stage($this->input->post(NULL, TRUE), $uid);
			$this->session->set_flashdata('ok', 'Tahap seleksi berhasil disimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('flowbuilder/stages');
	}

	public function toggle_stage()
	{
		if ($this->input->method() !== 'post') { show_404(); }
		$uid = (int) ($this->auth_user['id_user'] ?? 0);
		try {
			$this->mm->toggle_stage((int) $this->input->post('id'), (int) $this->input->post('is_aktif'), $uid);
			$this->session->set_flashdata('ok', $this->input->post('is_aktif') ? 'Tahap diaktifkan.' : 'Tahap dinonaktifkan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('flowbuilder/stages');
	}

	public function toggle_stage_sisipan()
	{
		if ($this->input->method() !== 'post') { show_404(); }
		$uid = (int) ($this->auth_user['id_user'] ?? 0);
		$id = (int) $this->input->post('id');
		$val = (int) $this->input->post('is_sisipan_allowed');
		try {
			$this->mm->toggle_stage_sisipan($id, $val, $uid);
			$this->session->set_flashdata('ok', $val ? 'Tahap diizinkan sebagai tahap sisipan.' : 'Tahap dikeluarkan dari daftar tahap sisipan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('flowbuilder/stages');
	}

	/* ---- Dokumen Wajib Per Tahap (M_FLOW_STAGE_DOKUMEN) ---- */

	public function flow_docs($id_flow = NULL)
	{
		if ( ! $id_flow) {
			$flow = $this->_get_standard_flow();
			$id_flow = $flow ? $flow['id_flow'] : NULL;
		}
		$flows = $this->mm->list_flow();

		$this->load->view('layouts/main', array(
			'title'    => 'Dokumen Wajib Per Tahap',
			'_content' => 'flow/docs',
			'wide'     => TRUE,
			'flows'    => $flows,
			'id_flow'  => (int) $id_flow,
			'rows'     => $id_flow ? $this->document_model->flow_required_docs($id_flow) : array(),
			'dokumen'  => $this->mm->list_dokumen(),
		));
	}

	public function set_flow_doc($id_flow = NULL)
	{
		if ($this->input->method() !== 'post') { show_404(); }
		if ( ! $id_flow) {
			$flow = $this->_get_standard_flow();
			$id_flow = $flow ? $flow['id_flow'] : NULL;
		}
		if ( ! $id_flow) { show_404(); }

		$this->document_model->set_flow_doc(
			(int) $this->input->post('id_flow_stage'),
			(int) $this->input->post('id_dokumen'),
			$this->input->post('wajib')   // '1' | '0' | 'remove'
		);
		$this->session->set_flashdata('ok', 'Konfigurasi dokumen tahap berhasil diperbarui.');

		$back = $this->input->post('return_to');
		if ($back === 'flowbuilder') {
			redirect('flowbuilder');
		}
		redirect('flowbuilder/flow_docs/' . (int) $id_flow);
	}

	/* ---- Remarks / Keputusan Seleksi ---- */

	public function remarks($id_stage = NULL)
	{
		$edit_id  = (int) $this->input->get('edit_remark');
		$edit_row = NULL;
		if ($edit_id) {
			$q = $this->db->query('SELECT * FROM dbo.M_REMARKS WHERE id_remark = ?', array($edit_id));
			$edit_row = $q->row_array() ?: NULL;
		}

		$this->load->view('layouts/main', array(
			'title'       => 'Remark Keputusan',
			'_content'    => 'flow/remarks',
			'wide'        => TRUE,
			'id_stage'    => $id_stage ? (int) $id_stage : NULL,
			'rows'        => $this->mm->remarks($id_stage ? (int) $id_stage : NULL),
			'all_stages'  => $this->mm->all_stages(),
			'efek'        => array('LANJUT','HIRED','TOLAK'),
			'edit_remark' => $edit_row,
		));
	}

	public function save_remark()
	{
		if ($this->input->method() !== 'post') { show_404(); }
		$uid = (int) ($this->auth_user['id_user'] ?? 0);
		try {
			$this->mm->save_remark($this->input->post(NULL, TRUE), $uid);
			$this->session->set_flashdata('ok', 'Remark keputusan berhasil disimpan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		redirect('flowbuilder/remarks' . ($this->input->post('id_stage') ? '/' . (int) $this->input->post('id_stage') : ''));
	}

	public function toggle_remark($id_remark)
	{
		if ($this->input->method() !== 'post') { show_404(); }
		$uid = (int) ($this->auth_user['id_user'] ?? 0);
		$akt = (int) $this->input->post('is_aktif');
		try {
			$this->mm->toggle_remark((int) $id_remark, $akt, $uid);
			$this->session->set_flashdata('ok', $akt ? 'Remark diaktifkan.' : 'Remark dinonaktifkan.');
		} catch (RuntimeException $e) {
			$this->session->set_flashdata('error', $e->getMessage());
		}
		$stg = $this->input->post('id_stage');
		redirect('flowbuilder/remarks' . ($stg ? '/' . (int) $stg : ''));
	}
}
