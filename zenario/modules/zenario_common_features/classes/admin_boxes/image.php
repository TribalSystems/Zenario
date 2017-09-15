<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


class zenario_common_features__admin_boxes__image extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!$details = getRow(
			'files',
			array('id','filename', 'width', 'height', 'size', 'alt_tag', 'floating_box_title', 'short_checksum'),
			$box['key']['id'])
		) {
			exit;
		}
		
		$box['title'] = adminPhrase('Properties of the image "[[filename]]".', $details);
		
		$box['identifier']['value'] = adminPhrase('Image ID [[id]], checksum "[[short_checksum]]"', $details);
		
		$this->getImageHtmlSnippet($box['key']['id'], $box['tabs']['details']['fields']['image']['snippet']['html']);
		
		$details['filesize'] = formatFilesizeNicely($details['size'], 1, true);
		
		$box['tabs']['details']['fields']['size']['snippet']['html'] = 
			adminPhrase('[[filesize]] [[[width]] × [[height]]]', $details);
		
		$box['tabs']['details']['fields']['filename']['value'] = $details['filename'];
		$box['tabs']['details']['fields']['alt_tag']['value'] = $details['alt_tag'];
		$box['tabs']['details']['fields']['floating_box_title']['value'] = $details['floating_box_title'];
		
		
		//Load details on the image tags in use in the system, and which have been chosen here
		$sql = "
			SELECT it.name, itl.tag_id
			FROM ". DB_NAME_PREFIX. "image_tags AS it
			LEFT JOIN ". DB_NAME_PREFIX. "image_tag_link AS itl
			   ON itl.image_id = ". (int) $box['key']['id']. "
			  AND itl.tag_id = it.id
			ORDER BY it.name";
		$result = sqlSelect($sql);
		
		$pickedTagNames = array();
		while ($tag = sqlFetchAssoc($result)) {
			if ($tag['tag_id']) {
				$pickedTagNames[] = $tag['name'];
			}
		}

		
		$box['tabs']['details']['fields']['tags']['value'] = implode(',', $pickedTagNames);
		$box['tabs']['details']['fields']['tags']['tag_colors'] = getImageTagColours($byId = false, $byName = true);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (!$values['details/filename'] || !Ze\File::guessAltTagFromname($values['details/filename'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('Please enter a filename.');
		
		} elseif (Ze\File::mimeType($values['details/filename']) != getRow('files', 'mime_type', $box['key']['id'])) {
			$box['tabs']['details']['errors'][] = adminPhrase("You must not change the file's extension.");
		}
		
		$tags = explodeAndTrim($values['details/tags']);
		
		//Validate the tags
		foreach ($tags as $tagName) {
			$tagName = trim($tagName);
		
			if (preg_match('/\s/', $tagName) !== 0) {
				$box['tabs']['details']['errors']['spaces'] = adminPhrase("Tag names cannot contain spaces.");
		
			} elseif (!validateScreenName(trim($tagName))) {
				$box['tabs']['details']['errors']['alphanumeric'] = adminPhrase("Tag names can contain only alphanumeric characters, underscores or hyphens.");
			}
		}
		
		
		$box['confirm']['show'] = false;
		
		if (empty($box['tabs']['details']['errors'])) {
			
			if (!empty($tags)) {
				$existingTags = sqlFetchValues("
					SELECT name
					FROM ". DB_NAME_PREFIX. "image_tags
					WHERE name IN (". inEscape($tags, 'sql'). ")
				");
			
			
				$newTags = array();
				foreach ($tags as $tagName) {
					if (!in_array($tagName, $existingTags)) {
						$newTags[] = $tagName;
					}
				}
			
				if (!empty($newTags)) {
					$count = count($newTags);
					$lastNewTag = array_pop($newTags);
					$mrg = array('newTags' => implode(', ', $newTags), 'lastNewTag' => $lastNewTag);
					$box['confirm']['show'] = true;
					$box['confirm']['message'] = nAdminPhrase('The tag [[lastNewTag]] does not exist. Are you sure you wish to create it?',
						'The tags [[newTags]] and [[lastNewTag]] do not exist. Are you sure you wish to create them?', $count, $mrg);
					$box['confirm']['button_message'] = nAdminPhrase('Create tag', 'Create tags', $count);
				}
			}
		}

	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		exitIfNotCheckPriv('_PRIV_MANAGE_MEDIA');
		
		
		//Update the image's details
		updateRow(
			'files',
			array(
				'filename' => $values['details/filename'],
				'alt_tag' => $values['details/alt_tag'],
				'floating_box_title' => $values['details/floating_box_title']),
			$box['key']['id']);
		
		
		//Check whether any tags were picked
		if ($values['details/tags']
		 && ($tagNames = inEscape($values['details/tags'], 'sql'))) {
			//If so, remove any tags that weren't picked
			$sql = "
				DELETE itl.*
				FROM ". DB_NAME_PREFIX. "image_tag_link AS itl
				LEFT JOIN ". DB_NAME_PREFIX. "image_tags AS it
				   ON it.name IN (". $tagNames. ")
				  AND it.id = itl.tag_id
				WHERE it.id IS NULL
				  AND itl.image_id = ". (int) $box['key']['id'];
			sqlUpdate($sql);
			
			//Check all added tags are in the database
			//Note: this logic is only safe because validateAdminBox() and the inEscape() function above
			//will insure that there are no commas in the tag names.
			$sql = "
				INSERT IGNORE INTO ". DB_NAME_PREFIX. "image_tags (name)
				VALUES (". str_replace(',', '),(', $tagNames). ")";
			sqlUpdate($sql);
			
			//Add the tags that were picked
			$sql = "
				INSERT IGNORE INTO ". DB_NAME_PREFIX. "image_tag_link (image_id, tag_id)
				SELECT ". (int) $box['key']['id']. ", id
				FROM ". DB_NAME_PREFIX. "image_tags
				WHERE name IN (". $tagNames. ")";
			sqlUpdate($sql);
		
		} else {
			//If no tags were picked, just remove any unused tags.
			deleteRow('image_tag_link', array('image_id' => $box['key']['id']));
		}
	}
}
