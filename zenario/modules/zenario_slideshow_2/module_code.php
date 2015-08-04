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

class zenario_slideshow_2 extends module_base_class {
	
	var $errors = array();
	
	public function fillAdminSlotControls(&$controls) {
		
		if (isset($controls['actions']['settings'])) {
			$controls['actions']['slideshow_settings'] = array(
				'ord' => 1.1,
				'label' => adminPhrase('Edit images'),
				'page_modes' => $controls['actions']['settings']['page_modes'],
				'onclick' => 'zenario_slideshow_2.openImageManager(
					this, 
					slotName, 
					\''. jsEscape($this->pluginAJAXLink()). '\'
				);'
			);
		}
	}
	
	var $slideData = array();
	
	public function init() {
		if ($userId = userId()) {
			$userDetails = getUserDetails($userId);
		}
		if ($this->slideData["slides"] = $this->getSlideDetails('main')) {
			$slideCaptionTransitions = array();
			$mobileImages = false;
			$missingMobileImage = false;
			$mobileImageDetails = array();
			$maxWidth = 0;
			$maxHeight = 0;
			$maxMobileWidth = 0;
			$maxMobileHeight = 0;
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
								$fieldValue = getDatasetFieldValue($userId, $slide['field_id']);
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
				}
				
				if (($slide['target_loc'] == 'internal') && $slide['dest_url']) {
					$cID = $cType = false;
					getCIDAndCTypeFromTagId($cID, $cType, $slide['dest_url']);
					if ($slide['link_to_translation_chain']) {
						langEquivalentItem($cID, $cType);
					}
					$slide['dest_url'] = linkToItem($cID, $cType);
				}
				if ($slide['use_title_transition']) {
					$slideCaptionTransitions[$slide['id']]['title'] = $slide['title_transition'];
				}
				if ($slide['use_extra_html_transition']) {
					$slideCaptionTransitions[$slide['id']]['extra_html'] = $slide['extra_html_transition'];
				}
				// Get slides max height and width
				if ($slide['mobile_image_id']) {
					$mobileImages = true;
					if ($slide['m_height'] > $maxMobileHeight) {
						$maxMobileHeight = $slide['m_height'];
					}
					if ($slide['m_width'] > $maxMobileWidth) {
						$maxMobileWidth = $slide['m_width'];
					}
				} else {
					$missingMobileImage = true;
				}
				
				if (($height = max(array($slide['height'], $slide['r_height']))) > $maxHeight) {
					$maxHeight = $height;
				}
				if (($width = max(array($slide['width'], $slide['r_width']))) > $maxWidth) {
					$maxWidth = $width;
				}
			}
			if ($mobileImages && $missingMobileImage) {
				foreach ($this->slideData["slides"] as $index => &$slide) {
					if (!$slide['mobile_image_id']) {
						$width = $height = $url = false;
						imageLink($width, $height, $url, $slide['image_id'], $maxMobileWidth, $maxMobileHeight);
						$slide['mobile_image_src'] = $url;
					}
				}
			}
			$this->slideData["maxHeight"] = $maxHeight;
			$this->slideData["maxWidth"] = $maxWidth;
			$this->slideData["navigation"] = $this->setting("navigation_style");
			$this->slideData["arrow_buttons"] = $this->setting("arrow_buttons");
			$this->slideData["enable_swipe"] = $this->setting("enable_swipe");
			$this->callScript(
				"zenario_slideshow_2", 
				"initiateSlideshow",
				$this->pluginAJAXLink(),
				$this->slotName,
				setting('image_mobile_resize_point'),
				$this->setting("fx"),
				$maxWidth,
				$maxHeight,
				$maxMobileWidth,
				$maxMobileHeight,
				$this->setting("hover_to_pause"),
				$this->setting("enable_swipe"),
				$this->setting("auto_play"),
				$this->setting("slide_duration"),
				$this->setting("arrow_buttons"),
				$this->setting("navigation_style"),
				$slideCaptionTransitions,
				$this->instanceId,
				$this->slideData["slides"],
				$mobileImages);
			$this->slideData['adminId'] = adminId();
		}
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->slideData);
	}
	
	public function pluginAJAX() {
		
		switch(request('mode')) {
			case 'get_details':
				header('Content-Type: text/javascript; charset=UTF-8');
				$details = array(
					'tabs' => ($this->setting('navigation_style') == 'thumbnail_navigator'),
					'slides' => $this->getSlideDetails('admin'),
					'dataset_fields' => listCustomFields('users', false, 'boolean_and_groups_only', false));
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
				$data = $this->getNewImageDetails(get("new_image_id"));
				header('Content-Type: text/javascript; charset=UTF-8');
				echo json_encode($data);
				break;
				
			case "save_slides":
				
				$slides = json_decode(post("slides"), true);
				$ordinals = explode(',', post("ordinals"));
				
				// Check for errors
				foreach ($slides as $key => $value) {
					// Validation for slides
					$index = array_search($key, $ordinals);
					switch ($value['slide_visibility']) {
						case 'call_static_method':
							if (!$value['plugin_class']) {
								$this->errors[$index][] = adminPhrase('Please enter the Class Name of a Plugin.');
							} elseif (!inc($value['plugin_class'])) {
								$this->errors[$index][] = adminPhrase('Please enter the Class Name of a Plugin that you have running on this site.');
							} elseif ($value['method_name'] 
								&& !method_exists(
									$value['plugin_class'],
									$value['method_name'])
							) {
								$this->errors[$index][] = adminPhrase('Please enter the name of an existing Static Method.');
							}
							if (!$value['method_name']) {
								$this->errors[$index][] = adminPhrase('Please enter the name of a Static Method.');
							}
							break;
					}
					switch($value['target_loc']) {
						case 'internal':
							if (empty($value['dest_url'])) {
								$this->errors[$index][] = adminPhrase('Please select a content item.');
							}
							break;
						case 'external':
							if (empty($value['dest_url'])) {
								$this->errors[$index][] = adminPhrase('Please enter a URL.');
							}
							break;
					}
				}
				
				// Save a new slide if one doesn't exist otherwise update the existing one.
				if (empty($this->errors)) {
					
					// Delete currently saved slides if not in save data.
					$ids = getRowsArray(ZENARIO_SLIDESHOW_2_PREFIX. "slides", "id", array('instance_id' => $this->instanceId));
					foreach ($ids as $key => $id) {
						if (array_search($id, $ordinals) === false) {
							deleteRow(ZENARIO_SLIDESHOW_2_PREFIX. "slides", array("id" => $id));
						}
					}
					
					foreach ($slides as $key => $value) {
						if (!in_array($value['slide_visibility'], array('logged_in_with_field', 'logged_in_without_field'))) {
							$value['field_id'] = 0;
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
									"use_title_transition" => $value["use_title_transition"],
									"use_extra_html_transition" => $value["use_extra_html_transition"],
									"title_transition" => $value["title_transition"],
									"extra_html_transition" => $value["extra_html_transition"],
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
									"use_title_transition" => $value["use_title_transition"],
									"use_extra_html_transition" => $value["use_extra_html_transition"],
									"title_transition" => $value['title_transition'],
									"extra_html_transition" => $value["extra_html_transition"],
									"hidden" => $value["hidden"]));
							
							$this->saveImageForSlide($newKey, $value, "image_id");
							$this->saveImageForSlide($newKey, $value, "rollover_image_id");
							$this->saveImageForSlide($newKey, $value, "mobile_image_id");
						}
					}
					
					// Delete all unused images from slideshow pot
					$fileIds = getRowsArray("files", "id", array("usage" => "slideshow"));
					foreach($fileIds as $fileId) {
						if (!checkRowExists(ZENARIO_SLIDESHOW_2_PREFIX. "slides", array("image_id" => $fileId))
							&& !checkRowExists(ZENARIO_SLIDESHOW_2_PREFIX. "slides", array("rollover_image_id" => $fileId))
							&& !checkRowExists(ZENARIO_SLIDESHOW_2_PREFIX. "slides", array("mobile_image_id" => $fileId))) {
							
							deleteRow("files", array("id" => $fileId));
						}
					}
				}
				$data = $this->errors;
				header('Content-Type: text/javascript; charset=UTF-8');
				echo json_encode($data);
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
				if ($id = addFileToDatabase("slideshow", $path)) {
					updateRow(ZENARIO_SLIDESHOW_2_PREFIX. "slides", 
						array($image_id => $id),
						array("id" => $key));
				}
			} else {
				if ($id = copyFileInDatabase("slideshow", $value[$image_id], $value[$prefix. "filename"])) {
					// new image from organizer
					updateRow(ZENARIO_SLIDESHOW_2_PREFIX. "slides",
						array($image_id => $id),
						array("id" => $key));
				}
			}
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
		$sql = 'SELECT';
		// Get mobile columns
			$sql .= "
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
				f.filename, 
				f.width, 
				f.height,
				s.use_title_transition, 
				s.use_extra_html_transition,
				s.title_transition,
				s.extra_html_transition,
				
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
			imageLink($row1["width"], $row1["height"], $url, $row1["image_id"], $this->setting("banner_width"), $this->setting("banner_height"), $this->setting('banner_canvas'), $this->setting("offset"));
			$row1['image_src'] = $url;
			
			// Get rollover image details
			$sql3 = "
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
			$result3 = sqlQuery($sql3);
			$row3 = sqlFetchAssoc($result3);
			
			$url3 = "";
			$row3['true_r_height'] = $row3['r_height'];
			$row3['true_r_width'] = $row3['r_width'];
			imageLink($row3["r_width"], $row3["r_height"], $url3, $row3["rollover_image_id"], $this->setting("banner_width"), $this->setting("banner_height"), $this->setting('banner_canvas'), $this->setting("offset"));
			$row3['rollover_image_src'] = $url3;

			// Get mobile image details
			$sql2 = "
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
			$result2 = sqlQuery($sql2);
			$row2 = sqlFetchAssoc($result2);
			
			$url2 = "";
			$row2['true_m_height'] = $row2['m_height'];
			$row2['true_m_width'] = $row2['m_width'];
			imageLink($row2["m_width"], $row2["m_height"], $url2, $row2["mobile_image_id"]);
			$row2['mobile_image_src'] = $url2;
			
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
				$url3 = "";
				$width = $row3["r_width"];
				$height = $row3["r_height"];
				
				imageLink($width, $height, $url3, $row3["rollover_image_id"], 300, 150);
				$row3['rollover_image_src_thumbnail_1'] = $url3;
				
				// Get mobile image thumbnails
				$url2 = "";
				$width = $row2["m_width"];
				$height = $row2["m_height"];
				imageLink($width, $height, $url2, $row2["mobile_image_id"], 300, 150);
				$row2['mobile_image_src_thumbnail_1'] = $url2;
				
				if ($row1['target_loc'] == 'internal' && $row1['dest_url']) {
					$row1['content_item_link'] = formatTagFromTagId($row1['dest_url']);
				}
				
			}
			$row = array_merge($row1, $row2, $row3);
			
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
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch($path){
			case 'plugin_settings':
				$box['tabs']['first_tab']['fields']['banner_width']['hidden'] = 
					!(in($values['first_tab/banner_canvas'], 'fixed_width', 'fixed_width_and_height', 'resize_and_crop'));
				 
				$box['tabs']['first_tab']['fields']['banner_height']['hidden'] =
					!(in($values['first_tab/banner_canvas'], 'fixed_height', 'fixed_width_and_height', 'resize_and_crop'));
					
				$box['tabs']['first_tab']['fields']['offset']['hidden'] = 
					!($values['first_tab/banner_canvas'] == 'resize_and_crop');
				
				$box['tabs']['first_tab']['fields']['slides_at_a_time']['hidden'] = 
					!($values['first_tab/fx'] == 'carousel');
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
		deleteRow(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', array('instance_id' => $instanceId));
	}
	
}