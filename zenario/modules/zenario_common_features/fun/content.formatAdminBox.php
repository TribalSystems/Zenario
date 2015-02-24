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


$box['tabs']['file']['hidden'] = true;

if (isset($box['tabs']['template'])) {
	
	if (!$box['key']['cID']) {
		if ($values['template/layout_id']) {
			$box['key']['cType'] = getRow('layouts', 'content_type', $values['template/layout_id']);
		}
	}
	
	$box['tabs']['template']['fields']['skin_id']['value'] =
	$box['tabs']['template']['fields']['skin_id']['current_value'] =
		$skinId = templateSkinId($values['template/layout_id']);
	
	$fields['css/background_image']['side_note'] = '';
	$fields['css/bg_color']['side_note'] = '';
	$fields['css/bg_position']['side_note'] = '';
	$fields['css/bg_repeat']['side_note'] = '';
	$box['tabs']['template']['notices']['archived_template']['show'] = false;
	
	if ($values['template/layout_id']
	 && ($layout = getTemplateDetails($values['template/layout_id']))) {
		
		if ($layout['status'] != 'active') {
			$box['tabs']['template']['notices']['archived_template']['show'] = true;
		}
		
		if ($layout['bg_image_id']) {
			$fields['css/background_image']['side_note'] = htmlspecialchars(
				adminPhrase("Setting a background image here will override the background image set on this item's layout ([[id_and_name]]).", $layout));
		}
		if ($layout['bg_color']) {
			$fields['css/bg_color']['side_note'] = htmlspecialchars(
				adminPhrase("Setting a background color here will override the background color set on this item's layout ([[id_and_name]]).", $layout));
		}
		if ($layout['bg_position']) {
			$fields['css/bg_position']['side_note'] = htmlspecialchars(
				adminPhrase("Setting a background position here will override the background position set on this item's layout ([[id_and_name]]).", $layout));
		}
		if ($layout['bg_repeat']) {
			$fields['css/bg_repeat']['side_note'] = htmlspecialchars(
				adminPhrase("Setting an option here will override the option set on this item's layout ([[id_and_name]]).", $layout));
		}
	}
}


		
$box['tabs']['meta_data']['fields']['description']['hidden'] = false;
$box['tabs']['meta_data']['fields']['writer']['hidden'] = false;
$box['tabs']['meta_data']['fields']['keywords']['hidden'] = false;
$box['tabs']['meta_data']['fields']['publication_date']['hidden'] = false;
$box['tabs']['meta_data']['fields']['content_summary']['hidden'] = false;
if ($path != 'zenario_quick_create' && $box['key']['cType'] && $details = getContentTypeDetails($box['key']['cType'])) {
	if ($details['description_field'] == 'hidden') {
		$box['tabs']['meta_data']['fields']['description']['hidden'] = true;
	}
	if ($details['keywords_field'] == 'hidden') {
		$box['tabs']['meta_data']['fields']['keywords']['hidden'] = true;
	}
	if ($details['release_date_field'] == 'hidden') {
		$box['tabs']['meta_data']['fields']['publication_date']['hidden'] = true;
	}
	if ($details['writer_field'] == 'hidden') {
		$box['tabs']['meta_data']['fields']['writer_id']['hidden'] = true;
		$box['tabs']['meta_data']['fields']['writer_name']['hidden'] = true;
	}
	if ($details['summary_field'] == 'hidden') {
		$box['tabs']['meta_data']['fields']['content_summary']['hidden'] = true;
	}
}

if (isset($box['tabs']['meta_data']['fields']['writer_id'])
 && !engToBooleanArray($box['tabs']['meta_data']['fields']['writer_id'], 'hidden')) {
	if ($values['meta_data/writer_id']) {
		if (engToBooleanArray($box, 'tabs', 'meta_data', 'edit_mode', 'on')) {
			if (empty($box['tabs']['meta_data']['fields']['writer_name']['current_value'])
			 || empty($box['tabs']['meta_data']['fields']['writer_id']['last_value'])
			 || $box['tabs']['meta_data']['fields']['writer_id']['last_value'] != $values['meta_data/writer_id']) {
				$adminDetails = getAdminDetails($values['meta_data/writer_id']);
				$box['tabs']['meta_data']['fields']['writer_name']['current_value'] = $adminDetails['first_name'] . " " . $adminDetails['last_name'];
			}
		}
		
		$box['tabs']['meta_data']['fields']['writer_name']['hidden'] = false;
	} else {
		$box['tabs']['meta_data']['fields']['writer_name']['hidden'] = true;
		$box['tabs']['meta_data']['fields']['writer_name']['current_value'] = "";
	}
	
	$box['tabs']['meta_data']['fields']['writer_id']['last_value'] = $values['meta_data/writer_id'];
}


if ($box['key']['cID']) {
	$languageId = getContentLang($box['key']['cID'], $box['key']['cType']);
	$specialPage = isSpecialPage($box['key']['cID'], $box['key']['cType']);
} else {
	$languageId = ifNull($values['meta_data/language_id'], $box['key']['target_template_id'], setting('default_language'));
	$specialPage = false;
}

$box['tabs']['template']['fields']['css_class']['pre_field_html'] =
	'<span class="zenario_css_class_label">'.
		($specialPage? $specialPage. ' ' : '').
		'lang_'. preg_replace('/[^\w-]/', '', $languageId).
	'</span> ';

$titleCounterHTML = '
	<div class="snippet__title" >
		<div id="snippet__title_length" class="[[initial_class_name]]">
			<span id="snippet__title_counter">[[initial_characters_count]]</span>
		</div>
	</div>';

$descriptionCounterHTML = '
	<div class="snippet__description" >
		<div id="snippet__description_length" class="[[initial_class_name]]">
			<span id="snippet__description_counter">[[initial_characters_count]]</span>
		</div>
	</div>';

$keywordsCounterHTML = '
	<div class="snippet__keywords" >
		<div id="snippet__keywords_length" >
			<span id="snippet__keywords_counter">[[initial_characters_count]]</span>
		</div>
	</div>';
	
$googlePreviewHTML = '									
	<div  class="google_preview_container">
			<h3 class="google_preview_title">
				<span id="google_preview_title">
					[[google_preview_title]]
				</span>
			</h3>
			<div class="google_preview_url">
				<div>
					<cite id="google_preview_url">[[google_preview_url]]</cite>
				</div>
				<span id="google_preview_description" class="google_preview_description">
					[[google_preview_description]]
				</span>
			</div>
		</div>';


	
if (strlen($values['meta_data/title'])<1) {
	$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_red', $titleCounterHTML);
} elseif (strlen($values['meta_data/title'])<20)  {
	$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_orange', $titleCounterHTML);
} elseif (strlen($values['meta_data/title'])<40)  {
	$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_yellow', $titleCounterHTML);
} elseif (strlen($values['meta_data/title'])<66)  {
	$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_green', $titleCounterHTML);
} else {
	$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_yellow', $titleCounterHTML);
}
$titleCounterHTML = str_replace('[[initial_characters_count]]', strlen($values['meta_data/title']), $titleCounterHTML);
$box['tabs']['meta_data']['fields']['title']['post_field_html'] = $titleCounterHTML;


if (strlen($values['meta_data/description'])<1) {
	$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_red', $descriptionCounterHTML);
} elseif (strlen($values['meta_data/description'])<50)  {
	$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_orange', $descriptionCounterHTML);
} elseif (strlen($values['meta_data/description'])<100)  {
	$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_yellow', $descriptionCounterHTML);
} elseif (strlen($values['meta_data/description'])<156)  {
	$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_green', $descriptionCounterHTML);
} else {
	$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_yellow', $descriptionCounterHTML);
}
$descriptionCounterHTML = str_replace('[[initial_characters_count]]', strlen($values['meta_data/description']), $descriptionCounterHTML);
$box['tabs']['meta_data']['fields']['description']['post_field_html'] = $descriptionCounterHTML;


$keywordsCounterHTML = str_replace('[[initial_characters_count]]', strlen($values['meta_data/keywords']) , $keywordsCounterHTML);
$box['tabs']['meta_data']['fields']['keywords']['post_field_html'] = $keywordsCounterHTML;

$title =  $values['meta_data/title'];
displayHTMLAsPlainText($title, 65 );
$googlePreviewHTML = str_replace('[[google_preview_title]]', $title, $googlePreviewHTML );

$description =  $values['meta_data/description'];
displayHTMLAsPlainText($description, 155 );
$googlePreviewHTML = str_replace('[[google_preview_description]]', $description, $googlePreviewHTML );

$alias = $values['meta_data/alias'];
displayHTMLAsPlainText($alias, 50 );
if ($alias) {
		$googlePreviewHTML = str_replace('[[google_preview_url]]', httpOrHttps() . httpHost() . SUBDIRECTORY . $alias,  $googlePreviewHTML);
} else {
	if ($link = linkToItem($box['key']['source_cID'], $box['key']['cType'], true, '', false, false, true)) {
		$googlePreviewHTML = str_replace('[[google_preview_url]]', $link,  $googlePreviewHTML);
	} else {
		$googlePreviewHTML = str_replace('[[google_preview_url]]', '', $googlePreviewHTML);
	}
}

$box['tabs']['meta_data']['fields']['google_preview']['hidden'] = 
	!($values['meta_data/alias'] || $values['meta_data/title'] || $values['meta_data/description']);


$box['tabs']['meta_data']['fields']['google_preview']['snippet']['html'] = $googlePreviewHTML;





//Set up content tabs (up to four of them), for each WYSIWYG Editor
if (isset($box['tabs']['content1'])) {
	$i = 0;
	$slots = array();
	if ($box['key']['source_cID']
	 && $box['key']['cType']
	 && $box['key']['source_cVersion']) {
		$slots = pluginMainSlot($box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'], false, false, $values['template/layout_id']);
	} else {
		$slots = pluginMainSlotOnLayout($values['template/layout_id'], false, false);
	}

	if (!empty($slots)) {
		foreach ($slots as $slot) {
			if (++$i > 4) {
				break;
			}
		
			$box['tabs']['content'. $i]['hidden'] = false;
			if (count($slots) == 1) {
				$box['tabs']['content'. $i]['label'] = adminPhrase('Main content');
			
			} elseif (strlen($slot) <= 20) {
				$box['tabs']['content'. $i]['label'] = $slot;
			
			} else {
				$box['tabs']['content'. $i]['label'] = substr($slot, 0, 8). '...'. substr($slot, -8);
			}
			addAbsURLsToAdminBoxField($box['tabs']['content'. $i]['fields']['content']);
			
			require_once CMS_ROOT. 'zenario/admin/grid_maker/grid_maker.inc.php';
			if (zenario_grid_maker::readLayoutCode($values['template/layout_id'], $justCheck = true, $quickCheck = true)) {
				$fields['content'. $i. '/thumbnail']['hidden'] = false;
				$fields['content'. $i. '/thumbnail']['snippet']['html'] = '
					<p style="text-align: center;">
						<a>
							<img src="'. htmlspecialchars(
								absCMSDirURL(). 'zenario/admin/grid_maker/ajax.php?loadDataFromLayout='. (int) $values['template/layout_id']. '&highlightSlot='. rawurlencode($slot). '&thumbnail=1&width=150&height=200'
							). '" width="150" height="200" style="border: 1px solid black;"/>
						</a>
					</p>';
			
			} else {
				$fields['content'. $i. '/thumbnail']['hidden'] = true;
				$fields['content'. $i. '/thumbnail']['snippet']['html'] = '';
			}
		}
	}

	while (++$i <= 4) {
		$box['tabs']['content'. $i]['hidden'] = true;
		$fields['content'. $i. '/thumbnail']['snippet']['html'] = '';
	}
}
if (isset($box['tabs']['meta_data']['fields']['content_summary'])) {
	addAbsURLsToAdminBoxField($box['tabs']['meta_data']['fields']['content_summary']);
}