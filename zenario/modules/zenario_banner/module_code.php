<?php
/*
 * Copyright (c) 2016, Tribal Limited
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



//This Plugin displays an image; it is intended to be used at the top of a page to display a Masthead
class zenario_banner extends module_base_class {
	
	protected $mergeFields = array();
	protected $subSections = array();
	protected $empty = false;
	
	protected $editing = false;
	protected $editorId = '';
	protected $request = '';
	
	protected function editTitleInlineOnClick() {
		return 'if (zenarioA.checkForEdits() && zenarioA.draft(this.id)) { '. $this->refreshPluginSlotJS('&content__edit_container='. $this->containerId, false). ' } return false;';
	}
	
	protected function openEditor() {
		$this->callScript('zenario_banner', 'open', $this->containerId, $this->editorId);
	}
	
	//The init method is called by the CMS lets Plugin Developers run code before the Plugin and the page it is on are displayed.
	//In visitor mode, the Plugin is only displayed if this method returns true.
	function init() {
		if ($this->isVersionControlled) {
		
			if (cms_core::$isDraft && checkPriv('_PRIV_EDIT_DRAFT', cms_core::$cID, cms_core::$cType)) {
				if (post('_zenario_save_content_')) {
					setRow('plugin_settings',
						array('is_content' => 'version_controlled_content', 'format' => 'translatable_html', 'value' => post('content__content')),
						array('name' => 'text', 'instance_id' => $this->instanceId, 'nest' => $this->eggId));
					setRow('plugin_settings',
						array('is_content' => 'version_controlled_content', 'format' => 'translatable_html', 'value' => post('content__title')),
						array('name' => 'title', 'instance_id' => $this->instanceId, 'nest' => $this->eggId));
					exit;
				}
				
				$this->editorId = $this->containerId. '_tinymce_content_'. str_replace('.', '', microtime(true));
			
				//Open the editor immediately if it is in the URL
				if (request('content__edit_container') == $this->containerId) {
					$this->editing = true;
					$this->markSlotAsBeingEdited();
					$this->openEditor();
				}
			}
		}
		
		
		$imageId = false;
		$fancyboxLink = false;
		
		//Check to see if an item is set in the hyperlink_target setting 
		$cID = $cType = false;
		if ($this->setting('link_type') == '_CONTENT_ITEM'
		 && ($linkExists = $this->getCIDAndCTypeFromSetting(
			$cID, $cType,
			'hyperlink_target',
			!$this->isVersionControlled && $this->setting('use_translation'))
		)) {
			
			$this->mergeFields['Target_Blank'] = '';
			
			$downloadFile = ($cType == 'document' && !$this->setting('use_download_page'));
			
			if ($downloadFile) {
				$this->request = 'download=1';
			}
			
			$link = $this->linkToItem($cID, $cType, $fullPath = false, $this->request);
			
			//Use the Theme Section for a Masthead with a link and set the link
			$this->mergeFields['Link_Href'] =
			$this->mergeFields['Image_Link_Href'] =
				'href="'. htmlspecialchars($link). '"';
			
			if ($downloadFile) {
				$this->mergeFields['Target_Blank'] .= ' onclick="'. htmlspecialchars(trackFileDownload($link)). '"';
			}
			
			if ($this->setting('target_blank')) {
				$this->mergeFields['Target_Blank'] .= " target=\"_blank\"";
			}
			
			
			$this->allowCaching(
				$atAll = true, $ifUserLoggedIn = false, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
			$this->clearCacheBy(
				$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
			
			//Check the Privacy settings on this banner
			if (!checkPriv()) {
				switch ($this->setting('hide_private_item')) {
					case '_LOGGED_IN':
						if (!session('extranetUserID')) {
							return false;
						}
						break;
					
					case '_LOGGED_OUT':
						if (session('extranetUserID')) {
							return false;
						}
						break;
					
					case '_PRIVATE':
						if (!checkPerm($cID, $cType)) {
							return false;
						}
						break;
					
					default:
						$this->allowCaching(
							$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
				}
			}
			
		} else {
			
			$this->allowCaching(
				$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
			$this->clearCacheBy(
				$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
			
			// If the content item this banner was linking to has been removed, update setting to no-link
			if ($this->setting('link_type') == '_CONTENT_ITEM' && !$linkExists) {
				
				if (!getCIDAndCTypeFromTagId($cID, $cType, $this->setting('hyperlink_target'))
				 || !(($equivId = equivId($cID, $cType))
				   && checkRowExists('content_items', array('equiv_id' => $equivId, 'type' => $cType, 'status' => array('!1' => 'trashed', '!2' => 'deleted'))))) {
					
					updateRow('plugin_settings', array('value' => ''), array('instance_id' => $this->instanceId, 'name' => 'hyperlink_target'));
					updateRow('plugin_settings', array('value' => '_NO_LINK'), array('instance_id' => $this->instanceId, 'name' => 'link_type'));
				}
			
			} elseif ($this->setting('link_type') == '_EXTERNAL_URL' && $this->setting('url')) {
				$this->mergeFields['Link_Href'] =
				$this->mergeFields['Image_Link_Href'] =
					'href="'. htmlspecialchars($this->setting('url')). '"';
				
				if ($this->setting('target_blank')) {
					$this->mergeFields['Target_Blank'] = " target=\"_blank\"";
				}
			}
		}
		
		
		$pictureCID = $pictureCType = $width = $height = $url = $url2 = $widthFullSize = $heightFullSize = $urlFullSize = false;
		$this->mergeFields['Image_Style'] = '';
		
		//Attempt to find a masthead image to display
		//Check to see if an overwrite has been set, and use it if so
		if (($this->setting('image_source') == '_CUSTOM_IMAGE'
		  && ($imageId = $this->setting('image')))
		
		 || ($this->setting('image_source') == '_PICTURE'
		  && (getCIDAndCTypeFromTagId($pictureCID, $pictureCType, $this->setting("picture")))
		  && ($imageId = getRow("content_item_versions", "file_id", array("id" => $pictureCID, 'type' => $pictureCType, "version" => contentVersion($pictureCID, $pictureCType)))))
		 
		 || ($this->setting('image_source') == '_STICKY_IMAGE'
		  && $cID
		  && ($imageId = itemStickyImageId($cID, $cType)))) {
			
			$cols = array();
			if (!$this->setting('alt_tag')) {
				$cols[] = 'alt_tag';
			}
			
			if ($fancyboxLink && !$this->setting('floating_box_title')) {
				$cols[] = 'floating_box_title';
			}
			
			if (!empty($cols)) {
				$image = getRow('files', $cols, $imageId);
			}
			
			if ($this->setting('alt_tag')) {
				$alt_tag = htmlspecialchars($this->setting('alt_tag'));
			} else {
				$alt_tag = htmlspecialchars($image['alt_tag']);
			}
			$this->mergeFields['Image_Alt'] = $alt_tag;
			
			if ($fancyboxLink) {
				if ($this->setting('floating_box_title')) {
					$this->mergeFields['Link_Href'] .= ' data-box-title="'. htmlspecialchars($this->setting('floating_box_title')). '"';
					$this->mergeFields['Image_Link_Href'] .= ' data-box-title="'. htmlspecialchars($this->setting('floating_box_title')). '"';
				} else {
					$this->mergeFields['Link_Href'] .= ' data-box-title="'. htmlspecialchars($image['floating_box_title']). '"';
					$this->mergeFields['Image_Link_Href'] .= ' data-box-title="'. htmlspecialchars($image['floating_box_title']). '"';
				}
			}
			
			if (imageLink($width, $height, $url, $imageId, $this->setting('width'), $this->setting('height'), $this->setting('canvas'), $this->setting('offset'))) {
				if ($this->setting('image_source') == '_CUSTOM_IMAGE') {
					$this->clearCacheBy(
						$clearByContent, $clearByMenu, $clearByUser, $clearByFile = true, $clearByModuleData);
				
				} else {
					$this->clearCacheBy(
						$clearByContent = true, $clearByMenu, $clearByUser, $clearByFile = true, $clearByModuleData);
				}
				
				
				$this->subSections['Image'] = true;
				$this->mergeFields['Image_Src'] = htmlspecialchars($url);
				$this->mergeFields['Image_Height'] = $height;
				$this->mergeFields['Image_Width'] = $width;
				$this->mergeFields['Image_Style'] .= 'style="width: '. $width. 'px; height: '. $height. 'px;"';
				
				if ($this->setting('use_rollover')
				 && $this->setting('image_source') == '_CUSTOM_IMAGE'
				 && imageLink($width, $height, $url2, $this->setting('rollover_image'), $this->setting('width'), $this->setting('height'), $this->setting('canvas'), $this->setting('offset'))) {
					$this->mergeFields['Image_Rollover'] = array(
							'Image_Src' => htmlspecialchars($url2),
							'Image_Height' => $height,
							'Image_Width' => $width,
						);
					
					$this->mergeFields['Image_Style'] .= ' id="'. $this->containerId. '_img" ';
					
					
					
					$this->mergeFields['Rollover_Images'] =
						'<img id="'. $this->containerId. '_rollout" alt="'. $alt_tag. '" src="'. htmlspecialchars($url). '" style="width: 1px; height: 1px; visibility: hidden;"/>'.
						'<img id="'. $this->containerId. '_rollover" alt="'. $alt_tag. '" src="'. htmlspecialchars($url2). '" style="width: 1px; height: 1px; visibility: hidden;"/>';
					
					$this->mergeFields['Wrap'] =
						'onmouseout="get(\''. $this->containerId. '_img\').src = get(\''. $this->containerId. '_rollout\').src;" '.
						'onmouseover="get(\''. $this->containerId. '_img\').src = get(\''. $this->containerId. '_rollover\').src;" ';
				
				} elseif (($this->setting('link_type')=='_ENLARGE_IMAGE') && ($this->setting('image_source') != '_STICKY_IMAGE') && (!arrayKey($this->mergeFields,'Link_Href'))){
					if (imageLink($widthFullSize, $heightFullSize, $urlFullSize, $imageId, $this->setting('enlarge_width'), $this->setting('enlarge_height'), $this->setting('enlarge_canvas'))) {

						$this->mergeFields['Link_Href'] = 'rel="lightbox" href="' . htmlspecialchars($urlFullSize) . '" class="enlarge_in_fancy_box"';
						$this->mergeFields['Image_Link_Href'] = 'rel="colorbox" href="' . htmlspecialchars($urlFullSize) . '" class="enlarge_in_fancy_box"';
						
						//HTML 5 friendly version of the above code
							//Would need support from colorbox, and ", a[data-colorbox-group]" added to the jQuery pattern that sets colorboxes up
						//$this->mergeFields['Link_Href'] = 'data-colorbox-group="group1" href="' . htmlspecialchars($urlFullSize) . '" class="enlarge_in_fancy_box"';
						//$this->mergeFields['Image_Link_Href'] = 'data-colorbox-group="group2" href="' . htmlspecialchars($urlFullSize) . '" class="enlarge_in_fancy_box"';
						
						$this->subSections['Enlarge_Image'] = true;
						$fancyboxLink = true;
					}
				}
			}
		}
		
		$this->subSections['Text'] = (bool) $this->setting('text') || $this->editing;
		$this->subSections['Title'] = (bool) $this->setting('title') || $this->editing;
		$this->subSections['More_Link_Text'] = (bool) $this->setting('more_link_text');
		
		$this->mergeFields['Title_Tags'] = $this->setting('title_tags') ? $this->setting('title_tags') : 'h2';
		
		//Don't show empty Banners
		//Note: If there is some more link text set, but no Image/Text/Title, then I'll still consider the Banner to be empty
		if (empty($this->subSections['Image'])
		 && empty($this->subSections['Text'])
		 && empty($this->subSections['Title'])) {
			$this->empty = true;
			return false;
			
		} else {
			//Setup the title, text, and more link.
			//Title and the more link text will need to be html escaped, and may need translating if this is a library plugin.
			//The text is html but may need parsing for merge fields.
			if ($this->subSections['Title']) {
				$this->mergeFields['Title'] = htmlspecialchars($this->setting('title'));
				
				if (!$this->isVersionControlled) {
					if ($this->setting('translate_text')) {
						$this->mergeFields['Title'] = $this->phrase($this->mergeFields['Title']);
					}
				} else {
					if ($this->editing) {
						// To display the title in edit mode if the title is blank, it's set to a space
						if (!$this->mergeFields['Title']) {
							$this->mergeFields['Title'] = ' ';
						}
					}
				}
			}
			
			if ($this->subSections['Text']) {
				$this->mergeFields['Text'] = $this->setting('text');
				
				if (!$this->isVersionControlled) {
					if ($this->setting('translate_text')) {
						$this->replacePhraseCodesInString($this->mergeFields['Text']);
					}
				} else {
					if ($this->editing) {
						$this->mergeFields['Text'] =
							'<form>'.
								'<input type="hidden" id="'.$this->containerId.'_save_link" value="'.htmlspecialchars($this->showFloatingBoxLink()).'" />'.
								'<div id="'. $this->editorId .'">'.
									$this->mergeFields['Text'].
								'</div>'.
							'</form>';
					}
				}
			}
			
			if ($this->subSections['More_Link_Text']) {
				$this->mergeFields['More_Link_Text'] = htmlspecialchars($this->setting('more_link_text'));
				
				if (!$this->isVersionControlled) {
					if ($this->setting('translate_text')) {
						$this->mergeFields['More_Link_Text'] = $this->phrase($this->mergeFields['More_Link_Text']);
					}
				}
			}
			
			return true;
		}
	}
	
	
	//The showSlot method is called by the CMS, and displays the Plugin on the page
	function showSlot() {
		if (!empty($this->subSections['Image'])
		 || !empty($this->subSections['Text'])
		 || !empty($this->subSections['Title'])) {
			//Display the Plugin
			$this->framework('Outer', $this->mergeFields, $this->subSections);
		}
	}	

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	public function fillAdminSlotControls(&$controls) {
		
		//If this is a version controlled plugin and the current administrator is an author,
		//show the cut/copy/patse options
		if ($this->isVersionControlled && checkPriv('_PRIV_EDIT_DRAFT')) {
			
			//Check whether something compatible was previously copied
			$copied =
				!empty($_SESSION['admin_copied_contents']['class_name'])
			 && $_SESSION['admin_copied_contents']['class_name'] == 'zenario_banner';
			
			//If something has been entered, show the copy button
			if (!$this->empty) {
				$controls['actions']['copy_contents']['hidden'] = false;
				$controls['actions']['copy_contents']['onclick'] =
					str_replace('list,of,allowed,modules', 'zenario_banner',
						$controls['actions']['copy_contents']['onclick']);
			}
			
			//Check to see if this is the most recent version and the current administrator can make changes
			if (cms_core::$cVersion == cms_core::$adminVersion
			 && checkPriv('_PRIV_EDIT_DRAFT', cms_core::$cID, cms_core::$cType)) {
				
				if (!$this->empty) {
					$controls['actions']['cut_contents']['hidden'] = false;
					$controls['actions']['cut_contents']['onclick'] =
						str_replace('list,of,allowed,modules', 'zenario_banner',
							$controls['actions']['cut_contents']['onclick']);
				}
			
				//If there is no contents here and something was copied, show the paste option
				if ($this->empty && $copied) {
					$controls['actions']['paste_contents']['hidden'] = false;
				}
			
				//If there is contents here and something was copied, show the swap and overwrite options
				if (!$this->empty && $copied) {
					$controls['actions']['overwrite_contents']['hidden'] = false;
					$controls['actions']['swap_contents']['hidden'] = false;
				}
			}
			
			if (isset($controls['actions']['settings'])) {
				$controls['actions']['banner_edit_title'] = array(
					'ord' => 1.1,
					'label' => adminPhrase('Edit title & HTML (inline)'),
					'page_modes' => $controls['actions']['settings']['page_modes'],
					'onclick' => htmlspecialchars_decode($this->editTitleInlineOnClick()));
				$controls['actions']['settings']['label'] = adminPhrase('Edit contents (admin box)');
			}
			
		}
	}

}
