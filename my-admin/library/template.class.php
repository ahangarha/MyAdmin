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
 * Template Class
 *
 * @modified : 14 November 2017
 * @created  : November 2014
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

require_once(__DIR__.'/template/engine.class.php');

class ma_template extends ma_template_engine
{
	/* Cached */
	public $cache = FALSE;
	public $cache_time = 3600;

	/* delimiter */
	public $left_delimiter = '{#';
	public $right_delimiter = '}';

	/* Compile Check */
	public $compile_check = TRUE;

	/* More Configuration */
	public $debug = FALSE;
	public $php_enabled = FALSE;
	public $auto_escape = FALSE; // add htmlspecialchars
	public $sandbox = TRUE;
	public $strip = TRUE;//HTML Commpresed Source

	/**
	 * Init
	 *
	 * return void
	*/
	public function init() {
		return;
	}

	/**
	 * Assign
	 *
	 * return void
	*/
	public function assign($variable, $value = NULL) {
		parent::main_assign($variable, $value);
	}

	/**
	 * Init
	 *
	 * return void
	*/
	public function display($tpl_name, $tpl_uniqid = NULL) {
		echo parent::main_display($tpl_name, $tpl_uniqid);
	}

	/**
	 * Init
	 *
	 * return void
	*/
	public function fetch($tpl_name, $tpl_uniqid = NULL) {
		return parent::main_display($tpl_name, $tpl_uniqid);
	}

	/**
	 * Template dir
	 *
	 * return void
	*/
	public function setTemplateDir($dir) {
		if (is_array($dir) && 0 == count($dir)) {
			$this->TemplateDir = [];
		}
		else {
			$this->TemplateDir[] = parent::cleaning_path($dir).'/';
		}
	}

	/**
	 * Compiled dir
	 *
	 * return void
	*/
	public function setCompileDir( $dir ) {
		$this->CompileDir = parent::cleaning_path( $dir ).'/';
	}

	/**
	 * Cache dir
	 *
	 * return void
	*/
	public function setCacheDir($dir) {
		$this->CacheDir = parent::cleaning_path($dir).'/';
	}

	/**
	 * Is Compiled?
	 *
	 * @return bool TRUE on success/FALSE on failure
	*/
	public function isCompiled( $tpl_filename , $tpl_uniqid = NULL) {
		return parent::is_compiled($tpl_filename, $tpl_uniqid);
	}

	/**
	 * Is Cache?
	 *
	 * @return bool TRUE on success/FALSE on failure
	*/
	public function isCache( $tpl_filename , $tpl_uniqid = NULL) {
		$compiled = parent::is_compiled($tpl_filename, $tpl_uniqid);
		if (FALSE == $compiled) {
			return FALSE;
		}

		return parent::is_cache($tpl_filename, $tpl_uniqid);
	}

	/**
	 * Clear Cache
	 *
	 * @return void
	*/
	public function clearCache($tpl_filename = NULL, $tpl_uniqid = NULL) {
		return parent::clear_cache($tpl_filename, $tpl_uniqid);
	}

	/**
	 * Clear cache - All
	 *
	 * @return bool TRUE on success/FALSE on failure
	*/
	public function clearCacheAll() {
		return parent::clear_all_cache();
	}
}