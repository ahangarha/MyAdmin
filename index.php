<?php
/**
 * MyAdmin CMS
 *
 * Copyright (C) 2014-2018 Persian Icon Software
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package	  MyAdmin Content Management System
 * @link      http://www.persianicon.com/myadmin
 * @copyright Persian Icon Software
 * @link      https://www.persianicon.com/
*/

/**
 * Index
 *
 * @modified : 16 September 2018
 * @created  : 01 May 2014
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

myWebsite(TRUE);