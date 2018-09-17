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
 * Captcha Class
 *
 * @modified : 17 September 2018
 * @created  : 25 October 2017
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

if (extension_loaded('gd') == FALSE || function_exists('gd_info') == FALSE) {
	ma_exit('Error: GD library is not installed on your web server.');
}

class ma_captcha
{
	// Characters
	public $characters = 'ABCDEFGHJKLMNPRSTUVWXYZabcdefghjkmnprstuvwxyz23456789';

	// Text
	public $text = NULL;

	// Text length min/max
	public $text_min = 4;
	public $text_max = 5;

	// Size (array[width, height])
	public $image_size = [180, 70];

	// Background color
	public $background_color = [255, 255, 255];

	// Use background
	public $background = FALSE;

	// Lines number (array|int|0=desabled)
	public $line_number = [2, 3];

	// Pixel (array|int|0=desabled)
	public $pixel_number = [200, 350];

	// Circle (array|int|0=desabled)
	public $circle_number = 1;

	// Blur (float|0=desabled)
	public $blur = 0.0;

	// Text color (array)
	public $text_color = [0, 0, 0];

	// Line color (array)
	public $line_color = [0, 0, 0];

	// Pixel color (array)
	public $pixel_color = [0, 0, 0];

	// Pixel color (array)
	public $circle_color = [0, 0, 0];

	// Fonts dir
	public $font_dir = MA_PATH.'/captcha/font';

	// Background dir
	public $background_dir = MA_PATH.'/captcha/background';

	// Font file - selected file - (string)
	public $font_file;

	// Session name
	public $session_key_name = 'maCaptcha';

	// Session timeout (second)
	public $session_timeout = 60;

	// Files list
	protected $files = [];

	// Image
	protected $image;

	// Random point - circle (array|null)
	protected $rand_point = NULL;

	// Filter
	protected $filter = [
		'font' => [],
		'background' => [],
		'text' => ['fuck', 'shit', 'sex']
	];

	// Client
	protected $client;

	/**
	 * Constructor
	*/
	public function __construct() {
		$this->client =& ma_class('client');
		return;
	}

	/**
	 * Files
	 *
	 * @param  bool
	 * @return bool TRUE|array on success/bool FALSE on failure
	*/
	public function get_files($return = FALSE) {
		if ($return != TRUE) {
			$this->files['font'] = $this->scan_dir('font');
			$this->files['background'] = $this->scan_dir('background');
			return TRUE;
		}
		return [
			'font' => $this->scan_dir('font'),
			'background' => $this->scan_dir('background')
		];
	}

	/**
	 * Scan dir
	 *
	 * @param  bool
	 * @return array on success/bool FALSE on failure
	*/
	protected function scan_dir($n) {
		$files = [];
		$dir = $n == 'font' ? $this->font_dir : $this->background_dir;
		if (is_dir($dir) == FALSE) {
			$this->error = 'error_wrong_files_folder_path, '.$dir;
			return FALSE;
		}

		$d_files = scandir($dir);
		foreach ($d_files as $f) {
			if ($f == '.' || $f == '..') {
				continue;
			}
			$mim = explode('.', $f);
			if (isset($mim[1]) == FALSE) {
				continue;
			}
			$mim = end($mim);
			if (($n == 'font' && $mim != 'ttf') || ($n != 'font' && $mim != 'png')) {
				continue;
			}

			// Filter
			if ($this->is_filter($n, $f) == TRUE) {
				continue;
			}

			$files[] = $dir.'/'.$f;
		}
		return $files;
	}

	/**
	 * Random - font & backgroun
	 *
	 * @param  bool
	 * @return string on success/bool FALSE on failure
	*/
	protected function rand_file($n = 'font') {
		$k = $n == 'font' ? 'font' : 'background';
		if (count($this->files[$k]) == 0) {
			return FALSE;
		}
		$c = $this->files[$k];
		$key = array_rand($c, 1);
		return $this->files[$k][$key];
	}

	/**
	 * Filter - Set
	 *
	 * @param  string
	 * @param  string|array
	 * @return bool TRUE on success/bool FALSE on failure
	*/
	public function set_filter($n, $value) {
		$n = strtolower($n);
		if (isset($this->filter[$n]) == FALSE) {
			return FALSE;
		}

		if (is_array($value)) {
			foreach($value as $v) {
				$this->filter[$n][] = $v;
			}
		}
		else {
			$this->filter[$n][] = $value;
		}
		return TRUE;
	}

	/**
	 * Filter - Get
	 *
	 * @return array
	*/
	public function get_filter() {
		return $this->filter;
	}

	/**
	 * Filter - isSet
	 *
	 * @param  string
	 * @param  string
	 * @return bool TRUE on success/bool FALSE on failure
	*/
	public function is_filter($n, $key) {
		// Text
		if ($n == 'text') {
			$key = strtolower($key);
			foreach ($this->filter['text'] as $f) {
				$f = strtolower($f);
				if (preg_match('/'.$f.'/i', $key)) {
					return TRUE;
				}
			}
		}
		// Files
		else {
			if (isset($this->filter[$n]) == FALSE) {
				return FALSE;
			}
			if (in_array($key, $this->filter[$n]) == TRUE) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Random characters
	 *
	 * @param  bool
	 * @return string
	*/
	protected function rand_characters() {
		$str = ma_random(mt_rand($this->text_min, $this->text_max), ['characters' => $this->characters]);

		// Filter
		if ($this->is_filter('text', $str) == TRUE) {
			$str = $this->rand_characters();
		}
		return $str;
	}

	/**
	 * Save - Session
	 *
	 * @param  string
	 * @return void
	*/
	protected function sess_save($input) {
		$_SESSION[$this->session_key_name] = [
			'c' => $this->hash($input),
			'e' => time() + $this->session_timeout,
			'ip' => $this->client->ip()
		];
		return;
	}

	/**
	 * Close - Session
	 *
	 * @param  string
	 * @return void
	*/
	public function destroy() {
		if (isset($_SESSION[$this->session_key_name]) == TRUE) {
			$_SESSION[$this->session_key_name] = NULL;
			unset($_SESSION[$this->session_key_name]);
		}
		return;
	}

	/**
	 * Validation - Session
	 *
	 * @param  string
	 * @param  bool
	 * @return string on success/bool FALSE on failure
	*/
	public function validation($input, $sess_destroy = TRUE) {
		$sess = NULL;

		if (isset($_SESSION[$this->session_key_name]['c']) == TRUE) {
			$sess = $_SESSION[$this->session_key_name];
		}

		if ($sess_destroy == TRUE) {
			$this->destroy();
		}

		if ($sess == NULL) {
			return FALSE;
		}

		// Check
		if (strlen($input) > $this->text_max) {
			return FALSE;
		}
		else if ($this->hash($input) != $sess['c']) {
			return FALSE;
		}
		else if (time() > $sess['e']) {
			return FALSE;
		}
		else if ($this->client->ip() != $sess['ip']) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Hash
	 *
	 * @param  string
	 * @return string
	*/
	protected function hash($input) {
		$enc_key = defined('MA_ENCRYPTION_KEY') ? MA_ENCRYPTION_KEY : 'MA';
		return md5(strtolower($input).$enc_key);
	}

	/**
	 * Display
	 *
	 * @param  bool
	 * @return void
	*/
	public function display($sess_save = TRUE) {

		if ($this->get_files() == FALSE) {
			ma_error(500, $this->error);
		}

		// Random files
		$this->rand_font();
		if ($this->background != FALSE) {
			$bg_file = $this->rand_file('background');
		}

		// Background
		if ($this->background != FALSE && $bg_file != FALSE) {
			$this->image_size = getimagesize($bg_file);
			$this->image = imagecreatefrompng($bg_file);
		}
		else {
			// Image size
			if ($this->image_size[0] < 80) {
				$this->image_size[0] = 80;
			}
			if ($this->image_size[1] < 50) {
				$this->image_size[1] = 50;
			}
			$this->image = imagecreate($this->image_size[0], $this->image_size[1]);
			imagecolorallocate($this->image, $this->background_color[0],
							   $this->background_color[1], $this->background_color[2]);
		}

		// Text
		$text = empty($this->text) == TRUE ? $this->rand_characters($sess_save) : $this->text;
		if ($sess_save == TRUE) {
			$this->sess_save($text);
		}

		/* Make */
		// Lines
		if ($this->line_number != 0) {
			$this->draw_line();
		}

		// Text
		$this->draw_text($text);

		// Circle
		$this->draw_circle();

		// Pixel
		if ($this->pixel_number != 0) {
			$this->draw_pixel();
		}

		// Last line
		$this->draw_last_line();

		// Blur
		if ($this->blur != 0) {
			$this->image_blur($this->blur);
		}

		// Send
		if (!$this->image) {
			ma_http_status(500);
			echo 'CAPTCHA ERROR.';
			exit(EXIT_FAILURE);
		}

		header('Expires: Wed, 25 Oct 2017 00:00:00 GMT');
		header('Last-Modified: '.gmdate("D, d M Y H:i:s").' GMT');
		header('Content-Type: image/png');
		imagepng($this->image);
		imagedestroy($this->image);
		return strtolower($text);
	}

	/**
	 * Font
	 *
	 * @return void
	*/
	protected function rand_font() {
		$this->font_file = $this->rand_file('font');
		if ($this->font_file == FALSE) {
			ma_error(500, 'error_captcha_font');
		}
		return;
	}

	/**
	 * Set lines
	 *
	 * @return void
	*/
	protected function draw_line() {
		$num = is_array($this->line_number) ? mt_rand($this->line_number[0], $this->line_number[1]) : $this->line_number;
		$color = imagecolorallocate($this->image, $this->line_color[0], $this->line_color[1], $this->line_color[2]);
		for ($i=0; $i<$num; ++$i) {
			imagesetthickness($this->image, mt_rand(0, 1));
			imageline($this->image, 0, mt_rand(0, $this->image_size[1]),
					$this->image_size[0], mt_rand(20, $this->image_size[1]), $color);
			imageline($this->image, mt_rand(0, $this->image_size[0]), $this->image_size[1],
					mt_rand(0, $this->image_size[0]), 0, $color);
		}
	}

	/**
	 * Lst line
	 *
	 * @return void
	*/
	protected function draw_last_line() {
		$color = imagecolorallocate($this->image, $this->background_color[0], $this->background_color[1], $this->background_color[2]);
		for ($i=0; $i<3; ++$i) {
			imagesetthickness($this->image, mt_rand(0, 2));
			imageline($this->image, 0, mt_rand(0, $this->image_size[1]),
					$this->image_size[0], mt_rand(20, $this->image_size[1]), $color);
			imageline($this->image, mt_rand(0, $this->image_size[0]), $this->image_size[1],
					mt_rand(0, $this->image_size[0]), 0, $color);
		}
	}

	/**
	 * Draw pixel (noise)
	 *
	 * @return void
	*/
	protected function draw_pixel() {
		$num = is_array($this->pixel_number) ? mt_rand($this->pixel_number[0], $this->pixel_number[1]) : $this->pixel_number;
		$pixel_color = imagecolorallocate($this->image, $this->pixel_color[0], $this->pixel_color[1], $this->pixel_color[2]);
		for ($i=0; $i<$num; ++$i) {
			imagesetpixel($this->image, rand()%$this->image_size[0], rand()%$this->image_size[1], $pixel_color);
		}
	}

	/**
	 * Draw arc
	 *
	 * @return void
	*/
	protected function draw_circle() {
		$num = is_array($this->circle_number) ? mt_rand($this->circle_number[0], $this->circle_number[1]) : $this->circle_number;
		$color = imagecolorallocate($this->image, $this->circle_color[0], $this->circle_color[1], $this->circle_color[2]);
		imagesetthickness($this->image, mt_rand(0, 2));
		for ($i=0; $i<$num; ++$i) {
			if ($i > 0) {
				$wh = $this->image_size[1]-20;
				if ($wh > 30) {
					$wh = 31;
				}
				$wh = mt_rand(30, $wh);
				imagearc($this->image, mt_rand(10, ($this->image_size[0]-70)), mt_rand(10, ($this->image_size[0]-70)),
						$wh, $wh, 0, mt_rand(300, 360), $color);
			}
			else {
				$cx = $this->rand_point[1]+10;
				$cx = floor($cx + $this->percentage($this->rand_point[1], 45));
				$cy = floor($this->percentage($this->rand_point[2], 70));
				$size = $this->rand_point[0]+15;
				imagearc($this->image, $cx, $cy, $size, $size, 10, mt_rand(260, 360), $color);
			}
		}
	}

	/**
	 * Draw text
	 *
	 * @param  string
	 * @return void
	*/
	protected function draw_text($text) {
		$length = strlen($text);

		$c = $this->percentage($this->image_size[0], 4);
		$size = floor(($this->image_size[0]/$length)-$c);

		$angle_max = 10+round($this->percentage($size, 8));
		$angle_min = -1*abs($angle_max);

		$y_min = floor($this->percentage($this->image_size[1], 50));
		$y_max = floor($this->percentage($this->image_size[1], 80));
		if ($y_min < $size) {
			$y_min = 10+$size;
		}
		if ($y_min > $y_max) {
			$y_max = $y_min+5;
		}

		$color = imagecolorallocate($this->image, $this->text_color[0], $this->text_color[1], $this->text_color[2]);
		$rp = mt_rand(0, ($length-2));
		for ($i=0; $i<$length; ++$i) {
			$x = round(mt_rand($c, $c*2))+$size*$i;
			$y = mt_rand($y_min, $y_max);
			$angle = rand($angle_min, $angle_max);
			imagettftext($this->image, $size, $angle, $x, $y, $color, $this->font_file, $text[$i]);
			if ($this->rand_point == NULL && $i == $rp) {
				$this->rand_point = [$size, $x, $y, $color];
			}
		}
	}

	/**
	 * Blur
	 *
	 * @param  float
	 * @return void
	*/
	protected function image_blur($blur = 0.02) {
		imagefilter($this->image, IMG_FILTER_SMOOTH, $blur);
	}

	/**
	 * Percentage
	 *
	 * @param  int
	 * @param  int
	 * @return int
	*/
	protected function percentage($num, $percentage) {
		return ($percentage/100)*$num;
	}
}