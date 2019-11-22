<?php
/*
 * Copyright (c) 2019, Tribal Limited
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
	case 'zenario__social/nav/forums/panel':
		if (!ze\priv::check('_PRIV_REORDER_MENU_ITEM')) {
			unset($panel['item']['tooltip']);
		}
		
		foreach($panel['items'] as &$item) {
			$cTypeAndCID = explode('_', $item['forum_link']);
			$item['title'] = ze\content::title($cTypeAndCID[1], $cTypeAndCID[0]);
			$item['frontend_link'] = ze\link::toItem($cTypeAndCID[1], $cTypeAndCID[0]);
			
			$item['traits'] = [];
			if (!$item['thread_count'] && !$item['post_count']) {
				$item['traits']['empty'] = true;
			}
			if ($item['thread_link'] && $item['thread_link'] != '_0') {
				$item['traits']['has_thread_page'] = true;
			}
			if ($item['new_thread_link'] && $item['new_thread_link'] != '_0') {
				$item['traits']['has_new_thread_page'] = true;
			}
			
			if ($item['locked']) {
				$item['traits']['locked'] = true;
			} else {
				$item['traits']['unlocked'] = true;
			}
			
			$item['categories'] = '';
			if ($categories = ze\category::contentItemCategories($cTypeAndCID[1], $cTypeAndCID[0])) {
				foreach ($categories as $i => $category) {
					$item['categories'] .= ($i? ', ' : ''). $category['name'];
				}
			}
		}
		
		break;
}

?>