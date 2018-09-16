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
 * Router Class
 *
 * @modified : 26 July 2018
 * @created  : 08 November 2017
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class ma_router
{
	protected $security;
	protected $mod_dir;
	protected $file_name;
	protected $class_name;
	protected $method_name;
	protected $url_params;

	/**
	 * Constructor
	*/
	public function __construct() {
		$this->security =& ma_class('security');

		$url_map =& ma_class('url_map', ['dir' => '/']);
		$this->url = $url_map->get_map();
		if ($this->url == FALSE) {
			ma_error(400, 'Error bad request');
		}
	}

	/**
	 * Module loader
	 *
	 * @return void
	*/
	public function module_loader() {
		if ($this->get_module_info() == FALSE) {
			ma_error(404, 'Module not found (code 01)');
		}

		if ($this->include_files() == FALSE) {
			ma_error(404, 'Module not found (code 02)');
		}
		if (class_exists($this->class_name) == FALSE) {
			ma_error(404, 'Module not found (code 03)');
		}

		// call
		$mod = new $this->class_name($this->url['language']);

		if (method_exists($mod, $this->method_name) == FALSE) {
			ma_error(404, 'Module not found (code 04)');
		}

		$mod->{$this->method_name}($this->url_params);

		return;
	}

	/**
	 * Module info
	 *
	 * @return bool TRUE on success/FALSE on failure
	*/
	protected function get_module_info() {
		$mod = $this->url['module'];
		$this->mod_dir = MA_PATH.'/modules/'.$mod.'/';
		$this->url_params = $this->url['path'];
		$def = FALSE;

		if (isset(MA_MODULES[$mod]['controller'])) {
			$cn = MA_MODULES[$mod]['controller'];
			$file = MA_MODULES[$mod]['controller'].'.php';
			$def = TRUE;
		}
		else {
			$path = $this->security->filter($this->url['path'][0], 'alphabet', 32);
			if ($path == FALSE) {
				return FALSE;
			}

			unset($this->url_params[0]);
			$cn = $path;
			$file = $path.'.php';
		}

		$this->class_name = '\myadmin\\module\\'.$mod.'\\'.$cn;
		$this->file_name = $file;

		if ($def == FALSE && isset($this->url['path'][1])) {
			$method = $this->security->filter($this->url['path'][1], 'alphabet', 32);
			if ($method) {
				$this->method_name = $method;
				unset($this->url_params[1]);
			}
		}
		else {
			$this->method_name = $def == TRUE ? MA_MODULES[$mod]['controller'] : $path;
		}

		if (count($this->url_params) == 0) {
			$this->url_params = NULL;
		}
		else {
			$this->url_params = array_merge($this->url_params);
		}

		return TRUE;
	}

	/**
	 * Include files
	 *
	 * @return bool TRUE on success/FALSE on failure
	*/
	protected function include_files() {
		$mod_main_file = $this->mod_dir.$this->url['module'].'.php';
		if (is_file($mod_main_file) == TRUE) {
			ma_include_module($mod_main_file);
		}

		$mod_file = $this->mod_dir.$this->file_name;
		if (is_file($mod_file) == FALSE) {
			return FALSE;
		}

		ma_include_module($mod_file);
		return TRUE;
	}
}


/**
 * Include module
*/
function ma_include_module($mod_file_path) {
	require_once($mod_file_path);
}