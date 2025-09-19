<?php
/*
 * Copyright (c) 2025, Tribal Limited
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

namespace ze;

class image {
	
	

	//If an image is set as public, add it to the public/images/ directory
	public static function addToPublicDir($imageId) {
		
		//Note: this actually just works by calling the function to get a link to the image.
		$width = $height = $url = null;
		return \ze\image::link($width, $height, $url, $imageId);
	}
	
	//Given details of an image, return the path it should have were it placed in the public/images/ directory
	public static function publicPath($image) {
		
		$mimeType = $image['mime_type'];
		$safeName = \ze\file::safeName($image['filename']);
		
		//Repeat the same logic that the linkInternal() function uses to decide whether
		//an image should use WebP encoding. I.e. must not be an SVG, must not already be a WebP,
		//and must not be oversized. Again, I've hard-coded the size limit, but it could possibly be a site setting at some point.
		//(Possibly with names like webp_max_width and webp_max_height; just leaving those there in a comment so I can grep for them later...)
		if ($mimeType !== 'image/webp'
		 && $mimeType !== 'image/svg+xml'
		 && ($image['width'] <= 4096 && $image['height'] <= 2160)) {
			$safeName = \ze\file::webpName($safeName);
		}
		
		return 'public/images/'. $image['short_checksum']. '/'. $safeName;
	}
	
	
	//Generate the HTML and CSS tags needed for an image on the page.
	//This supports numerous different modes and features.
	//N.b. some modes/features are not compatible with each other, and are mutualy exclusive:
		//You may only use one of the two following options: Show as background image; Lazy Load
		//Different options for mobile images cannot be used if the Lazy Load option is being used
	public static function html(
		&$cssRules, $preferInlineStypes,
		$imageId, $maxWidth, $maxHeight, $canvas, $retina,
		$altTag = '', $htmlID = '', $cssClass = '', $styles = '', $attributes = '',
		$showAsBackgroundImage = false, $lazyLoad = false, $hideOnMob = false, $changeOnMob = false,
		$mobImageId = false, $mobMaxWidth = false, $mobMaxHeight = false, $mobCanvas = false, $mobRetina = false,
		$sourceIDPrefix = '',
		$showImageLinkInAdminMode = true, $imageLinkNum = 1, $alsoShowMobileLink = true, $mobImageLinkNum = 2
	) {
		//Get a link to the image. Also get retina links if requested.
		$retinaSrcset = 
		$baseURL = $retinaURL =
		$width = $height = $mimeType = false;
		if (\ze\image::htmlInternal(
			$imageId, $maxWidth, $maxHeight, $canvas, $retina,
			$baseURL, $retinaURL,
			$retinaSrcset,
			$width, $height, $mimeType
		)) {
			$setMobDimensions =
			$mobRetinaSrcset =
			$mobBaseURL = $mobRetinaURL =
			$mobWidth = $mobHeight = $mobMimeType = false;
			
			//If in admin mode, add a specific CSS class to images.
			if ($showImageLinkInAdminMode && \ze::isAdmin()) {
				$cssClass .= ' zenario_image_properties zenario_image_id__'. $imageId. '__ zenario_image_num__'. $imageLinkNum. '__';
				
				if ($alsoShowMobileLink && $changeOnMob && $mobImageId != $imageId) {
					$cssClass .= ' zenario_mob_image_id__'. $mobImageId. '__ zenario_mob_image_num__'. $mobImageLinkNum. '__';
				}
				
				if ($canvas == 'crop_and_zoom') {
					$cssClass .= ' zenario_crop_properties';
				}
			}
			
			
			//Show an image in the background.
			//This mode:
				//Requires you to provide a $htmlID for the image.
				//Does not return any HTML.
				//Outputs CSS rules for the image into the $cssRules array.
			if ($showAsBackgroundImage) {
				
				//Add CSS rules for the images width/height/URL.
				$cssRules[] = '#'. $htmlID. ' {
'.					'	display: block;
'.					'	width: '. $width. 'px;
'.					'	height: '. $height. 'px;
'.					'	background-size: '. $width. 'px '. $height. 'px;
'.					'	background-image: url(\''. htmlspecialchars($baseURL).  '\');
'.					'	background-repeat: no-repeat;
'.				'}';
				
				//If we have a retina version of the image, add some extra rules to show this
				//on retina screens.
				if ($retinaURL) {
					$cssRules[] = 'body.retina #'. $htmlID. ' {
'.						'	background-image: url(\''. htmlspecialchars($retinaURL).  '\');
'.					'}';
				}
				
				//If we should hide the image on mobile, add one extra rule for this.
				if ($hideOnMob) {
					$cssRules[] = 'body.mobile #'. $htmlID. ' { display: none; }';
				
				//If we should show a different image on mobile, repeat some of the above logic
				//and add some extra CSS rules for a mobile version.
				} elseif ($changeOnMob) {
					if (\ze\image::htmlInternal(
						$mobImageId, $mobMaxWidth, $mobMaxHeight, $mobCanvas, $mobRetina,
						$mobBaseURL, $mobRetinaURL,
						$mobRetinaSrcset,
						$mobWidth, $mobHeight, $mobMimeType
					)) {
						$cssRules[] = 'body.mobile #'. $htmlID. ' {
'.							'	width: '. $width. 'px;
'.							'	height: '. $height. 'px;
'.							'	background-size: '. $mobWidth. 'px '. $mobHeight. 'px;
'.							'	background-image: url(\''. htmlspecialchars($mobBaseURL).  '\');
'.						'}';
				
						if ($mobRetinaURL) {
							$cssRules[] = 'body.mobile.retina #'. $htmlID. ' {
'.								'	background-image: url(\''. htmlspecialchars($mobRetinaURL).  '\');
'.							'}';
						}
						
						//If the mobile version is displayed at a different width and height, we'll need to add a
						//CSS rule to change that info too.
						if ($mobWidth != $width
						 || $mobHeight != $height) {
							$cssRules[] = 'body.mobile #'. $htmlID. ' { width: '. $mobWidth. 'px; height: '. $mobHeight. 'px; }';
						}
					}
				}
				
				
				$html = '';
				
				$html .= "\n\t". 'id="'. htmlspecialchars($htmlID). '"';
				
				if ($cssClass !== '') {
					$html .= "\n\t". 'class="'. htmlspecialchars($cssClass). '"';
				}
				
				return $html;
			
			
			//Lazy load an image
			//This mode:
				//Writes the URLs for the images needed onto the page, but not in a way that the browser will start loading them.
				//Relies on the jQuery Lazy library to load them later.
				//Has some supporting code in the zenario.addJQueryElements() function (in visitor.js) to trigger the load.
				//Does not currently support different images for mobile devices.
			} elseif ($lazyLoad) {
				
				//This code mostly works by writing a normal image tag, but with a few odd exceptions
				$html = '<img';
				
				//The "lazy" class is used to bind the Lazy Load logic to the image
				$cssClass .= ' lazy';
				
				//Require the lazy-load library
				//Note: due to some bugs when using the "Auto" option for this library, we've removed
				//the option to select "Auto" for this library, so this line is currently not needed.
				//\ze::requireJsLib('zenario/libs/yarn/jquery-lazy/jquery.lazy.min.js');
				
				$html .= "\n\t". 'type="'. htmlspecialchars($mimeType). '"';
				$html .= "\n\t". 'data-src="'. htmlspecialchars($baseURL). '"';
				
				if ($retinaSrcset) {
					$html .= "\n\t". 'data-srcset="'. htmlspecialchars($retinaSrcset). '"';
				}
				
				if ($htmlID !== '') {
					$html .= "\n\t". 'id="'. htmlspecialchars($htmlID). '"';
				}
				
				if ($cssClass !== '') {
					$html .= "\n\t". 'class="'. htmlspecialchars($cssClass). '"';
				}
		
				$html .= "\n\t". 'style="';
			
				$html .= htmlspecialchars('width: '. $width. 'px; height: '. $height. 'px;');
				$html .= ' ';
				$html .= htmlspecialchars($styles);
		
				$html .= '"';
				
				if ($altTag !== '') {
					$html .= "\n\t". 'alt="'. htmlspecialchars($altTag). '"';
				}
		
				if ($attributes !== '') {
					$html .= ' '. $attributes;
				}
				$html .= "\n". '/>';
				
				return $html;
			
			
			//"No additional behaviour" mode - aka normal mode.
			//This mode:
				//Uses a <picture> tag with <source> tags inside to specify all of the possible links for an image.
				//Tries to use inline styles to set information if requested and if possible, however some features do need to use CSS rules.
			} else {
				$html = '<picture>';
					
					//Count how many <source> tags we've used.
					//If we've been asked to give the <source> tags IDs, we'll use this variable to give them each a different ID.
					$sIdNum = 0;
					
					//Try to hide the image on mobile devices
					if ($hideOnMob) {
						if ($preferInlineStypes) {
							//This is a bit annoying to do if we can't use CSS rules.
							//As a hack to try and implement it, we'll add a blank source image.
							$trans = \ze\link::absoluteIfNeeded(). 'zenario/admin/images/trans.png';
					
							$html .= "\n\t". '<source';
								if ($sourceIDPrefix !== '') {
									$html .= ' id="'. htmlspecialchars($sourceIDPrefix. ++$sIdNum). '"';
								}
							$html .= ' srcset="'. htmlspecialchars($trans. ' 1x, '. $trans. ' 2x'). '" media="(max-width: '. (\ze::$minWidth - 1). 'px)" type="image/png">';
						
						} else {
							//So much easier to do if CSS is available!
							$cssRules[] = 'body.mobile #'. $htmlID. ' { display: none; }';
						}
					
					//Show a different image on a mobile device.
					} elseif ($changeOnMob) {
						if (\ze\image::htmlInternal(
							$mobImageId, $mobMaxWidth, $mobMaxHeight, $mobCanvas, $mobRetina,
							$mobBaseURL, $mobRetinaURL,
							$mobRetinaSrcset,
							$mobWidth, $mobHeight, $mobMimeType
						)) {
							if ($mobRetinaSrcset) {
								$mobSrcset = $mobBaseURL. ' 1x, '. $mobRetinaSrcset;
							} else {
								$mobSrcset = $mobBaseURL;
							}
						
							$html .= "\n\t". '<source';
								if ($sourceIDPrefix !== '') {
									$html .= ' id="'. htmlspecialchars($sourceIDPrefix. ++$sIdNum). '"';
								}
							$html .= ' srcset="'. htmlspecialchars($mobSrcset). '" media="(max-width: '. (\ze::$minWidth - 1). 'px)" type="'. htmlspecialchars($mobMimeType). '">';
							
							//If we need to change the image's dimensions on mobile, we can't use inline styles, as they
							//would overwrite the mobile options.
							if ($mobWidth != $width
							 || $mobHeight != $height) {
								$preferInlineStypes = false;
								$setMobDimensions = true;
							}
						}
					}
				
				
		
					//If we have a retina-version of the image, offer it as an alternate by setting a <source> tag
					if ($retinaSrcset) {
						$html .= "\n\t". '<source';
							if ($sourceIDPrefix !== '') {
								$html .= ' id="'. htmlspecialchars($sourceIDPrefix. ++$sIdNum). '"';
							}
						$html .= ' srcset="'. htmlspecialchars($retinaSrcset). '" type="'. htmlspecialchars($mimeType). '">';
					}
					
					//Write out an actual image tag, with the basic version of the image in the src.
					$html .= "\n\t". '<img';
				
						if ($htmlID !== '') {
							$html .= "\n\t\t". 'id="'. htmlspecialchars($htmlID). '"';
						}
					
						$html .= "\n\t\t". 'src="'. htmlspecialchars($baseURL). '"';
				
						if ($cssClass !== '') {
							$html .= "\n\t\t". 'class="'. htmlspecialchars($cssClass). '"';
						}
						
						if ($preferInlineStypes || $styles !== '') {
							$html .= "\n\t\t". 'style="';
						}
						
						//We need to set the width and height of the image. This can either be done using inline styles,
						//or as a CSS rule.
						if ($preferInlineStypes) {
							$html .= htmlspecialchars('width: '. $width. 'px; height: '. $height. 'px;');
						} else {
							$cssRules[] = '#'. $htmlID. ' { width: '. $width. 'px; height: '. $height. 'px; }';
						}
						
						//Set different width/height rules for mobile if needed.
						if ($setMobDimensions) {
							$cssRules[] = 'body.mobile #'. $htmlID. ' { width: '. $mobWidth. 'px; height: '. $mobHeight. 'px; }';
						}
				
						if ($styles !== '') {
							if ($preferInlineStypes) {
								$html .= ' ';
							}
							$html .= htmlspecialchars($styles);
						}
						
						if ($preferInlineStypes || $styles !== '') {
							$html .= '"';
						}
				
						$html .= "\n\t\t". 'type="'. htmlspecialchars($mimeType). '"';
				
						if ($altTag !== '') {
							$html .= "\n\t\t". 'alt="'. htmlspecialchars($altTag). '"';
						}
				
						if ($attributes !== '') {
							$html .= ' '. $attributes;
						}
					$html .= "\n\t". '/>';
				$html .= "\n". '</picture>';
			
				return $html;
			}
		} else {
			return '';
		}
	}
	
	private static function htmlInternal(
		$imageId, $maxWidth, $maxHeight, $canvas, $retina,
		&$baseURL, &$retinaURL,
		&$retinaSrcset,
		&$width, &$height, &$mimeType
	) {
		//The "retina" option only applies to images using the "unlimited" canvas option. 
		$retina = $retina || $canvas != 'unlimited';
		
		$baseURL = $retinaURL =
		$retinaSrcset =
		$width = $height = $baseURL = $isRetina = $mimeType = false;
		
		//Try and get a link to the image.
		if (\ze\image::linkInternal(
			$width, $height, $baseURL, $retina, $isRetina, $mimeType,
			$imageId, $maxWidth, $maxHeight, $canvas
		)) {
			//If this was a retina image, get a normal version of the image as well for standard displays
			if ($isRetina) {
				$sWidth = $sHeight = $sURL = false;
				if (\ze\image::linkInternal(
					$sWidth, $sHeight, $sURL, false, $isRetina, $mimeType,
					$imageId, $width, $height, $canvas == 'crop_and_zoom'? 'crop_and_zoom' : 'adjust'
						//We already know the width and height of the image from the call above,
						//so unless we're using the "crop_and_zoom" option, we don't need any
						//special logic and can switch the canvas to "adjust".
				)) {
					$retinaURL = $baseURL;
					$baseURL = $sURL;
					$retinaSrcset = $retinaURL. ' 2x';
				}
			}
			
			return true;
		}
		
		return false;
	}

	public static function link(
		&$width, &$height, &$url, $imageId, $maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto',
		$useCacheDir = true, $internalFilePath = false, $returnImageStringIfCacheDirNotWorking = false
	) {
		$mimeType = $isRetina = null;
		
		//This debug code here is me trying to look into a problem with large images crashing on my dev siet when they are resized
		//and converted to WebP in one step
		#return false;
		
		#$image = \ze\row::get('files', ['id', 'usage', 'filename', 'mime_type', 'width', 'height', 'size'], $imageId);
		#
		#if ($image && $image['size'] > 1e5) {
		#	//var_dump($imageId, $maxWidth, $maxHeight, $canvas, $image);
		#	return false;
		#}
		
		return \ze\image::linkInternal(
			$width, $height, $url, $retina, $isRetina, $mimeType,
			$imageId, $maxWidth, $maxHeight, $canvas, $offset,
			$fullPath, $privacy,
			$useCacheDir, $internalFilePath, $returnImageStringIfCacheDirNotWorking
		);
	}
	
	public static function internalPath(
		&$width, &$height, &$url, $imageId, $maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto', $useCacheDir = true
	) {
		$mimeType = $isRetina = null;
		
		return \ze\image::linkInternal(
			$width, $height, $url, $retina, $isRetina, $mimeType,
			$imageId, $maxWidth, $maxHeight, $canvas, $offset,
			$fullPath, $privacy, true, true
		);
	}
	
	
	
	public static function retinaLink(
		&$width, &$height, &$url, $imageId,
		$maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto'
	) {
		$mimeType = $isRetina = null;
		
		return \ze\image::linkInternal(
			$width, $height, $url, $retina, $isRetina, $mimeType,
			$imageId, $maxWidth, $maxHeight, $canvas, $offset,
			$fullPath, $privacy
		);
	}
	
	public static function adminLink(
		&$width, &$height, &$url, $imageId,
		$maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$retina = false, $fullPath = false
	) {
		$mimeType = $isRetina = null;

		return \ze\image::linkInternal(
			$width, $height, $url, $retina, $isRetina, $mimeType,
			$imageId, $maxWidth, $maxHeight, $canvas, $offset,
			$fullPath, 'auto', true, false, false, true
		);
	}
	
	public static function adminRetinaLink(
		&$width, &$height, &$url, $imageId,
		$maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$fullPath = false
	) {
		$mimeType = $isRetina = null;

		return \ze\image::linkInternal(
			$width, $height, $url, true, $isRetina, $mimeType,
			$imageId, $maxWidth, $maxHeight, $canvas, $offset,
			$fullPath, 'auto', true, false, false, true
		);
	}
	
	public static function linkInternal(
		&$width, &$height, &$url, $retina, &$isRetina, &$mimeType,
		$imageId, $maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$fullPath = false, $privacy = 'auto',
		$useCacheDir = true, $internalFilePath = false, $returnImageStringIfCacheDirNotWorking = false,
		$adminFacing = false
	) {
		$url =
		$width = $height = $isRetina = $mimeType = false;
		
		$maxWidth = (int) $maxWidth;
		$maxHeight = (int) $maxHeight;
	
		//Check the $privacy variable is set to a valid option
		if ($privacy != 'auto'
		 && $privacy != 'public'
		 && $privacy != 'private') {
			return false;
		}
	
		//Check that this file exists, and is actually an image
		if (!$imageId
		 || !($image = \ze\row::get('files', [
				'privacy', 'mime_type', 'width', 'height',
				'custom_thumbnail_1_width', 'custom_thumbnail_1_height', 'custom_thumbnail_2_width', 'custom_thumbnail_2_height',
				'thumbnail_180x130_width', 'thumbnail_180x130_height',
				'checksum', 'short_checksum', 'filename', 'location', 'path'
			], $imageId, $orderBy = [], $ignoreMissingColumns = true))
		 || !(\ze\file::isImageOrSVG($image['mime_type']))) {
			return false;
		}
		
		//From version 9.7 on Zenario we're adding an extra protection for private images.
		//If an image has been flagged as private, we won't show it unless we're on a private content item.
		if (!$adminFacing && \ze::$isPublic !== false && $image['privacy'] == 'private') {
			
			if (\ze::isAdmin()) {
				\ze\content::$piWarnings[$imageId] = $image['filename'];
			}
			
			return false;
		}
		
		
		$mimeType = $image['mime_type'];
		$inDocstore = $image['location'] == 'docstore';
	
		//SVG images do not need to use the retina logic, as they are always crisp
		if ($isSVG = $mimeType == 'image/svg+xml') {
			$retina = false;
		}
	
		$imageWidth = (int) $image['width'];
		$imageHeight = (int) $image['height'];
		
		$cropX = $cropY = $cropWidth = $cropHeight = $finalImageWidth = $finalImageHeight = false;
	
		//Special case for the "unlimited, but use a retina image" option
		if ($retina && $canvas === 'unlimited') {
			$cropX = $cropY = 0;
			$maxWidth =
			$cropWidth =
			$finalImageWidth = $imageWidth;
			$maxHeight =
			$cropHeight =
			$finalImageHeight = $imageHeight;
			$isRetina = true;
	
		} else {
			//If no limits were set, use the image's own width and height
			if (!$maxWidth) {
				$maxWidth = $imageWidth;
			}
			if (!$maxHeight) {
				$maxHeight = $imageHeight;
			}
			
			\ze\image::scaleByMode(
				$mimeType, $imageWidth, $imageHeight,
				$maxWidth, $maxHeight, $canvas, $offset,
				$cropX, $cropY, $cropWidth, $cropHeight, $finalImageWidth, $finalImageHeight,
				$imageId
			);
			
			//Try to use a retina image if requested
			if ($retina
			 && (2 * $finalImageWidth <= $imageWidth)
			 && (2 * $finalImageHeight <= $imageHeight)) {
				$finalImageWidth *= 2;
				$finalImageHeight *= 2;
				$isRetina = true;
			
			} else {
				$retina = false;
			}
		}
		
		$imageNeedsToBeCropped =
			$cropX != 0
		 || $cropY != 0
		 || $cropWidth != $imageWidth
		 || $cropHeight != $imageHeight;
	
		$imageNeedsToBeResized = $imageNeedsToBeCropped || $imageWidth != $finalImageWidth || $imageHeight != $finalImageHeight;
		$pregeneratedThumbnailUsed = false;
		
		$imageNeedsToBeReEncoded = $mimeType !== 'image/webp';
		
		//SVGs are vector images and don't need resizing or reprocessing.
		if ($isSVG) {
			$imageNeedsToBeResized = false;
			$imageNeedsToBeReEncoded = false;
		}
		
		//With images stored in the dosctore, we have a slight preference for not converting to WebP
		//and keeping the original image exactly as it was encoded.
		//(However if we need to resize the image later, it's okay if it gets converted to WebP.)
		if ($inDocstore) {
			$imageNeedsToBeReEncoded = false;
		}
		
		//WebP encoding is only suitable for sensibly-sized images. The encode tends to crash if
		//an oversized image is being used.
		//For the full-sized copy of an image, only use WebP if it is not overly large. I've hard-coded
		//in a limit of a 4k image here, but this could possibly be a site setting at some point.
		//(Possibly with names like webp_max_width and webp_max_height; just leaving those there in a comment so I can grep for them later...)
		if ($imageNeedsToBeReEncoded
		 && !$imageNeedsToBeResized
		 && ($finalImageWidth > 4096 || $finalImageHeight > 2160)) {
			$imageNeedsToBeReEncoded = false;
		}
		
	
		//Check the privacy settings for the image
		//If the image is set to auto, check the settings here
		if ($image['privacy'] == 'auto') {
		
			//If the privacy settings here weren't specified, try to work them out form the current content item
			if ($privacy == 'auto' && \ze::$equivId) {
				if (\ze::$isPublic) {
					$privacy = 'public';
				} else {
					$privacy = 'private';
				}
			}
		
			//If the privacy settings were specified, and the image was set to auto, update the image and to use these settings
			if ($privacy != 'auto') {
				$image['privacy'] = $privacy;
				\ze\row::update('files', ['privacy' => $privacy], $imageId);
				
				//Catch the following very specific case:
					//An image has just been uploaded and is still set to "auto".
					//It's used in a plugin (e.g. a slideshow) that's on a public page.
					//A resize is used, rather than a full sized image.
				//In this case, as well as flipping the image to public, we need to add it to the public directory.
				if ($privacy == 'public' && $imageNeedsToBeResized) {
					
					//N.b. if the " && $imageNeedsToBeResized" check wasn't in the if-statement above,
					//and the state change in the database didn't happen,
					//it would be possible to send the script into an infinite recursion loop, because
					//the addPublicImage() function actually calls this function again (without a resize)
					//to do its work!
					
					\ze\image::addToPublicDir($imageId);
				}
			}
		}
	
		//If we couldn't work out the privacy settings for an image, assume for now that it is private,
		//but don't update them in the database
		//if ($image['privacy'] == 'auto') {
		//	$image['privacy'] = 'private';
		//}
	
	
		//Combine the resize options into a string
		switch ($canvas) {
			case 'unlimited':
			case 'stretch':
			case 'adjust':
			case 'fixed_width':
			case 'fixed_height':
			case 'resize':
				//For any mode that shows the whole image without cropping it, there's no need to record the mode's name,
				//as any two images at the same dimensions will be the same
				$settingCode = $finalImageWidth. '_'. $finalImageHeight;
				break;
			case 'crop_and_zoom':
				//For crop and zoom mode, we need the crop-settings in the URL, so users don't see cached copies of old crop-settings
				$settingCode = $canvas. '_'. $finalImageWidth. 'x'. $finalImageHeight. '_'. $cropX. 'x'. $cropY. '_'. $cropWidth. 'x'. $cropHeight;
				break;
			case 'resize_and_crop':
				$settingCode = $canvas. '_'. $finalImageWidth. 'x'. $finalImageHeight. '_'. $offset;
				break;
			default:
				$settingCode = $canvas. '_'. $finalImageWidth. 'x'. $finalImageHeight;
		}
		
	
		//If the $useCacheDir variable is set and the public/private directories are writable,
		//try to create this image on the disk
		$path = false;
		$publicImagePath = false;
		if ($useCacheDir && \ze\cache::cleanDirs()) {
			//If this image should be in the public directory, try to create friendly and logical directory structure
			if ($image['privacy'] == 'public') {
				//We'll try to create a subdirectory inside public/images/ using the short checksum as the name
				$path = $publicImagePath = \ze\cache::createDir($image['short_checksum'], 'public/images', false);
			
				//If this is a resize, we'll put the resize in another subdirectory using the code above as the name.
				if ($path && $imageNeedsToBeResized) {
					$path = \ze\cache::createDir($image['short_checksum']. '/'. $settingCode, 'public/images', false);
				}
		
			//If the image should be in the private directory, don't worry about a friendly URL and
			//just use the full hash.
			} else {
				//Workout a hash for the image at this size.
				//Except for SVGs, the hash should also include the resize parameters
				if ($isSVG) {
					$hash = $image['checksum'];
				} else {
					$hash = \ze::hash64($settingCode. '_'. $image['checksum']);
				}			
				
				//Try to get a directory in the cache dir
				$path = \ze\cache::createDir($hash, 'private/images', false);
				
				
				//We shouldn't be using the page/plugin cache in this situation, as each visitor needs to see a unique link.
				//Try to disable caching for the current plugin slot.
				if (!is_null(\ze::$currentSlot) && isset(\ze::$slotContents[\ze::$currentSlot])) {
					\ze::$slotContents[\ze::$currentSlot]->allowCaching(false);
					\ze::$slotContents[\ze::$currentSlot]->setCacheMessage(\ze\admin::phrase('This plugin is using a private image, which has a unique link every time it is displayed, so the plugin cannot be cached.'));
				
				//However if we couldn't track/find this, we'll have to disable caching for the entire page.
				} else {
					\ze::$canCache = false;
				}
			}
		}
		
		//Work out the filename for the (possibly resized) image.
		$safeName = \ze\file::safeName($image['filename']);
		
		//Note that with the exception of SVG images, we'll want to convert to WebP when resizing,
		//so the extension will need to be changed.
		if ($imageNeedsToBeReEncoded) {
			$safeName = \ze\file::webpName($safeName);
			$mimeType = 'image/webp';
		}
		$filepath = CMS_ROOT. $path. $safeName;
		
		//Look for the image inside the cache directory
		if ($path && file_exists($filepath)) {
		
			//If the image is already available, all we need to do is link to it
			if ($internalFilePath) {
				$url = $filepath;
		
			} else {
				if ($fullPath) {
					$abs = \ze\link::absolute();
				} else {
					$abs = \ze\link::absoluteIfNeeded();
				}
				
				$url = $abs. $path. rawurlencode($safeName);
				
				if ($retina) {
					$width = (int) ($finalImageWidth / 2);
					$height = (int) ($finalImageHeight / 2);
				} else {
					$width = $finalImageWidth;
					$height = $finalImageHeight;
				}
			}
			
			return true;
		}
		
		
		//Catch another obscure issue:
			//A public image is missing from the pubic images directory.
			//We're about to display a resized copy of the image.
		//In this case, as well as creating the resized copy as normal,
		//we should check if the full sized version of the image is in the public directory.
		if ($imageNeedsToBeResized && $publicImagePath !== false) {
			if (!file_exists(CMS_ROOT. $publicImagePath. $safeName)) {
				
				//N.b. if the " && $imageNeedsToBeResized" check wasn't in the if-statement above,
				//and the file_exists() check wasn't made,
				//it would be possible to send the script into an infinite recursion loop, because
				//the addPublicImage() function actually calls this function again (without a resize)
				//to do its work!
				
				\ze\image::addToPublicDir($imageId);
			}
		}
		
		
		//If there wasn't already an image we could use in the cache directory,
		//create a resized version now.
		if ($path || $returnImageStringIfCacheDirNotWorking) {
		
			//Where an image has multiple sizes stored in the database, get the most suitable size
			if (\ze::setting('thumbnail_threshold')) {
				$wcit = ((int) \ze::setting('thumbnail_threshold') ?: 66) / 100;
			} else {
				$wcit = 0.66;
			}
			
			//Work out what size the image will need to be before being cropped.
			//(If we're not cropping, this is the same as the final size.)
			if ($imageNeedsToBeCropped) {
				$widthPreCrop = $finalImageWidth * $imageWidth / $cropWidth;
				$heightPreCrop = $finalImageHeight * $imageHeight / $cropHeight;
			} else {
				$widthPreCrop = $finalImageWidth;
				$heightPreCrop = $finalImageHeight;
			}
			
			//If resizing, check to see if we can use a pregenerated thumbnail.
			if ($imageNeedsToBeResized) {
				foreach ([
					['thumbnail_180x130_data', 'thumbnail_180x130_width', 'thumbnail_180x130_height'],
					['custom_thumbnail_1_data', 'custom_thumbnail_1_width', 'custom_thumbnail_1_height'],
					['custom_thumbnail_2_data', 'custom_thumbnail_2_width', 'custom_thumbnail_2_height']
				] as $c) {
			
					//Ideally there'll be a thumbnail with exactly the right size already.
					//Alternately, grab a thumbnail that's smaller than the full image, but big enough to not cause artifacts when
					//resized down.
					$xOK = !empty($image[$c[1]]) && $widthPreCrop == $image[$c[1]] || ($widthPreCrop < $image[$c[1]] * $wcit);
					$yOK = !empty($image[$c[1]]) && $heightPreCrop == $image[$c[2]] || ($heightPreCrop < $image[$c[2]] * $wcit);
			
					if ($xOK && $yOK) {
						$thumbWidth = $image[$c[1]];
						$thumbHeight = $image[$c[2]];
						$image['data'] = \ze\row::get('files', $c[0], $imageId);
						$pregeneratedThumbnailUsed = true;
						
						//Adjust the crop settings down to the same scale factor as the thumbnail
						if ($cropX != 0) {
							$cropX = $cropX * $thumbWidth / $imageWidth;
						}
						if ($cropY != 0) {
							$cropY = $cropY * $thumbHeight / $imageHeight;
						}
						if ($cropWidth != 0) {
							$cropWidth = $cropWidth * $thumbWidth / $imageWidth;
						}
						if ($cropHeight != 0) {
							$cropHeight = $cropHeight * $thumbHeight / $imageHeight;
						}
					
						$imageNeedsToBeResized = $imageNeedsToBeCropped || $thumbWidth != $finalImageWidth || $thumbHeight != $finalImageHeight;
						break;
					}
				}
			}
			
			if ($inDocstore) {
				$pathDS = \ze\file::docstorePath($image['path']);
			}
			
			if (empty($image['data'])) {
				if ($image['location'] == 'db') {
					$image['data'] = \ze\row::get('files', 'data', $imageId);
			
				} elseif ($inDocstore && $pathDS) {
					$image['data'] = file_get_contents($pathDS);
			
				} else {
					return false;
				}
			}
		
			if ($imageNeedsToBeResized || $imageNeedsToBeReEncoded) {
				\ze\image::resizeInternal(
					$image['data'], $mimeType,
					$cropX, $cropY, $cropWidth, $cropHeight, $finalImageWidth, $finalImageHeight
				);
			}
			
			//If $useCacheDir is set, attempt to store the image in the cache directory
			if ($useCacheDir && $path) {
				if ($imageNeedsToBeResized || $imageNeedsToBeReEncoded || $pregeneratedThumbnailUsed || !$inDocstore) {
					file_put_contents($filepath, $image['data']);
					\ze\cache::chmod($filepath, 0666);
				
				} elseif ($inDocstore) {
					if (!file_exists($filepath)) {
						\ze\server::symlinkOrCopy($pathDS, $filepath);
					}
				}
				
				if ($internalFilePath) {
					$url = $filepath;
		
				} else {
					if ($fullPath) {
						$abs = \ze\link::absolute();
					} else {
						$abs = \ze\link::absoluteIfNeeded();
					}
				
					$url = $abs. $path. rawurlencode($safeName);
				}
			
				if ($retina) {
					$width = (int) $finalImageWidth / 2;
					$height = (int) $finalImageHeight / 2;
				} else {
					$width = $finalImageWidth;
					$height = $finalImageHeight;
				}
				
				return true;
		
			//Otherwise just return the data if $returnImageStringIfCacheDirNotWorking is set
			} elseif ($returnImageStringIfCacheDirNotWorking) {
				return $image['data'];
			}
		}
	
		//If $internalFilePath or $returnImageStringIfCacheDirNotWorking were set then we need to give up at this point.
		if ($internalFilePath || $returnImageStringIfCacheDirNotWorking) {
			return false;
	
		//Otherwise, we'll have to link to file.php and do any resizing needed in there.
		} else {
			//Workout a hash for the image at this size
			$hash = \ze::hash64($settingCode. '_'. $image['checksum']);
		
			//Note that using the session for each image is quite slow, so it's better to make sure that your cache/ directory is writable
			//and not use this fallback logic!
			if (!isset($_SESSION['zenario_allowed_files'])) {
				$_SESSION['zenario_allowed_files'] = [];
			}
		
			$_SESSION['zenario_allowed_files'][$hash] =
				[
					'width' => $maxWidth, 'height' => $maxHeight,
					'mode' => $canvas, 'offset' => $offset,
					'id' => $imageId, 'useCacheDir' => $useCacheDir];
		
			$url = 'zenario/file.php?usage=resize&c='. $hash. ($retina? '&retina=1' : ''). '&filename='. rawurlencode($safeName);
		
			if ($retina) {
				$width = (int) $finalImageWidth / 2;
				$height = (int) $finalImageHeight / 2;
			} else {
				$width = $finalImageWidth;
				$height = $finalImageHeight;
			}
			return true;
		}
	}
	
	
	
	//Given an image size and a target size, resize the image (maintaining aspect ratio).
	public static function scale($imageWidth, $imageHeight, $constraint_width, $constraint_height, &$width_out, &$height_out, $allowUpscale = false) {
		$width_out = $imageWidth;
		$height_out = $imageHeight;
	
		if ($imageWidth == $constraint_width && $imageHeight == $constraint_height) {
			return;
		}
	
		if (!$allowUpscale && ($imageWidth <= $constraint_width) && ($imageHeight <= $constraint_height)) {
			return;
		}

		//Attempt to prevent "division by zero" error
		if (empty($imageWidth)) {
			$imageWidth = 1;
		}
		
		if (empty($imageHeight)) {
			$imageHeight = 1;
		}
		
		if (($constraint_width / $imageWidth) < ($constraint_height / $imageHeight)) {
			$width_out = $constraint_width;
			$height_out = (int) ($imageHeight * $constraint_width / $imageWidth);
		} else {
			$height_out = $constraint_height;
			$width_out = (int) ($imageWidth * $constraint_height / $imageHeight);
		}

		return;
	}

	//Given an image size and a target size, resize the image by different conditions and return the values used in the calculations
	public static function scaleByMode(
		$mimeType, $imageWidth, $imageHeight,
		$maxWidth, $maxHeight, &$canvas, $offset,
		&$cropX, &$cropY, &$cropWidth, &$cropHeight, &$finalImageWidth, &$finalImageHeight,
		$imageId = 0
	) {
	
		$maxWidth = (int) $maxWidth;
		$maxHeight = (int) $maxHeight;
		
		//By default, only allow upscaling for SVGs.
		$allowUpscale = $isSVG = $mimeType === 'image/svg+xml';
		
		//Don't allow the "crop" modes for SVGs
		if ($isSVG) {
			switch ($canvas) {
				case 'crop_and_zoom':
				case 'resize_and_crop':
					$canvas = 'resize';
			}
		}
		
		//Most modes don't make use of the crop settings.
		$cropX = $cropY = 0;
		$cropWidth = $imageWidth;
		$cropHeight = $imageHeight;
		
		switch ($canvas) {
			//No limits, just leave the image size as it is
			case 'unlimited':
				$finalImageWidth = $imageWidth;
				$finalImageHeight = $imageHeight;
				break;
			
			//"adjust" is an alternate name for stretch
			case 'adjust':
			
			//Stretch the image to meet the requested width and height, without worrying
			//about maintaining aspect ratio, or DPI/resolution.
			//You might also use this mode if you've previously called the
			//scaleImageDimensionsByMode() or resizeImageString() function, and already
			//know the correct numbers, thus don't need to check them again.
			case 'stretch':
				$allowUpscale = true;
				$finalImageWidth = $maxWidth;
				$finalImageHeight = $maxHeight;
				break;
			
			//Crop and zoom mode. WiP.
			case 'crop_and_zoom':
				$allowUpscale = true;
				$finalImageWidth = $maxWidth;
				$finalImageHeight = $maxHeight;
				
				//
				//To do - if this image has some pre-determined crops,
				//pick the best fit here.
				//
				$bestCropX = 0;
				$bestCropY = 0;
				$bestCropWidth = $imageWidth;
				$bestCropHeight = $imageHeight;
				
				//Attempt to prevent "division by zero" error
				if (empty($maxWidth)) {
					$maxWidth = 1;
				}
				if (empty($maxHeight)) {
					$maxHeight = 1;
				}
				if (empty($bestCropWidth)) {
					$bestCropWidth = 1;
				}
				if (empty($bestCropHeight)) {
					$bestCropHeight = 1;
				}
				
				if (!empty($imageId)) {
					$desiredAspectRatioAngle = \ze\file::aspectRatioToDegrees($maxWidth, $maxHeight);
					$bestAspectRatioAngle = \ze\file::aspectRatioToDegrees($bestCropWidth, $bestCropHeight);
					
					$sql = "
						SELECT crop_x, crop_y, crop_width, crop_height, aspect_ratio_angle
						FROM ". DB_PREFIX. "cropped_images
						WHERE aspect_ratio_angle
							BETWEEN ". (float) ($desiredAspectRatioAngle - \ze\file::ASPECT_RATIO_LIMIT_DEG). "
								AND ". (float) ($desiredAspectRatioAngle + \ze\file::ASPECT_RATIO_LIMIT_DEG). "
						  AND image_id = ". (int) $imageId. "
						  AND ABS (aspect_ratio_angle - ". (float) $desiredAspectRatioAngle. ") <= ". (float) $bestAspectRatioAngle. "
						ORDER BY ABS (aspect_ratio_angle - ". (float) $desiredAspectRatioAngle. ") ASC
						LIMIT 1";
					
					if ($row = \ze\sql::fetchAssoc($sql)) {
						$bestCropX = $row['crop_x'];
						$bestCropY = $row['crop_y'];
						$bestCropWidth = $row['crop_width'];
						$bestCropHeight = $row['crop_height'];
						$bestAspectRatioAngle = $row['aspect_ratio_angle'];
					}
				}
				
				//Slightly reduce either the width or the height of the cropped section
				//to make sure that the resulting aspect ratio matches the aspect ratio requested.
				if (($maxWidth / $bestCropWidth) < ($maxHeight / $bestCropHeight)) {
					$desiredCropWidth = (int) ($bestCropHeight * $maxWidth / $maxHeight);
					$chipOffLeft = (int) (($bestCropWidth - $desiredCropWidth) / 2);
					$chipOffRight = $bestCropWidth - $desiredCropWidth - $chipOffLeft;
					
					$cropX = $bestCropX + $chipOffLeft;
					$cropY = $bestCropY;
					$cropWidth = $bestCropWidth - $chipOffLeft - $chipOffRight;
					$cropHeight = $bestCropHeight;
					
				} else {
					$desiredCropHeight = (int) ($bestCropWidth * $maxHeight / $maxWidth);
					$chipOffTop = (int) (($bestCropHeight - $desiredCropHeight) / 2);
					$chipOffBottom = $bestCropHeight - $desiredCropHeight - $chipOffTop;
					
					$cropX = $bestCropX;
					$cropY = $bestCropY + $chipOffBottom;
					$cropWidth = $bestCropWidth;
					$cropHeight = $bestCropHeight - $chipOffTop - $chipOffBottom;
				}
				break;
			
			//The resize and crop mode is what we used before we implemented
			//the crop and zoom mode.
			//It's basically a (mostly) unguided crop that tried to trim off the edge of the image
			//to fit the requested aspect ratio.
			//Also, another difference here is we care about DPI/resolution, whereas
			//crop and zoom mode doesn't.
			case 'resize_and_crop':
				//Attempt to prevent "division by zero" error
				if (empty($imageWidth)) {
					$imageWidth = 1;
				}
			
				if (empty($imageHeight)) {
					$imageHeight = 1;
				}
			
				if (($maxWidth / $imageWidth) < ($maxHeight / $imageHeight)) {
					$widthPreCrop = (int) ($imageWidth * $maxHeight / $imageHeight);
					$heightPreCrop = $maxHeight;
					$cropWidth = (int) ($maxWidth * $imageHeight / $maxHeight);
					$cropHeight = $imageHeight;
					$finalImageWidth = $maxWidth;
					$finalImageHeight = $maxHeight;
		
				} else {
					$widthPreCrop = $maxWidth;
					$heightPreCrop = (int) ($imageHeight * $maxWidth / $imageWidth);
					$cropWidth = $imageWidth;
					$cropHeight = (int) ($maxHeight * $imageWidth / $maxWidth);
					$finalImageWidth = $maxWidth;
					$finalImageHeight = $maxHeight;
				}
				
				if ($widthPreCrop != $finalImageWidth) {
					$cropX = (int) (((10 - $offset) / 20) * ($imageWidth - $cropWidth));

				} elseif ($heightPreCrop != $finalImageHeight) {
					$cropY = (int) ((($offset + 10) / 20) * ($imageHeight - $cropHeight));
				}
				
				break;
			
			//Max width/height mode are actually implemented by changing the settings,
			//then using resize mode.
			case 'fixed_width':
				$maxHeight = $allowUpscale? 999999 : $imageHeight;
				$canvas = 'resize';
				break;
			
			case 'fixed_height':
				$maxWidth = $allowUpscale? 999999 : $imageWidth;
				$canvas = 'resize';
			
			default:
				$canvas = 'resize';
		}
		
		//For "resize" mode, scale the image whilst maintaining aspect ratio
		if ($canvas == 'resize') {
			$finalImageWidth = false;
			$finalImageHeight = false;
			\ze\image::scale($imageWidth, $imageHeight, $maxWidth, $maxHeight, $finalImageWidth, $finalImageHeight, $allowUpscale);
		}
	
		if ($cropWidth < 1) {
			$cropWidth = 1;
		}
		if ($finalImageWidth < 1) {
			$finalImageWidth = 1;
		}
	
		if ($cropHeight < 1) {
			$cropHeight = 1;
		}
		if ($finalImageHeight < 1) {
			$finalImageHeight = 1;
		}
	}

	public static function resize(
		&$imageString, $mimeType, &$imageWidth, &$imageHeight,
		$maxWidth, $maxHeight, $canvas = 'resize', $offset = 0
	) {
		//Work out the new width/height of the image
		$cropX = $cropY = $cropWidth = $cropHeight = $finalImageWidth = $finalImageHeight = false;
		\ze\image::scaleByMode(
			$mimeType, $imageWidth, $imageHeight,
			$maxWidth, $maxHeight, $canvas, $offset,
			$cropX, $cropY, $cropWidth, $cropHeight, $finalImageWidth, $finalImageHeight
		);
	
		\ze\image::resizeInternal(
			$imageString, $mimeType,
			$cropX, $cropY, $cropWidth, $cropHeight, $finalImageWidth, $finalImageHeight
		);
	
		if (!is_null($imageString)) {
			$imageWidth = $finalImageWidth;
			$imageHeight = $finalImageHeight;
		}
	}

	public static function resizeInternal(
		&$imageString, &$mimeType,
		$cropX, $cropY, $cropWidth, $cropHeight, $finalImageWidth, $finalImageHeight
	) {
		//Check if the image needs to be resized, or converted to WebP
		if ($cropX != 0
		 || $cropY != 0
		 || $cropWidth != $finalImageWidth
		 || $cropHeight != $finalImageHeight
		 || $mimeType !== 'image/webp') {
			
			if (\ze\file::isImage($mimeType)) {
				
				\ze::ignoreErrors();
					
					//The library we are using to handle resizing images can act as a wrapper
					//to either GD or Imagick. However Imagick is not installed by default so
					//we'll use GD here. It would be nice to have a site setting for this though!
					$imageDriver = new \Imagine\Gd\Imagine();
					//$imageDriver = new \Imagine\Imagick\Imagine();
					
					$image = $imageDriver->load($imageString);
					
					//I'd like to get the original string out of memory while we work.
					//However note that it's an in-out parameter! If we were to use unset() it would
					//break the link and stop this function from outputting the string!
					$imageString = '';
					
					//Read what the height and width of the image actually are
					$size = $image->getSize();
					$currentImageWidth = $size->getWidth();
					$currentImageHeight = $size->getHeight();
					
					//Check the crop settings (if any). Crop the image if needed.
					if ($cropX != 0
					 || $cropY != 0
					 || $cropWidth != $currentImageWidth
					 || $cropHeight != $currentImageHeight) {
						$image->crop(new \Imagine\Image\Point($cropX, $cropY), new \Imagine\Image\Box($cropWidth, $cropHeight));
						
						$size = $image->getSize();
						$currentImageWidth = $size->getWidth();
						$currentImageHeight = $size->getHeight();
					}
					
					//Check the size we have against the size we were commanded to make. Resize if needed.
					if ($finalImageWidth != $currentImageWidth
					 || $finalImageHeight != $currentImageHeight) {
						$image->resize(new \Imagine\Image\Box($finalImageWidth, $finalImageHeight));
					}
					
					//As of Zenario 10.1, we'll always save images using the WebP format.
					$filePath = tempnam(sys_get_temp_dir(), 'img');
					$image->save($filePath, ['format' => 'webp', 'quality' => ((int) \ze::setting('webp_quality')) ?: 80]);
					$imageString = file_get_contents($filePath);
					unlink($filePath);
						//N.b. you can use the "get()" function instead of the "save()" function from the library to get the string
						//without first saving it to disk.
						//However this is really badly implemented, as it actually dumps the file to the output then uses ob_get_clean() to retrieve it!
					
					$mimeType = 'image/webp';
					
				\ze::noteErrors();
			}
		}
	}
}
