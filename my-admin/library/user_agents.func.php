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
 * User Agents
 *
 * @modified : 14 November 2017
 * @created  : 11 February 2015
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
 *
 * Resources:
 * http://www.php.net/manual/en/function.get-browser.php#101125
 * http://detectmobilebrowsers.com
 * https://developers.facebook.com/docs/opengraph/howtos/maximizing-distribution-media-content
*/

/**
 * Detection
 *
 * @return array
*/
if (function_exists('ma_browser_detection') == FALSE) {
	function ma_browser_detection($u_agent) {
		$u_agent = strtolower($u_agent);
		$version = $ub = $bname = $platform = $platform_n = $is_mobile = 0; // unknown

		// First get the platform
		if (preg_match('/windows|win32/i', $u_agent)) {
			$platform = 1; // windows
			$platform_n = 'windows';
		}
		else if (preg_match('/macintosh|mac os x/i', $u_agent)) {
			$platform = 2; // mac
			$platform_n = 'mac';
			//iphone and ipad
			if (preg_match('/ipad/i', $u_agent)) {
				$platform = 4; // ipad
				$platform_n = 'ipad';
			}
			else if (preg_match('/iphone/i', $u_agent)) {
				$platform = 5; // iphone
				$platform_n = 'iphone';
			}
		}
		else if (preg_match('/linux/i', $u_agent)) {
			$platform = 3; // linux
			$platform_n = 'linux';
			// android
			if (preg_match('/android/i', $u_agent)) {
				$platform = 6; // android
				$platform_n = 'android';
			}
		}

		// Next get the name of the useragent yes seperately and for good reason
		if (preg_match('/msie|trident/i', $u_agent) && !preg_match('/opera/i', $u_agent)) {
			$bname = 1; // Internet Explorer
			$ub = 'msie';
		}
		else if (preg_match('/firefox/i', $u_agent)) {
			$bname = 2; // mozilla firefox
			$ub = 'firefox';
		}
		else if (preg_match('/chrome/i', $u_agent)) {
			$bname = 3; // google chrome
			$ub = 'chrome';
		}
		else if (preg_match('/safari/i', $u_agent)) {
			$bname = 4; // apple Safari
			$ub = 'safari';
		}
		else if (preg_match('/opera/i', $u_agent)) {
			$bname = 5; // Opera
			$ub = 'opera';
		}
		else if (preg_match('/netscape/i', $u_agent)) {
			$bname = 6; // netscape
			$ub = 'netscape';
		}
		// Bots
		else if (preg_match('/googlebot|googlebot-image/i', $u_agent)) {
			$bname = 7; // googlebot
			$ub = 'google';
			$platform = 3; // linux
			$platform_n = 'linux';
		}
		else if (preg_match('/yahoo/i', $u_agent)) {
			$bname = 8; // yahoo
			$ub = 'yahoo';
			$platform = 3; // linux
			$platform_n = 'linux';
		}
		else if (preg_match('/msnbot/i', $u_agent)) {
			$bname = 9; // msn
			$ub = 'msn';
			$platform = 1; // windows
			$platform_n = 'windows';
		}
		else if (preg_match('/facebot|facebookexternalhit/i', $u_agent)) {
			$bname = 10; // facebook
			$ub = 'facebook';
			$platform = 3; // linux
			$platform_n = 'linux';
		}

		// Finally get the correct version number
		$known = ['version', $ub, 'other'];
		$pattern = '#(?<browser>' . join('|', $known).')[/ ]+(?<version>[0-9.|a-z.]*)#';
		if (!preg_match_all($pattern, $u_agent, $matches)) {
			// we have no matching number just continue
		}

		// see how many we have
		$i = count($matches['browser']);
		if ($i != 1) {
		/* we will have two since we are not using 'other' argument yet
		   see if version is before or after the name*/
			if (strripos($u_agent, 'version') < strripos($u_agent,$ub)) {
				$version = isset($matches['version'][0]) ? $matches['version'][0] : 0;
			}
			else {
				$version = isset($matches['version'][1]) ? $matches['version'][1] : 0;
			}
		}
		else {
			$version= $matches['version'][0];
		}

		// Detect Mobile Browsers (update 01 August 2014)
		if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $u_agent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($u_agent,0,4))) { $is_mobile = 1; }

		// OS ID 99 => Mobile with unknow browser
		return [
			'os_id'        => ($is_mobile == 1 && $platform == 0) ? 99 : $platform,
			'os_name'      => $platform_n,
			'browser_id'   => $bname,
			'browser_name' => $ub,
			'version'      => $version,
			'mobile'       => $is_mobile
		];
	}
}

/**
 * IDs
 *
 * @return array
*/
if (function_exists('ma_browser_detection_get_id') == FALSE) {
	function ma_browser_detection_get_id() {
		return [
			'os' => [
				0 => 'unknown',
				1 => 'windows',
				2 => 'mac',
				3 => 'linux',
				4 => 'ipad',
				5 => 'iphone',
				6 => 'android',
				99 => 'unknown mobile'
			],
			'browser' => [
				0 => 'unknown',
				1 => 'internet explorer',
				2 => 'mozilla firefox',
				3 => 'google chrome',
				4 => 'apple safari',
				5 => 'opera',
				6 => 'netscape',
				7 => 'google',
				8 => 'yahoo',
				9 => 'msn',
				10 => 'facebook'
			]
		];
	}
}