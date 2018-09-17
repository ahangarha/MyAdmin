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
 * PDO Class
 *
 * @modified : 17 September 2018
 * @created  : 25 June 2015
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

if (class_exists('PDO') == FALSE) {
	ma_exit('Error: PDO library is not installed on your web server.');
}

class ma_pdo extends PDO
{
	protected $error = NULL;
	protected $table_prefix = NULL;

	/**
	 * Constructor
	*/
	function __construct($drive, $string_pdo, $user = NULL, $pass = NULL, $table_prefix = '') {
		$db = [];
		try {
			if ('mysql' == $drive) {
				parent::__construct($string_pdo, $user, $pass);
				$this->setAttribute(PDO::MYSQL_ATTR_FOUND_ROWS, TRUE);
			}
			else if ($drive == 'sqlite') {
				parent::__construct($string_pdo);
			}
		}
		catch (PDOException $e) {
			$error = 'error_40::database_connection::'.$e->getMessage();
			ma_save_log('error', $error);
			if (MA_ENVIRONMENT != 'development') {
				$error = 'error_100';
			}

			$err_class = ma_class('error_handler');
			$err_class->sending_message_to_admin($error);
			ma_error(403, $error, FALSE);
		}

		if ($table_prefix) {
			$this->table_prefix = $table_prefix;
		}
		
		// Debug
		ma_save_log('debug', 'PDO Class Initialized');
	}

	/**
	 * Exec
	 *
	 * @param  string
	 * @return mixed  
	*/
	public function exec($statement) {
		$statement = $this->_table_prefix_suffix($statement);
		return parent::exec($statement);
	}

	/**
	 * Query
	 *
	 * @param  string
	 * @return mixed  
	*/
	public function prepare($statement, $driver_options = array()) {
		$statement = $this->_table_prefix_suffix($statement);
		return parent::prepare($statement, $driver_options);
	}

	/**
	 * Query
	 *
	 * @param  string
	 * @return mixed  
	*/
	public function query($statement) {
		$statement = $this->_table_prefix_suffix($statement);
		$query = parent::query($statement);
		if (!$query) {
			$errors = $this->errorInfo();
			$error  = $errors[2];
			ma_save_log('error', 'err-db :'. $error . ' ('.$statement.') ');
			if (MA_ENVIRONMENT != 'development') {
				$error = 'ERR-3070';
			}
			ma_error(500, $error);
		}
		return $query;
    }

	/**
	 * Select
	 *
	 * Examples :
	 *  array [tabale_name, id, col_1, col_2] => select id, col_1, col_2 from tabale_name ...
	 *  string tabale_name => select * from tabale_name ...
	 *
	 *  array $where[id => 1000, name => test] => select * from tabale_name where id='1000' AND name='test' ...
	 *
	 * @param  string
	 * @param  array
	 * @param  array|null
	 * @param  int
	 * @param  true
	 * @return mixed
	*/
	public function select($table_name, $where, $options = NULL, $fetch = TRUE) {
		// Table
		if (is_array($table_name)) {
			$t_count = count($table_name);
			if (0 == $t_count) {
				return FALSE;
			}

			$table = $table_name[0];
			if (0 < $t_count) {
				unset($table_name[0]);
				$t_count--;
				$cols = '';
				$i=0;
				foreach ($table_name as $t) {
					$cols .= $t.' ';
					$i++;
					if ($i < $t_count) {
						$cols .= ',';
					}
				}
			}
		}
		else {
			$table = $table_name;
			$cols = '*';
		}

		// WHERE
		$exe = [];
		$count = count($where);
		$i=0;
		$where_q = '';
		if (0 != $count) {
			foreach ($where as $k => $v) {
				$k = ltrim($k, ':');
				$exe[':' . $k] = $v;

				$row = '`'.$k.'`=:'.$k;
				$where_q .= $row;
				$i++;

				if ($i < $count) {
					$where_q .= ' AND ';
				}
			}
		}

		// Options
		$options_q = $this->set_query_options($options);

		// Query
		$query = "SELECT $cols FROM `[prefix]$table` ";
		if (!empty($where_q)) {
			$query .= 'WHERE ' . $where_q;
		}
		$query .= ' '.$options_q;

		$query = $this->prepare($query);
		$query->execute($exe);
		if ($query == FALSE) {
			$this->error = $query->errorInfo();
			return FALSE;
		}

		if ($fetch == TRUE) {
			return $query->fetch(PDO::FETCH_ASSOC);
		}

		return $query;
	}

	/**
	 * Insert
	 *
	 * @param  string
	 * @param  array
	 * @return bool
	*/
	public function insert($table_name, $data) {
		$row = $val = $val2 = array(); $i=0;

		foreach ($data as $k => $v) {
			$x = ':'.ltrim($k, ':');
			$row[$i] = "`".ltrim($k, ':')."`";
			$val[$x] = $v;
			$val2[$i] = $x;
			$i++;
		}

		// Query
		$row = implode(',', $row);
		$val2 = implode(',', $val2);

		$query = $this->prepare('INSERT INTO `'.$this->table_prefix."$table_name` ($row) VALUES ($val2);");
		$ins = $query->execute($val);
		if ($ins == TRUE) {
			return TRUE;
		}
		else {
			$this->error = $query->errorInfo();
			return FALSE;
		}
	}

	/**
	 * Update
	 *
	 * @param  string
	 * @param  array
	 * @param  array
	 * @return bool
	*/
	public function update($table_name, $data, $where = [], $options = NULL) {
		$query = "UPDATE `[prefix]$table_name` SET ";
		$exec = [];

		// Rows
		foreach ($data as $k => $v) {
			$k = ltrim($k, ':');
			if (isset($where[$k])) {
				continue;
			}

			$query .= " `$k`=:$k,";
			$exec[':'.$k] = $v;
		}

		$query = rtrim($query, ',');

		// Where
		$count = count($where);
		if (0 != $count) {
			$query .= ' WHERE ';
			$i = 0;
			foreach ($where as $k => $v) {
				$k = ltrim($k, ':');
				$query .= " `$k`=:$k";
				$exec[':'.$k] = $v;
				$i++;
				if ($i < $count) {
					$query .= ' AND ';
				}
			}
		}

		// Query
		$dbq = $this->prepare($query);
		$q = $dbq->execute($exec);
		if ($q == FALSE) {
			$this->error = $dbq->errorInfo();
			return FALSE;
		}
		else {
			return $q;
		}
	}

	/**
	 * Set Query Options (array to string)
	 *
	 * @param  arary
	 * @return string
	*/
	protected function set_query_options($options) {
		if (!is_array($options)) {
			return '';
		}

		$options_q = ' ';

		// Sort
		if (isset($options['order'])) {
			$options_q .= 'ORDER BY `' . $options['order'] . '`';

			if (isset($options['sort'])) {
				$options_q .= ' ' . strtoupper($options['sort']);
			}
		}

		// Limit
		if (isset($options['limit'])) {
			$options_q .= ' LIMIT ' . $options['limit'];
		}

		return $options_q;
	}

	/**
	 * Table Prefix
	 *
	 * @param  string
	 * @return string
	*/
	protected function _table_prefix_suffix($statement) {
		return str_replace('[prefix]', $this->table_prefix, $statement);
	}

	/**
	 * Error
	 *
	 * @return string|null
	*/
	public function error() {
		if ($this->error == NULL) {
			return NULL;
		}
		$error = is_array($this->error) ? implode("\n_", $this->error) : (string) $this->error;
		return $error;
	}
}