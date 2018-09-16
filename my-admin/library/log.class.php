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
 * Log Class
 *
 * @modified : 04 December 2017
 * @created  : 25 January 2015
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class ma_log
{
	protected $config = [];
	protected $log_format;
	protected $log_path;
	protected $levels = ['error'=> 1, 'info'=> 2, 'security'=> 3, '404'=> 4];
	protected $date_fmt = 'Y-m';
	protected $benchmark;
	protected $log_files = [];
	public $is_file_check = TRUE; // overload

	/**
	 * Constructor
	*/
	function __construct() {
		$this->benchmark =& ma_class('benchmark');
		$this->benchmark->mark('save_log_start');

		// Max File Size
		$max_fileszie_txt = ma_config('log_max_fize_size');
		if ($max_fileszie_txt <= 0) {
			$max_fileszie_txt = 1048576;
		}

		$this->config = [
			'log_level' => MA_LOG_LEVEL,
			'debug'     => MA_DEBUG,
			'log_path'  => FALSE,
			'date_name' => FALSE,
			'max_fileszie_text' => 1*$max_fileszie_txt
		];

		$this->log_format = 'log';
		$this->log_path();
	}

	/**
	 * Init
	*/
	public function init() {
		return;
	}

	/**
	 * Save
	 *
	 * @param  string
	 * @param  string
	 * @param  array
	 * @return bool
	*/
	public function save($level, $message, $custom_detail = []) {
		// Level
		$level = strtolower($level);

		// Saved?
		$saved = $this->be_saved($level);
		if ($saved == FALSE) {
			return FALSE;
		}

		// Log Filename
		$filename = $this->log_filename($level);
		if (!isset($this->log_files[$filename])) {
			$large_file = $this->is_large_file($filename);
			if ($large_file == TRUE) {
				$filename = $this->log_filename($level, TRUE);
			}

			$this->log_files[$filename] = TRUE;
		}

		// Detail
		$detail = $this->set_log_detail($level, $message, $custom_detail);

		// Save
		$this->save_text($filename, $detail);
		return TRUE;
	}

	/**
	 * Be saved?
	 *
	 * @param  string
	 * @return bool FALSE no/bool TRUE yes
	*/
	protected function be_saved($level) {
		if ($level == 'debug') {
			if ($this->config['debug'] == 0) {
				return FALSE;
			}
		}
		else if ($this->config['log_level'] != FALSE && isset($this->levels[$level]) == TRUE) {
			if ($this->config['log_level'] != -1) {
				if (is_array($this->config['log_level'])) {
					if (in_array($this->levels[$level], $this->config['log_level']) == FALSE) {
						return FALSE;
					}
				}
				else if ($this->levels[$level] != $this->config['log_level']) {
					return FALSE;
				}
			}
		}
		else {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Log Details
	 *
	 * @param  string
	 * @param  string
	 * @param  array
	 * @return array
	*/
	protected function set_log_detail($level, $message, $custom_detail = []) {
		if (is_array($message)) {
			$message = implode('__', $message);
		}
		else {
			$message = str_replace(['[', ']', ';'], ['{', '}', ':'], $message);
		}

		$default = [
			'level'       => $level,
			'date'        => isset($custom_detail['date']) ? $custom_detail['date'] : date('r'), // RFC 2822
			'time'        => isset($custom_detail['date']) ? $custom_detail['time'] : time(),
			'message'     => trim($message),
			'ip'          => isset($custom_detail['ip']) ? $custom_detail['ip'] : FALSE,
			'remote_port' => isset($custom_detail['remote_port']) ? 1*$custom_detail['remote_port'] : FALSE,
			'server_port' => isset($custom_detail['server_port']) ? 1*$custom_detail['server_port'] : FALSE,
			'user_agent'  => isset($custom_detail['user_agent']) ? $custom_detail['user_agent'] : FALSE,
			'uri'         => isset($custom_detail['uri']) ? $custom_detail['uri'] : FALSE,
			'user_id'     => isset($custom_detail['user_id']) ? $custom_detail['user_id'] : 0
		];

		// Input (loop fix)
		// Check (input loop fix)
		if ($default['ip'] == FALSE && $default['remote_port'] == FALSE && $default['server_port'] == FALSE) {
			$client =& ma_class('client');

			// IP
			if ($default['ip'] == FALSE) {
				$default['ip'] = $client->ip();
			}

			// User Agent
			if ($default['user_agent'] == FALSE) {
				$default['user_agent'] = $client->user_agent();
			}

			// URI
			if ($default['uri'] == FALSE) {
				$http = $client->http_schema(TRUE);
				$protocol = $http == 'https' ? 'https://' : 'http://';
				$host = $_SERVER['HTTP_HOST'];
				$uri = trim($_SERVER['REQUEST_URI']);
				$default['uri'] = (string) $protocol.$host.$uri;
			}
		}
		else {
			if ($default['uri'] == FALSE && isset($_SERVER['REQUEST_URI'], $_SERVER['QUERY_STRING'])) {
				$default['uri'] = $_SERVER['HTTP_HOST'].''.$_SERVER['REQUEST_URI'];
			}
		}

		// Remote port
		if (FALSE == $default['remote_port'] && isset($_SERVER['REMOTE_PORT'])) {
			$default['remote_port'] = 1*$_SERVER['REMOTE_PORT'];
		}
		// Server port
		if (FALSE == $default['server_port'] && isset($_SERVER['SERVER_PORT'])) {
			$default['server_port'] = 1*$_SERVER['SERVER_PORT'];
		}
		// User ID
		if (0 == $default['user_id'] && defined('USER_ID')) {
			$default['user_id'] = 1*USER_ID;
		}
		// Benchmark
		$this->benchmark->mark('save_log_end');
		$default['benchmark'] = $this->benchmark->elapsed_time('save_log_start', 'save_log_end');

		return $default;
	}

	/**
	 * Save Text File
	 *
	 * @param  array
	 * @return void
	*/
	protected function save_text($filename, $detail) {
		$msg  = '['.$detail['date'].']';
		$msg .= '['.$detail['message'].']';
		$msg .= '['.$detail['ip'] . ':' . $detail['remote_port'].']';
		$msg .= '[port '.$detail['server_port'].']';
		$msg .= '[agent '.$detail['user_agent'].']';
		$msg .= '[uri '.$detail['uri'].']';
		$msg .= '[uid '. $detail['user_id'].']';
		$msg .= '[benchmark '.$detail['benchmark']."];\n";

		$file = $this->log_path.$filename;
		$new_file = is_file($file) ? FALSE : TRUE;
		if (file_put_contents($file, $msg, FILE_APPEND | LOCK_EX) == FALSE) {
			return FALSE;
		}
		if ($new_file == TRUE) {
			chmod($file, MA_FILE_W_MODE);
		}
	}

	/**
	 * Set Log Path
	 *
	 * @return void
	*/
	protected function log_path() {
		if ($this->config['log_path'] == TRUE) {
			$this->log_path = rtrim( $this->config['log_path'], '/') . '/';
		}
		else {
			$this->log_path = MA_PATH.'/tmp/log/';
		}

		$this->log_path = str_replace('\\', '/', $this->log_path);
		if (defined('MA_LOG_PATH') == FALSE) {
			define('MA_LOG_PATH', $this->log_path);
		}

		if (is_dir(MA_LOG_PATH) == FALSE) {
			$st = mkdir(MA_LOG_PATH, MA_DIR_W_MODE, TRUE);
			if ($st == FALSE) {
				exit('error_log_directory_broken');
			}
		}
	}

	/**
	 * Log Filename
	 *
	 * @param  string
	 * @param  bool
	 * @return string
	*/
	protected function log_filename($level, $rename = FALSE) {
		$type = '.' . $this->log_format;

		if ($this->config['date_name'] == TRUE) {
			$filename = $level.'-'.date($this->date_fmt);
		}
		else {
			$filename = $level;
		}

		if ($rename == TRUE) {
			for ($i=1; $i<100; ++$i) {
				$new_filename = $filename.'-'.$i;
				if (is_file($this->log_path.$new_filename.$type)) {
					$this->is_file_check = FALSE;
					if ($this->is_large_file($new_filename.$type) == TRUE) {
						continue;
					}
					else {
						$filename = $new_filename;
						break;
					}
				}
				else {
					$filename = $new_filename;
					break;
				}
			}
		}

		return $filename.$type;
	}

	/**
	 * Log Filesize
	 *
	 * @param  string
	 * @return bool true on Yes/false on no
	*/
	protected function is_large_file($filname) {
		if ($this->is_file_check == TRUE) {
			if (is_file($this->log_path.$filname) == FALSE) {
				return FALSE;
			}
		}

		$filesize = filesize($this->log_path.$filname);
		if ($filesize == FALSE) {
			return FALSE;
		}

		$max_file_size = $this->config['max_fileszie_text'];
		if ($filesize > $max_file_size) {
			return TRUE; // yes, is large
		}
		return FALSE;
	}

	/**
	 * Log Directory list
	 *
	 * @return array on success/bool FALSE on failure
	*/
	public function get_files() {
		if (!$this->log_path) {
			$this->log_path();
		}

		if (is_dir($this->log_path) == FALSE) {
			return FALSE;
		}

		$files = [];
		$dir = scandir( $this->log_path );
		$date =& load_class('date');
		$conv =& load_class('converter');
		$conv->decimals = 2;

		foreach ($dir as $f) {
			if ('.' == $f || '..' == $f) {
				continue;
			}

			// Type
			$lt = explode('.', $f);
			if ($this->log_format != end($lt)) {
				continue;
			}

			$file_size = @filesize($this->log_path.$f);
			$files[] = [
				'file'  => $f,
				'size'  => $file_size,
				'size_digi' => $conv->digital($file_size)
			];
		}

		return $files;
	}

	/**
	 * Get Log File
	 *
	 * @return or array on success/bool FALSE on failure
	*/
	public function get_file($log_filename) {
		$conv =& ma_class('converter');
		$conv->decimals = 2;

		if (!$this->log_path) {
			$this->log_path();
		}

		$file = $this->log_path.$log_filename;
		if (is_file($file) == FALSE) {
			return FALSE;
		}

		// File size
		$file_size = filesize($file);

		// Content
		$content = file_get_contents($file);
		if (empty($content)) {
			$content = '';
		}

		return [
			'file' => $log_filename,
			'size' => $file_size,
			'size_digi' => $conv->digital($file_size),
			'content' => $content
		];
	}

	/**
	 * Delete log
	 *
	 * @return boolean
	*/
	public function delete_file($log_filename) {
		if (!$this->log_path) {
			$this->log_path();
		}

		$log_filename = $this->log_path.$log_filename;
		if (is_file($log_filename) == FALSE) {
			return FALSE;
		}

		$unlink = unlink($log_filename);
		if ($unlink == FALSE) {
			return FALSE;
		}
		else {
			return TRUE;
		}
	}

	/**
	 * Download
	 *
	 * @return bool TRUE on success/FALSE on failure
	*/
	public function download($log_filename) {
		if (!$this->log_path) {
			$this->log_path();
		}

		$log_file = $this->log_path.$log_filename;
		if (is_file($log_file) == FALSE) {
			return FALSE;
		}

		$file_size = filesize($log_file);
		if ($file_size < 0) {
			$file_size = 0;
		}

		header('Cache-Control: no-cache, private');
		header('Content-Type: application/force-download');
		header('Content-Transfer-encoding: binary');
		header('Content-Disposition: attachment; filename="'.$log_filename.'"');
		header('Content-Length: '.$file_size);
		readfile($log_file);
		return TRUE;
	}
}