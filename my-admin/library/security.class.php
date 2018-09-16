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
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2018, British Columbia Institute of Technology
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2018, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
*/

defined('MA_PATH') OR exit('Restricted access');

/**
 * Security
 *
 * @modified : 26 July 2018
 * @created  : 27 July 2012
 * @since    : version 0.1
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

/**
 * References
 *
 * @http://php.net/manual/en/filter.filters.php
 * @https://www.ietf.org/rfc/rfc2396.txt
 * @https://www.ietf.org/rfc/rfc3986.txt
 * @http://www.ascii.cl/htmlcodes.htm
*/

class ma_security
{
	/**
	 * Constructor
	*/
	public function __construct() {
		$this->on_load();
		return;
	}

	/**
	 * onLoad
	 *
	 * @return void
	*/
	public function on_load() {
		$_SERVER['QUERY_STRING'] = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : FALSE;
		$_SERVER['REQUEST_URI']  = isset($_SERVER['REQUEST_URI'])  ? (string) $_SERVER['REQUEST_URI'] : FALSE;
		$_SERVER['REMOTE_PORT']  = isset($_SERVER['REMOTE_PORT']) ? (int) 1*$_SERVER['REMOTE_PORT'] : 0;
		$_SERVER['SERVER_PORT']  = isset($_SERVER['SERVER_PORT']) ? (int) 1*$_SERVER['SERVER_PORT'] : 0;
		$_SERVER['HTTP_REFERER'] = isset($_SERVER['HTTP_REFERER']) && empty($_SERVER['HTTP_REFERER']) == FALSE ? 
			(string) $_SERVER['HTTP_REFERER'] : FALSE;
	}

	/**
	 * Clear cache
	*/
	public function cache_clear() {
		$this->on_load();
		return;
	}

	/**
	 * Filter
	 *
	 * @param string or int
	 * @param string
	 * @param int
	 * @param bool
	 * @return mixed
	*/
	public function filter($str, $filter_name = 'xss', $max_length = 100, $quote_filter = FALSE) {
		if (is_array($str) == TRUE) {
			return FALSE;
		}

		if ($max_length > 0 && $this->length_check($str, $max_length) == FALSE) {
			return FALSE;
		}

		$mthd = 'filter_'.$filter_name;
		if (method_exists($this, $mthd) == TRUE) {
			$str = $this->{$mthd}($str);
			if ($quote_filter == TRUE) {
				$str = $this->filter_quote($str);
			}
			return $str;
		}
		else {
			ma_exit('error_04::security::filter_not_exists::'.$filter_name);
		}
	}

	/**
	 * XSS clean
	 *
	 * @param string
	 * @return string
	*/
	public function filter_xss($str, $max_length = 0) {
		if (is_array($str)) {
			return '';
		}

		if ($max_length > 0 && $this->length_check($str, $max_length) == FALSE) {
			return '';
		}

		$str = $this->remove_bad_characters(strip_tags(trim($str)));
		$str = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $str);
		$str = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $str);
		$str = str_replace(['"', "'"] , '', $str);
		$str = html_entity_decode($str, ENT_COMPAT, 'UTF-8');

		// Remove any attribute starting with "on" or xmlns
		$str = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $str);

		// Remove javascript: and vbscript: protocols
		$str = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $str);

		$str = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $str);

		$str = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $str);

		// Remove namespaced elements (we do not need them)
		$str = preg_replace('#</*\w+:\w[^>]*+>#i', '', $str);
		do {
			// Remove really unwanted tags
			$old_data = $str;
			$str = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml|vbscript|wscript|jscript|vbs|expression)[^>]*+>#i', '', $str);
		} while ($old_data !== $str);

		$str = htmlspecialchars($str);
		return $str;
	}

	/**
	 * Accept only the english alpha-nurmic
	 *
	 * @param string
	 * @return string on success/bool FALSE on failure
	*/
	public function filter_alphabet($str, $max_length = 0) {
		if ($max_length > 0 && $this->length_check($str, $max_length) == FALSE) {
			return FALSE;
		}

		$str = (string) htmlentities(trim($str));
		if (preg_match("/^[a-zA-Z0-9-+_.]+$/", $str) != 1) {
			return FALSE;
		}
		return $str;
	}

	/**
	 * Int filter
	 *
	 * @param string|int
	 * @return int on success/bool FALSE on failure
	*/
	public function filter_int($int, $max_length = 0) {
		if ($max_length > 0 && $this->length_check($int, $max_length) == FALSE) {
			return FALSE;
		}
		$int = trim($int);
		if (filter_var($int, FILTER_VALIDATE_INT) == FALSE) {
			return FALSE;
		}
		if (preg_match("/^[0-9]+$/", $int) != 1) {
			return FALSE;
		}
		return (int) $int;
	}

	/**
	 * Numeric filter
	 *
	 * @param string|int
	 * @return string|int on success/bool FALSE on failure
	*/
	public function filter_numeric($no, $max_length = 0) {
		if ($max_length > 0 && $this->length_check($no, $max_length) == FALSE) {
			return FALSE;
		}
		$no = trim($no);
		if (is_numeric($no) == FALSE) {
			return FALSE;
		}
		if (preg_match("/^[0-9.]+$/", $no) != 1) {
			return FALSE;
		}
		return $no;
	}

	/**
	 * Filter email address (validation)
	 *
	 * @param  string
	 * @param  int
	 * @param  bool
	 * @return string on success/bool FALSE on failure
	*/
	public function filter_email($email, $max_length = 0, $tolower = FALSE) {
		if ($max_length > 0 && $this->length_check($email, $max_length) == FALSE) {
			return FALSE;
		}
		$email = trim($email);
		if ($tolower == TRUE) {
			$email = strtolower($email);
		}
		if (!preg_match('|^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,6}$|i', $email)) {
			return FALSE;
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return $email;
		}
		return FALSE;
	}

	/**
	 * Filter email
	 * Alliance *filter_email()
	 *
	 * @return *filter_email()
	*/
	public function filter_mail($email, $max_length = 0, $tolower = FALSE) {
		return $this->filter_email($email, $max_length, $tolower);
	}

	/**
	 * URL filter (this solution is not unicode-safe and not XSS-safe)
	 *
	 * @param  string
	 * @return string on success/bool FALSE on failure
	*/
	public function filter_url($url, $max_length = 0) {
		if ($max_length > 0 && $this->length_check($url, $max_length) == FALSE) {
			return FALSE;
		}
		$url = filter_var(trim($url), FILTER_VALIDATE_URL);
		if ($url == FALSE) {
			return FALSE;
		}
		return (string) $url;
	}

	/**
	 * Quote to HTML (SQL injection - simple way!)
	 *
	 * @param string
	 * @return string on success/bool FALSE on failure
	*/
	public function filter_quote($str, $max_length = 0) {
		if ($max_length > 0 && $this->length_check($str, $max_length) == FALSE) {
			return FALSE;
		}

		$str = str_replace(["'", '"'], ['&#39;', '&quot;'], trim($str));
		return addslashes($str);
	}

	/**
	 * Quote remove
	 *
	 * @param string
	 * @param int
	 * @return string on success/bool FALSE on failure
	*/
	public function filter_quote_remove($str, $max_length = 0) {
		if ($max_length > 0 && $this->length_check($str, $max_length) == FALSE) {
			return FALSE;
		}

		$str = str_replace(["'", '"', '&#39;', '&quot;'], '', trim($str));
		return addslashes($str);
	}

	/**
	 * Simple filter
	 *
	 * @param string
	 * @return string on success/bool FALSE on failure
	*/
	public function filter_simple($str, $max_length = 0) {
		if ($max_length > 0 && $this->length_check($str, $max_length) == FALSE) {
			return FALSE;
		}
		return htmlspecialchars(addslashes(trim($str)));
	}

	/**
	 * IP filter (ipv4 and ipv6)
	 *
	 * @return string on success/bool FALSE on failure
	*/
	public function filter_ip($ip, $tolower = TRUE) {
		$ip = trim($ip);
		if (filter_var($ip, FILTER_VALIDATE_IP) == FALSE) {
			return FALSE;
		}
		if (preg_match("/^[a-zA-Z0-9:.]+$/", $ip) != 1) {
			return FALSE;
		}
		if ($tolower == TRUE) {
			return strtolower($ip);
		}
		return $ip;
	}

	/**
	 * String to HTML
	 * Convert all HTML entities to their applicable characters
	 *
	 * @param string
	 * @return string on success/bool FALSE on failure
	*/
	public function str_to_html($str, $max_length = 0) {
		if ($max_length > 0 && $this->length_check($str, $max_length) == FALSE) {
			return FALSE;
		}
		return html_entity_decode(trim($str), ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Sanitize filename
	 *
	 * CodeIgniter
	 *
	 * @param string
	 * @param bool
	 * @return string on success/bool FALSE on failure
	*/
	public function file_name($str, $relative_path = FALSE, $max_length = 0) {
		if ($max_length > 0 && $this->length_check($str, $max_length) == FALSE) {
			return FALSE;
		}

		$bad = ['../', '<!--', '-->', '<', '>', "'", '"', '&', '$', '#',
			'{', '}', '[', ']', '=', ';', '?', '%20', '%22',
			'%28', // (
			'%29', // )
			'%2528', // (
			'%26', // &
			'%24', // $
			'%3c', // <
			'%253c', // <
			'%3e', // >
			'%0e', // >
			'%3f', // ?
			'%3b', // ;
			'%3d'  // =
		];

		if ($relative_path == FALSE) {
			$bad[] = './';
			$bad[] = '/';
		}

		$str = $this->remove_bad_characters($str, FALSE);
		do {
			$old = $str;
			$str = str_replace($bad, '', $str);
		}
		while ($old !== $str);
		return stripslashes($str);
	}

	/**
	 * Check string length
	 *
	 * @param  string|int
	 * @param  int
	 * @return bool TRUE on success/FALSE on failure
	*/
	public function length_check($str, $max_length) {
		if (strlen($str) > $max_length) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Remove the bad characters
	 *
	 * CodeIgniter
	 *
	 * @param  string
	 * @return string
	*/
	public function remove_bad_characters($str, $url_encoded = TRUE) {
		$non_display = [];
		if ($url_encoded) {
			$non_display[] = '/%0[0-8bcef]/'; // url encoded 00-08, 11, 12, 14, 15
			$non_display[] = '/%1[0-9a-f]/'; // url encoded 16-31
		}
		$non_display[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127
		do {
			$str = preg_replace($non_display, '', $str, -1, $count);
		} while($count);
		return $str;
	}

	/**
	 * Fetch from array
	 *
	 * CodeIgniter
	 *
	 * @param  array
	 * @param  string
	 * @param  bool
	 * @param  int
	 * @return string on success/bool FALSE on failure
	*/
	public function fetch_array(&$array, $index = '', $security_filter = FALSE, $max_length = 250) {
		if (isset($array[$index]) == FALSE) {
			return FALSE;
		}
		if ($security_filter != FALSE) {
			return $this->filter($array[$index], $security_filter, $max_length);
		}
		else if ($max_length > 0 && strlen($array[$index]) > $max_length) {
			return FALSE;
		}
		return $array[$index];
	}
}