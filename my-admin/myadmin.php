<?php
/**
 * MyAdmin
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
 * @package	  MyAdmin CMS
 * @copyright Persian Icon Software
 * @link      https://www.persianicon.com/myadmin
*/

/**
 * MyAdmin
 *
 * @modified : 31 August 2018
 * @created  : 03 September 2011
 * @since    : version 0.1
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

/**
 * PHP Version
*/
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
	echo '<b>Error</b><br>You must upgrade the PHP version.<br>Current version: ('.PHP_VERSION.')';
	exit(0);
}

/**
 * Benchmark (being for real)
*/
defined('MA_PRE_TIME') OR define('MA_PRE_TIME', microtime(TRUE));

/**
 * Environment
*/
defined('MA_ENVIRONMENT') OR define('MA_ENVIRONMENT', 'working');

// Display errors
if (MA_ENVIRONMENT == 'development') {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}
else {
	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);
}

// MyAdmin directory path
define('MA_PATH', rtrim(realpath(__DIR__).DIRECTORY_SEPARATOR, '/'));

/**
 * File & directory modes
*/
defined('MA_FILE_R_MODE') OR define('MA_FILE_R_MODE', 0444);
defined('MA_FILE_W_MODE') OR define('MA_FILE_W_MODE', 0644);
defined('MA_DIR_R_MODE')  OR define('MA_DIR_R_MODE',  0755);
defined('MA_DIR_W_MODE')  OR define('MA_DIR_W_MODE',  0755);

/**
 * Erros & Logs
*/
defined('MA_LOG_LEVEL') OR define('MA_LOG_LEVEL', -1);
defined('MA_DEBUG') OR define('MA_DEBUG', 0);
defined('MA_ERROR_SEND_MAIL') OR define('MA_ERROR_SEND_MAIL', FALSE);
defined('MA_ERROR_SEND_MAIL_TIMEOUT') OR define('MA_ERROR_SEND_MAIL_TIMEOUT', 3600);

/**
 * C like
*/
defined('EXIT_SUCCESS') OR define('EXIT_SUCCESS', 0); // no error
defined('EXIT_FAILURE') OR define('EXIT_FAILURE', 1); // on error

/**
 * SAPI or CLI
*/
defined('MA_CLI') OR define('MA_CLI', FALSE);

require_once(MA_PATH.'/_version.inc.php');
require_once(MA_PATH.'/common.func.php');
require_once(MA_PATH.'/myadmin.class.php');