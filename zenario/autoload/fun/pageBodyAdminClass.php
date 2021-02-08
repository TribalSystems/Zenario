<?php
/*
 * Copyright (c) 2021, Tribal Limited
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
$toolbars['preview'] = ['ord' => 10, 'label' => \ze\admin::phrase('Navigate')];

if (\ze::$status != 'trashed' && (\ze::$cVersion == \ze::$adminVersion || (\ze::$visitorVersion && \ze::$cVersion == \ze::$visitorVersion))) {
	if (\ze\priv::check('_PRIV_VIEW_MENU_ITEM')) {
		$toolbars['menu1'] = ['ord' => 20, 'label' => \ze\admin::phrase('Menu')];
	}
}

//Only show one of rollback/edit, depending on the version in view and its status
if (\ze::$cVersion != \ze::$adminVersion || \ze::$status == 'trashed') {
	$toolbars['rollback'] = ['ord' => 41, 'label' => \ze\admin::phrase('Edit')];

} elseif (\ze\priv::check('_PRIV_EDIT_DRAFT', \ze::$cID, \ze::$cType)) {
	$toolbars['edit'] = ['ord' => 41, 'label' => \ze\admin::phrase('Edit')];

} else {
	$toolbars['edit_disabled'] = ['ord' => 41, 'label' => \ze\admin::phrase('Edit')];
}

//Only show slot/layout on the current version
if (\ze::$cVersion == \ze::$adminVersion) {
	
	if (\ze\priv::check('_PRIV_MANAGE_ITEM_SLOT')) {
		$toolbars['item'] = ['ord' => 50, 'label' => \ze\admin::phrase('Item')];
	}
	if (\ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT')) {
		$toolbars['layout'] = ['ord' => 51, 'label' => \ze\admin::phrase('Layout')];
	}
}

//Default to preview mode
//If this Content Item is not the same Content Item as last time, default to Preview mode
if (empty($_SESSION['page_mode'])
 || (!empty($_SESSION['last_item']) && $_SESSION['last_item'] != \ze::$cType. '_'. \ze::$cID)) {
	$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'preview';
}
$_SESSION['last_item'] = \ze::$cType. '_'. \ze::$cID;

//Check that we're about to use a toolbar that exists
if (!\ze::$cID || !isset($toolbars[($_SESSION['page_toolbar'] ?? false)])) {
	
	//Allow switching between edit/edit_disabled/rollback. Default to preview mode otherwise.
	if (($_SESSION['page_toolbar'] ?? false) == 'edit' || ($_SESSION['page_toolbar'] ?? false) == 'edit_disabled' || ($_SESSION['page_toolbar'] ?? false) == 'rollback') {
		
		if (isset($toolbars['edit'])) {
			$_SESSION['page_mode'] = 'edit';
			$_SESSION['page_toolbar'] = 'edit';
		
		} elseif (isset($toolbars['edit_disabled'])) {
			$_SESSION['page_mode'] = 'edit';
			$_SESSION['page_toolbar'] = 'edit_disabled';
		
		} elseif (isset($toolbars['rollback'])) {
			$_SESSION['page_mode'] = 'edit';
			$_SESSION['page_toolbar'] = 'rollback';
		
		} else {
			$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'preview';
		}
	
	} elseif ($_SESSION['page_mode'] == 'menu') {
		$_SESSION['page_toolbar'] = 'menu1';
	
	} else {
		$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'preview';
	}
}


$class .= ' zenario_adminLoggedIn';

foreach ([
	'preview',
	'edit_disabled',
	'edit',
	'rollback',
	'item',
	'menu',
	'layout'
] as $possiblePageMode) {
	if ($_SESSION['page_mode'] == $possiblePageMode) {
		$class .= ' zenario_pageMode_'. $possiblePageMode;
	} else {
		$class .= ' zenario_pageModeIsnt_'. $possiblePageMode;
	}
}


if ($_SESSION['page_mode'] == 'item' || $_SESSION['page_mode'] == 'layout') {
	$class .= ' zenario_slotWand_on';
} else {
	$class .= ' zenario_slotWand_off';
}

//Add the old class name for this for backwards compatability
if ($_SESSION['page_mode'] == 'layout') {
	$class .= ' zenario_pageMode_template';
} else {
	$class .= ' zenario_pageModeIsnt_template';
}

//Add a class when locked
if (ze::$locked) {
	$class .= ' zenario_pageLocked';
} else {
	$class .= ' zenario_pageIsntLocked';
}

$class .= ' zenario_status_'. \ze\contentAdm::versionStatus(\ze::$cVersion, \ze::$visitorVersion, \ze::$adminVersion, \ze::$status);