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

defined('MA_PATH') OR exit('Restricted access');

/**
 * Common functions
 *
 * @modified : 26 July 2018
 * @created  : 03 September 2011
 * @since    : version 0.1
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

/**
 * Types
*/
define('MA_ARRAY',  1);
define('MA_OBJECT', 2);
define('MA_JSON', 3);
define('MA_XML',  4);
define('MA_PHP',  5);
define('MA_HTML', 6);

/**
 * Actions...
*/
define('MA_READ',   1);
define('MA_LOAD',   1);
define('MA_WRITE',  2);
define('MA_SAVE',   2);
define('MA_DELETE', 3);
define('MA_REMOVE', 3);

/****************************************
 * System
****************************************/

/**
 * Config
 *
 * @param string
 * @param mixed
 * @param array options
 * @return mixed on success/bool FALSE on failure
*/
if (function_exists('ma_config') == FALSE) {
	function &ma_config($key, $value = '', $opt = []) {
		static $_ma_configs = [];

		// All
		if ($key == MA_ARRAY) {
			return $_ma_configs;
		}
		// New Item
		else if ($value != '') {
			$_ma_configs[$key] = $value;
			return $_ma_configs[$key];
		}

		$v = array_key_exists($key, $_ma_configs) ? $_ma_configs[$key] : FALSE;
		return $v;
	}
}

/**
 * Include
*/
if (function_exists('ma_include') == FALSE) {
	function ma_include($filename, $once = TRUE) {
		$cf = MA_PATH.'/'.$filename;
		if (is_file($cf) == FALSE) {
			exit('error_01::file_not_found::'.$filename);
		}

		if ($once == TRUE) {
			require_once($cf);
		}
		else {
			require($cf);
		}

		return;
	}
}

/**
 * Class loader
 *
 * @param string
 * @param mixed
 * @param array options
 * @return mixed on success/bool FALSE on failure
*/
if (function_exists('ma_class') == FALSE) {
	function &ma_class($class_name, $opt = []) {
		static $_ma_classes = [];

		$prefix = isset($opt['prefix']) ? $opt['prefix'] : 'ma_';
		$file_path = $class_name;
		$class_name = $prefix.$class_name;
		if (isset($_ma_classes[$class_name])) {
			return $_ma_classes[$class_name];
		}

		$dir = isset($opt['dir']) == TRUE ? $opt['dir'] : 'library';
		if ($dir == '/') {
			$file_path = MA_PATH.'/'.$file_path.'.class.php';
		}
		else {
			$file_path = MA_PATH.'/'.$dir.'/'.$file_path.'.class.php';
		}

		if (is_file($file_path) == FALSE) {
			echo 'error_10::class_file_not_found::'.$file_path;
			exit(EXIT_FAILURE);
		}

		require_once($file_path);
		if (class_exists($class_name) == FALSE) {
			echo 'error_11::class_name_is_wrong::'.$class_name;
			exit(EXIT_FAILURE);
		}

		$_ma_classes[$class_name] = new $class_name;
		return $_ma_classes[$class_name];
	}
}

/****************************************
 * Logs/Errors
****************************************/

/**
 * Save log
 *
 * @param  string
 * @param  string
 * @param  array
 * @return void
*/
if (function_exists('ma_save_log') == FALSE) {
	function ma_save_log($level = 'error', $message, $detail = []) {
		static $_log;

		if (defined('MA_DEBUG') == FALSE){
			return;
		}
		else if (MA_LOG_LEVEL == 0 && MA_DEBUG == 0) {
			return;
		}

		$_log =& ma_class('log');
		$_log->save($level, $message, $detail);
	}
}

/**
 * PHP Error Handling
 * All Errors
*/
if (function_exists('ma_error_handler') == FALSE) {
	function ma_error_handler($errno, $errstr, $errfile, $errline) {
		static $_ma_error_handler;
		if (isset($_ma_error_handler) == FALSE) {
			$_ma_error_handler[0] =& ma_class('error_handler');
		}
		$error = $_ma_error_handler[0]->error($errno, $errstr, $errfile, $errline);
	}
}

/**
 * PHP Error Shutdown
 * Fatal Errors
*/
if (function_exists('ma_error_shutdown') == FALSE) {
	function ma_error_shutdown() {
		static $_ma_error_handler;
		if (isset($_ma_error_handler) == FALSE) {
			$_ma_error_handler[0] =& ma_class('error_handler');
		}
		$error = $_ma_error_handler[0]->shotdown();
	}
}

/**
 * Error page
 *
 * @param  int the status code
 * @param  string
 * @param  bool
 * @param  string
 * @param  bool
 * @return void
*/
if (function_exists('ma_error') == FALSE) {
	function ma_error($code = 404, $message = NULL, $opt = []) {
		// Ajax
		$is_ajax = FALSE;
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
			strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$is_ajax = TRUE;
		}

		ma_header('Connection', 'Close');
		$text = ma_http_status($code);
		if ($text == FALSE) {
			exit('error::unknown_http_status_code::'.$message);
		}

		if ($is_ajax == TRUE) {
			ma_header('Content-Type', 'text/plain; charset=utf-8');
			echo json_encode([
				'error' => 1,
				'http_status' => $code.' '.$text,
				'message' => (empty($message)) ? NULL : str_replace(['<!--','-->'], '', $message)
			]);
		}
		else if (is_file(MA_PATH.'/var/error/'.$code.'.phtml')) {
			require_once MA_PATH.'/var/error/'.$code.'.phtml';
		}
		else if (is_file(MA_PATH.'/var/error/default.phtml')) {
			require_once MA_PATH.'/var/error/default.phtml';
		}
		else {
			echo '<!DOCTYPE HTML><html><head>';
			echo '<meta charset="utf-8">';
			echo '<title>Error '.$code.'</title>';
			echo '</head><body>';
			echo '<h1>'.$text.'</h1>';
			if ($message) {
				echo '<p>'.$message.'</p>';
			}
			echo '</body></html>';
		}
		exit(EXIT_FAILURE);
	}
}

/****************************************
 * Files (read/write)
****************************************/

/**
 * Reading the file content
 *
 * @param string
 * @param bool
 * @return string or array on success/bool FALSE on failure
*/
if (function_exists('ma_file_read') == FALSE) {
	function ma_file_read($file_path, $opt = []) {
		$file_path = MA_PATH.$file_path;
		if (is_file($file_path) == FALSE) {
			return FALSE;
		}

		$content = file_get_contents($file_path);
		if (isset($opt['json_decode']) && $opt['json_decode'] == TRUE) {
			$opt['json_array'] = isset($opt['json_array']) && $opt['json_array'] == TRUE ? TRUE : FALSE;
			return json_decode($content, $opt['json_array']);
		}
		return $content;
	}
}

/**
 * Writing in file
 *
 * @param string
 * @param string|int
 * @param array
 * @return string or array on success/bool FALSE on failure
*/
if (function_exists('ma_file_write') == FALSE) {
	function ma_file_write($file_path, $content, $opt = []) {
		if (isset($opt['ma_path']) == FALSE || $opt['ma_path'] == TRUE) {
			$file_path = MA_PATH.$file_path;
		}
		if (is_file($file_path) == TRUE && is_writable($file_path) == FALSE) {
			if (chmod($file_path, MA_FILE_W_MODE) == FALSE) {
				return FALSE;
			}
		}

		$fp = fopen($file_path, 'w');
		if ($fp != TRUE) {
			return FALSE;
		}

		$st = TRUE;
		if (flock($fp, LOCK_EX)) {
			if (fwrite($fp, $content) == FALSE) {
				$st = FALSE;
			}
		}
		else {
			$st = FALSE;
		}

		fclose($fp);
		if ($st == FALSE) {
			return FALSE;
		}

		if (isset($opt['chmod'])) {
			if (chmod($file_path, $opt['chmod']) == FALSE) {
				return FALSE;
			}
		}
		return TRUE;
	}
}

/**
 * Remove file
 *
 * @param string
 * @param bool
 * @return string or array on success/bool FALSE on failure
*/
if (function_exists('ma_file_remove') == FALSE) {
	function ma_file_remove($file_path, $opt = []) {
		$file_path = MA_PATH.$file_path;
		if (is_file($file_path) == FALSE) {
			return FALSE;
		}
		if (is_writable($file_path) == FALSE) {
			chmod($file_path, MA_FILE_W_MODE);
		}
		if (unlink($file_path) == FALSE) {
			return FALSE;
		}
		return TRUE;
	}
}

/**
 * File
 *
 * @param int (MA_READ, MA_WRITE, MA_DELETE)
 * @param string
 * @param bool
 * @return string or array on success/bool FALSE on failure
*/
if (function_exists('ma_file') == FALSE) {
	function ma_file($do, $file_path, $opt = []) {
		if ($do == MA_LOAD OR $do == MA_READ) {
			return ma_file_read($file_path, $opts);
		}
		else if ($do == MA_SAVE OR $do == MA_WRITE) {
			return ma_file_write($file_path, $opts);
		}
		else if ($do == MA_DELETE) {
			return ma_file_delete($file_path, $opts);
		}
		else {
			return FALSE;
		}
	}
}

/****************************************
 * Json
****************************************/

/**
 * Json encode
 *
 * @param  mixed
 * @param  array
 * @return string|bool TRUE on success/FALSE on failure
*/
if (function_exists('ma_json_encode') == FALSE) {
	function ma_json_encode($value, $opt = [], $array = TRUE) {
		return json_encode($value, $array);
	}
}

/**
 * Json decode
 *
 * @param  string
 * @param  array
 * @return bool TRUE on success/FALSE on failure
*/
if (function_exists('ma_json_decode') == FALSE) {
	function ma_json_decode($value, $opt = []) {
		return json_decode($value);
	}
}

/**
 * Json print
 *
 * @param  string|array
 * @param  array
 * @return void
*/
if (function_exists('ma_printjs') == FALSE) {
	function ma_printjs($value, $opt = []) {
		//if (is_array($value) == TRUE || is_object($value) == TRUE) {
		//	$value = ma_json_decode($value, $opt);
		//}

		if (isset($opt['content_type']) == TRUE) {
			if (empty($opt['content_type']) != TRUE) {
				header('Content-Type: '.$opt['content_type']);
			}
		}
		else {
			header('Content-Type: text/plain; charset=utf-8');
		}

		echo json_encode($value);
		return;
	}
}

/****************************************
 * Http
****************************************/

/**
 * HTTP Header
 *
 * @param  string|array
 * @return void
*/
if (function_exists('ma_header') == FALSE) {
	function ma_header($header, $val = NULL) {
		if (is_array($header)) {
			foreach ($header as $k => $v) {
				header($k . ':' . $v);
			}
		}
		else if ($val) {
			header($header . ':' . $val);
		}
		return;
	}
}

/**
 * HTTP Status
 *
 * @param  int
 * @return ($code is NULL = array, $code isset = string, invalid $code = FALSE)
*/
if (function_exists('ma_http_status') == FALSE) {
	function ma_http_status($code = NULL, $set = TRUE) {
		static $http_status = [
			100 => 'Continue',
			101 => 'Switching Protocols',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Moved Temporarily',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Time-out',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Large',
			415 => 'Unsupported Media Type',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Time-out',
			505 => 'HTTP Version not supported',
		];

		// Set header
		if ($set == TRUE && isset($http_status[$code])) {
			$text = $http_status[$code];
			$server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;

			if (substr(php_sapi_name(), 0, 3) == 'cgi') {
				header("Status: {$code} {$text}", TRUE);
			}
			else if ('HTTP/1.1' == $server_protocol || 'HTTP/1.0' == $server_protocol) {
				header($server_protocol." {$code} {$text}", TRUE, $code);
			}
			else {
				header("HTTP/1.1 {$code} {$text}", TRUE, $code);
			}
		}

		// Return
		if ($code == NULL) {
			return $http_status;
		}
		else if (isset($http_status[$code])) {
			return $http_status[$code];
		}
		else {
			return FALSE;
		}
	}
}

/****************************************
 * Validators
****************************************/

/**
 * IS Serialized
 *
 * @author    Chris Smith <code+php@chris.cs278.org>
 * @copyright Copyright (c) 2009 Chris Smith (http://www.cs278.org/)
 * @license   http://sam.zoy.org/wtfpl/ WTFPL
 * @param     string   $value	Value to test for serialized form
 * @param     mixed    $result	Result of unserialize() of the $value
 * @return    boolean  True if $value is serialized data, otherwise FALSE
*/
if (function_exists('ma_is_serialized') == FALSE) {
	function ma_is_serialized($value, &$result = NULL) {
		// Bit of a give away this one
		if (is_string($value) != TRUE) {
			return FALSE;
		}

		// Serialized FALSE, return TRUE. unserialize() returns FALSE on an
		// invalid string or it could return FALSE if the string is serialized
		// FALSE, eliminate that possibility.
		if ($value === 'b:0;') {
			$result = FALSE;
			return TRUE;
		}

		$length = strlen($value);
		$end	= '';

		switch ($value[0]) {
			case 's':
				if ($value[$length - 2] !== '"') {
				return FALSE;
			}
			case 'b': case 'i': case 'd':
				// This looks odd but it is quicker than isset()ing
				$end .= ';';
			case 'a': case 'O':
				$end .= '}';
				if ($value[1] !== ':') {
					return FALSE;
				}

				switch ($value[2]) {
					case 0: case 1: case 2: case 3: case 4:
					case 5: case 6: case 7: case 8: case 9:
					break;
					default:
						return FALSE;
				}
			case 'N':
				$end .= ';';
				if ($value[$length - 1] !== $end[0]) {
					return FALSE;
				}
			break;
			default:
				return FALSE;
		}

		if (($result = @unserialize($value)) === FALSE) {
			$result = NULL;
			return FALSE;
		}
		return TRUE;
	}
}

/**
 * Date Validation
 * http://php.net/manual/en/function.checkdate.php#113205
 *
 * @param  string
 * @param  string
 * @return bool
*/
if (function_exists('ma_date_validation') == FALSE) {
	function ma_date_validation($date, $format = 'Y-m-d H:i:s') {
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}
}

/****************************************
 * Helper functions
****************************************/

/**
 * Array sort by key values
 *
 * http://php.net/manual/en/function.array-multisort.php#100534
 *
 * @param  array
 * @return array
*/
if (function_exists('ma_array_sort') == FALSE) {
	function ma_array_sort($args = []) {
		$args = func_get_args();
		$data = array_shift($args);
		foreach ($args as $n => $field) {
			if (is_string($field)) {
				$tmp = [];
				foreach ($data as $key => $row) {
					$tmp[$key] = $row[$field];
				}
				$args[$n] = $tmp;
			}
		}
		$args[] = &$data;
		call_user_func_array('array_multisort', $args);
		return array_pop($args);
	}
}

/**
 * Language
 *
 * @param  string
 * @param  string
 * @param  array
 * @return string|array on success/bool FALSE on failure
*/
if (function_exists('ma_language') == FALSE) {
	function ma_language($filename = NULL, $language = 'en', $opt = []) {
		$filename = $filename.'.lang.php';

		$path = isset($opt['path']) ? trim($opt['path'], '/') : NULL;
		$file_path = MA_PATH.$path.'/language/'.$language.'/'.$filename;
		if (is_file($file_path) == FALSE) {
			return FALSE;
		}

		$dic = require($file_path);
		if (is_array($dic) == FALSE) {
			return FALSE;
		}

		if (isset($dic['dir']) == FALSE) {
			$dic['dir'] = 'ltr';
		}
		else {
			$dic['dir'] = strtolower($dic['dir']) == 'rtl' ? 'rtl' : 'ltr';
		}
		return $dic;
	}
}

/**
 * Money Format
 *
 * @param int
 * @return string|int
*/
if (function_exists('ma_money') == FALSE) {
	function ma_money($number, $fractional = FALSE) {
		if ($fractional) {
			$number = sprintf('%.2e', $number);
		}
		while (TRUE) {
			$replaced = preg_replace('/(-?\d+)(\d\d\d)/','$1,$2', $number);
			if ($replaced != $number) {
				$number = $replaced;
			}
			else {
				break;
			}
		}
		return $number;
	}
}

/**
 * Time Zone
 *
 * @param string
 * @return string or void
*/
if (function_exists('ma_timezone') == FALSE) {
	function ma_timezone($timezone = NULL) {
		if (empty($timezone)) {
			return date('e');
		}
		else if (date('e') != $timezone) {
			date_default_timezone_set($timezone);
		}
	}
}

/**
 * Random Alphanurmic
 *
 * @param  int
 * @param  array
 * @return string
*/
if (function_exists('ma_random') == FALSE) {
	function ma_random($length = 32, $opt = []) {
		if ($length > 1024) {
			$length = 1024;
		}

		if (isset($opt['characters']) == TRUE) {
			$characters = $opt['characters'];
		}
		else {
			$str = [
				'symbols' => isset($opt['symbols']) == FALSE || $opt['symbols'] == TRUE ? '!@#$%^*()_=/;:><' : NULL,
				'numbers' => isset($opt['numbers']) == FALSE || $opt['numbers'] == TRUE ? '0123456789' : NULL,
				'lowercase' => isset($opt['lowercase']) == FALSE || $opt['lowercase'] == TRUE ? 'abcdefghijklmnopqrstuvwxyz' : NULL,
				'uppercase' => isset($opt['uppercase']) == FALSE || $opt['uppercase'] == TRUE ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : NULL,
				'custom' => isset($opt['custom']) == TRUE ? $opt['custom'] : NULL
			];

			$characters = implode('', $str);
			if (empty($characters)) {
				return '';
			}
		}

		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i=0; $i<$length; ++$i) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
}

/**
 * Exit
 *
 * @param string
 * @param bool
 * @param int
 * @param int
 * @return string or void
*/
if (function_exists('ma_exit') == FALSE) {
	function ma_exit($message = NULL, $http_status = 503, $exite_failure = TRUE) {
		if ($message) {
			echo $message;
		}
		$err = $exite_failure == TRUE ? EXIT_FAILURE : EXIT_SUCCESS;
		if ($http_status != FALSE) {
			ma_http_status($http_status);
		}
		exit($err);
	}
}

/**
 * MyAdmin Info
 *
 * @param  string
 * @return array
*/
function ma_info($calendar = 'persian') {
	$ut = strtotime(MYADMIN['version_update']);
	$tz =& ma_class('date');
	$tz->calendar($calendar);
	$php_v = explode('.', phpversion(), 4);
	$php_v = $php_v[0].'.'.$php_v[1].'.'.$php_v[2];
	return [
		'name'      => 'MyAdmin',
		'developer' => 'PersianIcon Software',
		'copyright' => 'Copyright (C) 2014-'.date('Y').' Persian Icon Software',
		'version'        => MYADMIN['version_major'].'.'.MYADMIN['version_minor'].'.'.MYADMIN['version_path'],
		'version_major'  => MYADMIN['version_major'],
		'version_minor'  => MYADMIN['version_minor'],
		'version_path'   => MYADMIN['version_path'],
		'version_update' => date('l, F d, Y', $ut),
		'version_update_local_time' => $tz->date($ut, 'D d M Y'),
		'database_version'     => MYADMIN['db_version'],
		'environment'          => ucfirst(MA_ENVIRONMENT),
		'db_drive'  => defined('MA_DB') == TRUE ? strtoupper(MA_DB['drive']) : 'Unknown',
		'memcached' => defined('MA_MEMCACHED') == TRUE && MA_MEMCACHED != FALSE ? 'Enabled' : 'Disabled',
		'timezone'  => date('e'),
		// Server
		'document_root' => $_SERVER['DOCUMENT_ROOT'],
		'php_version'   => $php_v,
		'zend_version'  => zend_version(),
		'http_host' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'Unknown',
		'gateway_interface' => isset($_SERVER['GATEWAY_INTERFACE']) ? $_SERVER['GATEWAY_INTERFACE'] : 'Unknown',
		'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
		'server_protocol' => isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'Unknown',
		'server_addr'  => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'Unknown',
		'server_port'  => isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 'Unknown',
		'server_admin' => isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : 'Unknown',
		'server_signature' => isset($_SERVER['SERVER_SIGNATURE']) ? strip_tags($_SERVER['SERVER_SIGNATURE']) : 'Unknown'
	];
}

/**
 * PrintR - dev helper
 *
 * @param  array|object
 * @return string or void
*/
if (function_exists('printr') == FALSE) {
	function printr($args) {
		if (is_array($args) == FALSE && is_object($args) == FALSE) {
			return;
		}
		echo "<pre class=\"ma-dev-pre\">\n";
		print_r($args);
		echo "\n</pre>\n";
	}
}