<?php
/*
 * Copyright (c) 2020, Tribal Limited
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


class zenario_common_features__admin_boxes__image extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!$details = ze\row::get(
			'files',
			['id', 'filename', 'width', 'height', 'size', 'alt_tag', 'floating_box_title', 'short_checksum', 'privacy'],
			$box['key']['id'])
		) {
			exit;
		}
		
		$box['title'] = ze\admin::phrase('Properties of the image "[[filename]]".', $details);
		
		$box['identifier']['value'] = ze\admin::phrase('Image ID [[id]], checksum "[[short_checksum]]"', $details);
		
		$this->getImageHtmlSnippet($box['key']['id'], $box['tabs']['details']['fields']['image']['snippet']['html']);
		
		$details['filesize'] = ze\lang::formatFilesizeNicely($details['size'], 1, true);
		
		$box['tabs']['details']['fields']['size']['snippet']['html'] = 
			ze\admin::phrase('{{filesize}} [{{width}} × {{height}}px]', $details, false, '{{', '}}');
		
		$box['tabs']['details']['fields']['filename']['value'] = $details['filename'];
		$box['tabs']['details']['fields']['alt_tag']['value'] = $details['alt_tag'];
		$box['tabs']['details']['fields']['floating_box_title']['value'] = $details['floating_box_title'];
		
		
		//Load details on the image tags in use in the system, and which have been chosen here
		$sql = "
			SELECT it.name, itl.tag_id
			FROM ". DB_PREFIX. "image_tags AS it
			LEFT JOIN ". DB_PREFIX. "image_tag_link AS itl
			   ON itl.image_id = ". (int) $box['key']['id']. "
			  AND itl.tag_id = it.id
			ORDER BY it.name";
		$result = ze\sql::select($sql);
		
		$pickedTagNames = [];
		while ($tag = ze\sql::fetchAssoc($result)) {
			if ($tag['tag_id']) {
				$pickedTagNames[] = $tag['name'];
			}
		}

		
		$box['tabs']['details']['fields']['tags']['value'] = implode(',', $pickedTagNames);
		$box['tabs']['details']['fields']['tags']['tag_colors'] = ze\contentAdm::getImageTagColours($byId = false, $byName = true);
		
		
		switch ($details['privacy']) {
			case 'auto':
				$fields['details/privacy_auto']['hidden'] = false;
				break;
			
			case 'private':
				$fields['details/privacy_private']['hidden'] = false;
				break;
			
			case 'public':
				$fields['details/privacy_public']['hidden'] = false;
				
				$mrg = [];
				$mrg['path'] = 'public/images/'. $details['short_checksum']. '/'. ze\file::safeName($details['filename']);
				$mrg['link'] = ze\link::absolute(). $mrg['path'];
				
				$fields['details/privacy_public']['note_below'] =
					ze\admin::phrase('This image can be publicly accessed via the URL [[link]], internal references to the image should be via [[path]]', $mrg);
				
				if (!file_exists(CMS_ROOT. $mrg['path'])) {
					$fields['details/missing_public_image']['hidden'] = false;
				}
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (!$values['details/filename'] || !ze\file::guessAltTagFromname($values['details/filename'])) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('Please enter a filename.');
		
		} elseif (ze\file::mimeType($values['details/filename']) != ze\row::get('files', 'mime_type', $box['key']['id'])) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase("You must not change the file's extension.");
		
		} elseif ($values['details/filename'] !== ze\file::safeName($values['details/filename'])) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('The filename must not contain any of the following characters: \\ / : ; * ? " < > |');
		}
		
		$tags = ze\ray::explodeAndTrim($values['details/tags']);
		
		//Validate the tags
		foreach ($tags as $tagName) {
			$tagName = trim($tagName);
		
			if (preg_match('/\s/', $tagName) !== 0) {
				$box['tabs']['details']['errors']['spaces'] = ze\admin::phrase("Tag names cannot contain spaces.");
		
			} elseif (!ze\ring::validateScreenName(trim($tagName))) {
				$box['tabs']['details']['errors']['alphanumeric'] = ze\admin::phrase("Tag names can contain only alphanumeric characters, underscores or hyphens.");
			}
		}
		
		
		$box['confirm']['show'] = false;
		
		if (empty($box['tabs']['details']['errors'])) {
			
			if (!empty($tags)) {
				$existingTags = ze\sql::fetchValues("
					SELECT name
					FROM ". DB_PREFIX. "image_tags
					WHERE name IN (". ze\escape::in($tags, 'sql'). ")
				");
			
			
				$newTags = [];
				foreach ($tags as $tagName) {
					if (!in_array($tagName, $existingTags)) {
						$newTags[] = $tagName;
					}
				}
			
				if (!empty($newTags)) {
					$count = count($newTags);
					$lastNewTag = array_pop($newTags);
					$mrg = ['newTags' => implode(', ', $newTags), 'lastNewTag' => $lastNewTag];
					$box['confirm']['show'] = true;
					$box['confirm']['message'] = ze\admin::nPhrase('The tag [[lastNewTag]] does not exist. Are you sure you wish to create it?',
						'The tags [[newTags]] and [[lastNewTag]] do not exist. Are you sure you wish to create them?', $count, $mrg);
					$box['confirm']['button_message'] = ze\admin::nPhrase('Create tag', 'Create tags', $count);
				}
			}
		}

	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_MANAGE_MEDIA');
		
		
		//Update the image's details
		ze\row::update(
			'files',
			[
				'filename' => $values['details/filename'],
				'alt_tag' => $values['details/alt_tag'],
				'floating_box_title' => $values['details/floating_box_title']],
			$box['key']['id']);
		
		
		//Check whether any tags were picked
		if ($values['details/tags']
		 && ($tagNames = ze\escape::in($values['details/tags'], 'sql'))) {
			//If so, remove any tags that weren't picked
			$sql = "
				DELETE itl.*
				FROM ". DB_PREFIX. "image_tag_link AS itl
				LEFT JOIN ". DB_PREFIX. "image_tags AS it
				   ON it.name IN (". $tagNames. ")
				  AND it.id = itl.tag_id
				WHERE it.id IS NULL
				  AND itl.image_id = ". (int) $box['key']['id'];
			ze\sql::update($sql);
			
			//Check all added tags are in the database
			//Note: this logic is only safe because validateAdminBox() and the ze\escape::in() function above
			//will insure that there are no commas in the tag names.
			$sql = "
				INSERT IGNORE INTO ". DB_PREFIX. "image_tags (name)
				VALUES (". str_replace(',', '),(', $tagNames). ")";
			ze\sql::update($sql);
			
			//Add the tags that were picked
			$sql = "
				INSERT IGNORE INTO ". DB_PREFIX. "image_tag_link (image_id, tag_id)
				SELECT ". (int) $box['key']['id']. ", id
				FROM ". DB_PREFIX. "image_tags
				WHERE name IN (". $tagNames. ")
				ORDER BY id";
			ze\sql::update($sql);
		
		} else {
			//If no tags were picked, just remove any unused tags.
			ze\row::delete('image_tag_link', ['image_id' => $box['key']['id']]);
		}
	}
}
