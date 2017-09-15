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




class zenario_ctype_audio extends module_base_class {

	var	$targetID = false;
	var	$targetVersion = false;
	var	$targetType = false;

	
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		
		if ($this->setting('show_details_and_link') == 'another_content_item') {
			$this->clearCacheBy(
				$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = false);
		} else {
			$this->clearCacheBy(
				$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = false);
		}

		$this->forcePageReload();
		return true;
	}

	function addToPageHead() {
	
		echo "<script type=\"text/javascript\" src=\"" . moduleDir('zenario_ctype_audio', 'player/audio-player.js') . "\"></script>  
				<script type=\"text/javascript\">  
				AudioPlayer.setup(\"" . absCMSDirURL() . moduleDir('zenario_ctype_audio', 'player/player.swf')  . "\", {  
				width: 290,  
				transparentpagebg: \"yes\",  
				left: \"000000\",  
				lefticon: \"FFFFFF\"  ,
				});  
				</script>";
	}

	function showSlot() {
		if ($this->setting('show_details_and_link')=='another_content_item'){
			$item = $this->setting('another_audio');
			if (count($arr = explode("_",$item))==2){
				$this->targetID = $arr[1];
				$this->targetType = $arr[0];
				if (!$this->targetVersion = getShowableVersion($this->targetID,$this->targetType)){
					return;
				}
			}
		}
		if (!($this->targetID && $this->targetVersion && $this->targetType)) {
			$this->targetID = $this->cID;
			$this->targetVersion = $this->cVersion;
			$this->targetType = $this->cType;
		}
		if ($this->targetType!='audio'){
			if ((int)($_SESSION['admin_userid'] ?? false)){
				echo "This Plugin needs to be placed on an Audio Content Item or be configured to point to another Audio Content Item. Please check your Plugin Settings.";
			}
			return;
		}

		$mergeFields = array();
		$subSections = array();
		$constraints = array();

		$mergeFields['initialvolume'] = $this->setting('initial_volume');
		if ($this->setting('autostart')) {
			$mergeFields['autostart'] = "yes";
		} else {
			$mergeFields['autostart'] = "no";
		}

		if ($this->setting('remaining')) {
			$mergeFields['remaining'] = "yes";
		} else {
			$mergeFields['remaining'] = "no";
		}

		if ($this->setting('rtl')) {
			$mergeFields['rtl'] = "yes";
		} else {
			$mergeFields['rtl'] = "no";
		}

		if ($this->setting('animation')) {
			$mergeFields['animation'] = "yes";
		} else {
			$mergeFields['animation'] = "no";
		}
		
		if ($this->setting('loop')) {
			$mergeFields['loop'] = "yes";
		} else {
			$mergeFields['loop'] = "no";
		}
		

		$contentItemDetails = getRow('content_item_versions',array('title','file_id'),array('id'=>$this->targetID,'type'=>$this->targetType,'version'=>$this->targetVersion));
		
		$subSections['Audio'] = true;
		$mergeFields['Container_id'] = $this->containerId;
		$mergeFields['Size'] = formatFilesizeNicely(getRow('files','size',array('id'=> ($contentItemDetails['file_id'] ?? false))), 0, false, 'zenario_ctype_audio');
		$mergeFields['title'] = $contentItemDetails['title'] ?? false;
		Ze\File::contentLink($url, $this->targetID, $this->targetType, $this->targetVersion);
		$mergeFields['mp3Path'] = $url;
			
		$this->framework('mp3', $mergeFields, $subSections);
		
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
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
					$box['tabs']['file']['fields']['file']['upload']['extensions'] = array('.mp3');
				}
				
				break;
		}
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_content':
				if ($box['key']['cType'] == 'audio') {
					if (engToBoolean($box['tabs']['file']['edit_mode']['on'] ?? false)) {
						if (!$values['file/file']) {
							if ($saving) {
								$box['tabs']['file']['errors'][] = adminPhrase('Please select a file.');
							}
						
						} elseif ($path = Ze\File::getPathOfUploadedInCacheDir($values['file/file'])) {
							if (setting('content_max_filesize') < filesize($path)) {
								$box['tabs']['file']['errors'][] = adminPhrase('This file is larger than the Maximum Content File Size as set in the Site Settings.');
						
							} elseif (!$this->isFileTypeAllowed($path)) {
								$box['tabs']['file']['errors'][] = adminPhrase('Please select an MP3 file.');
							}
						}
					}
				} 
				break;
		}
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}

	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
				if (isset($panel['collection_buttons']['zenario_ctype_audio__create_multiple'])) {
					if ($panel['key']['cType'] != 'audio') {
						unset($panel['collection_buttons']['zenario_ctype_audio__create_multiple']);
					} else {
						$panel['collection_buttons']['zenario_ctype_audio__create_multiple']['tooltip'] = 
							adminPhrase('Create multiple Audio files in the Language "[[lang]]"',
								array('lang' => getLanguageName(ifNull($panel['key']['language'] ?? false, cms_core::$defaultLang))));
					}
				}
				
				break;
		}
	}


	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__content/panels/content':
				//Handle creating multiple Audios at once in Storekeeper
				if (($_POST['create_multiple'] ?? false) && checkPriv('_PRIV_CREATE_FIRST_DRAFT')) {
					$newIds = array();
					
					//This sholud only be allowed if we know what the language will be
					if (($languageId = ifNull($_POST['language'] ?? false, cms_core::$defaultLang))) {
						
						if ($_REQUEST['refiner__template'] ?? false) {
							$cType = getRow('layouts', 'content_type', ($_REQUEST['refiner__template'] ?? false));
						} else {
							$cType = $_POST['cType'] ?? false;
						}
						
						if ($cType == 'audio') {
							
							if ($_REQUEST['refiner__template'] ?? false) {
								$layoutId = $_REQUEST['refiner__template'] ?? false;
							} else {
								$layoutId = getRow('content_types', 'default_layout_id', array('content_type_id' => $cType));
							}
							
							
							exitIfUploadError();
							if (!$this->isFileTypeAllowed($_FILES['Filedata']['name'])) {
								echo
									adminPhrase(
										'The [[file]] is not an MP3 file.',
										array('file' => htmlspecialchars($_FILES['Filedata']['name'])));
							
							} elseif (setting('content_max_filesize') < filesize($_FILES['Filedata']['tmp_name'])) {
								echo
									adminPhrase(
										'The [[file]] is larger than the Maximum Content File Size as set in the Site Settings.',
										array('file' => htmlspecialchars($_FILES['Filedata']['name'])));
							
							} else {
								$filename = preg_replace('/([^.a-z0-9]+)/i', '_', $_FILES['Filedata']['name']);
								
								if ($fileId = Ze\File::addToDocstoreDir(
									'content',
									$_FILES['Filedata']['tmp_name'], $filename)
								) {
									$cID = $cVersion = false;
									createDraft($cID, false, $cType, $cVersion, false, $languageId);
									setRow(
										'content_item_versions',
										array('layout_id' => $layoutId, 'title' => $filename, 'filename' => $filename, 'file_id' => $fileId),
										array('id' => $cID, 'type' => $cType, 'version' => $cVersion));
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
		$mimeType = Ze\File::mimeType($filename);
		
		return ($mimeType == 'audio/mpeg');
	}


}
