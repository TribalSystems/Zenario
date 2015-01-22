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


$content = getRow('content', true, array('id' => $cID, 'type' => $cType));
$chain = getRow('translation_chains', true, array('equiv_id' => $content['equiv_id'], 'type' => $cType));
$version = getRow('versions', true, array('id' => $cID, 'type' => $cType, 'version' => $cVersion));
$menuItems = getMenuItemFromContent($cID, $cType, true, false, true);


if (!$content || !$version) {
	exit;
} else {
	setShowableContent($content, $version);
	$templateDetails = getTemplateDetails(cms_core::$layoutId);
	$templateDetails['usage'] = checkTemplateUsage(cms_core::$layoutId);
	
	//Set the link to Grid Maker
	if (isset($adminToolbar['sections']['template']['buttons']['edit_grid'])) {
		//To Do: only set the link if this Layout was actually made using grid maker
			//(Maybe you could check to see if a grid css file exists?)
		if (true) {
			$adminToolbar['sections']['template']['buttons']['edit_grid']['popout']['href'] .= '&id='. cms_core::$layoutId;
		} else {
			unset($adminToolbar['sections']['template']['buttons']['edit_grid']);
		}
	}
	
	if (isset($adminToolbar['sections']['template']['buttons']['settings']['admin_box']['key']['id'])) {
		$adminToolbar['sections']['template']['buttons']['settings']['admin_box']['key']['id'] = cms_core::$layoutId;
	}
}



if (cms_core::$status == 'trashed' && $cVersion == cms_core::$adminVersion) {
	unset($adminToolbar['sections']['edit']['buttons']['rollback_item']);
	unset($adminToolbar['sections']['edit']['buttons']['no_rollback_item']);
	unset($adminToolbar['sections']['edit']['buttons']['cant_start_editing']);

} else {
	unset($adminToolbar['sections']['edit']['buttons']['redraft']);
}

if (cms_core::$status == 'trashed' || ($cVersion < cms_core::$adminVersion && (!cms_core::$visitorVersion || $cVersion < cms_core::$visitorVersion))) {
	unset($adminToolbar['toolbars']['menu1']);
	unset($adminToolbar['sections']['menu1']);
	unset($adminToolbar['sections']['edit']['buttons']['start_editing']);
	unset($adminToolbar['sections']['edit']['buttons']['cant_start_editing']);
}

if (cms_core::$status == 'trashed' || $cVersion != cms_core::$adminVersion) {
	unset($adminToolbar['toolbars']['edit']);
	unset($adminToolbar['toolbars']['edit_disabled']);
} else {
	unset($adminToolbar['toolbars']['rollback']);
	unset($adminToolbar['sections']['edit']['buttons']['rollback_item']);
}

//Most recent Version
if ($cVersion == cms_core::$adminVersion) {
	unset($adminToolbar['sections']['edit']['buttons']['no_rollback_item']);
	unset($adminToolbar['sections']['edit']['buttons']['cant_start_editing']);

} else {
	unset($adminToolbar['toolbars']['edit']);
	unset($adminToolbar['toolbars']['template']);
	unset($adminToolbar['sections']['edit']['buttons']['head']);
	unset($adminToolbar['sections']['edit']['buttons']['foot']);
	unset($adminToolbar['sections']['edit']['buttons']['delete_draft']);
	unset($adminToolbar['sections']['edit']['buttons']['hide_content']);
	unset($adminToolbar['sections']['edit']['buttons']['trash_content']);
	unset($adminToolbar['sections']['edit']['buttons']['create_draft_by_copying']);
	unset($adminToolbar['sections']['edit']['buttons']['create_draft_by_overwriting']);
	unset($adminToolbar['sections']['slot_wand']['buttons']['slot_wand_on']);
	unset($adminToolbar['sections']['slot_wand']['buttons']['slot_wand_off']);
}



//Hidden Version
if ($cVersion == cms_core::$adminVersion && cms_core::$status == 'hidden') {
	foreach (array('edit', 'edit_disabled') as $toolbar) {
		if (isset($adminToolbar['toolbars'][$toolbar])) {
			$adminToolbar['toolbars'][$toolbar]['css_class'] = 'zenario_toolbar_warning';
			$adminToolbar['toolbars'][$toolbar]['tooltip'] .= '<br/>'. adminPhrase('Warning: this Content Item is Hidden');
		}
	}

} else {
	unset($adminToolbar['sections']['edit']['buttons']['unhide']);
}

//Draft Version
if ($cVersion == cms_core::$adminVersion && cms_core::$isDraft) {
	
	foreach (array('edit', 'edit_disabled') as $toolbar) {
		if (isset($adminToolbar['toolbars'][$toolbar])) {
			$adminToolbar['toolbars'][$toolbar]['css_class'] = 'zenario_toolbar_warning';
			$adminToolbar['toolbars'][$toolbar]['tooltip'] .= '<br/>'. adminPhrase('Warning: this Content Item is a Draft');
		}
	}
	
	
	$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_orange zenario_section_dark_text';
	
	if ($cVersion == 1) {
		$adminToolbar['sections']['edit']['label'] = adminPhrase('First Draft');
	
	} else {
		$adminToolbar['sections']['edit']['label'] = adminPhrase('Draft');
	}

} else {
	unset($adminToolbar['sections']['edit']['buttons']['publish']);
	unset($adminToolbar['sections']['edit']['buttons']['delete_draft']);
	

	//Published Version
	if ($cVersion == cms_core::$visitorVersion) {
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_green zenario_section_dark_text';
		$adminToolbar['sections']['edit']['label'] = adminPhrase('Published');
		unset($adminToolbar['sections']['edit']['buttons']['no_rollback_item']);
	
	} else
	if ((cms_core::$status == 'hidden' && $cVersion == cms_core::$adminVersion)
	 || (cms_core::$status == 'hidden_with_draft' && $cVersion == cms_core::$adminVersion - 1)) {
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_grey zenario_section_dark_text';
		$adminToolbar['sections']['edit']['label'] = adminPhrase('Hidden');
		unset($adminToolbar['sections']['edit']['buttons']['start_editing']);
	
	//Trashed Content Items
	} else
	if ((cms_core::$status == 'trashed' && $cVersion == cms_core::$adminVersion)
	 || (cms_core::$status == 'trashed_with_draft' && $cVersion == cms_core::$adminVersion - 1)) {
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_brown zenario_section_dark_text';
		$adminToolbar['sections']['edit']['label'] = adminPhrase('Trashed Item');
	
	//Archived/Previous Versions
	} else {
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_brown zenario_section_dark_text';
		$adminToolbar['sections']['edit']['label'] = adminPhrase('Archived Version');
	}
}


//Content with a draft version
if (cms_core::$isDraft) {
	unset($adminToolbar['sections']['edit']['buttons']['start_editing']);
	unset($adminToolbar['sections']['edit']['buttons']['rollback_item']);
	unset($adminToolbar['sections']['edit']['buttons']['create_draft_by_copying']);
} else {
	unset($adminToolbar['sections']['edit']['buttons']['no_rollback_item']);
	unset($adminToolbar['sections']['edit']['buttons']['cant_start_editing']);
	unset($adminToolbar['sections']['edit']['buttons']['create_draft_by_overwriting']);
}

//The current Admin can edit the Content
if (checkPriv('_PRIV_EDIT_DRAFT', $cID, $cType)) {
	unset($adminToolbar['toolbars']['edit_disabled']);
} else {
	unset($adminToolbar['toolbars']['edit']);
	unset($adminToolbar['sections']['edit']['buttons']['publish']);
}

//Check if deletion is allowed
if (!allowDelete($cID, $cType, cms_core::$status)) {
	unset($adminToolbar['sections']['edit']['buttons']['delete_draft']);
}
if (!allowHide($cID, $cType, cms_core::$status)) {
	unset($adminToolbar['sections']['edit']['buttons']['hide_content']);
}
if (!allowTrash($cID, $cType, cms_core::$status)) {
	unset($adminToolbar['sections']['edit']['buttons']['trash_content']);
}



//Only show locking info on drafts
if (!cms_core::$isDraft) {
	unset($adminToolbar['sections']['edit']['buttons']['lock']);
	unset($adminToolbar['sections']['edit']['buttons']['locked']);
	unset($adminToolbar['sections']['edit']['buttons']['unlock']);
	unset($adminToolbar['sections']['edit']['buttons']['force_open']);

//The Content Item is not locked
} elseif (!$content['lock_owner_id']) {
	$adminToolbar['sections']['edit']['label'] = adminPhrase('Unlocked');
	
	unset($adminToolbar['sections']['edit']['buttons']['locked']);
	unset($adminToolbar['sections']['edit']['buttons']['unlock']);
	unset($adminToolbar['sections']['edit']['buttons']['force_open']);

//The Content Item is locked
} else {
	$mrg = array(
		'name' => htmlspecialchars(formatAdminName($content['lock_owner_id'])),
		'time' => timeDiff(now(), $content['locked_datetime'], 300));
	
	if ($mrg['time'] === true) {
		$mrg['time'] = adminPhrase('< 5 minutes');
	}
	
	//The current Admin has a lock on the Content Item
	if ($content['lock_owner_id'] && $content['lock_owner_id'] == session('admin_userid')) {
		$adminToolbar['sections']['edit']['label'] = adminPhrase('Locked by you');
		
		$adminToolbar['sections']['edit']['buttons']['unlock']['tooltip'] =
			adminPhrase('Locked by you [[time]] ago|Click here to unlock', $mrg);
		
		unset($adminToolbar['sections']['edit']['buttons']['lock']);
		unset($adminToolbar['sections']['edit']['buttons']['locked']);
		unset($adminToolbar['sections']['edit']['buttons']['force_open']);
	
	//The current Admin can remove other's locks
	} elseif (checkPriv('_PRIV_CANCEL_CHECKOUT')) {
		$adminToolbar['sections']['edit']['label'] = adminPhrase('Locked');
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_pink';
		
		$adminToolbar['sections']['edit']['buttons']['force_open']['tooltip'] =
			adminPhrase('Locked by [[name]], [[time]] ago|Click here to force-unlock', $mrg);
		
		if (isset($adminToolbar['toolbars']['edit'])) {
			$adminToolbar['toolbars']['edit']['help'] =
				array(
					'message' => adminPhrase('This content item is locked by another administrator. Certain functions may not be available.'),
					'message_type' => 'warning');
		}
		
		unset($adminToolbar['sections']['edit']['buttons']['lock']);
		unset($adminToolbar['sections']['edit']['buttons']['locked']);
		unset($adminToolbar['sections']['edit']['buttons']['unlock']);
	
	} else {
		$adminToolbar['sections']['edit']['label'] = adminPhrase('Locked');
		$adminToolbar['sections']['edit']['css_class'] = 'zenario_section_pink';
		
		$adminToolbar['sections']['edit']['buttons']['locked']['tooltip'] =
			adminPhrase('Locked by [[name]], [[time]] ago', $mrg);
		
		if (isset($adminToolbar['toolbars']['edit'])) {
			$adminToolbar['toolbars']['edit']['help'] =
				array(
					'message' => adminPhrase('This content item is locked by another administrator. Certain functions may not be available.'),
					'message_type' => 'warning');
		}
		
		unset($adminToolbar['sections']['edit']['buttons']['lock']);
		unset($adminToolbar['sections']['edit']['buttons']['unlock']);
		unset($adminToolbar['sections']['edit']['buttons']['force_open']);
	}
}


if (isset($adminToolbar['sections']['edit']['buttons']['create_draft_by_copying'])) {
	$adminToolbar['sections']['edit']['buttons']['create_draft_by_copying']['pick_items']['path'] = 'zenario__content/nav/content_types/panel/item//'. $cType. '//';
}
if (isset($adminToolbar['sections']['edit']['buttons']['create_draft_by_overwriting'])) {
	$adminToolbar['sections']['edit']['buttons']['create_draft_by_overwriting']['pick_items']['path'] = 'zenario__content/nav/content_types/panel/item//'. $cType. '//';
}


if (isset($adminToolbar['sections']['edit']['buttons']['item_meta_data'])) {
	$adminToolbar['sections']['edit']['buttons']['item_meta_data']['tooltip'] .=
				 adminPhrase('Title:'). ' '. htmlspecialchars(cms_core::$pageTitle).
		'<br/>'. adminPhrase('Language:'). ' '. htmlspecialchars(getLanguageName(cms_core::$langId)).
		'<br/>'. adminPhrase('Description:'). ' '. htmlspecialchars(cms_core::$description).
		'<br/>'. adminPhrase('Keywords:'). ' '. htmlspecialchars(cms_core::$keywords);
}

if (isset($adminToolbar['sections']['edit']['buttons']['item_template'])) {
	$adminToolbar['sections']['edit']['buttons']['item_template']['tooltip'] .=
				 adminPhrase('Template Family:'). ' '. htmlspecialchars(cms_core::$templateFamily).
		'<br/>'. adminPhrase('Template Filename:'). ' '. htmlspecialchars(cms_core::$templateFilename).
		'<br/>'. adminPhrase('Layout:'). ' '. htmlspecialchars($templateDetails['id_and_name']).
		'<br/>'. adminPhrase('Skin:'). ' '. htmlspecialchars(cms_core::$skinName);
}


if (isset($adminToolbar['sections']['edit']['buttons']['view_items_images'])) {
	$adminToolbar['sections']['edit']['buttons']['view_items_images']['organizer_quick']['path'] =
		'zenario__content/nav/content/panel/item_buttons/images//'. $cType. '_'. $cID. '//';
}

if (isset($adminToolbar['sections']['edit']['buttons']['view_slots'])) {
	$adminToolbar['sections']['edit']['buttons']['view_slots']['organizer_quick']['path'] =
		'zenario__content/nav/content/panel/item_buttons/view_slots//'. $cType. '_'. $cID. '//';
}



//Multilingual options
if (getNumLanguages() <= 1) {
	unset($adminToolbar['sections']['edit']['buttons']['view_items_translations']);
	if (isset($adminToolbar['sections']['translations'])) {
		$adminToolbar['sections']['translations']['hidden'] = true;
	}

} else {
	if (isset($adminToolbar['sections']['edit']['buttons']['view_items_translations'])) {
		$adminToolbar['sections']['edit']['buttons']['view_items_translations']['organizer_quick']['path'] =
			'zenario__content/nav/content/panel/item_buttons/zenario_trans__view//'. $cType. '_'. $cID. '//';
	}
	if (isset($adminToolbar['sections']['translations'])) {
		$ord = 0;
		foreach (getLanguages($includeAllLanguages = false, $onlyIncludeLangId = false, $orderByEnglishName = false, $defaultLangFirst = true) as $lang) {
			$ddId = $lang['id']. '_dropdown';
			$gcId = $lang['id']. '_go_or_create';
			
			$adminToolbar['sections']['translations']['buttons'][$ddId] =
				$adminToolbar['sections']['translations']['custom_template_buttons']['dropdown'];
			
			if ($translation = getRow(
				'content',
				array('id', 'type', 'alias', 'status'),
				array('equiv_id' => cms_core::$equivId, 'type' => cms_core::$cType, 'language_id' => $lang['id'])
			)) {
				$adminToolbar['sections']['translations']['buttons'][$ddId]['css_class'] =
					'zenario_at_trans_dropdown zenario_at_trans_dropdown__'. $translation['status'];
				
				if ($translation['id'] == cms_core::$cID
				 && $translation['type'] == cms_core::$cType) {
					$adminToolbar['sections']['translations']['buttons'][$ddId]['css_class'] .= ' zenario_at_trans_dropdown_current';
					
					$adminToolbar['sections']['translations']['buttons'][$gcId] =
						$adminToolbar['sections']['translations']['custom_template_buttons']['this'];
				} else {
					$adminToolbar['sections']['translations']['buttons'][$gcId] =
						$adminToolbar['sections']['translations']['custom_template_buttons']['go'];
				}
				
				$adminToolbar['sections']['translations']['buttons'][$gcId]['frontend_link'] =
					linkToItem($translation['id'], $translation['type'], false, '', $translation['alias']);
			
			} else {
				$adminToolbar['sections']['translations']['buttons'][$ddId]['css_class'] =
					'zenario_at_trans_dropdown zenario_at_trans_dropdown__missing';
				
				$adminToolbar['sections']['translations']['buttons'][$gcId] =
					$adminToolbar['sections']['translations']['custom_template_buttons']['create'];
				
				$adminToolbar['sections']['translations']['buttons'][$gcId]['admin_box']['key']['id'] = $lang['id'];
			}
			
			$adminToolbar['sections']['translations']['buttons'][$ddId]['ord'] = ++$ord;
			$adminToolbar['sections']['translations']['buttons'][$ddId]['label'] =
				adminPhrase($adminToolbar['sections']['translations']['buttons'][$ddId]['label'], $lang);
			
			$adminToolbar['sections']['translations']['buttons'][$gcId]['parent'] = $ddId;
			$adminToolbar['sections']['translations']['buttons'][$gcId]['ord'] = ++$ord;
			$adminToolbar['sections']['translations']['buttons'][$gcId]['label'] =
				adminPhrase($adminToolbar['sections']['translations']['buttons'][$gcId]['label'], $lang);
		}
	}
}







//Set up the version history navigation, including a left arrow, the current item in view and a right arrow, with the correct icons and tooltips on each
if ($cVersion > 1 && checkRowExists('versions', array('id' => $cID, 'type' => $cType, 'version' => $cVersion - 1))) {
	$mrg = array('status' => getContentItemVersionStatus($content, $cVersion - 1));
	$adminToolbar['sections']['history']['buttons']['content_item_left']['css_class'] = getContentItemVersionToolbarIcon($content, $cVersion - 1, 'zenario_at_prev_version_');
	$adminToolbar['sections']['history']['buttons']['content_item_left']['frontend_link'] = indexDotPHP(true). '?cID='. $cID. '&cType='. $cType. '&cVersion='. ($cVersion - 1);
	$adminToolbar['sections']['history']['buttons']['content_item_left']['label'] = adminPhrase('View previous ([[status]])', $mrg);
	$adminToolbar['sections']['history']['buttons']['content_item_left']['tooltip'] =
		adminPhrase('View previous version'). '|'.
		adminPhrase('Version status: [[status]]', $mrg);
	
	unset($adminToolbar['sections']['history']['buttons']['no_content_left']);
} else {
	unset($adminToolbar['sections']['history']['buttons']['content_item_left']);
}


$mrg = array('status' => getContentItemVersionStatus($content, $cVersion));
$adminToolbar['sections']['history']['buttons']['content_item_current']['css_class'] = getContentItemVersionToolbarIcon($content, $cVersion, 'zenario_at_current_version_');
$adminToolbar['sections']['history']['buttons']['content_item_current']['label'] = adminPhrase('This version ([[status]])', $mrg);
$adminToolbar['sections']['history']['buttons']['content_item_current']['tooltip'] =
	adminPhrase('Version [[v]]', array('v' => $cVersion)). '|'.
	adminPhrase('Version status: [[status]]<br/><i>This version is in view</i>', $mrg);

//At the top right of the toolbar, show either a Publish button, or the current status if we can't currently publish
if (isset($adminToolbar['sections']['edit']['buttons']['publish'])
 && isset($adminToolbar['sections']['status_button']['buttons']['publish'])) {
	unset($adminToolbar['sections']['status_button']['buttons']['status_button']);

} else {
	unset($adminToolbar['sections']['status_button']['buttons']['publish']);
	$adminToolbar['sections']['status_button']['buttons']['status_button']['css_class'] .= ' '. getContentItemVersionToolbarIcon($content, $cVersion, 'zenario_at_status_button_');
	$adminToolbar['sections']['status_button']['buttons']['status_button']['label'] = ucwords($mrg['status']);
	$adminToolbar['sections']['status_button']['buttons']['status_button']['tooltip'] =
		adminPhrase('Version [[v]]', array('v' => $cVersion)). '|'.
		adminPhrase('Version status: [[status]]', $mrg);
}


if (checkRowExists('versions', array('id' => $cID, 'type' => $cType, 'version' => $cVersion + 1))) {
	$mrg = array('status' => getContentItemVersionStatus($content, $cVersion + 1));
	$adminToolbar['sections']['history']['buttons']['content_item_right']['css_class'] = getContentItemVersionToolbarIcon($content, $cVersion + 1, 'zenario_at_next_version_');
	$adminToolbar['sections']['history']['buttons']['content_item_right']['frontend_link'] = indexDotPHP(true). '?cID='. $cID. '&cType='. $cType. '&cVersion='. ($cVersion + 1);
	$adminToolbar['sections']['history']['buttons']['content_item_right']['label'] = adminPhrase('Next version ([[status]])', $mrg);
	$adminToolbar['sections']['history']['buttons']['content_item_right']['tooltip'] =
		adminPhrase('View next version'). '|'.
		adminPhrase('Version status: [[status]]', array('status' => $mrg));

	unset($adminToolbar['sections']['history']['buttons']['no_content_right']);

} else {
	unset($adminToolbar['sections']['history']['buttons']['content_item_right']);
}




if (isset($adminToolbar['sections']['edit'])
 || isset($adminToolbar['sections']['template'])) {
 	
 	//$version
 	$template = getRow(
 		'layouts',
 		array('head_html', 'head_visitor_only', 'foot_html', 'foot_visitor_only'),
 		cms_core::$layoutId);
}

if (isset($adminToolbar['sections']['edit']['buttons']['head'])) {
	$adminToolbar_edit_buttons_head = &$adminToolbar['sections']['edit']['buttons']['head'];
	if(!isset($adminToolbar_edit_buttons_head['tooltip'])) {
		$adminToolbar_edit_buttons_head['tooltip'] = '';
	}
	if ($version['head_html'] === null) {
		$adminToolbar_edit_buttons_head['css_class'] = 'head_slot_empty';
		$adminToolbar_edit_buttons_head['tooltip'] .= adminPhrase('This Layer is empty.');
	} else {
		$adminToolbar_edit_buttons_head['css_class'] = 'head_slot_full';
		$adminToolbar_edit_buttons_head['tooltip'] .= adminPhrase('This Layer is populated.');
	}
	if ($version['head_visitor_only']) {
		$adminToolbar_edit_buttons_head['tooltip'] .= '<br/>'. adminPhrase('This Layer is not output in Admin Mode.');
	}
}
if (isset($adminToolbar['sections']['edit']['buttons']['foot'])) {
	$adminToolbar_edit_buttons_foot = &$adminToolbar['sections']['edit']['buttons']['foot'];
	if(!isset($adminToolbar_edit_buttons_foot['tooltip'])) {
		$adminToolbar_edit_buttons_foot['tooltip'] = '';
	}
	if ($version['foot_html'] === null) {
 		$adminToolbar_edit_buttons_foot['css_class'] = 'foot_slot_empty';
 		$adminToolbar_edit_buttons_foot['tooltip'] .= adminPhrase('This Layer is empty.');
 	} else {
 		$adminToolbar_edit_buttons_foot['css_class'] = 'foot_slot_full';
 		$adminToolbar_edit_buttons_foot['tooltip'] .= adminPhrase('This Layer is populated.');
 	}
 	if ($version['foot_visitor_only']) {
 		$adminToolbar_edit_buttons_foot['tooltip'] .= '<br/>'. adminPhrase('This Layer is not output in Admin Mode.');
 	}
}

if (isset($adminToolbar['sections']['template'])) {
	$adminToolbar['sections']['template']['buttons']['id_and_name']['label'] =
		adminPhrase('Layout: [[id_and_name]]', $templateDetails);
	
	if ($templateDetails['usage'] == 1) {
		$adminToolbar['sections']['template']['buttons']['usage']['label'] =
			adminPhrase('Used on [[usage]] Content Item', $templateDetails);
	} else {
		$adminToolbar['sections']['template']['buttons']['usage']['label'] =
			adminPhrase('Used on [[usage]] Content Item(s)', $templateDetails);
	}
 	
 	$adminToolbar['sections']['template']['buttons']['skq']['organizer_quick']['path'] =
 		$templateDetails['status'] == 'active'?
 			'zenario__layouts/nav/layouts/panel//'. cms_core::$layoutId
 		:	'zenario__layouts/nav/layouts/panel/trash////'. cms_core::$layoutId;
 	
 	$adminToolbar_buttons_head = &$adminToolbar['sections']['template']['buttons']['head'];
	if(!isset($adminToolbar_buttons_head['tooltip'])) {
		$adminToolbar_buttons_head['tooltip'] = '';
	}
 	if ($version['head_overwrite']) {
		if ($template['head_html'] === null) {
			$adminToolbar_buttons_head['css_class'] = 'head_slot_empty_overwritten';
	 		$adminToolbar_buttons_head['tooltip'] .= adminPhrase('This Layer is empty.');
		} else {
			$adminToolbar_buttons_head['css_class'] = 'head_slot_full_overwritten';
	 		$adminToolbar_buttons_head['tooltip'] .= adminPhrase('This Layer is populated.');
		}
 		$adminToolbar_buttons_head['tooltip'] .= '<br/>'. adminPhrase('This Layer is being overwritten here by a Layer above.');
 	} else {
		if ($template['head_html'] === null) {
			$adminToolbar_buttons_head['css_class'] = 'head_slot_empty';
	 		$adminToolbar_buttons_head['tooltip'] .= adminPhrase('This Layer is empty.');
		} else {
			$adminToolbar_buttons_head['css_class'] = 'head_slot_full';
	 		$adminToolbar_buttons_head['tooltip'] .= adminPhrase('This Layer is populated.');
		}
 	}
 	if ($template['head_visitor_only']) {
 		$adminToolbar_buttons_head['tooltip'] .= '<br/>'. adminPhrase('This Layer is not output in Admin Mode.');
 	}
 	
 	$adminToolbar_buttons_foot = &$adminToolbar['sections']['template']['buttons']['foot'];
	if(!isset($adminToolbar_buttons_foot['tooltip'])) {
		$adminToolbar_buttons_foot['tooltip'] = '';
	}
 	if ($version['foot_overwrite']) {
		if ($template['foot_html'] === null) {
			$adminToolbar_buttons_foot['css_class'] = 'foot_slot_empty_overwritten';
	 		$adminToolbar_buttons_foot['tooltip'] .= adminPhrase('This Layer is empty.');
		} else {
			$adminToolbar_buttons_foot['css_class'] = 'foot_slot_full_overwritten';
	 		$adminToolbar_buttons_foot['tooltip'] .= adminPhrase('This Layer is populated.');
		}
 		$adminToolbar_buttons_foot['tooltip'] .= '<br/>'. adminPhrase('This Layer is being overwritten here by a Layer above.');
 	} else {
		if ($template['foot_html'] === null) {
			$adminToolbar_buttons_foot['css_class'] = 'foot_slot_empty';
	 		$adminToolbar_buttons_foot['tooltip'] .= adminPhrase('This Layer is empty.');
		} else {
			$adminToolbar_buttons_foot['css_class'] = 'foot_slot_full';
	 		$adminToolbar_buttons_foot['tooltip'] .= adminPhrase('This Layer is populated.');
		}
 	}
 	if ($template['foot_visitor_only']) {
 		$adminToolbar_buttons_foot['tooltip'] .= '<br/>'. adminPhrase('This Layer is not output in Admin Mode.');
 	}
}





if (isset($adminToolbar['sections']['primary_menu_node'])) {
	
	//Content that is not in the Menu
	if (empty($menuItems)) {
		$adminToolbar['sections']['menu1'] = $adminToolbar['sections']['no_menu_nodes'];
	
	//Content with at least one Menu Node
	} else {
		if (!cms_core::$visitorVersion) {
			if (isset($adminToolbar['toolbars']['menu1'])) {
				$adminToolbar['toolbars']['menu1']['css_class'] .= ' zenario_toolbar_warning';
				$adminToolbar['toolbars']['menu1']['tooltip'] .= '<br/>'. adminPhrase('Warning: visitors cannot see this menu node. Menu nodes like this are shown in italics.');
			}
		}
		
		//For each Menu Node, create a copy of the Menu Section
		$primary = true;
		$numberOfMenuItems = 0;
		foreach ($menuItems as $i => &$menuItem) {
			++$numberOfMenuItems;
			
			//Start numbering Menu Nodes from 1, not from 0
			++$i;
			
			if ($i > 1) {
				//Add extra tabs for each secondary Menu Node
				$adminToolbar['toolbars']['menu'. $i] = $adminToolbar['toolbars']['menu_secondary'];
				$adminToolbar['toolbars']['menu'. $i]['ord'] = '30.'. str_pad($i, 3, '0', STR_PAD_LEFT);
				$adminToolbar['toolbars']['menu'. $i]['label'] = $i;
			
				if ($menuItem['name'] === null) {
					$adminToolbar['toolbars']['menu'. $i]['css_class'] .= 'zenario_toolbar_warning';
					$adminToolbar['toolbars']['menu'. $i]['tooltip'] .= '<br/>'. adminPhrase('Warning: text of menu node is missing in this language.');
				}
				
				$adminToolbar['sections']['menu'. $i] = $adminToolbar['sections']['secondary_menu_node'];
			
			} else {
				if ($menuItem['name'] === null) {
					$adminToolbar['toolbars']['menu1']['css_class'] .= 'zenario_toolbar_warning';
					$adminToolbar['toolbars']['menu1']['tooltip'] .= '<br/>'. adminPhrase('Warning: text of menu node missing in this language.');
				}
				
				$adminToolbar['sections']['menu1'] = $adminToolbar['sections']['primary_menu_node'];
			}
			
			
			foreach ($adminToolbar['sections']['menu'. $i]['buttons'] as $tagName => &$button) {
				if (!isInfoTag($tagName)) {
					foreach (array('request', 'key') as $request) {
						foreach (array('admin_box', 'ajax', 'pick_items') as $action) {
							if (isset($button[$action][$request]['mID'])) {
								$button[$action][$request]['mID'] = $menuItem['id'];
							}
							if (isset($button[$action][$request]['languageId'])) {
								$button[$action][$request]['languageId'] = cms_core::$langId;
							}
						}
					}
				}
			}
			unset($button);
			
			
			//Get some information on this Menu Node's position/path
			$level = getMenuItemLevel($menuItem['id']);
			$parent = $menuItem;
			$menuItem['path'] = getMenuPath($menuItem['id'], cms_core::$langId);
			
			//Add a fake button with the path information
				//(This will actually be used to display an infobar)
			$adminToolbar['sections']['menu'. $i]['buttons']['menu_section']['label'] = menuSectionName($menuItem['section_id']);
			$adminToolbar['sections']['menu'. $i]['buttons']['menu_path']['label'] = $menuItem['path'];
			$adminToolbar['sections']['menu'. $i]['buttons']['menu_path']['css_class'] =
				'zenario_at_infobar'.
				($menuItem['parent_id']? '_child' : '_toplevel').
				($primary? '_menuitem' : '_secondary_menuitem').
				(checkRowExists('menu_nodes', array('parent_id' => $menuItem['id']))? '_with_children' : '_without_children');
		
			
			$mrg = array(
				'path' => htmlspecialchars($menuItem['path']),
				'level' => htmlspecialchars($level),
				'section' => htmlspecialchars(menuSectionName($menuItem['section_id'])));
			
			foreach (array('edit_menu_item', 'view_menu_node_in_sk') as $button) {
				if (isset($adminToolbar['sections']['menu'. $i]['buttons'][$button]['tooltip'])) {
					$adminToolbar['sections']['menu'. $i]['buttons'][$button]['tooltip'] .=
						'|'. 
						($primary?
								adminPhrase('Primary Menu Node')
							:	adminPhrase('Secondary Menu Node')).
						adminPhrase('<br/>Section: [[section]]<br/>Path: [[path]] (Level [[level]])', $mrg);
				}
			}
			
			if (isset($adminToolbar['sections']['menu'. $i]['buttons']['view_menu_node_in_sk']['organizer_quick'])) {
				$adminToolbar['sections']['menu'. $i]['buttons']['view_menu_node_in_sk']['organizer_quick']['path'] = getMenuItemStorekeeperDeepLink($menuItem['id'], cms_core::$langId);
			}
			
			$primary = false;
		}
		
		//If there is only one Menu Node left for this Content Item, warn that removing it will cause the
		//Content Item to be detached.
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



//
//Add information for the status icons at the top right
//

//Set labels and tooltips
$adminToolbar['sections']['icons']['buttons']['tag_id']['label'] = $cType. '_'. $cID;
$adminToolbar['sections']['icons']['buttons']['tag_id']['tooltip'] = adminPhrase('Content Id: [[cID]]<br/>Content Type: [[cType_name]]<br/>Tag Id: [[cType]]_[[cID]]', array('cID' => cms_core::$cID, 'cType' => cms_core::$cType, 'cType_name' => getContentTypeName(cms_core::$cType)));

if (isSpecialPage(cms_core::$cID, cms_core::$cType)) {
	$adminToolbar['sections']['icons']['buttons']['tag_id']['tooltip'] .= adminPhrase('<br/>Special Page');
	
	if (empty($adminToolbar['sections']['icons']['buttons']['tag_id']['css_class'])) {
		$adminToolbar['sections']['icons']['buttons']['tag_id']['css_class'] = '';
	}
	$adminToolbar['sections']['icons']['buttons']['tag_id']['css_class'] .= ' zenario_at_icon_tag_id_special_page';
}

$adminToolbar['meta_info']['title'] =
$adminToolbar['sections']['icons']['buttons']['title']['tooltip'] = adminPhrase('Title: [[title]]', array('title' => cms_core::$pageTitle));
$adminToolbar['sections']['icons']['buttons']['language_id']['label'] = cms_core::$langId;
$adminToolbar['sections']['icons']['buttons']['language_id']['tooltip'] = adminPhrase('Language: [[lang]]', array('lang' => getLanguageName(cms_core::$langId)));


//Alias
if (cms_core::$alias) {
	unset($adminToolbar['sections']['icons']['buttons']['no_alias']);
} else {
	unset($adminToolbar['sections']['icons']['buttons']['alias']);
}

$visitorURL = linkToItem(
	$cID, $cType, $fullPath = false, $request = '', cms_core::$alias,
	$autoAddImportantRequests = false, $useAliasInAdminMode = true);

if (isset($adminToolbar['sections']['icons']['buttons']['alias'])) {
	$adminToolbar['sections']['icons']['buttons']['alias']['tooltip'] = adminPhrase('Alias: [[alias]]', array('alias' => (cms_core::$alias)));
	$adminToolbar['sections']['icons']['buttons']['alias']['frontend_link'] = $visitorURL;
}
if (isset($adminToolbar['sections']['icons']['buttons']['no_alias'])) {
	$adminToolbar['sections']['icons']['buttons']['alias']['frontend_link'] = $visitorURL;
}


//here!
	//Right now: I need to order these by version
	//I need to make up to 3 version blobs, add CSS classes for the statuses, and put the info on each, or hide each if they're not needed

//Show version information on all relevant versions
$showVersions = array();
$showVersions[$cVersion] = true;
$showVersions[cms_core::$adminVersion] = true;
$showVersions[cms_core::$visitorVersion] = true;
$showVersions[cms_core::$adminVersion - 1] = true;
ksort($showVersions);

$i = 0;
foreach ($showVersions as $showVersion => $dummy) {
	
	if ($showVersion && ($v = getRow('versions', true, array('id' => $cID, 'type' => $cType, 'version' => $showVersion)))) {
		
		$tuixId = 'version_'. ++$i;
		
		if (isDraft(cms_core::$status)
		 && $v['version'] == cms_core::$adminVersion
		 && $v['version'] != cms_core::$visitorVersion) {
			if (checkIfVersionChanged($v)) { //('last_author_id', 'last_modified_datetime', 'creating_author_id', 'created_datetime')
				$labelPhrase = 'v[[version]] (draft)';
				$tooltipPhrase = 'Version [[version]], Draft modified by [[name]], [[time]] [[date]]';
				$cssClass = 'zenario_at_icon_version_draft';
				$lastAction = ifNull($v['last_modified_datetime'], $v['created_datetime']);
				$lastActionBy = $v['last_author_id'];

			} else {
				$labelPhrase = 'v[[version]] (draft)';
				$tooltipPhrase = 'Version [[version]], Draft created by [[name]], [[time]] [[date]], no edits made';
				$cssClass = 'zenario_at_icon_version_draft';
				$lastAction = $v['created_datetime'];
				$lastActionBy = $v['creating_author_id'];
			}
	
		} elseif (($v['version'] == cms_core::$adminVersion && cms_core::$status == 'published')
			   || ($v['version'] == cms_core::$visitorVersion && cms_core::$status == 'published_with_draft')) {
		
			$labelPhrase = 'v[[version]] (published)';
			$tooltipPhrase = 'Version [[version]], published by [[name]], [[time]] [[date]]';
			$cssClass = 'zenario_at_icon_version_published';
			$lastAction = $v['published_datetime'];
			$lastActionBy = $v['publisher_id'];

		} elseif (($v['version'] == cms_core::$adminVersion && cms_core::$status == 'hidden')
			   || ($v['version'] == cms_core::$adminVersion - 1 && cms_core::$status == 'hidden_with_draft')) {
	
			$labelPhrase = 'v[[version]] (hidden)';
			$tooltipPhrase = 'Version [[version]], hidden by [[name]], [[time]] [[date]]';
			$cssClass = 'zenario_at_icon_version_hidden';
			$lastAction = $v['concealed_datetime'];
			$lastActionBy = $v['concealer_id'];

		} elseif (($v['version'] == cms_core::$adminVersion && cms_core::$status == 'trashed')
			   || ($v['version'] == cms_core::$adminVersion - 1 && cms_core::$status == 'trashed_with_draft')) {
		
			$labelPhrase = 'v[[version]] (trashed)';
			$tooltipPhrase = 'Version [[version]], trashed by [[name]], [[time]] [[date]]';
			$cssClass = 'zenario_at_icon_version_trashed';
			$lastAction = $v['concealed_datetime'];
			$lastActionBy = $v['concealer_id'];

		} else {
			$labelPhrase = 'v[[version]] (archived)';
			$tooltipPhrase = 'Version [[version]], archived by [[name]], [[time]] [[date]]';
			$cssClass = 'zenario_at_icon_version_archived';
			$lastAction = $v['concealed_datetime'];
			$lastActionBy = $v['concealer_id'];
		}
	
		$mrg = array();
		$mrg['version'] = $v['version'];
		$mrg['name'] = formatAdminName($lastActionBy);
		$mrg['time'] = formatTimeNicely($lastAction, setting('vis_time_format'), true);
		$mrg['date'] = formatDateNicely($lastAction, setting('vis_date_format_med'), true);

		if ($mrg['date'] == formatDateNicely(now(), setting('vis_date_format_med'))) {
			$mrg['date'] = adminPhrase('today');
		}
		
		//Only show the status for the most recent version
		if ($v['version'] != cms_core::$adminVersion) {
			$labelPhrase = 'v[[version]]';
		}

		$adminToolbar['sections']['icons']['buttons'][$tuixId]['hidden'] = false;
		$adminToolbar['sections']['icons']['buttons'][$tuixId]['label'] = adminPhrase($labelPhrase, $mrg);
		$adminToolbar['sections']['icons']['buttons'][$tuixId]['tooltip'] = adminPhrase($tooltipPhrase, $mrg);
		$adminToolbar['sections']['icons']['buttons'][$tuixId]['css_class'] .= ' '. $cssClass;
		$adminToolbar['sections']['icons']['buttons'][$tuixId]['frontend_link'] = linkToItem($cID, $cType, false, 'cVersion='. $v['version']);
	
		if ($v['version'] == $cVersion) {
			$adminToolbar['sections']['icons']['buttons'][$tuixId]['css_class'] .= ' zenario_at_icon_version_in_view';
			$adminToolbar['meta_info']['version'] = $adminToolbar['sections']['icons']['buttons'][$tuixId]['tooltip'];
		}
	}
}


//Content Item is Public
if ($chain['privacy'] == 'public') {
	unset($adminToolbar['sections']['icons']['buttons']['item_permissions_closed']);
} else {
	unset($adminToolbar['sections']['icons']['buttons']['item_permissions_open']);
}

if (isset($adminToolbar['sections']['icons']['buttons']['item_permissions_closed'])) {
	$adminToolbar['sections']['icons']['buttons']['item_permissions_closed']['tooltip'] =
		$adminToolbar['sections']['icons']['buttons']['item_permissions_closed']['tooltip__'. $chain['privacy']];
	
	//If this item is set to show to specific users/groups, show them in the tooltip
	$sql = false;
	if ($chain['privacy'] == 'group_members') {
		$sql = "
			SELECT cdf.label as name
			FROM ". DB_NAME_PREFIX. "group_content_link AS gcl
			LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_fields AS cdf
			   ON gcl.group_id = cdf.id
			WHERE gcl.equiv_id = ". (int) $chain['equiv_id']. "
			  AND gcl.content_type = '" . sqlEscape($cType). "'";
	
	} elseif ($chain['privacy'] == 'specific_users') {
		$sql = "
			SELECT u.screen_name AS name
			FROM ". DB_NAME_PREFIX. "user_content_link AS ucl
			LEFT JOIN ". DB_NAME_PREFIX. "users AS u
			   ON ucl.user_id = u.id
			WHERE ucl.equiv_id = ". (int) $chain['equiv_id']. "
			  AND ucl.content_type = '" . sqlEscape($cType). "'";
	}
	
	$i = 0;
	if ($sql && ($result = sqlQuery($sql))) {
		$adminToolbar['sections']['icons']['buttons']['item_permissions_closed']['tooltip'] .= '<br/>';
		
		while ($row = sqlFetchAssoc($result)) {
			$adminToolbar['sections']['icons']['buttons']['item_permissions_closed']['tooltip'] .=
				($i++? ', ' : ''). htmlspecialchars($row['name']);
		}
	}
}


//Content Item is categorised
if (checkRowExists('category_item_link', array('equiv_id' => $content['equiv_id'], 'content_type' => $cType))) {
	unset($adminToolbar['sections']['icons']['buttons']['item_categories_none']);
} else {
	unset($adminToolbar['sections']['icons']['buttons']['item_categories_some']);
}

if (isset($adminToolbar['sections']['icons']['buttons']['item_categories_some'])) {
	//If this item is in categories, list them
	$sql = "
		SELECT c.name
		FROM ". DB_NAME_PREFIX. "category_item_link AS cil
		LEFT JOIN ". DB_NAME_PREFIX. "categories AS c
		   ON cil.category_id = c.id
		WHERE cil.equiv_id = ". (int) $content['equiv_id']. "
		  AND cil.content_type = '" . sqlEscape($cType). "'";
	
	$i = 0;
	if ($sql && ($result = sqlQuery($sql))) {
		while ($row = sqlFetchAssoc($result)) {
			$adminToolbar['sections']['icons']['buttons']['item_categories_some']['tooltip'] .=
				($i++? ', ' : ''). htmlspecialchars($row['name']);
		}
	}
}





return false;
