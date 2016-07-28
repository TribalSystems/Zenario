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

class zenario_slideshow_2 extends module_base_class {
	
	public $slideData = array();
	public $placeholderCSS = '';
	public $allowCaching = true;
	
	public function init() {
		
		if ($userId = userId()) {
			$userDetails = getUserDetails($userId);
		}
		$this->slideData['adminId'] = adminId();
		if ($this->slideData["slides"] = $this->getSlideDetails('main')) {
			$mobileImages = false;
			$missingMobileImage = false;
			$mobileImageDetails = array();
			$maxWidth = 0;
			$maxHeight = 0;
			$maxMobileWidth = 0;
			$maxMobileHeight = 0;
			$placeholderSlide = false;
			foreach ($this->slideData["slides"] as $index => &$slide) {
				if (!is_array($slide)) {
					continue;
				}
				
				// Hide hidden slides if no admin is logged in
				if (!adminId()) {
					if ($slide['hidden']) {
						unset($this->slideData["slides"][$index]);
						continue;
					}
					if ($slide['slide_visibility'] == 'call_static_method') {
						if (!(inc($slide['plugin_class'])
							&& (method_exists($slide['plugin_class'], $slide['method_name']))
							&& (call_user_func(array($slide['plugin_class'], $slide['method_name']),$slide['param_1'], $slide['param_2']))))
						{
							unset($this->slideData["slides"][$index]);
						}
						
					} elseif ($userId) {
						switch($slide['slide_visibility']) {
							case 'logged_in_with_field':
							case 'logged_in_without_field':
								$fieldValue = datasetFieldValue('users', $slide['field_id'], $userId);
								$fieldMatches = (bool)$fieldValue;
								if ($fieldValue != 1) {
									$fieldMatches = false;
								}
								if ($slide['slide_visibility'] != 'logged_in_with_field') {
									$fieldMatches = !$fieldMatches;
								}
								if (!$fieldMatches) {
									unset($this->slideData["slides"][$index]);
								}
								break;
							case 'logged_out':
								unset($this->slideData["slides"][$index]);
								break;
						}
					} else {
						switch($slide['slide_visibility']) {
							case 'logged_in_with_field':
							case 'logged_in_without_field':
							case 'logged_id':
								unset($this->slideData["slides"][$index]);
								break;
						}
					}
					if ($slide['slide_visibility'] != 'everyone') {
						$this->allowCaching = false;
					}
				}
				// Generate slide link if internal
				if (($slide['target_loc'] == 'internal') && $slide['dest_url']) {
					$cID = $cType = false;
					getCIDAndCTypeFromTagId($cID, $cType, $slide['dest_url']);
					if ($slide['link_to_translation_chain']) {
						langEquivalentItem($cID, $cType);
					}
					$slide['dest_url'] = linkToItem($cID, $cType);
				}
				
				// Get slides max mobile width and height
				if ($this->setting('mobile_options') == 'seperate_fixed' || $this->setting('mobile_options') == 'desktop_fixed') {
					if ($slide['mobile_image_src']) {
						$mobileImages = true;
						$maxMobileWidth = ($slide['m_width'] > $maxMobileWidth) ? $slide['m_width'] : $maxMobileWidth;
						$maxMobileHeight = ($slide['m_height'] > $maxMobileHeight) ? $slide['m_height'] : $maxMobileHeight;
					} else {
						$missingMobileImage = true;
					}
				}
				
				// Get slides max desktop width and height
				if (($height = max(array($slide['height'], $slide['r_height']))) > $maxHeight) {
					$maxHeight = $height;
				}
				if (($width = max(array($slide['width'], $slide['r_width']))) > $maxWidth) {
					$maxWidth = $width;
				}
				
				// Add a placeholder image to appear while js loads, or in case of no js
				if (!$placeholderSlide) {
					$this->slideData['placeholder'] = array(
						'image_src' => $slide['image_src'],
						'mobile_image_src' => $slide['mobile_image_src']);
					$this->placeholderCSS = "
						#".$this->containerId."_placeholder_image {
						 display:block;
						 height: ".$slide['height']."px;
						 width: ".$slide['width']."px;
						 background-image: url(".$slide['image_src'].");
						 background-repeat: no-repeat;
						}
						@media (max-width: ".setting('image_mobile_resize_point')."px) {
						 #".$this->containerId."_placeholder_image {
						  display:block;
						  height: ".$slide['m_height']."px;
						  width: ".$slide['m_width']."px;
						  background-image: url(".$slide['mobile_image_src'].");
						  background-repeat: no-repeat;
						 }
						}
					";
					$placeholderSlide = true;
				}
			}
			// If a slide has a mobile image, but some do not, display a smaller version of the original image instead
			if ($mobileImages && $missingMobileImage && ($this->setting('mobile_options') == 'seperate_fixed')) {
				foreach ($this->slideData["slides"] as $index => &$slide) {
					if (!$slide['mobile_image_id']) {
						$width = $height = $url = false;
						imageLink($width, $height, $url, $slide['image_id'], $maxMobileWidth, $maxMobileHeight);
						$slide['mobile_image_src'] = $url;
					}
				}
			}
			
			// Add a heading
			$this->slideData['heading'] = $this->phrase($this->setting('heading'));
			
			$settings = array(
				'desktop_height' => $maxHeight,
				'desktop_width' => $maxWidth,
				'mobile_height' => $maxMobileHeight,
				'mobile_width' => $maxMobileWidth,
				'slide_transition' => $this->setting("fx"),
				'mobile_resize_width' => setting('image_mobile_resize_point'),
				'hover_to_pause' => $this->setting("hover_to_pause"),
				'enable_swipe' => $this->setting("enable_swipe"),
				'auto_play' => $this->setting("auto_play"),
				'slide_duration' => $this->setting("slide_duration"),
				'enable_arrow_buttons' => $this->setting("arrow_buttons"),
				'navigation_style' => $this->setting("navigation_style"),
				'mobile_options' => $this->setting('mobile_options'),
				'desktop_resize_greater_than_image' => $this->setting('desktop_resize_greater_than_image'),
				'has_mobile_images' => $mobileImages);
			
			$this->callScript(
				"zenario_slideshow_2", 
				"initiateSlideshow",
				$this->slideData["slides"],
				$this->pluginAJAXLink(),
				$this->slotName,
				$this->instanceId,
				$settings);
		}
		
		$this->allowCaching(
			$atAll = $this->allowCaching, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = true);
		
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->slideData);
	}
	
	public function addToPageHead() {
		echo "\n<style type=\"text/css\">\n", $this->placeholderCSS, "\n</style>\n";
	}
	
	public function pluginAJAX() {
		
		switch(request('mode')) {
			case 'get_details':
				
				$recommededSize = false;
				$recommededSizeMessage = '';
				if ($this->setting('banner_width') && $this->setting('banner_height')) {
					$recommededSize = true;
					$recommededSizeMessage = adminPhrase('Recommended dimensions: [[width]] x [[height]]', 
						array(
							'width' => $this->setting('banner_width'), 
							'height' => $this->setting('banner_height')
						)
					);
				}
				
				$details = array(
					'tabs' => ($this->setting('navigation_style') == 'thumbnail_navigator'),
					'mobile_option' => $this->setting('mobile_options'),
					'slides' => $this->getSlideDetails('admin'),
					'dataset_fields' => listCustomFields('users', false, 'boolean_and_groups_only', false),
					'recommededSize' => true,
					'recommededSizeMessage' => $recommededSizeMessage
				);
				header('Content-Type: text/javascript; charset=UTF-8');
				echo json_encode($details);
				break;
				
			case "file_upload":
				exitIfUploadError();
				putUploadFileIntoCacheDir($_FILES['Filedata']['name'], $_FILES['Filedata']['tmp_name'], request('_html5_backwards_compatibility_hack'));
				exit;
				
			case "add_slides_from_organizer": 
				$keys = explode(',', get("ids"));
				$data = array();
				foreach($keys as $key) {
					$data[] = $this->getNewImageDetails($key);
				}
				header('Content-Type: text/javascript; charset=UTF-8');
				echo json_encode($data);
				break;
				
			case "change_image_from_organizer": 
				header('Content-Type: text/javascript; charset=UTF-8');
				echo json_encode($this->getNewImageDetails(get("new_image_id")));
				break;
				
			case "save_slides":
				
				$slides = json_decode(post("slides"), true);
				
				
				$ordinals = explode(',', post("ordinals"));
				$errors = array();
				// Check for errors
				foreach ($slides as $key => $value) {
					// Validation for slides
					$index = array_search($key, $ordinals);
					switch ($value['slide_visibility']) {
						case 'call_static_method':
							if (!$value['plugin_class']) {
								$errors[$index][] = adminPhrase('Please enter the Class Name of a Plugin.');
							} elseif (!inc($value['plugin_class'])) {
								$errors[$index][] = adminPhrase('Please enter the Class Name of a Plugin that you have running on this site.');
							} elseif ($value['method_name'] 
								&& !method_exists(
									$value['plugin_class'],
									$value['method_name'])
							) {
								$errors[$index][] = adminPhrase('Please enter the name of an existing Static Method.');
							}
							if (!$value['method_name']) {
								$errors[$index][] = adminPhrase('Please enter the name of a Static Method.');
							}
							break;
						case 'logged_in_with_field':
						case 'logged_in_without_field':
							if (!$value['field_id']) {
								$errors[$index][] = adminPhrase('Please choose a dataset field.');
							}
							break;
					}
					switch($value['target_loc']) {
						case 'internal':
							if (empty($value['content_item_tag_id'])) {
								$errors[$index][] = adminPhrase('Please select a content item.');
							}
							break;
						case 'external':
							if (empty($value['external_link'])) {
								$errors[$index][] = adminPhrase('Please enter a URL.');
							}
							break;
					}
				}
				
				// Save a new slide if one doesn't exist otherwise update the existing one.
				if (empty($errors)) {
					
					// Delete currently saved slides if not in save data.
					$ids = getRowsArray(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', array('image_id'), array('instance_id' => $this->instanceId));
					foreach ($ids as $key => $slideDetails) {
						if (array_search($key, $ordinals) === false) {
							deleteRow(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', array("id" => $key));
							if (!checkRowExists(ZENARIO_SLIDESHOW_2_PREFIX . 'slides', array('instance_id' => $this->instanceId, 'image_id' => $slideDetails['image_id']))) {
								deleteRow('inline_images', 
									array(
										'image_id' => $slideDetails['image_id'],
										'foreign_key_to' => 'library_plugin', 
										'foreign_key_id' => $this->instanceId
									)
								);
							}
						}
					}
					
					foreach ($slides as $key => $value) {
						if (!in_array($value['slide_visibility'], array('logged_in_with_field', 'logged_in_without_field'))) {
							$value['field_id'] = 0;
						}
						
						$value['dest_url'] = '';
						switch ($value["target_loc"]) {
							case 'internal':
								$value['dest_url'] = $value['content_item_tag_id'];
								break;
							case 'external':
								$value['dest_url'] = $value['external_link'];
								break;
							case 'none':
								$value["slide_more_link_text"] = '';
								break;
						}
						
						if (checkRowExists(ZENARIO_SLIDESHOW_2_PREFIX. "slides", array("id" => $key))) {
							updateRow(ZENARIO_SLIDESHOW_2_PREFIX . "slides",
								array(
									"ordinal" => array_search($key, $ordinals),
									"mobile_slide_extra_html" => $value["mobile_slide_extra_html"],
									"mobile_slide_title" => $value["mobile_slide_title"],
									"mobile_tab_name" => $value["mobile_tab_name"],
									"mobile_overwrite_alt_tag" => $value["mobile_overwrite_alt_tag"],
									"rollover_overwrite_alt_tag" => $value["rollover_overwrite_alt_tag"],
									"overwrite_alt_tag" => $value["overwrite_alt_tag"],
									"tab_name" => $value["tab_name"],
									"slide_title" => $value["slide_title"],
									"slide_extra_html" => $value["slide_extra_html"],
									"slide_more_link_text" => $value["slide_more_link_text"],
									"open_in_new_window" => $value["open_in_new_window"],
									"target_loc" => $value["target_loc"],
									"dest_url" => $value["dest_url"],
									"slide_visibility" => $value["slide_visibility"],
									"plugin_class" => $value["plugin_class"],
									"method_name" => $value["method_name"],
									"param_1" => $value["param_1"],
									"param_2" => $value["param_2"],
									'field_id' => (int)$value['field_id'],
									"link_to_translation_chain" => $value["link_to_translation_chain"],
									"transition_code" => isset($value["transition_code"]) ? $value["transition_code"] : '',
									"use_transition_code" => $value["use_transition_code"],
									"hidden" => $value["hidden"]),
								array("id" => $key));
								
							$this->saveImageForSlide($key, $value, "image_id");
							$this->saveImageForSlide($key, $value, "rollover_image_id");
							$this->saveImageForSlide($key, $value, "mobile_image_id");
								
						} else {
							$newKey = insertRow(ZENARIO_SLIDESHOW_2_PREFIX. "slides",
								array(
									"image_id" => 0,
									"ordinal" => array_search($key, $ordinals),
									"instance_id" => $this->instanceId,
									"mobile_slide_extra_html" => $value["mobile_slide_extra_html"],
									"mobile_slide_title" => $value["mobile_slide_title"],
									"mobile_tab_name" => $value["mobile_tab_name"],
									"mobile_overwrite_alt_tag" => $value["mobile_overwrite_alt_tag"],
									"rollover_overwrite_alt_tag" => $value["rollover_overwrite_alt_tag"],
									"overwrite_alt_tag" => $value["overwrite_alt_tag"],
									"tab_name" => $value["tab_name"],
									"slide_title" => $value["slide_title"],
									"slide_extra_html" => $value["slide_extra_html"],
									"slide_more_link_text" => $value["slide_more_link_text"],
									"open_in_new_window" => $value["open_in_new_window"],
									"target_loc" => $value["target_loc"],
									"dest_url" => $value["dest_url"],
									"slide_visibility" => $value["slide_visibility"],
									"plugin_class" => $value["plugin_class"],
									"method_name" => $value["method_name"],
									"param_1" => $value["param_1"],
									"param_2" => $value["param_2"],
									'field_id' => (int)$value['field_id'],
									"link_to_translation_chain" => $value["link_to_translation_chain"],
									"transition_code" => $value["transition_code"],
									"use_transition_code" => $value["use_transition_code"],
									"hidden" => $value["hidden"]));
							
							$this->saveImageForSlide($newKey, $value, "image_id");
							$this->saveImageForSlide($newKey, $value, "rollover_image_id");
							$this->saveImageForSlide($newKey, $value, "mobile_image_id");
						}
					}
				}
				header('Content-Type: text/javascript; charset=UTF-8');
				//echo '<pre>';var_dump($errors);exit;
				echo json_encode($errors);
				break;
				
		}
	}
	
	public function saveImageForSlide($key, $value, $image_id) {
		
		if (isset($value[$image_id]) && $value[$image_id] !== NULL) {
			
			switch($image_id) {
				case "rollover_image_id":
					$prefix = "r_";
					break;
				case "mobile_image_id":
					$prefix = "m_";
					break;
				default:
					$prefix = "";
					break;
			}
			
			if ($value[$image_id] == 0) {
				// new uploaded image
				$path = getPathOfUploadedFileInCacheDir($value[$prefix. "cache_id"]);
				if ($id = addFileToDatabase("image", $path)) {
					updateRow(ZENARIO_SLIDESHOW_2_PREFIX. "slides", 
						array($image_id => $id),
						array("id" => $key));
				}
			} else {
				if ($id = copyFileInDatabase("image", $value[$image_id], $value[$prefix. "filename"])) {
					// new image from organizer
					updateRow(ZENARIO_SLIDESHOW_2_PREFIX. "slides",
						array($image_id => $id),
						array("id" => $key));
				}
			}
			
			setRow('inline_images', 
				array('in_use' => 1), 
				array(
					'image_id' => $id, 
					'foreign_key_to' => 'library_plugin', 
					'foreign_key_id' => $this->instanceId
				)
			);
			
		} else {
			updateRow(ZENARIO_SLIDESHOW_2_PREFIX. "slides", 
				array($image_id => NULL),
				array("id" => $key));
		}
	}
	
	public function getSlideDetails($mode) {
		$adminMode = ($mode == 'admin');
		$mainMode = ($mode == 'main');
		$mobileMode = ($mode == 'mobile');
		
		$data = array();
		$sql = "
			SELECT
				s.mobile_overwrite_alt_tag, 
				s.mobile_tab_name, 
				s.mobile_slide_title, 
				s.mobile_slide_extra_html,
				
				s.image_id, 
				s.overwrite_alt_tag, 
				s.rollover_overwrite_alt_tag, 
				s.tab_name, 
				s.slide_title,
				s.slide_extra_html, 
				s.slide_more_link_text,
				f.alt_tag,
				f.filename, 
				f.width, 
				f.height,
				s.transition_code,
				s.use_transition_code,
				
				s.id, 
				s.ordinal,
				s.target_loc, 
				s.open_in_new_window,
				s.dest_url,
				s.slide_visibility, 
				s.link_to_translation_chain,
				s.plugin_class, 
				s.method_name, 
				s.param_1, 
				s.param_2,
				s.field_id,
				s.hidden
			FROM  ". DB_NAME_PREFIX. ZENARIO_SLIDESHOW_2_PREFIX. "slides AS s
			INNER JOIN ". DB_NAME_PREFIX. "files AS f
				ON s.image_id = f.id
			WHERE s.instance_id = ". (int) $this->instanceId. "
			ORDER BY s.ordinal";
		$result = sqlQuery($sql);
		while ($row1 = sqlFetchAssoc($result)) {
			$row2 = array();
			$row3 = array();
			
			$url = "";
			$row1['true_height'] = $row1["height"];
			$row1['true_width'] = $row1['width'];
			imageLink($row1["width"], $row1["height"], $url, $row1["image_id"], $this->setting("banner_width"), $this->setting("banner_height"), $this->setting('banner_canvas'));
			$row1['image_src'] = $url;
			
			// Get rollover image details
			$sql2 = "
				SELECT 
					count(s.rollover_image_id), 
					s.rollover_image_id, 
					f.height AS r_height, 
					f.width AS r_width, 
					f.filename AS r_filename,
					f.alt_tag AS r_alt_tag 
				FROM ". DB_NAME_PREFIX. ZENARIO_SLIDESHOW_2_PREFIX. "slides AS s
				INNER JOIN ". DB_NAME_PREFIX. "files AS f
					ON s.rollover_image_id = f.id
				WHERE s.instance_id = ". (int)$this->instanceId. "
					AND s.id = ". (int) $row1['id'];
			$result2 = sqlQuery($sql2);
			$row2 = sqlFetchAssoc($result2);
			
			$url2 = "";
			$row2['true_r_height'] = $row2['r_height'];
			$row2['true_r_width'] = $row2['r_width'];
			imageLink($row2["r_width"], $row2["r_height"], $url2, $row2["rollover_image_id"], $this->setting("banner_width"), $this->setting("banner_height"), $this->setting('banner_canvas'));
			$row2['rollover_image_src'] = $url2;

			// Get mobile image details
			$sql3 = "
				SELECT 
					count(s.mobile_image_id), 
					s.mobile_image_id, 
					f.height AS m_height, 
					f.width AS m_width, 
					f.filename AS m_filename,
					f.alt_tag AS m_alt_tag 
				FROM ". DB_NAME_PREFIX. ZENARIO_SLIDESHOW_2_PREFIX. "slides AS s
				INNER JOIN ". DB_NAME_PREFIX. "files AS f
					ON s.mobile_image_id = f.id
				WHERE s.instance_id = ". (int) $this->instanceId. "
					AND s.id = ". (int) $row1['id'];
			$result3 = sqlQuery($sql3);
			$row3 = sqlFetchAssoc($result3);
			$url3 = "";
			$row3['true_m_width'] = $row3['m_width'];
			$row3['true_m_height'] = $row3['m_height'];
			
			if ($this->setting('mobile_options') == 'seperate_fixed') {
				imageLink($row3["m_width"], $row3["m_height"], $url3, $row3["mobile_image_id"], $this->setting('mobile_width'), $this->setting('mobile_height'), $this->setting('mobile_canvas'));
			} elseif ($this->setting('mobile_options') == 'desktop_fixed') {
				imageLink($row3['m_width'], $row3['m_height'], $url3, $row1["image_id"], $this->setting('mobile_width'), $this->setting('mobile_height'), $this->setting('mobile_canvas'));
			}
			
			$row3['mobile_image_src'] = $url3;
			if ($adminMode) {
				// Get main image thumbnails
				$url = "";
				$width = $row1["width"];
				$height = $row1["height"];
				
				imageLink($width, $height, $url, $row1["image_id"], 300, 150);
				$row1['image_src_thumbnail_1'] = $url;
				
				$url = "";
				$width = $row1["width"];
				$height = $row1["height"];
				
				imageLink($width, $height, $url, $row1["image_id"], 150, 150);
				$row1['image_src_thumbnail_2'] = $url;
				
				// Get rollover image thumbnails
				$url2 = "";
				$width = $row2["r_width"];
				$height = $row2["r_height"];
				
				imageLink($width, $height, $url2, $row2["rollover_image_id"], 300, 150);
				$row2['rollover_image_src_thumbnail_1'] = $url2;
				
				// Get mobile image thumbnails
				$url3 = "";
				$width = $row3["m_width"];
				$height = $row3["m_height"];
				imageLink($width, $height, $url3, $row3["mobile_image_id"], 300, 150);
				$row3['mobile_image_src_thumbnail_1'] = $url3;
				
				
				$row1['content_item_link'] = $row1['content_item_tag_id'] = $row1['external_link'] = '';
				if ($row1['target_loc'] == 'internal' && $row1['dest_url']) {
					$row1['content_item_link'] = formatTagFromTagId($row1['dest_url']);
					$row1['content_item_tag_id'] = $row1['dest_url'];
				} elseif ($row1['target_loc'] == 'external' && $row1['dest_url']) {
					$row1['external_link'] = $row1['dest_url'];
				}
				
			}
			$row = array_merge($row1, $row2, $row3);
			//var_dump($row['m_width']);
			$id = $row['id'];
			if ($mainMode || $mobileMode) {
				$id = $row['ordinal'];
			}
			$data[$id] = $row;
			
			
		}
		return $data;
	}
	
	public function getNewImageDetails($id) {
		$sql = "
			SELECT id AS image_id, filename, alt_tag,  width, width AS true_width, height, height AS true_height
			FROM ". DB_NAME_PREFIX. "files
			WHERE id = ". (int) $id;
		$result = sqlQuery($sql);
		$row = sqlFetchAssoc($result);
		$url = "";
		$width = $row["width"];
		$height = $row["height"];
		//imageLink($width, $height, $url, $row["image_id"], $row["width"], $row["height"]);
		//$row["image_src"] = $url;
		
		imageLink($width, $height, $url, $row["image_id"], 300, 150);
		$row["image_src_thumbnail_1"] = $url;
		
		imageLink($width, $height, $url, $row["image_id"], 150, 150);
		$row["image_src_thumbnail_2"] = $url;
		return $row;
	}
	
	public function fillAdminSlotControls(&$controls) {
		if (isset($controls['actions']['settings'])) {
			$controls['actions']['settings']['label'] = adminPhrase('Slideshow properties');
			$controls['actions']['slideshow_settings'] = array(
				'ord' => 1.1,
				'label' => adminPhrase('Choose slideshow images'),
				'page_modes' => $controls['actions']['settings']['page_modes'],
				'onclick' => 'zenario_slideshow_2.openImageManager(
					this, 
					slotName, 
					\''. jsEscape($this->pluginAJAXLink()). '\'
				);'
			);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch($path){
			case 'plugin_settings':
			
				$fields['first_tab/banner_width']['hidden'] = 
					!in($values['first_tab/banner_canvas'], 'fixed_width', 'fixed_width_and_height', 'resize_and_crop');
				$fields['first_tab/banner_height']['hidden'] =
					!in($values['first_tab/banner_canvas'], 'fixed_height', 'fixed_width_and_height', 'resize_and_crop');
				
				$fields['mobile/mobile_canvas']['hidden'] = 
					!in($values['mobile/mobile_options'], 'desktop_fixed', 'seperate_fixed');
				$fields['mobile/mobile_width']['hidden'] = 
					!in($values['mobile/mobile_canvas'], 'fixed_width', 'fixed_width_and_height', 'resize_and_crop') ||
					$fields['mobile/mobile_canvas']['hidden'];
				$fields['mobile/mobile_height']['hidden'] =
					!in($values['mobile/mobile_canvas'], 'fixed_height', 'fixed_width_and_height', 'resize_and_crop') ||
					$fields['mobile/mobile_canvas']['hidden'];
				
				$fields['mobile/desktop_resize_greater_than_image']['hidden'] = 
					$values['mobile/mobile_options'] != 'desktop_resize';
				
				break;
		}
	}
	
	public static function eventPluginInstanceDuplicated($oldInstanceId, $newInstanceId) {
		$oldSlideshowSettings = getRowsArray(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', true, array('instance_id' => $oldInstanceId));
		foreach ($oldSlideshowSettings as $slideId => $slideDetails) {
			unset($slideDetails['id']);
			$slideDetails['instance_id'] = $newInstanceId;
			insertRow(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', $slideDetails);
		}
	}
	
	public static function eventPluginInstanceDeleted($instanceId) {
		// Delete slides
		deleteRow(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', array('instance_id' => $instanceId));
	}
	
	public static function removeContentItemFromSlideLinks($cID, $cType) {
		$tagID = formatTag($cID, $cType, false);
		$result = getRows(
			ZENARIO_SLIDESHOW_2_PREFIX . 'slides', 
			array('id', 'target_loc', 'dest_url'), 
			array('target_loc' => 'internal', 'dest_url' => $tagID)
		);
		while ($row = sqlFetchAssoc($result)) {
			updateRow(
				ZENARIO_SLIDESHOW_2_PREFIX . 'slides', 
				array(
					'target_loc' => 'none', 
					'dest_url' => null, 
					'link_to_translation_chain' => 0,
					'open_in_new_window' => 0
				), 
				array('id' => $row['id'])
			);
		}
	}
	
	public static function eventContentTrashed($cID, $cType) {
		// Remove any internal links from slides to this content item
		self::removeContentItemFromSlideLinks($cID, $cType);
	}
	
	public static function eventContentDeleted($cID, $cType, $cVersion) {
		// Remove any internal links from slides to this content item if the content item has been fully deleted
		$contentItem = getRow('content_items', array('status'), array('id' => $cID, 'type' => $cType));
		if ($contentItem === false || $contentItem['status'] === 'deleted') {
			self::removeContentItemFromSlideLinks($cID, $cType);
		}
	}
	
}