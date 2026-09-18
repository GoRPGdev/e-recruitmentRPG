# SSO dari Payroll ke e-Recruitment

Panduan untuk sisi **Payroll** (repo terpisah). e-Recruitment sudah siap
menerima; bagian ini yang perlu ditambahkan di kode Payroll.

## Cara kerja singkat

Payroll tidak pernah mengirim password atau data lain -- cuma NIK orang
yang sedang login di sana, dibungkus tiket sekali-pakai yang ditandatangani
pakai *secret* yang sama-sama diketahui kedua sisi. e-Recruitment percaya
tiket itu **hanya** kalau tanda tangannya cocok dan belum kedaluwarsa
(~90 detik). Detail alasan desain ini ada di percakapan/riwayat commit
`feat(users): jamin NIK karyawan unik` dan seterusnya di repo ini.

## 1. Secret bersama

Satu nilai rahasia, harus **identik persis** di kedua sisi. Generate sekali
lewat:

```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

**Jangan pernah taruh nilai ini di file yang di-commit ke Git, di repo mana
pun -- termasuk di dokumen ini.** Kirim nilainya ke Kahfi lewat jalur privat
(chat langsung, bukan commit message/issue/PR), lalu pasang lewat
environment variable di server:

- Sisi e-Recruitment: `EREC_SSO_SECRET` (lihat `web/application/config/erecruitment.php`)
- Sisi Payroll: nama terserah, misal `PAYROLL_EREC_SSO_SECRET` -- yang penting
  isinya sama persis dengan yang di atas.

## 2. Menu "E-Recruitment" di sidebar Payroll

Tambah item menu baru yang mengarah ke method `buatLinkErecruitment()` di
bawah, bukan link statis -- karena tiketnya harus dibuat baru setiap klik.

```php
function buatLinkErecruitment($nik)
{
    $secret = getenv('PAYROLL_EREC_SSO_SECRET');
    $exp    = time() + 60; // di bawah batas toleransi 90 detik di e-recruitment
    $sig    = hash_hmac('sha256', $nik . '|' . $exp, $secret);

    return 'https://erecruitment.rpgroup.co.id/sso?' . http_build_query(array(
        'nik' => $nik,
        'exp' => $exp,
        'sig' => $sig,
    ));
}
```

`$nik` diambil dari data user yang sedang login di sesi Payroll saat itu
juga (bukan input dari luar).

## 3. Sembunyikan menu dari NIK yang belum terdaftar

Supaya menunya tidak muncul ke semua 500+ karyawan, cek dulu ke
e-Recruitment sebelum render sidebar -- panggil server-ke-server (curl),
**bukan** dari browser user:

```php
function erecruitmentMenuBoleh($nik)
{
    // Cache di session Payroll supaya tidak nge-hit tiap halaman:
    if (isset($_SESSION['erec_menu_boleh'])) {
        return $_SESSION['erec_menu_boleh'];
    }

    $secret = getenv('PAYROLL_EREC_SSO_SECRET');
    $url    = 'https://erecruitment.rpgroup.co.id/sso/cek_akses?' . http_build_query(array('nik' => $nik));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $secret));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // jangan sampai Payroll ikut lambat kalau e-recruitment down
    $resp = curl_exec($ch);
    curl_close($ch);

    $data = json_decode((string) $resp, true);
    return $_SESSION['erec_menu_boleh'] = ! empty($data['allowed']);
}
```

Kalau `erecruitmentMenuBoleh($nik)` `false` atau curl gagal/timeout (server
e-recruitment lagi mati) -> **default-nya sembunyikan menu**, jangan
ditampilkan. Lebih aman salah sembunyi daripada salah tampilkan.

## 4. Yang HARUS diingat

- **Satu NIK = satu akun di e-Recruitment.** Kalau HR belum mendaftarkan
  NIK seseorang lewat Manajemen Pengguna di e-Recruitment, orang itu akan
  ditolak walau tanda tangan tokennya valid -- itu memang disengaja
  (lihat `Sso::masuk()`, pesannya "Akun Anda belum didaftarkan").
- **`exp` wajib dekat (~60 detik dari sekarang), jangan dibuat jauh ke
  depan** -- e-recruitment menolak token dengan `exp` lebih dari 90 detik
  dari waktu server e-recruitment menerimanya.
- Kalau ganti secret suatu saat (rotasi rutin/kebocoran), ganti **di kedua
  sisi bersamaan** -- kalau tidak sinkron, semua login SSO akan gagal
  sampai keduanya diperbarui.
