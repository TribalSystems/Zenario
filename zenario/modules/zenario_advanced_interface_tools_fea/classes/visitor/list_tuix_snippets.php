<?php
/*
 * Copyright (c) 2020, Tribal Limited
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


class zenario_advanced_interface_tools_fea__visitor__list_tuix_snippets extends zenario_advanced_interface_tools_fea {
	
	protected $scope = false;
	protected $userId = false;
	
	public function init() {
	    if (ze\user::can('edit', 'pluginSetting')) {
			$this->runVisitorTUIX();
			return true;
		} else {
			return ZENARIO_403_NO_PERMISSION;
		}
	}
	
	public function returnVisitorTUIXEnabled($path) {
		return true;
	}
	
	protected function populateItemsIdCol($path, &$tags, &$fields, &$values) {
		return 'id';
	}
	protected function populateItemsIdColDB($path, &$tags, &$fields, &$values) {
		return 'ts.id';
	}
	protected function populateItemsSelectCount($path, &$tags, &$fields, &$values) {
		return '
			SELECT COUNT(DISTINCT ts.id)';
	}
	protected function populateItemsSelect($path, &$tags, &$fields, &$values) {
		$sql = '
			SELECT
				ts.id,
				ts.name,
				ts.custom_json,
				COUNT(DISTINCT ps.instance_id, ps.egg_id) AS `usage`';
		return $sql;
	}
	
	protected function populateBreadcrumbsSelect() {
		$sql = '
			SELECT
				ts.id,
				ts.name';
		return $sql;
	}
	
	protected function populateBreadcrumbsFrom() {
		$sql = '
			FROM ' . DB_PREFIX . 'tuix_snippets AS ts';
		
		return $sql;
	}
	protected function populateItemsFrom($path, &$tags, &$fields, &$values) {
		$sql = $this->populateBreadcrumbsFrom();
		
		$sql .= "
			LEFT JOIN ". DB_PREFIX. "plugin_settings AS ps
			   ON ps.name = '~tuix_snippet~'
			  AND ps.value = ts.id";
			  
		return $sql;
	}
	
	protected function formatBreadcrumbRow(&$item) {
		return [
			'name' => $item['name'],
			'request' => [
				'id' => $item['id']
			]
		];
	}
	
	
	protected function populateItemsWhere($path, &$tags, &$fields, &$values) {
		$sql = '
			WHERE TRUE';
		
		if ($this->checkThingEnabled('search_box')) {
			if (!empty($_REQUEST['search'])) {
				$sql .= " AND name LIKE '%". ze\escape::like($_REQUEST['search'] ?? false, true). "%'";
			}
		}
		
		return $sql;
	}
	protected function populateItemsGroupBy($path, &$tags, &$fields, &$values) {
		return '
			GROUP BY ts.id';
	}
	protected function populateItemsOrderBy($path, &$tags, &$fields, &$values) {
		
		$sql = '
			ORDER BY ts.name';
		
		#switch ($this->sortCol($tags)) {
		#	case 'location_count':
		#		$sql .= 'COUNT(DISTINCT l.id)';
		#		break;
		#	default:
		#		$sql .= 'ts.name';
		#}
		#
		#if ($this->sortDesc($tags)) {
		#	$sql .= ' DESC';
		#}
		
		return $sql;
	}
	protected function populateItemsPageSize($path, &$tags, &$fields, &$values) {
		return false;
	}
	protected function formatItemRow(&$item, $path, &$tags, &$fields, &$values) {
		if ($tuix = json_decode($item['custom_json'], true)) {
			foreach (['columns', 'collection_buttons', 'item_buttons'] as $tag) {
				if (isset($tuix[$tag]) && is_array($tuix[$tag])) {
					$item['num_'. $tag] = count($tuix[$tag]);
				}
			}
		}
	}
	
	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		parent::fillVisitorTUIX($path, $tags, $fields, $values);
		$this->translatePhrasesInTUIX($tags, $path);
		$this->checkNewThing('tuix_snippets');
		$this->populateItems($path, $tags, $fields, $values);
		
		$this->applySearchBarSetting($tags);
	}
	
	public function handlePluginAJAX() {
		
		if (ze::post('command') == 'delete_tuix_snippet' && ze\user::can('edit', 'pluginSetting')) {
			$sql = '
				DELETE FROM '. DB_PREFIX. 'tuix_snippets
				WHERE id = '. (int) ze::post('id');
			ze\sql::update($sql);
		}
		
	}


}
