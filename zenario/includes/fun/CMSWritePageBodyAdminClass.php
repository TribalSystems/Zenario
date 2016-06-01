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


//Show some tabs whilst the page is loading.
//These won't be the real tabs, but we'll copy some of the basic logic the Admin Toolbar uses to come up with a close copy.

//Set the current Admin Toolbar tab in Admin Mode
//Set up which toolbars will be on the Admin Toolbar, and which is currently picked
$toolbars['preview'] = array('ord' => 10, 'label' => adminPhrase('Navigate'));

if (cms_core::$status != 'trashed' && (cms_core::$cVersion == cms_core::$adminVersion || (cms_core::$visitorVersion && cms_core::$cVersion == cms_core::$visitorVersion))) {
	if (checkPriv('_PRIV_VIEW_MENU_ITEM')) {
		$toolbars['menu1'] = array('ord' => 20, 'label' => adminPhrase('Menu'));
	}
}

//Only show one of rollback/edit, depending on the version in view and its status
if (cms_core::$cVersion != cms_core::$adminVersion || cms_core::$status == 'trashed') {
	$toolbars['rollback'] = array('ord' => 41, 'label' => adminPhrase('Edit'));

} elseif (checkPriv('_PRIV_EDIT_DRAFT', cms_core::$cID, cms_core::$cType)) {
	$toolbars['edit'] = array('ord' => 41, 'label' => adminPhrase('Edit'));

} else {
	$toolbars['edit_disabled'] = array('ord' => 41, 'label' => adminPhrase('Edit'));
}

//Only show slot/layout on the current version
if (cms_core::$cVersion == cms_core::$adminVersion) {
	if (checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT')) {
		$toolbars['template'] = array('ord' => 50, 'label' => adminPhrase('Layout'));
	}
}

//If this Content Item is not the same Content Item as last time, default to Preview mode
if (!empty($_SESSION['last_item']) && $_SESSION['last_item'] != cms_core::$cType. '_'. cms_core::$cID) {
	$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'preview';
}
$_SESSION['last_item'] = cms_core::$cType. '_'. cms_core::$cID;

//Check that we're about to use a toolbar that exists
if (!cms_core::$cID || !isset($toolbars[session('page_toolbar')])) {
	//Allow switching between edit/edit_disabled/rollback. Default to preview mode otherwise.
	if (session('page_toolbar') == 'edit' || session('page_toolbar') == 'edit_disabled' || session('page_toolbar') == 'rollback') {
		if (isset($toolbars['edit'])) {
			$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'edit';
		} elseif (isset($toolbars['edit_disabled'])) {
			$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'edit_disabled';
		} elseif (isset($toolbars['rollback'])) {
			$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'rollback';
		} else {
			$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'preview';
		}
	
	} elseif (session('page_mode') == 'menu') {
		$_SESSION['page_toolbar'] = 'menu1';
	
	} else {
		$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'preview';
	}
}

$class .=
	' zenario_adminLoggedIn zenario_pageMode_'. session('page_mode').
	' '.
	(session('page_mode') == 'menu'? 'zenario_menuWand_on' : 'zenario_menuWand_off').
	' '.
	(in(session('page_mode'), 'edit', 'template') && session('admin_slot_wand')? 'zenario_slotWand_on' : 'zenario_slotWand_off');