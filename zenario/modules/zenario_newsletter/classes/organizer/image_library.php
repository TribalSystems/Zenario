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


class zenario_newsletter__organizer__image_library extends zenario_newsletter {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		 //...your PHP code...//
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		foreach ($panel['items'] as $id => &$item) {
			$text = '';
			$comma = false;
			$usage_newsletters = (int)$item['usage_newsletters'];
			$usage_newsletter_templates = (int)$item['usage_newsletter_templates'];
			$newsletterUsage = $usage_newsletters + $usage_newsletter_templates;
			if ($newsletterUsage === 1) {
				if ($usage_newsletters === 1) {
					$sql = '
						SELECT 
							n.newsletter_name
						FROM ' . DB_NAME_PREFIX . 'inline_images ii
						INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_NEWSLETTER_PREFIX . 'newsletters n
							ON ii.foreign_key_id = n.id
							AND ii.foreign_key_to = "newsletter"
						WHERE image_id = ' . $item['id'] . '
						AND archived = 0';
					$result = ze\sql::select($sql);
					$row = ze\sql::fetchAssoc($result);
					$text .= ze\admin::phrase('Used on "[[newsletter_name]]"', $row);
				} else {
					$sql = '
						SELECT 
							nt.name
						FROM ' . DB_NAME_PREFIX . 'inline_images ii
						INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_NEWSLETTER_PREFIX . 'newsletter_templates nt
							ON ii.foreign_key_id = nt.id
							AND ii.foreign_key_to = "newsletter_template"
						WHERE image_id = ' . $item['id'] . '
						AND archived = 0';
					$result = ze\sql::select($sql);
					$row = ze\sql::fetchAssoc($result);
					$text .= ze\admin::phrase('Used on "[[name]]"', $row);
				}
			} elseif ($newsletterUsage > 1) {
				$text .= 'Used on ';
				if ($usage_newsletters > 0) {
					$text .= ze\admin::nPhrase(
						'[[count]] newsletter',
						'[[count]] newsletters',
						$usage_newsletters,
						['count' => $usage_newsletters]
					);
					$comma = true;
				}
				if ($usage_newsletter_templates > 0) {
					if ($comma) {
						$text .= ', ';
					}
					$text .= ze\admin::nPhrase(
						'[[count]] newsletter template',
						'[[count]] newsletter templates',
						$usage_newsletter_templates,
						['count' => $usage_newsletter_templates]
					);
				}
			}
			$item['all_usage_newsletters'] = $text;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		//...your PHP code...//
	}
	
}