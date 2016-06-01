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


class zenario_export_tools extends module_base_class {
	
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'zenario_export_tools__export':
				//Set up the primary key from the requests given
				if ($box['key']['id'] && !$box['key']['cID']) {
					getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);
				}
				
				if ($box['key']['cID'] && !$box['key']['cVersion']) {
					$box['key']['cVersion'] = getLatestVersion($box['key']['cID'], $box['key']['cType']);
				}
				
				break;
			
			
			case 'zenario_export_tools__import':
				//Set up the primary key from the requests given
				if ($box['key']['id'] && !$box['key']['cID']) {
					getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);
				}
				
				break;
			
			
			case 'zenario_export_tools__google_translate':
				//Set up the primary key from the requests given
				if ($box['key']['id'] && !$box['key']['cID']) {
					getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);
				}
				
				if ($box['key']['cID'] && !$box['key']['cVersion']) {
					$box['key']['cVersion'] = getLatestVersion($box['key']['cID'], $box['key']['cType']);
				}
				
				getLanguageSelectListOptions($box['tabs']['translate']['fields']['lang_from']);
				$box['tabs']['translate']['fields']['lang_to']['value'] = getContentLang($box['key']['cID'], $box['key']['cType']);
				unset($box['tabs']['translate']['fields']['lang_from']['values'][$box['tabs']['translate']['fields']['lang_to']['value']]);
				
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_export_tools__google_translate':
				if (!setting('google_translate_api_key')) {
					$box['tabs']['translate']['errors'][] = adminPhrase('Please enter your Google Translate API Key in the Site Settings.');
					$box['tabs']['translate']['fields']['lang_from']['read_only'] = true;
					$box['tabs']['translate']['fields']['lang_to']['read_only'] = true;
				}
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_export_tools__export':
				if (!checkPriv(false, $box['key']['cID'], $box['key']['cType'])) {
					$box['tabs']['export']['errors'][] = adminPhrase('This Content Item is checked out by another Administrator.');
				}
				
				break;
					
			
			case 'zenario_export_tools__import':
				if (!checkPriv(false, $box['key']['cID'], $box['key']['cType'])) {
					$box['tabs']['import']['errors'][] = adminPhrase('This Content Item is checked out by another Administrator.');
				}
				
				$box['tabs']['import']['notices']['okay']['show'] = false;
				
				if ($values['import/file']
				 && ($importFile = getPathOfUploadedFileInCacheDir($values['import/file']))
				 && (is_file($importFile))) {
					$targetCID = $targetCType = $error = false;
					$mimeType = documentMimeType($values['import/file']);
					
					if ($mimeType == 'text/html' || $mimeType == 'application/xhtml+xml') {
						if (zenario_export_tools::importContentItem(file_get_contents($importFile), false, true, $targetCID, $targetCType, $error, $box['key']['cID'], $box['key']['cType'])) {
							$box['tabs']['import']['notices']['okay']['show'] = true;
						} else {
							$box['tabs']['import']['errors'][] = $error;
						}
					
					} elseif ($mimeType == 'text/xml') {
						if (zenario_export_tools::importContentItem(file_get_contents($importFile), true, true, $targetCID, $targetCType, $error, $box['key']['cID'], $box['key']['cType'])) {
							$box['tabs']['import']['notices']['okay']['show'] = true;
						} else {
							$box['tabs']['import']['errors'][] = $error;
						}
					
					} else {
						$box['tabs']['import']['errors'][] = adminPhrase('Please select a HTML or a XML file to upload.');
					}
					
				} else {
					$box['tabs']['import']['errors'][] = adminPhrase('Please select a file to upload.');
				}
				
				if (empty($box['tabs']['import']['errors'])) {
					$box['confirm']['show'] =
						$targetCType != $box['key']['cType']
					 || getRow('content_items', 'equiv_id', array('id' => $targetCID, 'type' => $targetCType))
					 	!= getRow('content_items', 'equiv_id', array('id' => $box['key']['cID'], 'type' => $box['key']['cType']));
				}
				
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_export_tools__import':
				exitIfNotCheckPriv('_PRIV_IMPORT_CONTENT_ITEM');
				
				if ($values['import/file']
				 && ($importFile = getPathOfUploadedFileInCacheDir($values['import/file']))
				 && (is_file($importFile))) {
				
					$targetCID = $targetCType = $error = false;
					$mimeType = documentMimeType($importFile);
				
					if ($mimeType == 'text/html' || $mimeType == 'application/xhtml+xml') {
						if (zenario_export_tools::importContentItem(file_get_contents($importFile), false, false, $targetCID, $targetCType, $error, $box['key']['cID'], $box['key']['cType'])) {
							unlink($importFile);
						} else {
							echo $error;
						}
				
					} elseif ($mimeType == 'text/xml') {
						if (zenario_export_tools::importContentItem(file_get_contents($importFile), true, false, $targetCID, $targetCType, $error, $box['key']['cID'], $box['key']['cType'])) {
						} else {
							echo $error;
						}
					}
				}
				
				break;
			
			
			case 'zenario_export_tools__google_translate':
				exitIfNotCheckPriv('_PRIV_IMPORT_CONTENT_ITEM');
				
				$post = array('q' => '');
				$post['format'] = 'html';
				$post['key'] = setting('google_translate_api_key');
				
				if ($values['translate/lang_from'] && $values['translate/lang_from'] != 0) {
					$post['source'] = $values['translate/lang_from'];
				}
				
				$post['target'] = zenario_export_tools::googleLanguageCode($values['translate/lang_to']);
				
				if ($post['key'] && $post['target']) {
					if (zenario_export_tools::createExportFile($post['q'], false, false, $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'])) {
						
						$url = 'https://www.googleapis.com/language/translate/v2';
						$options = array(
							CURLOPT_HTTPHEADER => array('X-HTTP-Method-Override: GET'),
							CURLOPT_SSL_VERIFYPEER => false);
						
						if ($responce = curl($url, $post, $options)) {
							if ($json = json_decode($responce, true)) {
								if (isset($json['data']['translations'][0]['translatedText'])) {
									
									//Attempt to add a work-around for Google stripping off all of the white-space
									$json['data']['translations'][0]['translatedText'] = preg_replace('@><([hp]\d?\W)@', ">\n\t\t\t\t<\\1", $json['data']['translations'][0]['translatedText']);
									
									$targetCID = $targetCType = $error = false;
									if (zenario_export_tools::importContentItem($json['data']['translations'][0]['translatedText'], false, false, $targetCID, $targetCType, $error, $box['key']['cID'], $box['key']['cType'])) {
										//Completed!
									} else {
										echo adminPhrase('A corrupted translation was returned from Google Translate.');
										exit;
									}
								} else {
									echo adminPhrase('No translation was returned.');
									exit;
								}
							} else {
								echo $responce;
								exit;
							}
						} else {
							//Test to see if Google Translate is working
							$test = array();
							$test['q'] = 'Hello World';
							$test['source'] = 'en';
							$test['target'] = 'de';
							$test['key'] = $post['key'];
							
							if (curl($url, $test, $options)) {
								if ($values['translate/lang_from'] && $values['translate/lang_from'] != 0) {
									echo adminPhrase('Google Translate could not translate this text, and returned an unknown error.');
								} else {
									echo adminPhrase('Google Translate could not translate this text, and returned an error. You may wish to try again, without using the Auto-detect Language option.');
								}
							} else {
								echo adminPhrase('Could not launch a CURL request to https://www.googleapis.com. Please ensure that your API key is valid, that CURL is enabled on your server, and that there are no network issues between your server and the googleapis.com site.');
							}
							exit;
						}
					} else {
						echo adminPhrase('Could not create an export file for this Content Item.');
						exit;
					}
				}
			
			break;
		}
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_export_tools__export':
				exitIfNotCheckPriv('_PRIV_EXPORT_CONTENT_ITEM');
				
				$f = false;
				$isXML = $values['export/format'] == 'xml';
				$encodeHTMLAtt = $values['export/format'] == 'html_settings_encoded';
				if ($content = getRow('content_items', array('alias', 'tag_id'), array('id' => $box['key']['cID'], 'type' => $box['key']['cType']))) {
					if (zenario_export_tools::createExportFile($f, $isXML, $encodeHTMLAtt, $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'])) {
						if ($isXML) {
							header('Content-Type: text/xml; charset=UTF-8');
							header('Content-Disposition: attachment; filename="'. ifNull($content['alias'], $content['tag_id']). '.xml"');
						} else {
							header('Content-Type: text/html; charset=UTF-8');
							header('Content-Disposition: attachment; filename="'. ifNull($content['alias'], $content['tag_id']). '.html"');
						}
						
						header('Content-Length: '. strlen($f)); 
						echo $f;
					}
				}
			
			break;
		}
	}
	
	public static function createExportFile(&$f, $isXML, $encodeHTMLAtt, $cID, $cType, $cVersion, $targetCID = false, $targetCType = false) {
		if (($content = getRow('content_items', true, array('id' => $cID, 'type' => $cType)))
		 && ($version = getRow('content_item_versions', true, array('id' => $cID, 'type' => $cType, 'version' => $cVersion)))
		 && ($template = getRow('layouts', array('family_name', 'file_base_name', 'name'), $version['layout_id']))) {
			
			if ($isXML) {
				$f =
					'<?xml version="1.0" encoding="UTF-8"?>'.
					"\n<xml>\n\t<title>".
					XMLEscape($version['title']).
					"</title>";
			
			} else {
				$f =
					"<html>\n\t<head>\n\t\t<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\"></meta>\n\t\t<title>".
					htmlspecialchars($version['title']).
					"</title>\n\t</head>\n\t<body>";
			}
			
			zenario_export_tools::openTagStart($isXML, $f, 'target');
			zenario_export_tools::addAtt($isXML, $f, 'cID', ifNull($targetCID, $cID));
			zenario_export_tools::addAtt($isXML, $f, 'cType', ifNull($targetCType, $cType));
			zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f);
			zenario_export_tools::closeTag($isXML, $f, 'target');
			
			zenario_export_tools::openTagStart($isXML, $f, 'description');
			zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f, $version['description']);
			zenario_export_tools::closeTag($isXML, $f, 'description');
			
			zenario_export_tools::openTagStart($isXML, $f, 'keywords');
			zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f, $version['keywords']);
			zenario_export_tools::closeTag($isXML, $f, 'keywords');
			
			zenario_export_tools::openTagStart($isXML, $f, 'summary');
			zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f);
			zenario_export_tools::setTagContents($isXML, $f, $version['content_summary']);
			zenario_export_tools::closeTag($isXML, $f, 'summary', false);
			
			$menuNodes = getRows('menu_nodes', array('id', 'section_id'), array('target_loc' => 'int', 'equiv_id' => $content['equiv_id'], 'content_type' => $cType));
			while ($menuNode = sqlFetchAssoc($menuNodes)) {
				
				//Convert section_id to a string
				$menuNode['section_id'] = menuSectionName($menuNode['section_id']);
				
				if (($menuText = getRow('menu_text', array('name', 'descriptive_text'), array('menu_id' => $menuNode['id'], 'language_id' => $content['language_id'])))
				 || ($menuText = getRow('menu_text', array('name', 'descriptive_text'), array('menu_id' => $menuNode['id'], 'language_id' => setting('default_language'))))) {
					zenario_export_tools::openTagStart($isXML, $f, 'menu');
					zenario_export_tools::addAtt($isXML, $f, 'id', $menuNode['id']);
					zenario_export_tools::addAtt($isXML, $f, 'section_id', $menuNode['section_id']);
					zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f, $menuText['name']);
					zenario_export_tools::closeTag($isXML, $f, 'menu');
					
					if ($menuText['descriptive_text']) {
						zenario_export_tools::openTagStart($isXML, $f, 'menu_desc');
						zenario_export_tools::addAtt($isXML, $f, 'id', $menuNode['id']);
						zenario_export_tools::addAtt($isXML, $f, 'section_id', $menuNode['section_id']);
						zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f);
						zenario_export_tools::setTagContents($isXML, $f, $menuText['descriptive_text']);
						zenario_export_tools::closeTag($isXML, $f, 'menu_desc', false);
					}
				}
			}
			
			zenario_export_tools::openTagStart($isXML, $f, 'template');
			zenario_export_tools::addAtt($isXML, $f, 'family_name', $template['family_name']);
			zenario_export_tools::addAtt($isXML, $f, 'file_base_name', $template['file_base_name']);
			zenario_export_tools::addAtt($isXML, $f, 'name', $template['name']);
			zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f);
			zenario_export_tools::closeTag($isXML, $f, 'template');
			
			$slotContents = false;
			getSlotContents($slotContents, $cID, $cType, $cVersion, false, false, false, false, false, false, $runPlugins = false);
			$slotsOnTemplate = zenario_export_tools::getSlotsOnTemplate($template['family_name'], $template['file_base_name']);
			
			foreach ($slotsOnTemplate as $slotName) {
				if (!empty($slotContents[$slotName]['content_id'])
				 && !empty($slotContents[$slotName]['instance_id'])
				 && ($instance = getPluginInstanceDetails($slotContents[$slotName]['instance_id']))) {
					zenario_export_tools::openTagStart($isXML, $f, 'plugin');
					zenario_export_tools::addAtt($isXML, $f, 'class', $instance['class_name']);
					zenario_export_tools::addAtt($isXML, $f, 'slot', $slotName);
					zenario_export_tools::addAtt($isXML, $f, 'framework', $instance['framework']);
					zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f);
					
					$sql = "
						SELECT ps.*, psd.default_value
						FROM ". DB_NAME_PREFIX. "plugin_settings AS ps
						INNER JOIN ". DB_NAME_PREFIX. "plugin_instances AS pi
						   ON pi.id = ps.instance_id
						LEFT JOIN ". DB_NAME_PREFIX. "plugin_setting_defs AS psd
						   ON psd.module_id = pi.module_id
						  AND psd.name = ps.name
						WHERE ps.instance_id = ". (int) $slotContents[$slotName]['instance_id']. "
						  AND ps.nest = 0";		//Add support for Nests..?
					
					$result = sqlQuery($sql);
					while ($row = sqlFetchAssoc($result)) {
						
						//There's no need to include any settings set to their default values
						if ($row['default_value'] !== null && $row['value'] == $row['default_value']) {
							continue;
						}
						
						$writeAsContents =
							$row['format'] == 'html'
						 || $row['format'] == 'translatable_text'
						 || $row['format'] == 'translatable_html';
						
						zenario_export_tools::openTagStart($isXML, $f, 'setting', true);
						zenario_export_tools::addAtt($isXML, $f, 'name', $row['name']);
						zenario_export_tools::addAtt($isXML, $f, 'is_content', $row['is_content']);
						zenario_export_tools::addAtt($isXML, $f, 'format', $row['format']);
						
						if ($row['foreign_key_to']) {
							zenario_export_tools::addAtt($isXML, $f, 'foreign_key_to', $row['foreign_key_to']);
						}
						
						if (!$writeAsContents) {
							zenario_export_tools::addAtt($isXML, $f, 'value', $row['value']);
						}
						
						zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f);
						
						if ($writeAsContents) {
							zenario_export_tools::setTagContents($isXML, $f, $row['value'], $row['format'] == 'text' || $row['format'] == 'translatable_text', true);
							zenario_export_tools::closeTag($isXML, $f, 'setting', true);
						} else {
							zenario_export_tools::closeTag($isXML, $f, 'setting');
						}
					}
					
					
					$sql = "
						SELECT id, tab, ord, module_id, framework, is_tab, name_or_title
						FROM ". DB_NAME_PREFIX. "nested_plugins
						WHERE instance_id = ". (int) $slotContents[$slotName]['instance_id']. "
						ORDER BY tab, is_tab DESC, ord";
					
					$eggsResult = sqlQuery($sql);
					while ($egg = sqlFetchAssoc($eggsResult)) {
						if ($egg['is_tab']) {
							zenario_export_tools::openTagStart($isXML, $f, 'tab', true);
							zenario_export_tools::addAtt($isXML, $f, 'tab', $egg['tab']);
							zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f);
							zenario_export_tools::setTagContents($isXML, $f, $egg['name_or_title'], true, true);
							zenario_export_tools::closeTag($isXML, $f, 'tab');
							
						} else {
							zenario_export_tools::openTagStart($isXML, $f, 'egg', true);
							zenario_export_tools::addAtt($isXML, $f, 'tab', $egg['tab']);
							zenario_export_tools::addAtt($isXML, $f, 'ord', $egg['ord']);
							zenario_export_tools::addAtt($isXML, $f, 'class', getModuleClassName($egg['module_id']));
							zenario_export_tools::addAtt($isXML, $f, 'framework', $egg['framework']);
							zenario_export_tools::addAtt($isXML, $f, 'name_or_title', $egg['name_or_title']);
							zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f);
							zenario_export_tools::closeTag($isXML, $f, 'egg');
							
							$sql = "
								SELECT ps.*, psd.default_value
								FROM ". DB_NAME_PREFIX. "plugin_settings AS ps
								LEFT JOIN ". DB_NAME_PREFIX. "plugin_setting_defs AS psd
								   ON psd.module_id = ". (int) $egg['module_id']. "
								  AND psd.name = ps.name
								WHERE ps.instance_id = ". (int) $slotContents[$slotName]['instance_id']. "
								  AND ps.nest = ". (int) $egg['id'];
							
							$nestedResult = sqlQuery($sql);
							while ($row = sqlFetchAssoc($nestedResult)) {
								
								//There's no need to include any settings set to their default values
								if ($row['default_value'] !== null && $row['value'] == $row['default_value']) {
									continue;
								}
								
								$writeAsContents =
									$row['format'] == 'html'
								 || $row['format'] == 'translatable_text'
								 || $row['format'] == 'translatable_html';
								
								zenario_export_tools::openTagStart($isXML, $f, 'setting', true);
								zenario_export_tools::addAtt($isXML, $f, 'tab', $egg['tab']);
								zenario_export_tools::addAtt($isXML, $f, 'ord', $egg['ord']);
								zenario_export_tools::addAtt($isXML, $f, 'name', $row['name']);
								zenario_export_tools::addAtt($isXML, $f, 'is_content', $row['is_content']);
								zenario_export_tools::addAtt($isXML, $f, 'format', $row['format']);
								
								if ($row['foreign_key_to']) {
									zenario_export_tools::addAtt($isXML, $f, 'foreign_key_to', $row['foreign_key_to']);
								}
								
								if (!$writeAsContents) {
									zenario_export_tools::addAtt($isXML, $f, 'value', $row['value']);
								}
								
								zenario_export_tools::openTagEnd($isXML, $encodeHTMLAtt, $f);
								
								if ($writeAsContents) {
									zenario_export_tools::setTagContents($isXML, $f, $row['value'], $row['format'] == 'text' || $row['format'] == 'translatable_text', true);
									zenario_export_tools::closeTag($isXML, $f, 'setting', true);
								} else {
									zenario_export_tools::closeTag($isXML, $f, 'setting');
								}
							}
						}
					}
					
					zenario_export_tools::closeTag($isXML, $f, 'plugin', false);
				}
			}
			
			if ($isXML) {
				$f .= "\n</xml>";
			} else {
				$f .= "\n\t</body>\n</html>";
			}
			
			return true;
		} else {
			return false;
		}
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
			SELECT ENCODE('". sqlEscape($text). "', '". sqlEscape(setting('site_id')). "');";
		$result = sqlQuery($sql);
		$row = sqlFetchRow($result);
		return base64_encode($row[0]);
	}
	
	public static function decode($code) {
		
		try {
			$code = base64_decode($code);
		} catch (Exception $e) {
			return false;
		}
		
		$sql = "
			SELECT DECODE('". sqlEscape($code). "', '". sqlEscape(setting('site_id')). "');";
		$result = sqlQuery($sql);
		$row = sqlFetchRow($result);
		return $row[0];
	}
	
	protected static function openTagStart($isXML, &$f, $name, $ndLevel = false) {
		if ($isXML) {
			$f .= "\n\t". ($ndLevel? "\t" : ''). '<'. $name;
		} else {
			$f .= "\n\t\t". ($ndLevel? "\t" : ''). '<div id="zenario:'. $name. ',';
		}
		
		zenario_export_tools::$atts = '';
	}
	
	private static $atts;
	
	protected static function addAtt($isXML, &$f, $name, $value) {
		if ($isXML) {
			$f .= ' '. $name. '="'. XMLEscape($value). '"';
		} else {
			zenario_export_tools::$atts .=
				str_replace(array(',', ':'), array('&#44;', '&#58;'), $name).
				':'.
				str_replace(array(',', ':'), array('&#44;', '&#58;'), htmlspecialchars($value)).
				',';
		}
	}
	
	protected static function openTagEnd($isXML, $encodeHTMLAtt, &$f, $addTitle = false) {
		
		if (!$isXML) {
			if (!$encodeHTMLAtt) {
				$f .= zenario_export_tools::$atts;
			} else {
				$f .= zenario_export_tools::encode(zenario_export_tools::$atts);
			}
		}
		
		if ($addTitle !== false) {
			if ($isXML) {
				$f .= '>'. XMLEscape($addTitle);
			} else {
				$f .= '" title="'. htmlspecialchars($addTitle). '">';
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
			$f .= "\n\t\t". ($ndLevel? "\t" : ''). XMLEscape($value);
		
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
			return XMLEscape($value);
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
		return XMLEscape(str_ireplace('&apos;', "'", html_entity_decode($html, ENT_QUOTES, 'UTF-8')));
	}
	
	protected static function getSlotsOnTemplate($templateFamily, $fileBaseName) {
		$result = getRows(
			'template_slot_link',
			'slot_name',
			array('family_name' => $templateFamily, 'file_base_name' => $fileBaseName),
			array('slot_name'));
		
		$slotsOnTemplate = array();
		while ($slotName = sqlFetchAssoc($result)) {
			$slotsOnTemplate[] = $slotName['slot_name'];
		}
		
		return $slotsOnTemplate;
	}
	
	
	public static function importContentItem($input, $isXML, $onlyValidate, &$targetCID, &$targetCType, &$error, $cID = false, $cType = false) {
		$error = false;
		
		if (!($input = trim($input))) {
			$error = adminPhrase('The file is empty.');
			return false;
		}
		
		if ($isXML) {
			$xml = $input;
		
		} else {
			//Attempt to convert html title to xml
			if (($pos1 = stripos($input, '<title>'))
			 && ($pos2 = stripos($input, '</title>'))
			 && ($pos1 < $pos2)) {
				$input =
					substr($input, 0, $pos1 + 7).
					zenario_export_tools::HTMLToXML(substr($input, $pos1 + 7, $pos2 - $pos1 - 7)).
					substr($input, $pos2);
			}
			
			
			$xml = '<?xml version="1.0" encoding="UTF-8"?>';
			$ndLevels = array();
			$input = preg_split('@<div\s+id=([\'"])zenario:(\w+)([,;]?)(.*?)\1([^>]*?)/?>@s', $input, -1,  PREG_SPLIT_DELIM_CAPTURE);
			
			$levels = array();
			foreach ($input as $i => &$thing) {
				switch ($i % 6) {
					case 2:
						$ndLevels[ceil($i/6)] = $thing == 'egg' || $thing == 'setting' || $thing == 'tab';
				}
			}
			
			foreach ($input as $i => &$thing) {
				if ($i == 0) {
					$xml .= $thing;
				
				} else {
					$lastThingWasSecondLevel = arrayKey($ndLevels, ceil($i/6) - 1);
					$thisThingIsSecondLevel = arrayKey($ndLevels, ceil($i/6));
					$nextThingWillBeSecondLevel = arrayKey($ndLevels, ceil($i/6) + 1);
					
					switch ($i % 6) {
						case 1:
							//Opening/closing quotes
							break;
							
						case 2:
							//The first attribute value will be the name of this thing
							$type = $thing;
							break;
							
						case 3:
							//The seperator being used for the end of attribute values
							//This was a semi-column initially during development, but later changed to a comma due to several bugs
							//caused by overlapping with the semi-column in HTML escaping.
							//Note the seperator for the start of the values is always a colon.
							$sep = $thing;
							break;
							
						case 4:
							//The rest of the attributes. Break them up between name and value pairs using the two seperators mentioned above,
							//and then write them as XML tags
							$isHTML = true;
							if ($lastThingWasSecondLevel && !$thisThingIsSecondLevel) {
								$xml .= '</plugin>';
							}
							
							if ($thing && strpos($thing, ':') === false && strpos($thing, ':') === false) {
								if (!$thing = zenario_export_tools::decode($thing)) {
									$error = adminPhrase('The file cannot be imported. Either the file was exported from a different site, or the encoded settings have been corrupted.');
									return false;
								}
							}
							
							$xml .= '<'. $type;
							foreach (explode($sep, $thing) as $atts) {
								$att = explode(':', $atts, 2);
								if (!empty($att[0])) {
									$xml .= ' '. $att[0]. '="'. zenario_export_tools::HTMLToXML(arrayKey($att, 1)). '"';
									
									if ($att[0] == 'format' && (arrayKey($att, 1) == 'text' || arrayKey($att, 1) == 'translatable_text')) {
										$isHTML = false;
									}
								}
							}
							break;
							
						case 5:
							//Attempt to convert html attributes to xml attributes
							$jnput = preg_split('@([\'"])(.*?)\1@s', $thing, -1,  PREG_SPLIT_DELIM_CAPTURE);
							foreach ($jnput as $j => &$thjng) {
								switch ($j % 3) {
									case 2:
										$xml .= '"'. zenario_export_tools::HTMLToXML($thjng). '"';
										break;
									case 0:
										$xml .= $thjng;
										break;
								}
							}
							
							$xml .= '>';
							
							break;
						
						case 0:
							$thing = trim($thing);
							
							if (substr($thing, -7) == '</html>') {
								foreach (explode('</div>', $thing, $thisThingIsSecondLevel? -2 : -1) as $j => $snippet) {
									if ($j) {
										$xml .= XMLEscape('</div>');
									}
									
									if ($isHTML) {
										$xml .= XMLEscape($snippet);
									} else {
										$xml .= zenario_export_tools::HTMLToXML($snippet);
									}
								}
								
								if ($thisThingIsSecondLevel) {
									$xml .= '</'. $type. '></plugin></body></html>';
								} else {
									$xml .= '</'. $type. '></body></html>';
								}
								
							} else {
								$divs = 1;
								if (!$thisThingIsSecondLevel && $nextThingWillBeSecondLevel) {
									$divs = 0;
								} elseif ($thisThingIsSecondLevel && !$nextThingWillBeSecondLevel) {
									$divs = 2;
								}
								
								for ($j = 0; $j < $divs; ++$j) {
									if (substr($thing, -6) == '</div>') {
										$thing = trim(substr($thing, 0, -6));
									}
								}
								
								if ($isHTML) {
									$xml .= XMLEscape($thing);
								} else {
									$xml .= zenario_export_tools::HTMLToXML($thing);
								}
								
								if ($divs > 0) {
									$xml .= '</'. $type. '>';
								}
							}
							
							break;
					}
				}
			}
			
			if (!($xml = strip_tags($xml, '<html><target><title><description><keywords><summary><menu><menu_desc><template><plugin><setting><tab><egg>'))) {
				$error = adminPhrase('The file format has been corrupted and the file could not be read.');
				return false;
			}
		}
		
		
		//echo '<pre>', htmlspecialchars($xml), '</pre>'; exit;
		if (!$xml = SimpleXMLString($xml)) {
			$error = adminPhrase('The file format has been corrupted and the file could not be read.');
			return false;
		
		} else {
			
			//Work out which Content Item this is for
			if ($xml->target) {
				$targetCID = (int) $xml->target->attributes()->cID;
				$targetCType = (string) $xml->target->attributes()->cType;
				
				if (!$cID) {
					$cID = $targetCID;
					$cType = $targetCType;
				}
			}
			
			//Check which Content Item we are updating, and stop if we can't find the target
			if (!$cID || !$cType || !checkRowExists('content_items', array('id' => $cID, 'type' => $cType))) {
				$error = adminPhrase('The target Content Item could not be found.');
				return false;
			
			} elseif (!checkPriv(false, $cID, $cType)) {
				$error = adminPhrase('This Content Item is checked out by another Administrator.');
				return false;
			
			//If we're only checking if the file is valid, stop here
			} elseif ($onlyValidate) {
				return true;
			}
			
			$cVersion = false;
			if (!isDraft($cID, $cType)) {
				createDraft($cID, $cID, $cType, $cVersion);
			} else {
				$cVersion = getLatestVersion($cID, $cType);
			}
			
			$content = getRow('content_items', true, array('id' => $cID, 'type' => $cType));

			
			//Try to save the names of Menu Nodes from the exports
			//Firstly, check which Menu Nodes have been created for this Equivalence, and in which section
			$existingMenuItems = array();
			$menuNodes = getRows('menu_nodes', array('id', 'section_id'), array('target_loc' => 'int', 'equiv_id' => $content['equiv_id'], 'content_type' => $cType));
			while ($menuNode = sqlFetchAssoc($menuNodes)) {
				//Convert section_id to a string
				$menuNode['section_id'] = menuSectionName($menuNode['section_id']);
				
				if (!isset($existingMenuItems[$menuNode['section_id']])) {
					$existingMenuItems[$menuNode['section_id']] = array();
				}
				
				$existingMenuItems[$menuNode['section_id']][$menuNode['id']] = $menuNode['id'];
			}
			
			//Secondly, get the details of Menu Nodes that currently exist for this Equivalence
			$menuItemsInImport = array();
			if ($xml->menu) {
				foreach ($xml->menu as $menu) {
					$sectionId = (string) $menu->attributes()->section_id;
					$mID = (string) $menu->attributes()->id;
					
					if (!isset($menuItemsInImport[$sectionId])) {
						$menuItemsInImport[$sectionId] = array();
					}
					
					$menuItemsInImport[$sectionId][$mID] = array();
					$menuItemsInImport[$sectionId][$mID]['name'] = zenario_export_tools::getValue($menu);
				}
			}
			
			if ($xml->menu_desc) {
				foreach ($xml->menu_desc as $menu) {
					$sectionId = (string) $menu->attributes()->section_id;
					$mID = (string) $menu->attributes()->id;
					
					if (isset($menuItemsInImport[$sectionId][$mID])) {
						$menuItemsInImport[$sectionId][$mID]['descriptive_text'] = zenario_export_tools::getValue($menu);
					}
				}
			}
			
			//Finally, loop through each and try to match them up
			foreach ($menuItemsInImport as $sectionId => $menus) {
				foreach ($menus as $mID => $details) {
					if (isset($existingMenuItems[$sectionId])) {
						
						//Try to match by Menu Id
						if (isset($existingMenuItems[$sectionId][$mID])) {
							saveMenuText($mID, $content['language_id'], $details);
						
						//If that fails, check how many Menu Nodes are in this section.
						//If there's only one in both the import and the export, then we can match that way
						} elseif (count($menus) == 1 && count($existingMenuItems[$sectionId]) == 1) {
							foreach ($existingMenuItems[$sectionId] as $replaceMID) {
								saveMenuText($replaceMID, $content['language_id'], $details);
								break;
							}
						}
					}
				}
			}
			
			
			
			//Update metadata from the import file
			$version = array();
			$version['title'] = zenario_export_tools::getValue($xml->title);
			$version['description'] = zenario_export_tools::getValue($xml->description);
			$version['keywords'] = zenario_export_tools::getValue($xml->keywords);
			$version['content_summary'] = zenario_export_tools::fixHTML(zenario_export_tools::getValue($xml->summary));
			
			//Try to set/change the template of the Content Item to one that best matches the template mentioned in the import file
			if ($xml->template) {
				$sql = "
					SELECT layout_id
					FROM ". DB_NAME_PREFIX. "layouts
					WHERE content_type = '". sqlEscape($cType). "'
					ORDER BY
						family_name = '". sqlEscape($xml->template->attributes()->family_name). "' DESC,
						file_base_name = '". sqlEscape($xml->template->attributes()->file_base_name). "' DESC,
						name = '". sqlEscape($xml->template->attributes()->name). "' DESC,
						layout_id ASC
					LIMIT 1";
				
				if (($result = sqlQuery($sql)) && ($row = sqlFetchAssoc($result))) {
					$version['layout_id'] = $row['layout_id'];
				}
			}
			
			updateVersion($cID, $cType, $cVersion, $version, true);
			
			
			//Get information on the template we're using
			if (isset($version['layout_id'])) {
				$template = getRow('layouts', array('family_name', 'file_base_name', 'name'), $version['layout_id']);
			} else {
				$template = getRow('layouts', array('family_name', 'file_base_name', 'name'), contentItemTemplateId($cID, $cType, $cVersion));
			}
			
			//Loop through the slots on the template, seeing what Modules are placed where
			$slotContents = false;
			getSlotContents($slotContents, $cID, $cType, $cVersion, false, false, false, false, false, false, false, $runPlugins = false);
			$slotsOnTemplate = zenario_export_tools::getSlotsOnTemplate($template['family_name'], $template['file_base_name']);
			
			$pluginsToRemoveInTemplate = array();
			foreach ($slotsOnTemplate as $slotName) {
				if (!empty($slotContents[$slotName]['content_id'])
				 && !empty($slotContents[$slotName]['instance_id'])
				 && ($instance = getPluginInstanceDetails($slotContents[$slotName]['instance_id']))) {
					$className = $instance['class_name'];
					
					$pluginsToRemoveInTemplate[$slotName] = $className;
				}
			}
			
			
			//Loop through the import, seeing what Modules are placed in what order
			$pluginsInImport = array();
			$pluginsToAddFromImport = array();
			$matchesImportToTemplate = array();
			$matchesTemplateToImport = array();
			if ($xml->plugin) {
				foreach ($xml->plugin as $plugin) {
					if (($className = (string) $plugin->attributes()->{'class'})
					 && ($slotName = (string) $plugin->attributes()->slot)) {
						$pluginsInImport[$slotName] = $className;
						
						//Does this slot match with what's in the Layout?
						if (isset($pluginsToRemoveInTemplate[$slotName]) && $pluginsToRemoveInTemplate[$slotName] == $className) {
							//If so, note down this match, and remove all other mention of the slot
							//from the arrays so we don't try to move it somewhere else
							$matchesImportToTemplate[$slotName] = $slotName;
							$matchesTemplateToImport[$slotName] = $slotName;
							
							unset($pluginsToRemoveInTemplate[$slotName]);
						
						} else {
							//Otherwise, note down that we need to match things up
							$pluginsToAddFromImport[$slotName] = $className;
						}
					}
				}
			}
			
			//Try to handle the case where the same number of Plugins exist, but they are
			//just in different places, by adding them into slots as we see them
			$changes = true;
			while ($changes) {
				$changes = false;
				foreach ($pluginsToRemoveInTemplate as $tSlotName => $tClassName) {
					foreach ($pluginsToAddFromImport as $iSlotName => $iClassName) {
						if ($tClassName == $iClassName) {
							$matchesImportToTemplate[$iSlotName] = $tSlotName;
							$matchesTemplateToImport[$tSlotName] = $iSlotName;
							
							unset($pluginsToRemoveInTemplate[$tSlotName]);
							unset($pluginsToAddFromImport[$iSlotName]);
							
							$changes = true;
							continue 3;
						}
					}
				}
			}
			
			if (!empty($pluginsToAddFromImport)) {
				//So we can try and keep things in order, for each Plugin that we did place, work out where the last Plugin was placed
				$previousSlot = '';
				$previousSlots = array();
				foreach ($pluginsInImport as $slotName => $className) {
					if (isset($matchesImportToTemplate[$slotName])) {
						$previousSlot = $matchesImportToTemplate[$slotName];
					}
					$previousSlots[$slotName] = $previousSlot;
				}
				
				//Loop through any remaining Plugins in the import, and put them in the next empty slot after the slot that they were in the import
				foreach ($pluginsToAddFromImport as $iSlotName => $className) {
					$passedSlot = false;
					for ($i = 0; $i < 2; ++$i) {
						foreach ($slotsOnTemplate as $tSlotName) {
							
							if ($passedSlot) {
								//Add this Plugin to the next empty slot alphabetically after where it was in the import
								//(Note that a slot with a Wireframe Plugin currently in it, but that was not mentioned in the import,
								// is considered empty for this purpose.)
								if (!isset($matchesTemplateToImport[$tSlotName])
								 && (isset($pluginsToRemoveInTemplate[$tSlotName]) || empty($slotContents[$tSlotName]['instance_id']))
								) {
									$matchesImportToTemplate[$iSlotName] = $tSlotName;
									$matchesTemplateToImport[$tSlotName] = $iSlotName;
									unset($pluginsToRemoveInTemplate[$tSlotName]);
									continue 3;
								}
							
							} elseif ($tSlotName >= ifNull(arrayKey($previousSlots, $iSlotName), '', '')) {
								$passedSlot = true;
							}
						}
						$passedSlot = true;
					}
				}
			}
			
			
			//Remove any existing Plugins that are in the template but were not matched with anything in the import
			foreach ($pluginsToRemoveInTemplate as $slotName => $className) {
				if (isset($slotsOnTemplate[$slotName]['level']) && $slotsOnTemplate[$slotName]['level'] > 1) {
					//If we are trying to remove a Plugin that was set at the Layout/Template Family level,
					//then we need to make the slot opaque at the item level
					updatePluginInstanceInItemSlot(0, $slotName, $cID, $cType, $cVersion);
				} else {
					//Otherwise we just need to make sure that the slot is empty at the item level
					updatePluginInstanceInItemSlot('', $slotName, $cID, $cType, $cVersion);
				}
			}
			
			
			//Add the Plugins in from the Import
			if ($xml->plugin) {
				foreach ($xml->plugin as $plugin) {
					if (($className = (string) $plugin->attributes()->{'class'})
					 && ($slotName = (string) $plugin->attributes()->slot)
					 && ($moduleId = getModuleIdByClassName($className))) {
						
						if ($slotName = arrayKey($matchesImportToTemplate, $slotName)) {
							$images = array();
							$nestedPlugins = array();
							
							if ($instanceId = arrayKey($slotContents, $slotName, 'instance_id')) {
								//Look for any Nested Tabs that match up with the Nested Tabs we are importing
								if ($plugin->tab) {
									foreach ($plugin->tab as $tab) {
										if ($id = 
											getRow('nested_plugins', 'id',
												array(
													'instance_id' => $instanceId,
													'is_tab' => 1,
													'tab' => (int) $tab->attributes()->tab))
										) {
											$nestedPlugins[] = $id;
										}
									}
								}
								
								//Look for any Nested Plugins that match up with the Nested Plugins we are importing
								if ($plugin->egg) {
									foreach ($plugin->egg as $egg) {
										if ($nestedModuleId = getModuleIdByClassName($egg->attributes()->class)) {
											if ($id = 
												getRow('nested_plugins', 'id', 
													array(
														'instance_id' => $instanceId,
														'is_tab' => 0,
														'tab' => (int) $egg->attributes()->tab,
														'ord' => (int) $egg->attributes()->ord,
														'module_id' => $nestedModuleId))
											) {
												$nestedPlugins[] = $id;
											}
										}
									}
								}
								
								//Remove any Nested Tabs/Plugins that are in the database, and don't match up to the ones we just found.
								//Otherwise we'll try to preserve their ids, in order to preserve any Swatch choices that are linked to them
								$sql = "
									DELETE FROM ". DB_NAME_PREFIX. "nested_plugins
									WHERE instance_id = ". (int) $instanceId;
								
								if (!empty($nestedPlugins)) {
									$sql .= "
									  AND id NOT IN (". implode(',', $nestedPlugins). ")";
								}
								sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
							
							
								//Check for any existing file/image-links for the current Plugin, and note down what they are for use later
								$result = getRows('plugin_settings', array('name', 'nest', 'value'), array('instance_id' => $instanceId, 'foreign_key_to' => 'file'));
								while ($row = sqlFetchAssoc($result)) {
									if (!isset($images[$row['nest']])) {
										$images[$row['nest']] = array();
									}
									
									$images[$row['nest']][$row['name']] = $row['value'];
								}
							}
							
							
							//Place the Plugin on the slot, and get its Wireframe Instance Id
							//If is is the same type of Plugin that was there before, this should not change
							updatePluginInstanceInItemSlot(0, $slotName, $cID, $cType, $cVersion, $moduleId, $copySwatchUp = true);
							$instanceId = getVersionControlledPluginInstanceId($cID, $cType, $cVersion, $slotName, $moduleId);
							
							//Remove any settings
							$key = array('instance_id' => $instanceId);
							deleteRow('plugin_instance_cache', $key);
							deleteRow('plugin_settings', $key);
							
							//Add the new settings
							setRow('plugin_instances', array('framework' => (string) $plugin->attributes()->framework), $instanceId);
							
							//Import/update the tabs
							if ($plugin->tab) {
								foreach ($plugin->tab as $tab) {
									setRow(
										'nested_plugins',
										array(
											'ord' => 0,
											'module_id' => 0,
											'name_or_title' => zenario_export_tools::getValue($tab)),
										array(
											'instance_id' => $instanceId,
											'is_tab' => 1,
											'tab' => (int) $tab->attributes()->tab));
								}
							}
							
							//Import/update the Nested Plugins
							$nestedPlugins = array();
							if ($plugin->egg) {
								foreach ($plugin->egg as $egg) {
									if ($nestedModuleId = getModuleIdByClassName($egg->attributes()->class)) {
										$tab = (int) $egg->attributes()->tab;
										$ord = (int) $egg->attributes()->ord;
										if (!isset($nestedPlugins[$tab])) {
											$nestedPlugins[$tab] = array();
										}
										
										$nestedPlugins[$tab][$ord] = setRow(
											'nested_plugins',
											array(
												'framework' => $egg->attributes()->framework,
												'name_or_title' => $egg->attributes()->name_or_title),
											array(
												'instance_id' => $instanceId,
												'is_tab' => 0,
												'tab' => $tab,
												'ord' => $ord,
												'module_id' => $nestedModuleId));
									}
								}
							}
							
							if ($plugin->setting) {
								foreach ($plugin->setting as $setting) {
									$tab = (int) $setting->attributes()->tab;
									$ord = (int) $setting->attributes()->ord;
									
									if (!$tab && !$ord) {
										$key['nest'] = 0;
									
									} elseif (!empty($nestedPlugins[$tab][$ord])) {
										$key['nest'] = $nestedPlugins[$tab][$ord];
									
									} else {
										continue;
									}
									
									$key['name'] = (string) $setting->attributes()->name;
									
									$value = array();
									$value['value'] = zenario_export_tools::getValue($setting);
									$value['is_content'] = (string) $setting->attributes()->is_content;
									$value['format'] = (string) $setting->attributes()->format;
									$value['foreign_key_to'] = NULL;
									$value['foreign_key_id'] = 0;
									$value['foreign_key_char'] = '';
									
									if (in($value['format'], 'html', 'translatable_html')) {
										$value['value'] = zenario_export_tools::fixHTML($value['value']);
									}
									
									switch ($keyTo = (string) $setting->attributes()->foreign_key_to) {
										case 'content':
											$linkedCID = $linkedCType = false;
											if ((!getCIDAndCTypeFromTagId($linkedCID, $linkedCType, $value['value']))
											 || (!$status = getContentStatus($linkedCID, $linkedCType))
											 || ($status == 'deleted' || $status == 'trashed')) {
												continue 2;
											}
											
											$value['foreign_key_to'] = $keyTo;
											$value['foreign_key_id'] = $linkedCID;
											$value['foreign_key_char'] = $linkedCType;
											break;
										
										case 'email_template':
											if (!checkRowExists('email_templates', array('code' => $value['value']))) {
												continue 2;
											}
											
											$value['foreign_key_to'] = $keyTo;
											$value['foreign_key_id'] = 0;
											$value['foreign_key_char'] = $value['value'];
											break;
										
										case 'categories':
											foreach (explode(',', $value['value']) as $cat) {
												if (!checkRowExists('categories', $cat)) {
													continue 3;
												}
											}
											
											$value['foreign_key_to'] = $keyTo;
											break;
										
										case 'category':
											if (!checkRowExists('categories', $value['value'])) {
												continue 2;
											}
											
											$value['foreign_key_to'] = $keyTo;
											$value['foreign_key_id'] = $value['value'];
											break;
											
										case 'file':
											//Check the existing files to see if one was linked in this position before the export.
											//If so, don't change it
											if (!empty($images[$key['nest']][$key['name']])) {
												$value['value'] = $images[$key['nest']][$key['name']];
											
											} elseif (!checkRowExists('files', $value['value'])) {
												continue 2;
											}
											
											$value['foreign_key_to'] = $keyTo;
											$value['foreign_key_id'] = $value['value'];
											break;
									}
									
									
									setRow('plugin_settings', $value, $key);
								}
							}
						}
					}
				}
			}
			
			
			syncInlineFileContentLink($cID, $cType, $cVersion);
			
			return true;
		}
	}
}
?>
