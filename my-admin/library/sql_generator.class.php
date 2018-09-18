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
 * SQL generator class
 *
 * @modified : 18 September 2018
 * @created  : 29 January 2016
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class ma_sql_generator
{
	protected $drive = 'mysql';
	protected $schema = [];
	protected $sql_code = [];

	protected $primary_key = NULL;
	protected $unique_indexes = [];
	protected $table_options = [];
	protected $foreign_keys = [];
	protected $error = NULL;

	public $table_prefix = NULL;
	public $if_not_exists = TRUE;

	/**
	 * Constructor
	*/
	public function __construct() {
		return;
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
	 * SQL-Schema File
	 *
	 * @param  string|array
	 * @return bool FALSE on failure/TRUE on success
	*/
	public function schema_file($file) {
		if (is_array($file)) {
			$this->schema = $file;
			return TRUE;
		}

		$file = APPS_PATH . $file;
		if (!is_file($file)) {
			$this->error = 'schema file not found';
			return FALSE;
		}

		$this->schema = require($file);
		if (!is_array($this->schema)) {
			$this->error = 'invalid schema';
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Set Drive
	 *
	 * @return bool FALSE on failure/TRUE on success
	*/
	public function drive($database) {
		if ($database != 'mysql' && $database != 'sqlite') {
			$this->error = 'Error::database drive is not supported';
			$this->drive = FALSE;
			return FALSE;
		}

		$this->drive = $database;
	}

	/**
	 * Generate
	 *
	 * @return bool FALSE on failure/TRUE on success
	*/
	public function generate() {
		if ($this->drive == FALSE) {
			return FALSE;
		}
		else if (!$this->schema) {
			$this->error = 'invalid schema 2';
			return FALSE;
		}

		$sql = $this->sql_generator();
		if ($sql == FALSE) {
			if (!$this->error) {
				$this->error = 'Error';
			}
			return FALSE;
		}

		return $this->sql_code;
	}

	/**
	 * MySQL Generator
	 *
	 * @return bool FALSE on failure/TRUE on success
	*/
	protected function sql_generator() {
		foreach ($this->schema as $table_name => $table_data) {
			// New Table
			$add_new = $this->new_table($table_name, $table_data);
			if ($add_new == FALSE) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Add New Table
	 *
	 * @return bool FALSE on failure/string on success
	*/
	protected function new_table($table_name, $table_data) {
		$if_not = ($this->if_not_exists == TRUE) ? 'IF '.'NOT '.'EXISTS' : NULL;
		$sql_code = 'CREATE' . ' TABLE '.$if_not.' `'.$this->table_prefix . $table_name.'` (' . "\n";

		// Reset
		$this->primary_key = NULL;
		$this->unique_indexes = [];
		$this->foreign_keys = [];

		// Columns
		foreach ($table_data as $column_name => $data) {
			// Tale Indexes
			if ($column_name == '_index') {
				continue;
			}

			// Table Options
			if ($column_name == '_options') {
				$this->table_options[$table_name] = $data;
				continue;
			}

			// Map (no col)
			if ($column_name[0] == '_') {
				continue;
			}

			$col = $this->new_column($table_name, $column_name, $data);
			if ($col == FALSE) {
				return FALSE;
			}
			else {
				$sql_code .= $col . "\n";
			}
		}

		// Indexes (MySQL)
		if ($this->drive == 'mysql') {
			$sql_code .= $this->add_mysql_table_indexes($table_name);
		}

		// Foreign Key
		if (count($this->foreign_keys) != 0) {
			if ($this->drive == 'mysql') {
				$sql_code .= $this->add_foreign_key($table_name);
			}
			else if ($this->drive == 'sqlite') {
				$sql_code .= $this->add_foreign_key($table_name);
				$sqlite_fk_index = TRUE;
			}
		}

		// Close
		$sql_code = rtrim(trim($sql_code), ',');

		// END - MySQL
		if ($this->drive == 'mysql') {
			$opt = $this->add_tabale_options($table_name);
			$sql_code .= ')' . $opt . ';';
			$this->sql_code[] = $sql_code;
		}
		// END - SQLite
		else if ($this->drive == 'sqlite') {
			$sql_code .= ');';
			$this->sql_code[] = $sql_code;
			$this->add_sqlite_table_indexes($table_name);
		}

		return TRUE;
	}

	/**
	 * Add New Column
	 *
	 * @return bool FALSE on failure/string on success
	*/
	protected function new_column($table_name, $column_name, $data) {
		// Schema
		if (!isset( $this->schema[$table_name][$column_name] )) {
			$this->error = 'invalid schema tabal_column';
			return FALSE;
		}
		else {
			$schema = $this->schema[$table_name][$column_name];
		}

		// Open
		$sql_code = '  `'.$column_name.'` ';

		// Type
		if (!isset($schema['type']) || empty($schema['type'])) {
			$this->error = 'invalid column type['.$column_name.']';
			return FALSE;
		}
		else {
			$type = $this->rigid_type($schema['type']);
			if ($type == FALSE) {
				$this->error = 'invalid sql type::'.$column_name.'::'.$schema['type'].'';
				return FALSE;
			}

			$sql_code .= $type .' ';
		}

		// Unsigned
		if ( $this->drive != 'sqlite' && isset($schema['unsigned']) && $schema['unsigned'] == TRUE) {
			$sql_code .= 'UNSIGNED ';
		}

		// If Default
		$is_default = TRUE == array_key_exists('default', $schema) ? TRUE : FALSE;

		// Null
		if (TRUE == $is_default && is_null($schema['default'])) {
			$schema['null'] = TRUE;
		}

		if (isset($schema['null']) && TRUE == $schema['null']) {
			$not_null = FALSE;
		}
		else {
			$not_null = TRUE;
			$sql_code .= 'NOT NULL ';
		}

		// Primary Key
		if (isset($schema['primary_key']) && TRUE == $schema['primary_key']) {
			$sql_code .= $this->add_col_primary_key($column_name);
		}

		// Auto Increment
		if (isset($schema['autoincrement']) && TRUE == $schema['autoincrement']) {
			$sql_code .= $this->add_auto_increment();
		}

		// Unique Index
		if (isset($schema['unique']) && FALSE != $schema['unique']) {
			$sql_code .= $this->add_unique_index($column_name, $schema['unique']);
		}

		// Default
		if (TRUE == $is_default) {
			$sql_code .= $this->add_default_value($schema['default'], $not_null);
		}

		// Foreign Keys
		if (isset($schema['foreign_key']) && FALSE != $schema['foreign_key']) {
			$this->foreign_keys[$table_name][] = $schema['foreign_key'];
		}

		// Done
		return rtrim($sql_code) . ',';
	}

	/**
	 * Col Primary Key
	 *
	 * @param  string
	 * @return string
	*/
	protected function add_col_primary_key($column_name) {
		if ($this->drive == 'mysql') {
			$this->primary_key = $column_name;
			return '';
		}
		else if ( $this->drive == 'sqlite' ) {
			$this->primary_key = NULL; // reset
			return 'PRIMARY KEY ';
		}

		return '';
	}

	/**
	 * Auto Increment
	 *
	 * @return string
	*/
	protected function add_auto_increment() {
		if ( $this->drive == 'mysql' ) {
			return 'AUTO_INCREMENT ';
		}
		else if ( $this->drive == 'sqlite' ) {
			return 'AUTOINCREMENT ';
		}

		return '';
	}

	/**
	 * Unique Index
	 *
	 * @return string
	*/
	protected function add_unique_index($column_name, $sort = 'ASC') {
		if ($this->drive == 'mysql') {
			$sort = strtoupper($sort);
			$this->unique_indexes[$column_name] = ('DESC' === $sort) ? 'DESC' : 'ASC';
			return '';
		}
		else if ($this->drive == 'sqlite') {
			return 'UNIQUE ';
		}

		return;
	}

	/**
	 * Default Value
	 *
	 * @param  bool
	 * @param  string|int
	 * @return string
	*/
	protected function add_default_value($default, $not_null) {
		if (is_null($default) && $not_null == FALSE) {
			return 'DEFAULT NULL ';
		}
		else if (is_bool($default) && $default == TRUE) {
			return 'DEFAULT 1 ';
		}
		else if (is_bool($default) && $default == FALSE) {
			return 'DEFAULT 0 ';
		}
		else if (is_int($default)) {
			return "DEFAULT ".$default." ";
		}
		else if (is_string($default) || is_numeric($default) ) {
			return "DEFAULT '".$default."' ";
		}

		return '';
	}

	/**
	 * Add MySQL Table Indexes
	 *
	 * @param  string
	 * @return string
	*/
	protected function add_mysql_table_indexes($table_name) {
		if ($this->drive != 'mysql') {
			return '';
		}

		$sql_code = '';

		// MySQL Primary_key
		if ($this->primary_key == TRUE) {
			$sql_code .= '  PRIMARY KEY (`'.$this->primary_key.'`),' . "\n";
		}

		// MySQL Unique Indexes
		if (count($this->unique_indexes) != 0) {
			foreach ($this->unique_indexes as $name => $sort) {
				$name = strtoupper($name);
				$sql_code .= '  UNIQUE INDEX `'.$name.'_UNIQUE` (`'.$name.'` '.$sort.'),' . "\n";
			}
		}

		// MySQL All Indexes
		if (isset( $this->schema[$table_name]['_index'] )) {
			$sql_code .= $this->add_mysql_index( $this->schema[$table_name]['_index'] );
		}

		return $sql_code;
	}

	/**
	 * MySQL Index
	*/
	protected function add_mysql_index($schema) {
		$sql_code = '';
		foreach ($schema as $key => $indexes) {
			if ( !is_array($indexes) ) {
				$indexes = array($key => 'ASC');
			}

			// if is Unique indexes
			if (isset($indexes['unique'])) {
				unset($indexes['unique']);
				$sql_code .= ' UNIQUE';
			}

			$sql_code .= ' INDEX `'.$key.'` (';
			$code = '';
			while (list($index_n, $value) = each($indexes) ) {
				// Unique
				if ('unique' == $index_n) {
					continue;
				}

				// fix
				if (empty($index_n)) {
					$index_n = $value;
				}

				// set
				$val = strtoupper($value);
				$val = ('DESC' == $val) ? 'DESC' : 'ASC';
				$code .= '`'.$index_n .'` '. $val.',';
			}

			if (!empty($code)) {
				$sql_code .= rtrim(trim($code), ',') . '),' . "\n";
			}
		}
		return $sql_code;
	}

	/**
	 * Add Foreign Key
	 *
	 * @param  string
	 * @return string
	*/
	protected function add_foreign_key($table_name) {
		$sql_code = '';
		if (0 == count($this->foreign_keys)) {
			return $sql_code;
		}

		foreach ($this->foreign_keys as $key1 => $foreign_keys) {
			if (!is_array($foreign_keys)) {
				continue;
			}

			foreach ($foreign_keys as $key => $data) {
				$fk_name = 'fk_' . $this->table_prefix . $data['ref_table'] . '_' . $table_name . '_' . $data['column'];

				$sql_code .= "  CONSTRAINT `".$fk_name."`\n";
				$sql_code .= "   FOREIGN KEY (`".$data['column']."`)\n";
				$sql_code .= "   REFERENCES `".$this->table_prefix . $data['ref_table']."` (`".$data['ref_column']."`)\n";

				//  ON UPDATE
				$on_update = (isset($data['on_update']) && $data['on_update'] != FALSE) ? strtoupper($data['on_update']) : FALSE;
				if ($on_update == FALSE || $on_update == 'NO ACTION') {
					$sql_code .= "   ON UPDATE NO ACTION\n";
				}
				else {
					$sql_code .= "   ON UPDATE ".$on_update."\n";
				}

				//  ON DELETE
				$on_delete = (isset($data['on_delete']) && FALSE != $data['on_delete']) ? strtoupper($data['on_delete']) : FALSE;
				if ($on_delete == FALSE || $on_delete == 'NO ACTION') {
					$sql_code .= "   ON DELETE NO ACTION";
				}
				else {
					$sql_code .= "   ON DELETE ".$on_delete."";
				}

				$sql_code .= ",\n";
			}

			return $sql_code;
		}
	}

	/**
	 * Add Table Options
	 *
	 * @param  string
	 * @return string
	*/
	protected function add_tabale_options($table_name) {
		$sql_code = '';
		$options = isset($this->schema[$table_name]['_options']) ? $this->schema[$table_name]['_options'] : FALSE;

		// MySQL
		if ($this->drive == 'mysql') {
			// Engine
			if (isset($options['engine']) && strtolower($options['engine']) === 'myisam') {
				$sql_code .= "\n ENGINE = MyISAM";
			}
			else {
				$sql_code .= "\n ENGINE = InnoDB";
			}

			// Auto Increment
			if (isset($options['auto_increment'])) {
				$sql_code .= "\n AUTO_INCREMENT = " . $options['auto_increment'];
			}

			// Character Set
			if (isset($options['character_set'])) {
				$sql_code .= "\n DEFAULT CHARACTER SET = " . $options['character_set'];
			}

			// Collate
			if (isset($options['collate'])) {
				$sql_code .= "\n COLLATE = " . $options['collate'];
			}
		}

		return $sql_code;
	}

	/**
	 * Add Sqlite Table Indexes
	 *
	 * @param  string
	 * @return string
	*/
	protected function add_sqlite_table_indexes($table_name) {
		if ($this->drive != 'sqlite') {
			return '';
		}

		// ADD
		if (!isset( $this->schema[$table_name]['_index'] ) || empty( $this->schema[$table_name]['_index'] )) {
			return '';
		}

		$schema = $this->schema[$table_name]['_index'];
		$tname = $this->table_prefix.$table_name;
		$sql_code = '';

		// ADD
		foreach ($schema as $key => $indexes) {
			if ( !is_array($indexes) ) {
				$indexes = array($key => 'ASC');
			}

			$is_unique = isset($indexes['unique']) ? 'UNIQUE' : NULL;
			$ind = '';
			$ind .= 'CREATE '.$is_unique.' INDEX IF NOT EXISTS `'.$tname.'_'.$key.'` ON `'.$tname.'` (';

			while (list($index_n, $value) = each($indexes) ) {
				if ($index_n == 'unique') {
					continue;
				}

				$ind .= '`' . $index_n .'` '. strtoupper( $value ).',';
			}

			$this->sql_code[] = rtrim(trim($ind), ',') . ");";
		}

		return TRUE;
	}

	/**
	 * Rigid Type
	 *
	 * @return bool FALSE on failure/string on success
	*/
	protected function rigid_type($input) {
		$type = $input = strtolower($input);
		$type_val = NULL;

		$type_ex = explode('(', $input, 2);
		if (isset($type_ex[1])) {
			$type = $type_ex[0];
			$type_val = (int) trim($type_ex[1], ')');
		}

		// MySQL
		if ($this->drive == 'mysql') {
			switch($type)
			{
				case 'integer':
					$type = 'int';
				break;
				case 'character':
					$type = 'char';
				break;
			}
		}
		// SQLite
		else if ($this->drive == 'sqlite') {
			switch ($type) {
				case 'tinyint': case 'smallint': case 'mediumint': case 'int':  case 'bigint': 
					$type = 'integer';
					$type_val = FALSE;
				break;
				case 'char': case 'varchar':
					$type = 'text';
					$type_val = FALSE;
				break;
				case 'double': case 'float':
					$type = 'real';
					$type_val = FALSE;
				break;
				case 'date': case 'datetime':
					$type = 'numeric';
					$type_val = FALSE;
				break;
			}
		}

		// Set
		if ($type_val) {
			$type = strtoupper($type).'('.$type_val.')';
		}
		else {
			$type = strtoupper($type);
		}

		return $type;
	}

	/**
	 * Error
	 *
	 * @return string|null
	*/
	public function error() {
		return $this->error;
	}
}