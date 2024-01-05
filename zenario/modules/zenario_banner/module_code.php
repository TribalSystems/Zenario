<?php
/*
 * Copyright (c) 2024, Tribal Limited
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
		
		//Special logic for Storefront Banner: attempt to load the product information.
		//This will be used later.
		if ($linkTo == '_PRODUCT_DESCRIPTION_PAGE') {
			if ($this->setting("product_source") == 'auto') {
				$product = zenario_ecommerce_manager::getProductFromDescriptiveContentItem($this->cID, $this->cType);
			} elseif ($this->setting("product_source") == 'select_product' && $this->setting("product_for_sale")) {
				$product = zenario_ecommerce_manager::getProduct($this->setting("product_for_sale"));
			} else {
				$product = false;
			}
		}
		
		//Check to see if an item is set in the hyperlink_target setting 
		if (
			($linkTo == '_CONTENT_ITEM'
		 		&& ($linkExists = $this->getCIDAndCTypeFromSetting($cID, $cType, $hyperlink_target, $useTranslation)))
		 	||
		 		//Special logic for Storefront Banner: link to a product's descriptive page
		 		(
					$linkTo == '_PRODUCT_DESCRIPTION_PAGE'
					&& $product
					&& is_array($product)
					&& isset($product['content_item_id'])
					&& ($cID = $product['content_item_id'])
					&& isset($product['content_item_type'])
					&& ($cType = $product['content_item_type'])
		 		)
		) {
			
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
			    $link .= '#' . rawurlencode($anchor);
			}
			if ($this->setting('link_to_anchor_'. $imageId) && ($anchor = $this->setting('hyperlink_anchor_'. $imageId))) {
			    $link .= '#'. rawurlencode($anchor);
			}
			//Use the Theme Section for a Masthead with a link and set the link
			$mergeFields['Image_Link_Href'] =
				'href="'. htmlspecialchars($link). '"';
			
			if ($downloadFile) {
				$mergeFields['Target_Blank'] = ' onclick="'. htmlspecialchars(ze\file::trackDownload($link)). '"';
			}
			
			
			$this->allowCaching(
				$atAll = true, $ifUserLoggedIn = $this->setting('hide_private_item') == '_ALWAYS_SHOW', $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
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
				
				$mergeFields['External_Url_Class'] = 'link_external';
			
			} elseif ($linkTo == '_EMAIL') {
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
				$this->requireJsLib('zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox.min.js');
				
				$mergeFields['Target_Blank'] .= ' onclick="
					if (window.$) {
						$.colorbox({
							href: \''. ze\escape::jsOnClick($link). '\',
							className: \'zenario_banner_cb\',
							iframe: true,
							width: \'95%\',
							height: \'95%\'
						});
						return false;
					}
				"';
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
					$this->setSetting('text', ze\ring::sanitiseWYSIWYGEditorHTML(ze\ring::decodeIdForOrganizer($_POST['content__content'] ?? '')), true, true, 'translatable_html');
					$this->setSetting('title', ze\ring::decodeIdForOrganizer($_POST['content__title'] ?? ''), true, true, 'translatable_text');
					exit;
				}
				//N.b. encodeItemIdForOrganizer() was called on the HTML, to avoid sending RAW HTML over post and potentially
				//triggering Cloudflare to blocks it, so we need to call decodeIdForOrganizer() to decode it.
				
				$this->editorId = $this->containerId. '_tinymce_content_'. str_replace('.', '', microtime(true));
			
				//Open the editor immediately if it is in the URL
				if (ze::request('content__edit_container') == $this->containerId) {
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
		
		//Special logic for Storefront Banner: attempt to load the product information.
		//This will be used later.
		if ($this->setting("product_source") == 'auto') {
			$product = zenario_ecommerce_manager::getProductFromDescriptiveContentItem($this->cID, $this->cType);
		} elseif ($this->setting("product_source") == 'select_product' && $this->setting("product_for_sale")) {
			$product = zenario_ecommerce_manager::getProduct($this->setting("product_for_sale"));
		} else {
			$product = false;
		}
		
		//Attempt to find a masthead image to display
		//Check to see if an overwrite has been set, and use it if so
		if (($this->setting('image_source') == '_CUSTOM_IMAGE'
		  && ($imageId = $this->setting('image')))
		
		 || ($this->setting('image_source') == '_PICTURE' //TODO looks like a variable that was removed
		  && (ze\content::getCIDAndCTypeFromTagId($pictureCID, $pictureCType, $this->setting("picture")))
		  && ($imageId = ze\row::get("versions", "file_id", ["id" => $pictureCID, 'type' => $pictureCType, "version" => ze\content::version($pictureCID, $pictureCType)])))
		 
		 || ($this->setting('image_source') == '_STICKY_IMAGE'
		  && $cID
		  && ($imageId = ze\file::itemStickyImageId($cID, $cType)))
		 
		 //Special logic for Storefront Banner if set to display the product image
		 || ($this->setting('image_source') == '_PRODUCT_IMAGE' && !empty($product) && is_array($product) && !empty($product['image_id']) && ($imageId = $product['image_id']))
		) {
			
			//Get the resize options for the image from the plugin settings
			$setCanvas = $this->setting('canvas');
			$setWidth = $this->setting('width');
			$setHeight = $this->setting('height');
			$setRetina = $this->setting('retina');
			
			//If this banner is in a nest, check if there are default settings set by the nest
			$inNest = isset($this->parentNest);
			
			
			if ($inNest) {
				//The banner plugin has some fiddly logic for the default value of the canvas.
				//In a nest, the default value should be "Unlimited/Inherit"
				if ($setCanvas == 'DEFAULT') {
					$setCanvas = 'unlimited';
				}
				
				if ($setCanvas == 'unlimited'
				 && $this->parentNest->setting('banner_canvas')) {
					$setCanvas = $this->parentNest->setting('banner_canvas');
					$setWidth = $this->parentNest->setting('banner_width');
					$setHeight = $this->parentNest->setting('banner_height');
					$setRetina = $this->parentNest->setting('banner_retina');
				}
			
			} else {
				//The banner plugin has some fiddly logic for the default value of the canvas.
				//Outside of a nest, the default value should be "Crop and Zoom"
				if ($setCanvas == 'DEFAULT') {
					$setCanvas = 'crop_and_zoom';
				}
			}
			
			
			$setBehaviour = $this->setting('advanced_behaviour');
			
			if ($inNest
			 && $setBehaviour == 'none'
			 && $this->parentNest->setting('advanced_behaviour')) {
				$setBehaviour = $this->parentNest->setting('advanced_behaviour');
			}
			
			
			
			$setMobBehaviour = $this->setting('mobile_behaviour');
			$setMobWidth = $this->setting('mobile_width');
			$setMobHeight = $this->setting('mobile_height');
			$setMobCanvas = $this->setting('mobile_canvas');
			$setMobRetina = $this->setting('mobile_retina');
			
			if ($inNest
			 && $setMobBehaviour == 'mobile_same_image'
			 && $this->parentNest->setting('mobile_behaviour')) {
				$setMobBehaviour = $this->parentNest->setting('mobile_behaviour');
				
				if ($setMobBehaviour == 'mobile_same_image_different_size'
				 && $this->parentNest->setting('mobile_canvas')) {
					$setMobCanvas = $this->parentNest->setting('mobile_canvas');
					$setMobWidth = $this->parentNest->setting('mobile_width');
					$setMobHeight = $this->parentNest->setting('mobile_height');
					$setMobRetina = $this->parentNest->setting('mobile_retina');
				}
			}
			
			
			
			$setLargeTitle = $this->setting('floating_box_title_mode');
			$setLargeCanvas = $this->setting('enlarge_canvas');
			$setLargeWidth = (int) $this->setting('enlarge_width');
			$setLargeHeight = (int) $this->setting('enlarge_height');
			$setLinkType = $this->setting('link_type');
			
			if ($inNest
			 && $this->parentNest->setting('link_type')
			 && $setLinkType == '_NO_LINK') {
				$setLinkType = $this->parentNest->setting('link_type');
				
				if ($this->parentNest->setting('enlarge_canvas')
				 && $this->parentNest->setting('enlarge_canvas') != 'unlimited'
				 && $setLinkType == '_ENLARGE_IMAGE') {
					$setLargeWidth = $this->parentNest->setting('enlarge_width');
					$setLargeHeight = $this->parentNest->setting('enlarge_height');
					$setLargeCanvas = $this->parentNest->setting('enlarge_canvas');
				}
			}
			
			
			$cols = [];
			if (!$this->setting('alt_tag')) {
				$cols[] = 'alt_tag';
			}
			
			if ($setLinkType=='_ENLARGE_IMAGE' && $setLargeTitle != 'overwrite') {
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
			$useRollover = $cssRollover = $jsRollover =
			$showAsBackgroundImage = $lazyLoad = $hideOnMob = $changeOnMob =
			$mobImageId = $mobMaxWidth = $mobMaxHeight = $mobCanvas = $mobRetina = false;
			$cssClass = $rolloverClass = $styles = $attributes = $sourceIDPrefix = '';
			$preferInlineStypes = true;
			$makeWebP = $this->setting('webp');
			$mobWebP = $this->setting('mobile_webp');
			
			$htmlID = $this->containerId. '_img';
			
			if ($this->setting('link_type') == '_EXTERNAL_URL') {
				$cssClass = 'link_external';
			}
			
			$altTag = '';
			if ($this->setting('alt_tag')) {
				$altTag = $this->setting('alt_tag');
			
			} elseif (!empty($image)) {
				$altTag = $image['alt_tag'];
			}
			$this->mergeFields['Image_Alt'] = htmlspecialchars($altTag);
			
			//Change some parameters based on the option chosen for  the "Additional behaviour"
			//(né "Advanced behaviour") plugin setting.
			switch ($setBehaviour) {
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
						
						if ($this->setting('rollover_tech') == 'css') {
							$cssRollover = true;
							
							if ($cssClass !== '') {
								$cssClass .= ' ';
							}
							$rolloverClass = $cssClass. 'zenario_rollover';
							$cssClass .= 'zenario_rollout';
						
						} else {
							$jsRollover = true;
							
							//We'll need to give IDs to our <source> tags for the rollover code below to work.
							$sourceIDPrefix = $this->containerId. '_source_';
						}
					}
					break;
			}
			
			//Change some parameters based on the option chosen for "mobile behaviour" in the plugin settings
			//But note this needs a responsive layout with a minimum width set to work.
			//It's also not currently compatible with the "Lazy load" option.
			if (!$lazyLoad && ze::$minWidth) {
				switch ($setMobBehaviour) {
					
					//Same image as for desktop, but use a different size
					case 'mobile_same_image_different_size':
						$changeOnMob = true;
						$preferInlineStypes = false;
						$mobImageId = $imageId;
						$mobMaxWidth = $setMobWidth;
						$mobMaxHeight = $setMobHeight;
						$mobCanvas = $setMobCanvas;
						$mobRetina = $setMobRetina;
						break;
					
					//Different image
					case 'mobile_change_image':
						if ($mobImageId = $this->setting('mobile_image')) {
							$changeOnMob = true;
							$preferInlineStypes = false;
							$mobMaxWidth = $setMobWidth;
							$mobMaxHeight = $setMobHeight;
							$mobCanvas = $setMobCanvas;
							$mobRetina = $setMobRetina;
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
				$this->noteImage($imageId), $setWidth, $setHeight, $setCanvas, $setRetina, $makeWebP,
				$altTag, $htmlID, $cssClass, $styles, $attributes,
				$showAsBackgroundImage, $lazyLoad, $hideOnMob, $changeOnMob,
				$this->noteImage($mobImageId), $mobMaxWidth, $mobMaxHeight, $mobCanvas, $mobRetina, $mobWebP,
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
				if ($cssRollover) {
					//If we're using a CSS rollover, we need to prepare one more <picture> element for the rollover image.
					$htmlID = $this->containerId. '_rollover';
					$showImageLinkInAdminMode = true;
					$alsoShowMobileLink = false;
					
					if ($changeOnMob && $mobImageId != $imageId) {
						$imageLinkNum = 3;
					} else {
						$imageLinkNum = 2;
					}
					
					$html .= ze\file::imageHTML(
						$this->styles, $preferInlineStypes,
						$this->noteImage($rolloImageId), $setWidth, $setHeight, $setCanvas, $setRetina, $makeWebP,
						$altTag, $htmlID, $rolloverClass, $styles, $attributes,
						$showAsBackgroundImage, $lazyLoad, $hideOnMob, $changeOnMob,
						$mobImageId, $mobMaxWidth, $mobMaxHeight, $mobCanvas, $mobRetina, $mobWebP,
						$sourceIDPrefix,
						$showImageLinkInAdminMode, $imageLinkNum, $alsoShowMobileLink
					);
					
					
					
				} elseif ($jsRollover) {
					//If we're using a JS rollover, we need to prepare two more <picture> elements.
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
						$this->noteImage($rolloImageId), $setWidth, $setHeight, $setCanvas, $setRetina, $makeWebP,
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
						$imageId, $setWidth, $setHeight, $setCanvas, $setRetina, $makeWebP,
						$altTag, $htmlID, $cssClass, $styles, $attributes,
						$showAsBackgroundImage, $lazyLoad, $hideOnMob, $changeOnMob,
						$mobImageId, $mobMaxWidth, $mobMaxHeight, $mobCanvas, $mobRetina, $mobWebP,
						$sourceIDPrefix,
						$showImageLinkInAdminMode
					);
					
					//Add some code to switch the images in the <picture> element when the mouse
					//hovers over the wrapper, and switch back again when the mouse leaves.
					$rolloverJS = file_get_contents(CMS_ROOT. 'zenario/js/rollover.min.js');
					$rolloverJS = str_replace('"', "'", $rolloverJS);
					$mouseOverJS = str_replace('();', '(document,"'. ze\escape::js($this->containerId). '","rollover");', $rolloverJS);
					$mouseOutJS = str_replace('();', '(document,"'. ze\escape::js($this->containerId). '","rollout");', $rolloverJS);
					
					$this->mergeFields['Wrap'] = '
						onmouseover="'. htmlspecialchars($mouseOverJS). '"
						onmouseout="'. htmlspecialchars($mouseOutJS). '"';
				}
				
				$this->mergeFields['Image_HTML'] = $html;
				$this->mergeFields['Background_Image_Attributes'] = '';
			}
			
			$this->subSections['Image'] = true;
			
			if ($setLinkType == '_ENLARGE_IMAGE') {
				
				$width = $height = $url = $webPURL = $isRetina = $mimeType = false;
				if (ze\file::imageAndWebPLink($width, $height, $url, $makeWebP, $webPURL, false, $isRetina, $mimeType, $imageId, $setLargeWidth, $setLargeHeight, $setLargeCanvas)) {
					
					$this->requireJsLib('zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox.min.js');
					
					$this->mergeFields['Image_Link_Href'] = 'rel="colorbox" href="' . htmlspecialchars($url) . '" class="enlarge_in_fancy_box" ';
					
					if ($makeWebP && $webPURL) {
						$this->mergeFields['Image_Link_Href'] .= ' data-webp-href="'. htmlspecialchars($webPURL). '"';
					}
					
					//HTML 5 friendly version of the above code
						//Would need support from colorbox, and ", a[data-colorbox-group]" added to the jQuery pattern that sets colorboxes up
					//$this->mergeFields['Image_Link_Href'] = 'data-colorbox-group="group2" href="' . htmlspecialchars($url) . '" class="enlarge_in_fancy_box"';
					
					if ($setLargeTitle == 'overwrite') {
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
		
		$this->subSections['Text'] = (bool) $this->setting('text') || $this->editing || $this->setting("use_product_display_name");
		$this->subSections['Title'] = (bool) $this->setting('title') || $this->editing || $this->setting("use_product_display_name");
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
				if ($this->setting("use_product_display_name") && !empty($product) && is_array($product) && !empty($product['product_display_name'])) {
					$this->mergeFields['Title'] = htmlspecialchars($product['product_display_name']);
				} else {
					$this->mergeFields['Title'] = htmlspecialchars($this->setting('title'));
				}
				
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
				if ($this->setting("use_product_display_name") && !empty($product) && is_array($product) && !empty($product['description'])) {
					$this->mergeFields['Text'] = $product['description'];
				} else {
					$this->mergeFields['Text'] = $this->setting('text');
				}
				
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
			
			//If we're reloading via AJAX, our addToPageHead() method won't be called, and the addStylesOnAJAXReload() function will add the styles.
			//Otherwise the styles will be added using addToPageHead() and addStylesOnAJAXReload() as as normal.
			$this->addStylesOnAJAXReload($this->styles);
			
			return true;
		}
	}
	
	public function addToPageHead() {
		$this->addStylesToPageHead($this->styles);
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
	
	public static function nestedPluginName($eggId, $instanceId, $moduleClassName) {
		
		$desc = [];
		$desc[] = ze\admin::phrase('Banner');
		
		if ($imageId = ze\plugin::setting('image', $instanceId, $eggId)) {
			if ($filename = ze\row::get('files', 'filename', $imageId)) {
				$desc[] = $filename;
			}
		}
		
		if ($title = ze\plugin::setting('title', $instanceId, $eggId)) {
			$desc[] = $title;
		}
		
		if ($cIDAndType = ze\plugin::setting('hyperlink_target', $instanceId, $eggId)) {
			$desc[] = ze\content::formatTagFromTagId($cIDAndType, true);
		}
		
		return implode('; ', $desc);
	}
	
	
	protected $imgsUsed = [];
	public function noteImage($imageId) {
		if ($imageId) {
			if (isset($this->parentNest)) {
				$this->parentNest->noteImage($imageId);
			} else {
				$this->imgsUsed[$imageId] = $imageId;
			}
		}
		return $imageId;
	}
	
	public function fillAdminSlotControls(&$controls) {
		
		//If this is a version controlled plugin and the current administrator is an author
		if ($this->isVersionControlled && ze\priv::check('_PRIV_EDIT_DRAFT')) {
			
			if (isset($controls['actions']['settings'])) {
				//Show an edit inline button
				$controls['actions']['banner_edit_title'] = [
					'ord' => 1.1,
					'label' => ze\admin::phrase('Edit title & HTML (inline)'),
					'page_modes' => $controls['actions']['settings']['page_modes'],
					'onclick' => htmlspecialchars_decode($this->editTitleInlineOnClick())];
				
				//Give the edit in FAB button a more specific name to make it clear it's not the edit inline button
				$controls['actions']['settings']['label'] = ze\admin::phrase('Edit contents (admin box)');
			}
			
		}
		
		$this->listImagesOnSlotControls($controls, $this->imgsUsed, true);
	}

}
