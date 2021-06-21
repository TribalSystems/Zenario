<?php
/*
 * Copyright (c) 2021, Tribal Limited
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

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		if ($box['key']['id']
		 && $box['key']['id_is_error_log_id']
		 && ze\module::inc('zenario_error_log')) {
			$brokenAlias = ze\row::get(ZENARIO_ERROR_LOG_PREFIX.'error_log', 'page_alias', ['id' => $box['key']['id']]);
			$brokenAlias = substr($brokenAlias, 0, 255);
			
			if (ze\row::exists('spare_aliases', ['alias' => $brokenAlias])) {
				$box['key']['id'] = $brokenAlias;
			} else {
				$box['key']['id'] = '';
				$values['spare_alias/alias'] = $brokenAlias;
				$fields['spare_alias/alias']['readonly'] = true;
				$box['title'] = ze\admin::phrase('Fixing the 404 error "[[alias]]"', ['alias' => ($brokenAlias)]);
			}
			
			$fields['spare_alias/delete_error_log']['label'] = ze\admin::phrase('Delete all instances of "[[alias]]" from error log', ['alias' => $brokenAlias]);
			$values['spare_alias/delete_alias'] = $brokenAlias;
		}
		
		if (!$box['key']['id']) {
			$box['tabs']['spare_alias']['edit_mode']['on'] = true;
			$box['tabs']['spare_alias']['edit_mode']['always_on'] = true;
			
			if(isset($values['spare_alias/hyperlink_target'])){
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
				$values['spare_alias/redirect_target_url'] = ze\link::toItem($tagId, 'html', true, '', false, false, $forceAliasInAdminMode = true);
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
		if ($values['spare_alias/delete_error_log']== true) {
			if (ze\module::inc('zenario_error_log')) {
				
				$aliasCount = ze\row::count(ZENARIO_ERROR_LOG_PREFIX.'error_log', ['page_alias' => $values['spare_alias/alias']]);
			
				$box['confirm']['show'] = true;
				$box['confirm']['message'] = \ze\admin::phrase('[[number]] instances of "[[name]]" will be deleted from the error log.',['number' => $aliasCount, 'name' => $values['spare_alias/alias']]);
				$box['confirm']['button_message'] = \ze\admin::phrase('Confirm ');
			}
		}
		if (!$box['key']['id']) {
			if (!$values['spare_alias/alias']) {
				$box['tabs']['spare_alias']['errors'][] = ze\admin::phrase('Please enter an Alias.');
		
			} elseif (ze\row::exists('spare_aliases', ['alias' => $values['spare_alias/alias']])) {
				$box['tabs']['spare_alias']['errors'][] = ze\admin::phrase('The spare alias "[[alias]]" is already in use.', ['alias' => $values['spare_alias/alias']]);
		
			} elseif ($mistakesInAlias = ze\contentAdm::validateAlias($values['spare_alias/alias'])) {
				$box['tabs']['spare_alias']['errors'] = $mistakesInAlias;
			}
		}
		
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_MANAGE_SPARE_ALIAS');
		
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
		if ($values['spare_alias/delete_error_log']== true) {
			if (ze\module::inc('zenario_error_log')) {
				$deleteAliasLog = $values['spare_alias/delete_alias'];
				
				if ($deleteAliasLog) {
					$sql = '
					DELETE FROM ' . DB_PREFIX . ZENARIO_ERROR_LOG_PREFIX . 'error_log
					WHERE page_alias = "' . ze\escape::sql($deleteAliasLog) . '"';
					ze\sql::update($sql);
				}
			}
			
		}
	}
	
}
