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
 * MyAdmin Class
 *
 * @modified : 26 July 2018
 * @created  : 03 September 2014
 * @since    : version 0.1
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class myadmin
{
	protected $security;
	protected $client;
	protected $url_map = [];

	/**
	 * Constructor
	*/
	public function __construct($self_file, $self_path) {
		// self Path
		if (defined('MA_SELF_PATH') == FALSE) {
			define('MA_SELF_PATH', realpath($self_path));
		}

		// self
		defined('MA_SELF_FILE') OR define('MA_SELF_FILE', $self_file);

		// get configs
		$this->get_configs();

		// error handling
		ini_set('error_reporting', -1);
		set_error_handler('ma_error_handler');
		register_shutdown_function('ma_error_shutdown');

		ma_include('router.class.php');
		ma_include('controller.class.php');

		// default configs
		ma_timezone(ma_config('timezone'));

		// security
		$this->security =& ma_class('security');

		// client
		$this->client =& ma_class('client');

		// default http headers
		ma_header([
			'Cache-Control'    => 'no-cache, no-store, must-revalidate, private',
			'Content-Type'     => 'text/html; charset=utf-8',
			'X-Frame-Options'  => ma_config('x_frame_options'),
			'X-XSS-Protection' => '1; mode=block',
			'X-Content-Type-Options'=> 'nosniff',
			'Pragma' => 'no-cache'
		]);

		// modules list
		$this->get_modules();

		return;
	}

	/**
	 * Run
	*/
	public function run() {
		$router = new ma_router();
		$router->module_loader();
		return;
	}

	/**
	 * Get configs
	*/
	public function get_configs($set = TRUE) {
		$cf = MA_PATH.'/configs/main.inc.php';
		if (is_file($cf) == FALSE) {
			ma_exit('error::main_config::file_not_found');
		}

		require($cf);
		if (isset($ma_config) == FALSE) {
			ma_exit('error_15::configs_error');
		}

		if ($set != TRUE) {
			return $ma_config;
		}

		foreach($ma_config as $key => $val) {
			ma_config($key, $val);
		}

		return;
	}

	/**
	 * Get modules list
	*/
	protected function get_modules() {
		ma_include('configs/modules.inc.php');
		return;
	}
}