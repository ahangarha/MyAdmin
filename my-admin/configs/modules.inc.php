<?php
/**
 * MyAdmin - Config
 * Copyright (C) Persian Icon Software
 * The GPL License
*/

defined('MA_PATH') OR exit('Restricted access');

define('MA_MODULES', [

	'admin' => [
		'url_path' => 'myadmin',
		'enabled'  => TRUE,
		'controller' => 'index'
	],

	'post' => [
		'url_path'   => '*',
		'enabled'    => TRUE,
		'controller' => 'index'
	],

	'users' => [
		'url_path'   => 'user',
		'enabled'    => TRUE,
		'controller' => 'index'
	],

]);