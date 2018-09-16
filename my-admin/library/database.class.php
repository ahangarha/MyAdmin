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
 * Database Class
 *
 * @modified : 01 December 2017
 * @created  : 25 June 2015
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

require_once(__DIR__.'/pdo.class.php');

class ma_database
{
	protected $conn = NULL;

	/**
	 * Constructor
	*/
	function __construct() {
		return;
	}

	/**
	 * PDO loader
	 *
	 * @return void
	*/
	protected function pdo_loader() {
		$conf = ma_config('database');
		if ($conf == FALSE) {
			ma_error(500, 'error_30::database_config');
		}

		// MySQL
		if ($conf['drive'] == 'mysql') {
			$pdo_sttr = 'mysql:host='.$conf['host'].';dbname='.$conf['name'];
			$this->conn = new ma_pdo('mysql', $pdo_sttr, $conf['username'], $conf['password'], $conf['table_prefix']);
		}
		// Sqlite
		else if ($conf['drive'] == 'sqlite') {
			$pdo_sttr = 'sqlite:'.$conf['file_path'];
			$this->conn = new ma_pdo('sqlite', $pdo_sttr, null, null, $conf['table_prefix']);
		}
		else {
			ma_error(500, 'error_31::invalid_db_drive');
		}
	}

	/**
	 * PDO Proxy Call
	 * proxy calls to non-existant methods
	 * on this class to PDO instance
	 *
	 * @return mixed
	*/
	public function __call($method, $args) {
		// Load
		if ($this->conn == NULL) {
			$this->pdo_loader();
		}

		// PDO
		$callable = [$this->conn, $method];
		if (is_callable($callable)) {
			return call_user_func_array($callable, $args);
		}

		ma_error(500, 'error_32::invalid_pdo_method::'.$method);
	}
}