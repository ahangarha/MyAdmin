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
 * Error Handling Class
 *
 * @modified : 21 November 2017
 * @created  : 27 January 2015
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class ma_error_handler
{
	// Errors list
	protected $errors = [];

	// Sending message type filter
	public $error_type_mail_filter = ['E_NOTICE', 'E_USER_NOTICE', 'E_DEPRECATED', 'E_USER_DEPRECATED'];

	// Save sendign mail time
	protected $time_file = NULL;

	// after sending => TRUE
	protected $sending_mail = FALSE;

	/**
	 * Set PHP Error
	*/
	public function error($errno, $errstr, $errfile, $errline) {
		$type = $this->php_error_type($errno);

		// Log
		$error = $type.' - '.$errstr.' on '.$errfile.' line '.$errline;
		ma_save_log('error', $error);

		// Mail
		if (in_array($type, $this->error_type_mail_filter) == FALSE) {
			$this->sending_message_to_admin($errno);
		}

		// Show
		if (MA_ENVIRONMENT == 'development') {
			echo $error;
		}
		else {
			// This error code is not included in error_reporting
			if (!(error_reporting() & $errno)) {
				return;
			}
		}
	}

	/**
	 * Init
	 *
	 * @return void
	*/
	public function init() {
		return;
	}

	/**
	 * Error Shutdown
	 * Fatal Errors
	*/
	public function shotdown() {
		$error = error_get_last();
		if ($error != NULL) {
			$errfile = $error['file'];
			$errline = $error['line'];
			$errstr  = $error['message'];
			$type    = $error['type'];
			$errno   = E_CORE_ERROR;
			$error_message = 'FATAL ERROR - ' . $errstr. ' - ' . $errfile .  ' on line ' . $errline;

			// Log
			ma_save_log('error', $error_message);

			// Display
			if (MA_ENVIRONMENT != 'development') {
				$error_message = NULL;
			}

			// Mail
			$this->sending_message_to_admin();

			$this->display_error( $error_message );
			exit(EXIT_FAILURE);
		}
	}

	/**
	 * Set Error
	 * Use simple mechanism to save error for developers and log
	 *
	 * @param  int
	 * @param  string
	 * @param  bool
	 * @return void
	*/
	public function set_error($error_id, $error_message, $log_level = NULL) {
		$trace = $this->debug_trace();
		$this->errors[] = array(
			'id'      => $error_id,
			'message' => $error_message,
			'file'    => $trace['file'],
			'line'    => $trace['line']
		);

		if ($log_level == TRUE) {
			ma_save_log($log_level, $error_id.' > '.$error_message . '('.$trace['file'].' on line '.$trace['line'].')');
		}
	}

	/**
	 * Get Errors
	 *
	 * @return mixed NULL/array
	*/
	public function get_error() {
		if ( 0 == count($this->errors) ) {
			return NULL;
		}

		return $this->errors;
	}

	/**
	 * Error Shutdown
	 * Fatal Errors
	 *
	 * @return bool
	*/
	protected function display_error($message = NULL, $http_code = 500) {
		show_error($http_code, $message, FALSE, 'php-errors');
	}

	/**
	 * Error File Trace
	 *
	 * @param  int
	 * @return array
	*/
	public function debug_trace($level = 3) {
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $level);

		$return_id = ($level - 1);
		if ($return_id < 0) {
			$return_id = 0;
		}

		if (isset( $trace[$return_id])) {
			return [
				'file' => (string) $trace[2]['file'],
				'line' => (int) $trace[2]['line']
			];
		}

		return ['file'=> NULL, 'line'=> NULL];
	}

	/**
	 * PHP Error Types
	 *
	 * @param  int errorno
	 * @return string
	*/
	public function php_error_type($errno) {
		switch ($errno) {
			case 1:     $type = 'E_ERROR'; break;
			case 2:     $type = 'E_WARNING'; break;
			case 4:     $type = 'E_PARSE'; break;
			case 8:     $type = 'E_NOTICE'; break;
			case 16:    $type = 'E_CORE_ERROR'; break;
			case 32:    $type = 'E_CORE_WARNING'; break;
			case 64:    $type = 'E_COMPILE_ERROR'; break;
			case 128:   $type = 'E_COMPILE_WARNING'; break;
			case 256:   $type = 'E_USER_ERROR'; break;
			case 512:   $type = 'E_USER_WARNING'; break;
			case 1024:  $type = 'E_USER_NOTICE'; break;
			case 2048:  $type = 'E_STRICT'; break;
			case 4096:  $type = 'E_RECOVERABLE_ERROR'; break;
			case 8192:  $type = 'E_DEPRECATED'; break;
			case 16384: $type = 'E_USER_DEPRECATED'; break;
			case 32767: $type = 'E_ALL'; break;
			default:    $type = 'E_UNKNOWN'; break;
		}
		return $type;
	}

	/** 
	 * Sending mail to admin
	 *
	 * @param  string
	 * @return void
	*/
	public function sending_message_to_admin($message = 'Error') {
		if ($this->sending_mail == TRUE || $this->mail_time() == FALSE || defined('MA_ADMIN') == FALSE) {
			return;
		}

		// System Mail Address
		if (defined('MA_DOMAIN') == FALSE) {
			preg_match("/[^\.\/]+\.[^\.\/]+$/", $_SERVER['HTTP_HOST'], $host);
			if (isset($host[0])) {
				$domain = $host[0];
			}
			else {
				$domain = 'unknown';
			}
		}
		else {
			$domain = MA_DOMAIN;
		}

		// Submit
		$headers  = "From: MyAdminCMS Log System [".$domain."] <nobody@".$domain.">\r\n";
		$headers .= "Content-type: text/html; charset=utf-8\r\n";
		@mail(MA_ADMIN, ''.$domain.' - Error Log', $message, $headers, '-f nobody@'.$domain);
		$this->sending_mail = TRUE;

		// Save Time
		if (is_null($this->time_file) == FALSE) {
			$fp = fopen( $this->time_file, 'wb');
			fwrite($fp, time() );
			fclose($fp);
			@chmod($this->time_file, MA_FILE_W_MODE);
		}
	}

	/**
	 * Calc sendign message period
	 *
	 * @return bool TRUE on success/FALSE on failure
	*/
	protected function mail_time() {
		if (defined('MA_LOG_PATH') == FALSE) {
			return FALSE;
		}
		
		if (MA_ERROR_SEND_MAIL_TIMEOUT <= 0) {
			return FALSE;
		}

		$this->time_file = MA_LOG_PATH.'/smtime.php';
		$last_time = 0;
		if (is_file($this->time_file)) {
			$last_time = 1*file_get_contents($this->time_file);
		}

		if ($last_time <= 0) {
			return TRUE;
		}

		$do_time = MA_ERROR_SEND_MAIL_TIMEOUT + $last_time;
		if ($do_time > time()) {
			return FALSE;
		}
		else {
			return TRUE;
		}
	}
}