<?php
/*
 * Copyright (c) 2018, Tribal Limited
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


class zenario_common_features__admin_boxes__trash extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$ids = ze\ray::explodeAndTrim($box['key']['id']);
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
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			$sql = "
				SELECT
					pi.module_id,
					pi.name,
					m.class_name,
					m.display_name,
					ps.instance_id,
					ps.egg_id,
					pi.content_id,
					pi.content_type,
					pi.content_version,
					pi.slot_name,
					c.alias
				FROM ". DB_PREFIX. "plugin_settings AS ps
				INNER JOIN ". DB_PREFIX. "plugin_instances AS pi
				   ON pi.id = ps.instance_id
				INNER JOIN ". DB_PREFIX. "modules AS m
				   ON m.id = pi.module_id
				LEFT JOIN ". DB_PREFIX. "content_items AS c
				   ON pi.content_id = c.id AND pi.content_type = c.type
				WHERE foreign_key_to = 'content'
				  AND foreign_key_id = ".(int)$cID."
				  AND foreign_key_char = '".ze\escape::sql($cType)."'
				ORDER BY display_name, name DESC";
			$result = ze\sql::select($sql);
			
			if ((count($ids) > 1) && ze\sql::numRows($result)) {
				$message .= '<br/><p><b>'.ze\content::formatTag($cID, $cType).'</b></p><br/>';
			}
			
			$currentRow = [];
			$prevModuleId = false;
			$skLink = 'zenario/admin/organizer.php?fromCID='.(int)$cID.'&fromCType='.urlencode($cType);
			
			while ($row = ze\sql::fetchAssoc($result)) {
				if ($prevModuleId !== $row['module_id']) {
					if ($prevModuleId) {
						self::addToMessage($message, $plugabbleCount, $versionControlledCount, $currentRow, $linkToLibraryPlugin, $linkToVersionControlledPlugin);
					}
					$prevModuleId = $row['module_id'];
					$plugabbleCount = $versionControlledCount = 0;
					$linkToLibraryPlugin = $linkToVersionControlledPlugin = '';
					
					switch ($row['class_name']) {
						case 'zenario_plugin_nest':
							$pluginsLink = '#zenario__modules/panels/plugins/refiners/nests////';
							break;
							
						case 'zenario_slideshow':
							$pluginsLink = '#zenario__modules/panels/plugins/refiners/slideshows////';
							break;
							
						default:
							$pluginsLink = '#zenario__modules/panels/modules/item//'. $row['module_id']. '//';
					}
				}
				if ($row['content_id']) {
					if (!$linkToVersionControlledPlugin) {
						$linkToVersionControlledPlugin = '<a href="'.ze\link::toItem($row['content_id'], $row['content_type'], true, '', false, false, true).'" target="_blank">'.ze\content::formatTag($row['content_id'], $row['content_type']).'</a>';
					}
					$versionControlledCount++;
				} else {
					if (!$linkToLibraryPlugin) {
						$linkToLibraryPlugin = '<a href="'.$skLink.$pluginsLink.$row['instance_id'].'" target="_blank">'.$row['name'].'</a>';
					}
					$plugabbleCount++;
				}
				$currentRow = $row;
			}
			if ($prevModuleId) {
				self::addToMessage($message, $plugabbleCount, $versionControlledCount, $currentRow, $linkToLibraryPlugin, $linkToVersionControlledPlugin);
			}
		}
		if ($message) {
			$fields['trash/trash_options']['hidden'] = $box['max_height'] = false;
		}
		$fields['trash/links_warning']['snippet']['html'] = $message;
	}
	
	public static function addToMessage(&$message, $plugabbleCount, $versionControlledCount, $row, $linkToLibraryPlugin, $linkToVersionControlledPlugin) {
		if ($plugabbleCount) {
			$message .= ze\admin::nPhrase(
				'<p>There is [[count]] "[[display_name]]" library plugin linking to this Content Item. "[[link]]".</p>',
				'<p>There are [[count]] "[[display_name]]" library plugins linking to this Content Item. "[[link]]" and [[count2]] other[[s]].</p>', 
				$plugabbleCount,
				[
					'count' => $plugabbleCount, 
					'count2' => $plugabbleCount - 1, 
					'display_name' => $row['display_name'], 
					'link' => $linkToLibraryPlugin,
					's' => ($plugabbleCount - 1) == 1 ? '' : 's']);
		}
		if ($versionControlledCount) {
			$message .= ze\admin::nPhrase(
				'<p>There is [[count]] "[[display_name]]" version controlled plugin linking to this Content Item. "[[link]]".</p>',
				'<p>There are [[count]] "[[display_name]]" version controlled plugins linking to this Content Item. "[[link]]" plus [[count2]] other plugin[[s]].</p>',
				$versionControlledCount,
				[
					'count' => $versionControlledCount, 
					'count2' => $versionControlledCount - 1,
					'display_name' => $row['display_name'], 
					'link' => $linkToVersionControlledPlugin,
					's' => ($versionControlledCount - 1) == 1 ? '' : 's']);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$ids = ze\ray::explodeAndTrim($box['key']['id']);
		foreach ($ids as $tagId) {
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			if (ze\contentAdm::allowTrash($cID, $cType) && ze\priv::check('_PRIV_HIDE_CONTENT_ITEM', $cID, $cType)) {
				ze\contentAdm::trashContent($cID, $cType, false, $values['trash/trash_options']);
			}
		}
	}
}