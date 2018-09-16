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

namespace myadmin;

defined('MA_PATH') OR exit('Restricted access');

/**
 * Controller
 *
 * @modified : 16 September 2018
 * @created  : 08 November 2017
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class controller
{
	protected $client;
	protected $security;
	protected $db;
	protected $language;
	protected $tpl;
	protected $module_name;
	protected $url = [];

	/**
	 * Constructor
	*/
	public function __construct($language = NULL, $url = []) {
		$this->client   =& ma_class('client');
		$this->security =& ma_class('security');
		$this->db  =& ma_class('database');
		$this->url = $url;

		if ($language) {
			$this->language = $language;
		}

		if (defined('MA_LOAD_TPL') == FALSE || MA_LOAD_TPL == TRUE) {
			$this->template_engine_init();
		}
	}

	/**
	 * Template engine initializing
	*/
	public function template_engine_init() {
		$this->tpl =& ma_class('template');
		$template_name = ma_config('template_name');
		if (empty($template_name) == TRUE) {
			$template_name = 'default';
		}

		$this->tpl->setTemplateDir(MA_PATH.'/templates/'.$template_name);

		if (isset($this->url['module'])) {
			$this->tpl->setTemplateDir(MA_PATH.'/modules/'.$this->url['module'].'/templates/');
		}
		$this->tpl->setCompileDir(MA_PATH.'/tmp/tpl/compiled-'.$this->language);
		$this->tpl->setCacheDir(MA_PATH.'/tmp/tpl/cache-'.$this->language);

		$this->tpl->strip = FALSE; // false=>off|true=>full|2=>mini
		$this->tpl->cache = FALSE;
		$this->tpl->assign([
			'DOMAIN'   => $this->client->domain(),
			'LANGUAGE' => $this->language,
			//'BROWSER'  => MA_BROWSER_INFO,
			//'HOMEPAGE' => MA_URL['base_path']
		]);
	}
}