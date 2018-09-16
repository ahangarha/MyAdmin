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
 * Template Parser Class
 *
 * @modified : 14 November 2017
 * @created  : November 2014
 * @since    : version 0.4
*/

defined('TPL_ENGINE_V') or exit('Restricted access');

class ma_template_parser
{
	/* variables */
	protected static $left_delimiter;
	protected static $right_delimiter;
	protected $templateInfo;

	/* More Configuration */
	protected static $conf; 
	/* array(
		'checksum'=> array(),
		'charset'=> 'UTF-8',
		'debug'=> FALSE,
		'php_enabled'=> FALSE,
		'black_list_preg'=> '',
		'auto_escape'=> TRUE,
		'sandbox'=> TRUE,
		'strip'=> TRUE,
		'left_delimiter'=> '{',
		'right_delimiter'=> '}'
	);*/

	protected $add_nocache = NULL;

	/* Tags */
	protected static $tags = array();

	/* Filename */
	protected static $filename, $cache;

	/* Nocache Uniqid */
	protected static $nocache_id;

	/* black list of functions and variables */
    protected static $black_list = array(
		'exec', 'shell_exec', 'pcntl_exec', 'passthru', 'proc_open', 'system',
		'posix_kill', 'posix_setsid', 'pcntl_fork', 'posix_uname', 'php_uname',
		'phpinfo', 'popen', 'file_get_contents', 'file_put_contents', 'rmdir',
		'mkdir', 'unlink', 'highlight_contents', 'symlink',
		'apache_child_terminate', 'apache_setenv', 'define_syslog_variables',
		'escapeshellarg', 'escapeshellcmd', 'eval', 'fp', 'fput',
		'ftp_connect', 'ftp_exec', 'ftp_get', 'ftp_login', 'ftp_nb_fput',
		'ftp_put', 'ftp_raw', 'ftp_rawlist', 'highlight_file', 'ini_alter',
		'ini_get_all', 'ini_restore', 'inject_code', 'mysql_connect', 'mysql_pconnect',
		'mysql_query', 'mysqli_connect', 'mysqli_query', 'openlog', 'passthru', 'php_uname', 
		'phpAds_remoteInfo', 'phpAds_XmlRpc', 'phpAds_xmlrpcDecode', 'phpAds_xmlrpcEncode',
		'posix_getpwuid', 'posix_kill', 'posix_mkfifo', 'posix_setpgid',
		'posix_setsid', 'posix_setuid', 'posix_uname', 'proc_close',
		'proc_get_status', 'proc_nice', 'proc_open', 'proc_terminate',
		'syslog', 'xmlrpc_entity_decode'
	);


	/**
	 * Construct
	*/
	public function __construct($conf = []) {
		self::$conf = $conf;
		self::$left_delimiter = $conf['left_delimiter'];
		self::$right_delimiter = $conf['right_delimiter'];

		$ld = $conf['left_delimiter'];
		$rd = $conf['right_delimiter'];

		self::$tags = array(
			'loop' => array(
				'('.$ld.'loop.*?'.$rd.')',
				'/'.$ld.'loop (?<variable>\${0,1}[^"]*)(?: as (?<key>\$.*?)(?: => (?<value>\$.*?)){0,1}){0,1}'.$rd.'/'
			),
			'loop_close' => array('('.$ld.'\/loop'.$rd.')', '/'.$ld.'\/loop'.$rd.'/'),
			'loop_break' => array('('.$ld.'break'.$rd.'])', '/'.$ld.'break'.$rd.'/'),
			'loop_continue' => array('('.$ld.'continue'.$rd.')', '/'.$ld.'continue'.$rd.'/'),
			'for' => array(
				'('.$ld.'for.*?'.$rd.')',
				'/'.$ld.'for (?<variable>\${0,1}[^"]*)(?: as (?<key>\$.*?)(?: => (?<value>\$.*?)){0,1}){0,1}'.$rd.'/'
			),
			'for_close' => array('('.$ld.'\/for'.$rd.')', '/'.$ld.'\/for'.$rd.'/'),
			'function' => array(
				'('.$ld.'function.*?'.$rd.')',
				'/'.$ld.'function (?<variable>\${0,1}[^"]*)(?: as (?<key>\$.*?)(?: => (?<value>\$.*?)){0,1}){0,1}'.$rd.'/'
			),
			'function_call' => array(
				'('.$ld.'function.*?'.$rd.')',
				'/'.$ld.'function (?<variable>\${0,1}[^"]*)(?: as (?<key>\$.*?)(?: => (?<value>\$.*?)){0,1}){0,1}'.$rd.'/'
			),
			'function_close' => array('('.$ld.'\/function'.$rd.')', '/'.$ld.'\/function'.$rd.'/'),
			'if' => array('('.$ld.'if.*?'.$rd.')', '/'.$ld.'if ([^"]*)?'.$rd.'/'),
			'elseif' => array('('.$ld.'(?:elseif|else if).*?'.$rd.')', '/'.$ld.'(?:elseif|else if) ([^"]*)?'.$rd.'/'),
			'else' => array('('.$ld.'else'.$rd.')', '/'.$ld.'else'.$rd.'/'),
			'if_close' => array('('.$ld.'\/if'.$rd.')', '/'.$ld.'\/if'.$rd.'/'),
			'literal' => array('('.$ld.'literal'.$rd.')', '/'.$ld.'literal'.$rd.'/'),
			'literal_close' => array('('.$ld.'\/literal'.$rd.')', '/'.$ld.'\/literal'.$rd.'/'),
			'ignore' => array('('.$ld.'ignore'.$rd.'|{\*)', '/'.$ld.'ignore'.$rd.'|{\*/'),
			'ignore_close' => array('('.$ld.'\/ignore'.$rd.'|\*})', '/'.$ld.'\/ignore'.$rd.'|\*}/'),
			'include' => array('('.$ld.'include.*?'.$rd.')', '/'.$ld.'include file="([^"]*)"'.$rd.'/'),
			'if' => array('('.$ld.'if.*?'.$rd.')', '/'.$ld.'if ([^"]*)?'.$rd.'/'),
			'ternary' => array('('.$ld.'.[^{?]*?\?.*?\:.*?'.$rd.')', '/{(.[^{?]*?)\?(.*?)\:(.*?)'.$rd.'/'),
			'variable' => array('('.$ld.'\$.*?'.$rd.')', '/'.$ld.'(\$.*?)'.$rd.'/'),
			'constant' => array('('.$ld.'#.*?'.$rd.')', '/'.$ld.'#(.*?)#{0,1}'.$rd.'/'),
			'nocache' => array('('.$ld.'nocache'.$rd.')', '/'.$ld.'nocache'.$rd.'/'),
			'nocache_close' => array('('.$ld.'\/nocache'.$rd.')', '/'.$ld.'\/nocache'.$rd.'/'),
			'function_call' => array(
				'('.$ld.'call.*?'.$rd.')',
				'/'.$ld.'call(?<variable>\${0,1}[^"]*){0,1}'.$rd.'/'
			)
		);
	}

	/**
	 * Template Compiler
	 * dastorat php tarjome mishavad.
	 *
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return bool FALSE
	*/
	public function compile($template_code, $filename, $cache, $tpl_dir) {
		self::$filename = $filename;
		self::$cache = $cache;
		self::$nocache_id = md5($filename);
		self::$conf['tpl_dir'] = $tpl_dir;

		$this->templateInfo['code'] = $template_code;

		$final_code = self::clean_up( $template_code );
		$final_code = self::compiledCodes( $final_code );

		return $final_code;
	}

	/**
	 * Clenup
	 * in func code ra check mikonad va oun ra clean mikonad.
	 * masalan code hay php ra delete mikonad.
	*/
	protected function clean_up($code) {
		/* xml substitution */
		$code = preg_replace("/<\?xml(.*?)\?>/s", /*<?*/ "##XML\\1XML##", $code);

		/* disable php tag */
		if(FALSE == self::$conf['php_enabled']){
			$code = preg_replace('/<\?(.*)\?>/Uis', '', $code);
		}

		/* xml re-substitution */
		$code = preg_replace_callback("/##XML(.*?)XML##/s", function( $match ){
			return "<?php echo '<?xml " . stripslashes($match[1]) . " ?>'; ?>";
		}, $code);

		/* remove lightpl comments */
		$code = preg_replace('/'. self::$left_delimiter .'\*(.*)\*'. self::$right_delimiter .'/Uis', '', $code);

		return $code;
	}

	/**
	 * Compiled Codes
	 * in func code ha ra tarjome mikonad.
	*/
	protected function compiledCodes($template_code) {
		/* set tags */
		foreach (self::$tags as $tag => $tagArray) {
			list( $split, $match ) = $tagArray;
			$tagSplit[$tag] = $split;
			$tagMatch[$tag] = $match;
		}

		/* split the code with the tags regexp */
        $codeSplit = preg_split("/" . implode("|", $tagSplit) . "/", $template_code, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		if(!$codeSplit){
			return '';
			//exit('Error : parser_compile_error_' . __LINE__ );
		}

		$template_code = self::tag_parse($codeSplit, $tagSplit, $tagMatch);
		return $template_code;
	}

	/**
	 * Lightpl Tags Parse
	*/
	protected function tag_parse($codeSplit, $tagSplit , $tagMatch) {
		/* variables initialization */
		$commentIsOpen = $ignoreIsOpen = NULL;
		$openIf = $openNoCache = $loopLevel = 0;
		$include_files = '';
		/*$function_name = 'content_' . md5( self::$filename . 'Ltpl' );
		$parsedCode = '<?php $this->_ltpl_map["function"] = \'' . $function_name . "'; ?>";*/
		$parsedCode = NULL;

		/* Parse */
		foreach ($codeSplit as $html) {
			/* close ignore tag */
			if(!$commentIsOpen && preg_match($tagMatch['ignore_close'], $html)) {
				$ignoreIsOpen = FALSE;
			}

			/* code between tag ignore id deleted */
			else if ($ignoreIsOpen) {
				//ignore the code
			}

			/* close no parse tag */
			else if (preg_match($tagMatch['literal_close'], $html)) {
				$commentIsOpen = FALSE;
			}

			/* code between tag noparse is not compiled */
			else if ($commentIsOpen){
				$parsedCode .= $html;
			}

			/* ignore */
			else if (preg_match($tagMatch['ignore'], $html)) {
				$ignoreIsOpen = TRUE;
			}

			/* noparse */
			else if (preg_match($tagMatch['literal'], $html)) {
				$commentIsOpen = TRUE;
			}

			/* NoCache */
			else if (preg_match($tagMatch['nocache'], $html)) {
				if (TRUE == self::$cache) {
					$openNoCache++;
					$parsedCode .=  self::$left_delimiter . 'ltpnc-'.self::$nocache_id .  self::$right_delimiter;
				}
				else {
					$parsedCode .= '';
				}
			}

			/* close NoCache tag */
			else if (preg_match($tagMatch['nocache_close'], $html)) {
				if (TRUE == self::$cache) {
					$openNoCache--;
					$parsedCode .=  self::$left_delimiter . '/ltpnc-'.self::$nocache_id .  self::$right_delimiter;
				}
				else {
					$parsedCode .= '';
				}
			}

			/* IF */
			else if(preg_match($tagMatch['if'], $html, $matches)) {
				//increase open if counter (for intendation)
				$openIf++;

				//tag
				$tag = $matches[0];

				//condition attribute
				$condition = $matches[1];

				// check black list
				$this->blackList($condition);

				//variable substitution into condition (no delimiter into the condition)
				$parsedCondition = $this->varReplace($condition, $loopLevel, $escape = FALSE);

				//if code
				$parsedCode .= "<?php if( $parsedCondition ){ ?>";
			}

			/* elseif */
			else if(preg_match($tagMatch['elseif'], $html, $matches)) {
				// tag
				$tag = $matches[0];

				// condition attribute
				$condition = $matches[1];

				// check black list
				$this->blackList($condition);

				// variable substitution into condition (no delimiter into the condition)
				$parsedCondition = $this->varReplace($condition, $loopLevel, $escape = FALSE);

				// elseif code
				$parsedCode .= "<?php }elseif( $parsedCondition ){ ?>";
			}

			/* else */
			else if(preg_match($tagMatch['else'], $html)) {
				//else code
				$parsedCode .= '<?php }else{ ?>';
			}

			/* close if tag */
			else if(preg_match($tagMatch['if_close'], $html)) {
				//decrease if counter
				$openIf--;

				// close if code
				$parsedCode .= '<?php } ?>';
			}

			/* For */
			else if(preg_match($tagMatch['for'], $html, $matches)) {
				// increase the loop counter
				$loopLevel++;

				// replace the variable in the loop
				$var = $this->varReplace($matches['variable'], $loopLevel - 1, $escape = FALSE);
				if (preg_match('#\(#', $var)) {
					$newvar = "\$newvar{$loopLevel}";
					$assignNewVar = "$newvar=$var;";
				}
				else {
					$newvar = $var;
					$assignNewVar = NULL;
				}

				// loop variables
				$counter = "\$counter$loopLevel"; // count iteration

				if (isset($matches['key']) && isset($matches['value'])) {
					$key = $matches['key'];
					$value = $matches['value'];
				}
				else if (isset($matches['key'])) {
					$key = "\$key$loopLevel"; // key
					$value = $matches['key'];
				}
				else {
					$key = "\$key$loopLevel"; // key
					$value = "\$value$loopLevel"; // value
				}

				// loop code
				$parsedCode .= "<?php for($newvar): ?>";

				//exit( $parsedCode  );
			}

			/* close section tag */
			elseif(preg_match($tagMatch['for_close'], $html)) {
				//iterator
				$counter = "\$counter$loopLevel";

				//decrease the loop counter
				$loopLevel--;

				//close loop code
				$parsedCode .= "<?php endfor; ?>";
			}

			/* include tag */
			else if (preg_match($tagMatch['include'], $html, $matches)) {
				//get the folder of the actual template
				$actualFolder = self::$conf['tpl_dir'];

				if (is_array(self::$conf['tpl_dir'])) {
					foreach (self::$conf['tpl_dir'] as $tpl) {
						if(substr($actualFolder, 0, strlen($tpl)) == $tpl){
							$actualFolder = substr($actualFolder, strlen($tpl));
						}
					}
				}
				else if (substr($actualFolder, 0, strlen(self::$conf['tpl_dir'])) == self::$conf['tpl_dir']) {
                        $actualFolder = substr($actualFolder, strlen(self::$conf['tpl_dir']));
				}

				// get the included template
				if (strpos($matches[1], '$') !== FALSE) {
					$includeTemplate = "'$actualFolder'." . $this->varReplace($matches[1], $loopLevel);
				}
				else {
					$includeTemplate = $actualFolder . $this->varReplace($matches[1], $loopLevel);
				}

				// reduce the path
				$includeTemplate = self::reducePath( $includeTemplate );

				// list file hay include shode ra save mikonim
				if (!isset($inc_files[ $includeTemplate ])) {
					$inc_files[ $includeTemplate ] = $includeTemplate;
					if (empty($include_files)) {
						$include_files = '$this->_ltpl_map["includes"] = array('."'".$includeTemplate."'";
					}
					else {
						$include_files .= ", '".$includeTemplate."'";
					}
				}

				if (strpos($matches[1], '$') !== FALSE) {
					//dynamic include
					$parsedCode .= '<?php require self::subTemplate(' . $includeTemplate . '); ?>';
				}
				else {
					//dynamic include
					$parsedCode .= '<?php require self::subTemplate("' . $includeTemplate . '"); ?>';
				}
			}

			/* loop */
			else if (preg_match($tagMatch['loop'], $html, $matches)) {
				// increase the loop counter
				$loopLevel++;

				// replace the variable in the loop
				$var = $this->varReplace($matches['variable'], $loopLevel - 1, $escape = FALSE);

				if (preg_match('#\(#', $var)) {
					$newvar = "\$newvar{$loopLevel}";
					$assignNewVar = "$newvar=$var;";
				}
				else {
					$newvar = $var;
					$assignNewVar = NULL;
				}

				// check black list
				$this->blackList($var);

				// loop variables
				$counter = "\$counter$loopLevel"; // count iteration

				if (isset($matches['key']) && isset($matches['value'])) {
					$key = $matches['key'];
					$value = $matches['value'];
				}
				else if(isset($matches['key'])) {
					$key = "\$key$loopLevel"; // key
					$value = $matches['key'];
				}
				else {
					$key = "\$key$loopLevel"; // key
					$value = "\$value$loopLevel"; // value
				}

				// loop code
				$parsedCode .= "<?php $counter=-1; $assignNewVar if(isset($newvar) && is_array($newvar)){ ";
				$parsedCode .= "foreach($newvar as $key => $value){ $counter++; ?>";
			}

			/* close loop tag */
			else if(preg_match($tagMatch['loop_close'], $html)) {
				//iterator
				$counter = "\$counter$loopLevel";

				//decrease the loop counter
				$loopLevel--;

				//close loop code
				$parsedCode .= "<?php }} ?>";
			}

			/* break loop tag */
			else if(preg_match($tagMatch['loop_break'], $html)) {
				//close loop code
				$parsedCode .= "<?php break; ?>";
			}

			/* continue loop tag */
			else if(preg_match($tagMatch['loop_continue'], $html)) {
				//close loop code
				$parsedCode .= "<?php continue; ?>";
			}

			/* function */
			else if(preg_match($tagMatch['function'], $html, $matches)) {
				list($function_name, $vars) = self::function_parse( $matches['variable'] );
				$parsedCode .= '<?php function '.$function_name.'('.$vars.'){ ?>';
			}

			/* Function Close */
			else if(preg_match($tagMatch['function_close'], $html)) {
				//close loop code
				$parsedCode .= "<?php } ?>";
			}

			/* Function Call */
			else if(preg_match($tagMatch['function_call'], $html, $matches)) {
				list($function_name, $vars) = self::function_parse( $matches['variable'] );
				$parsedCode .= '<?php echo '.$function_name.'('.$vars.'); ?>';
			}

			/* ternary */
			else if (preg_match($tagMatch['ternary'], $html, $matches)) {
				$parsedCode .= "<?php echo " . '(' . 
					$this->varReplace($matches[1], $loopLevel, $escape = TRUE, $echo = FALSE) . '?' . 
					$this->varReplace($matches[2], $loopLevel, $escape = TRUE, $echo = FALSE) . ':' . 
					$this->varReplace($matches[3], $loopLevel, $escape = TRUE, $echo = FALSE) . ')' . "; ?>";
			}

			/* variables */
			else if(preg_match($tagMatch['variable'], $html, $matches)) {
				// variables substitution (eg {$title})
				$condition = $this->varReplace($matches[1], $loopLevel, $escape = TRUE, $echo = TRUE);
				if ($this->add_nocache) {
					$ldl = self::$left_delimiter;
					$rdl = self::$right_delimiter;
					$parsedCode .=  $ldl . 'ltpnc-'.self::$nocache_id.$rdl;
					$parsedCode .=  '<?php ' . $condition . '; ?>' . $ldl . '/ltpnc-' . self::$nocache_id . $rdl;
					$this->add_nocache = NULL;
				}
				else {
					$parsedCode .= "<?php " . $condition . "; ?>";
				}
			}

			/* constants */
			else if (preg_match($tagMatch['constant'], $html, $matches)) {
				$parsedCode .= "<?php echo " . $this->conReplace($matches[1], $loopLevel) . "; ?>";
			}

			/* All HTML Codes (registered tags) */
			else {
				$parsedCode .= $html;
			}
		}

		/** Compression */
		if (self::$conf['strip'] != FALSE) {
			$parsedCode = self::minify($parsedCode, self::$conf['strip']);
		}

		/** Data List */
		if (!empty($include_files)) {
			$parsedCode = '<?php '.$include_files.'); ?>' . $parsedCode;
		}

		return $parsedCode;
	}

	/*
	 * Variables Replace
	*/
	protected function varReplace($html, $loopLevel = NULL, $escape = TRUE, $echo = FALSE) {
		// change variable name if loop level
		if (!empty($loopLevel)) {
			$r1 = array('/(\$key)\b/', '/(\$value)\b/', '/(\$counter)\b/');
			$r2 = array('${1}' . $loopLevel, '${1}' . $loopLevel, '${1}' . $loopLevel);
			$html = preg_replace($r1, $r2, $html);
		}

		// if it is a variable
		if (preg_match_all('/(\$[a-z_A-Z][^\s]*)/', $html, $matches)) {
			// substitute . and [] with [" "]
			for ($i=0; $i < count($matches[1]); ++$i) {
				$rep = preg_replace('/\[(\${0,1}[a-zA-Z_0-9]*)\]/', '["$1"]', $matches[1][$i]);
				$rep = preg_replace( '/\.(\${0,1}[a-zA-Z_0-9]*(?![a-zA-Z_0-9]*(\'|\")))/', '["$1"]', $rep);

				/*
				 * Loop
				 * var hay beyne loop nabayad dar $VAR gharar begirad.
				*/
				$set_tv = TRUE;
				if (!empty($loopLevel)) {
					$ch = preg_match("/^(value|key|counter)[0-9](.*)/i", ltrim($rep, '$'));
					if (TRUE == $ch) {
						$set_tv = FALSE;
					}
				}

				// Set $VAR
				if (TRUE == $set_tv) {
					$rep = preg_replace('/\$([a-zA-Z_0-9]*)(.*)/', '$V[\'$1\']$2', $rep);
				}

				$html = str_replace($matches[0][$i], $rep, $html);
			}
			//echo "Html : ".$html."\n";

			// update modifier
			$html = $this->modifierReplace($html);

			// if does not initialize a value, e.g. {$a = 1}
			if (!preg_match('/\$.*=.*/', $html)) {
				// agar nocache dar entehay var bud remove mishavad,
				// add_nocache == TRUE mishavad, yani ghabl az tag php,
				// bayad tag {nocache} gharar begirad
				if (strpos($html, 'nocache')) {
					$this->add_nocache = TRUE;
					$html = explode('nocache', $html);
					$html = trim($html[0]);
				}

				// escape character
				if (self::$conf['auto_escape'] && $escape) {
					//$html = "htmlspecialchars( $html )";
					$html = "htmlspecialchars($html, ENT_COMPAT, 'UTF-8', FALSE)";
				}

				// if is an assignment it doesn't add echo
				if ($echo) {
					$html = 'echo ' . $html;
				}
            }
        }
		return $html;
    }

	/*
	 * Modifier Replace
	*/
    protected function modifierReplace($html) {
		$this->blackList($html);
		if (strpos($html,'|') !== FALSE && substr($html,strpos($html,'|')+1,1) != "|") {
			preg_match('/([\$a-z_A-Z0-9\(\),\[\]"->]+)\|([\$a-z_A-Z0-9\(\):,\[\]"->]+)/i', $html,$result);

			$function_params = $result[1];
			$explode = explode(":",$result[2]);
			$function = $explode[0];
			$params = isset($explode[1]) ? "," . $explode[1] : NULL;

			$html = str_replace($result[0], $function . "(" . $function_params . "$params)",$html);
			if (strpos($html,'|') !== FALSE && substr($html,strpos($html,'|')+1,1) != "|") {
				$html = $this->modifierReplace($html);
			}
		}
		return $html;
    }

	/*
	 * Function Parser
	*/
	protected function function_parse($variable)
	{
		if (preg_match('/^[name=\s]+(?<variable>\${0,1}[^"]*)/s', $variable, $matches)) {
			$variable = $matches['variable'];
		}

		$function_name = $vars = $exp_name = NULL;
		$detail = [];

		// taamame = , be space tabdil mishavad
		$variable = str_replace(array('(', ')') , ' ', $variable);
		$words = explode(' ', $variable);
		foreach ($words as $k) {
			if (empty($k)) {
				continue;
			}

			$detail[] = $k;
			break;
		}

		// Name
		if (!isset( $detail[0])) {
			exit('lightpl_function_syntax_error_'.__LINE__);
		}
		$function_name = $detail[0];
		$variable = str_replace($function_name, '', $variable);
		$vars = $this->varReplace($variable);
		$this->blackList($vars);

		return array(
			$function_name,
			$vars
		);
	}

	/*
	 * Black List
	*/
	protected function blackList($html) {
		if (self::$conf['sandbox'] == FALSE || static::$black_list == FALSE) {
			return TRUE;
		}

		if (empty(self::$conf['black_list_preg']) == TRUE) {
			self::$conf['black_list_preg'] = '#[\W\s]*'.implode('[\W\s]*|[\W\s]*', static::$black_list).'[\W\s]*#';
		}

		// check if the function is in the black list (or not in white list)
		if (preg_match(self::$conf['black_list_preg'], $html, $match)) {
			// find the line of the error
			$line = 0;
			$rows = explode("\n", $this->templateInfo['code']);
			while (!strpos($rows[$line], $html) && $line + 1 < count($rows)) {
				$line++;
			}

			// stop the execution of the script
			exit('Syntax ' . $match[0] . ' not allowed in template: ' . ' at line ' . $line);
			return FALSE;
		}
	}

	/**
	 * Reduce path
	*/
	public static function reducePath($path){
		// reduce the path
		$path = str_replace( "://", "@not_replace@", $path);
		$path = preg_replace( "#(/+)#", "/", $path);
		$path = preg_replace( "#(/\./+)#", "/", $path);
		$path = str_replace( "@not_replace@", "://", $path);
		while ( preg_match('#\w+\.\./#', $path)) {
			$path = preg_replace('#\w+/\.\./#', '', $path);
        }
		return $path;
    }

	/**
	 * HTML Minifi
	*/
	protected static function minify($source, $level) {
		// Basic
		if ($level === 2) {
			$replace = array(
				'#<!--.*?-->#s' => "", // strip comments
				"#\n\s+<#"=> "\n<", // strip excess whitespace
				'/\/\*(.*)\*\//Uis'=> '', // javascript comments
			);

			$search = array_keys($replace);
			return preg_replace($search, $replace, $source);
		}

		// Full
		$replace = array(
			'/\>[^\S ]+/s'=> '>', // strip whitespaces after tags, except space
			'/[^\S ]+\</s'=> '<', // strip whitespaces before tags, except space
			'/(\s)+/s'=> '\\1', // shorten multiple whitespace sequences
			'#<!--.*?-->#s'=> '', // html comments
			'/\/\*(.*)\*\//Uis'=> '' // javascript comments
		);

		$search = array_keys($replace);
		$source = preg_replace($search, $replace, $source);
		return str_replace(array("\n","\r","\t"), ' ', $source);
	}
}