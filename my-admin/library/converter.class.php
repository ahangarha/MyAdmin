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
 * Converter Class
 *
 * @modified : 14 November 2017
 * @created  : 29 January 2015
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class ma_converter
{
	public $digital_units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

	/**
	 * if int return like => 1024 
	 * if string  return like => 1024 KB
	 * if array return (site, unit)
	*/
	public $digital_format = 'string';

	// Number Format Decimals
	public $decimals = 2;

	/**
	 * Digital Storage
	 *
	 * @param $input int, b,kb,mb,gb,pb...
	 * @param $from string, b,kb,mb,gb,pb...
	 * @param $to string, b,kb,mb,gb,pb...
	*/
	public function digital($size, $from = 'B', $to = NULL) {
		$size = str_replace([',', '.'], '', $size);

		// hatman bayad input be byte tabdil shavad,
		// agar $from NULL bud yani byte, agar na bayad convert shavad.
		$size = $this->to_bytes($size, $from);

		// agat $to bod yani hatman bayad be $to tabdil shavad,
		// dar gheyre insoorate auto hesab mishava.
		return $this->format_size_units( $size , $to );
	}

	/**
	 * Any To Bytes
	 *
	 * @param $input int
	 * @param $unit string
	 * @return int
	*/
	public function to_bytes($size, $unit) {
		$unit = array_search(strtoupper($unit), $this->digital_units);
		return ($size * pow(1024, $unit));
	}

	/**
	 * Digital Units
	 *
	 * @param int
	 * @return string
	*/
	public function format_size_units($bytes, $unit = NULL) {
		if ($unit) {
			$unit = array_search(strtoupper($unit), $this->digital_units);
		}
		else {
			$unit = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
		}

		$c = number_format($bytes / pow(1024, $unit), $this->decimals, '.', ',');

		if ($this->digital_format == 'string') {
			$c .= ' ' . $this->digital_units[$unit];
		}
		else if ($this->digital_format == 'array') {
			$c = array($c, $this->digital_units[$unit]);
		}

		return $c;
	}

	/**
	 * Weather! :D
	 *
	 * @param $temp int
	 * @param $from string (c|celsius, f|fahrenheit)
	 * @param $to   string (c|celsius, f|fahrenheit)
	 * @return int
	*/
	public function weather($temperatures, $from, $to) {
		// Celsius to Fahrenheit
		if (($from == 'c' || $from == 'celsius') && ($to == 'f' || $to == 'fahrenheit')) {
			$value = $temperatures * 9/5+32;
		}
		// Fahrenheit to Celsius
		else {
			$value = 5/9 * ($temperatures - 32);
		}
		return number_format($value, $this->decimals, '.', ',');
	}
}