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


$box['confirm']['show'] = false;
$box['confirm']['message'] = '';

if ($path == 'zenario_quick_create') {
	if (!$values['meta_data/layout_id']) {
		$box['tabs']['meta_data']['errors'][] = adminPhrase('Please select a layout.');
	}

} else {
	if (!$box['key']['cID']) {
		if (!$values['template/layout_id']) {
			$box['tab'] = 'template';
			$box['tabs']['template']['errors'][] = adminPhrase('Please select a layout.');
		} else {
			$box['key']['cType'] = getRow('layouts', 'content_type', $values['template/layout_id']);
		}
	
	} else {
		$this->validateChangeSingleLayout($box, $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $values['template/layout_id'], $saving);
	}
	
	if (isset($box['tabs']['template']) && !checkContentTypeRunning($box['key']['cType'])) {
		$box['tabs']['template']['errors'][] =
			adminPhrase(
				'Drafts of "[[cType]]" type content items cannot be created as their handler module is missing or not running.',
				array('cType' => $box['key']['cType']));
	}
}

if (!$values['meta_data/title']) {
	$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter a title.');
}

if (!empty($values['meta_data/alias'])) {
	if ($box['key']['translate']) {
		$errors = validateAlias($values['meta_data/alias'], false, $box['key']['cType'], equivId($box['key']['source_cID'], $box['key']['cType']));
	} else {
		$errors = validateAlias($values['meta_data/alias']);
	}
	if (is_array($errors)) {
		$box['tabs']['meta_data']['errors'] = array_merge($box['tabs']['meta_data']['errors'], $errors);
	}
}


if ($path != 'zenario_quick_create' && $box['key']['cType'] && $details = getContentTypeDetails($box['key']['cType'])) {
	if ($details['description_field'] == 'mandatory' && !$values['meta_data/description']) {
		$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter a description.');
	}
	if ($details['keywords_field'] == 'mandatory' && !$values['meta_data/keywords']) {
		$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter keywords.');
	}
	if ($details['release_date_field'] == 'mandatory' && !$values['meta_data/publication_date']) {
		$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter a release date.');
	}
	if ($details['writer_field'] == 'mandatory' && !$values['meta_data/writer_id']) {
		$box['tabs']['meta_data']['errors'][] = adminPhrase('Please select a writer.');
	}
	if ($details['summary_field'] == 'mandatory' && !$values['meta_data/content_summary']) {
		$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter a summary.');
	}
}

if (issetArrayKey($values,'meta_data/writer_id') && !issetArrayKey($values,'meta_data/writer_name')) {
	$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter a writer name.');
}

if (!empty($box['key']['target_menu_section'])
 && $values['meta_data/create_menu']
 && !$values['meta_data/menu_title']) {
		$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter the menu node text.');
}

if ($box['key']['translate']) {
	$equivId = equivId($box['key']['source_cID'], $box['key']['cType']);
	
	if (checkRowExists('content_items', array('equiv_id' => $equivId, 'type' => $box['key']['cType'], 'language_id' => $values['meta_data/language_id']))) {
		$box['tabs']['meta_data']['errors'][] = adminPhrase('This translation already exists.');
	}
}

return false;