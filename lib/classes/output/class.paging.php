<?php

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2003-2009 the SysCP Team (see authors).
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author     Florian Lippert <flo@syscp.org> (2003-2009)
 * @author     Froxlor team <team@froxlor.org> (2010-)
 * @license    GPLv2 http://files.froxlor.org/misc/COPYING.txt
 * @package    Classes
 *
 */

/**
 * Class to manage paging system
 * @package Functions
 */
class paging {

	/**
	 * Userinfo
	 * @var array
	 */
	private $userinfo = array();

	/**
	 * MySQL-Table
	 * @var string
	 */
	private $table = '';

	/**
	 * Fields with description which should be selectable
	 * @var array
	 */
	private $fields = array();

	/**
	 * Entries per page
	 * @var int
	 */
	private $entriesperpage = 0;

	/**
	 * Number of entries of table
	 * @var int
	 */
	private $entries = 0;

	/**
	 * Sortorder, asc or desc
	 * @var string
	 */
	public $sortorder = 'asc';

	/**
	 * Sortfield
	 * @var string
	 */
	public $sortfield = '';

	/**
	 * Searchfield
	 * @var string
	 */
	private $searchfield = '';

	/**
	 * Searchtext
	 * @var string
	 */
	private $searchtext = '';

	/**
	 * Pagenumber
	 * @var int
	 */
	private $pageno = 0;

	/**
	 * Switch natsorting on/off
	 * @var bool
	 */
	private $natSorting = false;

	/**
	 * Class constructor. Loads settings from request or from userdata and saves them to session.
	 *
	 * @param array userinfo
	 * @param string Name of Table
	 * @param array Fields, in format array( 'fieldname_in_mysql' => 'field_caption' )
	 * @param int *deprecated* entries per page
	 * @param bool *deprecated* Switch natsorting on/off (global, affects all calls of sort)
	 * @param int $default_field default sorting-field-index
	 * @param string $default_order default sorting order 'asc' or 'desc'
	 *
	 */
	public function __construct($userinfo, $table, $fields, $entriesperpage = 0, $natSorting = false, $default_field = 0, $default_order = 'asc') {

		// entries per page and natsorting-flag are not
		// passed as parameter anymore, because these are
		// from the settings anyway
		$entriesperpage = Settings::Get('panel.paging');
		$natSorting = Settings::Get('panel.natsorting');

		$this->userinfo = $userinfo;

		if (!is_array($this->userinfo['lastpaging'])) {
			$this->userinfo['lastpaging'] = unserialize($this->userinfo['lastpaging']);
		}

		$this->table = $table;
		$this->fields = $fields;
		$this->entriesperpage = $entriesperpage;
		$this->natSorting = $natSorting;
		$checklastpaging = (isset($this->userinfo['lastpaging']['table']) && $this->userinfo['lastpaging']['table'] == $this->table);
		$this->userinfo['lastpaging']['table'] = $this->table;

		if (isset($_REQUEST['sortorder'])
			&& (strtolower($_REQUEST['sortorder']) == 'desc'
			|| strtolower($_REQUEST['sortorder']) == 'asc')
		) {
			$this->sortorder = strtolower($_REQUEST['sortorder']);

		} else {

			if ($checklastpaging
				&& isset($this->userinfo['lastpaging']['sortorder'])
				&& (strtolower($this->userinfo['lastpaging']['sortorder']) == 'desc'
				|| strtolower($this->userinfo['lastpaging']['sortorder']) == 'asc')
			) {
				$this->sortorder = strtolower($this->userinfo['lastpaging']['sortorder']);

			} else {
				$this->sortorder = $default_order;
			}
		}

		$this->userinfo['lastpaging']['sortorder'] = $this->sortorder;

		if (isset($_REQUEST['sortfield'])
			&& isset($fields[$_REQUEST['sortfield']])
		) {
			$this->sortfield = $_REQUEST['sortfield'];
		} else {
			if ($checklastpaging
				&& isset($this->userinfo['lastpaging']['sortfield'])
				&& isset($fields[$this->userinfo['lastpaging']['sortfield']])
			) {
				$this->sortfield = $this->userinfo['lastpaging']['sortfield'];
			} else {
				$fieldnames = array_keys($fields);
				$this->sortfield = $fieldnames[$default_field];
			}
		}

		$this->userinfo['lastpaging']['sortfield'] = $this->sortfield;

		if (isset($_REQUEST['searchfield'])
			&& isset($fields[$_REQUEST['searchfield']])
		) {
			$this->searchfield = $_REQUEST['searchfield'];
		} else {
			if ($checklastpaging
				&& isset($this->userinfo['lastpaging']['searchfield'])
				&& isset($fields[$this->userinfo['lastpaging']['searchfield']])
			) {
				$this->searchfield = $this->userinfo['lastpaging']['searchfield'];
			} else {
				$fieldnames = array_keys($fields);
				$this->searchfield = $fieldnames[0];
			}
		}

		$this->userinfo['lastpaging']['searchfield'] = $this->searchfield;

		if (isset($_REQUEST['searchtext'])
			&& (preg_match('/[-_@\p{L}\p{N}*.]+$/u', $_REQUEST['searchtext'])
			|| $_REQUEST['searchtext'] === '')
		) {
			$this->searchtext = trim($_REQUEST['searchtext']);
		} else {
			if ($checklastpaging
				&& isset($this->userinfo['lastpaging']['searchtext'])
				&& preg_match('/[-_@\p{L}\p{N}*.]+$/u', $this->userinfo['lastpaging']['searchtext'])
			) {
				$this->searchtext = $this->userinfo['lastpaging']['searchtext'];
			} else {
				$this->searchtext = '';
			}
		}

		$this->userinfo['lastpaging']['searchtext'] = $this->searchtext;

		if (isset($_REQUEST['pageno'])
			&& intval($_REQUEST['pageno']) != 0
		) {
			$this->pageno = intval($_REQUEST['pageno']);
		} else {
			if ($checklastpaging
				&& isset($this->userinfo['lastpaging']['pageno'])
				&& intval($this->userinfo['lastpaging']['pageno']) != 0
			) {
				$this->pageno = intval($this->userinfo['lastpaging']['pageno']);
			} else {
				$this->pageno = 1;
			}
		}

		$this->userinfo['lastpaging']['pageno'] = $this->pageno;
		$upd_stmt = Database::prepare("
			UPDATE `" . TABLE_PANEL_SESSIONS . "` SET
			`lastpaging` = :lastpaging
			WHERE `hash` = :hash  AND `userid` = :userid
			AND `ipaddress` = :ipaddr AND `useragent` = :ua
			AND `adminsession` = :adminsession
		");
		$upd_data = array(
			'lastpaging' => serialize($this->userinfo['lastpaging']),
			'hash' => $userinfo['hash'],
			'userid' => $userinfo['userid'],
			'ipaddr' => $userinfo['ipaddress'],
			'ua' => $userinfo['useragent'],
			'adminsession' => $userinfo['adminsession']
		);
		Database::pexecute($upd_stmt, $upd_data);
	}

	/**
	 * Sets number of entries and adjusts pageno if the number of entries doesn't correspond to the pageno.
	 *
	 * @param int entries
	 */
	public function setEntries($entries) {

		$this->entries = $entries;

		if (($this->pageno - 1) * $this->entriesperpage > $this->entries) {
			$this->pageno = 1;
		}

		return true;
	}

	/**
	 * Checks if a row should be displayed or not, used in loops
	 *
	 * @param int number of row
	 * @return bool to display or not to display, that's the question
	 */
	public function checkDisplay($count) {
		$begin = (intval($this->pageno) - 1) * intval($this->entriesperpage);
		$end = (intval($this->pageno) * intval($this->entriesperpage));
		return (($count >= $begin && $count < $end) || $this->entriesperpage == 0);
	}

	/**
	 * Returns condition code for sql query
	 *
	 * @param bool should returned condition code start with WHERE (false) or AND (true)?
	 * @return string the condition code
	 */
	public function getSqlWhere($append = false) {
		if ($this->searchtext != '') {
			if ($append == true) {
				$condition = ' AND ';
			} else {
				$condition = ' WHERE ';
			}

			$searchfield = explode('.', $this->searchfield);
			foreach ($searchfield as $id => $field) {
				if (substr($field, -1, 1) != '`') {
					$field.= '`';
				}

				if ($field{0} != '`') {
					$field = '`' . $field;
				}

				$searchfield[$id] = $field;
			}

			$searchfield = implode('.', $searchfield);
			
			$ops = array('<', '>', '=');
			
			// check if we use an operator or not
			$useOper = 0;
			$oper = "=";
			if (in_array(substr($this->searchtext, 0, 1), $ops)) {
				$useOper = 1;
				$oper = substr($this->searchtext, 0, 1);
			}
			
			// check for diskspace and whether searchtext is a number
			// in any other case the logical-operators would make no sense
			if (strpos($searchfield, 'diskspace') > 0 && is_numeric(substr($this->searchtext, $useOper))) {
				// anything with diskspace is *1024
				$searchtext = ((int)substr($this->searchtext, $useOper))*1024;
				$useOper = 1;
			} elseif (strpos($searchfield, 'traffic') > 0 && is_numeric(substr($this->searchtext, $useOper))) {
				// anything with traffic is *1024*1024
				$searchtext = ((int)substr($this->searchtext, $useOper))*1024*1024;
				$useOper = 1;
			} else {
				// any other field
				$searchtext = substr($this->searchtext, $useOper);
			}
			
			if ($useOper == 1 && is_numeric(substr($this->searchtext, $useOper))) {
				// now as we use >, < or = we use the given operator and not LIKE
				$condition.= $searchfield . " ".$oper." " . Database::quote($searchtext);
			} else {
				$searchtext = str_replace('*', '%', $this->searchtext);
				$condition.= $searchfield . " LIKE " . Database::quote($searchtext);
			}
			
		} else {
			$condition = '';
		}

		return $condition;
	}

	/**
	 * Returns "order by"-code for sql query
	 *
	 * @param bool Switch natsorting on/off (local, affects just this call)
	 * @return string the "order by"-code
	 */
	public function getSqlOrderBy($natSorting = null) {

		$sortfield = explode('.', $this->sortfield);
		foreach ($sortfield as $id => $field) {
			if (substr($field, -1, 1) != '`') {
				$field.= '`';
			}

			if ($field{0} != '`') {
				$field = '`' . $field;
			}

			$sortfield[$id] = $field;
		}

		$sortfield = implode('.', $sortfield);
		$sortorder = strtoupper($this->sortorder);

		if ($natSorting == true
			|| ($natSorting === null && $this->natSorting == true)
		) {
			// Acts similar to php's natsort(), found in one comment at http://my.opera.com/cpr/blog/show.dml/160556
			$sortcode = "ORDER BY CONCAT( IF( ASCII( LEFT( " . $sortfield . ", 5 ) ) > 57,
				LEFT( " . $sortfield . ", 1 ), 0 ),
				IF( ASCII( RIGHT( " . $sortfield . ", 1 ) ) > 57,
					LPAD( " . $sortfield . ", 255, '0' ),
					LPAD( CONCAT( " . $sortfield . ", '-' ), 255, '0' )
				)) " . $sortorder;
		} else {
			$sortcode = 'ORDER BY ' . $sortfield . ' ' . $sortorder;
		}

		return $sortcode;
	}

	/**
	 * Currently not used
	 *
	 * @return string always empty
	 */
	public function getSqlLimit() {
		/**
		 * currently not in use
		 */
		return '';
	}

	/**
	 * Returns html code for sorting field
	 *
	 * @param array Language array
	 * @return string the html sortcode
	 */
	public function getHtmlSortCode($lng, $break = false) {

		$sortcode = '';
		$fieldoptions = '';
		$orderoptions = '';

		foreach ($this->fields as $fieldname => $fieldcaption) {
			$fieldoptions.= makeoption($fieldcaption, $fieldname, $this->sortfield, true, true);
		}

		$breakorws = ($break ? '<br />' : '&nbsp;');
		foreach (array('asc' => $lng['panel']['ascending'], 'desc' => $lng['panel']['descending']) as $sortordertype => $sortorderdescription) {
			$orderoptions.= makeoption($sortorderdescription, $sortordertype, $this->sortorder, true, true);
		}

		eval("\$sortcode =\"" . getTemplate("misc/htmlsortcode", '1') . "\";");
		return $sortcode;
	}

	/**
	 * Returns html code for sorting arrows
	 *
	 * @param string URL to use as base for links
	 * @param string If set, only this field will be returned
	 * @return mixed An array or a string (if field is set) of html code of arrows
	 */
	public function getHtmlArrowCode($baseurl, $field = '') {

		global $theme;

		if ($field != ''
			&& isset($this->fields[$field])
		) {
			$baseurl = htmlspecialchars($baseurl);
			$fieldname = htmlspecialchars($field);
			eval("\$arrowcode =\"" . getTemplate("misc/htmlarrowcode", '1') . "\";");
		} else {
			$baseurl = htmlspecialchars($baseurl);
			$arrowcode = array();
			foreach ($this->fields as $fieldname => $fieldcaption) {
				$fieldname = htmlspecialchars($fieldname);
				eval("\$arrowcode[\$fieldname] =\"" . getTemplate("misc/htmlarrowcode", '1') . "\";");
			}
		}

		return $arrowcode;
	}

	/**
	 * Returns html code for searching field
	 *
	 * @param array Language array
	 * @return string the html searchcode
	 */
	public function getHtmlSearchCode($lng) {

		$searchcode = '';
		$fieldoptions = '';
		$searchtext = htmlspecialchars($this->searchtext);
		foreach ($this->fields as $fieldname => $fieldcaption) {
			$fieldoptions.= makeoption($fieldcaption, $fieldname, $this->searchfield, true, true);
		}
		eval("\$searchcode =\"" . getTemplate("misc/htmlsearchcode", '1') . "\";");
		return $searchcode;
	}

	/**
	 * Returns html code for paging
	 *
	 * @param string URL to use as base for links
	 * @return string the html pagingcode
	 */
	public function getHtmlPagingCode($baseurl) {
		if ($this->entriesperpage == 0) {
			return '';
		} else {
			$pages = intval($this->entries / $this->entriesperpage);
		}

		if ($this->entries % $this->entriesperpage != 0) {
			$pages++;
		}

		if ($pages > 1) {

			$start = $this->pageno - 4;
			if ($start < 1) {
				$start = 1;
			}

			$stop = $this->pageno + 4;
			if ($stop > $pages) {
				$stop = $pages;
			}

			$pagingcode = '<a href="' . htmlspecialchars($baseurl) . '&amp;pageno=1">&laquo;</a> <a href="' . htmlspecialchars($baseurl) . '&amp;pageno=' . ((intval($this->pageno) - 1) == 0 ? '1' : intval($this->pageno) - 1) . '">&lt;</a>&nbsp;';
			for ($i = $start;$i <= $stop;$i++) {
				if ($i != $this->pageno) {
					$pagingcode.= ' <a href="' . htmlspecialchars($baseurl) . '&amp;pageno=' . $i . '">' . $i . '</a>&nbsp;';
				} else {
					$pagingcode.= ' <strong>' . $i . '</strong>&nbsp;';
				}
			}
			$pagingcode.= ' <a href="' . htmlspecialchars($baseurl) . '&amp;pageno=' . ((intval($this->pageno) + 1) > $pages ? $pages : intval($this->pageno) + 1) . '">&gt;</a> <a href="' . $baseurl . '&amp;pageno=' . $pages . '">&raquo;</a>';
		} else {
			$pagingcode = '';
		}

		return $pagingcode;
	}
}
