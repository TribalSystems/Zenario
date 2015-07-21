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


//Check to see if a specific Language has been set, or if this site only has one language and this the language is implied
$onlyOneLanguage = ($result = getRows('languages', array('id'), array())) && ($lang = sqlFetchAssoc($result)) && !(sqlFetchAssoc($result));

if (get('refiner__language')) {
	$panel['key']['language'] = get('refiner__language');
} elseif ($onlyOneLanguage) {
	$panel['key']['language'] = $lang['id'];
}


//Handle the fact that a couple of refiners actually use several levels of refiners
if (get('refiner__template') && get('refiner__content_type') && $refinerName == 'language') {
	$panel['refiners']['language'] = $panel['refiners']['content_type__template__language'];
	unset($panel['collection_buttons']['equivs']);

} elseif (get('refiner__content_type') && $refinerName == 'language') {
	$panel['refiners']['language'] = $panel['refiners']['content_type__language'];
	unset($panel['collection_buttons']['equivs']);

} elseif (get('refiner__template') && $refinerName == 'language') {
	$panel['refiners']['language'] = $panel['refiners']['template__language'];
	unset($panel['collection_buttons']['equivs']);

} elseif ($refinerName != 'language' || $onlyOneLanguage) {
	unset($panel['collection_buttons']['equivs']);
}


//Check if a specific Content Type has been set
if (get('refiner__content_type')) {
	$panel['key']['cType'] = get('refiner__content_type');
} elseif (get('refiner__template')) {
	$panel['key']['cType'] = getRow('layouts', 'content_type', get('refiner__template'));
}

if (isset($panel['collection_buttons']['create'])) {
	if (($panel['key']['cType'] && $panel['key']['cType'] != 'html')
	 || get('refiner__category')) {
		$panel['collection_buttons']['create']['admin_box']['path'] = 'zenario_content';
	}
}

if (isset($_GET['refiner__trash']) && !get('refiner__template')) {
	unset($panel['columns']['status']['title']);
	$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__trash'];

} elseif (in($mode, 'get_item_name', 'get_item_links')) {
	unset($panel['db_items']['where_statement']);
}

//Attempt to customise the defaults slightly depending on the content type
//These options are only defaults and will be overridden if the Administrator has ever set or changed them.
if ($cType = get('refiner__content_type')) {
	switch ($cType) {
		case 'news':
			$panel['columns']['title']['show_by_default'] = true;
			$panel['columns']['description']['show_by_default'] = false;
			$panel['columns']['publication_date']['show_by_default'] = true;
			$panel['columns']['inline_files']['show_by_default'] = false;
			$panel['columns']['zenario_trans__links']['show_by_default'] = false;
			$panel['columns']['menu']['show_by_default'] = true;
			
			break;
		
		case 'blog':
			$panel['columns']['publication_date']['show_by_default'] = true;
			
			break;
	}
	
	//Task #9514: Release Date should always be visible if you are looking at a Content Type where it is mandatory.
	if ($details = getContentTypeDetails($cType)) {
		foreach (array(
			'writer_field' => 'writer_name',
			'description_field' => 'description',
			'keywords_field' => 'keywords',
			//'summary_field' => '...',
			'release_date_field' => 'publication_date'
		) as $fieldName => $columnName) {
		
			if ($details[$fieldName] == 'mandatory') {
				$panel['columns'][$columnName]['always_show'] = true;
		
			} elseif ($details[$fieldName] == 'hidden') {
				$panel['columns'][$columnName]['hidden'] = true;
			}
		}
	}

//If this is a panel for multiple content types then we are limited in how much we can customise it.
//But if any fields are always hidden, we can still hide them
} else {
	foreach (array(
		'writer_field' => 'writer_name',
		'description_field' => 'description',
		'keywords_field' => 'keywords',
		//'summary_field' => '...',
		'release_date_field' => 'publication_date'
	) as $fieldName => $columnName) {
	
		if (!checkRowExists('content_types', array($fieldName => array('!' => 'hidden')))) {
			$panel['columns'][$columnName]['hidden'] = true;
		}
	}
}

// Create page preview buttons
$pagePreviews = getRowsArray('page_preview_sizes', array('width', 'height', 'description', 'ordinal', 'is_default'), array(), 'ordinal');
foreach ($pagePreviews as $pagePreview) {
	$width = $pagePreview['width'];
	$height = $pagePreview['height'];
	$description = $pagePreview['description'];
	
	$pagePreviewButton = array(
		'parent' => 'page_preview_sizes',
		'label' => $width.' x '.$height.', '.$description,
		'custom_width' => $width,
		'custom_height' => $height,
		'custom_description' => $description,
		'call_js_function' => array(
			'encapsulated_object' => 'zenarioA',
			'function' => 'showPagePreview'));
			
	if ($pagePreview['is_default']) {
		$pagePreviewButton['label'] .= ' (Default)';
		 $panel['inline_buttons']['inspect']['custom_width'] = $width;
		 $panel['inline_buttons']['inspect']['custom_height'] = $height;
		 $panel['inline_buttons']['inspect']['custom_description'] = $description;
	}
	$panel['item_buttons']['page_preview_'.$pagePreview['ordinal'].'_'.$width.'x'.$height] = $pagePreviewButton;
}

return false;