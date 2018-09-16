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
 * Thumbnail Class
 *
 * @modified : 31 December 2017
 * @created  : 26 March 2015
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

require_once(MA_PATH.'/third-party/PHPThumb/ThumbLib.inc.php');

class ma_thumbnail
{
	public $quality = 100;

	public $read_file_mode  = 0;
	public $write_file_mode = 0;

	protected $phpthumb = FALSE;
	protected $config = [];

	protected $method      = FALSE;
	protected $methods_lib = [
		'adaptiveResize',
		'adaptiveResizePercent',
		'adaptiveResizeQuadrant',
		'resizePercent', 
		'cropFromCenter',
		'resize'
	];

	protected $file_name;
	protected $file_path;
	protected $full_path;
	protected $resizes = [];

	/**
	 * Constructor
	*/
	function __construct() {
		$this->config = ma_include('/var/config/thumbnail.inc.php');
		if (!is_array($this->config)) {
			exit('err_thumbnail_config_file_not_found');
		}

		$this->quality = (int) $this->config['quality'];
		$this->method  = (string) $this->config['method'];

		$this->read_file_mode  = defined('MA_THUMBNAIL_R_MODE') ? MA_THUMBNAIL_R_MODE : MA_FILE_R_MODE;
		$this->write_file_mode = defined('MA_THUMBNAIL_W_MODE') ? MA_THUMBNAIL_W_MODE : MA_FILE_W_MODE;
	}

	/**
	 * Init
	*/
	public function init() {
		$this->file_path = NULL;
		$this->file_name = NULL;
		$this->full_path = NULL;
		return;
	}

	/**
	 * Set Image File
	 *
	 * @param  string file path
	 * @param  array
	 * @param  array
	 * @return bool
	*/
	public function image($filename, $path, $options = [], $plugins = []) {
		$this->file_path = rtrim($path, '/').'/';
		$this->file_name = $filename;
		$this->full_path = $this->file_path . $this->file_name;

		if (is_file($this->full_path) == FALSE) {
			$this->error = 'err_thumb_inavlid_image_file';
			return FALSE;
		}

		$this->phpthumb = PhpThumbFactory::create($this->full_path);
		return $this->phpthumb;
	}

	/**
	 * Auto
	 * by config default sizes
	 *
	 @ param bool
	*/
	public function auto_resize($save_path = NULL) {
		if (!$this->phpthumb) {
			return;
		}

		if (!$save_path) {
			$save_path = $this->file_path;
		}

		$all_sizes = $this->config['default_sizes'];
		rsort($all_sizes);
		foreach ($all_sizes as $size) {
			$d = $size[0].'x'.$size[1];
			$dir = $save_path . $d . '/';
			if (is_dir($dir) == FALSE) {
				@mkdir($dir, MA_DIR_W_MODE);
			}

			$this->resize($size[0], $size[1]);
			$this->save($dir.$this->file_name);
		}
	}

	/**
	 * Resize
	 *
	 * @param  int
	 * @param  int
	 * @param  bool
	 * @return mixed/GdThumb
	*/
	public function resize($width, $height = 0, $value = FALSE) {
		if (!$this->phpthumb) {
			return;
		}

		$key = $width.'x'.$height;
		/*if (isset( $this->resizes[$key] )) {
			return;
		}*/

		$this->resizes[$key] = [$width, $height, $value];

		// Set Options
		$this->phpthumb->setOptions([
			'jpegQuality'           => $this->quality,
			'resizeUp'              => FALSE,
			'correctPermissions'    => FALSE,
			'preserveAlpha'         => TRUE,
			'alphaMaskColor'        => [255, 255, 255],
			'preserveTransparency'  => TRUE,
			'transparencyMaskColor' => [0, 0, 0],
			'interlace' => NULL
		]);

		// Resize
		switch($this->method) {
			case 'adaptiveResize':
				return $this->phpthumb->adaptiveResize($width, $height);
			break;

			case 'adaptiveResizePercent':
				$value = ( is_int($value) ) ? $value : $this->config['a_resize_percent'];
				return $this->phpthumb->adaptiveResizePercent($width, $height, $value);
			break;

			case 'adaptiveResizeQuadrant':
				$value = (in_array($value, ['T', 'B', 'C', 'L', 'R'])) ? $value : $this->config['quadrant'];
				return $this->phpthumb->adaptiveResizeQuadrant($width, $height, $value);
			break;

			case 'resizePercent':
				$percent = FALSE;

				if (is_string($width)) {
					$exp = explode('%', $width);
					if (isset($exp[0])) {
						$percent =  $exp[0];
					}
				}

				if (!$percent) {
					$percent = $this->config['resize_percent'];
				}

				return $this->phpthumb->resizePercent($percent);
			break;

			case 'cropFromCenter':
				$height = ( FALSE == $height ) ? NULL : $height;
				return $this->phpthumb->cropFromCenter($width, $height);
			break;

			default:
				return $this->phpthumb->resize($width, $height);
		}
	}

	/**
	 * Image Cropping
	 * 
	 * @param  int
	 * @param  int
	 * @param  int
	 * @param  int
	 * @retunrn GdThumb
	*/
	public function crop($startX, $startY, $cropWidth, $cropHeight) {
		if (!$this->phpthumb) {
			return;
		}

		return $this->phpthumb->crop($startX, $startY, $cropWidth, $cropHeight);
	}

	/**
	 * Rotates image either 90 degrees clockwise or counter-clockwise
	 * 
	 * @param string $direction
	 * @retunrn GdThumb
	*/
	public function rotate($direction = 'CW') {
		if (!$this->phpthumb) {
			return;
		}

		return $this->phpthumb->rotateImage($direction);
    }

	/**
	 * Rotates image specified number of degrees
	 * 
	 * @param int $degrees
	 * @return GdThumb
	*/
	public function rotate_degrees($degrees) {
		if (!$this->phpthumb) {
			return;
		}

		return $this->phpthumb->rotateImageNDegrees($degrees);
	}

	/**
	 * Applies a filter to the image
	 * 
	 * @param int
	 * @retunrn GdThumb
	*/
	public function filter($filter, $arg1 = FALSE, $arg2 = FALSE, $arg3 = FALSE, $arg4 = FALSE) {
		if (!$this->phpthumb) {
			return;
		}

		return $this->phpthumb->imageFilter($filter, $arg1, $arg2, $arg3, $arg4);
	}


	/**
	 * Save the new thumbnail
	 *
	 * @param  string
	 * @retunrn GdThumb
	*/
	public function save($path, $file_type = NULL) {
		if (!$this->phpthumb) {
			return;
		}

		if (is_file($path) && !is_writable($path)) {
			@chmod($path, $this->write_file_mode);
		}

		$this->phpthumb->save($path, $file_type);
		@chmod($path, $this->read_file_mode);
	}

	/**
	 * Set Resize Method
	 *
	 * @param  string
	 * @return bool
	*/
	public function set_method($method) {
		$return = NULL;
		foreach ($this->methods_lib as $m) {
			if ($method == $m) {
				$return = TRUE;
				break;
			}
		}

		if (!$return) {
			return FALSE;
		}

		$this->method = $method;
		return TRUE;
	}

	/**
	 * Get Resize Method
	 *
	 * @return string
	*/
	public function get_method() {
		return $this->method;
	}
}