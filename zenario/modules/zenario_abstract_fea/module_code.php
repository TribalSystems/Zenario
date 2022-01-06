<?php
/*
 * Copyright (c) 2022, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');



class zenario_abstract_fea extends ze\moduleBaseClass {
	
	protected $idVarName = 'id';
	public function getIdVarName() {
		return $this->idVarName;
	}
	
	
	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		if (\ze::$isTwig) return;
		
		if (!$this->isSubClass()) {
			if ($this->subClass || ($this->subClass = $this->runSubClass(static::class, false, $path))) {
				return $this->subClass->fillVisitorTUIX($path, $tags, $fields, $values);
			}
		}
		
		
		$this->checkThingEnabled();
		$tags['enable'] = $this->thingsEnabled;
		
		if ($microtemplate = $this->setting('microtemplate')) {
			$tags['microtemplate'] = $microtemplate;
		}
		
		foreach (['columns', 'item_buttons', 'collection_buttons'] as $tag) {
			if (isset($tags[$tag]) && is_array($tags[$tag])) {
				ze\tuix::addOrdinalsToTUIX($tags[$tag]);
			}
		}
	}
	
	
	protected function runVisitorTUIX($pages = []) {
		
		if ($this->beingDisplayed && !$this->isFeaAJAX()) {
			$mode = $this->getMode();
			$path = $this->getPathFromMode($mode);
			$requests = $this->passRequests($mode, $path);
			
			$libraryName = $this->moduleClassName;
			
			$this->runVisitorTUIX2($libraryName, $path, $requests, $mode, $pages);
		}
	}
	
	//Get a link to the page where TUIX Snippets are edited, if possible
	private static $tsLink;
	private function getTUIXSnippetLink() {
		
		//No need to get the link if no TUIX snippet is being used.
		//Also, we can cache the link for any other FEA plugins, as part from the ID, the page will be the same each time.
		if ($this->tuixSnippetId && self::$tsLink === null) {
		
			$sql = "
				SELECT ci.id, ci.type, ci.visitor_version, ci.alias, path.to_state
				FROM ". DB_PREFIX. "nested_paths AS path
				INNER JOIN ". DB_PREFIX. "plugin_item_link AS pil
				   ON pil.instance_id = path.instance_id
				INNER JOIN ". DB_PREFIX. "content_items AS ci
				   ON ci.id = pil.content_id
				  AND ci.type = pil.content_type
				  AND ci.visitor_version = pil.content_version
				WHERE path.command = 'edit_tuix_snippet'
				ORDER BY ci.language_id = '". ze\escape::asciiInSQL(ze::$visLang). "' DESC";
		
			if (($cItem = ze\sql::fetchAssoc($sql))
			 && (ze\content::checkPerm($cItem['id'], $cItem['type'], $cItem['visitor_version']))) {
				self::$tsLink = ze\link::toItem($cItem['id'], $cItem['type'], false, ['state' => $cItem['to_state']], $cItem['alias']). '&id=';
		
			} elseif (ze\priv::check('_PRIV_EDIT_SITE_SETTING')) {
				self::$tsLink = 'organizer.php?fromCID='. $this->cID. '&fromCType='. $this->cType. '#zenario__modules/panels/tuix_snippets//';
			
			} else {
				self::$tsLink = false;
			}
		}
		
		if ($this->tuixSnippetId && self::$tsLink) {
			return self::$tsLink. $this->tuixSnippetId;
		} else {
			return false;
		}
	}
	
	
	private function runVisitorTUIX2($libraryName, $path, $requests, $mode = '', $pages = []) {
		
		if ($this->beingDisplayed
		 && !$this->isFeaAJAX()
		 && $this->returnVisitorTUIXEnabled($path)) {
			
			//Code to use a second AJAX load if this was already an AJAX load
			//if (!empty($_REQUEST['method_call'])) {
			//	$this->callScriptBeforeFoot(
			//		$libraryName, 'init',
			//		$this->containerId,
			//		$path, $requests, $mode, $pages);
			//	return;
			//}
			
			
			//Initialise the FEA library, setting $request to -1 to indicate that
			//we'll be inserting the data by calling the loadData() function.
			$this->callScript(
				$libraryName, 'init',
				$this->containerId,
				$path, -1, $mode, $pages,
				$libraryName, $this->idVarName
			);
		
			//Trim any empty requests
			foreach ($requests as $key => $val) {
				if (!$val) {
					unset($requests[$key]);
				}
			}
			//Add the path and mode if not there already
			$requests['path'] = $path;
			$requests['mode'] = $mode;
			
			//Populate the TUIX tags, and run the fillVisitorTUIX() method
			$tags = [];
			ze\tuix::visitorTUIX($this, $path, $tags);
			
			//Output the tags onto the page and pass them through to the loadData() function.
			$this->callScript(
				$this->returnGlobalName(),
				'loadData',
				$requests,
				ze\tuix::stringify($tags),
				$this->getTUIXSnippetLink()
			);
			
		}
	}
	
	//Deprecated old names
	protected function feaAJAXRequestIfNeeded($pages = []) {
		$this->runVisitorTUIX($pages);
	}
	protected function feaAJAXRequest($libraryName, $path, $requests, $mode, $pages) {
		$this->runVisitorTUIX2();
	}
	
	
	protected function passRequests($mode, $path) {
		
		//New 8.2 logic, passes all GET requests
		$vars = array_filter(array_merge($_GET, ze::$vars));
		
		unset(
			//Clear any standard content item variables
			$vars['cID'], $vars['cType'], $vars['cVersion'], $vars['visLang'],
			
			//Clear any requests that point to this nest/slide/state
			$vars['state'], $vars['slideId'], $vars['slideNum'],
			
			//Clear some FEA variables
			$vars['mode'], $vars['path']
		);
		
		//Clear any standard plugin variables, unless this is a link to the showSingleSlot() method
		if (!isset($vars['method_call'])
		 || $vars['method_call'] != 'showSingleSlot') {
			unset($vars['slotName'], $vars['instanceId'], $vars['method_call']);
		}
		
		//Paranoia check, just in case the idVarName is in the POST and not the GET
		//(Might be okay to delete)
		if ($this->idVarName
		 && !isset($vars[$this->idVarName])
		 && isset($_POST[$this->idVarName])) {
			$vars[$this->idVarName] = $_POST[$this->idVarName];
		}

		
		//Old 8.1 logic, only passes certain variables
		//$vars = ze::$vars;
		//if ($this->idVarName && !isset($vars[$this->idVarName])) {
		//	$vars[$this->idVarName] = $_REQUEST[$this->idVarName] ?? false;
		//}
		
		return $vars;
	}
	
	protected $thingsEnabled;
	protected function checkThingEnabled($thing = '') {
		
		if (!isset($this->thingsEnabled)) {
			$this->thingsEnabled = [];
			foreach ($this->zAPISettings as $settingName => &$value) {
				if (substr($settingName, 0, 7) == 'enable.') {
					$this->thingsEnabled[substr($settingName, 7)] = (bool) $value;
				}
			}
			$this->thingsEnabled[$this->setting('mode')] = true;
		}
		
		return !empty($this->thingsEnabled[$thing]);
	}
	
	protected $newThing = false;
	protected function checkNewThing($table, $clear = true) {
		$this->newThing = ze\db::getNewThingFromSession($table, $clear);
	}
	
	protected function sortingEnabled() {
		return $this->checkThingEnabled('sort_list') || $this->checkThingEnabled('sort_col_headers');
	}
	
	protected function sortCol($tags) {
		if ($this->sortingEnabled()) {
			if ($col = $tags['key']['sortCol'] ?? '') {
				if (!empty($tags['columns'][$col]['sort_asc'])
				 || !empty($tags['columns'][$col]['sort_desc'])) {
					return $col;
				}
			}
		}
		return '';
	}
	
	protected function sortDesc($tags) {
		return ($col = $this->sortCol($tags))
			&& (!empty($tags['columns'][$col]['sort_desc']))
			&& (empty($tags['columns'][$col]['sort_asc']) || !empty($tags['key']['sortDesc']));
	}
	
	protected function isFeaAJAX() {
		switch ($_REQUEST['method_call'] ?? false) {
			case 'fillVisitorTUIX':
			case 'formatVisitorTUIX':
			case 'validateVisitorTUIX':
			case 'saveVisitorTUIX':
				return true;
		}
		
		return false;
	}
	
	protected function gettingBreadcrumbs($tags = false) {
		return !$this->beingDisplayed || empty($tags);
	}

	public function init() {
		ze::requireJsLib('zenario/js/tuix.wrapper.js.php');
		
		return true;
	}

	public function showSlot() {
		//$this->twigFramework([]);
	}
	
	
	
	protected function sqlSelect($sql) {
		return ze\sql::select($sql);
	}
	
	
	protected function populateItemsIdCol($path, &$tags, &$fields, &$values) {
		return 'id';
	}
	protected function populateItemsIdColDB($path, &$tags, &$fields, &$values) {
		return $this->populateItemsIdCol($path, $tags, $fields, $values);
	}
	protected function populateItemsSelect($path, &$tags, &$fields, &$values) {
		return "SELECT id, name";
	}
	protected function populateItemsSelectCount($path, &$tags, &$fields, &$values) {
		return '
			SELECT COUNT(*)';
	}
	protected function populateItemsFrom($path, &$tags, &$fields, &$values) {
		return "FROM ". DB_PREFIX. "table";
	}
	protected function populateItemsWhere($path, &$tags, &$fields, &$values) {
		return "WHERE false";
	}
	protected function populateItemsOrderBy($path, &$tags, &$fields, &$values) {
		return "ORDER BY name";
	}
	protected function populateItemsGroupBy($path, &$tags, &$fields, &$values) {
		return '';
	}
	protected function populateItemsPageSize($path, &$tags, &$fields, &$values) {
		return false;
	}
	protected function formatItemRow(&$item, $path, &$tags, &$fields, &$values) {
		//...
	}
	
	//Functions for generating smart breadcrumbs.
	//They default to the functions for the items, unless overridden.
	protected function populateBreadcrumbsSelect() {
		$tags = $fields = $values = [];
		return $this->populateItemsSelect('', $tags, $fields, $values);
	}
	protected function populateBreadcrumbsFrom() {
		$tags = $fields = $values = [];
		return $this->populateItemsFrom('', $tags, $fields, $values);
	}
	protected function populateBreadcrumbsWhere() {
		$tags = $fields = $values = [];
		return $this->populateItemsWhere('', $tags, $fields, $values);
	}
	protected function populateBreadcrumbsOrderBy() {
		$tags = $fields = $values = [];
		return $this->populateItemsOrderBy('', $tags, $fields, $values);
	}
	protected function populateBreadcrumbsGroupBy() {
		$tags = $fields = $values = [];
		return $this->populateItemsGroupBy('', $tags, $fields, $values);
	}
	protected function populateBreadcrumbsPageSize() {
		$tags = $fields = $values = [];
		return $this->populateItemsPageSize('', $tags, $fields, $values);
	}
	protected function formatBreadcrumbRow(&$item) {
		//...
	}
	
	
	public function generateSmartBreadcrumbs() {
		
		$sql = $this->populateBreadcrumbsSelect(). "
			". $this->populateBreadcrumbsFrom(). "
			". $this->populateBreadcrumbsWhere(). "
			". $this->populateBreadcrumbsGroupBy(). "
			". $this->populateBreadcrumbsOrderBy();
		
		if ($pageSize = (int) $this->populateBreadcrumbsPageSize()) {
			$sql .= '
				LIMIT '. $pageSize;
		}
		
		$breadcrumbs = [];
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$breadcrumb = $this->formatBreadcrumbRow($row);
			
			if (!empty($breadcrumb)) {
				$breadcrumbs[] = $breadcrumb;
			}
		}
		
		return $breadcrumbs;
	}
	
	
	
	protected function applyTwigSnippets($id, &$item, $path, &$tags, &$fields, &$values) {
		if ($this->hasCustomisedColumns($tags)) {
			foreach ($tags['columns'] as $key => &$col) {
				
				if (!$this->customVisibility('column', $key, $col, $id, $item)) {
					if (isset($item[$key])) {
						$item[$key] = null;
					}
					
					if (!isset($tags['_hiddenColumns'][$key])) {
						$tags['_hiddenColumns'][$key] = [];
					}
					$tags['_hiddenColumns'][$key][$id] = true;
				
				} elseif (!empty($col['twig_snippet'])) {
					$item[$key] = $this->twigFramework(
						['id' => $id, 'item' => $item, 'column' => $col, 'tuix' => $tags],
						true, $col['twig_snippet']
					);
				
				} else {
					$this->setCustomColumnValue($key, $col, $item);
				}
			}
		}
		
		if ($this->hasCustomisedItemButtons($tags)) {
			foreach ($tags['item_buttons'] as $key => &$button) {
				
				if (!$this->customVisibility('item_button', $key, $button, $id, $item)) {
					if (!isset($tags['_hiddenItemButtons'][$key])) {
						$tags['_hiddenItemButtons'][$key] = [];
					}
					$tags['_hiddenItemButtons'][$key][$id] = true;
				}
			}
		}
	}
	
	protected $hasCC;
	protected function hasCustomisedColumns(&$tags) {
		
		if (!isset($this->hasCC)) {
			$this->hasCC = false;
			
			if (!empty($tags['columns'])
			 && is_array($tags['columns'])) {
				foreach ($tags['columns'] as $key => $ob) {
					if (!empty($ob['twig_snippet'])) {
						$this->hasCC = true;
						break;
					
					} elseif ($this->checkIfCustomLogicIsUsed('column', $key, $ob)) {
						$this->hasCC = true;
						break;
					}
				}
			}
			
			if (!isset($tags['_hiddenColumns'])) {
				$tags['_hiddenColumns'] = [];
			}
		}
		
		return $this->hasCC;
	}
	
	protected $hasCIB;
	protected function hasCustomisedItemButtons(&$tags) {
		
		if (!isset($this->hasCIB)) {
			$this->hasCIB = false;
			
			if (!empty($tags['item_buttons'])
			 && is_array($tags['item_buttons'])) {
				foreach ($tags['item_buttons'] as $key => $ob) {
					if ($this->checkIfCustomLogicIsUsed('item_button', $key, $ob)) {
						$this->hasCIB = true;
						break;
					}
				}
			}
			
			if (!isset($tags['_hiddenItemButtons'])) {
				$tags['_hiddenItemButtons'] = [];
			}
		}
		
		return $this->hasCIB;
	}
	
	
	protected function checkIfCustomLogicIsUsed($obType, $obCodeName, &$ob) {
		return false;
	}
	
	protected function customVisibility($obType, $obCodeName, &$ob, $itemId, &$item) {
		return true;
	}
	
	protected function setCustomColumnValue($colCodeName, &$col, &$item) {
		//...
	}
	
	
	protected function populateItems($path, &$tags, &$fields, &$values) {
		
		$page = 1;
		$limit = '';
		$itemCount = 0;
		$idCol =  $this->populateItemsIdCol($path, $tags, $fields, $values);
		
		if ($pageSize = $this->populateItemsPageSize($path, $tags, $fields, $values)) {
			
			$sql = $this->populateItemsSelectCount($path, $tags, $fields, $values). "
				". $this->populateItemsFrom($path, $tags, $fields, $values). "
				". $this->populateItemsWhere($path, $tags, $fields, $values);
			
			$result = $this->sqlSelect($sql);
			$row = ze\sql::fetchRow($result);
			$itemCount = $row[0];
			
			if (ze\tuix::$feaDebugMode) {
				ze\tuix::$feaSelectCountQuery = $sql;
			}
			unset($sql, $row);
			
			if ((!$page = (int) ($_REQUEST['page'] ?? false))
			 || ($page > ceil($itemCount / $pageSize))) {
				$page = 1;
			}
			
			$limit = ze\sql::limit($page, $pageSize);
			$tags['__page_size__'] = $pageSize;
			$tags['__page__'] = $page;
		}
		
		$sql = $this->populateItemsSelect($path, $tags, $fields, $values). "
				". $this->populateItemsFrom($path, $tags, $fields, $values). "
				". $this->populateItemsWhere($path, $tags, $fields, $values). "
				". $this->populateItemsGroupBy($path, $tags, $fields, $values);
		
		$orderBy = $this->populateItemsOrderBy($path, $tags, $fields, $values);
		
		//If there is something newly created, try to place it at the top of the list
		if ($this->newThing !== false) {
			$pos = stripos($orderBy, 'ORDER BY');
			
			if ($pos !== false) {
				$orderBy =
					substr($orderBy, 0, $pos + 8). ' '.
					$this->populateItemsIdColDB($path, $tags, $fields, $values). " = '".
					ze\escape::sql($this->newThing). "' DESC, ".
					substr($orderBy, $pos + 8);
			}
		}
		
		$sql .= "
			". $orderBy. "
			". $limit;
		
		$result = $this->sqlSelect($sql);
		
		if (ze\tuix::$feaDebugMode) {
			ze\tuix::$feaSelectQuery = $sql;
		}
		unset($sql);
		
		$tags['items'] = [];
		$tags['__item_sort_order__'] = [];
		while ($item = ze\sql::fetchAssoc($result)) {
			$id = $item[$idCol];
			$this->formatItemRow($item, $path, $tags, $fields, $values);
			
			//Automatically add the "newly created item" glow if a created item was set
			if ($this->newThing !== false
			 && $this->newThing == $id) {
			 	if (isset($item['row_class'])) {
					$item['row_class'] .= ' zfea_new_row';
				} else {
					$item['row_class'] = 'zfea_new_row';
				}
			}
			
			$this->applyTwigSnippets($id, $item, $path, $tags, $fields, $values);
			
			$tags['items'][$id] = $item;
			$tags['__item_sort_order__'][] = $item[$idCol];
			
			if (!$pageSize) {
				++$itemCount;
			}
		}
		$tags['__item_count__'] = $itemCount;
		
		if ($limit
		 && $pageSize < $itemCount) {
		 	
		 	$mrg = [
		 		'count' => $itemCount,
				'start' => ze\sql::pageStart($page, $pageSize) + 1,
				'stop' => min(ze\sql::pageStart($page + 1, $pageSize), $itemCount)
			];
		 	
			if ($this->checkThingEnabled('search_box') && !empty($_REQUEST['search'])) {
				$mrg['search'] = $_REQUEST['search'];
				$tags['__items_phrase__'] = $this->phrase('[[start]] - [[stop]] of [[count]] items found from search "[[search]]"', $mrg);
			} elseif ($itemCount) {
				$tags['__items_phrase__'] = $this->phrase('[[start]] - [[stop]] of [[count]] items', $mrg);
			}
		} else {
			if ($this->checkThingEnabled('search_box') && !empty($_REQUEST['search'])) {
				$mrg = ['search' => $_REQUEST['search']];
				if($itemCount) {
					$tags['__items_phrase__'] = $this->nphrase('1 item found from search "[[search]]"', '[[count]] items found from search "[[search]]"', $itemCount, $mrg);
				} else {
					$tags['__items_phrase__'] = $this->phrase('No items found from search "[[search]]"', $mrg);
				}
			} elseif ($itemCount) {
				$tags['__items_phrase__'] = $this->nphrase('1 item', '[[count]] items', $itemCount);
			}
		}
	}
	
	private $tuixSnippetId = false;
	protected function mergeCustomTUIX(&$tags) {
		if (($this->tuixSnippetId = $this->setting('~tuix_snippet~'))
		 && ($custom = ze\sql::fetchValue('SELECT custom_json FROM '. DB_PREFIX. 'tuix_snippets WHERE id = '. (int) $this->tuixSnippetId))
		 && ($custom = json_decode($custom, true))
		 && (is_array($custom))
		 && (!empty($custom))) {
			ze\tuix::merge($tags, $custom);
		}
	}
	
	
	
	protected function applySearchBarSetting(&$tags) {
	
		if (!$this->checkThingEnabled('search_box')) {
			$tags['hide_search_bar'] = true;
		} elseif (empty($_REQUEST['search'])) {
			$numberOfItemsRequired = $this->setting('search_box_items_required');
			if ($numberOfItemsRequired && count($tags['items']) < $numberOfItemsRequired) {
				$tags['hide_search_bar'] = true;
			}
		}
	}
	
	
	protected function setupOverridesForPhrases(&$box, &$fields, &$values) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	//Add dataset fields onto an FEA form
	//Called in fillVisitorTUIX
	protected function setupDatasetFields(&$tags, &$fields, &$values, $tab, $dataset, $datasetFieldIds, $recordId, $startOrd = 99, $edit = true, $flat = true) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	//Save dataset fields on an FEA form added by setupDatasetFields(...)
	//Called in saveVisitorTUIX
	protected function saveDatasetFields(&$tags, &$fields, &$values, $tab, $dataset, $datasetFieldIds, $recordId) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function includeEditor() {
		if (!ze::isAdmin()) {
			ze::requireJsLib('zenario/js/ace.wrapper.js.php');
			ze::requireJsLib('zenario/libs/yarn/toastr/toastr.min.js', 'zenario/libs/yarn/toastr/build/toastr.min.css');
			ze::requireJsLib('zenario/libs/yarn/spectrum-colorpicker/spectrum.min.js', 'zenario/libs/yarn/spectrum-colorpicker/spectrum.min.css');
		}
	}
	
}