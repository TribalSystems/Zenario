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


switch ($path) {
	case 'plugin_settings':
		$fields['each_item/author_retina']['hidden'] = 
			!$values['each_item/show_author_image'];
		
		$hidden = !$values['each_item/show_author_image'];
		$this->showHideImageOptions($fields, $values, 'each_item', $hidden, 'author_');
		
		$fields['overall_list/heading_if_items']['hidden'] = 
		$fields['overall_list/heading_tags']['hidden'] = 
			($values['show_headings'] != 1);
		
		$fields['heading_if_no_items']['hidden'] = ($values['show_headings_if_no_items'] != 1);
		
		$fields['overall_list/more_link_text']['hidden'] =
		$fields['overall_list/more_hyperlink_target']['hidden'] =
			!$values['overall_list/show_more_link'];
		
		$fields['each_item/retina']['hidden'] = 
		$fields['each_item/fall_back_to_default_image']['hidden'] = 
			!$values['each_item/show_sticky_images'];
		
		$fields['each_item/default_image_id']['hidden'] = 
			!($values['each_item/show_sticky_images'] && $values['each_item/fall_back_to_default_image']);
		
		$hidden = !$values['each_item/show_sticky_images'];
		$this->showHideImageOptions($fields, $values, 'each_item', $hidden);
		
		$fields['each_item/date_format']['hidden'] = 
		$fields['each_item/show_times']['hidden'] = 
			!$values['each_item/show_dates'];
		
		$fields['hidden'] = 
			$values['first_tab/content_type'] != 'all'
		 && $values['first_tab/content_type'] != 'document';
		
		$fields['pagination/page_limit']['hidden'] = 
		$fields['pagination/pagination_style']['hidden'] = 
			!$values['pagination/show_pagination'];
		
		
		//Don't show the translations checkbox if this can never be translated
		$fields['overall_list/translate_text']['hidden'] =
			$box['key']['isVersionControlled']
		 || !ze\row::exists('languages', ['translate_phrases' => 1]);
		
		//Don't show notes about translations if this won't be translated
		if ($fields['overall_list/translate_text']['hidden'] || !$values['overall_list/translate_text']) {
			$fields['overall_list/heading_if_items']['show_phrase_icon'] =
			$fields['overall_list/heading_if_no_items']['show_phrase_icon'] =
			$fields['overall_list/more_link_text']['show_phrase_icon'] = false;
			
			$fields['overall_list/heading_if_items']['side_note'] =
			$fields['overall_list/heading_if_no_items']['side_note'] =
			$fields['overall_list/more_link_text']['side_note'] = '';
		
		} else {
			
			$mrg = [
				'def_lang_name' => htmlspecialchars(ze\lang::name(ze::$defaultLang)),
				'phrases_panel' => htmlspecialchars(ze\link::absolute(). 'zenario/admin/organizer.php#zenario__languages/panels/phrases')
			];
			
			$fields['overall_list/heading_if_items']['show_phrase_icon'] =
			$fields['overall_list/heading_if_no_items']['show_phrase_icon'] =
			$fields['overall_list/more_link_text']['show_phrase_icon'] = true;
			
			$fields['overall_list/heading_if_items']['side_note'] = 
			$fields['overall_list/heading_if_no_items']['side_note'] =
			$fields['overall_list/more_link_text']['side_note'] =
				ze\admin::phrase('Enter text in [[def_lang_name]], this site\'s default language. <a href="[[phrases_panel]]" target="_blank">Click here to manage translations in Organizer.</a>.', $mrg);
		}
		
		
		if (!$values['first_tab/only_show_child_items']) {
			$fields['first_tab/child_item_levels']['hidden'] = 
			$fields['first_tab/show_secondaries']['hidden'] = true;
		
			unset($box['tabs']['overall_list']['fields']['order']['values']['Menu']);
		
			if ($values['overall_list/order'] == 'Menu') {
				$fields['overall_list/order']['current_value'] = 'Alphabetically';
			}
	
		} else {
			$fields['first_tab/child_item_levels']['hidden'] = 
			$fields['first_tab/show_secondaries']['hidden'] = false;
		
			if ($values['first_tab/child_item_levels'] != 1) {
				$fields['overall_list/order']['values']['Menu'] = ze\admin::phrase('Menu level then ordinal');
			} else {
				$fields['overall_list/order']['values']['Menu'] = ze\admin::phrase('Menu ordinal');
			}
		}
		
		$fields['first_tab/specific_languages']['hidden'] = $values['first_tab/language_selection'] != 'specific_languages';
	
	
	
		//datepicker
		$releaseDateValue = $values['release_date'];
		if ($releaseDateValue == "date_range"){
			$fields['start_date']['hidden'] = false;
			$fields['end_date']['hidden'] = false;
		}else{
			$fields['start_date']['hidden'] = true;
			$fields['end_date']['hidden'] = true;
		}
	
		if ($releaseDateValue == "relative_date_range"){
			$fields['relative_operator']['hidden'] = false;
			$fields['relative_value']['hidden'] = false;
			$fields['relative_units']['hidden'] = false;
		}else{
			$fields['relative_operator']['hidden'] = true;
			$fields['relative_value']['hidden'] = true;
			$fields['relative_units']['hidden'] = true;
		}
	
	
		if ($releaseDateValue == "prior_to_date"){
			$fields['prior_to_date']['hidden'] = false;
		}else{
			$fields['prior_to_date']['hidden'] = true;
		}
	
		if ($releaseDateValue == "on_date"){
			$fields['on_date']['hidden'] = false;
		}else{
			$fields['on_date']['hidden'] = true;
		}
	
		if ($releaseDateValue == "after_date"){
			$fields['after_date']['hidden'] = false;
		}else{
			$fields['after_date']['hidden'] = true;
		}
		
		
		//Show a warning on the pagination tab if the "Call a module's static method to decide" option is selected
		if (isset($fields['each_item/hide_private_items'])
		 && isset($box['tabs']['pagination']['notices']['using_static_method'])) {
			$box['tabs']['pagination']['notices']['using_static_method']['show'] = 
				$values['each_item/hide_private_items'] == 3;
		}
		
		break;
}