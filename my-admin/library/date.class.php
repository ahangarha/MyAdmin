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

require_once(MA_PATH.'/library/calendar/persian_date.php');

/**
 * Date Class
 *
 * @modified : 26 July 2018
 * @created  : 03 September 2014
 * @since    : version 0.1
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class ma_date
{
	// Calendar string (persian, gregorian)
	protected $calendar = 'gregorian';

	// Basic timezone
	public $ma_timezone;

	// Client timezone
	public $client_timezone;

	// Calendars API
	public $persian;

	// Cache
	protected $offsets = [];

	// Date format
	public $format = 'D d M Y - H:i';
	public $local_numbers = NULL;

	/**
	 * Constructor
	*/
	function __construct() {
		$this->ma_timezone = ma_config('timezone');
		if ($this->ma_timezone == FALSE) {
			$this->ma_timezone = 'UTC';
		}

		$this->client_timezone = ma_config('client_timezone');
		if ($this->client_timezone == FALSE) {
			$this->client_timezone = $this->ma_timezone;
		}
	}

	/**
	 * Date
	 *
	 * @param  int
	 * @param  string|NULL
	 * @param  bool
	 * @param  string|NULL
	 * @return string
	*/
	public function date($time, $format = NULL, $offset_check = TRUE, $calendar = NULL) {
		// Calendar
		if ($calendar) {
			$this->calendar_select($calendar);
		}

		// Timezone Offset
		if ($offset_check == TRUE) {
			$offset = $this->timezone_offset($this->client_timezone, $this->ma_timezone);
			$time = $time + $offset;
		}

		// Format
		$format = ($format) ? $format : $this->format;

		// Local numbers
		if (is_null($this->local_numbers) == TRUE) {
			$this->local_numbers = ma_config('calendar_local_num');
		}

		// Persian
		if ($this->persian) {
			$date = $this->persian->date($format, $time );
			if ($this->local_numbers == TRUE) {
				$date = str_replace(array(0,1,2,3,4,5,6,7,8,9), array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'), $date);
			}
			return $date;
		}
		// Init
		else {
			return date($format, $time);
		}
	}

	/**
	 * Timezone offset
	 *
	 * @param string eg : UTC
	 * @param string eg : Europe/Paris
	*/
	public function timezone_offset($timezone_1 = NULL, $timezone_2 = NULL) {
		if ($timezone_1 == NULL) {
			$timezone_1 = $this->ma_timezone;
		}

		if ($timezone_2 == NULL) {
			$timezone_2 = $this->client_timezone;
		}

		if (isset($this->offsets[ $timezone_1.$timezone_2 ]) == TRUE) {
			return $this->offsets[ $timezone_1.$timezone_2 ];
		}
		else if ($timezone_1 == $timezone_2) {
			return 0;
		}

		$origin_dtz = new DateTimeZone($timezone_1);
		$origin_dt = new DateTime('now', $origin_dtz);

		$remote_dtz = new DateTimeZone($timezone_2);
		$remote_dt = new DateTime('now', $remote_dtz);

		$offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
		$this->offsets[$timezone_1.$timezone_2] = $offset;
		return $offset;
	}

	/**
	 * Custom date to main timezone
	 *
	 * @param  string
	 * @param  bool
	 * @param  string|NULL
	 * @param  bool
	 * @return int|string on success/int zero on failure
	*/
	public function to_main_time($user_time, $user_calendar = FALSE, $client_timezone = NULL, $return_timestamp = TRUE) {
		$input_date = $this->date_to_array($user_time);
		if ($input_date == FALSE) {
			return 0;
		}

		// Convert to Gregorian
		if (TRUE == $user_calendar)
		{
			$grgrgi_date = $this->convert_to_gregorian($input_date['y'].'-'.$input_date['m'].'-'.$input_date['d'], $user_calendar);
			if (isset($grgrgi_date['y'], $grgrgi_date['m'], $grgrgi_date['d']) == TRUE) {
				$input_date['y'] = $grgrgi_date['y'];
				$input_date['m'] = $grgrgi_date['m'];
				$input_date['d'] = $grgrgi_date['d'];
			}
		}

		// Input Timestamp
		// in timestamp datei ast ke user vared karde,
		$input_stamp = mktime($input_date['h'], $input_date['i'], $input_date['s'], $input_date['m'], $input_date['d'], $input_date['y']);

		// Timezone offset
		if ($client_timezone == NULL) {
			$client_timezone = $this->client_timezone;
		}

		// agar zone ha yeki bood dige neeazi be mohasebe nist,
		if ($client_timezone == $this->ma_timezone) {
			if ($return_timestamp != TRUE) {
				return date('Y-m-d H:i:s', $input_stamp);
			}
			else {
				return $input_stamp;
			}
		}

		// ekhtelafe zamanie 2 timezone
		$server_offset = $this->timezone_offset($this->ma_timezone, $client_timezone);

		// in zamane nahaie ast (ekhtelaf zone ha hesab shode)
		$final_stamp = $input_stamp + $server_offset;
		if ($return_timestamp == FALSE) {
			return date('Y-m-d H:i:s', $final_stamp);
		}
		else {
			return $final_stamp;
		}
	}

	/**
	 * Datetime to array
	 * string (iso date) or mktime to array
	 *
	 * @param  string|NULL
	 * @return array on success/bool FALSE on failure
	*/
	public function date_to_array($input) {
		if (is_numeric($input)) {
			$input = date('Y-m-d H:i:s', 1*$input);
		}

		$input_string = explode(' ', $input);
		if (isset($input_string[0]) == FALSE) {
			$this->error = 'invalid_date_'.__LINE__;
			return FALSE;
		}

		// Date
		$input_date = explode('-', $input_string[0]);
		if (isset($input_date[0], $input_date[1], $input_date[2]) == FALSE) {
			$this->error = 'invalid_date_'.__LINE__;
			return FALSE;
		}

		// Time
		$input_time = [];
		if (isset($input_string[1]) == TRUE) {
			$input_time = explode(':', $input_string[1]); // H:i:s
		}

		return [
			'y' => $input_date[0],
			'm' => $input_date[1],
			'd' => $input_date[2],
			'h' => isset($input_time[0]) ? $input_time[0] : 0,
			'i' => isset($input_time[1]) ? $input_time[1] : 0,
			's' => isset($input_time[2]) ? $input_time[2] : 0,
		];
	}

	/**
	 * Load Calendar API
	 *
	 * @param  string|NULL
	 * @return bool
	*/
	public function calendar($calendar = NULL) {
		if ($calendar == NULL) {
			$calendar = ma_config('calendar');
		}

		if ($calendar == FALSE) {
			$this->calendar = 'gregorian';
			return TRUE;
		}

		switch ($calendar) {
			case 'persian':
				if ($this->persian == NULL) {
					$this->persian = new ma_persian_date;
				}
				$calendar_load = TRUE;
			break;
			case 'gregorian':
				$calendar_load = TRUE;
			break;
			default:
				$calendar_load = FALSE;
		}

		if ($calendar_load == FALSE) {
			ma_save_log('error', 'Calendar API error (date.class.php)');
			$this->calendar = 'gregorian';
			return FALSE;
		}

		$this->calendar = $calendar;
		return TRUE;
	}

	/**
	 * Convert
	 *
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return string on success/FALSE on failure
	*/
	public function convert($time, $from_calendar, $to = 'gregorian') {
		if ($to == 'gregorian') {
			return $this->convert_to_gregorian($time, $from_calendar);
		}
		return FALSE;
	}

	/**
	 * Convert to Gregorian
	 *
	 * @param  string
	 * @param  string
	 * @return string on success/FALSE on failure
	*/
	public function convert_to_gregorian($time, $from_calendar) {
		// Calendar
		$c_date = FALSE;

		// D
		$d = explode('-', $time);
		if (isset($d[0], $d[1], $d[2]) == FALSE) {
			$this->error = 'invalid_time';
			return FALSE;
		}
		else {
			$day = 1*$d[2];
			$month = 1*$d[1];
			$year = 1*$d[0];
		}

		switch ($from_calendar) {
			case 'persian':
				if (function_exists('jalali_to_gregorian') == FALSE) {
					require_once(MA_PATH.'/library/calendar/jdf.php');
				}
				$c_date = jalali_to_gregorian($year, $month, $day);
				if (is_array($c_date) == FALSE) {
					$this->error = 'invalid_date';
					return FALSE;
				}
				else {
					$c_date = [
						'd' => ($c_date[2] < 10) ? '0'.$c_date[2] : $c_date[2],
						'm' => ($c_date[1] < 10) ? '0'.$c_date[1] : $c_date[1],
						'y' => $c_date[0]
					];
				}
			break;
		}

		if ($c_date == FALSE) {
			$this->error = 'invalid_calendar_from';
			return FALSE;
		}
		else {
			return $c_date;
		}
	}

	/**
	 * Days between two dates
	 *
	 * @param  string|int
	 * @param  string|int
	 * @return int
	*/
	public function between_days($date1, $date2) {
		$now = (is_numeric($date1)) ? 1*$date1 : strtotime($date1);
		$your_date = (is_numeric($date2)) ? 1*$date1 : strtotime($date2);
		$datediff = $now - $your_date;
		return floor($datediff/86400);
	}

	/**
	 * Between tow date
	 * in method 2 date ra moghayese mikonad,
	 * date be tartibe [koochak,bozorg] return mikonad.
	 *
	 * @param  string
	 * @param  string
	 * @return array
	*/
	public function sort_date($date1, $date2) {
		$str1 = strtotime($date1);
		$str2 = strtotime($date2);
		if ($str1 < $str2) {
			return [$date1, $date2, $str1, $str2];
		}
		else {
			return [$date2, $date1, $str2, $str1];
		}
	}

	/**
	 * The last day of month
	 *
	 * eg : last_day_month(1[jan], gregorian)
	 *
	 * @param  int
	 * @param  string
	 * @return string on success/FALSE on failure
	*/
	public function last_day_of_month($month = NULL, $year = NULL, $format = NULL, $calendar = NULL) {
		if ($calendar == NULL) {
			$calendar = $this->calendar;
		}

		if ($calendar == 'persian') {
			if (!$this->persian) {
				$this->calendar_select('persian');
			}
			return $this->persian->last_month_day($month, $year, $format);
		}
		// Gregorian
		else {
			$result = strtotime("{$year}-{$month}-01");
			$result = strtotime('-1 second', strtotime('+1 month', $result));
			return $last = date($format, $result);
		}
	}

	/**
	 * Available libraries
	 *
	 * @return array
	*/
	public function libraries_info() {
		return [
			'gregorian' => TRUE,
			'persian' => TRUE
		];
	}
}