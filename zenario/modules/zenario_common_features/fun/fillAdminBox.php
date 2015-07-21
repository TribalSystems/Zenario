<?php
/*
 * Copyright (c) 2015, Tribal Limited
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
		
		
		//If every language has a specific domain name, there's no point in showing the
		//lang_code_in_url field as it will never be used.
		$langSpecificDomainsUsed = checkRowExists('languages', array('domain' => array('!' => '')));
		$langSpecificDomainsNotUsed = checkRowExists('languages', array('domain' => ''));
		
		if ($langSpecificDomainsUsed && !$langSpecificDomainsNotUsed) {
			$box['tabs']['meta_data']['fields']['lang_code_in_url']['hidden'] =
			$box['tabs']['meta_data']['fields']['lang_code_in_url_dummy']['hidden'] = true;
		}
		
		
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
			
			$box['tabs']['layout']['fields']['layout_id']['pick_items']['path'] = 'zenario__content/panels/content_types/hidden_nav/layouts//'. $box['key']['cType']. '//';
			
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
					array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/panels/categories'). '" target="_blank"'));
			
			
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
					array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/panels/categories'). '" target="_blank"'));
			
			
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
			
			$documentName=getRow('documents', 'filename', array('type' => 'file','id' => $document_id));
			//$documentName = getRow('files', array('filename'), $documentDetails['file_id']);
			$box['title'] = adminPhrase('Editing metadata for document "[[filename]]".', array("filename"=>$documentName));
			
			
			$fields['details/document_name']['value'] = $documentName;
			$fileDatetime=getRow('documents', 'file_datetime', array('type' => 'file','id' => $document_id));
			$fields['details/date_uploaded']['value'] = date('jS F Y H:i', strtotime($fileDatetime));
			
			foreach ($documentTags as $tag) {
				$documentTagsString .= $tag . ",";
			}
			
			$fields['details/tags']['value'] = $documentTagsString;
			$fields['details/link_to_add_tags']['snippet']['html'] = 
					adminPhrase('To add or edit document tags click <a[[link]]>this link</a>.',
						array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/panels/document_tags'). '" target="_blank"'));
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
		
	case 'zenario_migrate_old_documents':
		// Only show button if ctype_document module is running
		if (getModuleStatusByClassName('zenario_ctype_document') == 'module_running') {
			if ($box['key']['id']) {
				
				$fields['details/html']['snippet']['html'] = adminPhrase('For each document meta data field below, select a dataset field to migrate the data to. If no dataset field is chosen then the data won\'t be saved. (<a [[link]]>Edit dataset fields</a>)', array('link' => 'href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__administration/panels/custom_datasets'). '" target="_blank"'));
				
				// Set select lists for dataset fields
				$link = '';
				$datasetDetails = getDatasetDetails('documents');
				if ($details = getDatasetDetails('documents')) {
					$link = absCMSDirURL(). 'zenario/admin/organizer.php?#zenario__administration/panels/custom_datasets/item//'.$details['id'].'//';
				}
				$textDocumentDatasetFields = 
					getRowsArray('custom_dataset_fields', 'label', array('type' => 'text', 'dataset_id' => $datasetDetails['id']));
				if (empty($textDocumentDatasetFields)) {
					$fields['details/title']['hidden'] = $fields['details/language_id']['hidden'] = true;
					$fields['details/title_warning']['hidden'] = $fields['details/language_id_warning']['hidden'] = false;
					$fields['details/title_warning']['snippet']['html'] = 
					$fields['details/language_id_warning']['snippet']['html'] = 
						'No "Text" type fields found in the document dataset, go <a href="'.$link.'">here</a> to create one.';
				} else {
					$fields['details/title']['values'] = $fields['details/language_id']['values'] = $textDocumentDatasetFields;
				}
				$textAreaDocumentDatasetFields = 
					getRowsArray('custom_dataset_fields', 'label', array('type' => 'textarea', 'dataset_id' => $datasetDetails['id']));
				if (empty($textAreaDocumentDatasetFields)) {
					$fields['details/description']['hidden'] = $fields['details/keywords']['hidden'] = true;
					$fields['details/description_warning']['hidden'] = $fields['details/keywords_warning']['hidden'] = false;
					$fields['details/description_warning']['snippet']['html'] = 
					$fields['details/keywords_warning']['snippet']['html'] = 
						'No "Textarea" type fields found in the document dataset, go <a href="'.$link.'" target="_blank">here</a> to create one.';
				} else {
					$fields['details/description']['values'] = $fields['details/keywords']['values'] = $textAreaDocumentDatasetFields;
				}
				$editorDocumentDatasetFields = 
					getRowsArray('custom_dataset_fields', 'label', array('type' => 'editor', 'dataset_id' => $datasetDetails['id']));
				if (empty($editorDocumentDatasetFields)) {
					$fields['details/content_summary']['hidden'] = true;
					$fields['details/content_summary_warning']['hidden'] = false;
					$fields['details/content_summary_warning']['snippet']['html'] = 'No "Editor" type fields found in the document dataset, go <a href="'.$link.'">here</a> to create one.';
				} else {
					$fields['details/content_summary']['values'] = $editorDocumentDatasetFields;
				}
			}
		}
		break;
	
	
	case 'zenario_content_type_details':
		$box['tabs']['details']['fields']['default_layout_id']['pick_items']['path'] =
			'zenario__content/panels/content_types/hidden_nav/layouts//'. $box['key']['id']. '//';
		
		foreach (getContentTypeDetails($box['key']['id']) as $col => $value) {
			if ($col == 'enable_categories') {
				$box['tabs']['details']['fields'][$col]['value'] = $value ? 'enabled' : 'disabled';
			} else {
				$box['tabs']['details']['fields'][$col]['value'] = $value;
			}
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
		
	case 'zenario_reorder_documents':
		if ($box['key']['id']){
			$folderId = $box['key']['id'];
			$folderName = getRow('documents', 'folder_name', array('id' => $folderId));
			//$box['title'] = adminPhrase('Renaming/adding a title to the image "[[folder_name]]".', $folderName);
			$box['title'] = "Re-order documents for the folder: '".$folderName."'";
		}else{
			$box['title'] = "Re-order documents";
		}
	
		$datasetDetails = getDatasetDetails('documents');
		$datasetId = $datasetDetails['id'];
		if ($datasetDetails = getRowsArray('custom_dataset_fields', true, array('dataset_id' => $datasetId, 'type' => array('!' => 'other_system_field')))) {
			foreach ($datasetDetails as $details) {
				if($details['type'] == 'text' || $details['type'] == 'date') {
					$datesetFields[]= $details;
				}
				
			}
			$i = 3;
			foreach ($datesetFields as $dataset){
				$box['tabs']['details']['fields']['reorder']['values'][$dataset['id']] = 
					array('label' => $dataset['label'] . " - (custom dataset field)", 'ord' => $i);
				$i++;
			}
		}
		
	break;
	
	case 'zenario_document_upload':
	
		$folderDetails= getRow('documents', array('id','folder_name'), array('id' => $box['key']['id'],'type'=>'folder'));
		if ($folderDetails) {
			$box['title'] = 'Uploading document for the folder "'.$folderDetails['folder_name'].'"';
			$documentProperties['folder_id'] = $box['key']['id'];
		}
		break;
	
	case 'zenario_document_rename':
			$documentId = $box['key']['id'];
			
			$isfolder=getRow('documents', 'type', array('type' => 'folder','id' => $documentId));
			
			if ($isfolder){
				$documentName=getRow('documents', 'folder_name', array('type' => 'folder','id' => $documentId));
				$box['title'] = 'Renaming the folder "'.$documentName.'"';
			}else{
				$documentName=getRow('documents', 'filename', array('type' => 'file','id' => $documentId));
				$box['title'] = 'Renaming the file "'.$documentName.'"';
			}
			$values['details/document_name'] = $documentName;
		break;
	
	
}

return false;
