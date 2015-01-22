<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

switch ($path) {
	case 'plugin_settings':
	case 'plugin_css_and_framework':
		return require funIncPath(__FILE__, 'plugin_settings.fillAdminBox');
	
	
	case 'site_settings':
		return require funIncPath(__FILE__, 'site_settings.fillAdminBox');
	
	
	case 'advanced_search':
		if (!empty($box['tabs']['first_tab']['fields']['name']['value'])) {
			$box['title'] = adminPhrase('Editing/running the advanced search "[[value]]".', $box['tabs']['first_tab']['fields']['name']);
		}
		
		break;
		
	case 'zenario_publish':
		
		$status = getModuleStatusByClassName('zenario_scheduled_task_manager');
		if ($status != 'module_running') {
			$fields['publish/publish_options']['hidden'] = true;
		} else {
			$allJobsEnabled = setting('jobs_enabled');
			$scheduledPublishingEnabled = getRow('jobs', 'enabled', array('job_name' => 'jobPublishContent', 'module_class_name' => 'zenario_common_features'));
			if (!($allJobsEnabled && $scheduledPublishingEnabled)) {
				$scheduledTaskLink = absCMSDirURL() . 
					'zenario/admin/organizer.php#zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks';
				$fields['publish/publish_options']['values']['schedule']['disabled'] = true;
				$fields['publish/publish_options']['values']['schedule']['post_field_html'] = "
					<br />
					<br />
					Scheduled publishing is not available. The Scheduled Task Manager is installed 
					but the Scheduled Publishing task is not enabled. 
					<a href='".$scheduledTaskLink."'>Click for more info.</a>";
			} else {
				$values['publish/publish_date'] = date('Y-m-d');
			}
		}
		break;
	
	
	case 'zenario_reusable_plugin':
		if (!$instance = getPluginInstanceDetails($box['key']['id'])) {
			exit;
		}
		
		if ($box['key']['duplicate']) {
			$box['tabs']['instance']['edit_mode']['always_on'] = true;
			$box['title'] = adminPhrase('Duplicating the plugin "[[instance_name]]".', $instance);
		
		} else {
			$box['title'] = adminPhrase('Renaming the plugin "[[instance_name]]".', $instance);
		}
		
		$box['tabs']['instance']['fields']['name']['value'] = $instance['instance_name'];
		
		break;
	
	
	case 'zenario_setup_language':
		return require funIncPath(__FILE__, 'setup_language.fillAdminBox');
	
	
	case 'zenario_setup_module':
		return require funIncPath(__FILE__, 'setup_module.fillAdminBox');
	
	
	case 'zenario_alias':
		//Set up the primary key from the requests given
		if ($box['key']['id'] && !$box['key']['cID']) {
			getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);
		}
		
		//Load the alias
		$box['tabs']['meta_data']['fields']['alias']['value'] =
			contentItemAlias($box['key']['cID'], $box['key']['cType']);
		$box['tabs']['meta_data']['fields']['lang_code_in_url']['value'] =
			getRow('content', 'lang_code_in_url', array('id' => $box['key']['cID'], 'type' => $box['key']['cType']));
		
		$box['tabs']['meta_data']['fields']['update_translations']['value'] =
			setting('translations_different_aliases')? 'update_this' : 'update_all';
		
		$box['tabs']['meta_data']['fields']['lang_code_in_url']['values']['default']['label'] =
			setting('translations_hide_language_code')?
				$box['tabs']['meta_data']['fields']['lang_code_in_url']['values']['default']['label__hide']
			 :	$box['tabs']['meta_data']['fields']['lang_code_in_url']['values']['default']['label__show'];
		
		
		getLanguageSelectListOptions($box['tabs']['meta_data']['fields']['language_id']);
		$box['tabs']['meta_data']['fields']['language_id']['value'] = getContentLang($box['key']['cID'], $box['key']['cType']);
		
		$box['title'] =
			adminPhrase('Editing the alias for content item "[[tag]]"',
				array('tag' => formatTag($box['key']['cID'], $box['key']['cType'])));
		
		break;
	
	
	case 'zenario_enable_site':
		if (setting('site_enabled')) {
			$box['tabs']['site']['fields']['enable_site']['value'] = 1;
		} else {
			$box['tabs']['site']['fields']['disable_site']['value'] = 1;
		}
		$box['tabs']['site']['fields']['site_disabled_title']['value'] = setting('site_disabled_title');
		$box['tabs']['site']['fields']['site_disabled_message']['value'] = setting('site_disabled_message');
		
		break;
	
	
	case 'zenario_delete_language':
		exitIfNotCheckPriv('_PRIV_MANAGE_LANGUAGE_CONFIG');
		$box['tabs']['site']['fields']['username']['value'] = session('admin_username');
		$box['tabs']['site']['notices']['are_you_sure']['message'] =
			adminPhrase(
				'Are you sure that you wish to delete the Language "[[lang]]"? All Content Items and Menu Node text in this Language will also be deleted. THIS CANNOT BE UNDONE!',
				array('lang' => getLanguageName($box['key']['id'])));
		
		break;
	
	
	case 'zenario_site_reset':
		exitIfNotCheckPriv('_PRIV_RESET_SITE');
		$box['tabs']['site']['fields']['username']['value'] = session('admin_username');
		
		break;
	
	
	case 'zenario_menu':
		return require funIncPath(__FILE__, 'menu_node.fillAdminBox');
	
	
	case 'zenario_content':
	case 'zenario_quick_create':
		return require funIncPath(__FILE__, 'content.fillAdminBox');
	
	
	case 'zenario_content_layout':
		
		$tagSQL = "";
		$cID = $cType = false;
		$canEdit = true;
		
		if (request('cID') && request('cType')) {
			$total = 1;
			$cID = $box['key']['cID'] = request('cID');
			$cType = $box['key']['cType'] = request('cType');
			$tagSQL = "'". sqlEscape($box['key']['id'] = $cType. '_'. $cID). "'";
			$canEdit = checkPriv('_PRIV_EDIT_DRAFT', $cID, $cType);
		
		} else {
			$tagIds = explode(',', $box['key']['id']);
			
			foreach ($tagIds as $tagId) {
				if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
					
					if (!checkPriv('_PRIV_EDIT_DRAFT', $cID, $cType)) {
						$canEdit = false;
					}
					
					$tagSQL .= ($tagSQL? ", " : ""). "'". sqlEscape($tagId). "'";
				}
			}
			
			if (!$tagSQL) {
				exit;
			}
			$total = count($tagIds);
			
			$box['key']['cType'] = false;
			foreach ($tagIds as $tagId) {
				if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
					if (!$box['key']['cType']) {
						$box['key']['cType'] = $cType;
					} elseif ($cType != $box['key']['cType']) {
						$box['key']['cType'] = false;
						break;
					}
				}
			}
		}
		
		if (!$canEdit) {
			$box['tabs']['cant_edit']['hidden'] = false;
		
		} elseif (!$box['key']['cType']) {
			$box['tabs']['mix_of_types']['hidden'] = false;
		
		} else {
			$box['tabs']['layout']['hidden'] = false;
			$box['tabs']['layout']['edit_mode']['enabled'] = true;
			
			$box['tabs']['layout']['fields']['layout_id']['pick_items']['path'] = 'zenario__content/nav/content_types/panel/hidden_nav/layouts//'. $box['key']['cType']. '//';
			
			//Run a SQL query to check how many distinct values this column has for each Content Item.
			//If there is only one unique value then populate it, otherwise show the field as blank.
			$sql = "
				SELECT DISTINCT v.layout_id
				FROM ". DB_NAME_PREFIX. "content AS c
				INNER JOIN ". DB_NAME_PREFIX. "versions AS v
				   ON c.id = v.id
				  AND c.type = v.type
				  AND c.admin_version = v.version
				WHERE c.tag_id IN (". $tagSQL. ")
				LIMIT 2";
			$result = sqlQuery($sql);
			
			if (($row1 = sqlFetchRow($result)) && !($row2 = sqlFetchRow($result))) {
				$fields['layout_id']['value'] = $row1[0];
			}
		}
		
		if ($total > 1) {
			$box['title'] =
				adminPhrase('Changing the layout of [[count]] content items',
					array('count' => $total));
		} else {
			$box['title'] =
				adminPhrase('Changing the layout of the content item "[[tag]]"',
					array('tag' => formatTag($cID, $cType)));
		}
		
		break;
	
	
	case 'zenario_content_categories':
		
		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = array();
		$equivId = $cType = false;
		
		if (request('equivId') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('equivId');
		
		} elseif (request('cID') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('cID');
		}
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (explode(',', $box['key']['id']) as $tagId) {
			if (getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				$tagId = $cType. '_'. $equivId;
				if (!isset($tagIds[$tagId])) {
					$tagIds[$tagId] = $tagId;
					++$total;
				}
			}
		}
		
		if (empty($tagIds)) {
			exit;
		} else {
			$box['key']['id'] = implode(',', $tagIds);
		}
		
		
		setupCategoryCheckboxes($fields['categories/categories'], false);
		//foreach ($fields['categories/categories']['values'] as $checkbox){
		
		if (empty($fields['categories/categories']['values'])) {
			unset($box['tabs']['categories']['edit_mode']);
			$fields['categories/categories']['hidden'] = true;
		
		}	
		else {
			
			$fields['categories/no_categories']['hidden'] = true;
			$box['tabs']['categories']['fields']['desc']['snippet']['html'] = 
				adminPhrase('You can put content item(s) into one or more categories. (<a[[link]]>Define categories</a>.)',
					array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/nav/categories/panel'). '" target="_blank"'));
			
			
			$inCats = array();
			$sql = "
				SELECT l.category_id, COUNT(DISTINCT c.tag_id) AS cnt
				FROM ". DB_NAME_PREFIX. "content AS c
				INNER JOIN ". DB_NAME_PREFIX. "category_item_link AS l
				   ON c.equiv_id = l.equiv_id
				  AND c.type = l.content_type
				WHERE c.tag_id IN (". inEscape($tagIds). ")
				GROUP BY l.category_id";
			$result = sqlQuery($sql);
			while ($row = sqlFetchAssoc($result)) {
							
					
					
					if (isset($fields['categories/categories']['values'][$row['category_id']])) {
						$inCats[] = $row['category_id'];
					
							if($fields['categories/categories']['values'][$row['category_id']]){
								$fields['categories/categories']['values'][$row['category_id']]['disabled'] = false;
									if ($total > 1) {
										$row['total'] = $total;
										if ($row['cnt'] == $total) {
											$fields['categories/categories']['values'][$row['category_id']]['label'] .=
											' '. adminPhrase('(all [[total]] in this category)', $row);
										} else {
											$fields['categories/categories']['values'][$row['category_id']]['label'] .=
											' '. adminPhrase('([[cnt]] of [[total]] in this category)', $row);
											}
									}
							}		
					}
					
							
						
			$values['categories/categories'] = inEscape($inCats, false);
			//$values['categories_add/categories_add'] = inEscape($inCats, false);
			//$values['categories_remove/categories_remove'] = inEscape($inCats, false);
		}
		}
		$numLanguages = getNumLanguages();
		if ($numLanguages > 1) {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items and their translations.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items and their translations',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]" and its translations',
						array('tag' => formatTag($equivId, $cType)));
			}
			
		} else {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]"',
						array('tag' => formatTag($equivId, $cType)));
			}
		}
		
		if ($total > 1) {
			$box['confirm']['message'] .=
				"\n\n".
				adminPhrase('The content items in all selected translation chains will be set to the categories you selected.');
		}
		
		break;
		
	case 'zenario_content_categories_add':
		
		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = array();
		$equivId = $cType = false;
		
		if (request('equivId') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('equivId');
		
		} elseif (request('cID') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('cID');
		}
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (explode(',', $box['key']['id']) as $tagId) {
			if (getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				$tagId = $cType. '_'. $equivId;
				if (!isset($tagIds[$tagId])) {
					$tagIds[$tagId] = $tagId;
					++$total;
				}
			}
		}
		
		if (empty($tagIds)) {
			exit;
		} else {
			$box['key']['id'] = implode(',', $tagIds);
		}
		
		
		
		//setupcategory boxes for adding categories
		setupCategoryCheckboxes($fields['categories_add/categories_add'], false);
		//setup category boxes for removing categories
		
		
		if (empty($fields['categories_add/categories_add']['values'])) {
			unset($box['tabs']['categories_add']['edit_mode']);
			$fields['categories_add/categories_add']['hidden'] = true;
		
		}	
		else {
			$fields['categories_add/no_categories']['hidden'] = true;
			
			$box['tabs']['categories_add']['fields']['desc']['snippet']['html'] = 
				adminPhrase('You can put content item(s) into one or more categories. (<a[[link]]>Define categories</a>.)',
					array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/nav/categories/panel'). '" target="_blank"'));
			
			
			$inCats = array();
			$sql = "
				SELECT l.category_id, COUNT(DISTINCT c.tag_id) AS cnt
				FROM ". DB_NAME_PREFIX. "content AS c
				INNER JOIN ". DB_NAME_PREFIX. "category_item_link AS l
				   ON c.equiv_id = l.equiv_id
				  AND c.type = l.content_type
				WHERE c.tag_id IN (". inEscape($tagIds). ")
				GROUP BY l.category_id";
			$result = sqlQuery($sql);
			while ($row = sqlFetchAssoc($result)) {
							
					if (isset($fields['categories_add/categories_add']['values'][$row['category_id']])) {
						$inCats[] = $row['category_id'];
					
							if($fields['categories_add/categories_add']['values'][$row['category_id']]){
								$fields['categories_add/categories_add']['values'][$row['category_id']]['disabled'] = false;
									if ($total > 1) {
										$row['total'] = $total;
										if ($row['cnt'] == $total) {
											$fields['categories_add/categories_add']['values'][$row['category_id']]['label'] .=
											' '. adminPhrase('(all [[total]] in this category)', $row);
										} else {
											$fields['categories_add/categories_add']['values'][$row['category_id']]['label'] .=
											' '. adminPhrase('([[cnt]] of [[total]] in this category)', $row);
											}
									}
							}		
					}
					
					
						
			//$values['categories/categories'] = inEscape($inCats, false);
			//$values['categories_add/categories_add'] = inEscape($inCats, false);
			//$values['categories_remove/categories_remove'] = inEscape($inCats, false);
		}
		}
		$numLanguages = getNumLanguages();
		if ($numLanguages > 1) {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items and their translations.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items and their translations',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]" and its translations',
						array('tag' => formatTag($equivId, $cType)));
			}
			
		} else {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]"',
						array('tag' => formatTag($equivId, $cType)));
			}
		}
		
		if ($total > 1) {
			$box['confirm']['message'] .=
				"\n\n".
				adminPhrase('The content items in all selected translation chains will be set to the categories you selected.');
		}
		
		break;
		
	case 'zenario_content_categories_remove':
		
		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = array();
		$equivId = $cType = false;
		
		if (request('equivId') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('equivId');
		
		} elseif (request('cID') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('cID');
		}
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (explode(',', $box['key']['id']) as $tagId) {
			if (getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				$tagId = $cType. '_'. $equivId;
				if (!isset($tagIds[$tagId])) {
					$tagIds[$tagId] = $tagId;
					++$total;
				}
			}
		}
		
		if (empty($tagIds)) {
			exit;
		} else {
			$box['key']['id'] = implode(',', $tagIds);
		}
		
		
		
		//setup category boxes for removing categories
		setupCategoryCheckboxes($fields['categories_remove/categories_remove'], false);
		
		//foreach ($fields['categories/categories']['values'] as $checkbox){
		
			$inCats = array();
			$sql = "
				SELECT l.category_id, COUNT(DISTINCT c.tag_id) AS cnt
				FROM ". DB_NAME_PREFIX. "content AS c
				INNER JOIN ". DB_NAME_PREFIX. "category_item_link AS l
				   ON c.equiv_id = l.equiv_id
				  AND c.type = l.content_type
				WHERE c.tag_id IN (". inEscape($tagIds). ")
				GROUP BY l.category_id";
			$result = sqlQuery($sql);
			while ($row = sqlFetchAssoc($result)) {
					
					if (isset($fields['categories_remove/categories_remove']['values'][$row['category_id']])) {
						$inCats[] = $row['category_id'];
								if ($total > 1) {
									$row['total'] = $total;
									if ($row['cnt'] == $total) {
										$fields['categories_remove/categories_remove']['values'][$row['category_id']]['label'] .=
										' '. adminPhrase('(all [[total]] in this category)', $row);
									} else {
										$fields['categories_remove/categories_remove']['values'][$row['category_id']]['label'] .=
										' '. adminPhrase('([[cnt]] of [[total]] in this category)', $row);
										}
								}
				}
			}
			
			foreach ($fields['categories_remove/categories_remove']['values'] as $key => $category) {
				if(!in_array($key, $inCats)) {
					$fields['categories_remove/categories_remove']['values'][$key]['hidden'] = true;
				}
			}
			
						
			//$values['categories/categories'] = inEscape($inCats, false);
			//$values['categories_add/categories_add'] = inEscape($inCats, false);
			//$values['categories_remove/categories_remove'] = inEscape($inCats, false);
		
		
		$numLanguages = getNumLanguages();
		if ($numLanguages > 1) {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items and their translations.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items and their translations',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]" and its translations',
						array('tag' => formatTag($equivId, $cType)));
			}
			
		} else {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]"',
						array('tag' => formatTag($equivId, $cType)));
			}
		}
		
		if ($total > 1) {
			$box['confirm']['message'] .=
				"\n\n".
				adminPhrase('The content items in all selected translation chains will be set to the categories you selected.');
		}
		
		break;
	case 'zenario_publish':
		if ($box['key']['cID']) {
			$count = 1;
			$box['key']['id'] = $box['key']['cType']. '_'. $box['key']['cID'];
		} else {
			$tags = explode(',', $box['key']['id']);
			$count = count($tags);
			
			if ($count == 1) {
				getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $tags[0]);
			}
		}
		
		if ($count == 1) {
			$box['tabs']['publish']['fields']['desc']['snippet']['html'] = 
				adminPhrase('Are you sure you wish to Publish the Content Item &quot;[[tag]]&quot;?', array('tag' => htmlspecialchars(formatTag($box['key']['cID'], $box['key']['cType']))));
		} else {
			$box['tabs']['publish']['fields']['desc']['snippet']['html'] = 
				adminPhrase('Are you sure you wish to Publish the [[count]] selected Content Items?', array('count' => $count));
		}
		
		break;
	
	
	case 'zenario_export_vlp':
		
		$phrases = array();
		$sql = "
			SELECT COUNT(*)
			FROM (
				SELECT DISTINCT code, module_class_name
				FROM ". DB_NAME_PREFIX. "visitor_phrases
			) AS c";
		$result = sqlQuery($sql);
		list($phrases['total']) = sqlFetchRow($result);
		
		$sql = "
			SELECT COUNT(*)
			FROM (
				SELECT DISTINCT code, module_class_name
				FROM ". DB_NAME_PREFIX. "visitor_phrases
				WHERE language_id = '". sqlEscape($box['key']['id']). "'
			) AS c";
		$result = sqlQuery($sql);
		list($phrases['present']) = sqlFetchRow($result);
		$phrases['missing'] = $phrases['total'] - $phrases['present'];
		
		
		$box['tabs']['export']['fields']['desc']['snippet']['html'] =
			adminPhrase(
				'This will export all of the Phrases in the Language Pack "[[lang]]".',
				array('lang' => getLanguageName($box['key']['id'])));
		
		$box['tabs']['export']['fields']['option']['values']['present'] =
			adminPhrase('Only existing Phrases ([[present]])', $phrases);
		
		if ($box['key']['id'] != setting('default_language')) {
			$phrases['def_lang'] = getLanguageName(setting('default_language'));
			
			$box['tabs']['export']['fields']['option']['values']['missing'] =
				adminPhrase('Only missing Phrases ([[missing]]), with [[def_lang]] as a reference', $phrases);
			$box['tabs']['export']['fields']['option']['values']['all'] =
				adminPhrase('All possible Phrases ([[total]]), with [[def_lang]] as a reference', $phrases);
		
		} else {
			$box['tabs']['export']['fields']['option']['values']['missing'] =
				adminPhrase('Only missing Phrases ([[missing]])', $phrases);
			$box['tabs']['export']['fields']['option']['values']['all'] =
				adminPhrase('All possible Phrases ([[total]])', $phrases);
		}
		
		break;
	
	
	case 'zenario_phrase':
		//Load details for this phrase. We could be:
		$details = false;
		
		//...editing an existing phrase (that has a numeric id)
		if ($box['key']['id'] && is_numeric($box['key']['id'])) {
			$details = getRow('visitor_phrases', array('code', 'module_class_name', 'language_id'), $box['key']['id']);
			
			$box['key']['code'] = $details['code'];
			$box['key']['language_id'] = $details['language_id'];
			$box['key']['module_class_name'] = $details['module_class_name'];
			if ($box['key']['is_code'] = substr($box['key']['code'], 0, 1) == '_') {
				$fields['phrase/code']['label'] = adminPhrase('Phrase code:');
			}
			
			$existingPhrases = array();
			$result = getRows('visitor_phrases', array('local_text', 'language_id', 'protect_flag'), array('code'=>$details['code'], 'module_class_name'=>$details['module_class_name']));
			while ($row = sqlFetchAssoc($result)) {
				$existingPhrases[$row['language_id']] = $row;
			}
			$languages = getLanguages(false, false, true, true);
			
			if (!$box['key']['is_code']) {
				$fields['phrase/code']['label'] = 'Phrase / '. $languages[setting('default_language')]['english_name'].':';
				$fields['phrase/code']['note_below'] = 
					adminPhrase('This code comes from a module or one of its plugins. In order to edit the text in '.$languages[setting('default_language')]['english_name'].'
								please go to the module '.$details['module_class_name'].', and inspect its plugins\' settings, their frameworks, 
								and possibly the module\'s program code.');
				unset($languages[setting('default_language')]);
			}
			
			$ord = 4;
			foreach ($languages as $language) {
				
				if (isset($existingPhrases[$language['id']])) {
					$phraseValue = $existingPhrases[$language['id']]['local_text'];
					$protectValue = $existingPhrases[$language['id']]['protect_flag'];
				} else {
					$phraseValue = '';
					$protectValue = '';
				}
				
				
				$box['tabs']['phrase']['fields'][$language['id']] =
					array(
						'class_name' => 'zenario_common_features',
						'ord' => $ord,
						'label' => $language['english_name']. ':',
						'type' => 'textarea',
						'rows' => '4',
						'note_below' => 
							"This is HTML text.
							Any special characters such as <code>&amp;</code> <code>&quot;</code> <code>&lt;</code> or <code>&gt;</code>
							should be escaped (i.e. by replacing them with <code>&amp;amp;</code> <code>&amp;quot;</code> <code>&amp;lt;</code>
							and <code>&amp;gt;</code> respectively).",
						'value' => $phraseValue
						);
				
				$box['tabs']['phrase']['fields']['protect_flag_edit_mode_'. $language['id']] =
					array(
						'class_name' => 'zenario_common_features',
						'ord' => $ord + 1,
						'label' => 'Protect',
						'type' => 'checkbox',
						'visible_if' => 'zenarioAB.editModeOn()',
						'value' => $protectValue,
						'note_below' =>
						"Protecting a Phrase will stop it from being overwritten when
						importing Phrases from a CSV file."
					);
				$ord += 2;
			}
			
		//...creating a translation of an existing phrase (with the id in request('refiner__translations')
		//and the language code in $box['key']['id']) (redundant?)
		} elseif ($box['key']['id'] && (int) request('refiner__translations')) {
			$details = getRow('visitor_phrases', true, request('refiner__translations'));
			
			$box['key']['code'] = $details['code'];
			$box['key']['language_id'] = $box['key']['id'];
			$box['key']['module_class_name'] = $details['module_class_name'];
			$box['key']['is_code'] = substr($box['key']['code'], 0, 1) == '_';
			$box['key']['id'] = false;
		
		//...or creating a brand new phrase code.
		} else {
			$box['key']['id'] = false;
			$box['key']['module_class_name'] = ifNull($box['key']['module_class_name'], request('moduleClass'));
			$box['key']['is_code'] = true;
			
			$languageName = getLanguageName(setting('default_language'), false);
			
			$box['tabs']['phrase']['fields'][setting('default_language')] =
				array(
					'class_name' => 'zenario_common_features',
					'label' => $languageName,
					'type' => 'textarea',
					'rows' => '4',
					'note_below' => "
						This is HTML text.
						Any special characters such as <code>&amp;</code> <code>&quot;</code> <code>&lt;</code> or <code>&gt;</code>
						should be escaped (i.e. by replacing them with <code>&amp;amp;</code> <code>&amp;quot;</code> <code>&amp;lt;</code>
						and <code>&amp;gt;</code> respectively).",
					'ord' => 10
				);
			$box['tabs']['phrase']['fields']['protect_flag_edit_mode'] = 
				array(
					'class_name' => 'zenario_common_features',
					'label' => 'Protect:',
					'type' => 'checkbox',
					'visible_if' => 'zenarioAB.editModeOn()',
					'note_below' =>
					"Protecting a Phrase will stop it from being overwritten when
					importing Phrases from a CSV file."
				);
		}
		
		//If this phrase isn't for the default language, mark it as a translation
		if ($box['key']['language_id'] != setting('default_language')) {
			$box['key']['translation'] = true;
		} else {
			$box['key']['language_id'] = setting('default_language');
		}
		$box['tabs']['phrase']['fields']['language_id']['value'] = $box['key']['language_id'];
		
		
		//Try to set the Module's name
		if ($box['key']['module_class_name']) {
			if ($box['tabs']['phrase']['fields']['module']['value'] = getModuleIdByClassName($box['key']['module_class_name'])) {
				$box['tabs']['phrase']['fields']['module']['read_only'] = true;
			} else {
				//If this is a phrase for a Module that doesn't exist any more, don't let it be edited
				unset($box['tabs']['phrase']['fields']['module']['pick_items']);
				$box['tabs']['phrase']['fields']['module']['type'] = 'text';
				$box['tabs']['phrase']['fields']['module']['value'] = $box['key']['module_class_name'];
				unset($box['tabs']['phrase']['edit_mode']);
			}
		
		//Any unclaimed phrases should be marked against the Common Features Module
		} else {
			$box['tabs']['phrase']['fields']['module']['value'] = getModuleIdByClassName('zenario_common_features');
		}
		
		//Only allow a code to be changed when creating a brand new phrase
		if ($box['key']['id'] || $box['key']['code']) {
			$box['tabs']['phrase']['fields']['code']['value'] = $box['key']['code'];
			$box['tabs']['phrase']['fields']['code']['read_only'] = true;
		}
		
		// If this a phrase code (e.g. _HELLO_WORLD) or a phrase (e.g. Hello World)
		if ($box['key']['is_code']) {
			//For phrase codes, show the "protected" checkbox and show the phrase code as a code
			if ($box['key']['code'] && $box['key']['translation']) {
				$box['title'] =
					adminPhrase('Localizing the Phrase Code "[[code]]" into the Language "[[language]]".',
						array('code' => $box['key']['code'], 'language' => getLanguageName($box['key']['language_id'])));
			
			} elseif ($box['key']['id']) {
				$box['title'] =
					adminPhrase('Modifying the Phrase "[[code]]".',
						array('code' => $box['key']['code']));
			
			} else {
				$box['title'] = adminPhrase('Creating a new Phrase Code');
			}
		
		// If this is a phrase (not a code)
		} else {
			$box['title'] = adminPhrase('Modifying a Phrase');
			
			foreach ($languages as $language) {
				$translate = getRow('languages', 'translate_phrases', array('id' => $language['id']));
				$box['tabs']['phrase']['fields'][$language['id']]['read_only'] = 
				$box['tabs']['phrase']['fields']['protect_flag_edit_mode_'. $language['id']]['read_only'] = 
					!(bool)$translate;
			}
		}
		
		break;
	
	case 'zenario_document_folder':
		if (isset($box['key']['add_folder']) && $box['key']['add_folder']) {
			$parentFolderDetails = 
				getRow(
					'documents',
					array('folder_name'), $box['key']['id']);
			$box['title'] = adminPhrase('Create a subfolder inside "[[folder_name]]".', $parentFolderDetails);
		} elseif ($folderDetails = getRow('documents', array('folder_name'), $box['key']['id'])) {
			$values['details/folder_name'] = $folderDetails['folder_name'];
			$box['title'] = adminPhrase('Editing folder "[[folder_name]]".', $folderDetails);
		}
		break;
		
	case 'zenario_document_tag':
		if ($tagDetails = getRow('document_tags', array('tag_name'), $box['key']['id'])) {
			$values['details/tag_name'] = $tagDetails['tag_name'];
			$box['title'] = adminPhrase('Editing tag "[[tag_name]]".', $tagDetails);
		}
		break;
	
	case 'zenario_document_properties':
		if ($document_id = $box['key']['id']) {
			$documentTagsString = '';
			$documentTags = getRowsArray('document_tag_link', 'tag_id', array('document_id' => $document_id));
			$documentDetails = getRow('documents',array('file_id', 'thumbnail_id', 'extract', 'extract_wordcount'),  $document_id);
			$documentName = getRow('files', array('filename'), $documentDetails['file_id']);
			$box['title'] = adminPhrase('Editing metadata for document "[[filename]]".', $documentName);
			foreach ($documentTags as $tag) {
				$documentTagsString .= $tag . ",";
			}
			
			$fields['details/tags']['value'] = $documentTagsString;
			$fields['details/link_to_add_tags']['snippet']['html'] = 
					adminPhrase('To add or edit document tags click <a[[link]]>this link</a>.',
						array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/nav/document_tags/panel'). '" target="_blank"'));
			$fields['extract/extract_wordcount']['value'] = $documentDetails['extract_wordcount'];
			$fields['extract/extract']['value'] = ($documentDetails['extract'] ? $documentDetails['extract']: 'No plain-text extract');
			
			// Add a preview image for JPEG/PNG/GIF images 
			if (!empty($documentDetails['thumbnail_id'])) {
				$this->getImageHtmlSnippet($documentDetails['thumbnail_id'], $fields['upload_image/thumbnail_image']['snippet']['html']);
			} else {
				$mimeType = getRow('files', 'mime_type', $documentDetails['file_id']);
				if ($mimeType == 'image/gif' || $mimeType == 'image/png' || $mimeType == 'image/jpeg' || $mimeType == 'image/pjpeg') {
					$this->getImageHtmlSnippet($documentDetails['file_id'], $fields['upload_image/thumbnail_image']['snippet']['html']);
				}
			}
			
		}
		break;
	
	
	case 'zenario_image':
		if (!$details = getRow('files', array('filename', 'width', 'height', 'size', 'alt_tag', 'title', 'floating_box_title'), $box['key']['id'])) {
			exit;
		}
		
		$box['title'] = adminPhrase('Renaming/adding a title to the image "[[filename]]".', $details);
		
		$this->getImageHtmlSnippet($box['key']['id'], $box['tabs']['details']['fields']['image']['snippet']['html']);
		
		$details['filesize'] = formatFilesizeNicely($details['size'], 1, true);
		
		$box['tabs']['details']['fields']['size']['snippet']['html'] = 
			adminPhrase('[[filesize]] [[[width]] × [[height]]]', $details);
		
		$box['tabs']['details']['fields']['filename']['value'] = $details['filename'];
		$box['tabs']['details']['fields']['alt_tag']['value'] = $details['alt_tag'];
		$box['tabs']['details']['fields']['title']['value'] = $details['title'];
		$box['tabs']['details']['fields']['floating_box_title']['value'] = $details['floating_box_title'];
		
		break;
	
	
	case 'zenario_content_type_details':
		$box['tabs']['details']['fields']['default_layout_id']['pick_items']['path'] =
			'zenario__content/nav/content_types/panel/hidden_nav/layouts//'. $box['key']['id']. '//';
		
		foreach (getContentTypeDetails($box['key']['id']) as $col => $value) {
			$box['tabs']['details']['fields'][$col]['value'] = $value;
		}
		
		switch ($box['key']['id']) {
			case 'html':
			case 'document':
			case 'picture':
			case 'video':
			case 'audio':
				//HTML, Document, Picture, Video and Audio fields cannot currently be mandatory
				foreach (array('description_field', 'keywords_field', 'summary_field', 'release_date_field') as $field) {
					$box['tabs']['details']['fields'][$field]['values']['mandatory']['hidden'] = true;
				}
				
				break;
				
			
			case 'event':
				//Event release dates must be hidden as it is overridden by another field
				$box['tabs']['details']['fields']['release_date_field']['hidden'] = true;
		}
		
		break;
	
	
}

return false;
