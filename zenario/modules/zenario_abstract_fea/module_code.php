<?php
/*
 * Copyright (c) 2017, Tribal Limited
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



class zenario_abstract_fea extends module_base_class {
	
	protected function getMode() {
		//From version 7.6, if you have a plugin, we'll only allow the plugin to run in the mode chosen in the plugin settings.
		//If you want extra modes then you'll either need to make links in the conductor, or links to other content items.
		if ($this->instanceId && ($mode = $this->setting('mode'))) {
			return $mode;
		
		//Otherwise check the mode in the request
		} elseif (!empty($_REQUEST['mode'])) {
			return $_REQUEST['mode'];
		
		//Otherwise check the path in the request
		} elseif (!empty($_REQUEST['path'])) {
			return $this->getModeFromPath($_REQUEST['path']);
		}
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
	
	protected function isFeaAJAX() {
		return in($_REQUEST['method_call'] ?? false, 'fillVisitorTUIX', 'formatVisitorTUIX', 'validateVisitorTUIX', 'saveVisitorTUIX');
	}
	
	protected function getPathFromMode($mode) {
		return 'zenario_' . $mode;
	}
	
	protected function getModeFromPath($path) {
		return str_replace('zenario_', '', $path);
	}

	public function init() {
		requireJsLib('js/tuix.wrapper.js.php', null, true);
		
		return true;
	}

	public function showSlot() {
		//$this->twigFramework(array());
	}
	
	
	protected function feaAJAXRequest($libraryName, $path, $requests, $mode = '', $pages = array()) {
			
		//If this is the initial page load, rather than doing an AJAX request,
		//instead write a script tag to the bottom of the page
		if (empty($_REQUEST['method_call'])) {
			$this->setScriptTag = $this->pluginVisitorTUIXLink(true, $path, $requests);
			$requests = -1;
		}
		
		//Initialise the FEA library
		$this->callScriptBeforeFoot(
			$libraryName, 'init',
			$this->containerId,
			$path, $requests, $mode, $pages);
	}
	
	protected $setScriptTag = '';
	
	public function addToPageFoot() {
		
		if ($this->setScriptTag !== '') {
			echo '
<script type="text/javascript" src="', htmlspecialchars($this->setScriptTag), '"></script>';
		}
	}
	
	
	
	
	
	protected function populateItemsIdCol($path, &$tags, &$fields, &$values) {
		return 'id';
	}
	protected function populateItemsSelect($path, &$tags, &$fields, &$values) {
		return "SELECT id, name";
	}
	protected function populateItemsSelectCount($path, &$tags, &$fields, &$values) {
		return '
			SELECT COUNT(*)';
	}
	protected function populateItemsFrom($path, &$tags, &$fields, &$values) {
		return "FROM ". DB_NAME_PREFIX. "table";
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
	protected function sqlSelect($sql) {
		return sqlSelect($sql);
	}
	
	protected function populateItems($path, &$tags, &$fields, &$values) {
		
		$page = 1;
		$limit = '';
		$itemCount = 0;
		$idCol =  $this->populateItemsIdCol($path, $tags, $fields, $values);
		
		if ($pageSize = $this->populateItemsPageSize($path, $tags, $fields, $values)) {
			$row = sqlFetchRow(
				$this->populateItemsSelectCount($path, $tags, $fields, $values). "
				". $this->populateItemsFrom($path, $tags, $fields, $values). "
				". $this->populateItemsWhere($path, $tags, $fields, $values));
			$itemCount = $row[0];
			
			if ((!$page = (int) ($_REQUEST['page'] ?? false))
			 || ($page > ceil($itemCount / $pageSize))) {
				$page = 1;
			}
			
			$limit = paginationLimit($page, $pageSize);
			$tags['__page_size__'] = $pageSize;
			$tags['__page__'] = $page;
		}
		
		
		$result = $this->sqlSelect(
				$this->populateItemsSelect($path, $tags, $fields, $values). "
				". $this->populateItemsFrom($path, $tags, $fields, $values). "
				". $this->populateItemsWhere($path, $tags, $fields, $values). "
				". $this->populateItemsGroupBy($path, $tags, $fields, $values). "
				". $this->populateItemsOrderBy($path, $tags, $fields, $values). "
				". $limit);
		
		$tags['items'] = array();
		$tags['__item_sort_order__'] = array();
		while ($item = sqlFetchAssoc($result)) {
			$this->formatItemRow($item, $path, $tags, $fields, $values);
			
			$tags['items'][$item[$idCol]] = $item;
			$tags['__item_sort_order__'][] = $item[$idCol];
			
			if (!$pageSize) {
				++$itemCount;
			}
		}
		$tags['__item_count__'] = $itemCount;
	}
}