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


		
		//Get plugins linking to this content item.
		$message = '';
		$pluginsLinkingToThisContentItem = false;
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
			
			//If attempting to trash/delete multiple content items, show headings with the appropriate content item tags.
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
						$pluginsLinkingToThisContentItem = true;
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
				$pluginsLinkingToThisContentItem = true;
			}
			
			
			$equivId = ze\content::equivId($cID, $cType);
			
			if ($equivId && $equivId == $cID) {
				//Show content translations if any exist			
				$sql = "
					SELECT
						ci.id,
						ci.type,
						ci.equiv_id,
						ci.status,
						ci.language_id
					FROM ". DB_PREFIX. "content_items AS ci
					WHERE ci.equiv_id = " . (int) $equivId . "
					AND ci.type = '" . ze\escape::sql($cType) . "'";
			
				$result = ze\sql::select($sql);
		
				$numTranslations = ze\sql::numRows($result);
				$showParentAlias = true;
				if ($numTranslations) {
				
					while ($row = ze\sql::fetchAssoc($result)) {
						if (!in_array($row['type'] . '_' . $row['id'], $ids)) {
							++$totalRowNum;
					
							$suffix = '__' . $totalRowNum;
					
							if ($showParentAlias) {
								$values[$panelName . '/content_item' . $suffix] = ze\content::formatTag($cID, $cType);
							} else {
								$values[$panelName . '/content_item' . $suffix] = '';
							}
							$values[$panelName . '/translation' . $suffix] = ze\content::formatTag($row['id'], $row['type']);
							$values[$panelName . '/status' . $suffix] = ze\contentAdm::statusPhrase($row['status']);
							$values[$panelName . '/language_id' . $suffix] = $row['language_id'];
					
							$showParentAlias = false;
						}
					}
				
					if ($totalRowNum > 0) {
					
						//Show the translations table if any content item has translations.
						$fields[$panelName . '/th_content_item']['hidden'] = 
						$fields[$panelName . '/th_translation']['hidden'] = 
						$fields[$panelName . '/th_status']['hidden'] = 
						$fields[$panelName . '/th_action']['hidden'] = 
						$fields[$panelName . '/table_end']['hidden'] = false;
				
						$fields[$panelName . '/translations_warning']['snippet']['html'] = 
							ze\admin::nPhrase(
								'This content item has 1 translation. Please select what to do with the content item in the other language.',
								'This content item has [[count]] translations. Please select what to do with the content items in other languages.',
								$totalRowNum,
								['count' => $totalRowNum]
							);
						$fields[$panelName . '/translations_warning']['hidden'] = false;
						$box['max_height'] = false;
					}
				}
			}
		}
		
		$changes = [];
		ze\tuix::setupMultipleRows(
			$box, $fields, $values, $changes, $filling = true,
			$box['tabs'][$panelName]['custom_template_fields'],
			$totalRowNum,
			$minNumRows = 0,
			$tabName = $panelName
		);
		
		//Disable "Trash/Delete translation" option for content items which can't be trashed/deleted
		$startAt = 1;
		for ($n = $startAt; (($suffix = '__'. $n) && (!empty($fields[$panelName . '/translation'. $suffix]))); ++$n) {
			$tagId = $values[$panelName . '/translation'. $suffix];
			
			ze\content::removeFormattingFromTag($tagId);
			
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			
			switch ($panelName) {
				case 'trash':
					if (!ze\contentAdm::allowTrash($cID, $cType, false, false, $contentItemLanguageId = $values[$panelName . '/language_id'. $suffix])) {
						$fields[$panelName . '/action'. $suffix]['values']['trash']['disabled'] = true;
						$fields[$panelName . '/action'. $suffix]['values']['trash']['label'] = ze\admin::phrase('This translation cannot be trashed.');
						if (ze\contentAdm::allowDelete($cID, $cType, false, $contentItemLanguageId = $values[$panelName . '/language_id'. $suffix])) {
							$fields[$panelName . '/action'. $suffix]['values']['delete']['label'] = ze\admin::phrase('Delete draft translation');
						}
					}
					break;
				case 'delete_draft':
					if (!ze\contentAdm::allowDelete($cID, $cType, false, $contentItemLanguageId = $values[$panelName . '/language_id'. $suffix])) {
						$fields[$panelName . '/action'. $suffix]['values']['delete']['disabled'] = true;
						$fields[$panelName . '/action'. $suffix]['values']['delete']['label'] = ze\admin::phrase('This translation cannot be deleted.');
						if (ze\contentAdm::allowTrash($cID, $cType, false, false, $contentItemLanguageId = $values[$panelName . '/language_id'. $suffix])) {
							$fields[$panelName . '/action'. $suffix]['values']['trash']['label'] = ze\admin::phrase('Trash translation');
						}
					}
					break;
			}
			
		}
		
		if ($message) {
			if ($pluginsLinkingToThisContentItem) {
				switch ($panelName) {
					case 'trash':
						$fields[$panelName . '/trash_options']['hidden'] = false;
						break;
					case 'delete_draft':
						$fields[$panelName . '/delete_options']['hidden'] = false;
						break;
				}
			}
			
			$fields[$panelName . '/links_warning']['hidden'] = false;
			$fields[$panelName . '/links_warning']['snippet']['html'] = $message;
			$box['max_height'] = false;
		}
	