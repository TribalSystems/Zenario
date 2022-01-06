<?php
/*
 * Copyright (c) 2022, Tribal Limited
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
class zenario_banner extends ze\moduleBaseClass {
	
	protected $mergeFields = [];
	protected $subSections = [];
	protected $empty = false;
	
	protected $editing = false;
	protected $editorId = '';
	protected $request = '';
	
	protected $styles = [];
	
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
	

	protected function setupLink(&$mergeFields, &$cID, &$cType, $useTranslation = true, $link_type = 'link_type', $hyperlink_target = 'hyperlink_target', $target_blank = 'target_blank', $url = false, $imageId='') {
		
		$mergeFields['Target_Blank'] = '';
		$link = $downloadFile = $cID = $cType = false;
		$linkExists = false;
		$linkTo = $this->setting($link_type);
		
		//Check to see if an item is set in the hyperlink_target setting 
		if ($linkTo == '_CONTENT_ITEM'
		 && ($linkExists = $this->getCIDAndCTypeFromSetting($cID, $cType, $hyperlink_target, $useTranslation))) {
			
			$downloadFile = ($cType == 'document' && !$this->setting('use_download_page'));
			
			if ($downloadFile) {
				$this->request = 'download=1';
			}
			
			if (!$this->isVersionControlled && $useTranslation) {
				$link = ze\link::toItemInVisitorsLanguage($cID, $cType, $fullPath = false, $this->request);
			} else {
				$link = ze\link::toItem($cID, $cType, $fullPath = false, $this->request);
			}

			if ($this->setting('link_to_anchor') && ($anchor = $this->setting('hyperlink_anchor'))) {
			    $link .= '#' . $anchor;
			}
			if ($this->setting('link_to_anchor_'. $imageId) && ($anchor = $this->setting('hyperlink_anchor_'. $imageId))) {
			    $link .= '#' . $anchor;
			}
			//Use the Theme Section for a Masthead with a link and set the link
			$mergeFields['Link_Href'] =
			$mergeFields['Image_Link_Href'] =
				'href="'. htmlspecialchars($link). '"';
			
			if ($downloadFile) {
				$mergeFields['Target_Blank'] = ' onclick="'. htmlspecialchars(ze\file::trackDownload($link)). '"';
			}
			
			
			$this->allowCaching(
				$atAll = true, $ifUserLoggedIn = false, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
			$this->clearCacheBy(
				$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
			
			//Check the Privacy settings on this banner
			if (!ze\priv::check()) {
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
						if (!ze\content::checkPerm($cID, $cType)) {
							return false;
						}
						break;
					
					default:
						$this->allowCaching(
							$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
				}
			}
		
		} else
		if ($linkTo == '_DOCUMENT'
		 && ($documentId = $this->setting('document_id'))
		 && ($linkExists = (bool) $link = ze\file::getDocumentFrontEndLink($documentId))) {
			
			$document = ze\row::get('documents', ['filename', 'privacy'], ['id' => $documentId]);
			$contentItemPrivacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => ze::$equivId]);

			//Always show public documents,
			//Don't show private documents on public content items.
			if ($document['privacy'] == 'public' || ($document['privacy'] == 'private' && $contentItemPrivacy != 'public' && $contentItemPrivacy != 'logged_out')) {
				//Use the Theme Section for a Masthead with a link and set the link
				$mergeFields['Link_Href'] =
				$mergeFields['Image_Link_Href'] =
					'href="'. htmlspecialchars($link). '"';
		
				$mergeFields['Target_Blank'] = ' onclick="'. htmlspecialchars(ze\file::trackDownload($link)). '"';
		
				$downloadFile = true;
				
				//Only allow caching for public documents.
				if ($document['privacy'] == 'public') {
					$this->allowCaching(
						$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
					$this->clearCacheBy(
						$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = false);
				}
			} else {
				if (ze\admin::id()) {
					$mergeFields['privacy_warning'] = true;
					$mergeFields['filename'] = $document['filename'];
					$mergeFields['privacy'] = $document['privacy'];
				}
			}
			
		} else {
			$this->allowCaching(
				$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
			$this->clearCacheBy(
				$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
			
			// If the content item this banner was linking to has been removed, update setting to no-link
			if ($linkTo == '_CONTENT_ITEM' && !$linkExists) {
				
				if (!ze\content::getCIDAndCTypeFromTagId($cID, $cType, $this->setting($hyperlink_target))
				 || !(($equivId = ze\content::equivId($cID, $cType))
				   && ze\row::exists('content_items', ['equiv_id' => $equivId, 'type' => $cType, 'status' => ['!1' => 'trashed', '!2' => 'deleted']]))) {
					
					//Don't update the settings if this was just a preview!
					if (empty($_POST['overrideSettings'])) {
						$this->setSetting($link_type, '_NO_LINK', true);
						$this->setSetting($hyperlink_target, '', true);
						$this->setSetting($target_blank, '', true);
					}
				}
			
			//If a document that this banner was linking to has been removed, update the settingas not no-link as well.
			} elseif ($linkTo == '_DOCUMENT' && !$linkExists) {
				
				if (ze::isAdmin()) {
					if ($document = ze\row::get('documents', ['filename', 'privacy'], ['id' => $documentId])) {
						
						if ($document['privacy'] == 'offline') {
							$mergeFields['privacy_warning'] = true;
							$mergeFields['filename'] = $document['filename'];
							$mergeFields['privacy'] = $document['privacy'];
						}
					
					} else {
						//Don't update the settings if this was just a preview!
						if (empty($_POST['overrideSettings'])) {
							$this->setSetting($link_type, '_NO_LINK', true);
							$this->setSetting('document_id', '', true);
						}
					}
				}
			
			} elseif ($linkTo == '_EXTERNAL_URL') {
				if (!$url) {
					$url = 'url';
				}
				
				if ($link = $this->setting($url)) {
					$mergeFields['Link_Href'] =
					$mergeFields['Image_Link_Href'] =
						'href="'. htmlspecialchars($link). '"';
				}
			
			}  elseif ($linkTo == '_EMAIL') {
				$url = 'email_address';
				if ($link = $this->setting($url)) {
					$mergeFields['Link_Href'] =
					$mergeFields['Image_Link_Href'] =
						'href="'. htmlspecialchars($link). '"';
				}
			}
		}
		
		if ($link && ($openIn = $this->setting($target_blank))) {
			
			$mergeFields['Target_Blank'] .= ' target="_blank"';
			
			if (!$downloadFile && $openIn == 2) {
				$mergeFields['Target_Blank'] .= ' onclick="if (window.$) { $.colorbox({href: \''. ze\escape::js($link). '\', iframe: true, width: \'95%\', height: \'95%\'}); return false; }"';
			}
		}
		
		return true;
	}
	
	//The init method is called by the CMS lets Plugin Developers run code before the Plugin and the page it is on are displayed.
	//In visitor mode, the Plugin is only displayed if this method returns true.
	function init() {
		if ($this->isVersionControlled) {
		
			if (ze::$isDraft && ze\priv::check('_PRIV_EDIT_DRAFT', ze::$cID, ze::$cType)) {
				if (!empty($_POST['_zenario_save_content_'])) {
					$this->setSetting('text', ze\ring::decodeIdForOrganizer($_POST['content__content'] ?? ''), true, true, 'translatable_html');
					$this->setSetting('title', ze\ring::decodeIdForOrganizer($_POST['content__title'] ?? ''), true, true, 'translatable_text');
					exit;
				}
				//N.b. encodeItemIdForOrganizer() was called on the HTML, to avoid sending RAW HTML over post and potentially
				//triggering Cloudflare to blocks it, so we need to call decodeIdForOrganizer() to decode it.
				
				$this->editorId = $this->containerId. '_tinymce_content_'. str_replace('.', '', microtime(true));
			
				//Open the editor immediately if it is in the URL
				if (($_REQUEST['content__edit_container'] ?? false) == $this->containerId) {
					$this->editing = true;
					$this->markSlotAsBeingEdited();
					$this->openEditor();
				}
			}
		}
		
		
		$addWidthAndHeight = false;
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
		
		 || ($this->setting('image_source') == '_PICTURE' //TODO looks like a variable that was removed
		  && (ze\content::getCIDAndCTypeFromTagId($pictureCID, $pictureCType, $this->setting("picture")))
		  && ($imageId = ze\row::get("versions", "file_id", ["id" => $pictureCID, 'type' => $pictureCType, "version" => ze\content::version($pictureCID, $pictureCType)])))
		 
		 || ($this->setting('image_source') == '_STICKY_IMAGE'
		  && $cID
		  && ($imageId = ze\file::itemStickyImageId($cID, $cType)))) {
			
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
				//crop_and_zoom settings on the banner, and vice versa. So do an XOR and only update the settings if
				//they're not both different
				} else
				if (!$banner_canvas
				 || $banner_canvas == 'unlimited'
				 || !(($this->parentNest->banner_canvas == 'crop_and_zoom') XOR ($banner_canvas == 'crop_and_zoom'))) {
					$banner_canvas = $this->parentNest->banner_canvas;
				
				} else {
					$inheritDimensions = false;
				}
				
				if ($inheritDimensions && $this->parentNest->banner_width) {
					if (!$banner_width
					 || !ze::in($banner_canvas, 'fixed_width', 'fixed_width_and_height', 'crop_and_zoom')) {
						$banner_width = $this->parentNest->banner_width;
					}
				}
				
				if ($inheritDimensions && $this->parentNest->banner_height) {
					if (!$banner_height
					 || !ze::in($banner_canvas, 'fixed_height', 'fixed_width_and_height', 'crop_and_zoom')) {
						$banner_height = $this->parentNest->banner_height;
					}
				}
			}
			
			$banner__enlarge_image = true;
			$banner__enlarge_floating_box_title_mode = $this->setting('floating_box_title_mode');
			$banner__enlarge_canvas = $this->setting('enlarge_canvas');
			$banner__enlarge_width = (int) $this->setting('enlarge_width');
			$banner__enlarge_height = (int) $this->setting('enlarge_height');
			
			//Also have some nest-wide options to enable colorbox popups, and to set restrictions there too
			if (isset($this->parentNest)
			 && $this->parentNest->banner__enlarge_image
			 && !ze::in($this->setting('link_type'), '_CONTENT_ITEM', '_EXTERNAL_URL', '_EMAIL')) {
				
				//Set the link type to "_ENLARGE_IMAGE" if it's not already.
				$this->setSetting('link_type', '_ENLARGE_IMAGE', false);
				if(!$banner__enlarge_floating_box_title_mode) {
					$this->setSetting('floating_box_title_mode', 'use_default', false);
				}
				
				$inheritDimensions = true;
				
				//fixed_width/fixed_height/fixed_width_and_height settings can be merged together
				if ($banner__enlarge_canvas == 'fixed_width_and_height'
				 || $this->parentNest->banner__enlarge_canvas == 'fixed_width_and_height'
				 || ($this->parentNest->banner__enlarge_canvas == 'fixed_width' && $banner__enlarge_canvas == 'fixed_height')
				 || ($this->parentNest->banner__enlarge_canvas == 'fixed_height' && $banner__enlarge_canvas == 'fixed_width')) {
					$banner__enlarge_canvas = 'fixed_width_and_height';
				
				//fixed_width/fixed_height/fixed_width_and_height settings on the nest should not be combined with
				//crop_and_zoom settings on the banner, and vice versa. So do an XOR and only update the settings if
				//they're not both different
				} else
				if (!$banner__enlarge_canvas
				 || $banner__enlarge_canvas == 'unlimited'
				 || !(($this->parentNest->banner__enlarge_canvas == 'crop_and_zoom') XOR ($banner__enlarge_canvas == 'crop_and_zoom'))) {
					$banner__enlarge_canvas = $this->parentNest->banner__enlarge_canvas;
				
				} else {
					$inheritDimensions = false;
				}
				
				if ($inheritDimensions && $this->parentNest->banner__enlarge_width) {
					if (!$banner__enlarge_width
					 || !ze::in($banner__enlarge_canvas, 'fixed_width', 'fixed_width_and_height', 'crop_and_zoom')) {
						$banner__enlarge_width = $this->parentNest->banner__enlarge_width;
					}
				}
				
				if ($inheritDimensions && $this->parentNest->banner__enlarge_height) {
					if (!$banner__enlarge_height
					 || !ze::in($banner__enlarge_canvas, 'fixed_height', 'fixed_width_and_height', 'crop_and_zoom')) {
						$banner__enlarge_height = $this->parentNest->banner__enlarge_height;
					}
				}
			}
			
			$cols = [];
			if (!$this->setting('alt_tag')) {
				$cols[] = 'alt_tag';
			}
			
			if ($this->setting('link_type')=='_ENLARGE_IMAGE' && $this->setting('floating_box_title_mode') != 'overwrite') {
				$cols[] = 'floating_box_title';
			}
			
			if (!empty($cols)) {
				$image = ze\row::get('files', $cols, $imageId);
			}
			
			$alt_tag = '';
			if ($this->setting('alt_tag')) {
				$alt_tag = htmlspecialchars($this->setting('alt_tag'));
			} else {
				if (!empty($image)) {
					$alt_tag = htmlspecialchars($image['alt_tag']);
				}
			}
			$this->mergeFields['Image_Alt'] = $alt_tag;
			
			
			$banner_offset = $this->setting('offset');
			$banner_retina = $banner_canvas != 'unlimited' || $this->setting('retina');
			
			
			
			//Try to get a link to the image
			if (ze\file::imageLink($width, $height, $url, $imageId, $banner_width, $banner_height, $banner_canvas, $banner_offset, $banner_retina)) {
				
				$this->normalImage = $url;
				
				$mimeType = ze\file::mimeType($url);

				if (ze::in($mimeType, 'image/png', 'image/jpeg')) {
					$fileParts = pathinfo($url);
					$this->normalImageWebP = $fileParts['dirname'] . '/' . $fileParts['filename'] . '.webp';
					$this->mergeFields['WebP_Image_URL'] = $this->normalImageWebP;
				}

				//If this was a retina image, get a normal version of the image as well for standard displays
				if ($banner_retina) {
					$sWidth = $sHeight = $sURL = false;
					if (ze\file::imageLink($sWidth, $sHeight, $sURL, $imageId, $width, $height, $banner_canvas == 'crop_and_zoom'? 'crop_and_zoom' : 'adjust', $banner_offset, false)) {
						
						if ($url != $sURL) {
							$this->mergeFields['Image_Retina_Srcset'] = $url. ' 2x';

							$mimeType = ze\file::mimeType($url);
							$this->mergeFields['Mime_Type'] = $mimeType;

							$this->retinaImage = $url;
							$this->normalImage = $sURL;
							
							$url = $sURL;

							if (ze::in($mimeType, 'image/png', 'image/jpeg')) {
								$fileParts = pathinfo($sURL);
								$this->normalImageWebP = $fileParts['dirname'] . '/' . $fileParts['filename'] . '.webp';
								$this->mergeFields['WebP_Image_URL'] = $this->normalImageWebP. ' 1x, '. $this->mergeFields['WebP_Image_URL']. ' 2x';
							}
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
					
				if (ze::isAdmin()) {
					$this->mergeFields['Image_Class'] = ' zenario_image_properties zenario_image_id__'. $imageId. '__';
				}
				
				$addWidthAndHeight = $addWidthAndHeightInline = true;
				
				//Deprecated merge field for old frameworks
				$this->mergeFields['Image_Src'] = htmlspecialchars($url);
				
				
				
				//Set a responsive version of the image
				if (ze::$minWidth) {
					switch ($this->setting('mobile_behaviour')) {
						case 'mobile_change_image':
						case 'mobile_same_image_different_size':
							
							switch ($this->setting('mobile_behaviour')) {
								case 'mobile_change_image':
									$mobile_image = $this->setting('mobile_image');
									break;
								case 'mobile_same_image_different_size':
									$mobile_image = $this->setting('image');
									break;
							}
							
							$mobile_canvas = $this->setting('mobile_canvas');
							$mobile_width = $this->setting('mobile_width');
							$mobile_height = $this->setting('mobile_height');
							$mobile_offset = $this->setting('mobile_offset');
							$mobile_retina = $mobile_canvas != 'unlimited' || $this->setting('mobile_retina');
					
							$respURL = false;
							if (ze\file::imageLink($respWidth, $respHeight, $respURL, $mobile_image, $mobile_width, $mobile_height, $mobile_canvas, $mobile_offset, $mobile_retina)) {
				
								$this->respImage = $respURL;
								$this->mergeFields['Mobile_Srcset'] = $respURL;

								$mimeType = ze\file::mimeType($respURL);
								$this->mergeFields['Mobile_Mime_Type'] = $mimeType;

								if (ze::in($mimeType, 'image/png', 'image/jpeg')) {
									$fileParts = pathinfo($respURL);
									$this->respImageWebP = $fileParts['dirname'] . '/' . $fileParts['filename'] . '.webp';
									$this->mergeFields['Mobile_Srcset_WebP'] = $this->respImageWebP;
								}
						
								//If this was a retina image, get a normal version of the image as well for standard displays
								if ($mobile_retina) {
									$sWidth = $sHeight = $sURL = false;
									if (ze\file::imageLink($sWidth, $sHeight, $sURL, $mobile_image, $respWidth, $respHeight, $mobile_canvas, $mobile_offset, false)) {
										if ($respURL != $sURL) {
											
											$this->respImage = $sURL;
											$this->retinaRespImage = $respURL;

											$fileParts = pathinfo($sURL);
											$this->respImageWebP = $fileParts['dirname'] . '/' . $fileParts['filename'] . '.webp';

											$fileParts = pathinfo($respURL);
											$this->retinaRespImageWebP = $fileParts['dirname'] . '/' . $fileParts['filename'] . '.webp';
											
											$this->mergeFields['Mobile_Srcset'] = $sURL. ' 1x, '. $respURL. ' 2x';

											if (ze::in($mimeType, 'image/png', 'image/jpeg')) {
												$this->mergeFields['Mobile_Srcset_WebP'] = $this->respImageWebP. ' 1x, '. $this->retinaRespImageWebP. ' 2x';
											}
										}
									}
								}
								
								$this->mergeFields['Mobile_Media'] = '(max-width: '. (ze::$minWidth - 1). 'px)';
						
								if ($respWidth != $width
								 || $respHeight != $height) {
									$this->styles[] = 'body.mobile #'. $this->containerId. '_img { width: '. $respWidth. 'px; height: '. $respHeight. 'px; }';
									$addWidthAndHeightInline = false;
								}
							}
							
							break;
						
						//Hide the image on mobiles, and add some hacks to try and make sure that they never even try to download it
						case 'mobile_hide_image':
							$this->mergeFields['Mobile_Media'] = '(max-width: '. (ze::$minWidth - 1). 'px)';
							$trans = ze\link::absoluteIfNeeded(). 'zenario/admin/images/trans.png';
							$this->mergeFields['Mobile_Srcset'] = $trans. ' 1x, '. $trans. ' 2x';
							$this->styles[] = 'body.mobile #'. $this->containerId. '_img { display: none; }';
					}
				}
				
				
				
				//Set a rollover version of the image
				if ($this->setting('advanced_behaviour')
				 && ($this->setting('advanced_behaviour') == 'use_rollover')
				 && $this->setting('image_source') == '_CUSTOM_IMAGE'
				 && ($rollover_image = $this->setting('rollover_image'))) {
					$this->mergeFields['Rollover_Image'] = true;
					
					$rollSrcset = '';
					$normalSrcset = '';
					
					$rWidth = $rHeight = $rollURL = false;
					if (ze\file::imageLink($rWidth, $rHeight, $rollURL, $rollover_image, $banner_width, $banner_height, $banner_canvas, $banner_offset, $banner_retina)) {
						
						$this->rolloverImage = $rollURL;
						$this->mergeFields['Rollover_Image_URL'] = $this->rolloverImage;

						$mimeType = ze\file::mimeType($rollURL);

						if (ze::in($mimeType, 'image/png', 'image/jpeg')) {
							$fileParts = pathinfo($rollURL);
							$this->rolloverImageWebP = $fileParts['dirname'] . '/' . $fileParts['filename'] . '.webp';
							$this->mergeFields['Rollover_WebP_Image_URL'] = $this->rolloverImageWebP;
						}
						
						//If this was a retina image, get a normal version of the image as well for standard displays
						if ($banner_retina) {
							$sWidth = $sHeight = $sURL = false;
							if (ze\file::imageLink($sWidth, $sHeight, $sURL, $rollover_image, $rWidth, $rHeight, $banner_canvas == 'crop_and_zoom'? 'crop_and_zoom' : 'adjust', $banner_offset, false)) {
								if ($rollURL != $sURL) {
									$rollSrcset = $rollURL. ' 2x';
									$rollSrcset = 'srcset="'. htmlspecialchars($rollSrcset). '"';

									if (ze::in($mimeType, 'image/png', 'image/jpeg')) {
										$fileParts = pathinfo($rollURL);
										$this->rolloverImageRetinaWebP = $fileParts['dirname'] . '/' . $fileParts['filename'] . '.webp';
										$this->mergeFields['Rollover_Image_Retina_Srcset'] = $this->rolloverImageRetinaWebP;
									}
									
									$this->rolloverImage = $sURL;
									$this->retinaRolloverImage = $rollURL;
									
									$rollURL = $sURL;
								}
							}
						}
					}
					
					if (!empty($this->mergeFields['Image_Retina_Srcset'])) {
						$normalSrcset = 'srcset="'. htmlspecialchars($this->mergeFields['Image_Retina_Srcset']). '"';
					}
					
					//Do not allow mobile images to use a rollover.
					//Mobile images have a "media" attribute set in the framework.
					//Non-mobile images do not have that attribute.
					//The onmouseover event will skip images with the "media" attribute.
					$this->mergeFields['Wrap'] =
						'onmouseover="
							var
								i = 0,
								x = \''. $this->containerId. '\',
								y = document.getElementById(x + \'_img\'),
								z = document.getElementById(x + \'_rollover\');
							
							y.src = z.src;
							y.srcset = z.srcset;
							
							while (y = document.getElementById(x + \'_source_\' + ++i)) {
								if (!y.media) {
									z = document.getElementById(x + \'_rollover_source_\' + i);
									y.srcset = z.srcset;
								}
							}"
					
						onmouseout="
							var
								i = 0,
								x = \''. $this->containerId. '\',
								y = document.getElementById(x + \'_img\'),
								z = document.getElementById(x + \'_rollout\');
							
							y.src = z.src;
							y.srcset = z.srcset;

							while (y = document.getElementById(x + \'_source_\' + ++i)) {
								z = document.getElementById(x + \'_rollout_source_\' + i);
								y.srcset = z.srcset;
							}"';
				
				
				
				
				} if (($this->setting('link_type')=='_ENLARGE_IMAGE') && ($this->setting('image_source') != '_STICKY_IMAGE') && (empty($this->mergeFields['Link_Href']))){
					if (ze\file::imageLink($widthFullSize, $heightFullSize, $urlFullSize, $imageId, $banner__enlarge_width, $banner__enlarge_height, $banner__enlarge_canvas)) {
						$this->mergeFields['Link_Href'] = 'rel="lightbox" href="' . htmlspecialchars($urlFullSize) . '" class="enlarge_in_fancy_box" ';
						$this->mergeFields['Image_Link_Href'] = 'rel="colorbox" href="' . htmlspecialchars($urlFullSize) . '" class="enlarge_in_fancy_box" ';
						
						if ($this->setting('floating_box_title_mode') == 'overwrite') {
							$this->mergeFields['Link_Href'] .= ' data-box-title="'. htmlspecialchars($this->setting('floating_box_title')). '"';
							$this->mergeFields['Image_Link_Href'] .= ' data-box-title="'. htmlspecialchars($this->setting('floating_box_title')). '"';
						} else {
							$this->mergeFields['Link_Href'] .= ' data-box-title="'. htmlspecialchars($image['floating_box_title']). '"';
							$this->mergeFields['Image_Link_Href'] .= ' data-box-title="'. htmlspecialchars($image['floating_box_title']). '"';
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
		
		//Enable lazy load in the framework if enabled.
		$this->mergeFields['Lazy_Load'] = (
			$this->setting('advanced_behaviour') && $this->setting('advanced_behaviour') == 'lazy_load'
			&& $this->setting('mobile_behaviour') && $this->setting('mobile_behaviour') == 'mobile_same_image');
		
		$this->subSections['Text'] = (bool) $this->setting('text') || $this->editing;
		$this->subSections['Title'] = (bool) $this->setting('title') || $this->editing;
		$this->subSections['Title_Anchor_Enabled'] = (bool) $this->setting('set_an_anchor');
		$this->subSections['Title_Anchor'] = $this->setting('anchor_name');
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
			if ($this->setting('advanced_behaviour') && ($this->setting('advanced_behaviour') == 'background_image') && $this->normalImage) {
				$this->mergeFields['Wrap'] = '';
				$this->mergeFields['Background_Image'] = true;
				$this->mergeFields['Image_css_id'] = $this->containerId. '_img';

				$isJpegOrPng = ze::in($mimeType, 'image/png', 'image/jpeg');
				
				if ($isJpegOrPng && $this->normalImageWebP) {
					$this->styles[] = '#'. $this->containerId. '_img { display: block; background-repeat: no-repeat; background-image: url(\''. htmlspecialchars($this->normalImageWebP).  '\'); }';
					$this->styles[] = 'body.no_webp #'. $this->containerId. '_img { display: block; background-repeat: no-repeat; background-image: url(\''. htmlspecialchars($this->normalImage).  '\'); }';
				} else {
					$this->styles[] = '#'. $this->containerId. '_img { display: block; background-repeat: no-repeat; background-image: url(\''. htmlspecialchars($this->normalImage).  '\'); }';
				}
				
				if ($this->retinaImage) {
					if ($isJpegOrPng) {
						$fileParts = pathinfo($this->retinaImage);
						$this->retinaImageWebP = $fileParts['dirname'] . '/' . $fileParts['filename'] . '.webp';
						
						$this->styles[] = 'body.retina #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->retinaImageWebP).  '\'); background-size: '. $width. 'px '. $height. 'px; }';
						$this->styles[] = 'body.retina.no_webp #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->retinaImage).  '\'); background-size: '. $width. 'px '. $height. 'px; }';
					} else {
						$this->styles[] = 'body.retina #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->retinaImage).  '\'); background-size: '. $width. 'px '. $height. 'px; }';
					}
				}
				
				if ($this->respImage) {
					if ($isJpegOrPng && $this->respImageWebP) {
						$this->styles[] = 'body.mobile #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->respImageWebP).  '\'); }';
					} else {
						$this->styles[] = 'body.mobile #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->respImage).  '\'); }';
					}
					
					if ($this->retinaRespImage) {
						if ($isJpegOrPng && $this->retinaRespImageWebP) {
							$this->styles[] = 'body.mobile.retina #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->retinaRespImageWebP).  '\'); background-size: '. $respWidth. 'px '. $respHeight. 'px; }';
						} else {
							$this->styles[] = 'body.mobile.retina #'. $this->containerId. '_img { background-image: url(\''. htmlspecialchars($this->retinaRespImage).  '\'); background-size: '. $respWidth. 'px '. $respHeight. 'px; }';
						}
					}
				}
				
				$addWidthAndHeightInline = false;
			}
			
			if ($addWidthAndHeight) {
				if ($addWidthAndHeightInline) {
					$this->mergeFields['Image_Style'] .= ' style="width: '. $width. 'px; height: '. $height. 'px;"';
			
				} else {
					array_unshift($this->styles, '#'. $this->containerId. '_img { width: '. $width. 'px; height: '. $height. 'px; }');
				}
			}
			
			//If we're reloading via AJAX, we need to call a JavaScript function to add the style to the head.
			//Otherwise we can use addToPageHead() below.
			if ($this->styles !== [] && $this->methodCallIs('refreshPlugin')) {
				$this->callScriptBeforeAJAXReload('zenario', 'addStyles', $this->containerId, implode("\n", $this->styles));
			}
			
			return true;
		}
	}
	
	public function addToPageHead() {
		if ($this->styles !== []) {
			echo "\n", '<style type="text/css" id="', $this->containerId, '-styles">', "\n", implode("\n", $this->styles), "\n", '</style>';
		}
	}
	
	public function privacyWarning($field, $contentItemPrivacy) {
		//Get selected document...
		if (isset($field['document_id']['current_value'])) {
			$documentId = $field['document_id']['current_value'];
		} else {
			$documentId = $field['document_id']['value'];
		}

		//...get privacy settings of the document and content item...
		$document = ze\row::get('documents', ['filename', 'privacy'], ['id' => $documentId]);
		

		//...and display or hide a privacy warning note if necessary.
		if ($document['privacy'] == 'private' && ($contentItemPrivacy == 'public' || $contentItemPrivacy == 'logged_out')) {
			$field['privacy_warning']['note_below'] = '<p>Warning: this content item is public, the selected document is private, so it will not appear to visitors.</p>';
		} elseif ($document['privacy'] == 'offline') {
			$field['privacy_warning']['note_below'] = '<p>Warning: the selected document is offline, so it will not appear to visitors. Change the privacy of the document to make it available.</p>';
		} else {
			$field['privacy_warning']['note_below'] = '';
		}
		
		return $field['privacy_warning']['note_below'];
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
	
	
	public function fillAdminSlotControls(&$controls) {
		
		//If this is a version controlled plugin and the current administrator is an author,
		//show the cut/copy/patse options
		if ($this->isVersionControlled && ze\priv::check('_PRIV_EDIT_DRAFT')) {
			
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
			if (ze::$cVersion == ze::$adminVersion
			 && ze\priv::check('_PRIV_EDIT_DRAFT', ze::$cID, ze::$cType)) {
				
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
				$controls['actions']['banner_edit_title'] = [
					'ord' => 1.1,
					'label' => ze\admin::phrase('Edit title & HTML (inline)'),
					'page_modes' => $controls['actions']['settings']['page_modes'],
					'onclick' => htmlspecialchars_decode($this->editTitleInlineOnClick())];
				$controls['actions']['settings']['label'] = ze\admin::phrase('Edit contents (admin box)');
			}
			
		}
	}

}
