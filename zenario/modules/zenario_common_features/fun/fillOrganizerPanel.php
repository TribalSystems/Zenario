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

switch ($path) {
	
	
	case 'zenario__menu/nav/default_language/panel/tree_explorer':
		$panel['html'] = '
			<iframe
				class="zenario_tree_explorer_iframe"
				style="width: 100%; height: 100%;"
				src="'. htmlspecialchars(
					absCMSDirURL(). 'zenario/admin/tree_explorer/index.php'.
						'?language='. urlencode(FOCUSED_LANGUAGE_ID__NO_QUOTES).
						'&type='. urlencode($refinerName).
						'&id='. urlencode($refinerId).
						'&og=1'
			). '"></iframe>';
		
		break;
	case 'zenario__content/panels/documents':
		
		if (!setting('enable_document_tags')) {
			unset($panel['collection_buttons']['document_tags']);
		}
		
		if (isset($panel['item_buttons']['autoset'])
		 && !checkRowExists('document_rules', array())) {
			$panel['item_buttons']['autoset']['disabled'] = true;
			$panel['item_buttons']['autoset']['disabled_tooltip'] = adminPhrase('No rules for auto-setting document metadata have been created');
		}
		
		foreach ($panel['items'] as &$item) {
			$filePath = "";
			$fileId = "";
			if ($item['type'] == 'folder') {
				$tempArray = array();
				$item['css_class'] = 'zenario_folder_item';
				$item['traits']['is_folder'] = true;
				$tempArray = getRowsArray('documents', 'id', array('folder_id' => $item['id']));
				$item['folder_file_count'] = count($tempArray);
				if (!$item['folder_file_count']) {
					$item['traits']['is_empty_folder'] = true;
				}
				$item['extract_wordcount'] = 
				$item['privacy'] = '';
			} else {
			
			/* if one document has public link */
				$document = getRow('documents', array('file_id', 'filename'), $item['id']);
				
				$file = getRow('files', 
								array('id', 'filename', 'path', 'created_datetime', 'short_checksum'),
								$document['file_id']);
				
				if($document['filename']) {
					$dirPath = 'public' . '/downloads/' . $file['short_checksum'];
					$frontLink = $dirPath . '/' . $document['filename'];
					$symPath = CMS_ROOT . $frontLink;
					$symFolder =  CMS_ROOT . $dirPath;
					
					if (is_file($symPath)) {
						$item['traits']['public_link'] = true;
						$item['frontend_link'] = $frontLink;
						$publicLink = true;
					}
				}
				
				//change icon
				$item['css_class'] = 'zenario_file_item';
				$sql = "
					SELECT
						file_id,
						extract_wordcount,
						SUBSTR(extract, 1, 40) as extract
					FROM ".  DB_NAME_PREFIX. "documents
					WHERE id = ". (int) $item['id'];
				
				$result = sqlSelect($sql);
				$documentDetails = sqlFetchAssoc($result);
				if (!empty($documentDetails['extract_wordcount'])) {
					$documentDetails['extract_wordcount'] .= ', ';
				}
				$item['plaintext_extract_details'] = 'Word count: '.$documentDetails['extract_wordcount'].$documentDetails['extract'];
				$fileId = $documentDetails['file_id'];
				if ($fileId && empty($item['frontend_link'])) {
					$filePath = fileLink($fileId);
					$item['frontend_link'] = $filePath;
				}
				$filenameInfo = pathinfo($item['name']);
				if(isset($filenameInfo['extension'])) {
					$item['type'] = $filenameInfo['extension'];
				}
			}
			$item['tooltip'] = $item['name'];
			if (strlen($item['name']) > 50) {
				$item['name'] = substr($item['name'], 0, 25) . "..." .  substr($item['name'], -25);
			}
			if ($fileId && docstoreFilePath($fileId)) {
				$item['filesize'] = fileSizeConvert(filesize(docstoreFilePath($fileId)));
			}
			$item['css_class'] .= ' zenario_document_privacy_' . $item['privacy'];
			
			if ($item['date_uploaded']) {
				$item['date_uploaded'] = adminPhrase('Uploaded [[date]]', array('date' => formatDateTimeNicely($item['date_uploaded'], '_MEDIUM')));
			}
			if ($item['extract_wordcount']) {
				$item['extract_wordcount'] = nAdminPhrase(
					'[[extract_wordcount]] word', 
					'[[extract_wordcount]] words',
					$item['extract_wordcount'],
					$item
				);
			} else {
				$item['extract_wordcount'] = '';
			}
		}
		
		if (count($panel['items']) <= 0) {
			unset($panel['collection_buttons']['reorder_root']);
		}
		
		break;
		
	
	case 'zenario__modules/panels/modules/hidden_nav/view_frameworks/panel':
		
		if ($refinerName == 'module' && ($module = getModuleDetails(get('refiner__module')))) {
			$panel['title'] =
				adminPhrase('Frameworks for the Module "[[name]]"', array('name' => $module['display_name']));
			
			$panel['items'] = array();
			foreach (listModuleFrameworks($module['class_name']) as $dir => $framework) {
				$panel['items'][encodeItemIdForOrganizer($dir)] = $framework;
			}
		}
		
		break;

}

return false;
