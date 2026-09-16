# Panduan & Lembar Kerja UAT (User Acceptance Testing)
## Sistem E-Recruitment Ratu Pertiwi Group (RPG)

**Versi Sistem:** 1.0 (Release Candidate)  
**Target Penguji:** Tim HR (Mas Fachri & Tim Recruitment), IT Admin, & Kepala Departemen  
**Tujuan:** Memvalidasi seluruh siklus rekrutmen *end-to-end* berjalan sesuai alur operasional resmi RPG sebelum sistem dibuka ke domain publik.

---

### Informasi Pelaksanaan UAT
* **Tanggal Pelaksanaan:** `[Diisi Tanggal UAT]`
* **Tempat / Server:** Server Lokal Kantor RPG (`http://localhost:8080` / Domain Kantor)
* **Penguji Utama (Tester):** `[Nama Penguji / Mas Fachri]`
* **Pendamping Teknis:** Kahfi / Kiki

---

### Matriks Skenario Pengujian UAT (End-to-End)

| No | Modul & Skenario | Langkah Pengujian | Hasil yang Diharapkan | Status (PASS/FAIL) | Catatan HR |
|:--:|---|---|---|:---:|---|
| **1** | **Pengajuan MPR oleh User Dept** | 1. Login sebagai `dept.marketing`<br>2. Buka menu **Requisitions** ➔ Klik **+ Pengajuan Baru**<br>3. Isi posisi, jumlah orang, alasan pengajuan, spesifikasi<br>4. Klik **Kirim Pengajuan ke HR** | - MPR terbuat dengan status *Review HR*<br>- Nomor otomatis terbentuk: `MPR/YYYY/MM/NNN`<br>- Muncul di daftar MPR dengan badge kuning | `[  ]` | |
| **2** | **Review HR Putaran 1** | 1. Login sebagai `admin.rpg` (HR)<br>2. Buka MPR yang diajukan<br>3. Klik **Review HR**<br>4. Isi analisis beban kerja & catatan HR<br>5. Klik **Teruskan ke BOD** | - Status MPR berubah menjadi *Review BOD*<br>- Catatan HR tersimpan dan tercatat di riwayat approval | `[  ]` | |
| **3** | **Persetujuan BOD (Putaran 2)** | 1. Login sebagai Admin/BOD<br>2. Buka detail MPR<br>3. Klik tombol **Setujui Formasi (Approve)**<br>4. Unggah bukti persetujuan (opsional) | - Status MPR berubah menjadi **Disetujui**<br>- Tombol **Buat Lowongan (Posting)** aktif | `[  ]` | |
| **4** | **Publikasi Lowongan & Link Form** | 1. Pada detail MPR disetujui, klik **Buat Lowongan**<br>2. Tentukan tanggal buka & tutup<br>3. Salin URL publik yang dihasilkan (misal: `/lamar/marketing-staff-xxx`) | - Lowongan aktif<br>- URL publik siap disebarkan ke pelamar luar | `[  ]` | |
| **5** | **Pendaftaran Mandiri Pelamar** | 1. Buka link `/lamar/{slug}` di browser atau HP<br>2. Isi 21 field data pelamar<br>3. Unggah file CV (format PDF/JPG)<br>4. Centang persetujuan kebenaran data & PDP<br>5. Klik **Kirim Lamaran** | - Muncul halaman sukses: *"Lamaran Terkirim"*<br>- File CV tersimpan aman di storage server<br>- Data kandidat otomatis masuk ke database | `[  ]` | |
| **6** | **Pipeline & Kartu Pelamar Otomatis** | 1. Login HR ➔ Buka menu **Pipeline** posisi terkait<br>2. Periksa baris tahap pertama (*Screening / Seleksi Berkas*) | - Kartu pelamar baru otomatis tampil di tahap awal tanpa input manual HR<br>- Menampilkan nama, kontak WA, dan ringkasan profil | `[  ]` | |
| **7** | **Pindah Tahap (Advance Stage)** | 1. Klik kartu pelamar di Pipeline<br>2. Klik tombol **Proses ke Tahap Berikutnya**<br>3. Pilih keputusan Remark (misal: *Lolos Seleksi Berkas*)<br>4. Ketik catatan evaluasi singkat ➔ Simpan | - Kartu pelamar otomatis berpindah ke kolom/baris tahap selanjutnya (misal: *Tahap Kontak / Interview*)<br>- Riwayat mutasi tercatat otomatis | `[  ]` | |
| **8** | **Penambahan Tahap Sisipan (Ad-Hoc)** | 1. Pada kartu pelamar, klik **Sisip Tahap Khusus**<br>2. Pilih tahap tambahan (misal: *Tes Koding / Psikotes*)<br>3. Pilih remark lanjut ➔ Klik **Sisipkan & Lanjut** | - Tahap baru otomatis disisipkan ke alur kandidat tersebut<br>- Kandidat langsung aktif di tahap sisipan tanpa duplikasi tahap | `[  ]` | |
| **9** | **Jadwal Interview & Form Penilaian** | 1. Pada tahap Interview, klik **Catat Wawancara**<br>2. Isi tanggal, waktu, pewawancara, dan rekomendasi | - Data wawancara tersimpan rapi<br>- Catatan evaluasi langsung dapat dibaca oleh tim HR | `[  ]` | |
| **10** | **Formulir Onboarding Digital A-I** | 1. Pada kartu pelamar, klik tombol **Tautan Onboarding**<br>2. Klik **Buka WhatsApp** untuk mengirim tautan ke pelamar<br>3. Buka tautan di tab baru: lengkapi biodata keluarga, pengalaman kerja, kuesioner, rekening payroll<br>4. Klik **Submit Final** | - Pelamar dapat mengisi formulir lanjutan secara mandiri<br>- Tautan otomatis berstatus *Sudah Pernah Digunakan* setelah submit | `[  ]` | |
| **11** | **Cetak Fisik Formulir Pelamar (Print)** | 1. Buka detail kandidat di menu HR<br>2. Klik tombol **Cetak Formulir Pelamar (A-I)** | - Muncul tampilan cetak rapi standar dokumen cetak RPG<br>- Siap dicetak ke kertas A4 atau disimpan sebagai PDF arsip | `[  ]` | |
| **12** | **Tahap Offering & Perlindungan Gaji** | 1. Pada tahap Offering, klik **Input Penawaran (Offer)**<br>2. Masukkan nominal gaji & tanggal mulai kerja<br>3. Cek tampilan detail kandidat | - Data gaji hanya dapat dilihat oleh role yang berwenang (HR Spv/Admin)<br>- Pembukaan data tercatat di *ACCESS_LOG_SENSITIF* | `[  ]` | |
| **13** | **Finalisasi Hired (Karyawan Baru)** | 1. Pindahkan kandidat ke tahap **Hired**<br>2. Simpan evaluasi | - Status global kandidat berubah menjadi **HIRED**<br>- Kuota MPR berkurang otomatis sesuai target rekrutmen | `[  ]` | |
| **14** | **Dashboard Eksekutif & Matriks Funnel** | 1. Buka menu **Dashboard**<br>2. Periksa kartu KPI (Pelamar Baru, Proses, Hired)<br>3. Cek Matriks Posisi x Tahapan<br>4. Cek Tren 14 Hari | - Seluruh angka terakumulasi akurat dan real-time<br>- Filter tanggal dan posisi berfungsi responsif | `[  ]` | |
| **15** | **Ekspor Spreadsheet Data Pelamar** | 1. Klik menu **Export Data Pelamar**<br>2. Buka file `.xls` yang terunduh di Microsoft Excel | - File Excel terbuka rapi<br>- Kolom nomor WhatsApp dan NIK tidak berubah menjadi notasi ilmiah (`6.28E+11`) | `[  ]` | |

---

### Lembar Evaluasi & Rekomendasi Tim HR
Gunakan bagian ini untuk mencatat masukan atau penyesuaian yang diinginkan tim HR saat sesi uji coba:

```text
1. Masukan terkait alur kerja:
   ....................................................................................................
   ....................................................................................................

2. Masukan terkait tampilan antarmuka / formulir:
   ....................................................................................................
   ....................................................................................................

3. Kendala atau bug yang ditemukan:
   ....................................................................................................
   ....................................................................................................
```

---

### Tanda Tangan Persetujuan UAT (Sign-off)

Dengan ini menyatakan bahwa pengujian penerimaan pengguna (*User Acceptance Testing*) sistem **E-Recruitment Ratu Pertiwi Group (RPG)** telah dilaksanakan dengan hasil yang tertera di atas dan sistem dinyatakan **SIAP / BELUM SIAP** untuk tahap deployment produksi.

| Pihak HR Recruitment (RPG) | Pihak Pengembang Sistem | Pihak Manajemen / IT |
|:---:|:---:|:---:|
| <br><br><br>___________________________<br>**Fachri / Tim HR** | <br><br><br>___________________________<br>**Kahfi / Kiki** | <br><br><br>___________________________<br>**IT / General Management** |
| Tanggal: _______________ | Tanggal: _______________ | Tanggal: _______________ |
