<?php
/**
 * MyAdmin - Config
 * Copyright (C) Persian Icon Software
 * The GPL License
*/

defined('MA_PATH') OR exit('Restricted access');

$ma_config = [

	'languages' => [
		'fa' => 'Farsi',
		'en' => 'English',
	],

	'default_language' => 'fa',

	'language_redirection' => TRUE,

	'admin_languages' => [
		'fa' => 'Farsi',
		'en' => 'English',
	],

	'admin_default_language' => 'fa',

	'timezone'  => 'Asia/Tehran',

	'x_frame_options' => 'SAMEORIGIN',

	'database' => [
		'drive'    => 'sqlite',
		'file_path' => MA_PATH.'/database.db',
		'host'     => '127.0.0.1',
		'port'     => 3306,
		'name'     => '',
		'username' => '',
		'password' => '',
		'table_prefix' => '',
	]
];