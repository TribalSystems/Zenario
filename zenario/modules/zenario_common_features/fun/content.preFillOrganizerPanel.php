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


if (get('refiner__content_type')) {
	switch (get('refiner__content_type')) {
		case 'news':
			$panel['default_sort_column'] = 'publication_date';
			
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
}




return false;