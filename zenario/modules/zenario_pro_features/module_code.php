<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

class zenario_pro_features extends zenario_common_features {
	
	
	private static $atts;
	
	
	//The Module Methods from the zenario_common_features class need to be overridden even if there is not extra functionality
	//in this case because we are inheriting from zenario_common_features instead of zenario_base_module
	//and because of that these functions if not declared here will end up calling zenario_common_features twice.
	
	public function showFile() {
		
		//...your PHP code...//
	}

	public function showImage() {
		
		//...your PHP code...//
	}
	
	
	public function fillAllAdminSlotControls(
		&$controls,
		$cID, $cType, $cVersion,
		$slotName, $containerId,
		$level, $moduleId, $instanceId, $isVersionControlled
	) {
		//...your PHP code...//
	}
	
	public function fillAdminToolbar(&$adminToolbar, $cID, $cType, $cVersion) {
		$languagesEnabledOnSite = ze\lang::getLanguages();
		$numLanguageEnabled = count($languagesEnabledOnSite);

		if ($numLanguageEnabled == 1) {
			unset($adminToolbar['sections']['edit']['buttons']['translate']);
		}
	}
	
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(static::class, false, $path)) {
			return $c->preFillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(static::class, false, $path)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(static::class, false, $path)) {
			return $c->organizerPanelDownload($path, $ids, $refinerName, $refinerId);
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		switch ($path) {
			case 'zenario_pro_features__google_translate':
		
				//Set up the primary key from the requests given
				if ($box['key']['id'] && !$box['key']['cID']) {
					ze\content::getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);
				}
		
				if (!ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
					echo ze\admin::phrase("This content item is locked by another administrator, or you don't have the permissions to modify it.");
					exit;
				}
		
				ze\contentAdm::getLanguageSelectListOptions($box['tabs']['translate']['fields']['lang_from']);
				$box['tabs']['translate']['fields']['lang_to']['value'] = ze\content::langId($box['key']['cID'], $box['key']['cType']);
				unset($box['tabs']['translate']['fields']['lang_from']['values'][$box['tabs']['translate']['fields']['lang_to']['value']]);
				
				break;
			
			default:
				if ($c = $this->runSubClass(static::class, false, $path)) {
					return $c->fillAdminBox($path, $settingGroup, $box, $fields, $values);
				}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_pro_features__google_translate':
				if (!ze::setting('google_translate_api_key')) {
					$box['tabs']['translate']['errors'][] = ze\admin::phrase('Please enter your Google Translate API Key in Configuration->Site Settings, API Keys interface.');
					$box['tabs']['translate']['fields']['lang_from']['readonly'] = true;
					$box['tabs']['translate']['fields']['lang_to']['readonly'] = true;
				}
				
				break;
			
			default:
				if ($c = $this->runSubClass(static::class, false, $path)) {
					return $c->formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
				}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_pro_features__google_translate':
				if (!ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
					$box['tabs']['import']['errors'][] = ze\admin::phrase("This content item is locked by another administrator, or you don't have the permissions to modify it.");
				}
				
				break;
			
			default:
				if ($c = $this->runSubClass(static::class, false, $path)) {
					return $c->validateAdminBox($path, $settingGroup, $box, $fields, $values, $changes, $saving);
				}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_pro_features__google_translate':
				ze\priv::exitIfNot('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType']);
				
				$post = ['q' => ''];
				$post['format'] = 'html';
				$post['key'] = ze::setting('google_translate_api_key');
				
				if ($values['translate/lang_from'] && $values['translate/lang_from'] != 0) {
					$post['source'] = $values['translate/lang_from'];
				}
				
				$post['target'] = zenario_pro_features::googleLanguageCode($values['translate/lang_to']);
				
				if ($post['key'] && $post['target']) {
					if (zenario_pro_features::createExportFile($post['q'], false, false, $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'])) {
						
						$url = 'https://www.googleapis.com/language/translate/v2';
						$options = [
							CURLOPT_HTTPHEADER => ['X-HTTP-Method-Override: GET'],
							CURLOPT_SSL_VERIFYPEER => false];
						
						if ($responce = ze\curl::fetch($url, $post, $options)) {
							if ($json = json_decode($responce, true)) {
								if (isset($json['data']['translations'][0]['translatedText'])) {
									
									//Attempt to add a work-around for Google stripping off all of the white-space
									$json['data']['translations'][0]['translatedText'] = preg_replace('@><([hp]\d?\W)@', ">\n\t\t\t\t<\\1", $json['data']['translations'][0]['translatedText']);
									
									$targetCID = $targetCType = $error = false;
									if (zenario_pro_features::importContentItem($json['data']['translations'][0]['translatedText'], false, false, $targetCID, $targetCType, $error, $box['key']['cID'], $box['key']['cType'])) {
										//Completed!
									} else {
										echo ze\admin::phrase('A corrupted translation was returned from Google Translate.');
										exit;
									}
								} else {
									echo ze\admin::phrase('No translation was returned.');
									exit;
								}
							} else {
								echo $responce;
								exit;
							}
						} else {
							//Test to see if Google Translate is working
							$test = [];
							$test['q'] = 'Hello World';
							$test['source'] = 'en';
							$test['target'] = 'de';
							$test['key'] = $post['key'];
							
							if (ze\curl::fetch($url, $test, $options)) {
								if ($values['translate/lang_from'] && $values['translate/lang_from'] != 0) {
									echo ze\admin::phrase('Google Translate could not translate this text, and returned an unknown error.');
								} else {
									echo ze\admin::phrase('Google Translate could not translate this text, and returned an error. You may wish to try again, without using the Auto-detect Language option.');
								}
							} else {
								echo ze\admin::phrase('Could not launch a CURL request to https://www.googleapis.com. Please ensure that your API key is valid, that CURL is enabled on your server, and that there are no network issues between your server and the googleapis.com site.');
							}
							
							exit;
						}
					} else {
						echo ze\admin::phrase('Could not create an export file for this Content Item.');
						exit;
					}
				}
				
				break;
			
			default:
				if ($c = $this->runSubClass(static::class, false, $path)) {
					return $c->saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
				}
		}
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		switch ($path) {
			default:
				if ($c = $this->runSubClass(static::class, false, $path)) {
					return $c->adminBoxDownload($path, $settingGroup, $box, $fields, $values, $changes);
				}
		}
	}
	
	
	
	
	
	public function pagSmart($currentPage, &$pages, &$html) {
		$this->pageNumbers($currentPage, $pages, $html, 'Smart', $showNextPrev = false, $showFirstLast = false, $alwaysShowNextPrev = false);
	}
	
	public function pagSmartWithNPIfNeeded($currentPage, &$pages, &$html, $links = [], $extraAttributes = []) {
		$this->pageNumbers($currentPage, $pages, $html, 'Smart', $showNextPrev = true, $showFirstLast = false, $alwaysShowNextPrev = false, $links, $extraAttributes);
	}
	
	public function pagSmartWithNP($currentPage, &$pages, &$html, $links = [], $extraAttributes = []) {
		$this->pageNumbers($currentPage, $pages, $html, 'Smart', $showNextPrev = true, $showFirstLast = false, $alwaysShowNextPrev = true, $links, $extraAttributes);
	}
	
	protected function smartPageNumbers($currentPos, $count, $showFirstLast, &$pagesPos, &$pages, &$html, $currentPage, $prevPage, $nextPage, $links = [], $extraAttributes = []) {
		//Have a set list of positions that will be displayed, if there
		$positions1 = [
				-999999,
				-100000, -70000, -40000, -20000,
				-10000, -7000, -4000, -2000,
				-1000, -700, -400, -200,
				-100, -70, -40, -20,
				-10, -7, -4, -2,
				-1, 0,
				1, 2, 4, 7,
				10, 20, 40, 70,
				100, 200, 400, 700,
				1000, 2000, 4000, 7000,
				10000, 20000, 40000, 70000,
				100000,
				999999
			];
		$positions2 = [];
		
		//Check if each is there, and include it if so
		foreach ($positions1 as $rel) {
			//Check if the set position is out of range, and replace it with the first/last page in range if needed
			$pos = $currentPos + $rel;
			if ($pos < 0) {
				if ($showFirstLast) {
					continue;
				}
				$pos = 0;
			} elseif ($pos >= $count) {
				if ($showFirstLast) {
					continue;
				}
				$pos = $count-1;
			} else {
				//Otherwise if the numbers are in range then round numbers, depending on how far away they are from the current page
				foreach ([100000, 10000, 1000, 100, 10] as $round) {
					if ($rel < -$round || $round < $rel) {
						$pos = $pos - ($currentPos % $round) - 1;
						break;
					}
				}
				
				if ($pos < 0) {
					$pos = 0;
				} elseif ($pos >= $count) {
					$pos = $count-1;
				}
			}
			
			$positions2[$pos] = true;
		}
		
		foreach ($positions2 as $pos => $dummy) {
			$page = $pagesPos[$pos];
			$html .= $this->drawPageLink($page, $pages[$page], $page, $currentPage, $prevPage, $nextPage, $css = 'pag_page', $links, $extraAttributes);
		}
	}
	
	
	
	
	
	
	
	
	//
	//	Admin functions
	//
	
	
	
	
	function handleAJAX() {
		
		if (ze::post('getBottomLeftInfo')) {
			
			//Note: this AJAX request used to return info about optimisation settings in positions 0 and 1.
			//We're since removed this info, but I don't want to re-adjust the other indices,
			//so I'm leaving positions 0 and 1 empty for now.
			echo '~~';
			
			//Get the current server time
			if (ze\server::isWindows() || !ze\server::execEnabled()) {
				echo date('H~i~s');
		
			} else {
				echo trim(exec('date +"%H~%M~%S"'));
		
				//Check if the scheduled task manager is running
				if (!ze\module::inc('zenario_scheduled_task_manager')) {
					echo '~~', ze\admin::phrase('The Scheduled Tasks Manager module is not running.');
					return;
		
				} elseif (!zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = false)) {
					echo '~jobs_not_running~', ze\admin::phrase('The Scheduled Tasks Manager module is running, but the master switch is Off and so tasks are not being run.');
		
				} elseif (!zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = true)) {
					echo '~jobs_not_running~', ze\admin::phrase('The Scheduled Tasks Manager module is running, but not correctly configured in the crontab.');
		
				} else {
					echo '~jobs_running~', ze\admin::phrase('The Scheduled Tasks Manager is running');
				}
		
				if (ze\priv::check('_PRIV_VIEW_SCHEDULED_TASK')) {
					echo '~zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks';
				}
			}
		
		}
		
		zenario_common_features::handleAJAX();
	}
	
	
	
	
	
	var $categoryHierarchyOutput = "";
	var $categoryChildren = [];
	var $categoryAncestors = [];
	
	
	public function categoryHasChild ($id) {
		$sql = "SELECT id
				FROM " . DB_PREFIX . "categories
				WHERE parent_id = " . (int) $id;
	
		$result = ze\sql::select($sql);
	
		if (ze\sql::numRows($result)>0) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getCategoryChildren ($id, $recurseCount = 0) {
		$recurseCount++;
	
		$sql = "SELECT id
				FROM " . DB_PREFIX . "categories
				WHERE parent_id = " . (int) $id;
	
		$result = ze\sql::select($sql);
	
		if (ze\sql::numRows($result)>0) {
			while ($row = ze\sql::fetchRow($result)) {
				$this->categoryChildren[] = $row[0];
	
				if ($recurseCount<=10) {
					$this->getCategoryChildren($row[0],$recurseCount);
				}
			}
		}
	}
	
	
	
	
	
	
	//
	//	Export/Import functions
	//
	
	
	public static function createExportFile(&$f, $isXML, $encodeHTMLAtt, $cID, $cType, $cVersion, $targetCID = false, $targetCType = false) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function googleLanguageCode($langId) {
		switch ($langId) {
			case 'zh-si':
				return 'zh-CN';
			case 'zh-tr':
				return 'zh-TW';
			default:
				return substr($langId, 0, 2);
		}
	}
	
	public static function encode($text) {
		$sql = "
			SELECT ENCODE('". ze\escape::sql($text). "', '". ze\escape::sql(ze::setting('site_id')). "');";
		$result = ze\sql::select($sql);
		$row = ze\sql::fetchRow($result);
		return base64_encode($row[0]);
	}
	
	public static function decode($code) {
		
		try {
			$code = base64_decode($code);
		} catch (Exception $e) {
			return false;
		}
		
		$sql = "
			SELECT DECODE('". ze\escape::sql($code). "', '". ze\escape::sql(ze::setting('site_id')). "');";
		$result = ze\sql::select($sql);
		$row = ze\sql::fetchRow($result);
		return $row[0];
	}
	
	protected static function openTagStart($isXML, &$f, $name, $ndLevel = false) {
		if ($isXML) {
			$f .= "\n\t". ($ndLevel? "\t" : ''). '<'. $name;
		} else {
			$f .= "\n\t\t". ($ndLevel? "\t" : ''). '<div id="zenario:'. $name. ',';
		}
		
		zenario_pro_features::$atts = '';
	}
	
	protected static function addAtt($isXML, &$f, $name, $value) {
		if ($isXML) {
			$f .= ' '. $name. '="'. ze\escape::xml($value). '"';
		} else {
			zenario_pro_features::$atts .=
				str_replace([',', ':'], ['&#44;', '&#58;'], $name).
				':'.
				str_replace([',', ':'], ['&#44;', '&#58;'], htmlspecialchars($value)).
				',';
		}
	}
	
	protected static function openTagEnd($isXML, $encodeHTMLAtt, &$f, $addTitle = false) {
		
		if (!$isXML) {
			if (!$encodeHTMLAtt) {
				$f .= zenario_pro_features::$atts;
			} else {
				$f .= zenario_pro_features::encode(zenario_pro_features::$atts);
			}
		}
		
		if ($addTitle !== false) {
			if ($isXML) {
				$f .= '>'. ze\escape::xml($addTitle);
			} else {
				$f .= '" title="'. ($addTitle ? htmlspecialchars($addTitle) : ''). '">';
			}
		} else {
			if ($isXML) {
				$f .= '>';
			} else {
				$f .= '">';
			}
		}
	}
	
	protected static function setTagContents($isXML, &$f, $value, $escapeHTML = false, $ndLevel = false) {
		if (empty($value)) {
			$f .= $value;
		
		} elseif ($isXML) {
			$f .= "\n\t\t". ($ndLevel? "\t" : ''). ze\escape::xml($value);
		
		} elseif ($escapeHTML) {
			$f .= "\n\t\t\t". ($ndLevel? "\t" : ''). htmlspecialchars($value);
		
		} else {
			$f .= "\n\t\t\t". ($ndLevel? "\t" : ''). $value;
		}
	}
	
	protected static function closeTag($isXML, &$f, $name, $ndLevel = null) {
		if ($ndLevel !== null) {
			if ($isXML) {
				$f .= "\n\t". ($ndLevel? "\t" : '');
			} else {
				$f .= "\n\t\t". ($ndLevel? "\t" : '');
			}
		}
		
		if ($isXML) {
			$f .= '</'. $name. '>';
		} else {
			$f .= '</div>';
		}
	}
	
	protected static function escape($isXML, $value) {
		if ($isXML) {
			return ze\escape::xml($value);
		} else {
			return htmlspecialchars($value);
		}
	}
	
	protected static function getValue(&$xml) {
		if (!$xml) {
			return false;
			
		} elseif ($xml->attributes()->value) {
			return (string) $xml->attributes()->value;
		
		} elseif ($xml->attributes()->title) {
			return (string) $xml->attributes()->title;
		
		} else {
			return trim((string) $xml);
		}
	}
	
	protected static function fixHTML($html) {
		return str_replace('<p></p>', '<p>&nbsp;</p>', $html);
	}
	
	protected static function HTMLToXML($html) {
		return ze\escape::xml(str_ireplace('&apos;', "'", html_entity_decode($html, ENT_QUOTES, 'UTF-8')));
	}
	
	protected static function getSlotsOnTemplate($layoutId) {
		$result = ze\row::query(
			'layout_slot_link',
			'slot_name',
			['layout_id' => $layoutId],
			['slot_name']);
		
		$slotsOnTemplate = [];
		while ($slotName = ze\sql::fetchAssoc($result)) {
			$slotsOnTemplate[] = $slotName['slot_name'];
		}
		
		return $slotsOnTemplate;
	}
	
	public static function importContentItem($input, $isXML, $onlyValidate, &$targetCID, &$targetCType, &$error, $cID = false, $cType = false) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}

	public static function trashAdminBoxPreviewOnKeyup($tagId) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	public static function eventContentDeleted($cID, $cType, $cVersion) {
		if (!ze\row::exists('content_item_versions', ['id' => $cID, 'type' => $cType])) {
			ze\row::delete('spare_aliases', ['content_id' => $cID, 'content_type' => $cType]);
		}
	}
	
	public static function eventContentTrashed($cID, $cType) {
		ze\row::delete('spare_aliases', ['content_id' => $cID, 'content_type' => $cType]);
	}
	
}

