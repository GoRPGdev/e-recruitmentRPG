<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* -------------------------------------------------------------------------
 * Salin file ini jadi database.php lalu isi sesuai mesin masing-masing.
 * database.php SUDAH di-gitignore -- jangan pernah di-commit.
 *
 * Driver: sqlsrv (ekstensi php_sqlsrv 5.9). Target SQL Server 2008 R2.
 * Instance default -> hostname 'localhost' (BUKAN 'localhost\SQLEXPRESS').
 * ------------------------------------------------------------------------- */

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'          => '',
	'hostname'     => 'localhost',
	'username'     => 'erec_app',
	'password'     => 'GANTI_DENGAN_PASSWORD',
	'database'     => 'RPG_EREC_DEV_XXXX',   // KIKI / KAHFI
	'dbdriver'     => 'sqlsrv',
	'dbprefix'     => '',
	'pconnect'     => FALSE,
	'db_debug'     => (ENVIRONMENT !== 'production'),
	'cache_on'     => FALSE,
	'cachedir'     => '',
	'char_set'     => 'utf8',
	'dbcollat'     => 'utf8_general_ci',
	'swap_pre'     => '',
	'encrypt'      => FALSE,
	'compress'     => FALSE,
	'stricton'     => FALSE,
	'failover'     => array(),
	'save_queries' => TRUE,
);
