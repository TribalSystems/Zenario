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


//The $importantGetRequests should be passed to us in the URL, get them and decode them.
if (empty($_GET['get']) || !($importantGetRequests = json_decode($_GET['get'], true))) {
	$importantGetRequests = [];
}

//Look up details on the content item and version we are displaying the toolbar for
$content = ze\row::get('content_items', true, ['id' => $cID, 'type' => $cType]);
$chain = ze\row::get('translation_chains', true, ['equiv_id' => $content['equiv_id'], 'type' => $cType]);
$version = ze\row::get('content_item_versions', true, ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
$menuItems = ze\menu::getFromContentItem($cID, $cType, true, false, true, true);

$tagId = $cType. '_'. $cID;
$isMultilingual = ze\lang::count() > 1;

if (!$content || !$version) {
	exit;
} else {
	ze\content::setShowableContent($content, $chain, $version, false);
	$layout = ze\content::layoutDetails(ze::$layoutId);
	$LayoutIdentifier = ze\layoutAdm::codeName(ze::$layoutId);
	$layout['usage'] = ze\layoutAdm::usage(ze::$layoutId);
	
	$adminToolbar['sections']['layout']['buttons']['settings']['label'] = ze\admin::phrase('[[name]] properties', ['name' => $LayoutIdentifier]);
	$adminToolbar['sections']['layout']['buttons']['settings']['admin_box']['key']['id'] = ze::$layoutId;
	
	//Hide "copy from.." when content type is document.
	if ($cType == 'document') {
		$adminToolbar['sections']['edit']['buttons']['create_draft_by_overwriting']['hidden'] = true;

		//If this is a document, apply the "Rescan" confirmation merge fields...
		$tagFormattedNicely = ze\content::formatTag($cID, $cType);
		ze\lang::applyMergeFields($adminToolbar['sections']['edit']['buttons']['rescan_extract']['ajax']['confirm']['message'], ['tag' => $tagFormattedNicely]);
	} else {
		//... or hide the "Rescan" button otherwise.
		$adminToolbar['sections']['edit']['buttons']['rescan_extract']['hidden'] = true;
	}
		
	//Set the link to Gridmaker
	if (isset($adminToolbar['sections']['layout']['buttons']['edit_grid'])) {
		//To Do: only set the link if this Layout was actually made using grid maker
			//(Maybe you could check to see if a grid css file exists?)
		if (true) {
			$adminToolbar['sections']['layout']['buttons']['edit_grid']['popout']['href'] .= '&id='. ze::$layoutId;
		} else {
			unset($adminToolbar['sections']['layout']['buttons']['edit_grid']);
		}
		
		$adminToolbar['sections']['layout']['buttons']['edit_grid']['label'] = ze\admin::phrase('Edit [[name]] with Gridmaker', ['name' => $LayoutIdentifier]);
	}
	
	if (!ze::$skinId
	 || !($skin = ze\row::get('skins', ['display_name', 'enable_editable_css'], ze::$skinId))
	 || !($skin['enable_editable_css'])) {
		unset($adminToolbar['sections']['layout']['buttons']['edit_skin']);
	
	} elseif (isset($adminToolbar['sections']['layout']['buttons']['edit_skin'])) {
		$adminToolbar['sections']['layout']['buttons']['edit_skin']['admin_box']['key']['skinId'] = ze::$skinId;
		$adminToolbar['sections']['layout']['buttons']['edit_skin']['label'] =
			ze\admin::phrase('Edit skin "[[display_name]]"', $skin);
	}
	
	if (isset($adminToolbar['sections']['layout']['buttons']['settings']['admin_box']['key']['id'])) {
		$adminToolbar['sections']['layout']['buttons']['settings']['admin_box']['key']['id'] = ze::$layoutId;
	}

	if (ze\row::get('content_types', 'allow_pinned_content', ['content_type_id' => $cType])) {
		if ($version['pinned']) {
			unset($adminToolbar['sections']['icons']['buttons']['not_pinned']);
		} else {
			unset($adminToolbar['sections']['icons']['buttons']['pinned']);
		}
	} else {
		unset($adminToolbar['sections']['icons']['buttons']['not_pinned']);
		unset($adminToolbar['sections']['icons']['buttons']['pinned']);
	}
}

if (!ze::setting('create_draft_warning')) {
	unset($adminToolbar['sections']['status_button']['buttons']['start_editing']['ajax']['confirm']);
}

if (ze::$status == 'trashed' && $cVersion == ze::$adminVersion) {
	unset($adminToolbar['sections']['edit']['buttons']['rollback_item']);
	unset($adminToolbar['sections']['edit']['buttons']['no_rollback_item']);
	unset($adminToolbar['sections']['status_button']['buttons']['cant_start_editing']);
}

if (ze::in(ze::$status, 'hidden', 'trashed') && $cVersion == ze::$adminVersion) {
	unset($adminToolbar['sections']['status_button']['buttons']['start_editing']);
} else {
	unset($adminToolbar['sections']['status_button']['buttons']['redraft']);
}

if (ze::$status == 'trashed' || ($cVersion < ze::$adminVersion && (!ze::$visitorVersion || $cVersion < ze::$visitorVersion))) {
	unset($adminToolbar['toolbars']['menu1']);
	unset($adminToolbar['sections']['menu1']);
	unset($adminToolbar['sections']['status_button']['buttons']['start_editing']);
	unset($adminToolbar['sections']['status_button']['buttons']['cant_start_editing']);
}

//Disable the "start editing"/"Rollback" buttons if a draft exists
if (ze::$adminVersion > $cVersion
 && ze::$adminVersion > ze::$visitorVersion) {
	unset($adminToolbar['sections']['status_button']['buttons']['start_editing']);
	unset($adminToolbar['sections']['edit']['buttons']['rollback_item']);
} else {
	unset($adminToolbar['sections']['status_button']['buttons']['cant_start_editing']);
	unset($adminToolbar['sections']['edit']['buttons']['no_rollback_item']);
}

if (ze::$status == 'trashed' || $cVersion != ze::$adminVersion) {
	unset($adminToolbar['toolbars']['edit']);
	unset($adminToolbar['toolbars']['edit_disabled']);
} else {
	unset($adminToolbar['toolbars']['rollback']);
	unset($adminToolbar['sections']['edit']['buttons']['rollback_item']);
}

//Most recent Version
if ($cVersion == ze::$adminVersion) {
	unset($adminToolbar['sections']['edit']['buttons']['no_rollback_item']);
	unset($adminToolbar['sections']['status_button']['buttons']['cant_start_editing']);

} else {
	unset($adminToolbar['toolbars']['edit']);
	unset($adminToolbar['toolbars']['layout']);
	unset($adminToolbar['sections']['slot_controls']['buttons']['item_head']);
	unset($adminToolbar['sections']['slot_controls']['buttons']['item_foot']);
	unset($adminToolbar['sections']['status_button']['buttons']['delete_draft']);
	unset($adminToolbar['sections']['status_button']['buttons']['hide_content']);
	unset($adminToolbar['sections']['status_button']['buttons']['trash_content']);
	unset($adminToolbar['sections']['edit']['buttons']['create_draft_by_copying']);
	unset($adminToolbar['sections']['edit']['buttons']['create_draft_by_overwriting']);
	unset($adminToolbar['sections']['slot_wand']['buttons']['slot_wand_on']);
	unset($adminToolbar['sections']['slot_wand']['buttons']['slot_wand_off']);
}


$permsOnThisItem = true;
if (ze\admin::hasSpecificPerms()) {
	
	$permsOnThisItem = ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType);
	
	//Check if this admin can edit any of the menu text...
	$canEditSomeMenuText = false;
	foreach ($menuItems as $i => &$menuItem) {
		if (ze\priv::onMenuText('_PRIV_EDIT_MENU_TEXT', $menuItem['id'], $menuItem['language_id'], $menuItem['section_id'])) {
			$canEditSomeMenuText = true;
			break;
		}
	}
	
	//...and if not, don't show the menu toolbar
	if (!$canEditSomeMenuText) {
		$menuItems = [];
		unset($adminToolbar['toolbars']['menu1']);
		unset($adminToolbar['toolbars']['menu_secondary']);
		unset($adminToolbar['sections']['primary_menu_node']);
	}
}

if ($permsOnThisItem) {
	$editToolbar = 'edit';
	unset($adminToolbar['toolbars']['restricted_editing']);
} else {
	$editToolbar = 'restricted_editing';
	unset($adminToolbar['toolbars']['edit']);
	unset($adminToolbar['toolbars']['edit_disabled']);
	unset($adminToolbar['toolbars']['rollback']);
}



//Hidden Version
if ($cVersion == ze::$adminVersion && ze::$status == 'hidden') {
	foreach (['edit', 'edit_disabled'] as $toolbar) {
##		if (isset($adminToolbar['toolbars'][$toolbar])) {
##			$adminToolbar['toolbars'][$toolbar]['tooltip'] .= '<br/>'. ze\admin::phrase('This content item is hidden');
##		}
	}
	
	if (isset($adminToolbar['sections']['status_button']['buttons']['republish'])) {
		if (!ze\sql::fetchRow('
			SELECT 1
			FROM '. DB_PREFIX. 'content_item_versions
			WHERE published_datetime IS NOT NULL
			  AND published_datetime
			  AND id = '. (int) ze::$cID. '
			  AND `type` = \''. ze\escape::asciiInSQL(ze::$cType). '\'
			LIMIT 1
		')) {
			$adminToolbar['sections']['status_button']['buttons']['republish']['label'] = ze\admin::phrase('Publish');
		}
	}

} else {
	unset($adminToolbar['sections']['status_button']['buttons']['republish']);
}

if (ze::$adminVersion == 1
 || !ze::in ($cVersion, ze::$visitorVersion, ze::$adminVersion)
 || !ze\row::exists('content_item_versions', [
		'id' => ze::$cID,
		'type' => ze::$cType,
		'version' => ['!' => [ze::$visitorVersion, ze::$adminVersion]]]
)) {
	unset($adminToolbar['sections']['edit']['buttons']['delete_archives']);
}

//Draft Version
if ($cVersion == ze::$adminVersion && ze::$isDraft) {
	
##	foreach (['edit', 'edit_disabled'] as $toolbar) {
##		if (isset($adminToolbar['toolbars'][$toolbar])) {
##			
##			if (!empty($adminToolbar['toolbars'][$toolbar]['tooltip'])) {
##				$adminToolbar['toolbars'][$toolbar]['tooltip'] .= '<br/>';
##			}
##			$adminToolbar['toolbars'][$toolbar]['tooltip'] .= ze\admin::phrase('This content item is a draft. Click to edit.');
##		}
##	}
	
	
	$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_orange zenario_section_dark_text';
	
	// First draft
	if ($cVersion == 1) {
		$menu = ze\menu::getFromContentItem($cID, $cType);
		
		$redirect_page = ze\admin::phrase('the Home page');
		if ($menu && $menu['parent_id']) {
			$redirectContent = ze\row::get('menu_nodes', ['equiv_id', 'content_type'], ['id' => $menu['parent_id']]);
			$redirectCID = $redirectContent['equiv_id'];
			$redrectCType = $redirectContent['content_type'];
			$redirect_page = ze\content::formatTag($redirectCID, $redrectCType, -1, false, true);
		}
		
		$adminToolbar['sections']['edit']['label'] = ze\admin::phrase('First Draft');
		$adminToolbar['sections']['status_button']['buttons']['delete_draft']['ajax']['confirm']['message'] = ze\admin::phrase("
			You are about to delete the current draft version of this content item. 
			
			As there isn't published version of this content item, it'll be deleted and you will be redirected to [[redirect_page]].
			
			Are you sure you wish to proceed?
		", ['redirect_page' => $redirect_page]);
	} else {
		$adminToolbar['sections']['edit']['label'] = ze\admin::phrase('Draft');
	}

} else {
	unset($adminToolbar['sections']['status_button']['buttons']['publish']);
	unset($adminToolbar['sections']['status_button']['buttons']['delete_draft']);
	

	//Published Version
	if ($cVersion == ze::$visitorVersion) {
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_green zenario_section_dark_text';
		$adminToolbar['sections']['edit']['label'] = ze\admin::phrase('Published');
		unset($adminToolbar['sections']['edit']['buttons']['no_rollback_item']);
	
	} else
	if ((ze::$status == 'hidden' && $cVersion == ze::$adminVersion)
	 || (ze::$status == 'hidden_with_draft' && $cVersion == ze::$adminVersion - 1)) {
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_grey zenario_section_dark_text';
		$adminToolbar['sections']['edit']['label'] = ze\admin::phrase('Hidden');
	
	//Trashed content items
	} else
	if ((ze::$status == 'trashed' && $cVersion == ze::$adminVersion)
	 || (ze::$status == 'trashed_with_draft' && $cVersion == ze::$adminVersion - 1)) {
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_brown zenario_section_dark_text';
		$adminToolbar['sections']['edit']['label'] = ze\admin::phrase('Trashed Item');
	
	//Archived/Previous Versions
	} else {
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_brown zenario_section_dark_text';
		$adminToolbar['sections']['edit']['label'] = ze\admin::phrase('Archived Version');
	}
}


//Content with a draft version
if (ze::$isDraft) {
	unset($adminToolbar['sections']['status_button']['buttons']['start_editing']);
	unset($adminToolbar['sections']['edit']['buttons']['rollback_item']);
	unset($adminToolbar['sections']['edit']['buttons']['create_draft_by_copying']);
} else {
	unset($adminToolbar['sections']['edit']['buttons']['create_draft_by_overwriting']);
}

//The current Admin can edit the Content
if (ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType)) {
	unset($adminToolbar['toolbars']['edit_disabled']);
} else {
	unset($adminToolbar['toolbars']['edit']);
	unset($adminToolbar['sections']['status_button']['buttons']['publish']);
	
	if (isset($adminToolbar['toolbars']['edit_disabled'])) {
		if (ze\content::isDraft(ze::$status) && $cVersion != ze::$adminVersion) {
			$adminToolbar['toolbars']['edit_disabled']['tooltip'] =
				ze\admin::phrase('Editing of this version is disabled because a draft version exists');
		}
	}
}

//Check if deletion is allowed
$allowDelete = null;
if (isset($adminToolbar['sections']['status_button']['buttons']['delete_draft'])) {
	$allowDelete = ze\contentAdm::allowDelete($cID, $cType, ze::$status);
	
	if (!$allowDelete) {
		if ($allowDelete === ze\contentAdm::CANT_BECAUSE_SPECIAL_PAGE) {
			$adminToolbar['sections']['status_button']['buttons']['delete_draft']['disabled'] = true;
			$adminToolbar['sections']['status_button']['buttons']['delete_draft']['disabled_tooltip'] = ze\admin::phrase("You can't delete a special page.");
		} else {
			unset($adminToolbar['sections']['status_button']['buttons']['delete_draft']);
		}
	}
}

$allowHide = null;
if (isset($adminToolbar['sections']['status_button']['buttons']['hide_content'])) {
	$allowHide = ze\contentAdm::allowHide($cID, $cType, ze::$status);
	
	if (!$allowHide) {
		if ($allowHide === ze\contentAdm::CANT_BECAUSE_SPECIAL_PAGE) {
			$adminToolbar['sections']['status_button']['buttons']['hide_content']['disabled'] = true;
			$adminToolbar['sections']['status_button']['buttons']['hide_content']['disabled_tooltip'] = ze\admin::phrase("You can't hide this special page.");
		} else {
			unset($adminToolbar['sections']['status_button']['buttons']['hide_content']);
		}
	}
}

$allowTrash = null;
if (isset($adminToolbar['sections']['status_button']['buttons']['trash_content'])) {
	$allowTrash = ze\contentAdm::allowTrash($cID, $cType, ze::$status);
	
	if (!$allowTrash) {
		if ($allowTrash === ze\contentAdm::CANT_BECAUSE_SPECIAL_PAGE) {
			$adminToolbar['sections']['status_button']['buttons']['trash_content']['disabled'] = true;
			$adminToolbar['sections']['status_button']['buttons']['trash_content']['disabled_tooltip'] = ze\admin::phrase("You can't trash a special page.");
		} else {
			unset($adminToolbar['sections']['status_button']['buttons']['trash_content']);
		}
	}
}



//Only show locking info on drafts
if (!ze::$isDraft) {
	unset($adminToolbar['sections']['edit']['buttons']['lock']);
	unset($adminToolbar['sections']['edit']['buttons']['locked']);
	unset($adminToolbar['sections']['edit']['buttons']['unlock']);
	unset($adminToolbar['sections']['edit']['buttons']['force_open']);

//The content item is not locked
} elseif (!$content['lock_owner_id']) {
	$adminToolbar['sections']['edit']['label'] = ze\admin::phrase('Unlocked');
	
	unset($adminToolbar['sections']['edit']['buttons']['locked']);
	unset($adminToolbar['sections']['edit']['buttons']['unlock']);
	unset($adminToolbar['sections']['edit']['buttons']['force_open']);

//The content item is locked
} else {
	$mrg = [
		'name' => htmlspecialchars(ze\admin::formatName($content['lock_owner_id'])),
		'time' => ze\admin::timeDiff(ze\date::now(), $content['locked_datetime'], 300)];
	
	if ($mrg['time'] === true) {
		$mrg['time'] = ze\admin::phrase('< 5 minutes');
	}
	
	//The current Admin has a lock on the content item
	if ($content['lock_owner_id'] && $content['lock_owner_id'] == ($_SESSION['admin_userid'] ?? false)) {
		$adminToolbar['sections']['edit']['buttons']['lock_dropdown']['label'] = ze\admin::phrase('LOCKED by you');
		
		$adminToolbar['sections']['edit']['buttons']['unlock']['tooltip'] =
			ze\admin::phrase('Locked by you [[time]] ago|Click here to unlock', $mrg);
		
		unset($adminToolbar['sections']['edit']['buttons']['lock']);
		unset($adminToolbar['sections']['edit']['buttons']['locked']);
		unset($adminToolbar['sections']['edit']['buttons']['force_open']);
	
	//The current Admin can remove other's locks
	} elseif (ze\priv::check('_PRIV_CANCEL_CHECKOUT')) {
		$adminToolbar['sections']['edit']['buttons']['lock_dropdown']['label'] = ze\admin::phrase('LOCKED');
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_pink';
		
		$adminToolbar['sections']['edit']['buttons']['force_open']['tooltip'] =
			ze\admin::phrase('Locked by [[name]], [[time]] ago|Click here to force-unlock', $mrg);
		
		if (isset($adminToolbar['toolbars']['edit'])) {
			$adminToolbar['toolbars']['edit']['help'] =
				[
					'message' => ze\admin::phrase('This content item is locked by another administrator. Certain functions may not be available.'),
					'message_type' => 'warning'];
		}
		
		unset($adminToolbar['sections']['edit']['buttons']['lock']);
		unset($adminToolbar['sections']['edit']['buttons']['locked']);
		unset($adminToolbar['sections']['edit']['buttons']['unlock']);
		
		$adminToolbar['lock_warning'] =
			ze\admin::phrase('This content item is locked, you will need to unlock it via the Edit tab before you can make changes.');
	
	} else {
		$adminToolbar['sections']['edit']['buttons']['lock_dropdown']['label'] = ze\admin::phrase('LOCKED');
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_pink';
		
		$adminToolbar['sections']['edit']['buttons']['locked']['tooltip'] =
			ze\admin::phrase('Locked by [[name]], [[time]] ago', $mrg);
		
		if (isset($adminToolbar['toolbars']['edit'])) {
			$adminToolbar['toolbars']['edit']['help'] =
				[
					'message' => ze\admin::phrase('This content item is locked by another administrator. Certain functions may not be available.'),
					'message_type' => 'warning'];
		}
		
		unset($adminToolbar['sections']['edit']['buttons']['lock']);
		unset($adminToolbar['sections']['edit']['buttons']['unlock']);
		unset($adminToolbar['sections']['edit']['buttons']['force_open']);
		
		$adminToolbar['lock_warning'] =
			ze\admin::phrase('This content item is locked by another administrator, you will not be able to make changes.');
	}
}


if (isset($adminToolbar['sections']['edit']['buttons']['create_draft_by_copying'])) {
	$adminToolbar['sections']['edit']['buttons']['create_draft_by_copying']['pick_items']['path'] = 'zenario__content/panels/content/refiners/content_type//'. $cType. '//';
}
if (isset($adminToolbar['sections']['edit']['buttons']['create_draft_by_overwriting'])) {
	$adminToolbar['sections']['edit']['buttons']['create_draft_by_overwriting']['pick_items']['path'] = 'zenario__content/panels/content/refiners/content_type//'. $cType. '//';
}

if (isset($adminToolbar['sections']['edit']['buttons']['item_meta_data'])) {
	$cTypeDetails = ze\contentAdm::cTypeDetails($cType);
	
	$tooltip =
		'<strong>'. ze\admin::phrase('Title:'). '</strong> '. htmlspecialchars(ze::$pageTitle);

	if (ze\lang::count() > 1) {
		$tooltip .=
			'<br/><strong>'. ze\admin::phrase('Language:'). '</strong> '. htmlspecialchars(ze\lang::name(ze::$langId));
	}
	
	if ($cTypeDetails['description_field'] != 'hidden') {
		$tooltip .= 
			'<br/><strong>'. ze\admin::phrase('Description:'). '</strong> '. (ze::$pageDesc ? htmlspecialchars(ze::$pageDesc) : ze\admin::phrase('[Empty]'));
	}
	if ($cTypeDetails['keywords_field'] != 'hidden') {
		$tooltip .= 
			'<br/><strong>'. ze\admin::phrase('Keywords:'). '</strong> '. (ze::$pageKeywords ? htmlspecialchars(ze::$pageKeywords) : ze\admin::phrase('[Empty]'));
	}
	if ($cTypeDetails['release_date_field'] != 'hidden') {
		$tooltip .= 
			'<br/><strong>'. ze\admin::phrase('Release date:'). '</strong> ';
		
		if ($version['release_date']) {
			$tooltip .=  htmlspecialchars(ze\admin::formatDate($version['release_date']));
		} else {
			$tooltip .= ze\admin::phrase('[Empty]');
		}
	}
	if ($cTypeDetails['writer_field'] != 'hidden') {
		$tooltip .= 
			'<br/><strong>'. ze\admin::phrase('Author:'). '</strong> ';
		
		if ($version['writer_id']) {
			$tooltip .= ze\admin::formatName($version['writer_id']);
		} else {
			$tooltip .= ze\admin::phrase('[Empty]');
		}
	}
	if ($cTypeDetails['summary_field'] != 'hidden') {
		$tooltip .= 
			'<br/><strong>'. ze\admin::phrase('Summary:'). '</strong> ';
		
		if ($version['content_summary']) {
			$tooltip .= strip_tags(trim($version['content_summary']));
		} else {
			$tooltip .= ze\admin::phrase('[Empty]');
		}
	}
	if ($cTypeDetails['allow_pinned_content'] == 1) {
		if ($version['pinned']) {
			$tooltip .= 
				'<br/>'. ze\admin::phrase('Pinned'). ' ';
		} else {
			$tooltip .= 
				'<br/>'. ze\admin::phrase('Not pinned'). ' ';
		}
	}
	
	$adminToolbar['sections']['edit']['buttons']['item_meta_data']['tooltip'] .= $tooltip;
}

if (isset($adminToolbar['sections']['edit']['buttons']['item_template'])) {
	$adminToolbar['sections']['edit']['buttons']['item_template']['tooltip'] .=
				 ze\admin::phrase('Layout:'). ' '. htmlspecialchars($layout['id_and_name']).
		'<br/>'. ze\admin::phrase('Skin:'). ' '. htmlspecialchars(ze::$skinName);
}


if (isset($adminToolbar['sections'][$editToolbar]['buttons']['view_items_images'])) {
	$adminToolbar['sections'][$editToolbar]['buttons']['view_items_images']['organizer_quick']['path'] =
		'zenario__content/panels/content/item_buttons/images//'. $tagId. '//';
}

if (isset($adminToolbar['sections']['edit']['buttons']['view_slots'])) {
	$adminToolbar['sections']['edit']['buttons']['view_slots']['organizer_quick']['path'] =
		'zenario__content/panels/content/item_buttons/view_slots//'. $tagId. '//';
}


//Multilingual options
if (isset($adminToolbar['sections']['translations'])) {
	
	//Hide the multilingual section of the toolbar if not in use
	if (!$isMultilingual) {
		$adminToolbar['sections']['translations']['hidden'] = true;

	} else {
		//Loop through every possible language, added a drop-down with options for that language
		$n = 0;
		$ord = 0;
		foreach (ze\lang::getLanguages($includeAllLanguages = false, $orderByEnglishName = false, $defaultLangFirst = true) as $lang) {
			
			//Check to see if there is already a translation
			$translation = ze\row::get(
				'content_items',
				['id', 'type', 'alias', 'status'],
				['equiv_id' => ze::$equivId, 'type' => ze::$cType, 'language_id' => $lang['id']]
			);
			
			$exists = (bool) $translation;
			$isOG = $exists && $translation['id'] == ze::$equivId;
			$isCurrent = $exists && $translation['id'] == ze::$cID;
			
			//Select one of the different templates to copy, depending on what the status of the translation is
			if ($exists) {
				$lang['status'] = $translation['status'];
				
				if ($isCurrent) {
					$buttons = $adminToolbar['sections']['translations']['custom_template_buttons_current'];
			
				} else {
					$buttons = $adminToolbar['sections']['translations']['custom_template_buttons_exists'];
				}
			} else {
				if (ze\priv::onLanguage('_PRIV_CREATE_TRANSLATION_FIRST_DRAFT', $lang['id'])) {
					$buttons = $adminToolbar['sections']['translations']['custom_template_buttons_missing_can_create'];
			
				} else {
					$buttons = $adminToolbar['sections']['translations']['custom_template_buttons_missing_cant_create'];
			
				}
			}
			$buttons = json_decode(str_replace('znz', ++$n, str_replace('zlangIdz', preg_replace('/[^a-z0-9_-]/', '', $lang['id']), json_encode($buttons))), true);
				//N.b. language codes can only contain the symbols "a-z0-9_-".
				//The only reason this replacement is in any way safe is because they can't contain special characters.
			
			foreach ($buttons as &$button) {
				$button['ord'] = ++$ord;
				
				//Have a slightly different message when talking about the original content item
				if ($isOG && isset($button['label_og'])) {
					$button['label'] = $button['label_og'];
				}
				unset($button['label_og']);
				
				//Apply merge fields to the label
				if (isset($button['label'])) {
					ze\lang::applyMergeFields($button['label'], $lang);
				}
				if (isset($button['css_class'])) {
					ze\lang::applyMergeFields($button['css_class'], $lang);
				}
				
				if ($exists) {
					//Set a link to the content item
					if (isset($button['frontend_link'])) {
						$button['frontend_link'] =
							ze\link::toItem($translation['id'], $translation['type'], false, $importantGetRequests, $translation['alias']);
							//Note: The ze\link::toItem() function has the option to automatically add the $importantGetRequests.
							//However as we're actually handling an AJAX request, and are not on the page itself, this
							//option won't work here so we need to manually pass in the $importantGetRequests.
					}
				
				} else {
				
				
				}
			}
			unset($button);
			
			
			
			
			$adminToolbar['sections']['translations']['buttons'] = array_merge(
				$adminToolbar['sections']['translations']['buttons'],
				$buttons
			);

		}
	}
}







//Set up the version history navigation, including a left arrow, the current item in view and a right arrow, with the correct icons and tooltips on each
if ($cVersion > 1 && ze\row::exists('content_item_versions', ['id' => $cID, 'type' => $cType, 'version' => $cVersion - 1])) {
	$mrg = ['status' => ze\contentAdm::getContentItemVersionStatus($content, $cVersion - 1)];
	$adminToolbar['sections']['history']['buttons']['content_item_left']['css_class'] = ze\contentAdm::getContentItemVersionToolbarIcon($content, $cVersion - 1, 'zenario_at_prev_version_');
	$adminToolbar['sections']['history']['buttons']['content_item_left']['frontend_link'] = DIRECTORY_INDEX_FILENAME. '?cID='. $cID. '&cType='. $cType. '&cVersion='. ($cVersion - 1);
	$adminToolbar['sections']['history']['buttons']['content_item_left']['label'] = ze\admin::phrase('View previous ([[status]])', $mrg);
	$adminToolbar['sections']['history']['buttons']['content_item_left']['tooltip'] =
		ze\admin::phrase('View previous version'). '|'.
		ze\admin::phrase('Version status: [[status]]', $mrg);
	
	unset($adminToolbar['sections']['history']['buttons']['no_content_left']);
} else {
	unset($adminToolbar['sections']['history']['buttons']['content_item_left']);
}


$mrg = ['status' => ze\contentAdm::getContentItemVersionStatus($content, $cVersion)];
$adminToolbar['sections']['history']['buttons']['content_item_current']['css_class'] = ze\contentAdm::getContentItemVersionToolbarIcon($content, $cVersion, 'zenario_at_current_version_');
$adminToolbar['sections']['history']['buttons']['content_item_current']['label'] = ze\admin::phrase('This version ([[status]])', $mrg);
$adminToolbar['sections']['history']['buttons']['content_item_current']['tooltip'] =
	ze\admin::phrase('Version [[v]]', ['v' => $cVersion]). '|'.
	ze\admin::phrase('Version status: [[status]]<br/><i>This version is in view</i>', $mrg);

//At the top right of the toolbar, show either a Publish button, or the current status if we can't currently publish
if (isset($adminToolbar['sections']['status_button']['buttons']['publish'])) {
	unset($adminToolbar['sections']['status_button']['buttons']['status_button']);

} else {
	$adminToolbar['sections']['status_button']['buttons']['status_button']['css_class'] .= ' '. ze\contentAdm::getContentItemVersionToolbarIcon($content, $cVersion, 'zenario_at_status_button_');
	$adminToolbar['sections']['status_button']['buttons']['status_button']['label'] = ucwords($mrg['status']);
	$adminToolbar['sections']['status_button']['buttons']['status_button']['tooltip'] =
		ze\admin::phrase('Version [[v]]', ['v' => $cVersion]). '|'.
		ze\admin::phrase('Version status: [[status]]', $mrg);
}


if (ze\row::exists('content_item_versions', ['id' => $cID, 'type' => $cType, 'version' => $cVersion + 1])) {
	$mrg = ['status' => ze\contentAdm::getContentItemVersionStatus($content, $cVersion + 1)];
	$adminToolbar['sections']['history']['buttons']['content_item_right']['css_class'] = ze\contentAdm::getContentItemVersionToolbarIcon($content, $cVersion + 1, 'zenario_at_next_version_');
	$adminToolbar['sections']['history']['buttons']['content_item_right']['frontend_link'] = DIRECTORY_INDEX_FILENAME. '?cID='. $cID. '&cType='. $cType. '&cVersion='. ($cVersion + 1);
	$adminToolbar['sections']['history']['buttons']['content_item_right']['label'] = ze\admin::phrase('Next version ([[status]])', $mrg);
	$adminToolbar['sections']['history']['buttons']['content_item_right']['tooltip'] =
		ze\admin::phrase('View next version'). '|'.
		ze\admin::phrase('Version status: [[status]]', $mrg);

	unset($adminToolbar['sections']['history']['buttons']['no_content_right']);

} else {
	unset($adminToolbar['sections']['history']['buttons']['content_item_right']);
}




if (isset($adminToolbar['sections']['edit'])
 || isset($adminToolbar['sections']['layout'])) {
 	
 	$headAndFoot = ze\row::get(
 		'layouts',
 		['head_html', 'head_visitor_only', 'foot_html', 'foot_visitor_only'],
 		ze::$layoutId);
 	$layout = array_merge($layout, $headAndFoot);
}


if (isset($adminToolbar['sections']['slot_controls']['buttons']['item_head'])) {
	$adminToolbar_edit_buttons_head = &$adminToolbar['sections']['slot_controls']['buttons']['item_head'];
	if(!isset($adminToolbar_edit_buttons_head['tooltip'])) {
		$adminToolbar_edit_buttons_head['tooltip'] = '';
	}
	if ($version['head_html'] === null) {
		$adminToolbar_edit_buttons_head['css_class'] = 'head_slot_empty';
		$adminToolbar_edit_buttons_head['tooltip'] .= ze\admin::phrase('This Layer is empty.');
	} else {
		$adminToolbar_edit_buttons_head['css_class'] = 'head_slot_full';
		$adminToolbar_edit_buttons_head['tooltip'] .= ze\admin::phrase('This Layer is populated.');
	}
	if ($version['head_visitor_only']) {
		$adminToolbar_edit_buttons_head['tooltip'] .= '<br/>'. ze\admin::phrase('This Layer is not output in Admin Mode.');
	}
}


if (isset($adminToolbar['sections']['slot_controls']['buttons']['item_foot'])) {
	$adminToolbar_edit_buttons_foot = &$adminToolbar['sections']['slot_controls']['buttons']['item_foot'];
	if(!isset($adminToolbar_edit_buttons_foot['tooltip'])) {
		$adminToolbar_edit_buttons_foot['tooltip'] = '';
	}
	if ($version['foot_html'] === null) {
 		$adminToolbar_edit_buttons_foot['css_class'] = 'foot_slot_empty';
 		$adminToolbar_edit_buttons_foot['tooltip'] .= ze\admin::phrase('This Layer is empty.');
 	} else {
 		$adminToolbar_edit_buttons_foot['css_class'] = 'foot_slot_full';
 		$adminToolbar_edit_buttons_foot['tooltip'] .= ze\admin::phrase('This Layer is populated.');
 	}
 	if ($version['foot_visitor_only']) {
 		$adminToolbar_edit_buttons_foot['tooltip'] .= '<br/>'. ze\admin::phrase('This Layer is not output in Admin Mode.');
 	}
}


if (isset($adminToolbar['sections']['layout'])) {
	$adminToolbar['sections']['layout']['buttons']['id_and_name']['label'] =
		ze\admin::phrase('Layout: [[id_and_name]]', $layout);
	
	if ($layout['usage'] == 1) {
		$adminToolbar['sections']['layout']['buttons']['usage']['label'] =
			ze\admin::phrase('[[usage]] content item uses this layout', $layout);
	} else {
		$adminToolbar['sections']['layout']['buttons']['usage']['label'] =
			ze\admin::phrase('[[usage]] content items use this layout', $layout);
	}
 	
 	$adminToolbar['sections']['layout']['buttons']['skq']['organizer_quick']['path'] =
 		$layout['status'] == 'active'?
 			'zenario__layouts/panels/layouts//'. ze::$layoutId
 		:	'zenario__layouts/panels/layouts/trash////'. ze::$layoutId;
 	
 	
 	$adminToolbar_buttons_head = &$adminToolbar['sections']['slot_controls']['buttons']['layout_head'];
	if(!isset($adminToolbar_buttons_head['tooltip'])) {
		$adminToolbar_buttons_head['tooltip'] = '';
	}
 	if ($version['head_overwrite']) {
		if ($layout['head_html'] === null) {
			$adminToolbar_buttons_head['css_class'] = 'head_slot_empty_overwritten';
	 		$adminToolbar_buttons_head['tooltip'] .= ze\admin::phrase('This layer is empty.');
		} else {
			$adminToolbar_buttons_head['css_class'] = 'head_slot_full_overwritten';
	 		$adminToolbar_buttons_head['tooltip'] .= ze\admin::phrase('This layer is populated.');
		}
 		$adminToolbar_buttons_head['tooltip'] .= '<br/>'. ze\admin::phrase('This layer is being overwritten here by a layer above.');
 	} else {
		if ($layout['head_html'] === null) {
			$adminToolbar_buttons_head['css_class'] = 'head_slot_empty';
	 		$adminToolbar_buttons_head['tooltip'] .= ze\admin::phrase('This layer is empty.');
		} else {
			$adminToolbar_buttons_head['css_class'] = 'head_slot_full';
	 		$adminToolbar_buttons_head['tooltip'] .= ze\admin::phrase('This layer is populated.');
		}
 	}
 	if ($layout['head_visitor_only']) {
 		$adminToolbar_buttons_head['tooltip'] .= '<br/>'. ze\admin::phrase('This layer is not output in admin mode.');
 	}
 	
 	
 	
 	$adminToolbar_buttons_foot = &$adminToolbar['sections']['slot_controls']['buttons']['layout_foot'];
	if(!isset($adminToolbar_buttons_foot['tooltip'])) {
		$adminToolbar_buttons_foot['tooltip'] = '';
	}
 	if ($version['foot_overwrite']) {
		if ($layout['foot_html'] === null) {
			$adminToolbar_buttons_foot['css_class'] = 'foot_slot_empty_overwritten';
	 		$adminToolbar_buttons_foot['tooltip'] .= ze\admin::phrase('This layer is empty.');
		} else {
			$adminToolbar_buttons_foot['css_class'] = 'foot_slot_full_overwritten';
	 		$adminToolbar_buttons_foot['tooltip'] .= ze\admin::phrase('This layer is populated.');
		}
 		$adminToolbar_buttons_foot['tooltip'] .= '<br/>'. ze\admin::phrase('This layer is being overwritten here by a layer above.');
 	} else {
		if ($layout['foot_html'] === null) {
			$adminToolbar_buttons_foot['css_class'] = 'foot_slot_empty';
	 		$adminToolbar_buttons_foot['tooltip'] .= ze\admin::phrase('This layer is empty.');
		} else {
			$adminToolbar_buttons_foot['css_class'] = 'foot_slot_full';
	 		$adminToolbar_buttons_foot['tooltip'] .= ze\admin::phrase('This layer is populated.');
		}
 	}
 	if ($layout['foot_visitor_only']) {
 		$adminToolbar_buttons_foot['tooltip'] .= '<br/>'. ze\admin::phrase('This layer is not output in admin mode.');
 	}
}





if (isset($adminToolbar['sections']['primary_menu_node'])) {
	
	//Content that is not in the Menu
	if (empty($menuItems)) {
		if (!empty($adminToolbar['toolbars']['menu1'])
		 && !empty($adminToolbar['sections']['no_menu_nodes'])) {
			$adminToolbar['sections']['menu1'] = $adminToolbar['sections']['no_menu_nodes'];
			$adminToolbar['toolbars']['menu1']['warning_icon'] = 'zenario_link_status zenario_link_status__menu_warning';
			$adminToolbar['toolbars']['menu1']['tooltip'] .= '<br/>'. ze\admin::phrase('
				This content item is not in the menu!<br/>
				Click the "Attach content item to menu" button to attach the item to the menu.
			');
		}
	//Content with at least one Menu Node
	} else {
		
		//For each Menu Node, create a copy of the Menu Section
		$primary = true;
		$numberOfMenuItems = 0;
		foreach ($menuItems as $i => &$menuItem) {
			++$numberOfMenuItems;
			
			//Start numbering Menu Nodes from 1, not from 0
			++$i;
			
			if ($i > 1 && isset($adminToolbar['toolbars']['menu_secondary'])) {
				//Add extra tabs for each secondary Menu Node
				$adminToolbar['toolbars']['menu'. $i] = $adminToolbar['toolbars']['menu_secondary'];
				$adminToolbar['toolbars']['menu'. $i]['ord'] .= '.'. str_pad($i, 3, '0', STR_PAD_LEFT);
				$adminToolbar['toolbars']['menu'. $i]['label'] = $i;
			
				if ($menuItem['name'] === null) {
					$adminToolbar['toolbars']['menu'. $i]['warning_icon'] = 'zenario_link_status zenario_link_status__menu_warning';
					$adminToolbar['toolbars']['menu'. $i]['tooltip'] .= '<br/>'. ze\admin::phrase('Warning: text of menu node is missing in this language.');
				}
				
				if (isset($adminToolbar['sections']['menu'. $i]['buttons']['delete'])) {
					if ($childCount = ze\row::count('menu_nodes', ['parent_id' => $menuItem['id']])) {
						$adminToolbar['sections']['menu'. $i]['buttons']['delete']['ajax']['confirm']['message'] .=
							"\n\n".
							ze\admin::phrase('Note, it has [[children]] child node(s)! These, and any further child nodes below them, will be deleted.',
								['children' => $childCount]);
					}
				}
				
				$adminToolbar['sections']['menu'. $i] = $adminToolbar['sections']['secondary_menu_node'];
			
			} else {
				if ($menuItem['name'] === null) {
					$adminToolbar['toolbars']['menu1']['warning_icon'] = 'zenario_link_status zenario_link_status__menu_warning';
					$adminToolbar['toolbars']['menu1']['tooltip'] .= '<br/>'. ze\admin::phrase('Warning: text of menu node missing in this language.');
				}
				
				$adminToolbar['sections']['menu1'] = $adminToolbar['sections']['primary_menu_node'];
			}
			
			
			if (!empty($adminToolbar['sections']['menu'. $i]['buttons'])) {
				foreach ($adminToolbar['sections']['menu'. $i]['buttons'] as $tagName => &$button) {
					if (is_array($button)) {
						foreach (['request', 'key'] as $request) {
							foreach (['admin_box', 'ajax', 'pick_items'] as $action) {
								if (isset($button[$action][$request]['mID'])) {
									$button[$action][$request]['mID'] = $menuItem['id'];
								}
								if (isset($button[$action][$request]['languageId'])) {
									$button[$action][$request]['languageId'] = ze::$langId;
								}
							}
						}
					}
				}
			}
			unset($button);
			
			
			//Get some information on this Menu Node's position/path
			$level = ze\menuAdm::level($menuItem['id']);
			$parent = $menuItem;
			$menuItem['path'] = ze\menuAdm::path($menuItem['id'], ze::$langId);
			
			//Add a fake button with the path information
				//(This will actually be used to display an infobar)
			$adminToolbar['sections']['menu'. $i]['buttons']['menu_section']['label'] = ze\menu::sectionName($menuItem['section_id']);
			$adminToolbar['sections']['menu'. $i]['buttons']['menu_path']['label'] = $menuItem['path'];
			$adminToolbar['sections']['menu'. $i]['buttons']['menu_path']['css_class'] =
				'zenario_at_infobar'.
				($menuItem['parent_id']? '_child' : '_toplevel').
				($primary? '_menuitem' : '_secondary_menuitem').
				(ze\row::exists('menu_nodes', ['parent_id' => $menuItem['id']])? '_with_children' : '_without_children');
		
			
			$mrg = [
				'path' => htmlspecialchars($menuItem['path']),
				'level' => htmlspecialchars($level),
				'section' => htmlspecialchars(ze\menu::sectionName($menuItem['section_id']))];
			
			foreach (['edit_menu_item', 'edit_menu_text', 'view_menu_node_in_sk'] as $button) {
				if (isset($adminToolbar['sections']['menu'. $i]['buttons'][$button]['tooltip'])) {
					$adminToolbar['sections']['menu'. $i]['buttons'][$button]['tooltip'] .=
						'|'. 
						($primary?
								ze\admin::phrase('Primary Menu Node')
							:	ze\admin::phrase('Secondary Menu Node')).
						ze\admin::phrase('<br/>Section: [[section]]<br/>Path: [[path]] (Level [[level]])', $mrg);
				}
			}
			
			$menuLink = ze\menuAdm::organizerLink($menuItem['id'], ze::$langId);
			if (isset($adminToolbar['sections']['menu'. $i]['buttons']['view_menu_node_in_sk']['organizer_quick'])) {
				$adminToolbar['sections']['menu'. $i]['buttons']['view_menu_node_in_sk']['organizer_quick']['path'] = $menuLink;
			}
			if ($primary) {
				$adminToolbar['meta_info']['menu_organizer_path'] = $menuLink;
			}
			
			//Check to see if this menu node has any images, and add info on each
			$adminToolbar['sections']['menu'. $i]['images'] = [
				'image' => $menuItem['image_id'],
				'rollover_image' => $menuItem['rollover_image_id']
			];
			if (ze\module::inc('zenario_promo_menu')) {
				$adminToolbar['sections']['menu'. $i]['images']['feature_image'] =
					zenario_promo_menu::getFeatureImageId($menuItem['id']);
			}
			
			foreach ($adminToolbar['sections']['menu'. $i]['images'] as &$image) {
				if ($image) {
					if ($image = ze\row::get('files', ['id', 'usage', 'checksum'], $image)) {
						$image['url'] = ze\link::absolute(). 'zenario/file.php?usage='. $image['usage']. '&c='. $image['checksum']. '&og=1';
					}
				}
			}
			
			$primary = false;
		}
		
		//If there is only one Menu Node left for this content item, warn that removing it will cause the
		//content item to be detached.
		if (isset($i) && $i ==1 && isset($adminToolbar['sections']['menu'. $i]['buttons']['detach'])) {
			$adminToolbar['sections']['menu'. $i]['buttons']['detach']['ajax']['confirm']['message'] =
				$adminToolbar['sections']['menu'. $i]['buttons']['detach']['ajax']['confirm']['message__orphaned'];
		}
	}
}

unset($adminToolbar['toolbars']['menu_secondary']);
unset($adminToolbar['sections']['no_menu_nodes']);
unset($adminToolbar['sections']['primary_menu_node']);
unset($adminToolbar['sections']['secondary_menu_node']);



if (isset($adminToolbar['sections']['create'])) {
	
	// Create a 'create button' for each content type in the order HTML, News, Events, Others dropdown (Alphabetical)
	$ord = 3;
	foreach (ze\content::getContentTypes(false, true) as $contentTypeId => $contentType) {
		if (ze\priv::check('_PRIV_CREATE_FIRST_DRAFT', false, $contentTypeId)) {
			$button = [
				'label' => $contentType['content_type_name_en'],
				'css_class' => 'zenario_create_a_new',
				'appears_in_toolbars' => [
					'create' => true
				],
				'admin_box' => [
					'path' => 'zenario_content',
					'key' => [
						'id' => '',
						'cID' => '',
						'cType' => $contentTypeId,
						'target_cType' => $contentTypeId,
						'create_from_toolbar' => 1,
						'from_cID' => ze::$cID,
						'from_cType' => ze::$cType,
						'target_language_id' => ze::$langId
					]
				]
			];
		
			switch ($contentTypeId) {
				case 'html':
					$button['ord'] = 1;
					break;
				case 'news':
					$button['ord'] = 2;
					break;
				case 'event':
					$button['ord'] = 3;
					break;
				default:
					$button['ord'] = ++$ord;
			}
		
			$adminToolbar['sections']['create']['buttons'][$contentTypeId] = $button;
		}
	}
}




//
//Add information for the status icons at the top right
//

//Set labels and tooltips
$mrg = [
	'tagId' => $tagId,
	'cID' => ze::$cID,
	'cType' => ze::$cType,
	'cVersion' => ze::$cVersion,
	'cType_name' => htmlspecialchars(ze\content::getContentTypeName(ze::$cType)),
	'title' => htmlspecialchars(ze::$pageTitle),
	'alias' => htmlspecialchars(ze::$alias),
	'lang' => ze\lang::name(ze::$langId),
	'wordcount' => (int) ze\row::get('content_cache', 'text_wordcount', ['content_id' => ze::$cID, 'content_type' => ze::$cType, 'content_version' => ze::$cVersion])
];

if (ze::$cVersion < ze::$adminVersion
 || ze::$cVersion < ze::$visitorVersion) {
	$mrg['status'] = 'archived';
} elseif (ze::$cVersion == ze::$visitorVersion) {
	$mrg['status'] = str_replace('_with_draft', '', ze::$status);
} else {
	$mrg['status'] = str_replace('_with_draft', ' with draft', ze::$status);
}

$adminToolbar['sections']['icons']['buttons']['tag_id']['label'] = $tagId;
$adminToolbar['sections']['icons']['buttons']['tag_id']['tooltip'] =
	ze\admin::phrase('Content Type: [[cType_name]]<br/>Tag Id: [[cType]]_[[cID]]', $mrg);

if (ze\content::isSpecialPage(ze::$cID, ze::$cType)) {
	if (isset($allowHide) && !$allowHide) {
		if (isset($allowTrash) && !$allowTrash) {
			$adminToolbar['sections']['icons']['buttons']['tag_id']['tooltip'] .= ze\admin::phrase('<br/>Special page - cannot be trashed or hidden');
		} else {
			$adminToolbar['sections']['icons']['buttons']['tag_id']['tooltip'] .= ze\admin::phrase('<br/>Special page - cannot be hidden');
		}
	} else {
		if (isset($allowTrash) && !$allowTrash) {
			$adminToolbar['sections']['icons']['buttons']['tag_id']['tooltip'] .= ze\admin::phrase('<br/>Special page - cannot be trashed');
		} else {
			$adminToolbar['sections']['icons']['buttons']['tag_id']['tooltip'] .= ze\admin::phrase('<br/>Special page');
		}
	}
	
	if (empty($adminToolbar['sections']['icons']['buttons']['tag_id']['css_class'])) {
		$adminToolbar['sections']['icons']['buttons']['tag_id']['css_class'] = '';
	}
	$adminToolbar['sections']['icons']['buttons']['tag_id']['css_class'] .= ' zenario_at_icon_tag_id_special_page';
}

$adminToolbar['meta_info']['title'] = ze::$pageTitle;

if ($isMultilingual) {
	$adminToolbar['sections']['icons']['buttons']['title']['tooltip'] =
		ze\admin::phrase('Title: [[title]]<hr/>Content item ID: [[tagId]]<br/>Version: [[cVersion]] ([[status]])<br/>Alias: [[alias]]<br/>Word count: [[wordcount]]<br/>Language: [[lang]]', $mrg);
} else {
	$adminToolbar['sections']['icons']['buttons']['title']['tooltip'] =
		ze\admin::phrase('Title: [[title]]<hr/>Content item ID: [[tagId]]<br/>Version: [[cVersion]] ([[status]])<br/>Alias: [[alias]]<br/>Word count: [[wordcount]]', $mrg);
}

// Language
if (!$isMultilingual) {
	unset($adminToolbar['sections']['icons']['buttons']['language_id']);
} else {
	$adminToolbar['sections']['icons']['buttons']['language_id']['label'] = ze::$langId;
	$adminToolbar['sections']['icons']['buttons']['language_id']['tooltip'] = ze\admin::phrase('Language: [[lang]]', $mrg);
}

// Alias
if (ze::$alias) {
	unset($adminToolbar['sections']['icons']['buttons']['no_alias']);
} else {
	unset($adminToolbar['sections']['icons']['buttons']['go_to_alias']);
	$adminToolbar['sections']['icons']['buttons']['alias_dropdown']['css_class'] =
		'zenario_at_icon_alias zenario_at_icon_no_alias';
}




$layoutLabel = 'L';
if (ze::$layoutId < 10) {
	$layoutLabel .= '0';
}
$layoutLabel .= ze::$layoutId;
$adminToolbar['sections']['icons']['buttons']['layout_id']['label'] = $layoutLabel;
$sql = '
	SELECT 
		COUNT(DISTINCT c.tag_id) AS item_count, 
		l.name, l.status, ct.content_type_name_en AS default_layout_for_ctype
	FROM '.DB_PREFIX.'content_item_versions v
	INNER JOIN '.DB_PREFIX.'layouts l
		ON v.layout_id = l.layout_id
	INNER JOIN '.DB_PREFIX.'content_items c
		ON (v.version = c.admin_version) AND (v.tag_id = c.tag_id)
	LEFT JOIN ' . DB_PREFIX . 'content_types ct
		ON ct.default_layout_id = l.layout_id
	WHERE v.layout_id = '.(int)ze::$layoutId. '
	AND c.status NOT IN ("trashed", "deleted")';
$result = ze\sql::select($sql);
$layoutDetails = ze\sql::fetchAssoc($result);
$layoutName = $layoutDetails['name'];
$layoutItemCount = $layoutDetails['item_count'];

if ($layoutDetails['status'] == 'active') {
	$layoutStatus = '';
} elseif ($layoutDetails['status'] == 'suspended') {
	$layoutStatus = 'Layout is archived';
}

$isDefaultForAContentType = $layoutDetails['default_layout_for_ctype'] ? 'Default layout for content type ' . $layoutDetails['default_layout_for_ctype'] : '';

$adminToolbar['sections']['icons']['buttons']['layout_id']['tooltip'] = 
	'Layout '.$layoutLabel.'<br />'.$layoutName.'<br />'.$layoutItemCount.' content item(s) use this layout<br /> '.$layoutStatus.'<br />'.$isDefaultForAContentType;
	
$adminToolbar['sections']['icons']['buttons']['layout_id']['css_class'] .= ' layout_status_' . $layoutDetails['status'];

$visitorURL = ze\link::toItem(
	$cID, $cType, $fullPath = true, $request = '', ze::$alias,
	$autoAddImportantRequests = false, $forceAliasInAdminMode = true);

if (isset($adminToolbar['sections']['icons']['buttons']['copy_url'])) {
	$adminToolbar['sections']['icons']['buttons']['copy_url']['onclick'] =
		//Attempt to copy the cannonical URL to the clipboard when the visitor presses this button
		'zenarioA.copy("'. ze\escape::js($visitorURL). '");'.
		//Small little hack here:
			//After the URL is copy/pasted, the dropdown stays open which is counter-intuative.
			//However the dropdown is powered by pure CSS and there's no way to close it using JavaScript.
			//So as a workaround, redraw the admin toolbar with the dropdown closed.
		'if (!zenarioA.checkSlotsBeingEdited()) zenarioAT.draw();';
}
if (isset($adminToolbar['sections']['icons']['buttons']['go_to_alias'])) {
	$adminToolbar['sections']['icons']['buttons']['go_to_alias']['label'] = ze\admin::phrase('Go to content item via its alias "[[alias]]"', ['alias' => (ze::$alias)]);
	$adminToolbar['sections']['icons']['buttons']['go_to_alias']['frontend_link'] = $visitorURL;
} else {
	$adminToolbar['sections']['icons']['buttons']['no_alias']['label'] = ze\admin::phrase('This content item does not have an alias');
	unset($adminToolbar['sections']['icons']['buttons']['no_alias']['tooltip']);
	$adminToolbar['sections']['icons']['buttons']['alias']['label'] = ze\admin::phrase('Set an alias');
}


if (isset($adminToolbar['sections']['icons']['buttons']['no_alias'])) {
	$adminToolbar['sections']['icons']['buttons']['no_alias']['frontend_link'] = $visitorURL;
}


//here!
	//Right now: I need to order these by version
	//I need to make up to 3 version blobs, add CSS classes for the statuses, and put the info on each, or hide each if they're not needed

//Show version information on all relevant versions
$showVersions = [];
$showVersions[$cVersion] = true;
$showVersions[ze::$adminVersion] = true;
$showVersions[ze::$visitorVersion] = true;
$showVersions[ze::$adminVersion - 1] = true;
ksort($showVersions);

$i = 0;
foreach ($showVersions as $showVersion => $dummy) {
	
	if ($showVersion && ($v = ze\row::get('content_item_versions', true, ['id' => $cID, 'type' => $cType, 'version' => $showVersion]))) {
		
		$tuixId = 'version_'. ++$i;
		
		switch (ze\contentAdm::versionStatus($v['version'], ze::$visitorVersion, ze::$adminVersion, ze::$status)) {
			case 'draft':
				if ($versionChanged = ze\contentAdm::checkIfVersionChanged($v)) {
					$labelPhrase = 'v[[version]] (draft)';
					$tooltipPhrase = 'Version [[version]], Draft modified by [[name]], [[time]] [[date]]';
					$cssClass = 'zenario_at_icon_version_draft';
					
					if ($v['last_modified_datetime']) {
						$lastAction = $v['last_modified_datetime'];
						$lastActionBy = $v['last_author_id'];
					} else {
						$lastAction = $v['created_datetime'];
						$lastActionBy = $v['creating_author_id'];
					}

				} else {
					$labelPhrase = 'v[[version]] (draft)';
					
					if ($versionChanged === null) {
						$tooltipPhrase = 'Version [[version]], Draft created by [[name]], [[time]] [[date]], no changes made';
					} else {
						$tooltipPhrase = 'Version [[version]], Draft created by [[name]], [[time]] [[date]]';
					}
					
					$cssClass = 'zenario_at_icon_version_draft';
					$lastAction = $v['created_datetime'];
					$lastActionBy = $v['creating_author_id'];
				}
				break;
			
			case 'published':
				$labelPhrase = 'v[[version]] (published)';
				$tooltipPhrase = 'Version [[version]], published by [[name]], [[time]] [[date]]';
				$cssClass = 'zenario_at_icon_version_published';
				$lastAction = $v['published_datetime'];
				$lastActionBy = $v['publisher_id'];
				break;

			case 'hidden':
				$labelPhrase = 'v[[version]] (hidden)';
				$tooltipPhrase = 'Version [[version]], hidden by [[name]], [[time]] [[date]]';
				$cssClass = 'zenario_at_icon_version_hidden';
				
				if (!is_null($v['concealed_datetime'])) {
					$lastAction = $v['concealed_datetime'];
				} elseif (!is_null($v['last_modified_datetime'])) {
					$lastAction = $v['last_modified_datetime'];
				} else {
					$lastAction = $v['created_datetime'];
				}
				
				if ($v['concealer_id'] != 0) {
					$lastActionBy = $v['concealer_id'];
				} elseif ($v['last_author_id'] != 0) {
					$lastActionBy = $v['last_author_id'];
				} else {
					$lastActionBy = $v['creating_author_id'];
				}
				
				break;
	
			case 'trashed':
				$labelPhrase = 'v[[version]] (trashed)';
				$tooltipPhrase = 'Version [[version]], trashed by [[name]], [[time]] [[date]]';
				$cssClass = 'zenario_at_icon_version_trashed';
				
				if (!is_null($v['concealed_datetime'])) {
					$lastAction = $v['concealed_datetime'];
				} elseif (!is_null($v['last_modified_datetime'])) {
					$lastAction = $v['last_modified_datetime'];
				} else {
					$lastAction = $v['created_datetime'];
				}
				
				if ($v['concealer_id'] != 0) {
					$lastActionBy = $v['concealer_id'];
				} elseif ($v['last_author_id'] != 0) {
					$lastActionBy = $v['last_author_id'];
				} else {
					$lastActionBy = $v['creating_author_id'];
				}
				
				break;
	
			case 'archived':
				$labelPhrase = 'v[[version]] (archived)';
				$tooltipPhrase = 'Version [[version]], archived by [[name]], [[time]] [[date]]';
				$cssClass = 'zenario_at_icon_version_archived';
				
				if (!is_null($v['concealed_datetime'])) {
					$lastAction = $v['concealed_datetime'];
				} elseif (!is_null($v['last_modified_datetime'])) {
					$lastAction = $v['last_modified_datetime'];
				} else {
					$lastAction = $v['created_datetime'];
				}
				
				if ($v['concealer_id'] != 0) {
					$lastActionBy = $v['concealer_id'];
				} elseif ($v['last_author_id'] != 0) {
					$lastActionBy = $v['last_author_id'];
				} else {
					$lastActionBy = $v['creating_author_id'];
				}
				
				break;
		}
	
		$mrg = [];
		$mrg['version'] = $v['version'];
		$mrg['name'] = htmlspecialchars(ze\admin::formatName($lastActionBy));
		$mrg['time'] = ze\date::formatTime($lastAction, ze::setting('vis_time_format'), true);
		$mrg['date'] = ze\admin::formatDate($lastAction, ze::setting('vis_date_format_med'), true);

		if ($mrg['date'] == ze\admin::formatDate(ze\date::now(), ze::setting('vis_date_format_med'))) {
			$mrg['date'] = ze\admin::phrase('today');
		}
		
		//Only show the status for the most recent version
		if ($v['version'] != ze::$adminVersion) {
			$labelPhrase = 'v[[version]]';
		}

		$adminToolbar['sections']['icons']['buttons'][$tuixId]['hidden'] = false;
		$adminToolbar['sections']['icons']['buttons'][$tuixId]['label'] = ze\admin::phrase($labelPhrase, $mrg);
		$adminToolbar['sections']['icons']['buttons'][$tuixId]['tooltip'] = ze\admin::phrase($tooltipPhrase, $mrg);
		$adminToolbar['sections']['icons']['buttons'][$tuixId]['css_class'] .= ' '. $cssClass;
		$adminToolbar['sections']['icons']['buttons'][$tuixId]['frontend_link'] = ze\link::toItem($cID, $cType, false, 'cVersion='. $v['version']);
	
		if ($v['version'] == $cVersion) {
			$adminToolbar['sections']['icons']['buttons'][$tuixId]['css_class'] .= ' zenario_at_icon_version_in_view';
			$adminToolbar['meta_info']['version'] = $adminToolbar['sections']['icons']['buttons'][$tuixId]['tooltip'];
		}
	}
}

//Content item is categorised
if (ze\row::exists('category_item_link', ['equiv_id' => $content['equiv_id'], 'content_type' => $cType])) {
	unset($adminToolbar['sections']['icons']['buttons']['item_categories_none']);
} else {
	unset($adminToolbar['sections']['icons']['buttons']['item_categories_some']);
}

if (isset($adminToolbar['sections']['icons']['buttons']['item_categories_some'])) {
	//If this item is in categories, list them
	$sql = "
		SELECT c.name
		FROM ". DB_PREFIX. "category_item_link AS cil
		LEFT JOIN ". DB_PREFIX. "categories AS c
		   ON cil.category_id = c.id
		WHERE cil.equiv_id = ". (int) $content['equiv_id']. "
		  AND cil.content_type = '" . ze\escape::asciiInSQL($cType). "'";
	
	$i = 0;
	if ($sql && ($result = ze\sql::select($sql))) {
		while ($row = ze\sql::fetchAssoc($result)) {
			$adminToolbar['sections']['icons']['buttons']['item_categories_some']['tooltip'] .=
				($i++? ', ' : ''). htmlspecialchars($row['name']);
		}
	}
}


//Content item type has categories enabled
if (ze\row::exists('content_types', ['enable_categories' => 0, 'content_type_id' => $content['type']])) {
	unset($adminToolbar['sections']['edit']['buttons']['categories']);
	unset($adminToolbar['sections']['icons']['buttons']['item_categories_some']);
	unset($adminToolbar['sections']['icons']['buttons']['item_categories_none']);
}



//Get the slots on this Layout, and add a button for each
$ord = 2000;
$lookForSlots = ['layout_id' => ze::$layoutId];
foreach(ze\row::getAssocs('layout_slot_link', ['ord', 'slot_name'], $lookForSlots, ['ord', 'slot_name']) as $slot) {
	$adminToolbar['sections']['slot_controls']['buttons']['slot_'. $slot['slot_name']] =
		[
			'ord' => ++$ord,
			'parent' => 'slot_control_dropdown',
			'css_class' => 'zenario_atSlotControl',
			'label' => $slot['slot_name'],
			
			//Little hack - only show the slot control if the slot is visible and the opacity is over 0.3.
			//(N.b. empty slots are 0.4, slots from other tabs are 0.2)
			//'visible_if' => 'var plgslt = $("#plgslt_'. ze\escape::js($slot['slot_name']). '"); plgslt.is(":visible") && 1*plgslt.css("opacity") > .3;',
			'disabled_if' => 'zenarioAT.slotDisabled("'. ze\escape::js($slot['slot_name']). '");',
			'onmouseover' => 'return zenarioA.openSlotControls(this, this, "'. ze\escape::js($slot['slot_name']). '", true);',
			'onclick' => 'return false;'
		];
}


$linkStatus = false;        
switch (ze::$status) {
	case 'published':
	case 'published_with_draft':
		$linkStatus = ze::$status;
		
		$perms = ze\content::getShowableContent($content, $chain, $version, $cID, $cType, $cVersion, $checkRequestVars = false, $adminMode = true, $adminsSee400Errors = true);
	
		if ($perms === ZENARIO_401_NOT_LOGGED_IN) {
			$linkStatus .= '_401';
	
		} elseif (!$perms) {
			$linkStatus .= '_403';
		}
		break;

	case 'first_draft':
	case 'hidden_with_draft':
	case 'hidden':
		$linkStatus = 'hidden';
		break;
}

if ($linkStatus) {
	$adminToolbar['toolbars']['preview']['warning_icon'] = 'zenario_link_status zenario_link_status__'. $linkStatus;
}



//Handle the case where there's not enough width on the screen to show the delete draft and publish buttons
//Also add a copy of them to the "Actions" dropdown on the edit tab
if (isset($adminToolbar['sections']['edit']['buttons'])) {
	if ($button = $adminToolbar['sections']['status_button']['buttons']['start_editing'] ?? false) {
		unset($button['tooltip'], $button['appears_in_toolbars']);
		$button['parent'] = 'action_dropdown';
		$button['ord'] = $adminToolbar['sections']['edit']['buttons']['start_editing_pos']['ord'] ?? 0.2;
		$adminToolbar['sections']['edit']['buttons']['start_editing'] = $button;
	}
	if ($button = $adminToolbar['sections']['status_button']['buttons']['cant_start_editing'] ?? false) {
		unset($button['tooltip'], $button['appears_in_toolbars']);
		$button['parent'] = 'action_dropdown';
		$button['ord'] = $adminToolbar['sections']['edit']['buttons']['cant_start_editing_pos']['ord'] ?? 0.3;
		$adminToolbar['sections']['edit']['buttons']['cant_start_editing'] = $button;
	}
	if ($button = $adminToolbar['sections']['status_button']['buttons']['hide_content'] ?? false) {
		unset($button['tooltip'], $button['appears_in_toolbars']);
		$button['parent'] = 'action_dropdown';
		$button['ord'] = $adminToolbar['sections']['edit']['buttons']['hide_content_pos']['ord'] ?? 994;
		$adminToolbar['sections']['edit']['buttons']['hide_content'] = $button;
	}
	if ($button = $adminToolbar['sections']['status_button']['buttons']['republish'] ?? false) {
		unset($button['tooltip'], $button['appears_in_toolbars']);
		$button['parent'] = 'action_dropdown';
		$button['ord'] = $adminToolbar['sections']['edit']['buttons']['unhide_pos']['ord'] ?? 995;
		$adminToolbar['sections']['edit']['buttons']['republish'] = $button;
	}
	if ($button = $adminToolbar['sections']['status_button']['buttons']['trash_content'] ?? false) {
		unset($button['tooltip'], $button['appears_in_toolbars']);
		$button['parent'] = 'action_dropdown';
		$button['ord'] = $adminToolbar['sections']['edit']['buttons']['trash_content_pos']['ord'] ?? 996;
		$adminToolbar['sections']['edit']['buttons']['trash_content'] = $button;
	}
	if ($button = $adminToolbar['sections']['status_button']['buttons']['redraft'] ?? false) {
		unset($button['tooltip'], $button['appears_in_toolbars']);
		$button['parent'] = 'action_dropdown';
		$button['ord'] = $adminToolbar['sections']['edit']['buttons']['redraft_pos']['ord'] ?? 997;
		$adminToolbar['sections']['edit']['buttons']['redraft'] = $button;
	}
	if ($button = $adminToolbar['sections']['status_button']['buttons']['delete_draft'] ?? false) {
		unset($button['tooltip'], $button['appears_in_toolbars']);
		$button['ord'] = 998;
		$button['parent'] = 'action_dropdown';
		$adminToolbar['sections']['edit']['buttons']['delete_draft'] = $button;
	}
	if ($button = $adminToolbar['sections']['status_button']['buttons']['publish'] ?? false) {
		unset($button['tooltip'], $button['appears_in_toolbars']);
		$button['ord'] = 999;
		$button['parent'] = 'action_dropdown';
		$adminToolbar['sections']['edit']['buttons']['publish'] = $button;
	}
}
unset($adminToolbar['sections']['edit']['buttons']['start_editing_pos']);
unset($adminToolbar['sections']['edit']['buttons']['cant_start_editing_pos']);
unset($adminToolbar['sections']['edit']['buttons']['hide_content_pos']);
unset($adminToolbar['sections']['edit']['buttons']['unhide_pos']);
unset($adminToolbar['sections']['edit']['buttons']['trash_content_pos']);
unset($adminToolbar['sections']['edit']['buttons']['redraft_pos']);
