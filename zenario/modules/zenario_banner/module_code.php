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



//This Plugin displays an image; it is intended to be used at the top of a page to display a Masthead
class zenario_banner extends module_base_class {
	
	protected $mergeFields = array();
	protected $subSections = array();
	protected $empty = false;
	
	protected $editing = false;
	protected $editorId = '';
	protected $request = '';
	
	protected $styles = '';
	
	protected $normalImage = false;
	protected $retinaImage = false;
	protected $rolloverImage = false;
	protected $retinaRolloverImage = false;
	protected $respImage = false;
	protected $retinaRespImage = false;
	
	protected function editTitleInlineOnClick() {
		return 'if (zenarioA.checkForEdits() && zenarioA.draft(this.id)) { '. $this->refreshPluginSlotJS('&content__edit_container='. $this->containerId, false). ' } return false;';
	}
	
	protected function openEditor() {
		$this->callScript('zenario_banner', 'open', $this->containerId, $this->editorId);
	}
	

	protected function setupLink(&$mergeFields, &$cID, &$cType, $useTranslation = true, $link_type = 'link_type', $hyperlink_target = 'hyperlink_target', $target_blank = 'target_blank', $url = 'url') {
		
		$mergeFields['Target_Blank'] = '';
		$link = $downloadFile = $cID = $cType = false;
		
		//Check to see if an item is set in the hyperlink_target setting 
		if ($this->setting($link_type) == '_CONTENT_ITEM'
		 && ($linkExists = $this->getCIDAndCTypeFromSetting($cID, $cType, $hyperlink_target, $this->setting('use_translation')))) {
			
			$downloadFile = ($cType == 'document' && !$this->setting('use_download_page'));
			
			if ($downloadFile) {
				$this->request = 'download=1';
			}
			
			if (!$this->isVersionControlled && $this->setting('use_translation')) {
				$link = linkToItemStayInCurrentLanguage($cID, $cType, $fullPath = false, $this->request);
			} else {
				$link = linkToItem($cID, $cType, $fullPath = false, $this->request);
			}
			
			if ($this->setting('link_to_anchor') && ($anchor = $this->setting('hyperlink_anchor'))) {
			    $link .= '#' . $anchor;
			}
			
			//Use the Theme Section for a Masthead with a link and set the link
			$mergeFields['Link_Href'] =
			$mergeFields['Image_Link_Href'] =
				'href="'. htmlspecialchars($link). '"';
			
			if ($downloadFile) {
				$mergeFields['Target_Blank'] = ' onclick="'. htmlspecialchars(Ze\File::trackDownload($link)). '"';
			}
			
			
			$this->allowCaching(
				$atAll = true, $ifUserLoggedIn = false, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
			$this->clearCacheBy(
				$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
			
			//Check the Privacy settings on this banner
			if (!checkPriv()) {
				switch ($this->setting('hide_private_item')) {
					case '_LOGGED_IN':
						if (empty($_SESSION['extranetUserID'])) {
							return false;
						}
						break;
					
					case '_LOGGED_OUT':
						if (!empty($_SESSION['extranetUserID'])) {
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
			if ($this->setting($link_type) == '_CONTENT_ITEM' && !$linkExists) {
				
				if (!getCIDAndCTypeFromTagId($cID, $cType, $this->setting($hyperlink_target))
				 || !(($equivId = equivId($cID, $cType))
				   && checkRowExists('content_items', array('equiv_id' => $equivId, 'type' => $cType, 'status' => array('!1' => 'trashed', '!2' => 'deleted'))))) {
					
					$this->setSetting($link_type, '_NO_LINK', true);
					$this->setSetting($hyperlink_target, '', true);
					$this->setSetting($target_blank, '', true);
				}
			
			} elseif ($this->setting($link_type) == '_EXTERNAL_URL' && ($link = $this->setting($url))) {
				$mergeFields['Link_Href'] =
				$mergeFields['Image_Link_Href'] =
					'href="'. htmlspecialchars($link). '"';
			}
		}
		
		if ($link && ($openIn = $this->setting($target_blank))) {
			
			$mergeFields['Target_Blank'] .= ' target="_blank"';
			
			if (!$downloadFile && $openIn == 2) {
				$mergeFields['Target_Blank'] .= ' onclick="if (window.$) { $.colorbox({href: \''. jsEscape($link). '\', iframe: true, width: \'95%\', height: \'95%\'}); return false; }"';
			}
		}
		
		return true;
	}
	
	//The init method is called by the CMS lets Plugin Developers run code before the Plugin and the page it is on are displayed.
	//In visitor mode, the Plugin is only displayed if this method returns true.
	function init() {
		if ($this->isVersionControlled) {
			if ($_POST['_zenario_save_content_'] ?? false) {
				$this->setSetting('text', ($_POST['content__content'] ?? false), true, true, 'translatable_html');
				$this->setSetting('title', ($_POST['content__title'] ?? false), true, true, 'translatable_text');
				exit;
			}
		
			if (cms_core::$isDraft && checkPriv('_PRIV_EDIT_DRAFT', cms_core::$cID, cms_core::$cType)) {
				
				$this->editorId = $this->containerId. '_tinymce_content_'. str_replace('.', '', microtime(true));
			
				//Open the editor immediately if it is in the URL
				if (($_REQUEST['content__edit_container'] ?? false) == $this->containerId) {
					$this->editing = true;
					$this->markSlotAsBeingEdited();
					$this->openEditor();
				}
			}
		}
		
		
		$imageId = false;
		$fancyboxLink = false;
		$cID = $cType = false;
		if (!$this->setupLink($this->mergeFields, $cID, $cType, $this->setting('use_translation'))) {
			return false;
		}
		
		
		$pictureCID = $pictureCType = $width = $height = $respWidth = $respHeight = $url = $url2 = $widthFullSize = $heightFullSize = $urlFullSize = false;
		
		//Attempt to find a masthead image to display
		//Check to see if an overwrite has been set, and use it if so
		if (($this->setting('image_source') == '_CUSTOM_IMAGE'
		  && ($imageId = $this->setting('image')))
		
		 || ($this->setting('image_source') == '_PICTURE'
		  && (getCIDAndCTypeFromTagId($pictureCID, $pictureCType, $this->setting("picture")))
		  && ($imageId = getRow("versions", "file_id", array("id" => $pictureCID, 'type' => $pictureCType, "version" => contentVersion($pictureCID, $pictureCType)))))
		 
		 || ($this->setting('image_source') == '_STICKY_IMAGE'
		  && $cID
		  && ($imageId = Ze\File::itemStickyImageId($cID, $cType)))) {
			
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
			
			
			//Get the resize options for the image from the plugin settings
			$banner_canvas = $this->setting('canvas');
			$banner_width = $this->setting('width');
			$banner_height = $this->setting('height');
			
			//If this banner is in a nest, check if there are default settings set by the nest
			if (isset($this->parentNest)
			 && $this->parentNest->banner_canvas) {
				
				$inheritDimensions = true;
				
				//fixed_width/fixed_height/fixed_width_and_height settings can be merged together
				if ($banner_canvas == 'fixed_width_and_height'
				 || $this->parentNest->banner_canvas == 'fixed_width_and_height'
				 || ($this->parentNest->banner_canvas == 'fixed_width' && $banner_canvas == 'fixed_height')
				 || ($this->parentNest->banner_canvas == 'fixed_height' && $banner_canvas == 'fixed_width')) {
					$banner_canvas = 'fixed_width_and_height';
				
				//fixed_width/fixed_height/fixed_width_and_height settings on the nest should not be combined with
				//resize_and_crop settings on the banner, and vice versa. So do an XOR and only update the settings if
				//they're not both different
				} else
				if (!$banner_canvas
				 || $banner_canvas == 'unlimited'
				 || !(($this->parentNest->banner_canvas == 'resize_and_crop') XOR ($banner_canvas == 'resize_and_crop'))) {
					$banner_canvas = $this->parentNest->banner_canvas;
				
				} else {
					$inheritDimensions = false;
				}
				
				if ($inheritDimensions && $this->parentNest->banner_width) {
					if (!$banner_width
					 || !in($banner_canvas, 'fixed_width', 'fixed_width_and_height', 'resize_and_crop')) {
						$banner_width = $this->parentNest->banner_width;
					}
				}
				
				if ($inheritDimensions && $this->parentNest->banner_height) {
					if (!$banner_height
					 || !in($banner_canvas, 'fixed_height', 'fixed_width_and_height', 'resize_and_crop')) {
						$banner_height = $this->parentNest->banner_height;
					}
				}
			}
			
			$banner__enlarge_image = true;
			$banner__enlarge_canvas = $this->setting('enlarge_canvas');
			$banner__enlarge_width = (int) $this->setting('enlarge_width');
			$banner__enlarge_height = (int) $this->setting('enlarge_height');
			
			//Also have some nest-wide options to enable colorbox popups, and to set restrictions there too
			if (isset($this->parentNest)
			 && $this->parentNest->banner__enlarge_image
			 && !in($this->setting('link_type'), '_CONTENT_ITEM', '_EXTERNAL_URL')) {
				
				//Set the link type to "_ENLARGE_IMAGE" if it's not already.
				$this->setSetting('link_type', '_ENLARGE_IMAGE', false);
				
				$inheritDimensions = true;
				
				//fixed_width/fixed_height/fixed_width_and_height settings can be merged together
				if ($banner__enlarge_canvas == 'fixed_width_and_height'
				 || $this->parentNest->banner__enlarge_canvas == 'fixed_width_and_height'
				 || ($this->parentNest->banner__enlarge_canvas == 'fixed_width' && $banner__enlarge_canvas == 'fixed_height')
				 || ($this->parentNest->banner__enlarge_canvas == 'fixed_height' && $banner__enlarge_canvas == 'fixed_width')) {
					$banner__enlarge_canvas = 'fixed_width_and_height';
				
				//fixed_width/fixed_height/fixed_width_and_height settings on the nest should not be combined with
				//resize_and_crop settings on the banner, and vice versa. So do an XOR and only update the settings if
				//they're not both different
				} else
				if (!$banner__enlarge_canvas
				 || $banner__enlarge_canvas == 'unlimited'
				 || !(($this->parentNest->banner__enlarge_canvas == 'resize_and_crop') XOR ($banner__enlarge_canvas == 'resize_and_crop'))) {
					$banner__enlarge_canvas = $this->parentNest->banner__enlarge_canvas;
				
				} else {
					$inheritDimensions = false;
				}
				
				if ($inheritDimensions && $this->parentNest->banner__enlarge_width) {
					if (!$banner__enlarge_width
					 || !in($banner__enlarge_canvas, 'fixed_width', 'fixed_width_and_height', 'resize_and_crop')) {
						$banner__enlarge_width = $this->parentNest->banner__enlarge_width;
					}
				}
				
				if ($inheritDimensions && $this->parentNest->banner__enlarge_height) {
					if (!$banner__enlarge_height
					 || !in($banner__enlarge_canvas, 'fixed_height', 'fixed_width_and_height', 'resize_and_crop')) {
						$banner__enlarge_height = $this->parentNest->banner__enlarge_height;
					}
				}
			}
			
			
			$banner_offset = $this->setting('offset');
			$banner_retina = $banner_canvas != 'unlimited' || $this->setting('retina');
			
			
			
			//Try to get a link to the image
			if (Ze\File::imageLink($width, $height, $url, $imageId, $banner_width, $banner_height, $banner_canvas, $banner_offset, $banner_retina)) {
				
				$this->normalImage = $url;

				//If this was a retina image, get a normal version of the image as well for standard displays
				if ($banner_retina) {
					$sWidth = $sHeight = $sURL = false;
					if (Ze\File::imageLink($sWidth, $sHeight, $sURL, $imageId, $width, $height, $banner_canvas == 'resize_and_crop'? 'resize_and_crop' : 'stretch', $banner_offset, false)) {
						
						if ($url != $sURL) {
							$this->mergeFields['Image_Srcset'] = $url. ' 2x';
							
							$this->retinaImage = $url;
							$this->normalImage = $sURL;
							
							$url = $sURL;
						}
					}
				}
				
				
				if ($this->setting('image_source') == '_CUSTOM_IMAGE') {
					$this->clearCacheBy(
						false, false, false, $clearByFile = true, false);
				
				} else {
					$this->clearCacheBy(
						$clearByContent = true, false, false, $clearByFile = true, false);
				}
				
				
				$this->subSections['Image'] = true;
				$this->mergeFields['Image_URL'] = $url;
				$this->mergeFields['Image_Height'] = $height;
				$this->mergeFields['Image_Width'] = $width;
				$this->mergeFields['Image_Style'] = 'id="'. $this->containerId. '_img"';
				//$this->mergeFields['Image_Style'] .= 'style="width: '. $width. 'px; height: '. $height. 'px;"';
				
				$this->styles = '#'. $this->containerId. '_img { width: '. $width. 'px; height: '. $height. 'px; }';
				
				//Deprecated merge field for old frameworks
				$this->mergeFields['Image_Src'] = htmlspecialchars($url);
				
				
				
				//Set a responsive version of the image
				if (cms_core::$minWidth) {
					switch ($this->setting('mobile_behavior')) {
						case 'change_image':
							$mobile_image = $this->setting('mobile_image');
							$mobile_canvas = $this->setting('mobile_canvas');
							$mobile_width = $this->setting('mobile_width');
							$mobile_height = $this->setting('mobile_height');
							$mobile_offset = $this->setting('mobile_offset');
							$mobile_retina = $mobile_canvas != 'unlimited' || $this->setting('mobile_retina');
					
							$respURL = false;
							if (Ze\File::imageLink($respWidth, $respHeight, $respURL, $mobile_image, $mobile_width, $mobile_height, $mobile_canvas, $mobile_offset, $mobile_retina)) {
				
								$this->respImage = $respURL;
								$this->mergeFields['Mobile_Srcset'] = $respURL;
						
								//If this was a retina image, get a normal version of the image as well for standard displays
								if ($mobile_retina) {
									$sWidth = $sHeight = $sURL = false;
									if (Ze\File::imageLink($sWidth, $sHeight, $sURL, $mobile_image, $respWidth, $respHeight, $mobile_canvas, $mobile_offset, false)) {
										if ($respURL != $sURL) {
											
											$this->respImage = $sURL;
											$this->retinaRespImage = $respURL;
											
											$this->mergeFields['Mobile_Srcset'] = $sURL. ' 1x, '. $respURL. ' 2x';
										}
									}
								}
								
								$this->mergeFields['Mobile_Media'] = '(max-width: '. (cms_core::$minWidth - 1). 'px)';
						
								if ($respWidth != $width
								 || $respHeight != $height) {
									$this->styles .= "\n". 'body.mobile #'. $this->containerId. '_img { width: '. $respWidth. 'px; height: '. $respHeight. 'px; }';
								}
							}
							
							break;
						
						//Hide the image on mobiles, and add some hacks to try and make sure that they never even try to download it
						case 'hide_image':
							$this->mergeFields['Mobile_Media'] = '(max-width: '. (cms_core::$minWidth - 1). 'px)';
							$trans = absURLIfNeeded(). 'zenario/admin/images/trans.png';
							$this->mergeFields['Mobile_Srcset'] = $trans. ' 1x, '. $trans. ' 2x';
							$this->styles .= "\n". 'body.mobile #'. $this->containerId. '_img { display: none; }';
					}
				}
				
				
				
				//Set a rollover version of the image
				if ($this->setting('use_rollover')
				 && $this->setting('image_source') == '_CUSTOM_IMAGE') {
					
					$rollSrcset = '';
					$normalSrcset = '';
					
					$rollover_image = $this->setting('rollover_image');
					
					$rWidth = $rHeight = $rollURL = false;
					if (Ze\File::imageLink($rWidth, $rHeight, $rollURL, $rollover_image, $banner_width, $banner_height, $banner_canvas, $banner_offset, $banner_retina)) {
						
						$this->rolloverImage = $rollURL;
						
						//If this was a retina image, get a normal version of the image as well for standard displays
						if ($banner_retina) {
							$sWidth = $sHeight = $sURL = false;
							if (Ze\File::imageLink($sWidth, $sHeight, $sURL, $rollover_image, $rWidth, $rHeight, $banner_canvas == 'resize_and_crop'? 'resize_and_crop' : 'stretch', $banner_offset, false)) {
								if ($rollURL != $sURL) {
									$rollSrcset = $rollURL. ' 2x';
									$rollSrcset = 'srcset="'. htmlspecialchars($rollSrcset). '"';
									
									$this->rolloverImage = $sURL;
									$this->retinaRolloverImage = $rollURL;
									
									$rollURL = $sURL;
								}
							}
						}
					}
					
					if (!empty($this->mergeFields['Image_Srcset'])) {
						$normalSrcset = 'srcset="'. htmlspecialchars($this->mergeFields['Image_Srcset']). '"';
					}
					
					$this->mergeFields['Rollover_Images'] =
						'<img id="'. $this->containerId. '_rollout" alt="'. $alt_tag. '" src="'. htmlspecialchars($url). '" '. $normalSrcset. ' style="width: 1px; height: 1px; visibility: hidden;"/>'.
						'<img id="'. $this->containerId. '_rollover" alt="'. $alt_tag. '" src="'. htmlspecialchars($rollURL). '" '. $rollSrcset. ' style="width: 1px; height: 1px; visibility: hidden;"/>';
					
					$this->mergeFields['Wrap'] =
						'onmouseout="var x = \''. $this->containerId. '\', y = document.getElementById(x + \'_img\'), z = document.getElementById(x + \'_rollout\'); y.src = z.src; y.srcset = z.srcset;" '.
						'onmouseover="var x = \''. $this->containerId. '\', y = document.getElementById(x + \'_img\'), z = document.getElementById(x + \'_rollover\'); y.src = z.src; y.srcset = z.srcset;" ';
				
				
				
				
				} elseif (($this->setting('link_type')=='_ENLARGE_IMAGE') && ($this->setting('image_source') != '_STICKY_IMAGE') && (empty($this->mergeFields['Link_Href']))){
					if (Ze\File::imageLink($widthFullSize, $heightFullSize, $urlFullSize, $imageId, $banner__enlarge_width, $banner__enlarge_height, $banner__enlarge_canvas)) {
						if ($this->setting('disable_rel')) {
							$this->mergeFields['Link_Href'] =
							$this->mergeFields['Image_Link_Href'] = 'rel="colorbox_no_arrows" href="' . htmlspecialchars($urlFullSize) . '" class="enlarge_in_fancy_box"';
						} else {
							$this->mergeFields['Link_Href'] = 'rel="lightbox" href="' . htmlspecialchars($urlFullSize) . '" class="enlarge_in_fancy_box"';
							$this->mergeFields['Image_Link_Href'] = 'rel="colorbox" href="' . htmlspecialchars($urlFullSize) . '" class="enlarge_in_fancy_box"';
						}
						
						
						
						
						
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
		
		$this->mergeFields['Title_Tags'] =
		$this->mergeFields['Title_Tags_Close'] = $this->setting('title_tags') ? $this->setting('title_tags') : 'h2';
		
		if ($this->editorId !== '') {
			$this->mergeFields['Title_Tags'] .= ' id="banner_title__'. $this->containerId. '"';
		}
		
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
			
			//Enable an option to use a background images instead of <picture><img/></picture>
			if ($this->setting('background_image') && $this->normalImage) {
				$this->mergeFields['Wrap'] = '';
				$this->mergeFields['Background_Image'] = true;
				$this->mergeFields['Image_css_id'] = $this->containerId. '_img';
				
				$this->styles .= "\n". '#'. $this->containerId. '_img { display: block; background-repeat: no-repeat; background-image: url(\''. htmlspecialchars($this->normalImage).  '\'); }';
				
				if ($this->retinaImage) {
					$this->styles .= "\n". 'body.retina #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->retinaImage).  '\'); background-size: '. $width. 'px '. $height. 'px; }';
				}
				
				if ($this->rolloverImage) {
					$this->styles .= "\n". 'div.banner_wrap:hover #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->rolloverImage).  '\'); }';
					
					if ($this->retinaRolloverImage) {
						$this->styles .= "\n". 'body.retina div.banner_wrap:hover #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->retinaRolloverImage).  '\'); background-size: '. $width. 'px '. $height. 'px; }';
					}
				}
				
				if ($this->respImage) {
					$this->styles .= "\n". 'body.mobile #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->respImage).  '\'); }';
					
					if ($this->retinaRespImage) {
						$this->styles .= "\n". 'body.mobile.retina #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->retinaRespImage).  '\'); background-size: '. $respWidth. 'px '. $respHeight. 'px; }';
					}
				}
			}
			
			//If we're reloading via AJAX, we need to call a JavaScript function to add the style to the head.
			//Otherwise we can use addToPageHead() below.
			if ($this->styles !== '' && ($_REQUEST['method_call'] ?? false) == 'refreshPlugin') {
				$this->callScriptBeforeAJAXReload('zenario_banner', 'addToPageHead', $this->containerId, $this->styles);
			}
			
			return true;
		}
	}
	
	public function addToPageHead() {
		if ($this->styles !== '') {
			echo "\n", '<style type="text/css" id="', $this->containerId, '-styles">', "\n", $this->styles, "\n", '</style>';
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
