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

				
class zenario_pro_features__admin_boxes__alias extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		//Load details on the spare aliases in use in the system, and which have been chosen here
		$sql = "
			SELECT sa.alias AS spare_alias
			FROM ". DB_PREFIX. "spare_aliases AS sa
			WHERE sa.content_id = ". (int) $box['key']['cID']. "
			AND sa.content_type = '". ze\escape::sql($box['key']['cType']). "'
			ORDER BY sa.alias";
		$result = ze\sql::select($sql);
		
		$spareAliases = [];
		while ($row = ze\sql::fetchAssoc($result)) {
			$spareAliases[] = $row['spare_alias'];
		}
		
		$values['meta_data/spare_aliases'] = implode(',', $spareAliases);
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$oldAliasAdded = false;
		
		//If the "add old alias" button is pressed, add the old alias to the list
		if (empty($fields['meta_data/create_spare_alias']['hidden'])
		 && !empty($fields['meta_data/create_spare_alias']['pressed'])) {
		 	$alias = $fields['meta_data/alias']['value'];
			$values['meta_data/spare_aliases'] = implode(',', ze\ray::explodeAndTrim($values['meta_data/spare_aliases']. ','. $alias));
		}
		
		//I don't want any aliases to be flagged with the "missing" tag, so I'm going to manually register them
		if (empty($fields['meta_data/spare_aliases']['values'])) {
			$fields['meta_data/spare_aliases']['values'] = [];
		}
		foreach (ze\ray::explodeAndTrim($values['spare_aliases']) as $alias) {
			$fields['meta_data/spare_aliases']['values'][$alias] = [
				'label' => $alias
				
				//Adding this line would give them the "alias" icon as well
				//'css_class' => 'alias_url'
			];
			
			if ($alias == $fields['meta_data/alias']['value']) {
				$oldAliasAdded = true;
			}
		}
		
		//Only show the "Create a Spare Alias under the old name" field if there is an existing alias,
		//which is not already in the spare aliases table.
		if (isset($fields['meta_data/create_spare_alias'])) {
			$fields['meta_data/create_spare_alias']['hidden'] = 
				$oldAliasAdded
			 || empty($fields['meta_data/alias']['value'])
			 || !ze\content::isPublished($box['key']['cID'], $box['key']['cType']);
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//Check permissions
		if (ze\priv::check('_PRIV_MANAGE_SPARE_ALIAS')) {
			$box['tabs']['meta_data']['notices']['alias_cannot_also_be_spare_alias']['show'] = false;
			
			foreach (ze\ray::explodeAndTrim($values['spare_aliases']) as $alias) {
				
				if ($alias == $values['meta_data/alias']) {
					$href = ze\link::protocol() . ze\link::host() . SUBDIRECTORY . 'zenario/admin/organizer.php#zenario__administration/panels/zenario_settings_pro_features__spare_aliases';
					$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
					$linkEnd = '</a>';
					$box['tabs']['meta_data']['notices']['alias_cannot_also_be_spare_alias']['message'] =
						ze\admin::phrase('There is already a spare alias "[[alias]]", so you cannot also use it for this item\'s alias. [[link_start]]View spare aliases[[link_end]]',
						['alias' => $alias, 'link_start' => $linkStart, 'link_end' => $linkEnd]
					);
					$box['tabs']['meta_data']['notices']['alias_cannot_also_be_spare_alias']['show'] = true;
					$fields['meta_data/alias']['error'] = true;
				
				} else {
					$sql = "
						SELECT 1
						FROM ". DB_PREFIX. "content_items
						WHERE `alias`= '". ze\escape::sql($alias). "'
						  AND (id, `type`) NOT IN ((". (int) $box['key']['cID']. ", '". ze\escape::sql($box['key']['cType']). "'))
						LIMIT 1";
					
					if (ze\sql::numRows($sql)) {
						$box['tabs']['meta_data']['errors'][] = ze\admin::phrase('"[[alias]]" already exists as an alias.', ['alias' => $alias]);
					
					} else {
						$sql = "
							SELECT content_id,content_type,alias
							FROM ". DB_PREFIX. "spare_aliases
							WHERE `alias`= '". ze\escape::sql($alias). "'
							  AND (content_id, content_type) NOT IN ((". (int) $box['key']['cID']. ", '". ze\escape::sql($box['key']['cType']). "'))
							LIMIT 1";
						
						$box['tabs']['meta_data']['notices']['spare_alias_already_exists']['show'] = false;
						if (ze\sql::numRows($sql)) {
							$aliasResult = ze\sql::fetchAssoc($sql);
							$aliasTag = ze\content::formatTag($aliasResult['content_id'], $aliasResult['content_type']);
							$linkSpare = ' <a href = "'.ze\link::absolute().'zenario/admin/organizer.php#zenario__administration/panels/zenario_settings_pro_features__spare_aliases" target= "_blank"> View this spare alias. </a>';
							
							$box['tabs']['meta_data']['notices']['spare_alias_already_exists']['show'] = true;
							$box['tabs']['meta_data']['notices']['spare_alias_already_exists']['message'] = ze\admin::phrase('\'[[alias]]\' already exists as a spare alias. It points to \'[[aliasTag]]\'. [[linkSpare]]', ['alias' => $alias,'aliasTag' => $aliasTag,'linkSpare' => $linkSpare]);
							
							$fields['meta_data/spare_aliases']['error'] = true;
						}
					}
				}
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Check permissions
		if (ze\priv::check('_PRIV_MANAGE_SPARE_ALIAS')) {
			
			$spareAliases = [];
			$key = [
				'target_loc' => 'int',
				'content_id' => $box['key']['cID'],
				'content_type' => $box['key']['cType'],
				'ext_url' => ''
			];
			
			//Create any new spare aliases that have just been added
			foreach (ze\ray::explodeAndTrim($values['spare_aliases']) as $alias) {
				$row = $key;

				//Handle the case where a user has pasted a URL.
				//Get the last element after a slash, and also
				//remove the rewrite suffix if one is set.
				$aliasExploded = explode('/', $alias);
				$row['alias'] = array_pop($aliasExploded);

				if (ze::setting('mod_rewrite_enabled')) {
					$suffix = ze::setting('mod_rewrite_suffix');
					if ($suffix) {
						$row['alias'] = str_replace($suffix, '', $row['alias']);
					}
				}
				
				if (!ze\row::exists('spare_aliases', $row)) {
					$row['ext_url'] = '';
					$row['created_datetime'] = ze\date::now();
					ze\row::set('spare_aliases', $row, ['alias' => $row['alias']]);
				}
				
				$spareAliases[] = $row['alias'];
			}
			
			//Delete any spare aliases for this content item that were not in this list
			$key['alias'] = ['!' => $spareAliases];
			ze\row::delete('spare_aliases', $key);
		}
	}
}