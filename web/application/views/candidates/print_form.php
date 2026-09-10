<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: candidates/print_form.php -- Template Cetak Dokumen Resmi Formulir Lamaran RPG (A4)
 *
 * Fungsi:
 * - Menghasilkan layout cetak / PDF formal A4 presisi tinggi untuk data pelamar lengkap (Section A s/d J).
 * - Dilengkapi proteksi data sensitif finansial & kesehatan sesuai hak akses (RBAC) dan kepatuhan UU PDP.
 * - Mengintegrasikan pas foto pelamar resmi dan lembar kuesioner komprehensif.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Aplikasi Calon Karyawan — <?= html_escape($c['nama_lengkap']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Pengaturan spesifik untuk PDF/Cetak */
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #e2e8f0; /* Latar abu-abu di layar untuk simulasi kertas */
            -webkit-print-color-adjust: exact; /* Memastikan warna tercetak di Chrome/Safari */
            print-color-adjust: exact;
        }
        .a4-container {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        /* Menghindari elemen terpotong antar halaman saat jadi PDF */
        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .no-print-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #1e293b;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .no-print-bar button {
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }

        @media print {
            body {
                background-color: white;
            }
            .no-print-bar {
                display: none !important;
            }
            .a4-container {
                margin: 0;
                padding: 0;
                box-shadow: none;
                width: auto;
                min-height: auto;
            }
        }

        /* Styling dasar tabel dokumen */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            font-size: 0.875rem; /* text-sm */
        }
        th, td {
            border: 1px solid #cbd5e1; /* border-slate-300 */
            padding: 0.5rem 0.75rem;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f8fafc; /* bg-slate-50 */
            font-weight: 600;
            color: #334155; /* text-slate-700 */
        }

        /* Heading Section Document */
        .section-title {
            background-color: #f1f5f9;
            border-left: 4px solid #1e293b;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #0f172a;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="text-slate-900 antialiased">

    <!-- Bilah Navigasi Layar (Tidak Muncul Saat Dicetak / Disimpan ke PDF) -->
    <div class="no-print-bar">
        <div class="flex items-center gap-2">
            <span class="font-bold text-slate-100">RATU PERTIWI GROUP</span>
            <span class="text-slate-400">&middot;</span>
            <span class="text-sm text-slate-300">Pratinjau Dokumen Cetak / Export PDF Resmi</span>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white border border-blue-500 shadow-sm">
                Cetak / Simpan PDF
            </button>
            <button type="button" onclick="window.close()" class="bg-slate-700 hover:bg-slate-600 text-slate-200 border border-slate-600">
                Tutup
            </button>
        </div>
    </div>

    <!-- Area Kertas A4 -->
    <div class="a4-container" style="margin-top: 60px;">

        <!-- Header Dokumen / Kop -->
        <div class="border-b-2 border-slate-800 pb-4 mb-6 avoid-break">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">RATU PERTIWI GROUP</h1>
                    <p class="text-sm font-medium text-slate-600">Dokumen Pribadi &amp; Rahasia &mdash; e-Recruitment Human Resource Department</p>
                    <h2 class="text-lg font-bold mt-2">FORMULIR APLIKASI CALON KARYAWAN</h2>
                </div>
                <div class="text-right border border-slate-300 p-3 bg-slate-50 rounded">
                    <p class="text-xs text-slate-500 font-semibold mb-1 uppercase">Posisi Dilamar:</p>
                    <p class="text-base font-bold text-slate-900"><?= strtoupper(html_escape($c['nama_posisi'])) ?></p>
                    <div class="mt-2 text-xs text-slate-600">
                        <p>No. Ref: <?= html_escape($c['no_mpr'] ?: '#' . $c['id_req']) ?></p>
                        <p>Tanggal: <?= date('d/m/Y') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= SECTION A: IDENTITAS PRIBADI ================= -->
        <div class="avoid-break">
            <h3 class="section-title">A. IDENTITAS PRIBADI</h3>
            <div class="flex gap-6">
                <!-- Data Kiri -->
                <div class="flex-grow">
                    <table class="!mb-0 border-none">
                        <tbody>
                            <tr>
                                <td class="w-1/3 border-none !p-1 text-slate-600">Nama Lengkap</td>
                                <td class="w-2/3 border-none !p-1 font-semibold">: <?= html_escape($c['nama_lengkap']) ?> <?= !empty($c['nama_panggilan']) ? '(' . html_escape($c['nama_panggilan']) . ')' : '' ?></td>
                            </tr>
                            <?php
                                $tgl_lahir_str = '-';
                                $usia_str = '';
                                if (!empty($c['tanggal_lahir'])) {
                                    if ($c['tanggal_lahir'] instanceof DateTime) {
                                        $tgl_lahir_str = $c['tanggal_lahir']->format('Y-m-d');
                                        $thn_lahir = (int) $c['tanggal_lahir']->format('Y');
                                    } else {
                                        $tgl_lahir_str = substr((string)$c['tanggal_lahir'], 0, 10);
                                        $thn_lahir = (int) substr((string)$c['tanggal_lahir'], 0, 4);
                                    }
                                    if ($thn_lahir > 1900) {
                                        $usia_str = ' / ' . ((int)date('Y') - $thn_lahir) . ' Tahun';
                                    }
                                }
                            ?>
                            <tr>
                                <td class="border-none !p-1 text-slate-600">Tempat, Tgl Lahir</td>
                                <td class="border-none !p-1 font-semibold">: <?= html_escape($c['tempat_lahir'] ?: '-') ?>, <?= html_escape($tgl_lahir_str) ?></td>
                            </tr>
                            <tr>
                                <td class="border-none !p-1 text-slate-600">Jenis Kelamin / Usia</td>
                                <td class="border-none !p-1 font-semibold">: <?= $c['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($c['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?><?= html_escape($usia_str) ?></td>
                            </tr>
                            <tr>
                                <td class="border-none !p-1 text-slate-600">Agama / Gol. Darah</td>
                                <td class="border-none !p-1 font-semibold">: <?= html_escape($c['agama'] ?: '-') ?> / <?= html_escape($c['gol_darah'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td class="border-none !p-1 text-slate-600">Status Pernikahan</td>
                                <td class="border-none !p-1 font-semibold">: <?= html_escape($c['status_pernikahan'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td class="border-none !p-1 text-slate-600">Nomor KTP (NIK)</td>
                                <td class="border-none !p-1 font-semibold">: <?= html_escape($c['nik'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td class="border-none !p-1 text-slate-600">Nomor NPWP</td>
                                <td class="border-none !p-1 font-semibold">: <?= html_escape($c['npwp'] ?: '-') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Foto Pas Kanan -->
                <div class="w-32 h-40 border-2 border-slate-300 bg-slate-100 flex items-center justify-center text-slate-400 flex-shrink-0 overflow-hidden">
                    <?php if (!empty($c['foto_path'])): ?>
                        <img src="<?= site_url('candidates/photo/' . (int) $c['id_lamaran']) ?>" class="w-full h-full object-cover" alt="Pas Foto">
                    <?php else: ?>
                        <span class="text-xs text-center font-medium">FOTO<br>3 x 4</span>
                    <?php endif; ?>
                </div>
            </div>

            <table class="mt-4">
                <tbody>
                    <tr>
                        <td class="w-1/3 bg-slate-50 font-semibold text-slate-700">Alamat KTP / Lengkap</td>
                        <td>
                            <?= nl2br(html_escape($c['alamat_lengkap'] ?: '-')) ?>, Kota: <?= html_escape($c['kota_domisili'] ?: '-') ?><br>
                            <span class="text-xs text-slate-500">Status: <?= html_escape($c['status_tempat_tinggal'] ?: '-') ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="bg-slate-50 font-semibold text-slate-700">Kontak</td>
                        <td>
                            No. Telp / WA: <strong><?= html_escape($c['no_wa_normal'] ?: '-') ?></strong><br>
                            Email: <strong><?= html_escape($c['email'] ?: '-') ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="bg-slate-50 font-semibold text-slate-700">Kontak Darurat</td>
                        <td>
                            <strong><?= html_escape($c['kontak_darurat_nama'] ?: '-') ?></strong> (<?= html_escape($c['kontak_darurat_hub'] ?: '-') ?>) - Telp: <strong><?= html_escape($c['kontak_darurat_telp'] ?: '-') ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="bg-slate-50 font-semibold text-slate-700">Keahlian &amp; Bahasa</td>
                        <td>
                            Komputer: <?= html_escape($c['keahlian_komputer'] ?: '-') ?><br>
                            Bahasa: <?= html_escape($c['bahasa_asing'] ?: '-') ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ================= SECTION B: SUSUNAN KELUARGA ================= -->
        <?php
            $fam_sendiri = array();
            $fam_ortu    = array();
            $rel_sendiri = array('suami', 'istri', 'pasangan', 'anak');

            if (!empty($families)) {
                foreach ($families as $f) {
                    $hub = strtolower(trim((string)$f['hubungan']));
                    if (in_array($hub, $rel_sendiri, TRUE)) {
                        $fam_sendiri[] = $f;
                    } else {
                        $fam_ortu[] = $f;
                    }
                }
            }
        ?>
        <div class="avoid-break mt-6">
            <h3 class="section-title">B. SUSUNAN KELUARGA</h3>

            <!-- B.1 Susunan Keluarga Sendiri (Bagi yang sudah berkeluarga) -->
            <div class="mb-3">
                <div class="text-xs font-bold text-slate-700 mb-1">1. Susunan Keluarga Sendiri (Suami / Istri &amp; Anak) :</div>
                <table>
                    <thead>
                        <tr>
                            <th class="w-12 text-center">No</th>
                            <th>Hubungan</th>
                            <th>Nama Lengkap</th>
                            <th class="w-12 text-center">L/P</th>
                            <th class="w-16">Usia</th>
                            <th>Pendidikan</th>
                            <th>Pekerjaan</th>
                            <th>Nomor Telepon</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($fam_sendiri)): ?>
                            <?php foreach ($fam_sendiri as $idx => $fam): ?>
                                <tr>
                                    <td class="text-center"><?= $idx + 1 ?></td>
                                    <td><?= html_escape($fam['hubungan']) ?></td>
                                    <td class="font-medium"><?= html_escape($fam['nama_lengkap']) ?></td>
                                    <td class="text-center"><?= html_escape($fam['jenis_kelamin'] ?: '-') ?></td>
                                    <td><?= $fam['usia'] ? (int)$fam['usia'] . ' th' : '-' ?></td>
                                    <td><?= html_escape($fam['pendidikan'] ?: '-') ?></td>
                                    <td><?= html_escape($fam['pekerjaan'] ?: '-') ?></td>
                                    <td><?= html_escape($fam['no_telp'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-slate-500 italic">
                                    <?= ($c['status_pernikahan'] === 'Belum Menikah' || empty($c['status_pernikahan'])) ? 'Belum berkeluarga (Belum Menikah).' : 'Tidak ada data keluarga sendiri yang dicantumkan.' ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- B.2 Susunan Keluarga Orang Tua -->
            <div>
                <div class="text-xs font-bold text-slate-700 mb-1">2. Susunan Keluarga Orang Tua (Ayah, Ibu &amp; Saudara Kandung / Diri Sendiri) :</div>
                <table>
                    <thead>
                        <tr>
                            <th class="w-12 text-center">No</th>
                            <th>Hubungan</th>
                            <th>Nama Lengkap</th>
                            <th class="w-12 text-center">L/P</th>
                            <th class="w-16">Usia</th>
                            <th>Pendidikan</th>
                            <th>Pekerjaan</th>
                            <th>Nomor Telepon</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($fam_ortu)): ?>
                            <?php foreach ($fam_ortu as $idx => $fam): ?>
                                <tr>
                                    <td class="text-center"><?= $idx + 1 ?></td>
                                    <td><?= html_escape($fam['hubungan']) ?></td>
                                    <td class="font-medium"><?= html_escape($fam['nama_lengkap']) ?></td>
                                    <td class="text-center"><?= html_escape($fam['jenis_kelamin'] ?: '-') ?></td>
                                    <td><?= $fam['usia'] ? (int)$fam['usia'] . ' th' : '-' ?></td>
                                    <td><?= html_escape($fam['pendidikan'] ?: '-') ?></td>
                                    <td><?= html_escape($fam['pekerjaan'] ?: '-') ?></td>
                                    <td><?= html_escape($fam['no_telp'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-slate-500 italic">Tidak ada data keluarga orang tua yang dicantumkan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= SECTION C: RIWAYAT PENDIDIKAN FORMAL ================= -->
        <div class="avoid-break mt-6">
            <h3 class="section-title">C. RIWAYAT PENDIDIKAN FORMAL</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-24">Jenjang</th>
                        <th>Nama Institusi / Sekolah</th>
                        <th>Jurusan</th>
                        <th>Kota</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-semibold"><?= html_escape($c['pendidikan_terakhir'] ?: '-') ?></td>
                        <td><?= html_escape($c['nama_sekolah'] ?: '-') ?></td>
                        <td><?= html_escape($c['jurusan'] ?: '-') ?></td>
                        <td><?= html_escape($c['kota_domisili'] ?: '-') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ================= SECTION D: PENDIDIKAN NON-FORMAL / PELATIHAN / KURSUS ================= -->
        <div class="avoid-break mt-6">
            <h3 class="section-title">D. PENDIDIKAN NON-FORMAL / PELATIHAN / KURSUS</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-12 text-center">No</th>
                        <th>Nama Pelatihan / Kursus</th>
                        <th>Lembaga Penyelenggara</th>
                        <th>Tahun</th>
                        <th>Keterangan / Sertifikat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($trainings)): ?>
                        <?php foreach ($trainings as $idx => $tr): ?>
                            <tr>
                                <td class="text-center"><?= $idx + 1 ?></td>
                                <td class="font-medium"><?= html_escape($tr['nama_pelatihan']) ?></td>
                                <td><?= html_escape($tr['penyelenggara'] ?: '-') ?></td>
                                <td><?= html_escape($tr['tahun'] ?: '-') ?></td>
                                <td><?= html_escape($tr['keterangan'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-slate-500 italic">Tidak ada data pelatihan/kursus.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ================= SECTION E: RIWAYAT PENGALAMAN KERJA ================= -->
        <div class="avoid-break mt-6">
            <h3 class="section-title">E. RIWAYAT PENGALAMAN KERJA</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-10 text-center">No</th>
                        <th>Perusahaan &amp; Lokasi</th>
                        <th>Jabatan / Posisi</th>
                        <th>Periode</th>
                        <th>Gaji Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($experiences)): ?>
                        <?php foreach ($experiences as $idx => $exp): ?>
                            <tr>
                                <td class="text-center"><?= $idx + 1 ?></td>
                                <td>
                                    <strong><?= html_escape($exp['nama_perusahaan']) ?></strong>
                                </td>
                                <td><?= html_escape($exp['posisi_jabatan']) ?></td>
                                <td><?= html_escape($exp['periode_kerja'] ?: '-') ?></td>
                                <td>
                                    <?php if (can_sensitif('GAJI_PELAMAR')): ?>
                                        <?= $exp['gaji_terakhir'] !== NULL ? 'Rp ' . number_format((float)$exp['gaji_terakhir'], 0, ',', '.') : '-' ?>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">[Terproteksi]</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5" class="bg-slate-50">
                                    <div class="flex flex-col gap-2 text-xs">
                                        <div><span class="font-bold text-slate-700">Alasan Keluar:</span> <?= html_escape($exp['alasan_keluar'] ?: '-') ?></div>
                                        <div><span class="font-bold text-slate-700">Deskripsi Tugas &amp; Tanggung Jawab:</span> <?= nl2br(html_escape($exp['deskripsi_tugas'] ?: '-')) ?></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php elseif ($profile && !empty($profile['perusahaan_terakhir'])): ?>
                        <tr>
                            <td class="text-center">1</td>
                            <td><strong><?= html_escape($profile['perusahaan_terakhir']) ?></strong></td>
                            <td><?= html_escape($profile['jabatan_terakhir'] ?: '-') ?></td>
                            <td><?= html_escape($profile['periode_kerja'] ?: '-') ?></td>
                            <td>
                                <?php if (can_sensitif('GAJI_PELAMAR')): ?>
                                    <?= $profile['gaji_terakhir'] !== NULL ? 'Rp ' . number_format((float)$profile['gaji_terakhir'], 0, ',', '.') : '-' ?>
                                <?php else: ?>
                                    <span class="text-slate-400 italic">[Terproteksi]</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5" class="bg-slate-50">
                                <div class="flex flex-col gap-2 text-xs">
                                    <div><span class="font-bold text-slate-700">Alasan Keluar:</span> -</div>
                                    <div><span class="font-bold text-slate-700">Deskripsi Tugas &amp; Tanggung Jawab:</span> -</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-slate-500 italic">Tidak ada riwayat pengalaman kerja (Fresh Graduate).</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ================= SECTION F: KEADAAN KESEHATAN / FISIK ================= -->
        <div class="avoid-break mt-6">
            <h3 class="section-title">F. KEADAAN KESEHATAN / FISIK</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-10 text-center">No</th>
                        <th>Uraian Pemeriksaan / Kondisi Fisik</th>
                        <th class="w-24">Status</th>
                        <th>Keterangan / Penjelasan Lengkap</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">1</td>
                        <td>Apakah Anda menderita suatu penyakit atau cacat tubuh tertentu?</td>
                        <td class="font-semibold text-slate-700">
                            <?php if (can_sensitif('KESEHATAN')): ?>
                                <?= !empty($health['riwayat_penyakit']) ? 'Tercatat' : 'Tidak' ?>
                            <?php else: ?>
                                <span class="text-slate-400 italic">[Terproteksi]</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (can_sensitif('KESEHATAN')): ?>
                                <?= !empty($health['riwayat_penyakit']) ? nl2br(html_escape($health['riwayat_penyakit'])) : 'Tidak memiliki riwayat penyakit berat, rawat inap, ataupun alergi tertentu.' ?>
                            <?php else: ?>
                                <span class="text-slate-400 italic">[Terproteksi UU PDP 27/2022 - Butuh Izin LIHAT_KESEHATAN]</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-center">2</td>
                        <td>Kondisi Fisik: Tinggi &amp; Berat Badan / Golongan Darah</td>
                        <td class="font-semibold text-green-600">Normal</td>
                        <td>TB: <?= $c['tinggi_badan'] ? (int)$c['tinggi_badan'] . ' cm' : '-' ?> | BB: <?= $c['berat_badan'] ? (int)$c['berat_badan'] . ' kg' : '-' ?> | Gol. Darah: <?= html_escape($c['gol_darah'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">3</td>
                        <td>Persetujuan Pengolahan Data Kesehatan (Consent PDP)</td>
                        <td class="font-semibold text-blue-600"><?= !empty($health['consent_khusus']) ? 'Disetujui' : 'Belum' ?></td>
                        <td>
                            <?php if (!empty($health['consent_pada'])): ?>
                                Dikonfirmasi pelamar pada: <?= html_escape($health['consent_pada'] instanceof DateTime ? $health['consent_pada']->format('Y-m-d H:i') : substr((string)$health['consent_pada'], 0, 16)) ?>
                            <?php else: ?>
                                Persetujuan kepatuhan UU Perlindungan Data Pribadi
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ================= SECTION G: REFERENSI KERJA PROFESIONAL ================= -->
        <div class="avoid-break mt-6">
            <h3 class="section-title">G. REFERENSI KERJA PROFESIONAL</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-10 text-center">No</th>
                        <th>Nama Referensi</th>
                        <th>Perusahaan</th>
                        <th>Jabatan</th>
                        <th>Nomor Telepon</th>
                        <th>Hubungan Kerja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($references)): ?>
                        <?php foreach ($references as $idx => $rf): ?>
                            <tr>
                                <td class="text-center"><?= $idx + 1 ?></td>
                                <td class="font-medium"><?= html_escape($rf['nama_referensi']) ?></td>
                                <td><?= html_escape($rf['perusahaan'] ?: '-') ?></td>
                                <td><?= html_escape($rf['jabatan'] ?: '-') ?></td>
                                <td><?= html_escape($rf['no_telp'] ?: '-') ?></td>
                                <td><?= html_escape($rf['hubungan'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-slate-500 italic">Tidak ada data referensi kerja.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ================= SECTION H: MINAT DAN KONSEP PRIBADI ================= -->
        <?php $q = $questionnaire ?? array(); ?>
        <div class="avoid-break mt-6">
            <h3 class="section-title">H. MINAT DAN KONSEP PRIBADI</h3>
            <div class="border border-slate-300 p-4 space-y-4 text-sm">

                <div class="avoid-break">
                    <p class="font-bold text-slate-800">1. Apa yang mendorong Anda untuk bekerja / Mengapa Anda melamar di Ratu Pertiwi Group?</p>
                    <p class="mt-1 text-slate-700 pl-4 border-l-2 border-slate-300"><?= nl2br(html_escape($q['alasan_melamar'] ?? '-')) ?></p>
                </div>

                <div class="avoid-break">
                    <p class="font-bold text-slate-800">2. Mengapa Anda merasa cocok untuk menduduki posisi yang Anda lamar saat ini?</p>
                    <p class="mt-1 text-slate-700 pl-4 border-l-2 border-slate-300"><?= nl2br(html_escape($q['alasan_cocok_posisi'] ?? '-')) ?></p>
                </div>

                <div class="avoid-break">
                    <p class="font-bold text-slate-800">3. Apa yang Anda ketahui mengenai lini bisnis, produk, dan brand unit usaha Ratu Pertiwi Group?</p>
                    <p class="mt-1 text-slate-700 pl-4 border-l-2 border-slate-300"><?= nl2br(html_escape($q['pengetahuan_rpg'] ?? '-')) ?></p>
                </div>

                <div class="flex gap-4 avoid-break">
                    <div class="w-1/2">
                        <p class="font-bold text-slate-800">4. Faktor KEKUATAN / KELEBIHAN Anda?</p>
                        <p class="mt-1 text-slate-700 pl-4 border-l-2 border-slate-300"><?= nl2br(html_escape($q['kelebihan_diri'] ?? '-')) ?></p>
                    </div>
                    <div class="w-1/2">
                        <p class="font-bold text-slate-800">5. Faktor KELEMAHAN / KEKURANGAN Anda?</p>
                        <p class="mt-1 text-slate-700 pl-4 border-l-2 border-slate-300"><?= nl2br(html_escape($q['kekurangan_diri'] ?? '-')) ?></p>
                    </div>
                </div>

                <div class="avoid-break">
                    <p class="font-bold text-slate-800">6. Prestasi / pencapaian apakah yang pernah Anda capai selama ini?</p>
                    <p class="mt-1 text-slate-700 pl-4 border-l-2 border-slate-300"><?= nl2br(html_escape($q['prestasi_terbesar'] ?? '-')) ?></p>
                </div>

                <div class="avoid-break">
                    <p class="font-bold text-slate-800">7. Apakah rencana dan sasaran karir Anda dalam 3 sampai 5 tahun mendatang?</p>
                    <p class="mt-1 text-slate-700 pl-4 border-l-2 border-slate-300"><?= nl2br(html_escape($q['rencana_karir_5thn'] ?? '-')) ?></p>
                </div>

                <div class="avoid-break">
                    <p class="font-bold text-slate-800">8. Masalah kerja paling sulit yang pernah dihadapi dan bagaimana Anda menyelesaikannya?</p>
                    <p class="mt-1 text-slate-700 pl-4 border-l-2 border-slate-300"><?= nl2br(html_escape($q['masalah_tersulit_solusi'] ?? '-')) ?></p>
                </div>

                <div class="avoid-break">
                    <p class="font-bold text-slate-800">9. Lingkungan kerja seperti apa yang paling Anda sukai dan yang paling Anda hindari?</p>
                    <p class="mt-1 text-slate-700 pl-4 border-l-2 border-slate-300"><?= nl2br(html_escape($q['lingkungan_kerja_idaman'] ?? '-')) ?></p>
                </div>

            </div>
        </div>

        <!-- ================= SECTION I: INFORMASI UMUM ================= -->
        <div class="avoid-break mt-6">
            <h3 class="section-title">I. INFORMASI UMUM &amp; KESIAPAN OPERASIONAL</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-10 text-center">No</th>
                        <th>Pertanyaan Seleksi</th>
                        <th class="w-32">Status/Pilihan</th>
                        <th>Keterangan / Penjelasan Lengkap</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">1</td>
                        <td>Pernah melamar di perusahaan ini sebelumnya?</td>
                        <td class="font-semibold text-slate-700"><?= !empty($q['riwayat_melamar_rpg']) && strtolower($q['riwayat_melamar_rpg']) !== 'tidak pernah' ? 'Ya' : 'Tidak' ?></td>
                        <td><?= html_escape($q['riwayat_melamar_rpg'] ?? 'Tidak Pernah') ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">2</td>
                        <td>Mempunyai pekerjaan sampingan/bisnis/ikatan dinas?</td>
                        <td class="font-semibold text-slate-700"><?= !empty($q['punya_bisnis_sampingan']) && strtolower($q['punya_bisnis_sampingan']) !== 'tidak ada' ? 'Ya' : 'Tidak' ?></td>
                        <td><?= html_escape($q['punya_bisnis_sampingan'] ?? 'Tidak Ada') ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">3</td>
                        <td>Mempunyai saudara/teman yang bekerja di perusahaan ini?</td>
                        <td class="font-semibold text-slate-700"><?= !empty($q['relasi_keluarga_rpg']) && strtolower($q['relasi_keluarga_rpg']) !== 'tidak ada' ? 'Ya' : 'Tidak' ?></td>
                        <td><?= html_escape($q['relasi_keluarga_rpg'] ?? 'Tidak Ada') ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">4</td>
                        <td>Bersedia bekerja shift, hari libur dan lembur?</td>
                        <td class="font-semibold text-green-600"><?= html_escape($q['bersedia_shift_lembur'] ?? 'Bersedia') ?></td>
                        <td>Siap mengikuti jadwal operasional outlet / unit kerja RPG</td>
                    </tr>
                    <tr>
                        <td class="text-center">5</td>
                        <td>Bila diterima bekerja, bersediakah ditempatkan / mutasi ke luar kota?</td>
                        <td class="font-semibold text-green-600"><?= html_escape($q['bersedia_luar_kota'] ?? 'Bersedia') ?></td>
                        <td>Bersedia penempatan unit/cabang perusahaan RPG</td>
                    </tr>
                    <tr>
                        <td class="text-center">6</td>
                        <td>Alat transportasi &amp; SIM yang Anda gunakan untuk bekerja?</td>
                        <td class="font-semibold text-slate-700"><?= html_escape($c['no_sim'] ?: 'SIM C') ?></td>
                        <td><?= html_escape($q['kepemilikan_kendaraan'] ?? 'Kendaraan Pribadi (SIM C)') ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">7</td>
                        <td>Pernah tersangkut perkara pidana / hukum?</td>
                        <td class="font-semibold text-slate-700"><?= !empty($q['riwayat_tindak_pidana']) && strtolower($q['riwayat_tindak_pidana']) !== 'tidak pernah' ? 'Pernah' : 'Tidak' ?></td>
                        <td><?= html_escape($q['riwayat_tindak_pidana'] ?? 'Tidak Pernah') ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">8</td>
                        <td>Kapan Anda dapat mulai aktif bekerja di perusahaan ini?</td>
                        <td class="font-semibold text-slate-700"><?= html_escape($q['ketersediaan_mulai'] ?? 'Secepatnya') ?></td>
                        <td>Notice period / kesiapan bergabung</td>
                    </tr>
                    <tr>
                        <td class="text-center">9</td>
                        <td>Berapa gaji dan fasilitas yang Anda harapkan?</td>
                        <td class="font-semibold text-slate-700">
                            <?= html_escape(!empty($q['harapan_gaji_fasilitas']) ? $q['harapan_gaji_fasilitas'] : ($profile && !empty($profile['gaji_diharapkan']) ? 'Rp ' . number_format((float)$profile['gaji_diharapkan'], 0, ',', '.') : 'Sesuai UMK')) ?>
                        </td>
                        <td>Harapan kompensasi bulanan</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ================= SECTION J: REKENING PAYROLL ================= -->
        <div class="avoid-break mt-6 mb-8">
            <h3 class="section-title">J. REKENING PAYROLL</h3>
            <div class="border-2 border-slate-300 bg-slate-50 p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 font-semibold uppercase mb-1">Rekening Pencairan Gaji (Payroll)</p>
                    <?php if ($bank && !empty($bank['nama_bank'])): ?>
                        <p class="text-lg font-bold text-slate-900"><?= html_escape($bank['nama_bank']) ?></p>
                        <?php if (can_sensitif('FINANSIAL')): ?>
                            <p class="text-xl font-mono tracking-widest text-slate-800"><?= html_escape($bank['no_rekening'] ?: '-') ?></p>
                        <?php else: ?>
                            <p class="text-sm text-slate-500 italic">[Nomor Rekening Terproteksi RBAC]</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-base text-slate-500 italic">Belum mengisi rekening payroll</p>
                    <?php endif; ?>
                </div>
                <div class="text-right">
                    <p class="text-sm text-slate-500">Atas Nama:</p>
                    <p class="text-lg font-bold text-slate-900 uppercase">
                        <?= $bank && !empty($bank['nama_pemilik']) ? html_escape($bank['nama_pemilik']) : html_escape($c['nama_lengkap']) ?>
                    </p>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
