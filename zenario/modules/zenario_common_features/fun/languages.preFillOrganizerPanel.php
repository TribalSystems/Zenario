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


if ($refinerName == 'plugin') {
	$panel['db_items']['table'] = '
				[[DB_NAME_PREFIX]]languages AS l
			LEFT JOIN 
				[[DB_NAME_PREFIX]]visitor_phrases AS vp
			ON
				l.id = vp.language_id
			LEFT JOIN 
				[[DB_NAME_PREFIX]]modules pl
			ON 
				vp.module_class_name=pl.class_name';
	
	$panel['db_items']['id_column'] = 'l.id';
	
	
	foreach ($panel['columns'] as &$column) {
		if (trim(arrayKey($column, 'db_column')) == 'vp.language_id') {
			$column['db_column'] = 'l.id';
		}
	}
	
	$panel['columns']['phrase_count']['db_column'] = 'COUNT(DISTINCT IF (NOT [[REFINER__PLUGIN]] OR pl.id=[[REFINER__PLUGIN]],vp.code,NULL))';
	unset($panel['view_content']);

} elseif (($atLeastOneLanguageEnabled = checkRowExists('languages', array())) && $refinerName != 'not_enabled') {
	unset($panel['collection_buttons']['create']);
	$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_at_least_one_language_enabled'];

} else {
	unset($panel['view_content']);
	unset($panel['item_buttons']['import']);
	unset($panel['item_buttons']['export']);
	unset($panel['item_buttons']['delete']);
	unset($panel['collection_buttons']['add']);
	$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_languages_enabled'];
	
	if (!$atLeastOneLanguageEnabled) {
		$panel['title'] = adminPhrase('Enable a Language');
		$panel['popout_message'] =
			'<!--Message_Type:Warning-->'.
			adminPhrase(
<<<_text
<p>Zenario needs at least one Language to be enabled in order to run.</p>

<p>Please select a Language Pack from the panel - click it once and then click the &quot;Enable Language&quot; button.</p>

<p>If the language you require is not shown, you can create it by clicking on the button &quot;Create a Custom Language&quot;.</p>
_text
		);
	
	} else {
		$panel['title'] = adminPhrase('Enable another Language');
	}
}

if (in($mode, 'select', 'quick')) {
	unset($panel['popout_message']);
}

return false;
