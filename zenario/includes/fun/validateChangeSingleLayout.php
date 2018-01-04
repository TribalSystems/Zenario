<?php
/*
 * Copyright (c) 2018, Tribal Limited
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


$box['confirm']['show'] = false;
$box['confirm']['message'] = '';

$status = getContentStatus($cID, $cType);

switch ($status) {
	case 'published':
		$box['confirm']['message'] .=
			adminPhrase('You are editing a content item that\'s already published.');
		break;
	
	case 'hidden':
		$box['confirm']['message'] .=
			adminPhrase('You are editing a content item that\'s hidden.');
		break;
	
	case 'trashed':
		$box['confirm']['message'] .=
			adminPhrase('You are editing a content item that\'s trashed.');
		break;
}

if (!isDraft($status)) {
	$box['confirm']['show'] = true;
	$box['confirm']['message'] .= $box['confirm']['message']? '<br/><br/>' : '';
	$box['confirm']['message'] .=
		adminPhrase('When you want to edit a content item, the CMS makes a draft version. This won\'t be seen by site visitors until it is published.');
}

if ($saving && ($warnings = changeContentItemLayout(
	$cID, $cType, $cVersion, $newLayoutId,
	$check = true, $warnOnChanges = true
))) {
	$box['confirm']['show'] = true;
	$box['confirm']['message'] .= $box['confirm']['message']? '<br/><br/>' : '';
	$box['confirm']['message'] .=
		adminPhrase('You are about to change the layout of this content item. The content and settings will moved as follows:').
		'<br/><br/>'.
		$warnings;
	$box['confirm']['button_message'] = adminPhrase('Change Layout');
}



if (!isDraft($status)) {
	$box['confirm']['show'] = true;
	$box['confirm']['message'] .= $box['confirm']['message']? '<br/><br/>' : '';
	$box['confirm']['message'] .= adminPhrase('Make a draft?');
	$box['confirm']['button_message'] = adminPhrase('Make a draft');
}