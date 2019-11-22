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


class zenario_ctype_audio extends ze\moduleBaseClass {
	
	protected $data = [];
	
	function init() {
		$clearByContent = $this->setting('show_details_and_link') == 'another_content_item';
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = false);
		
		return true;
	}

	function showSlot() {
		$targetID = $targetVersion = $targetType = false;
		if ($this->setting('show_details_and_link') == 'another_content_item'){
			$item = $this->setting('another_audio');
			if (count($arr = explode("_",$item))==2){
				$targetID = $arr[1];
				$targetType = $arr[0];
				if (!$targetVersion = ze\content::showableVersion($targetID,$targetType)){
					return;
				}
			}
		}
		if (!($targetID && $targetVersion && $targetType)) {
			$targetID = $this->cID;
			$targetVersion = $this->cVersion;
			$targetType = $this->cType;
		}
		if ($targetType != 'audio'){
			if (ze\admin::id()) {
				echo "This Plugin needs to be placed on an Audio Content Item or be configured to point to another Audio Content Item. Please check your Plugin Settings.";
			}
			return;
		}
		
		$this->data['mp3'] = true;
		$this->data['autostart'] = $this->setting('autostart');
		$this->data['loop'] = $this->setting('loop');
		
		$contentItemDetails = ze\row::get('content_item_versions', ['title', 'file_id'], ['id' => $targetID, 'type' => $targetType, 'version' => $targetVersion]);
		
		$this->data['Size'] = ze\lang::formatFilesizeNicely(ze\row::get('files','size', ['id' => ($contentItemDetails['file_id'] ?? false)]), 0, false, 'zenario_ctype_audio');
		$this->data['title'] = $contentItemDetails['title'] ?? false;
		ze\file::contentLink($url, $targetID, $targetType, $targetVersion);
		$this->data['mp3Path'] = $url;
		
		
		$this->twigFramework($this->data);
	}


	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes){
		switch ( $path ){
		    case 'plugin_settings':
		        $box['tabs']['first_tab']['fields']['another_audio']['hidden'] = !(($values['first_tab/show_details_and_link'] ?? false)=='another_content_item');
		        break;
			
			case 'zenario_content':
				if ($box['key']['cType'] == 'audio') {
					$box['tabs']['file']['hidden'] = false;
					$box['tabs']['file']['fields']['file']['upload']['accept'] = 'audio/*';
					$box['tabs']['file']['fields']['file']['upload']['extensions'] = ['.mp3'];
				}
				break;
		}
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_content':
				if ($box['key']['cType'] == 'audio') {
					if (ze\ring::engToBoolean($box['tabs']['file']['edit_mode']['on'] ?? false)) {
						if (!$values['file/file']) {
							if ($saving) {
								$box['tabs']['file']['errors'][] = ze\admin::phrase('Please select a file.');
							}
						
						} elseif ($path = ze\file::getPathOfUploadInCacheDir($values['file/file'])) {
							if (ze::setting('content_max_filesize') < filesize($path)) {
								$box['tabs']['file']['errors'][] = ze\admin::phrase('This file is larger than the Maximum Content File Size as set in the Site Settings.');
						
							} elseif (!$this->isFileTypeAllowed($path)) {
								$box['tabs']['file']['errors'][] = ze\admin::phrase('Please select an MP3 file.');
							}
						}
					}
				} 
				break;
		}
	}

	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
				if (isset($panel['collection_buttons']['zenario_ctype_audio__create_multiple'])) {
					if ($panel['key']['cType'] != 'audio') {
						unset($panel['collection_buttons']['zenario_ctype_audio__create_multiple']);
					} else {
						$panel['collection_buttons']['zenario_ctype_audio__create_multiple']['tooltip'] = 
							ze\admin::phrase('Create multiple Audio files in the Language "[[lang]]"',
								['lang' => ze\lang::name((($panel['key']['language'] ?? false) ?: ze::$defaultLang))]);
					}
				}
				break;
		}
	}


	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__content/panels/content':
				//Handle creating multiple Audios at once in Storekeeper
				if (($_POST['create_multiple'] ?? false) && ze\priv::check('_PRIV_CREATE_FIRST_DRAFT', false, 'audio')) {
					$newIds = [];
					
					//This sholud only be allowed if we know what the language will be
					if (($languageId = (($_POST['language'] ?? false) ?: ze::$defaultLang))) {
						
						if ($_REQUEST['refiner__template'] ?? false) {
							$cType = ze\row::get('layouts', 'content_type', ($_REQUEST['refiner__template'] ?? false));
						} else {
							$cType = $_POST['cType'] ?? false;
						}
						
						if ($cType == 'audio') {
							
							if ($_REQUEST['refiner__template'] ?? false) {
								$layoutId = $_REQUEST['refiner__template'] ?? false;
							} else {
								$layoutId = ze\row::get('content_types', 'default_layout_id', ['content_type_id' => $cType]);
							}
							
							
							ze\fileAdm::exitIfUploadError();
							if (!$this->isFileTypeAllowed($_FILES['Filedata']['name'])) {
								echo
									ze\admin::phrase(
										'The [[file]] is not an MP3 file.',
										['file' => htmlspecialchars($_FILES['Filedata']['name'])]);
							
							} elseif (ze::setting('content_max_filesize') < filesize($_FILES['Filedata']['tmp_name'])) {
								echo
									ze\admin::phrase(
										'The [[file]] is larger than the Maximum Content File Size as set in the Site Settings.',
										['file' => htmlspecialchars($_FILES['Filedata']['name'])]);
							
							} else {
								$filename = preg_replace('/([^.a-z0-9]+)/i', '_', $_FILES['Filedata']['name']);
								
								if ($fileId = ze\file::addToDocstoreDir(
									'content',
									$_FILES['Filedata']['tmp_name'], $filename)
								) {
									$cID = $cVersion = false;
									ze\contentAdm::createDraft($cID, false, $cType, $cVersion, false, $languageId);
									ze\row::set(
										'content_item_versions',
										['layout_id' => $layoutId, 'title' => $filename, 'filename' => $filename, 'file_id' => $fileId],
										['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
									$newIds[] = $cType. '_'. $cID;
								}
							}
						}
					}
					
					return $newIds;
				}
				break;
		}
	}
	
	protected function isFileTypeAllowed($filename) {
		return ze\file::mimeType($filename) == 'audio/mpeg';
	}

}
