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


class zenario_common_features__organizer__start_page extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		//...
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		if (ze::setting('site_disabled_title') && !ze::setting('site_enabled')) {
			$panel['title'] = ze\admin::phrase('Welcome to the Organizer start page (this site is disabled)');
		} elseif (ze::setting('site_in_dev_mode')) {
			$panel['title'] = ze\admin::phrase('Welcome to the Organizer start page (this site is in developer mode)');
		} else {
			$panel['title'] = ze\admin::phrase('Welcome to the Organizer start page');
		}
		
		$links = [];
		
		//Check which page is the home page
		$homePageCID = $homePageCType = false;
		ze\content::langSpecialPage('zenario_home', $homePageCID, $homePageCType, ze::$defaultLang);
		
		//Get a link to it
		$homeLink = ze\link::toItem($homePageCID, $homePageCType, true);
		
		//Set a link on the start page
		$links[] = [
			'type' => 'home_page',
			'css_class' => 'home_page_button',
			'href' => $homeLink,
			'title' => ze\admin::phrase('Home page'),
			'text' => ze\admin::phrase('Go to site home page')
		];
		
		//Check if the admin just came from a page, and if that's a different page to the home page
		if (!empty($_GET['fromCID'])
		 && !empty($_GET['fromCType'])
		 && ($_GET['fromCID'] != $homePageCID
		  || $_GET['fromCType'] != $homePageCType)) {
			
			//Get a link to it
			$backLink = ze\link::toItem($_GET['fromCID'], $_GET['fromCType'], true);
			
			//Also get its tag name
			$mrg = [
				'cItem' => ze\content::formatTag($_GET['fromCID'], $_GET['fromCType'])
			];
			
			//Set a link back to the page the admin just came from
			$links[] = [
				'type' => 'last_page',
				'css_class' => 'last_page_button',
				'href' => $backLink,
				'title' => ze\admin::phrase('Last page'),
				'text' => ze\admin::phrase('Go to [[cItem]]', $mrg)
			];
		}
		
		//If there are any content items with a draft, list them. Also display a link to the "Work in progress" panel.
		$wipCount = ze\row::count('content_items', ['status' => ['first_draft', 'published_with_draft', 'unlisted_with_draft', 'hidden_with_draft', 'trashed_with_draft']]);
		if ($wipCount > 0) {
			$statusFormattedNicely = [
				'first_draft' => ze\admin::phrase('First draft'),
				'published_with_draft' => ze\admin::phrase('Published with draft'),
				'unlisted_with_draft' => ze\admin::phrase('Published with draft'),
				'hidden_with_draft' => ze\admin::phrase('Hidden with draft'),
				'trashed_with_draft' => ze\admin::phrase('Trashed with draft')
            ];
            
			$wipContentItems = ze\row::getArray('content_items', ['id', 'type', 'alias', 'status', 'admin_version'], ['status' => ['first_draft', 'published_with_draft', 'unlisted_with_draft', 'hidden_with_draft', 'trashed_with_draft']]);
			
			//Up to 20 WIP content items should appear.
			$displayedWipContentItems = 0;
			foreach ($wipContentItems as $wipContentItem) {
				$displayedWipContentItems++;
				
				if ($displayedWipContentItems > 20) {
					break;
				}
				
				$links[] = [
					'type' => 'wip_content_item',
					'href' => ze\link::toItem($wipContentItem['id'], $wipContentItem['type']),
					'title' => ze\content::title($wipContentItem['id'], $wipContentItem['type'], $wipContentItem['admin_version']),
					'tag_and_alias' => ze\content::formatTag($wipContentItem['id'], $wipContentItem['type'], $wipContentItem['alias']),
					'status' => $wipContentItem['status'],
					'status_formatted_nicely' => $statusFormattedNicely[$wipContentItem['status']]
				];
			}
			
			$links[] = [
				'type' => 'wip_page',
				'css_class' => 'wip_page_button',
				'href' => 'organizer.php?fromCID=6&fromCType=html#zenario__content/panels/content/refiners/work_in_progress////',
				'title' => ze\admin::phrase('Work in progress '),
				'text' => ze\admin::phrase($wipCount.' content items in draft form'),
				'record_count' => $wipCount
			];
		}
			
		$panel['wip_content_links'] = $links;
		
		//Also show enabled content types.
		$enabledContentTypes = ze\content::getContentTypes(false, false);
		foreach ($enabledContentTypes as $enabledContentTypeName => &$enabledContentTypeData) {
			$publishedAndUnlistedCount = ze\row::count('content_items', ['type' => $enabledContentTypeName, 'status' => ['published', 'unlisted']]);
			$revisedDraftCount = ze\row::count('content_items', ['type' => $enabledContentTypeName, 'status' => ['published_with_draft', 'unlisted_with_draft', 'hidden_with_draft', 'trashed_with_draft']]);
			$firstDraftCount = ze\row::count('content_items', ['type' => $enabledContentTypeName, 'status' => 'first_draft']);
			
			//The "published" count phrase should always be visible.
			//The count phrases for "revised draft" and "first draft" should only appear if there are > 0 items.
			$enabledContentTypeData['published_and_unlisted_count_phrase'] = ze\admin::nPhrase('1 published', '[[count]] published', $publishedAndUnlistedCount, ['count' => $publishedAndUnlistedCount]);
			
			if ($revisedDraftCount > 0) {
				$enabledContentTypeData['revised_draft_count_phrase'] = ze\admin::nPhrase('1 revised draft', '[[count]] revised drafts', $revisedDraftCount, ['count' => $revisedDraftCount]);
			}
			
			if ($firstDraftCount > 0) { 
				$enabledContentTypeData['first_draft_count_phrase'] = ze\admin::nPhrase('1 new draft', '[[count]] new drafts', $firstDraftCount, ['count' => $firstDraftCount]);
			}
		}
		$panel['enabled_content_types'] = $enabledContentTypes;
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		//...
	}
}
