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
					$mergeFields['Image_Link_Href'] =
						'href="'. htmlspecialchars($link). '"';
				}
			
			}  elseif ($linkTo == '_EMAIL') {
				$url = 'email_address';
				if ($link = $this->setting($url)) {
					$mergeFields['Image_Link_Href'] =
						'href="'. htmlspecialchars($link). '"';
				}
			}
		}
		
		if ($link && ($openIn = $this->setting($target_blank))) {
			
			$mergeFields['Target_Blank'] .= ' target="_blank"';
			
			if (!$downloadFile && $openIn == 2) {
				ze::requireJsLib('zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox.min.js');
				
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
		$cID = $cType = false;
		if (!$this->setupLink($this->mergeFields, $cID, $cType, $this->setting('use_translation'))) {
			return false;
		}
		
		
		$pictureCID = $pictureCType = false;
		
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
			$bannerMaxWidth = $this->setting('width');
			$bannerMaxHeight = $this->setting('height');
			$bannerCanvas = $this->setting('canvas');
			$bannerRetina = $this->setting('retina');
			
			//If this banner is in a nest, check if there are default settings set by the nest
			if (isset($this->parentNest)
			 && $this->parentNest->banner_canvas) {
				
				$inheritDimensions = true;
				
				//fixed_width/fixed_height/fixed_width_and_height settings can be merged together
				if ($bannerCanvas == 'fixed_width_and_height'
				 || $this->parentNest->banner_canvas == 'fixed_width_and_height'
				 || ($this->parentNest->banner_canvas == 'fixed_width' && $bannerCanvas == 'fixed_height')
				 || ($this->parentNest->banner_canvas == 'fixed_height' && $bannerCanvas == 'fixed_width')) {
					$bannerCanvas = 'fixed_width_and_height';
				
				//fixed_width/fixed_height/fixed_width_and_height settings on the nest should not be combined with
				//crop_and_zoom settings on the banner, and vice versa. So do an XOR and only update the settings if
				//they're not both different
				} else
				if (!$bannerCanvas
				 || $bannerCanvas == 'unlimited'
				 || !(($this->parentNest->banner_canvas == 'crop_and_zoom') XOR ($bannerCanvas == 'crop_and_zoom'))) {
					$bannerCanvas = $this->parentNest->banner_canvas;
				
				} else {
					$inheritDimensions = false;
				}
				
				if ($inheritDimensions && $this->parentNest->banner_width) {
					if (!$bannerMaxWidth
					 || !ze::in($bannerCanvas, 'fixed_width', 'fixed_width_and_height', 'crop_and_zoom')) {
						$bannerMaxWidth = $this->parentNest->banner_width;
					}
				}
				
				if ($inheritDimensions && $this->parentNest->banner_height) {
					if (!$bannerMaxHeight
					 || !ze::in($bannerCanvas, 'fixed_height', 'fixed_width_and_height', 'crop_and_zoom')) {
						$bannerMaxHeight = $this->parentNest->banner_height;
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
			
			if ($this->setting('show_image_credit')) {
				$cols[] = 'image_credit';
			}
			
			$image = false;
			if (!empty($cols)) {
				$image = ze\row::get('files', $cols, $imageId);
			}
			
			
			//Start prepping some parameters for a call to the ze\file::imageHTML() function
			$useRollover =
			$showAsBackgroundImage = $lazyLoad = $hideOnMob = $changeOnMob =
			$mobImageId = $mobMaxWidth = $mobMaxHeight = $mobCanvas = $mobRetina = false;
			$cssClass = $styles = $attributes = $sourceIDPrefix = '';
			$preferInlineStypes = true;
			$makeWebP = $this->setting('webp');
			$mobWebP = $this->setting('mobile_webp');
			
			$htmlID = $this->containerId. '_img';
			
			$altTag = '';
			if ($this->setting('alt_tag')) {
				$altTag = $this->setting('alt_tag');
			
			} elseif (!empty($image)) {
				$altTag = $image['alt_tag'];
			}
			$this->mergeFields['Image_Alt'] = htmlspecialchars($altTag);
			
			//Change some parameters based on the option chosen for  the "Additional behaviour"
			//(né "Advanced behaviour") plugin setting.
			switch ($this->setting('advanced_behaviour')) {
				case 'background_image':
					$showAsBackgroundImage = true;
					$preferInlineStypes = false;
					break;
				
				case 'lazy_load':
					$lazyLoad = true;
					break;
				
				case 'use_rollover':
					if ($rolloImageId = $this->setting('rollover_image')) {
						$useRollover = true;
						
						//We'll need to give IDs to our <source> tags for the rollover code below to work.
						$sourceIDPrefix = $this->containerId. '_source_';
					}
					break;
			}
			
			//Change some parameters based on the option chosen for "mobile behaviour" in the plugin settings
			//But note this needs a responsive layout with a minimum width set to work.
			//It's also not currently compatible with the "Lazy load" option.
			if (!$lazyLoad && ze::$minWidth) {
				switch ($this->setting('mobile_behaviour')) {
					
					//Same image as for desktop, but use a different size
					case 'mobile_same_image_different_size':
						$changeOnMob = true;
						$preferInlineStypes = false;
						$mobImageId = $imageId;
						$mobMaxWidth = $this->setting('mobile_width');
						$mobMaxHeight = $this->setting('mobile_height');
						$mobCanvas = $this->setting('mobile_canvas');
						$mobRetina = $this->setting('mobile_retina');
						break;
					
					//Different image
					case 'mobile_change_image':
						if ($mobImageId = $this->setting('mobile_image')) {
							$changeOnMob = true;
							$preferInlineStypes = false;
							$mobMaxWidth = $this->setting('mobile_width');
							$mobMaxHeight = $this->setting('mobile_height');
							$mobCanvas = $this->setting('mobile_canvas');
							$mobRetina = $this->setting('mobile_retina');
						}
						break;
					
					//Don't show an image
					case 'mobile_hide_image':
						$hideOnMob = true;
						break;
				}
			}
			
			$html = ze\file::imageHTML(
				$this->styles, $preferInlineStypes,
				$imageId, $bannerMaxWidth, $bannerMaxHeight, $bannerCanvas, $bannerRetina, $makeWebP,
				$altTag, $htmlID, $cssClass, $styles, $attributes,
				$showAsBackgroundImage, $lazyLoad, $hideOnMob, $changeOnMob,
				$mobImageId, $mobMaxWidth, $mobMaxHeight, $mobCanvas, $mobRetina, $mobWebP,
				$sourceIDPrefix
			);
			
			if ($this->setting('show_image_credit')) {
				
				if ($image) {
					$icText = $image['image_credit'];
				
					if (!empty($icText)) {
						$this->mergeFields['Image_Credit_1'] = true;
						$this->mergeFields['Image_Credit_1_CSS'] = '';
						$this->mergeFields['Image_Credit_1_Text'] = $icText;
					}
				}
				
				
				if ($hideOnMob) {
					$this->mergeFields['Image_Credit_1_CSS'] = 'responsive';
				
				} elseif ($changeOnMob && $mobImageId != $imageId) {
					$this->mergeFields['Image_Credit_1_CSS'] = 'responsive';
					
					$icText = ze\row::get('files', 'image_credit', $mobImageId);
					
					if (!empty($icText)) {
						$this->mergeFields['Image_Credit_2'] = true;
						$this->mergeFields['Image_Credit_2_CSS'] = 'responsive_only';
						$this->mergeFields['Image_Credit_2_Text'] = $icText;
					}
				}
			}
			
			if ($showAsBackgroundImage) {
				$this->mergeFields['Image_HTML'] = '';
				$this->mergeFields['Background_Image_Attributes'] = $html;
			
			} else {
				if ($useRollover) {
					//If we're using a rollover, we need to prepare two more <picture> elements.
					//The first is to pre-load and store the rollover image.
					//The second is to store the original image, which will be used later to revert the rollover.
					$ignoreStyles = [];
					$preferInlineStypes = false;
					$styles = 'width: 1px; height: 1px; visibility: hidden;';
					
					$htmlID = $this->containerId. '_rollover';
					$sourceIDPrefix = $this->containerId. '_rollover_source_';
					$showImageLinkInAdminMode = true;
					$alsoShowMobileLink = false;
					
					if ($changeOnMob && $mobImageId != $imageId) {
						$imageLinkNum = 3;
					} else {
						$imageLinkNum = 2;
					}
					
					$html .= ze\file::imageHTML(
						$ignoreStyles, $preferInlineStypes,
						$rolloImageId, $bannerMaxWidth, $bannerMaxHeight, $bannerCanvas, $bannerRetina, $makeWebP,
						$altTag, $htmlID, $cssClass, $styles, $attributes,
						$showAsBackgroundImage, $lazyLoad, $hideOnMob, $changeOnMob,
						$mobImageId, $mobMaxWidth, $mobMaxHeight, $mobCanvas, $mobRetina, $mobWebP,
						$sourceIDPrefix,
						$showImageLinkInAdminMode, $imageLinkNum, $alsoShowMobileLink
					);
					
					$htmlID = $this->containerId. '_rollout';
					$sourceIDPrefix = $this->containerId. '_rollout_source_';
					$showImageLinkInAdminMode = false;
					
					$html .= ze\file::imageHTML(
						$ignoreStyles, $preferInlineStypes,
						$imageId, $bannerMaxWidth, $bannerMaxHeight, $bannerCanvas, $bannerRetina, $makeWebP,
						$altTag, $htmlID, $cssClass, $styles, $attributes,
						$showAsBackgroundImage, $lazyLoad, $hideOnMob, $changeOnMob,
						$mobImageId, $mobMaxWidth, $mobMaxHeight, $mobCanvas, $mobRetina, $mobWebP,
						$sourceIDPrefix,
						$showImageLinkInAdminMode
					);
					
					//Add some code to switch the images in the <picture> element when the mouse
					//hovers over the wrapper, and switch back again when the mouse leaves.
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
					//N.b. do not allow mobile images to use a rollover.
					//Mobile images have a "media" attribute set in the framework.
					//Non-mobile images do not have that attribute.
					//The onmouseover event will skip images with the "media" attribute.
				}
				
				$this->mergeFields['Image_HTML'] = $html;
				$this->mergeFields['Background_Image_Attributes'] = '';
			}
			
			$this->subSections['Image'] = true;
			
			if ($this->setting('link_type') == '_ENLARGE_IMAGE') {
				
				$width = $height = $url = $webPURL = $isRetina = $mimeType = false;
				if (ze\file::imageAndWebPLink($width, $height, $url, $makeWebP, $webPURL, false, $isRetina, $mimeType, $imageId, $banner__enlarge_width, $banner__enlarge_height, $banner__enlarge_canvas)) {
					
					ze::requireJsLib('zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox.min.js');
					
					$this->mergeFields['Image_Link_Href'] = 'rel="colorbox" href="' . htmlspecialchars($url) . '" class="enlarge_in_fancy_box" ';
					
					if ($makeWebP && $webPURL) {
						$this->mergeFields['Image_Link_Href'] .= ' data-webp-href="'. htmlspecialchars($webPURL). '"';
					}
					
					//HTML 5 friendly version of the above code
						//Would need support from colorbox, and ", a[data-colorbox-group]" added to the jQuery pattern that sets colorboxes up
					//$this->mergeFields['Image_Link_Href'] = 'data-colorbox-group="group2" href="' . htmlspecialchars($url) . '" class="enlarge_in_fancy_box"';
					
					if ($this->setting('floating_box_title_mode') == 'overwrite') {
						$caption = $this->setting('floating_box_title');
					} else {
						$caption = $image['floating_box_title'];
					}
					
					if ($this->setting('show_image_credit') && !empty($image['image_credit'])) {
						$icText = $this->phrase('Credit: [[image_credit]]', $image);
						
						if (empty($caption)) {
							$caption = $icText;
						} else {
							$caption .= ' ('. $icText. ')';
						}
					}
					
					if (!empty($caption)) {
						$this->mergeFields['Image_Link_Href'] .= ' data-box-title="'. htmlspecialchars($caption). '"';
					}
					
					$this->subSections['Enlarge_Image'] = true;
				}
			}
		}
		
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
