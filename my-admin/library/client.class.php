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
 * Input Class
 *
 * @modified : 17 September 2018
 * @created  : 12 April 2015
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class ma_client
{
	public $csrf_session_name = '_csrfKey';
	public $csrf_input_name = 'csky';
	public $security;
	protected $client_cache = [];

	/**
	 * Constructor
	*/
	public function __construct() {
		$this->security =& ma_class('security');
		$this->clear_cache();
	}

	/**
	 * Clear cache
	*/
	public function clear_cache() {
		$this->client_cache = [
			'ip' => NULL,
			'domain' => NULL,
			'url_path' => NULL,
			'user_agent' => NULL
		];
	}

	/******************************
	 * HTTP Schema/Headers
	******************************/

	/**
	 * HTTP schema
	 *
	 * @param string on http/bool FALSE on CLI
	*/
	public function http_schema() {
		if (!empty($this->security->fetch_array($_SERVER, 'HTTPS', FALSE, 15))) {
			return 'https';
		}
		return 'http';
	}

	/**
	 * HTTP schema (X-Forward/like CloudFlare)
	 *
	 * @param TRUE on proxy/bool FALSE on no-proxy
	*/
	public function http_schema_x() {
		$hx = $this->security->fetch_array($_SERVER, 'HTTP_X_FORWARDED_PROTO', FALSE, 15);
		if (!empty($hx) && strtolower($hx) == 'https') {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Get HTTP header
	 *
	 * @param string
	 * @param string
	 * @param int
	 * @param string|int on success/bool on FALSE on failure
	*/
	public function http_header($key_name, $security_filter = NULL, $max_length = 0) {
		if (MA_CLI == TRUE) {
			return FALSE;
		}

		$key_name = strtolower($key_name);
		foreach (getallheaders() as $k => $v) {
			if (strtolower($k) != $key_name) {
				continue;
			}
			// Found
			if ($security_filter != NULL) {
				$v = $this->security->filter($v, $security_filter, 0);
				if ($v == FALSE) {
					return FALSE;
				}
			}
			if ($max_length > 0 && strlen($v) > $max_length) {
				return FALSE;
			}
			return $v;
		}
	}

	/******************************
	 * Port
	******************************/

	/**
	 * Port
	 *
	 * @param int >0 on http/=0 FALSE on CLI
	*/
	public function server_port() {
		$p = $this->security->fetch_array($_SERVER, 'SERVER_PORT', 'int', 6);
		if (!empty($p)) {
			return $p;
		}
		return 0;
	}

	/**
	 * Port (X-Forward/like CloudFlare)
	 *
	 * @param int >0 on proxy/=0 on no-proxy
	*/
	public function server_port_x() {
		$px = $this->security->fetch_array($_SERVER, 'HTTP_X_FORWARDED_PORT', 'int', 6);
		if (empty($px) == FALSE) {
			return $px;
		}
		return 0;
	}

	/******************************
	 * Get/Post
	******************************/

	/**
	 * _GET
	 *
	 * @param string
	 * @param string security filters name
	 * @param int the maximum length
	 * @return void
	*/
	public function get($index, $security_filter = 'xss', $max_length = 100) {
		return $this->security->fetch_array($_GET, $index, $security_filter, $max_length);
	}

	/**
	 * _POST
	 *
	 * @param string
	 * @param sttring - security filters name
	 * @param int the maximum length
	 * @return void
	*/
	public function post($index, $security_filter = 'xss', $max_length = 100) {
		return $this->security->fetch_array($_POST, $index, $security_filter, $max_length);
	}

	/**
	 * _GET or _POST
	 *
	 * @param string
	 * @param sttring - security filters name
	 * @param int the maximum length
	 * @return void
	*/
	public function get_post($index, $security_filter = 'xss', $max_length = 100) {
		if (isset( $_POST[$index] )) {
			return $this->security->fetch_array($_POST, $index, $security_filter, $max_length);
		}
		return $this->security->fetch_array($_GET, $index, $security_filter, $max_length);
	}

	/******************************
	 * Domain/URL
	******************************/

	/**
	 * Domain
	 *
	 * @param string/empty strong on failure
	*/
	public function domain($opt = []) {
		if ($this->client_cache['domain']) {
			return $this->client_cache['domain'];
		}

		$filter = !isset($opt['filter']) || $opt['filter'] == TRUE ? 'alphabet' : FALSE;
		$domain = $this->security->fetch_array($_SERVER, 'SERVER_NAME', $filter, 150);
		if (empty($domain)) {
			return '';
		}
		$domain = strtolower($domain);
		if (!isset($opt['with_www']) || $opt['with_www'] == FALSE) {
			$domain = preg_replace('/www\./i', '', $domain);
		}
		$this->client_cache['domain'] = $domain;
		return $domain;
	}

	/**
	 * URL path
	 *
	 * @param string or NULL
	*/
	public function url_path($opt = []) {
		if ($this->client_cache['url_path'] != NULL) {
			return $this->client_cache['url_path'];
		}

		$max_len = isset($opt['max_length']) ? $opt['max_length'] : 250;
		$filter = !isset($opt['filter']) || $opt['filter'] == TRUE ? 'url' : FALSE;

		$url = $this->security->fetch_array($_SERVER, 'REQUEST_URI', FALSE, $max_len);
		if ($url == FALSE) {
			return NULL;
		}

		// QueryString
		$url_rem_que = explode('?', $url, 2);
		if (isset($url_rem_que[0])) {
			$url = $url_rem_que[0];
		}
		$url_rem_que = NULL;

		if (isset($opt['remove_path']) && $opt['remove_path'] != FALSE) {

			/***
			foreach ($opt['remove_path'] as $p) {
				echo strlen($p) . "<br>\n";
				if (substr($url, strlen($p)) == $p) {
					$url = substr_replace($p, '', 0, $url);
					break;
				}
			}
			***/
			$url = str_replace($opt['remove_path'], '', $url);
		}

		if (empty($url)) {
			$url = NULL;
		}
		$this->client_cache['url_path'] = $url;
		return $url;
	}

	/******************************
	 * Cookie
	******************************/

	/**
	 * Get Cookie
	 *
	 * @param string
	 * @param sttring - security filters name
	 * @param string the number of seconds until expiration
	 * @param string the cookie prefix
	 * @param int the maximum length
	 * @return void
	*/
	public function cookie($index = '', $security_filter = 'xss', $max_length = 100, $prefix = '') {
		$cp = ma_config('cookie_prefix');
		if ($prefix == '' && !empty($cp)) {
			$prefix = ma_config('cookie_prefix');
			if ($prefix == FALSE) {
				$prefix = NULL;
			}
		}

		$name = $prefix.$index;
		return $this->security->fetch_array($_COOKIE, $name, $security_filter, $max_length);
	}

	/**
	 * Set Cookie
	 *
	 * @param string
	 * @param string the value
	 * @param string the number of seconds expiration
	 * @param string the cookie domain
	 * @param string the cookie path
	 * @param string the cookie prefix
	 * @param bool TRUE makes the cookie secure
	 * @return bool TRUE on success/bool FALSE on failure
	*/
	public function set_cookie($name = '', $value = '', $expire = '', $http = TRUE, 
							   $domain = '', $path = '/', $prefix = '', $secure = FALSE) {
		// Prefix
		$cp = ma_config('cookie_prefix');
		if ($prefix == '' && !empty($cp)) {
			$prefix = NULL;
		}
		// Domain
		if ($domain == '') {
			$domain = ma_config('cookie_domain');
			if ($domain == FALSE) {
				$domain = NULL;
			}
		}
		// Path
		if ($path == '/' && ma_config('cookie_path') != FALSE) {
			$path = ma_config('cookie_path');
			if ($path == FALSE) {
				$path = NULL;
			}
		}
		// Secure
		if ($secure == FALSE && ma_config('cookie_secure_flag') != FALSE) {
			$secure = ma_config('cookie_secure_flag');
			if ($secure == FALSE) {
				$secure = NULL;
			}
		}

		if (is_numeric($expire) == FALSE) {
			$expire = time() - 86500;
		}
		else {
			$expire = ($expire > 0) ? time() + $expire : 0;
		}

		return setcookie($prefix.$name, $value, $expire, $path, $domain, $secure, $http);
	}

	/******************************
	 * Info
	******************************/

	/**
	 * Get User Agant Info
	 *
	 * @return string on success/bool FALSE on failure
	*/
	public function user_agent() {
		if ($this->client_cache['user_agent']) {
			return $this->client_cache['user_agent'];
		}

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			$user_agent = htmlentities((string)$_SERVER['HTTP_X_REQUESTED_WITH']);
		}
		else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$user_agent = htmlentities((string)$_SERVER['HTTP_X_FORWARDED_FOR']);
		}
		else if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = htmlentities((string)$_SERVER['HTTP_USER_AGENT']);
		}
		else {
			$user_agent = FALSE;
		}
		$this->client_cache['user_agent'] = $user_agent;
		return $user_agent;
	}

	/**
	 * Is ajax?
	 *
	 * @return bool TRUE on yes/bool FALSE on no
	*/
	public function is_ajax() {
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) == FALSE || 
		   'xmlhttprequest' != strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			return FALSE;
		}
		return TRUE;
	}

	/******************************
	 * IP
	******************************/

	/**
	 * Client IP
	 *
	 * @return string on success/bool FALSE on failure
	*/
	public function ip() {
		if ($this->client_cache['ip']) {
			return $this->client_cache['ip'];
		}

		$user_ip = $ip = FALSE;
		// Check ip - share internet
		if (isset($_SERVER['HTTP_CLIENT_IP']) && 
		   !empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = is_string($_SERVER['HTTP_CLIENT_IP']) 
				? $_SERVER['HTTP_CLIENT_IP'] : FALSE;
			$user_ip = $this->security->filter_ip($ip);
		}
		// Check ip - share internet - Proxy (X-Foeward)
		else if (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && 
				!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
			$ip = is_string($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) ? 
				$_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] : FALSE;
			$user_ip = $this->security->filter_ip($ip);
		}
		// To check ip - Pass proxy
		else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && 
				!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = is_string($_SERVER['HTTP_X_FORWARDED_FOR']) 
				? $_SERVER['HTTP_X_FORWARDED_FOR'] : FALSE;
			$user_ip = $this->security->filter_ip($ip);
		}
		// Normal
		else {
			$ip = (isset($_SERVER['REMOTE_ADDR']) && 
				   is_string($_SERVER['REMOTE_ADDR'])) 
				? $_SERVER['REMOTE_ADDR'] : FALSE;
			$user_ip = $this->security->filter_ip($ip);
		}

		if ($user_ip != FALSE) {
			$this->client_cache['ip'] = $user_ip;
		}
		return $user_ip;
	}

	/******************************
	 * CSRF Protection
	******************************/

	/**
	 * CSRF generator
	 *
	 * @param string/NULL
	 * @return string
	*/
	public function csrf_generator($type = NULL) {
		//if (session_id() === '') {
		//	return FALSE;
		//}
		$csrf_key = mt_rand(9999,99999999);
		$_SESSION[$this->csrf_session_name] = $csrf_key;

		if ($type == 'html') {
			return '<input type="hidden" value="'.$csrf_key.'" name="'.$this->csrf_input_name.'">';
		}
		return $csrf_key;
	}

	/**
	 * CSRF validator
	 *
	 * @param boolen
	 * @param string get or post or csrf value
	 * @return string
	*/
	public function csrf_validator($unset_session = TRUE, $request = 'post') {
		if (isset($_SESSION[$this->csrf_session_name]) == FALSE) {
			return FALSE;
		}

		if (strlen($_SESSION[$this->csrf_session_name]) > 10) {
			unset($_SESSION[$this->csrf_session_name]);
			return FALSE;
		}

		$sess = 1*$_SESSION[$this->csrf_session_name];
		if ($unset_session == TRUE) {
			unset($_SESSION[$this->csrf_session_name]);
		}

		if ($request == strtolower('get')) {
			$inp = $this->get($this->csrf_input_name, 'int', 10);
		}
		else if ($request == strtolower('post')) {
			$inp = $this->post($this->csrf_input_name, 'int', 10);
		}
		else {
			$inp = $request;
		}

		if ($sess != $inp) {
			return FALSE;
		}
		return TRUE;
	}

	/******************************
	 * Process
	******************************/

	/**
	 * Process ID
	 *
	 * @return int >0 success/=0 failure
	*/
	public function pid() {
		if (function_exists('posix_getpid') == TRUE) {
			return posix_getpid();
		}
		else if (function_exists('getmygid') == TRUE) {
			return getmygid();
		}
		return 0;
	}
}