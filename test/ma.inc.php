<?php
/**
 * MyAdmin - Test
 * Copyright (C) Persian Icon Software
 * The GPL License
*/

// Environment (development|working)
define('MA_ENVIRONMENT', 'development');

// Core
$init_file = dirname(__DIR__).'/my-admin/myadmin.php';
if (is_file($init_file) == FALSE) {
	$init_file = __DIR__.'/my-admin/myadmin.php';
	if (is_file($init_file) == FALSE) {
		header('HTTP/1.1 500 Internal Server Error');
		echo 'error_00::myadmin.php_not_found';
		exit(1);
	}
}

require_once($init_file);

function myWebsite($run = TRUE) {
	$myadmin = new myadmin(__FILE__, __DIR__);
	if ($run == TRUE) {
		$myadmin->run();
	}
}

if (defined('CRLF') == FALSE) {
	define('CRLF', "\n");
}