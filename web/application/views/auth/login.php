<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>E-Recruitment RPG</h1>
	<p class="muted" style="margin-top:0">Masuk untuk melanjutkan.</p>

	<?= validation_errors('<div class="flash err">', '</div>') ?>

	<?= form_open(site_url('auth/login')) ?>
		<label for="username">Username</label>
		<input type="text" id="username" name="username" value="<?= set_value('username') ?>" autocomplete="username" autofocus required>

		<label for="password">Password</label>
		<input type="password" id="password" name="password" autocomplete="current-password" required>

		<button type="submit">Masuk</button>
	<?= form_close() ?>
</main>
