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
	case 'zenario_publish':
		// Make sure chosen time is not in the past
		if ($values['publish/publish_options'] == 'schedule') {
			if (!empty($values['publish/publish_date'])) {
				$now = strtotime('now');
				$scheduledDate = strtotime($values['publish/publish_date'].' '. $values['publish/publish_hours'].':'.$values['publish/publish_mins']);
				if ($now < $scheduledDate) {
					break;
				} else {
					$box['tabs']['publish']['errors'][] = 'The scheduled publishing time cannot be in the past.';
				}
			} else {
				$box['tabs']['publish']['errors'][] = 'Please enter a date.';
			}
			
		}
		
		break;
	
	case 'plugin_settings':
	case 'plugin_css_and_framework':
		return require funIncPath(__FILE__, 'plugin_settings.validateAdminBox');
	
	
	case 'zenario_reusable_plugin':
		if (engToBooleanArray($box['tabs']['instance'], 'edit_mode', 'on') && checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			if ($values['instance/name']) {
				//Check to see if an instance of that name already exists
				$sql = "
					SELECT 1
					FROM ". DB_NAME_PREFIX. "plugin_instances
					WHERE name =  '". sqlEscape($values['instance/name']). "'";
				
				if (!$box['key']['duplicate']) {
					$sql .= "
					  AND id != ". (int) $box['key']['id'];
				}
				
				$result = sqlQuery($sql);
				if (sqlNumRows($result)) {
					$box['tabs']['instance']['errors'][] = adminPhrase('A plugin with the name "[[name]]" already exists. Please choose a different name.', array('name' => $values['instance/name']));
				}
			}
		}
		
		break;
	
	
	case 'zenario_alias':
		if (!empty($values['meta_data/alias'])) {
			if (is_array($errors = validateAlias($values['meta_data/alias'], $box['key']['cID'], $box['key']['cType']))) {
				$box['tabs']['meta_data']['errors'] = array_merge($box['tabs']['meta_data']['errors'], $errors);
			}
		}
		
		break;
	
	
	case 'zenario_menu':
		return require funIncPath(__FILE__, 'menu_node.validateAdminBox');
	
	
	case 'zenario_setup_language':
		return require funIncPath(__FILE__, 'setup_language.validateAdminBox');
	
	
	case 'zenario_enable_site':
		if ($values['site/enable_site']) {
			if (checkIfDBUpdatesAreNeeded($andDoUpdates = false)) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('You must apply database updates before you can enable your site.');
			}
			
			if (!checkRowExists('languages', array())) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('You must enable a Language before you can enable your site.');
			} else {
				$tags = '';
				
				$result = getRows(
					'special_pages',
					array('equiv_id', 'content_type'),
					array('logic' => array('create_and_maintain_in_default_language','create_and_maintain_in_all_languages')),
					array('page_type'));
				
				while ($row = sqlFetchAssoc($result)) {
					if (!getRow('content', 'visitor_version', array('id' => $row['equiv_id'], 'type' => $row['content_type']))) {
						$tags .= ($tags? ', ' : ''). '"'. formatTag($row['equiv_id'], $row['content_type']). '"';
					}
				}
				
				if ($tags) {
					$box['tabs']['site']['errors'][] =
						adminPhrase('You must publish every Special Page needed by the CMS before you can enable your site. Please publish the following pages: [[tags]].', array('tags' => $tags));
				}
			}
		
		} else {
			if (!$values['site/site_disabled_title']) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('Please enter a browser title.');
			}
			if (!$values['site/site_disabled_message']) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('Please enter a message.');
			}
		}
		
		break;
	
	
	case 'zenario_delete_language':
		exitIfNotCheckPriv('_PRIV_MANAGE_LANGUAGE_CONFIG');
		$details = array();
		
		if (!allowDeleteLanguage($box['key']['id'])) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('You cannot delete the default language of your site.');
		}
		
		if (!$values['site/password']) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('Please enter your password.');
		
		} elseif (!checkPasswordAdmin(session('admin_username'), $details, $values['site/password'])) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('Your password was not recognised. Please check and try again.');
		}
		
		break;
	
	
	case 'zenario_site_reset':
		exitIfNotCheckPriv('_PRIV_RESET_SITE');
		$details = array();
		
		if (!$values['site/password']) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('Please enter your password.');
		
		} elseif (!checkPasswordAdmin(session('admin_username'), $details, $values['site/password'])) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('Your password was not recognised. Please check and try again.');
		}
		
		break;
	
	
	case 'zenario_content':
	case 'zenario_quick_create':
		return require funIncPath(__FILE__, 'content.validateAdminBox');
	
	
	case 'zenario_content_layout':
		
		$box['confirm']['message'] = '';
		
		if (empty($values['layout_id'])) {
			$box['tabs']['layout']['errors'][] = adminPhrase('Please select a layout.');
		
		} else {
			//Are we saving one or multiple items..?
			$cID = $cType = false;
			if (getCIDAndCTypeFromTagId($cID, $cType, $box['key']['id'])) {
				//Just one item in the id
				$cVersion = getLatestVersion($cID, $cType);
				
				//If changing the layout of one content item, warn the administrator if plugins
				//will be moved/lost, but still allow them to do the change.
				$this->validateChangeSingleLayout($box, $cID, $cType, $cVersion, $values['layout/layout_id'], $saving);
				
			} else {
				//Multiple comma-seperated items
				$mrg = array(
					'draft' => 0,
					'hidden' => 0,
					'published' => 0,
					'trashed' => 0);
				
				$tagIds = explode(',', $box['key']['id']);
				foreach ($tagIds as $tagId) {
					if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				
						//If changing the layout of multiple content items, don't warn the administrator if plugins
						//will be moved, but don't allow them to do the change if plugins will be lost.
						$warnings = changeContentItemLayout(
							$cID, $cType, getLatestVersion($cID, $cType), $values['layout/layout_id'],
							$check = true, $warnOnChanges = false
						);
						
						if ($warnings) {
							$box['tabs']['layout']['errors'][] = adminPhrase('Your new layout lacks one or more Banners, Content Summary Lists, Raw HTML Snippets or WYSIWYG editors from the content items\' current layout.');
							return;
						}
						
						if ($status = getContentStatus($cID, $cType)) {
					
							if ($status == 'hidden') {
								++$mrg['hidden'];
					
							} elseif ($status == 'trashed') {
								++$mrg['trashed'];
					
							} elseif (isDraft($status)) {
								++$mrg['draft'];
					
							} else {
								++$mrg['published'];
							}
					
						}
					}
				}
				
				
				$box['confirm']['button_message'] = adminPhrase('Save');
				if ($mrg['published'] || $mrg['hidden'] || $mrg['trashed']) {
					$box['confirm']['button_message'] = adminPhrase('Make new Drafts');
					$box['confirm']['message'] .= '<p>'. adminPhrase('This will create a new Draft for:'). '</p>';
			
					if ($mrg['published']) {
						$box['confirm']['message'] .= '<p> &nbsp; &bull; '. adminPhrase('[[published]] Published Content Item(s)', $mrg). '</p>';
					}
			
					if ($mrg['hidden']) {
						$box['confirm']['message'] .= '<p> &nbsp; &bull; '. adminPhrase('[[hidden]] Hidden Content Item(s)', $mrg). '</p>';
					}
			
					if ($mrg['trashed']) {
						$box['confirm']['message'] .= '<p> &nbsp; &bull; '. adminPhrase('[[trashed]] Archived Content Item(s)', $mrg). '</p>';
					}
			
					if ($mrg['draft']) {
						$box['confirm']['message'] .= '<p>'. adminPhrase('and will update [[draft]] Draft Content Item(s).', $mrg);
					}
				} else {
					$box['confirm']['message'] .= '<p>'. adminPhrase('This will update [[draft]] Draft Content Item(s).', $mrg);
				}
				
				//print_r($box['confirm']);
			}
		}
		
		break;
	
	
	case 'zenario_admin':
		return require funIncPath(__FILE__, 'admin.validateAdminBox');
	
	
	case 'zenario_export_vlp':
		if ($values['export/format'] == 'xlsx'
		 && !extension_loaded('zip')) {
			$box['tabs']['export']['errors'][] =
				adminPhrase('Importing or exporting .xlsx files requires the php_zip extension. Please ask your server administrator to enable it.');
		}
		
		break;
	
	
	case 'zenario_create_vlp':
		if (!$values['details/language_id']) {
			$box['tabs']['details']['errors'][] = adminPhrase('Please enter a Language Code.');
		
		} elseif ($values['details/language_id'] != preg_replace('/[^a-z0-9_-]/', '', $values['details/language_id'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('The Language Code can only contain lower-case letters, numbers, underscores or hyphens.');
		
		} elseif (checkIfLanguageCanBeAdded($values['details/language_id'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('The Language Code [[id]] already exists', array('id' => $values['details/language_id']));
		}
		
		break;
	
	case 'zenario_document_folder':
		
		if ($values['details/folder_name'] == "") {
			$box['tabs']['details']['errors'][] = adminPhrase('You must give the folder a name.');
		}
		break;
		
	case 'zenario_migrate_old_documents':
		if (!checkRowExists('documents', array('type' => 'folder', 'id' => $values['details/folder']))) {
			$box['tabs']['details']['errors'][] = adminPhrase('You must select a folder for the documents.');
		}
		break;
	
	case 'zenario_document_move':
		
		if (!$values['details/move_to'] && !$values['details/move_to_root']) {
			$box['tabs']['details']['errors'][] = adminPhrase('You must select a target folder.');
		}
		$ids = explode(',', $box['key']['id']);
		if (in_array($values['details/move_to'], $ids)) {
			$box['tabs']['details']['errors'][] = adminPhrase('You can not move a folder inside itself.');
		}
		break;
	
	case 'zenario_file_type':
		if (preg_replace('/[a-zA-Z0-9_\\.-]/', '', $values['details/type'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('The Extension must not contain any special characters.');
		
		} elseif (checkRowExists('document_types', array('type' => $values['details/type']))) {
			$box['tabs']['details']['errors'][] = adminPhrase('This extension is already registered in the CMS.');
		}
		
		break;
	
	
	case 'zenario_image':
		if (!$values['details/filename'] || !guessAltTagFromFilename($values['details/filename'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('Please enter a filename.');
		
		} elseif (documentMimeType($values['details/filename']) != getRow('files', 'mime_type', $box['key']['id'])) {
			$box['tabs']['details']['errors'][] = adminPhrase("You must not change the file's extension.");
		}
		
		break;
	
	
	case 'zenario_content_type_details':
		if (!$values['details/default_layout_id'] || !($template = getTemplateDetails($values['details/default_layout_id']))) {
			$box['tabs']['details']['errors'][] = adminPhrase('Please select a default layout.');
		
		} elseif ($template['status'] != 'active') {
			$box['tabs']['details']['errors'][] = adminPhrase('The default layout must be an active layout.');
		}
		
		break;
	
	
	case 'site_settings':
		return require funIncPath(__FILE__, 'site_settings.validateAdminBox');
	
}

return false;