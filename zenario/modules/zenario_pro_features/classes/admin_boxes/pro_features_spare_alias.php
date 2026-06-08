<?php
/*
 * Copyright (c) 2026, Tribal Limited
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

				
class zenario_pro_features__admin_boxes__pro_features_spare_alias extends ze\moduleBaseClass {
	
	
	protected function queryLogTable($targetAlias, $doDelete = false) {
		
		//Look through the error_404_log table, looking for related aliases to the current one.
		//We'll start by using a "like" to do a basic string match.
		$count = 0;
		$sql = "
			SELECT l.id, l.page_alias
			FROM ". DB_PREFIX. "error_404_log AS l
			WHERE page_alias LIKE '%". ze\escape::sql($targetAlias). "%'";
		
		foreach (ze\sql::select($sql) as $row) {
			
			//The basic string match will return a lot of false-positives.
			//use the aliasHasSupportedExtension() on each row and check it actually contains an alias,
			//and that the alias actually matches the current one.
			$alias = ze\contentAdm::aliasHasSupportedExtension($row['page_alias']);
			
			if ($alias !== false
			 && $alias === $targetAlias) {
			 	
			 	//Either return a count or do a delete, depending on what stage of the FAB we are at.
				++$count;
				
				if ($doDelete) {
					ze\row::delete('error_404_log', $row['id']);
				}
			}
		}
		
		return $count;
	}
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		if ($box['key']['id'] && $box['key']['id_is_error_log_id']) {
			
			$brokenAlias = ze\row::get('error_404_log', 'page_alias', ['id' => $box['key']['id']]);
			$brokenAlias = substr($brokenAlias, 0, 255);
			
			if (!$brokenAlias) {
				echo ze\admin::phrase('Item not found.');
				exit;
			}
			
			$alias = ze\contentAdm::aliasHasSupportedExtension($brokenAlias);
			
			if ($alias === false) {
				echo ze\admin::phrase('This alias could not be used.');
				exit;
			}
			
			
			if (ze\row::exists('spare_aliases', ['alias' => $alias])) {
				$box['key']['id'] = $alias;
			} else {
				$box['key']['id'] = '';
				$values['spare_alias/alias'] = $alias;
				$fields['spare_alias/alias']['readonly'] = true;
				$box['title'] = ze\admin::phrase('Fixing the 404 (page not found) error "[[alias]]"', ['alias' => ($brokenAlias)]);
			}
			
			$values['spare_alias/delete_alias'] = $alias;

			$logCount = $this->queryLogTable($values['spare_alias/delete_alias']);
			
			$fields['spare_alias/delete_error_log']['label'] =
				ze\admin::phrase(
					'Delete all instances of "[[alias]]" from error log ([[log_count]] in log) ',
					['alias' => $alias, 'log_count' => (int) $logCount]
				);
		} else {
			$fields['spare_alias/delete_error_log']['hidden'] = true;
		}
		
		if (!$box['key']['id']) {
			$box['tabs']['spare_alias']['edit_mode']['on'] = true;
			
			if (isset($values['spare_alias/hyperlink_target'])) {
				$values['spare_alias/hyperlink_target'] = ze::$homeCType . '_' . ze::$homeEquivId;
			}
			
		} else {
			$details = ze\row::get('spare_aliases', true, $box['key']['id']);
			$box['title'] = ze\admin::phrase('Editing the spare alias "[[alias]]"', ['alias' => ($details['alias'])]);
				
			$fields['spare_alias/alias']['value'] = $details['alias'];
			$fields['spare_alias/alias']['readonly'] = true;
				
			$fields['spare_alias/target_loc']['value'] = $details['target_loc'];
				
			if ($details['target_loc'] == 'int') {
				$fields['spare_alias/hyperlink_target']['value'] = $details['content_type']. '_'. $details['content_id'];
			} else {
				$fields['spare_alias/ext_url']['value'] = $details['ext_url'];
			}
			
			$box['last_updated'] = ze\admin::phrase('Created [[date_time]]', ['date_time' => ze\date::formatDateTime($details['created_datetime'])]);
		}
		
		//Show suffix if settings enabled
		if (ze::setting('mod_rewrite_enabled') && ($suffix = ze::setting('mod_rewrite_suffix'))) {
			$fields['spare_alias/alias']['post_field_html'] = "&nbsp;" . $suffix;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['spare_alias/hyperlink_target']['hidden'] = 
			$values['spare_alias/target_loc'] != 'int';
		
		$fields['spare_alias/ext_url']['hidden'] = 
			$values['spare_alias/target_loc'] != 'ext';
		
		
		//Remember redirect target
		if ($values['spare_alias/target_loc'] == 'int') {
			$tagId = $values['spare_alias/hyperlink_target'];
			if ($tagId) {
				$values['spare_alias/redirect_target_url'] = ze\link::toItemWithAlias($tagId, 'html', true);
			}
		} elseif ($values['spare_alias/target_loc'] == 'ext') {
			$target = $values['spare_alias/ext_url'];
			if (!preg_match("/^((http|https|ftp):\/\/)/", $target)) {
				$target = 'http://' . $target;
			}
			$values['spare_alias/redirect_target_url'] = $target;
		}
		//Show preview
		$alias = $values['spare_alias/alias'];
		$suffix = ze::setting('mod_rewrite_suffix');
		if ($alias !== "") {
			if ($suffix && strpos($alias, $suffix) === false) {
				$alias .= $suffix;
			}
		}
		$fields['spare_alias/preview']['snippet']['html'] = '<a id="spare_alias_preview" data-base="' . ze\link::absolute() . '" data-suffix="' . $suffix . '" href="' . ze\link::absolute() . $alias . '" target="spare_alias_preview">' . ze\link::absolute() . $alias . '</a>';

		
			
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$box['confirm']['show'] = false;
		$box['confirm']['message'] = '';
		if ($values['spare_alias/delete_error_log'] == true) {
			
			$logCount = $this->queryLogTable($values['spare_alias/delete_alias']);
		
			$box['confirm']['show'] = true;
			$box['confirm']['message'] = \ze\admin::phrase('[[number]] instances of "[[name]]" will be deleted from the error log.',['number' => $logCount, 'name' => $values['spare_alias/alias']]);
			$box['confirm']['button_message'] = \ze\admin::phrase('Confirm ');
		}
		if (!$box['key']['id']) {
			if (!$values['spare_alias/alias']) {
				$box['tabs']['spare_alias']['errors'][] = ze\admin::phrase('Please enter some text.');
		
			} elseif (ze\row::exists('spare_aliases', ['alias' => $values['spare_alias/alias']])) {
				$box['tabs']['spare_alias']['errors'][] = ze\admin::phrase('The spare alias "[[alias]]" is already in use.', ['alias' => $values['spare_alias/alias']]);
		
			} elseif ($mistakesInAlias = ze\contentAdm::validateAlias($values['spare_alias/alias'], false, false, false, $isSpareAlias = true)) {
				$fields['spare_alias/alias']['error'] = implode('<br />', $mistakesInAlias);
			}
		}
		
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_PUBLISH_CONTENT_ITEM');
		
		$alias = ($box['key']['id'] ?: $values['spare_alias/alias']);
		
		$row = [
				'ext_url' => '',
				'content_id' => 0,
				'content_type' => ''];
		
		if ($values['spare_alias/target_loc'] == 'int') {
			$row['target_loc'] = 'int';
			ze\content::getCIDAndCTypeFromTagId($row['content_id'], $row['content_type'], $values['spare_alias/hyperlink_target']);
		
		} elseif ($values['spare_alias/target_loc'] == 'ext') {
			$row['target_loc'] = 'ext';
			$row['ext_url'] = $values['spare_alias/ext_url'];
		
		} else {
			exit;
		}
		
		if (!$box['key']['id']) {
			$row['created_datetime'] = ze\date::now();
		}
		
		ze\row::set('spare_aliases', $row, ['alias' => $alias]);
		
		//Delete all instances of alias from error log		
		if ($values['spare_alias/delete_error_log'] == true) {
			$deleteAliasLog = $values['spare_alias/delete_alias'];
			
			if ($deleteAliasLog) {
				$this->queryLogTable($values['spare_alias/delete_alias'], true);
			}
			
		}
	}
	
}
