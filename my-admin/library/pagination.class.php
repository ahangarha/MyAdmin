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
 * SQL Pagination Class
 *
 * @modified : 17 November 2017
 * @created  : 26 July 2015
 * @since    : version 0.4
 * @author   : Ali Bakhtiar (ali@persianicon.com)
*/

class ma_pagination
{
	public $page  = 0; // current page
	public $limit = 20;
	public $limit_page = 3;

	public $labels = [
		'next'  => 'Next',
		'prev'  => 'Prev',
		'first' => 'First',
		'last'  => 'Last'
	];

	protected $total_pages = 0;
	protected $total_records = 0;
	protected $count_query = FALSE;
	protected $from_page = 0;

	/**
	 * Constructor
	*/
	public function __construct() {
		$this->db =& ma_class('database');
	}

	/**
	 * Init
	*/
	public function init() {
		$this->count_query = FALSE;
		return;
	}

	/**
	 * MySQL Query
	 *
	 * @param  string
	 * @return bool/pdo objects
	*/
	public function query($query)  {
		// Only selected queries
		if (!preg_match("/^[\s]*select*/i", $query)) {
			$this->db_error(__LINE__);
			return FALSE;
		}

		// Total Records
		$this->total_records = $this->get_total_records();

		// Limit per query
		$this->total_pages = ceil($this->total_records / $this->limit);

		// Query limit
		// Min
		if ($this->page < 1) {
			$this->page = 1;
		}
		// Max
		else if ($this->page > $this->total_pages) {
			$this->page = $this->total_pages;
		}

		$this->from_page = $this->page * $this->limit - $this->limit; // mulai
		if ($this->from_page < 1) {
			$this->from_page = 0;
		}

		// Final
		$query = $query." LIMIT " . $this->from_page .",". $this->limit;

		$db_query = $this->db->query($query);
		if ($db_query == FALSE) {
			$this->db_error(__LINE__);
			return FALSE;
		}

		return $db_query;
	}

	/**
	 * MySQL Total Records
	 *
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  int
	 * @return string
	*/
	public function count_query($table_name, $row, $where = NULL, $limit = 0)  {
		$query = "SELECT COUNT(`$row`) AS qpcount FROM `[prefix]$table_name` ";

		if ($where) {
			if (!preg_match("/^[\s]*where*/i", $where)) {
				$query .= "WHERE  " . $where;
			}
			else {
				$query .= $where;
			}
		}

		if ($limit > 0) {
			$query .= ' LIMIT '.$limit;
		}

		$this->count_query = $query;
	}

	/**
	 * Total Pages
	 *
	 * @return int
	*/
	protected function get_total_records() {
		if ($this->count_query == FALSE) {
			return 0;
		}

		$db_query = $this->db->query($this->count_query);
		if ($db_query == FALSE) {
			return 0;
		}

		$select = $db_query->fetch(PDO::FETCH_ASSOC);
		if (!isset( $select['qpcount'])) {
			return 0;
		}

		return $select['qpcount'];
	}

	/**
	 * Info
	 *
	 * @return array
	*/
	public function info() {
		$page = [
			'page'  => $this->page,
			'start' => 1 + $this->from_page,
			'end'   => $this->from_page + $this->limit,
			'total' => $this->total_records,
			'total_pages' => $this->total_pages
		];

		if ($page['end'] > $page['total']) {
			$page['end'] = $page['total'];
		}

		if (empty($this->total_records)) {
			$page['start'] = 0;
		}

		return $page;
	}

	/**
	 * Html Links
	 *
	 * @return string
	 * @return string
	*/
	public function html($page_url = NULL, $list_class = 'pagination pagination-sm') {
		if ($this->total_records <= $this->limit) {
			return '';
		}

		// URL
		if (!$page_url) {
			$page_url = C_URL.'?';
			$input =& load_class('input');
			$qs = $input->query_string('page');
			if ($qs != '') {
				$page_url = $page_url.$qs.'&amp;';
			}
		}

		$last = $this->total_pages;

		$start = (($this->page - $this->limit_page) > 0 ) ? $this->page - $this->limit_page : 1;
		$end  = (($this->page + $this->limit_page) < $last) ? $this->page + $this->limit_page : $last;

		// Open Ul
		$html  = '<ul class="'.$list_class.'">';

		// First
		if ($start > 1) {
			$label = (empty($this->labels['first'])) ? 1 : $this->labels['first'];
			$html .= '<li><a href="'.$page_url.'">'.$label.'</a></li>';
			//$html .= '<li class="disabled"><span>...</span></li>';
		}

		// Prev
		$class = $this->page == 1 ? ' class="disabled"' : '';
		$label = (empty($this->labels['prev'])) ? '&laquo;' : $this->labels['prev'];
		$p_page = $this->page - 1;
		if ($p_page <=1) {
			$html  .= '<li'.$class.'><a href="'.$page_url.'">'.$label.'</a></li>';
		}
		else {
			$html  .= '<li'.$class.'><a href="'.$page_url.'page=' . ( $this->page - 1 ) . '">'.$label.'</a></li>';
		}

		// Pages
		for ($i = $start ; $i <= $end; ++$i) {
			$class = ( $this->page == $i ) ? ' class="active"' : NULL;
			$html .= '<li'.$class.'><a href="'.$page_url.'page=' . $i . '">' . $i . '</a></li>';
		}

		// Next
		$class = ( $this->page == $last ) ? ' class="disabled"' : '';
		$label = (empty($this->labels['next'])) ? '&raquo;' : $this->labels['next'];
		$p_page = $this->page + 1;
		if ($p_page >= $this->total_pages) {
			$html .= '<li'.$class.'><a href="'.$page_url.'page=' . $this->total_pages . '">'.$label.'</a></li>';
		}
		else {
			$html .= '<li'.$class.'><a href="'.$page_url.'page=' . ( $this->page + 1 ) . '">'.$label.'</a></li>';
		}

		// Last
		if ($end < $last) {
			//$html .= '<li class="disabled"><span>...</span></li>';
			$label = empty($this->labels['last']) ? $last : $this->labels['last'];
			$html .= '<li><a href="'.$page_url.'page=' . $last . '">' . $label . '</a></li>';
		}

		// Close Ul
		$html .= '</ul>';

		return $html;
	}
}