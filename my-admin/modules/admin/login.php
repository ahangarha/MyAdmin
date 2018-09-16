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

/**
 * Admin > Login
 *
 * @modified : 26 July 2018
 * @created  : 03 September 2011
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

namespace myadmin\module\admin;

defined('MA_PATH') OR exit('Restricted access');

class login extends \myadmin\module\admin
{
	/**
	 * Login (index)
	*/
	public function login($params) {
		echo "::Login";
	}

	/**
	 * Auth
	*/
	public function auth($params) {
		echo "::Auth";
		printr($params);
	}

}