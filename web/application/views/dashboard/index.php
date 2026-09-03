<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Dashboard</h1>
	<p>Halo, <strong><?= html_escape($auth_user['nama']) ?></strong>
	   (<code><?= html_escape($auth_user['kode_role']) ?></code>) &middot;
	   <a href="<?= site_url('auth/logout') ?>">keluar</a></p>

	<h2>Permission efektif (<?= count($permissions) ?>)</h2>
	<?php if ($permissions): ?>
		<ul>
		<?php foreach ($permissions as $p): ?>
			<li><code><?= html_escape($p) ?></code></li>
		<?php endforeach; ?>
		</ul>
	<?php else: ?>
		<p class="muted">Role ini belum punya permission.</p>
	<?php endif; ?>

	<p class="muted">
		Kerangka Fase 1 tersambung: koneksi <code>sqlsrv</code> &rarr;
		<code>sp_Login</code> + <code>sp_GetUserPermissions</code> &rarr;
		helper RBAC (<code>has_permission()</code>) &rarr; session.
	</p>
</main>
