<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Gangguan Transaksi Database — e-Recruitment RPG</title>
<style>
:root {
  --bg: #f5f7f4; --surface: #ffffff; --border: #d6dbcf;
  --text: #1b211c; --text-muted: #59635b; --text-faint: #8a938a;
  --accent: #1f6f5c; --accent-soft: #e3f0ea; --accent-contrast: #ffffff;
  --crit: #b23b3b; --crit-soft: #f6e1df;
  --shadow: 0 1px 3px rgba(20,30,25,.06), 0 12px 32px rgba(20,30,25,.08);
}
@media (prefers-color-scheme: dark) {
  :root {
    --bg: #111512; --surface: #191d18; --border: #333a32;
    --text: #e7ebe4; --text-muted: #9ba79c; --text-faint: #717d72;
    --accent: #4bb89d; --accent-soft: #183129; --accent-contrast: #07130f;
    --crit: #e08585; --crit-soft: #3a201f;
    --shadow: 0 1px 3px rgba(0,0,0,.3), 0 14px 36px rgba(0,0,0,.45);
  }
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
}
.error-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  box-shadow: var(--shadow);
  max-width: 540px;
  width: 100%;
  padding: 36px 30px;
  text-align: center;
}
.badge-code {
  display: inline-block;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--crit);
  background: var(--crit-soft);
  padding: 4px 12px;
  border-radius: 20px;
  margin-bottom: 14px;
}
.error-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 10px;
  line-height: 1.3;
}
.error-desc {
  font-size: 13.5px;
  line-height: 1.6;
  color: var(--text-muted);
  margin-bottom: 24px;
}
.error-box {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 12px 14px;
  text-align: left;
  font-family: Consolas, monospace;
  font-size: 12px;
  color: var(--text);
  margin-bottom: 24px;
  max-height: 180px;
  overflow-y: auto;
  word-break: break-word;
}
.btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: var(--accent);
  color: var(--accent-contrast);
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 600;
  padding: 10px 22px;
  border-radius: 8px;
  transition: opacity .15s ease, transform .15s ease;
}
.btn-action:hover {
  opacity: .92;
  transform: translateY(-1px);
}
.brand-foot {
  margin-top: 20px;
  font-size: 11.5px;
  color: var(--text-faint);
}
</style>
</head>
<body>

<div class="error-card">
  <div class="badge-code">Database Notice</div>
  <h1 class="error-title"><?php echo htmlspecialchars($heading, ENT_QUOTES, 'UTF-8'); ?></h1>

  <?php if (defined('ENVIRONMENT') && ENVIRONMENT === 'production'): ?>
    <p class="error-desc">
      Terjadi gangguan koneksi atau proses pada database server. Silakan coba muat ulang halaman beberapa saat lagi atau hubungi administrator jika kendala berlanjut.
    </p>
  <?php else: ?>
    <p class="error-desc">
      Terjadi kendala saat mengeksekusi perintah database:
    </p>
    <div class="error-box">
      <?php echo $message; ?>
    </div>
  <?php endif; ?>

  <a href="javascript:history.back()" class="btn-action">
    &larr; Kembali ke Halaman Sebelumnya
  </a>
</div>

<div class="brand-foot">
  &copy; <?php echo date('Y'); ?> Ratu Pertiwi Group &middot; e-Recruitment System
</div>

</body>
</html>
