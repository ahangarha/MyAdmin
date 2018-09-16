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
 * @modified : 26 July 2018
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

	/**
	 * Constructor
	*/
	public function __construct($language = NULL) {
		$this->client   =& ma_class('client');
		$this->security =& ma_class('security');
		$this->db =& ma_class('database');

		//$this->db->query("SELECT * FROM `posts`");

		if ($language) {
			$this->language = $language;
		}
	}
}