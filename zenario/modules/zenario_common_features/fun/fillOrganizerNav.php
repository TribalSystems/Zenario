<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

//Look up every content type on this site
//Sort the items in the following order: html, news, blog then documents.
$sql = "
	SELECT content_type_id, content_type_name_en, content_type_plural_en,tooltip_text
	FROM ". DB_PREFIX. "content_types AS ct
	INNER JOIN ". DB_PREFIX. "modules AS m
	   ON m.id = ct.module_id
	  AND m.status IN ('module_running', 'module_is_abstract')
	ORDER BY
		ct.content_type_id != 'html',
		ct.content_type_id != 'news',
		ct.content_type_id != 'blog',
		ct.content_type_id != 'document',
		ct.content_type_name_en";

//Add links to every content type to the navigation
$ord = 0;
foreach (ze\sql::fetchAssocs($sql) as $details) {
	$ord++;
	$cType = $details['content_type_id'];
	
	$nav['zenario__content']['nav']['content_type_'. $cType] = [
		'ord' => $ord,
		'label' => ($details['content_type_plural_en'] ?: $details['content_type_name_en']),
		'css_class' => 'content_type_'. $cType,
		'tooltip' => $details['tooltip_text'],
		'link' => [
			'path' => 'zenario__content/panels/content',
			'refiner' => 'content_type',
			'refinerId' => $cType
	]];
}


//Look up every menu section
//Sort the items in the following order: Main, any others alphabetically, Footer
$sql = "
	SELECT id, section_name
	FROM ". DB_PREFIX. "menu_sections
	ORDER BY
		id = 1 DESC,
		id = 2,
		section_name";

//Add links to every menu section, in the default language, to the navigation
$last = false;
foreach (ze\sql::fetchAssocs($sql) as $details) {
	$thisOrd = $ord = $ord + 0.0001;
	$id = $details['id'];
	
	$nav['zenario__menu']['nav'][$last = 'menu_section'. $id] = [
		'ord' => $thisOrd,
		'css_class' => 'menu_section',
		'label' => $details['section_name'],
		'tooltip' => ze\admin::phrase('Menu navigation: edit menu nodes in the menu section "[[section_name]]"', $details),
		'keywords' => 'Menu navigation',
		'link' => [
			'path' => 'zenario__menu/panels/by_language/item//'. ze::$defaultLang. '//item//'. $id. '//'
	]];
}

if ($last) {
	$nav['zenario__menu']['nav'][$last]['css_class'] .= ' zenario_separator_after_this';
}


//Look up every language, other than the default one, and add a link to the menu in that language
$first = true;
foreach (ze\lang::getLanguages($includeAllLanguages = false, $orderByEnglishName = true, $defaultLangFirst = true) as $langId => $details) {
	
	if ($first) {
		$first = false;
	} else {
		$thisOrd = $ord = $ord + 0.0001;
		$id = $details['id'];
	
		$nav['zenario__menu']['nav']['menu_lang'. $langId] = [
			'ord' => $thisOrd,
			'css_class' => 'menu_section_language',
			'label' => $details['english_name'],
			'tooltip' => ze\admin::phrase('Manage menu nodes in [[english_name]] ([[id]])', $details),
			'keywords' => 'Menu navigation, language, '. $details['english_name']. ', '. $details['language_local_name'],
			'link' => [
				'path' => 'zenario__menu/panels/by_language/item//'. $langId. '//'
		]];
	}
}

//If there's only one language enabled, show an "Edit sections" link
//to the menu sections panel so admins can still create/edit/delete them.
if (isset($nav['zenario__menu']['nav']['menu_sections']['link'])) {
	$nav['zenario__menu']['nav']['menu_sections']['link']['path'] =
		'zenario__menu/panels/by_language/item//'. ze::$defaultLang. '//';
}

$nav['top_right_buttons']['admin_name']['label'] = ze\admin::formatName();

if ($_SESSION['admin_global_id'] ?? false) {
	$nav['top_right_buttons']['change_password']['disabled'] = true;
}
