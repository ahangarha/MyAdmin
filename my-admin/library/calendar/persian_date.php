<?php
/**
 * Persian Date
 *
 * GPL License
 *
 * @developer : Hossein Shams/shamsoft2006@yahoo.com
 * @modified    : 15 November 2017 (by PersianIcon)
 *
 * Format Help
 * ---  Day  ---
 * d : 01 to 31
 * j : 1 to 31
 * D : shanbe ta jom'e[l]
 * N : 1 to 7
 * z : 1 to 365
 * ---  Month  ---
 * M : farvardin ta esfand[F]
 * m : 01 to 12
 * n : 1 to 12
 * ---  Year  ---
 * y : 87
 * Y : 1387
 * ---  Time  ---
 * a : sobh ya asr
 * g : Hour 1 to 12
 * G : Hour 1 to 24
 * h : Hour 01 to 12
 * H : Hour 01 to 24
 * i : minute 00 to 59
 * I : minute 0 to 59
 * s : second 00 to 59
 * S : second 0 to 59
 * ---  Full Date  ---
 * u : jome 1387/09/01 20:23:46
 * U : jome 1 azar 1387 va sa@ 20:23:46
*/

defined('MA_PATH') or exit('Restricted access');

class ma_persian_date
{
	public function date($format = '', $timestamp = -1, $nonChar = '~') {
		$strDate = '';
		if (is_numeric($timestamp)) {
			if ($timestamp < 0) {
					$timestamp = time();
			}
		}
		else {
			$timestamp = strtotime($timestamp);
		}

		list($y, $m, $d) = preg_split ('/-/', date('Y-m-d', $timestamp));
		list($y, $m, $d) = $this->pDate($y, $m, $d);
		for ($i = 0; $i < strlen($format); $i++) {
			switch ($format[$i]) {
				case $nonChar:
					while ($format[++$i] != $nonChar)
						$strDate .= $format[$i];
				break;
				case 'd':
					$strDate .= $this->addZero($d);
				break;
				case 'j':
					$strDate .= $d;
				break;
				case 'D':
					$strDate .= $this->dayName(date('w', $timestamp));
				break;
				case 'N':
					$a = (date('N', $timestamp) + 2) % 7;
					if ($a > 0)
						$strDate .= $a;
					else
						$strDate .= '7';
				break;
				case 'z':
					$i_m = $m - 1;
					$days = 0;
					if ($i_m < 6)
						$days += $i_m * 31;
					else
						$days += $i_m * 30 + 6;
					$days += $d;
					$strDate .= $days;
				break;
				case 'M'://Default:F
					$strDate .= $this->monthName($m);
				break;
				case 'm':
					$strDate .= $this->addZero($m);
				break;
				case 'n':
					$strDate .= $m;
				break;
				case 'y':
					$strDate .= substr($y, 2, 2);
				break;
				case 'Y':
					$strDate .= $y;
				break;
				case 'a':
					$a = date('a', $timestamp);
					if ($a == 'am')
						$strDate .= 'صبح';
					else
						$strDate .= 'عصر';
				break;
				case 'g':
					$strDate .= date('g', $timestamp);
				break;
				case 'G':
					$strDate .= date('G', $timestamp);
				break;
				case 'h':
					$strDate .= date('h', $timestamp);
				break;
				case 'H':
					$strDate .= date('H', $timestamp);
				break;
				case 'i':
					$strDate .= date('i', $timestamp);
				break;
				case 'I':
					$strDate .= $this->removeZero(date('i', $timestamp));
				break;
				case 's':
					$strDate .= date('s', $timestamp);
				break;
				case 'S':
					$strDate .= $this->removeZero(date('s', $timestamp));
				break;
				case 'u':
					$strDate .= $this->date('l Y/m/d H:i:s', $timestamp, $nonChar);
				break;
				case 'U':
					$strDate .= $this->date('l j M Y و ساعت H:i:s', $timestamp, $nonChar);
				break;
				default:
					$strDate .= $format[$i];
				break;
			}
		}
		return $strDate;
	}

	protected function addZero($num) {
		if (strlen($num) == 1) {
			return '0' . $num;
		}
		return $num;
	}

	protected function removeZero($num) {
		if($num[0] == '0') {
			return substr($num, 1);
		}
		return $num;
	}

	protected function dayName($day) {
		$week = Array('يكشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه', 'شنبه');
		return $week[$day];
	}

	protected function monthName($monthnum) {
		switch ($monthnum) {
			case 1: return 'فروردين'; break;
			case 2: return 'ارديبهشت'; break;
			case 3: return 'خرداد'; break;
			case 4: return 'تير'; break;
			case 5: return 'مرداد'; break;
			case 6: return 'شهريور'; break;
			case 7: return 'مهر'; break;
			case 8: return 'آبان'; break;
			case 9: return 'آذر'; break;
			case 10: return 'دى'; break;
			case 11: return 'بهمن'; break;
			case 12: return 'اسفند'; break;
		}
	}

	protected function div($a, $b) {
		return (int) ($a / $b);
	}

	// This function is gregorian_to_jalali that imported from jalali.php
	protected function pDate($g_y, $g_m, $g_d) {
		$g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		$j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
		$gy = $g_y - 1600;
		$gm = $g_m - 1;
		$gd = $g_d - 1;
		$g_day_no = 365 * $gy + $this->div($gy + 3, 4) - $this->div($gy + 99, 100) + $this->div($gy + 399, 400);
		for ($i=0; $i < $gm; ++$i) {
			$g_day_no += $g_days_in_month[$i];
		}
		if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))) {
			$g_day_no++;
		}
		$g_day_no += $gd;
		$j_day_no = $g_day_no - 79;
		$j_np = $this->div($j_day_no, 12053);
		$j_day_no = $j_day_no % 12053;
		$jy = 979 + 33 * $j_np + 4 * $this->div($j_day_no, 1461);
		$j_day_no %= 1461;
		if ($j_day_no >= 366) {
			$jy += $this->div($j_day_no-1, 365);
			$j_day_no = ($j_day_no - 1) % 365;
		} 
		for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
		$j_day_no -= $j_days_in_month[$i];
		$jm = $i + 1;
		$jd = $j_day_no + 1;
		return array($jy, $jm, $jd);
	}

	// Check date
	public function check_date($jy, $jm, $jd) {
		$l_d = ($jm==12)?(($jy % 33 % 4-1 == (int) ($jy%33*.05))? 30 : 29) : 31 - (int)($jm/6.5);
		return ($jm>0 and $jd>0 and $jy>0 and $jm<13 and $jd<=$l_d) ? true : false;
	}

	// Last day of month
	public function last_month_day($month, $year, $format = 'd') {
		$month = 1*$month;
		$year = 1*$year;
		$days = 0;
		if ($month <= 6) {
			return 31;
		}
		else if ($month > 6 && $month <= 11) {
			return 30;
		}
		else if (12 == $month) {
			$d1 = $this->check_date($year, 12, 30);
			if ($d1) {
				return 30;
			}
			else {
				return 29;
			}
		}
	}
}