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
 * Benchmark Class
 *
 * @modified : 14 November 2017
 * @created  : 18 April 2015
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class ma_benchmark
{
	public $markers = [];

	/**
	 * Mark
	 *
	 * @param string
	 * @param integer microtime(TRUE)
	 * return FALSE
	*/
	public function mark($name, $microtime = NULL) {
		$this->markers[ $name ] = (NULL == $microtime) ? microtime(TRUE) : $microtime;
	}

	/**
	 * Difference between two points
	 *
	 * @param string marked
	 * @param string marked
	 * @param int
	 * @return mixed
	*/
	public function elapsed_time($point1 = '', $point2 = '', $decimals = 3) {
		if(!isset($this->markers[$point1], $this->markers[$point2])){
			return '';
		}
		return number_format($this->markers[$point2] - $this->markers[$point1], $decimals);
	}
}