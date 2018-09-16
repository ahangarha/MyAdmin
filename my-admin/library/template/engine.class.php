<?php
/**
 * MyAdmin CMS
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
 * @package	  MyAdmin Content Management System
 * @link      http://www.myadmincms.com/
 * @copyright Persian Icon Software
 * @link      https://www.persianicon.com/
*/

defined('MA_PATH') or exit('Restricted access');

/**
 * Template Engine Class
 *
 * @modified : 28 December 2017
 * @created  : November 2014
 * @since    : version 0.4
*/

define('TPL_ENGINE_V', '0.8.2');

class ma_template_engine
{
	// Templates directory
	protected $TemplateDir;

	// Compiles directory
	protected $CompileDir;

	// Compiles directory
	protected $CacheDir;

	// Internal Vars
	protected $tpl = []; // tpl file info
	protected $V = [];

	// Main TPL (file khaste shode)
	protected $main_tpl = [];

	// Parser
	protected $parser_api;

	// Dir Chmod
	public $dir_chmod = 0755;

	/**
	 * Main Display
	*/
	protected function main_display($filename, $uniqid = NULL) {
		// Save
		$this->main_tpl = array('filename'=> $filename, 'uniqid'=> $uniqid);

		// Executable file path
		$executable_file = self::get_executable_file($filename, $uniqid);
		if (FALSE == $executable_file) {
			return '';
		}

		// Final code (PHP)
		return $this->_run($executable_file, $filename);
	}

	/**
	 * Sub Template
	*/
	protected function main_subTemplate($filename, $unuqid = NULL) {
		return self::get_executable_file($filename, $unuqid, $subTemplate = TRUE);
	}

	/**
	 * Template File Content Compile/Display
	*/
	protected function get_executable_file($filename, $uniqid = NULL , $subTemplate = NULL) {
		// Aval Address tamame file ha ra moshakhas mikonim
		self::_paths( $filename, $uniqid, $subTemplate);

		// agar compile new lazem nabood cache ra check mikonim
		$isCompiled = self::is_compiled( $filename );
		//$isCompiled = FALSE;//test

		if ($isCompiled == TRUE) {
			// Cache ya Compile file neeaz be update nadarad:
			if ( $this->cache && !$subTemplate ) {
				if (self::is_cache($filename) == TRUE) {
					return $this->tpl[$filename]['cache_file'];
				}
				else {
					// file cache vojood nadarad,
					// bayad aval file cache ijad shavad:
					self::compiled_to_cache($filename);
					return $this->tpl[$filename]['cache_file'];
				}
			}

			// cache on nist va file compile shode return mishavad.
			return $this->tpl[$filename]['compiled_file'];
		}

		// New Compile
		$nc = self::new_compile($filename);
		if (empty($nc)) {
			return FALSE;
		}

		// agar cache oun bud file cahce shode ra ejra mikonim
		if ($this->cache && $subTemplate != TRUE) {
			self::compiled_to_cache($filename, NULL, $subTemplate);
			return $this->tpl[$filename]['cache_file'];
		}

		return $this->tpl[$filename]['compiled_file'];
	}

	/**
	 * Compiler
	 * baray compile kardan TPL aval content file grefte mishavad,
	*/
	protected function new_compile($filename) {
		// Type
		$type = explode('.', $this->tpl[$filename]['file']);
		if (end($type) == 'phtml') {
			$this->php_enabled = TRUE;
		}

		// Get Content Code
		$source_code = file_get_contents($this->tpl[$filename]['file']);

		// Compile
		$this->load_parser_api();

		$template_code = $this->parser_api->compile($source_code, $this->tpl[$filename]['file'],
								$this->cache, $this->tpl[$filename]['dir']);
		if (empty($template_code)) {
			return FALSE;
			//exit('lightpl_compiler_error_' . __LINE__ );
		}

		// Clean Nocache
		if ( FALSE == $this->cache ) {
			$template_code = $this->clear_nocache( $this->tpl[$filename]['file'] , $template_code );
		}

		// Save File
		$fp = fopen( $this->tpl[$filename]['compiled_file'] , 'wb');
		fwrite($fp, '<?php defined(\'TPL_ENGINE_V\') or exit(\'Restricted access\'); ?>'.$template_code);
		fclose($fp);
		return TRUE;
	}

	/**
	 * Load Parser Class
	 *
	 * @return bool
	*/
	protected function load_parser_api() {
		if ($this->parser_api) {
			return;
		}

		if (class_exists('ma_template_parser') == FALSE) {
			require_once(__DIR__.'/parser.class.php');
		}

		$conf = [
			'checksum' => [],
			'debug' => $this->debug,
			'php_enabled' => $this->php_enabled,
			'black_list_preg' => '',
			'auto_escape' => $this->auto_escape,
			'sandbox' => TRUE,
			'strip' => $this->strip,
			'left_delimiter'  => $this->left_delimiter,
			'right_delimiter' => $this->right_delimiter
		];

		$this->parser_api = new ma_template_parser($conf);
		$conf = NULL;
	}

	/**
	 * Clear nocache tags
	 *
	 * remove kardan tag nocache.
	 * baray zamani ke file compile mishavad
	 * va FALSE == cache ast.
	 *
	 * @param string
	 * @return string
	*/
	protected function clear_nocache($filename, $template_code) {
		$uniqid = md5($filename);
		$ldl = $this->left_delimiter;
		$rdl = $this->right_delimiter;
		return str_replace([$ldl.'ltpnc-'.$uniqid.$rdl, $ldl.'/ltpnc-'.$uniqid.$rdl], '', $template_code);
	}

	/** 
	 * Created New Cache File / Nocache Parse
	 *
	 * 1 -> include ha ra peyda mikonim, code anha ra be source ezafe mikonim,
	 *   -> in kar baes mishavad tamame code baray cache kamel khande shavad.
	 * 2 -> {nocache} ra az tpl joda mikonim, dar Array migozarim
	 * 3 -> php ra run mikonim
	 * 4 -> nocache ra az php joda mikonim, array ha ra avaz mikonim
	*/
	protected function compiled_to_cache( $filename, $template_code = NULL) {
		if (!$template_code) {
			$template_code = file_get_contents($this->tpl[$filename]['compiled_file']);
		}

		// Nocache Parse
		$ima = '/subTemplate\(\"(.*?)\"\)/is';

		// Include - code file hay include shode be $template_code ezafe mishavad.
		preg_match_all($ima, $template_code, $include);
		$finalCode = [];
		// avaz kardan tag include ba source code
		$nocache_uniqid = [];
		if (isset( $include[1][0])) {
			for ($i=0; $i<count($include[1]); ++$i) {
				$sub_filename = $include[1][$i];
				$exe_file = self::get_executable_file($sub_filename, $uniqid = NULL, $subTemplate = TRUE);
				$code = file_get_contents( $exe_file );
				$template_code = str_replace('<?php require self::subTemplate("'.$sub_filename.'"); ?>', $code, $template_code);
				$nocache_uniqid[] = $this->tpl[ $sub_filename ]['file'];
			}
		}

		// Temp Save
		$this->dir_check('cache');

		$fp = fopen( $this->tpl[$filename]['cache_file'], 'wb');
		fwrite($fp, $template_code);

		// Final Code
		$nocache_uniqid[] = $this->tpl[$filename]['file'];
		$final_php_code = $this->_run( $this->tpl[$filename]['cache_file'], $filename);
		$final_php_code = self::nocache_php($template_code, $final_php_code, $nocache_uniqid);

		// Save
		$fp = fopen( $this->tpl[$filename]['cache_file'], 'wb');
		fwrite($fp, '<?php defined(\'TPL_ENGINE_V\') or exit(\'Restricted access\'); ?>'.$final_php_code);
		fclose($fp);

		$nocache_php = $nocache_tpl = $php_code = $template_code = NULL;
		return TRUE;
	}

	/**
	 * Nocache Detector
	 * in func nocache ha ra source va php jabeja mikonad.
	*/
	protected function nocache_php($compile_code, $php_code, $nocache_uniqid) {
		$ldl = $this->left_delimiter;
		$rdl = $this->right_delimiter;

		for ($i=0; $i<count($nocache_uniqid); $i++) {
			$uniqid = md5( $nocache_uniqid[$i]);
			$pma = '/'.$ldl.'ltpnc-'.$uniqid.$rdl.'(.*?)\\'.$ldl.'\/ltpnc-'.$uniqid.$rdl.'/s';

			// Find TPL Nocache (peeyda kardan {nocache} dar compile code)
			preg_match_all($pma, $compile_code, $tpl_nocache);

			// peyda kardan {nocache} dar php
			preg_match_all($pma, $php_code, $php_nocache);

			// taviz code php ba compile
			for ($x=0; $x<count($php_nocache[0]); ++$x) {
				if ( isset($php_nocache[0][$x], $tpl_nocache[1][$x])) {
					$php_code = str_replace($php_nocache[0][$x], $tpl_nocache[1][$x], $php_code);
				}
			}
		}

		return $php_code;
	}

	/**
	 * Assign
	 * set kardan var hay lazem baray tpl
	*/
	protected function main_assign($variable, $value = NULL) {
		if (is_array($variable)) {
			$this->V += $variable;
		}
		else {
			$this->V[$variable]= $value;
		}
	}

	/**
	 * Compilded Last Modified
	 * check mikonad ke file compile shode key renew shod,
	 *  TRUE  : compile lazem nist.
	 *  FALSE : tpl bayad compile shavad.
	*/
	protected function is_compiled($filename, $uniqid = NULL) {
		if (isset($this->tpl[$filename]) == FALSE) {
			self::_paths($filename, $uniqid, $subTemplate = NULL);
		}

		if (is_file($this->tpl[$filename]['compiled_file']) == FALSE) {
			return FALSE;
		}

		if ($this->compile_check == FALSE) {
			return TRUE;
		}

		$compile_time = filemtime( $this->tpl[$filename]['compiled_file']);
		if ($this->tpl[$filename]['last_modified'] < $compile_time) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Is Cache?
	 * check mikonad ke file cache shode va update mikhahad ya na
	 *  TRUE  : cache vojood darad.
	 *  FALSE : file cache shode vojood nadarad.
	*/
	protected function is_cache( $filename, $uniqid = NULL) {
		// Cache File Check
		if ($this->cache == FALSE) {
			return FALSE;
		}

		if (isset($this->tpl[$filename]) == FALSE) {
			self::_paths($filename, $uniqid, $subTemplate = NULL);
		}

		if (is_file( $this->tpl[$filename]['cache_file']) == FALSE) {
			return FALSE;
		}

		$cache_modified = filemtime( $this->tpl[$filename]['cache_file']);
		$cache_expire = $this->cache_time + $cache_modified;
		if (time() >= $cache_expire) {
			return FALSE;
		}

		// cahe file, is good
		return TRUE;
	}

	/**
	 * Cleaer Cache File
	*/
	protected function clear_cache( $filename , $uniqid = NULL) {
		if (isset($this->tpl[$filename]) == FALSE) {
			self::_paths($filename, $uniqid);
		}

		// Remove
		if (is_file($this->tpl[$filename]['cache_file']) == TRUE) {
			@unlink($this->tpl[$filename]['cache_file']);
		}
	}

	/**
	 * Cleaer All Cache
	*/
	protected function clear_all_cache() {
		if (isset($this->CompileDir) == FALSE) {
			return;
		}
	}

	/**
	 * Directroy Check
	 * in func address file va dir ha ra set mikonad
	 * hamintor check mikonad ke folder hay lazem vojood darad ya na.
	*/
	protected function _paths( $filename, $uniqid = NULL, $subTemplate = NULL) {
		// Check
		if (isset($this->tpl[$filename]) == TRUE) {
			return;
		}

		// Sort
		// last is first
		$TemplateDir = $this->TemplateDir;
		rsort($TemplateDir);

		$dir = $file = NULL;
		// mitavan az chandin dir estefade kard,
		// bayad check shabvad ke tpl dar kodam dir ast
		if (!isset($TemplateDir[1]) && is_file($TemplateDir[0].$filename)) {
			$dir  = $TemplateDir[0];
			$file = $TemplateDir[0].$filename;
		}
		else {
			foreach ($TemplateDir as $k) {
				$tFile =  $k . $filename;
				if (is_file($tFile) == TRUE) {
					$dir  = $k;
					$file = $k.$filename;
					break;
				}
			}
		}

		if ($dir == NULL) {
			if (!$filename) {
				$filename = '';
			}
			exit('template_error_file_not_found_'.__LINE__.'('.$filename.')');
		}

		// Tpl Info
		if ($this->cache) {
			$compiled_file = 'cache.'. $filename;
		}
		else {
			$compiled_file = $filename;
		}
		$compiled_file = str_replace('/', '-', $compiled_file);

		$this->tpl[$filename] = array(
			'file' => $file,
			'dir'  => $dir,
			'compiled_file' => $this->CompileDir . md5($dir.$filename.'com-d').'.'.$compiled_file.'.php',
			'last_modified' => filemtime($file)
		);

		// For Inculde files on phtml templates.
		$this->V['_tpl_path_dir'] = $dir;

		// agar $subTemplate nabood
		if (!$subTemplate) {
			// Compiled Dir
			$this->dir_check();

			// Cache File
			$cache_file = str_replace('/', '-', $filename) . '.cache.php';
			$this->tpl[$filename]['cache_file'] = $this->CacheDir . md5($dir . $uniqid . $filename . 'cache-d') .'.'. $cache_file;

			// Cache Dir
			if (TRUE == $this->cache) {
				$this->dir_check('cache');
			}
		}
	}

	/**
	 * Address dir hara clean mikonad
	 *
	 * @param   string
	 * @return  string
	*/
	protected function cleaning_path($dir) {
		$dir = htmlspecialchars(trim($dir));
		return preg_replace(array('/\\\/', '/(\/+)/', '{/$}'), array('/', '/', ''), $dir);
	}

	/**
	 * Check Dir
	 *
	 * @param  string|NULL
	 * @param  bool
	 * @return bool FALSE
	*/
	protected function dir_check($k = NULL, $new = TRUE) {
		if ($k == 'cache' && is_dir($this->CacheDir) == FALSE) {
			mkdir($this->CacheDir, $this->dir_chmod, TRUE);
		}
		else if (is_dir($this->CompileDir) == FALSE) {
			mkdir($this->CompileDir, $this->dir_chmod, TRUE);
		}
	}

	/**
	 * PHP Execute
	 *
	 * @param  string
	 * @param  string
	 * @return string
	*/
	protected function _run($compiled_file_path, $filename) {
		$V = $this->V;
		$vars = NULL;
		ob_start();
		require ($compiled_file_path);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/**
	 * Sub Template Execute
	 *
	 * @param  string
	 * @return string
	*/
	protected function subTemplate($file_name, $uniqid = NULL) {
		return $this->main_subTemplate($file_name, $uniqid);
	}
}