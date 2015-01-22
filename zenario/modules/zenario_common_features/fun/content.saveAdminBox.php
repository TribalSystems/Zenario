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


if ($box['key']['cID'] && !checkPriv('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
	exit;
}


//Create a new Content Item, or a new Draft of a Content Item, as needed.
createDraft($box['key']['cID'], $box['key']['source_cID'], $box['key']['cType'], $box['key']['cVersion'], $box['key']['source_cVersion'], $values['meta_data/language_id']);

if (!$box['key']['cID']) {
	exit;
} else {
	$box['key']['id'] = $box['key']['cType'].  '_'. $box['key']['cID'];
}

$version = array();
$newLayoutId = false;


//Save the values of each field in the Meta Data tab
if (engToBoolean($box['tabs']['meta_data']['edit_mode']['on'])
 && checkPriv('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
	//Only save aliases for first drafts
	if (!empty($values['meta_data/alias']) && $box['key']['cVersion'] == 1) {
		setRow('content', array('alias' => tidyAlias($values['meta_data/alias'])), array('id' => $box['key']['cID'], 'type' => $box['key']['cType']));
	}
	
	//Create Menu Nodes for first drafts
	if (!$box['key']['translate']) {
		if ($values['meta_data/create_menu'] && $box['key']['cVersion'] == 1 && $box['key']['target_menu_section']) {
			if (($box['key']['target_menu_parent'] && checkPriv('_PRIV_ADD_MENU_ITEM'))
			 || (!$box['key']['target_menu_parent'] && checkPriv('_PRIV_ADD_MENU_ITEM'))) {
				
				if ($box['key']['duplicate']
				 && recordEquivalence($box['key']['source_cID'], $box['key']['cID'], $box['key']['cType'])
				 && ($menu = getMenuItemFromContent($box['key']['source_cID'], $box['key']['cType']))) {
					//Try to use one equivalent Menu Node rather than creating two copies, if duplicationg into a new Language
					$menuId = $menu['mID'];
				
				} else {
					$submission = array();
					$submission['target_loc'] = 'int';
					$submission['content_id'] = $box['key']['cID'];
					$submission['content_type'] = $box['key']['cType'];
					$submission['content_version'] = $box['key']['cVersion'];
					$submission['parent_id'] = $box['key']['target_menu_parent'];
					$submission['section_id'] = $box['key']['target_menu_section'];
					
					$menuId = saveMenuDetails($submission);
				}
				
				saveMenuText($menuId, $values['meta_data/language_id'], array('name' => $values['meta_data/menu_title']));
			}
		}
	}

	//Set the title
	$version['title'] = $values['meta_data/title'];
	
	if ($path == 'zenario_quick_create') {
		$version['layout_id'] = $values['meta_data/layout_id'];
	} else {
		$version['description'] = $values['meta_data/description'];
		$version['keywords'] = $values['meta_data/keywords'];
		$version['publication_date'] = $values['meta_data/publication_date'];
		$version['writer_id'] = $values['meta_data/writer_id'];
		$version['writer_name'] = $values['meta_data/writer_name'];
		
		stripAbsURLsFromAdminBoxField($box['tabs']['meta_data']['fields']['content_summary']);
		$version['content_summary'] = $values['meta_data/content_summary'];
		
		if (isset($box['tabs']['meta_data']['fields']['lock_summary_edit_mode']) && !$box['tabs']['meta_data']['fields']['lock_summary_edit_mode']['hidden']) {
			$version['lock_summary'] = (int) $values['meta_data/lock_summary_edit_mode'];
		}
	}
}

//Set the Layout and Skin
if (engToBooleanArray($box, 'tabs', 'template', 'edit_mode', 'on')
 && checkPriv('_PRIV_EDIT_CONTENT_ITEM_TEMPLATE', $box['key']['cID'], $box['key']['cType'])) {
	$newLayoutId = $values['template/layout_id'];
	$version['css_class'] = $values['template/css_class'];
}

//Save the chosen file, if a file was chosen
if (engToBooleanArray($box, 'tabs', 'file', 'edit_mode', 'on')) {
	if ($values['file/file']
	 && ($path = getPathOfUploadedFileInCacheDir($values['file/file']))
	 && ($filename = preg_replace('/([^.a-z0-9]+)/i', '_', basename($path)))
	 && ($fileId = addFileToDocstoreDir('content', $path, $filename))) {
		$version['file_id'] = $fileId;
		$version['filename'] = $filename;
	} else {
		$version['file_id'] = $values['file/file'];
	}
}

//Update the latest version
if (!empty($version) || $newLayoutId) {
	updateVersion($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $version);
	
	//Update the layout
	if ($newLayoutId) {
		changeContentItemLayout($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $newLayoutId);
	}
}


//Save the content tabs (up to four of them), for each WYSIWYG Editor
if (isset($box['tabs']['content1'])
 && checkPriv('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
	$i = 0;
	$slots = pluginMainSlot($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], false, false, $values['template/layout_id']);

	if (!empty($slots)) {
		foreach ($slots as $slot) {
			if (++$i > 4) {
				break;
			}
			
			if (!empty($box['tabs']['content'. $i]['edit_mode']['on'])) {
				stripAbsURLsFromAdminBoxField($box['tabs']['content'. $i]['fields']['content']);
				saveContent($values['content'. $i. '/content'], $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $slot);
			}
		}
	}
}


//Update item Categories
if (empty($box['tabs']['categories']['hidden'])
 && engToBooleanArray($box, 'tabs', 'categories', 'edit_mode', 'on')
 && isset($values['categories/categories'])
 && checkPriv('_PRIV_EDIT_CONTENT_ITEM_CATEGORIES')) {
	setContentItemCategories($box['key']['cID'], $box['key']['cType'], explode(',', $values['categories/categories']));
}

//Record and equivalence if this Content Item was duplicated into another Language
if ($box['key']['translate']) {
	if ($equivId = recordEquivalence($box['key']['source_cID'], $box['key']['cID'], $box['key']['cType'])) {
		//Create copies of any Menu Node Text into this language
		$sql = "
			INSERT IGNORE INTO ". DB_NAME_PREFIX. "menu_text
				(menu_id, language_id, name, descriptive_text)
			SELECT menu_id, '". sqlEscape($values['meta_data/language_id']). "', name, descriptive_text
			FROM ". DB_NAME_PREFIX. "menu_nodes AS mn
			INNER JOIN ". DB_NAME_PREFIX. "menu_text AS mt
			   ON mt.menu_id = mn.id
			  AND mt.language_id = '". sqlEscape(getContentLang($box['key']['source_cID'], $box['key']['cType'])). "'
			WHERE mn.equiv_id = ". (int) $equivId. "
			  AND mn.content_type = '". sqlEscape($box['key']['cType']). "'";
		sqlQuery($sql);
	}
}

return false;