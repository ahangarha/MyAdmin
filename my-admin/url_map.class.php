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

defined('MA_SELF_FILE') OR exit('Restricted access');

/**
 * URL Map Class
 *
 * @modified : 26 July 2018
 * @created  : 08 November 2017
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class ma_url_map
{
	protected $route = [];
	protected $map;
	protected $client;
	protected $security;
	protected $languages = [];
	protected $default_language;
	protected $redirection;

	/**
	 * Constructor
	*/
	public function __construct() {
		$this->client   =& ma_class('client');
		$this->security =& ma_class('security');

		$this->default_language = ma_config('default_language');
		$this->redirection = ma_config('language_redirection');
		$this->languages = ma_config('languages');

		$this->clear_cache();
	}

	/**
	 * Clear cache
	*/
	public function clear_cache() {
		$this->route = [
			'schema'    => $this->client->http_schema(),
			'base_path' => '/',
			'url_path'  => NULL,
			'module'    => NULL,
			'language'  => NULL,
			'path'     => []
		];
	}
	
	/**
	 * URL map
	 *
	 * @param  bool
	 * @return array on success/bool FALSE on failure
	*/
	public function get_map($cache = TRUE) {
		// cache
		if ($cache == TRUE && $this->route['module']) {
			return $this->route;
		}

		$st = $this->url_detection();
		if ($st == FALSE) {
			return FALSE;
		}

		$this->route['path'] = $this->map;
		return $this->route;
	}

	/**
	 * URL Detection
	 *
	 * @return bool TRUE on success/bool FALSE on failure
	*/
	protected function url_detection() {
		// Cerrent URL
		$this->route['url_path'] = $this->get_url();

		// Map
		$this->map = $this->url_to_array($this->route['url_path']);
		if (is_array($this->map) == FALSE) {
			$this->map = [];
			return FALSE;
		}

		$this->language_detection();
		$this->module_detection();

		return TRUE;
	}

	/**
	 * Current URL path
	 *
	 * @return string on success/NULL on failure
	*/
	protected function get_url() {
		if (defined('MA_BASE_PATH') == FALSE) {
			$base_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', MA_SELF_FILE);
			$opt = ['remove_path' => [$base_path, rtrim(dirname($base_path), '/')]];
			$this->route['base_path'] = dirname($base_path);
		}
		else {
			$opt = ['remove_path' => MA_BASE_PATH];
			$this->route['base_path'] = MA_BASE_PATH;
		}

		// easy link
		if ($this->route['base_path'] == '/') {
			$this->route['base_path'] = NULL;
		}

		$url_path = $this->client->url_path($opt);
		if ($url_path) {
			$url_path = preg_replace('#/+#', '/', $url_path, 1);
		}
		if ($url_path == '') {
			$url_path = '/';
		}

		return $url_path;
	}

	/**
	 * URL to array
	 *
	 * @param  string
	 * @param  int
	 * @return array on success/bool FALSE on failure
	*/
	public function url_to_array($url_path, $max_depth = 0) {
		if ($max_depth == 0) {
			$max_depth = defined('MA_URL_DEPTH') ? MA_URL_DEPTH : 20;
		}

		$map = explode('/', $url_path, $max_depth);
		if (isset($map[1]) == FALSE) {
			return FALSE;
		}

		foreach ($map as $k => $m) {
			if ($m == '') {
				unset($map[$k]);
			}
		}

		$map = array_merge($map);
		if (isset($map[0]) == FALSE) {
			$map[0] = 'index';
		}
		return $map;
	}

	/**
	 * Language detection
	 *
	 * @return void
	*/
	protected function language_detection() {
		// first url path
		$url_lang = $this->security->filter($this->map[0], 'alphabet', 5);
		if (empty($url_lang) == TRUE) {
			return;
		}

		// in url?
		if (isset($this->languages[$url_lang]) == TRUE) {
			$this->route['language'] = $url_lang;

			// remove from url_path
			unset($this->map[0]);
			$this->map = array_merge($this->map);
			if (count($this->map) == 0) {
				$this->map[0] = 'index';
			}
		}
		else {
			$this->route['language'] = $this->default_language;
		}

		// default language in url
		if ($this->redirection == TRUE) {
			$this->default_language_detection($url_lang);
		}

		return;
	}

	/**
	 * Default language path
	 * eg : /en/about to /about OR /about to /en/about
	 *
	 * @return void
	*/
	protected function default_language_detection($language_in_url) {
		if ($this->default_language != $language_in_url) {
			return;
		}

		$go_to = str_replace('/'.$language_in_url, '', $this->route['url_path']);
		if (empty($go_to)) {
			$go_to = '/';
		}

		if (empty($_SERVER['QUERY_STRING']) == FALSE) {
			$go_to .= '?'.$_SERVER['QUERY_STRING'];
		}

		ma_http_status(301);
		ma_header('Location', $go_to);
		exit(EXIT_SUCCESS);
	}

	/**
	 * Module detection
	 *
	 * @return void
	*/
	protected function module_detection() {
		$set = FALSE;
		$default_mod = NULL;

		foreach (MA_MODULES as $mod_name => $module) {
			$mod_name = strtolower($mod_name);
			if ($module['url_path'] == $this->map[0] && $module['enabled'] == TRUE) {
				$set = TRUE;
				$this->route['module'] = $mod_name;

				// remove module_url path
				unset($this->map[0]);
				$this->map = array_merge($this->map);
				break;
			}

			if ($module['url_path'] == '*' && $default_mod == NULL) {
				$default_mod = $mod_name;
			}
		}

		if ($set == FALSE) {
			$this->route['module'] = $default_mod;
		}
		return;
	}
}