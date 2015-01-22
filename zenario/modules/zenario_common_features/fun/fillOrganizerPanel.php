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
	
	case 'zenario__menu/hidden_nav/sections/panel':
		if (get('refiner__language')) {
			$panel['title'] = adminPhrase('Menu sections (language [[lang]])', array('lang' => getLanguageName(get('refiner__language'))));
		}
		
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = array('empty' => !checkRowExists('menu_nodes', array('section_id' => $id)));
		}
		
		break;

	
	case 'zenario__menu/nav/default_language/panel/tree_explorer':
		$panel['html'] = '
			<iframe
				class="zenario_tree_explorer_iframe"
				style="width: 100%; height: 100%;"
				src="'. htmlspecialchars(
					absCMSDirURL(). 'zenario/admin/tree_explorer/index.php'.
						'?language='. urlencode(FOCUSED_LANGUAGE_ID__NO_QUOTES).
						'&type='. urlencode($refinerName).
						'&id='. urlencode($refinerId).
						'&sk=1'
			). '"></iframe>';
		
		break;
	case 'zenario__content/nav/hierarchical_files/panel':
		
		if (isset($panel['item_buttons']['autoset'])
		 && !checkRowExists('document_rules', array())) {
			$panel['item_buttons']['autoset']['disabled'] = true;
			$panel['item_buttons']['autoset']['disabled_tooltip'] = adminPhrase('No rules for auto-setting document metadata have been created');
		}
		
		foreach ($panel['items'] as &$item) {
			$filePath = "";
			$fileId = "";
			if ($item['type'] == 'folder') {
				$tempArray = array();
				$item['css_class'] = 'zenario_folder_item';
				$item['traits']['is_folder'] = true;
				$tempArray = getRowsArray('documents', 'id', array('folder_id' => $item['id']));
				$item['folder_file_count'] = count($tempArray);
			} else {
				$item['css_class'] = 'zenario_file_item';
				
				
				$sql = "
					SELECT
						file_id,
						extract_wordcount,
						SUBSTR(extract, 1, 40) as extract
					FROM ".  DB_NAME_PREFIX. "documents
					WHERE id = ". (int) $item['id'];
				
				$result = sqlSelect($sql);
				$documentDetails = sqlFetchAssoc($result);
				if (!empty($documentDetails['extract_wordcount'])) {
					$documentDetails['extract_wordcount'] .= ', ';
				}
				$item['plaintext_extract_details'] = 'Word count: '.$documentDetails['extract_wordcount'].$documentDetails['extract'];
				$fileId = $documentDetails['file_id'];
				if ($fileId) {
					$filePath = fileLink($fileId);
					$item['frontend_link'] = $filePath;
				}
				$filenameInfo = pathinfo($item['name']);
				if(isset($filenameInfo['extension'])) {
					$item['type'] = $filenameInfo['extension'];
				}
			}
			$item['tooltip'] = $item['name'];
			if (strlen($item['name']) > 30) {
				$item['name'] = substr($item['name'], 0, 10) . "..." .  substr($item['name'], -15);
			}
			if ($fileId && docstoreFilePath($fileId)) {
				$item['filesize'] = self::fileSizeConvert(filesize(docstoreFilePath($fileId)));
			}
		}
		
		break;
	
	
	case 'zenario__menu/panels/menu_position':
	
		foreach ($panel['items'] as $id => &$item) {
			
			
			if ($item['is_dummy_child']) {
				$item['css_class'] = 'zenario_menunode_unlinked ghost';
				$item['name'] = adminPhrase('[ Put menu node here ]');
				$item['target'] =
				$item['target_loc'] =
				$item['internal_target'] =
				$item['redundancy'] = '';
			
			} elseif ($item['menu_id']) {
				if ($item['target_loc'] == 'int' && $item['internal_target']) {
					if ($item['redundancy'] == 'primary') {
						$item['css_class'] = 'zenario_menunode_internal';
					} else {
						$item['css_class'] = 'zenario_menunode_internal_secondary';
					}

				} elseif ($item['target_loc'] == 'ext' && $item['target']) {
					$item['css_class'] = 'zenario_menunode_external';

				} else {
					$item['css_class'] = 'zenario_menunode_unlinked';
				}

				if (empty($item['parent_id'])) {
					$item['css_class'] .= ' zenario_menunode_toplevel';
				}
			
			} else {
				$item['css_class'] = 'menu_section';
			}
		}
		
		break;
	
	
	case 'zenario__menu/hidden_nav/menu_nodes/panel':
		return require funIncPath(__FILE__, 'menu_nodes.fillOrganizerPanel');

	
	case 'zenario__content/panels/slots':
		return require funIncPath(__FILE__, 'slots.fillOrganizerPanel');
	
	
	case 'zenario__layouts/nav/template_families/panel':

		foreach ($panel['items'] as $family => &$item) {
			$item['path'] = CMS_ROOT. zenarioTemplatePath($item['name']);
			
			if (is_dir($item['path'])) {
				$item['files'] = 0;
				foreach (scandir($item['path']) as $file) {
					if (substr($file, 0, 1) != '.' && substr($file, -8) == '.tpl.php' && is_file($item['path']. $file)) {
						++$item['files'];
					}
				}
			}
		}
		
		break;
	
	
	case 'zenario__layouts/nav/layouts/panel':
		require_once CMS_ROOT. 'zenario/admin/grid_maker/grid_maker.inc.php';
		
		$panel['key']['disableItemLayer'] = true;
		
		if ($refinerName == 'content_type') {
			$panel['title'] = adminPhrase('Layouts available for the "[[name]]" content type', array('name' => getContentTypeName($refinerId)));
			$panel['no_items_message'] = adminPhrase('There are no layouts available for the "[[name]]" content type', array('name' => getContentTypeName($refinerId)));
		
		} elseif (get('refiner__module_usage')) {
			$mrg = array(
				'name' => getModuleDisplayName(get('refiner__module_usage')));
			$panel['title'] = adminPhrase('Layouts on which the module "[[name]]" is used (layout layer)', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no layouts using the module "[[name]]".', $mrg);
		
		} elseif (get('refiner__plugin_instance_usage')) {
			$mrg = array(
				'name' => getPluginInstanceName(get('refiner__plugin_instance_usage')));
			$panel['title'] = adminPhrase('Layouts on which the plugin "[[name]]" is used (layout layer)', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no layouts using the plugin "[[name]]".', $mrg);
		
		}
		
		$panel['columns']['content_type']['values'] = array();
		foreach (getContentTypes() as $cType) {
			$panel['columns']['content_type']['values'][$cType['content_type_id']] = $cType['content_type_name_en'];
		}
		
		$foundPaths = array();
		$defaultLayouts = getRowsArray('content_types', 'default_layout_id', array());
		
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = array();
			
			//Numeric ids are Layouts
			if (is_numeric($id)) {
				//Add user images to each Layout, if they have an image
				if ($item['checksum']) {
					$item['traits']['has_image'] = true;
					$img = '&usage=template&c='. $item['checksum'];
					
					$item['image'] = 'zenario/file.php?sk=1'. $img;
					$item['list_image'] = 'zenario/file.php?skl=1'. $img;
				}
				
				if (!checkRowExists('content_types', array('default_layout_id' => $id)) && !checkRowExists('versions', array('layout_id' => $id))) {
					$item['traits']['deletable'] = true;
				
				}
				
				if ($item['status'] == 'suspended') {
					$item['traits']['archived'] = true;
				}
				
				$item['usage_status'] = $item['usage_count'];
			
			//Non-numeric ids are the Family and Filenames of Template Files that have no layouts created
			} else {
				$item['name'] = adminPhrase('[[Unregistered template file]]');
				$item['usage_status'] = $item['status'];
				$item['traits']['unregistered'] = true;
			}
			
			//For each Template file that's not missing, check its size and check the contents
			//to see if it has grid data saved inside it.
			//Multiple layouts could use the same file, so store the results of this to avoid
			//wasting time scanning the same file more than once.
			if (empty($item['missing']) && !isset($foundPaths[$item['path']])) {
				if ($fileContents = @file_get_contents($item['path'])) {
					$foundPaths[$item['path']] = array(
						'filesize' => strlen($fileContents),
						'checksum' => md5($fileContents),
						'grid' => zenario_grid_maker::readCode($fileContents, true, true)
					);
				} else {
					$foundPaths[$item['path']] = false;
				}
			}
			unset($fileContents);
			
			if (empty($item['missing']) && !empty($foundPaths[$item['path']])) {
				$item['filesize'] = $foundPaths[$item['path']]['filesize'];
				
				if ($foundPaths[$item['path']]['grid']) {
					$item['traits']['grid'] = true;
				}
			
			} else {
				$item['missing'] = 1;
				$item['usage_status'] = 'missing';
			}
			
			//If a Layout did not have an image set, and was a grid layout,
			//Try to automatically add a thumbnail
			if (empty($item['image'])
			 && !empty($foundPaths[$item['path']]['grid'])) {
				$item['image'] = 'zenario/admin/grid_maker/ajax.php?thumbnail=1&width=180&height=130&loadDataFromLayout='. $id. '&checksum='. $foundPaths[$item['path']]['checksum'];
				$item['list_image'] = 'zenario/admin/grid_maker/ajax.php?thumbnail=1&width=24&height=23&loadDataFromLayout='. $id. '&checksum='. $foundPaths[$item['path']]['checksum'];
			}
			
		}

		break;
	
	
	case 'zenario__layouts/hidden_nav/skins/panel':
		require_once CMS_ROOT. 'zenario/admin/grid_maker/grid_maker.inc.php';
		
		if (($refinerName == 'template_family' || $refinerName == 'template_family__panel_above')
		 && $templateFamily = decodeItemIdForStorekeeper(get('refiner__template_family'))) {
			$panel['title'] = adminPhrase('Skins in the template directory "[[family]]"', array('family' => $templateFamily));
			$panel['no_items_message'] = adminPhrase('There are no skins for this template directory.');
			unset($panel['columns']['family_name']['title']);
		
		} elseif ($refinerName == 'usable_in_template_family'
		 && $templateFamily = decodeItemIdForStorekeeper(get('refiner__usable_in_template_family'))) {
			$panel['title'] = adminPhrase('SKins in the template directory "[[family]]"', array('family' => $templateFamily));
			$panel['no_items_message'] = adminPhrase('There are no usable skin for this template directory.');
			unset($panel['columns']['family_name']['title']);
		}
		
		break;
	
	
	case 'zenario__layouts/hidden_nav/skins/panel/hidden_nav/skin_files/panel':
		
		if ($skin = getSkinFromId(get('refiner__skin'))) {
			
			$dir = getSkinPath($skin['family_name'], $skin['name']);
			$skin['subpath'] = '';
			
			if (($skin['subpath'] = get('refiner__subpath')) && ($skin['subpath'] = decodeItemIdForStorekeeper($skin['subpath'])) && (strpos($skin['subpath'], '..') === false)) {
				$panel['title'] = adminPhrase('Files for the skin "[[display_name]]" in the template directory "[[family_name]]" in the sub-directory "[[subpath]]"', $skin);
				$skin['subpath'] .= '/';
				$dir .= $skin['subpath'];
			
			} else {
				$skin['subpath'] = '';
				$panel['title'] = adminPhrase('Files for the skin "[[display_name]]" in the template directory "[[family_name]]"', $skin);
			}
			
			
			$panel['items'] = array();
			if (is_dir(CMS_ROOT. $dir)) {
				foreach (scandir(CMS_ROOT. $dir) as $file) {
					if (substr($file, 0, 1) != '.') {
						$item = array(
							'name' => $file,
							'href' => $dir. $file,
							'path' => CMS_ROOT. $dir. $file,
							'filesize' => filesize(CMS_ROOT. $dir. $file));
						
						if (is_file(CMS_ROOT. $dir. $file)) {
							if (substr($file, -4) == '.gif'
							  || substr($file, -4) == '.jpg'
							  || substr($file, -5) == '.jpeg'
							  || substr($file, -4) == '.png') {
								if ($item['filesize'] < 15000) {
									$item['list_image'] = $dir. $file;
								} else {
									$item['css_class'] = 'media_image';
								}
							}
						}
						
						if (is_dir(CMS_ROOT. $dir. $file)) {
							$item['traits']['subdir'] = true;
							$item['css_class'] = 'dropbox_files';
						} else {
							$item['link'] = false;
						}
						
						$panel['items'][encodeItemIdForStorekeeper($skin['subpath']. $file)] = $item;
					}
				}
			}
		}
		
		break;

	
	case 'zenario__content/nav/languages/panel':
		return require funIncPath(__FILE__, 'languages.fillOrganizerPanel');
	
	
	case 'zenario__content/nav/content_types/panel':
		foreach ($panel['items'] as $id => &$item) {
			$item['css_class'] = 'content_type_'. $item['content_type_id'];
			
			if ($item['not_enabled']) {
				$item['not_enabled'] = ' '. adminPhrase('(not enabled)');
			} else {
				$item['not_enabled'] = '';
			}
		}
		
		break;
	
	
	case 'zenario__content/nav/categories/panel':
		$langs = getLanguages();
		foreach($langs as $lang) {
			$panel['columns']['lang_'. $lang['id']] = array('title' => $lang['id']);
		}
		
		
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = array();
			
			if ($item['public']) {
				$item['traits']['public'] = true;
				
				foreach($langs as $lang) {
						$item['lang_'. $lang['id']] =
							getRow('visitor_phrases', 'local_text',
										array('language_id' => $lang['id'], 'code' => '_CATEGORY_'. (int) $id, 'module_class_name' => 'zenario_common_features'));
				}
				
				if ($item['landing_page_equiv_id'] && $item['landing_page_content_type']) {
					$item['landing_page'] = $item['landing_page_content_type']. '_'. $item['landing_page_equiv_id'];
					$item['frontend_link'] = linkToItem($item['landing_page_equiv_id'], $item['landing_page_content_type'], false, 'zenario_sk_return=navigation_path');
				}
			}
			
			$item['children'] = countCategoryChildren($id);
			$item['path'] = getCategoryPath($id);
		}
		
		
		if (get('refiner__parent_category')) {
			$mrg = array(
				'category' => getCategoryName(get('refiner__parent_category')));
			$panel['title'] = adminPhrase('Sub-categories of category "[[category]]"', $mrg);
			$panel['no_items_message'] = adminPhrase('Category "[[category]]" has no sub-categories.', $mrg);
		}
				
		break;
	
	
	case 'zenario__content/hidden_nav/sitemap/panel':
		foreach ($panel['items'] as &$item) {
			$item['loc'] = linkToItem($item['id'], $item['type'], true, '', $item['alias'], false, true);
			$item['lastmod'] = substr($item['lastmod'], 0, 10);
			$item['xml_tag_name'] = 'url';
			unset($item['id']);
			unset($item['type']);
			unset($item['alias']);
		}
		
		break;
	
	
	case 'zenario__content/nav/content/panel':
	case 'zenario__content/hidden_nav/chained/panel':
	case 'zenario__content/hidden_nav/language_equivs/panel':
		return require funIncPath(__FILE__, 'content.fillOrganizerPanel');
	
	
	
	
	case 'generic_image_panel':
	case 'zenario__content/hidden_nav/media/panel/hidden_nav/email_images_for_email_templates/panel':
	case 'zenario__content/hidden_nav/media/panel/hidden_nav/email_images_shared/panel':
	case 'zenario__content/hidden_nav/media/panel/hidden_nav/inline_images_for_content/panel':
	case 'zenario__content/hidden_nav/media/panel/hidden_nav/inline_images_for_reusable_plugins/panel':
	case 'zenario__content/hidden_nav/media/panel/hidden_nav/inline_images_shared/panel':
		foreach ($panel['items'] as $id => &$item) {
			
			$img = 'zenario/file.php?c='. $item['checksum'];
			
			if (!empty($panel['key']['usage']) && $panel['key']['usage'] != 'inline') {
				$img .= '&usage='. rawurlencode($panel['key']['usage']);
			}
			
			$item['image'] = $img. '&sk=1';
			$item['list_image'] = $img. '&skl=1';
			
			$item['row_css_class'] = '';
			if (!empty($item['sticky_flag'])) {
				$item['row_css_class'] .= ' zenario_sticky';
			}
			if (!empty($item['shared'])) {
				$item['row_css_class'] .= ' zenario_shared';
			}
			
			if (!($item['row_css_class'] = trim($item['row_css_class']))) {
				unset($item['row_css_class']);
			}
		}
		
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = array();
			
			if (!empty($item['shared'])) {
				$item['traits']['shared'] = true;
			}
			
			if ($refinerName == 'content') {
				if (!empty($item['sticky_flag'])) {
					$item['traits']['sticky'] = true;
					$item['traits']['used'] = true;
				}
			}
			
			foreach ($item as $colName => &$col) {
				if (($colName == 'in_use' || substr($colName, 0, 5) == 'usage') && !empty($col)) {
					$item['traits']['used'] = true;
					break;
				}
			}
		}
		
		break;
		
	
	case 'zenario__modules/nav/modules/panel':
		return require funIncPath(__FILE__, 'modules.fillOrganizerPanel');

	
	case 'zenario__modules/nav/instances/panel':
		
		if (get('refiner__plugin') && !isset($_GET['refiner__all_instances'])) {
			$panel['title'] =
			$panel['select_mode_title'] =
				adminPhrase('"[[name]]" plugins in the library', array('name' => getModuleDisplayName(get('refiner__plugin'))));
			$panel['no_items_message'] =
				adminPhrase('There are no "[[name]]" plugins in the library. Click the "Create" button to create one.', array('name' => getModuleDisplayName(get('refiner__plugin'))));
		}
		
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = array();
		
			if ($item['checksum']) {
				$img = '&c='. $item['checksum'];
				$item['traits']['has_image'] = true;
				$item['image'] = 'zenario/file.php?sk=1'. $img;
				$item['list_image'] = 'zenario/file.php?skl=1'. $img;
			} else {
				$item['image'] = getModuleIconURL($item['module_class_name']);
			}
			
			
			if ($item['usage_item'] || $item['usage_template']) {
				$item['traits']['in_use'] = true;
			} else {
				$item['traits']['unused'] = true;
			}

		}
		
		break;

	
	case 'zenario__modules/nav/modules/panel/hidden_nav/view_frameworks/panel':
		
		if ($refinerName == 'module' && ($module = getModuleDetails(get('refiner__module')))) {
			$panel['title'] =
				adminPhrase('Frameworks for the Module "[[name]]"', array('name' => $module['display_name']));
			
			$panel['items'] = array();
			foreach (listModuleFrameworks($module['class_name']) as $dir => $framework) {
				$panel['items'][encodeItemIdForStorekeeper($dir)] = $framework;
			}
		}
		
		break;

	
	case 'zenario__languages/nav/languages/panel':
		if ($mode != 'xml') {
			
			$enabledCount = 0;
			foreach ($panel['items'] as $id => &$item) {
				
				//If we're looking up a Language Name, we can't rely on the formatting that Storekeeper provides and must use the actual Language Name
				if ($mode == 'get_item_name') {
					$item['name'] = getLanguageName($id, $addIdInBracketsToEnd = true);
				
				} elseif (!$item['enabled']) {
					$item['traits'] = array('not_enabled' => true);
				
				} else {
					$item['traits'] = array('enabled' => true);
					++$enabledCount;
					
					if (allowDeleteLanguage($id)) {
						$item['traits']['can_delete'] = true;
					}
					
					$cID = $cType = false;
					if (langSpecialPage('zenario_home', $cID, $cType, $id, true)) {
						$item['frontend_link'] = linkToItem($cID, $cType, false, 'zenario_sk_return=navigation_path');
						$item['homepage_id'] = $cType. '_'. $cID;
						$item['traits']['has_homepage'] = true;
					}
				}
			}
			
			if ($enabledCount < 2) {
				unset($panel['collection_buttons']['default_language']);
			}
		}
		
		break;
		
	
	case 'zenario__languages/nav/vlp/panel':
		/*
		if ($mode != 'xml') {
			foreach ($panel['items'] as $id => &$item) {
				$item['local_text'] = formatNicely($item['local_text'], 50);
			}
		}
		*/
		
		break;

	
	case 'zenario__languages/nav/vlp/vlp_chained/panel':
		if ($mode != 'xml') {
			foreach ($panel['items'] as $id => &$item) {
				$item['local_text'] = formatNicely($item['local_text'], 50);
				$item['cell_css_classes'] = array();
				$item['cell_css_classes']['local_text'] = 'lang_flag_'. $item['language_id'];
				
				$item['traits'] = array();
				if ($item['phrase_id'] == $refinerId) {
					$item['traits']['reference_lang'] = true;
				
				} elseif ($item['phrase_id'] === null) {
					$item['traits']['ghost'] = true;
					$item['css_class'] = 'language ghost';
					$item['cell_css_classes']['language_id'] = 'ghost';
					$item['cell_css_classes']['protect_flag'] = 'ghost';
					$item['cell_css_classes']['local_text'] = 'ghost';
					$item['local_text'] = adminPhrase('MISSING [[lang_name]] ([[language_id]])', array('language_id' => $item['language_id'], 'lang_name' => getLanguageName($item['language_id'], false, false)));
				}
			}
		}
		
		break;
		
	
	case 'zenario__users/nav/admins/panel':
		foreach ($panel['items'] as $id => &$item) {
			
			$item['traits'] = array();
			$item['has_permissions'] = checkRowExists('action_admin_link', array('admin_id' => $id));
			
			if ($id == session('admin_userid')) {
				$item['traits']['current_admin'] = true;
			}
			
			if ($item['authtype'] == 'super') {
				$item['traits']['super'] = true;
			} else {
				$item['traits']['local'] = true;
			}
			
			if ($item['status'] == 'active') {
				$item['traits']['active'] = true;
			} else {
				$item['traits']['trashed'] = true;
			}
			
			if (!empty($item['checksum'])) {
				$item['traits']['has_image'] = true;
				$img = '&usage=admin&c='. $item['checksum'];
	
				$item['image'] = 'zenario/file.php?sk=1'. $img;
				$item['list_image'] = 'zenario/file.php?skl=1'. $img;
			}
			
		}
		
		if ($refinerName == 'trashed') {
			$panel['trash'] = false;
			$panel['title'] = adminPhrase('Trashed Administrators');
			$panel['no_items_message'] = adminPhrase('No Administrators have been Trashed.');
		
		} else {
			$panel['trash']['empty'] = !checkRowExists('admins', array('status' => 'deleted'));
		}
		
		break;


	case 'zenario__administration/panels/backups':
		
		if ($errorsAndWarnings = initialiseBackupFunctions(true)) {
			$panel['no_items_message'] = '';
			foreach ($errorsAndWarnings as $errorOrWarning) {
				$panel['no_items_message'] .= $errorOrWarning . '<br />';
			}
			
			$panel['no_items_message'] = str_replace('<br />', "\n", $panel['no_items_message']);
			$panel['collection_buttons'] = false;
			return;
		}
		
		if (file_exists($dirpath = setting('backup_dir'))) {
			$panel['items'] = array();
			foreach (scandir($dirpath) as $i => $file) {
				if (is_file($dirpath. '/'. $file) && substr($file, 0, 1) != '.') {
					$panel['items'][encodeItemIdForStorekeeper($file)] = array('filename' => $file, 'size' => filesize($dirpath. '/'. $file));
				}
			}
		}
		
		break;


	case 'zenario__administration/panels/file_types':
		foreach ($panel['items'] as &$item) {
			if ($item['custom']) {
				$item['traits'] = array('custom' => true);
			}
		}
		
		break;

}

return false;
