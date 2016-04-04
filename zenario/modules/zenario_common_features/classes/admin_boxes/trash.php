<?php
/*
 * Copyright (c) 2016, Tribal Limited
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


class zenario_common_features__admin_boxes__trash extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$ids = explodeAndTrim($box['key']['id']);
		if (count($ids) > 1) {
			/*$fields['trash/desc']['snippet']['html'] = '
				<p>
					Trashing these content items will remove them from the site. They will no longer be visible to site visitors.
				</p><br/><p>
					You can recover a trashed item in Organizer, go to Content and then click on the trash can.
				</p><br/><p>
					Are you sure you wish to proceed?
				</p>';*/
			$box['tabs']['trash']['notices']['trash_items']['show'] = true;
		} else {
			$box['tabs']['trash']['notices']['trash_item']['show'] = true;
		}
		
		$message = '';
		foreach ($ids as $tagId) {
			$cID = $cType = false;
			getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			$sql = "
				SELECT
					pi.module_id,
					pi.name,
					m.class_name,
					m.display_name,
					ps.instance_id,
					ps.nest,
					pi.content_id,
					pi.content_type,
					pi.content_version,
					pi.slot_name,
					c.alias
				FROM ". DB_NAME_PREFIX. "plugin_settings AS ps
				INNER JOIN ". DB_NAME_PREFIX. "plugin_instances AS pi
				   ON pi.id = ps.instance_id
				INNER JOIN ". DB_NAME_PREFIX. "modules AS m
				   ON m.id = pi.module_id
				LEFT JOIN ". DB_NAME_PREFIX. "content_items AS c
				   ON pi.content_id = c.id AND pi.content_type = c.type
				WHERE foreign_key_to = 'content'
				  AND foreign_key_id = ".(int)$cID."
				  AND foreign_key_char = '".sqlEscape($cType)."'
				ORDER BY display_name, name DESC";
			$result = sqlSelect($sql);
			
			if ((count($ids) > 1) && sqlNumRows($result)) {
				$message .= '<br/><p><b>'.formatTag($cID, $cType).'</b></p><br/>';
			}
			
			$plugabbleCount = 0;
			$versionControlledCount = 0;
			$currentModule = false;
			$currentRow = array();
			$linkToLibraryPlugin = '';
			$linkToVersionControlledPlugin = '';
			
			$skLink = 'zenario/admin/organizer.php?fromCID='.(int)$cID.'&fromCType='.urlencode($cType);
			$pluginsLink = '#zenario__modules/panels/modules/item//';
			
			while ($row = sqlFetchAssoc($result)) {
				if (!$currentModule) {
					$currentModule = $row['module_id'];
				} elseif ($currentModule !== $row['module_id']) {
					$currentModule = $row['module_id'];
					self::addToMessage($message, $plugabbleCount, $versionControlledCount, $currentRow, $linkToLibraryPlugin, $linkToVersionControlledPlugin);
					$plugabbleCount = $versionControlledCount = 0;
					$linkToLibraryPlugin = $linkToVersionControlledPlugin = '';
				}
				if ($row['content_id']) {
					if (!$linkToVersionControlledPlugin) {
						$linkToVersionControlledPlugin = '<a href="'.linkToItem($row['content_id'], $row['content_type'], true, '', false, false, true).'" target="_blank">'.formatTag($row['content_id'], $row['content_type']).'</a>';
					}
					$versionControlledCount++;
				} else {
					if (!$linkToLibraryPlugin) {
						$linkToLibraryPlugin = '<a href="'.$skLink.$pluginsLink.$row['module_id'].'//'.$row['instance_id'].'" target="_blank">'.$row['name'].'</a>';
					}
					$plugabbleCount++;
				}
				$currentRow = $row;
			}
			self::addToMessage($message, $plugabbleCount, $versionControlledCount, $currentRow, $linkToLibraryPlugin, $linkToVersionControlledPlugin);
		}
		if ($message) {
			$fields['trash/trash_options']['hidden'] = $box['max_height'] = false;
		}
		$fields['trash/links_warning']['snippet']['html'] = $message;
	}
	
	public static function addToMessage(&$message, $plugabbleCount, $versionControlledCount, $row, $linkToLibraryPlugin, $linkToVersionControlledPlugin) {
		if ($plugabbleCount) {
			$message .= nAdminPhrase(
				'<p>There is [[count]] "[[display_name]]" library plugin linking to this Content Item. "[[link]]".</p>',
				'<p>There are [[count]] "[[display_name]]" library plugins linking to this Content Item. "[[link]]" and [[count2]] other[[s]].</p>', 
				$plugabbleCount,
				array(
					'count' => $plugabbleCount, 
					'count2' => $plugabbleCount - 1, 
					'display_name' => $row['display_name'], 
					'link' => $linkToLibraryPlugin,
					's' => ($plugabbleCount - 1) == 1 ? '' : 's'));
		}
		if ($versionControlledCount) {
			$message .= nAdminPhrase(
				'<p>There is [[count]] "[[display_name]]" version controlled plugin linking to this Content Item. "[[link]]".</p>',
				'<p>There are [[count]] "[[display_name]]" version controlled plugins linking to this Content Item. "[[link]]" plus [[count2]] other plugin[[s]].</p>',
				$versionControlledCount,
				array(
					'count' => $versionControlledCount, 
					'count2' => $versionControlledCount - 1,
					'display_name' => $row['display_name'], 
					'link' => $linkToVersionControlledPlugin,
					's' => ($versionControlledCount - 1) == 1 ? '' : 's'));
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$ids = explodeAndTrim($box['key']['id']);
		foreach ($ids as $tagId) {
			$cID = $cType = false;
			getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			if (allowTrash($cID, $cType) && checkPriv('_PRIV_TRASH_CONTENT_ITEM', $cID, $cType)) {
				trashContent($cID, $cType, false, $values['trash/trash_options']);
			}
		}
	}
}