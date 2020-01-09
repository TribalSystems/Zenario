<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

if (($content = ze\row::get('content_items', true, ['id' => $cID, 'type' => $cType]))
 && ($version = ze\row::get('content_item_versions', true, ['id' => $cID, 'type' => $cType, 'version' => $cVersion]))
 && ($template = ze\row::get('layouts', ['family_name', 'file_base_name', 'name'], $version['layout_id']))) {
	
	if ($isXML) {
		$f =
			'<?xml version="1.0" encoding="UTF-8"?>'.
			"\n<xml>\n\t<title>".
			ze\escape::xml($version['title']).
			"</title>";
	
	} else {
		$f =
			"<html>\n\t<head>\n\t\t<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\"></meta>\n\t\t<title>".
			htmlspecialchars($version['title']).
			"</title>\n\t</head>\n\t<body>";
	}
	
	zenario_pro_features::openTagStart($isXML, $f, 'target');
	zenario_pro_features::addAtt($isXML, $f, 'cID', ($targetCID ?: $cID));
	zenario_pro_features::addAtt($isXML, $f, 'cType', ($targetCType ?: $cType));
	zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f);
	zenario_pro_features::closeTag($isXML, $f, 'target');
	
	zenario_pro_features::openTagStart($isXML, $f, 'description');
	zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f, $version['description']);
	zenario_pro_features::closeTag($isXML, $f, 'description');
	
	zenario_pro_features::openTagStart($isXML, $f, 'keywords');
	zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f, $version['keywords']);
	zenario_pro_features::closeTag($isXML, $f, 'keywords');
	
	zenario_pro_features::openTagStart($isXML, $f, 'summary');
	zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f);
	zenario_pro_features::setTagContents($isXML, $f, $version['content_summary']);
	zenario_pro_features::closeTag($isXML, $f, 'summary', false);
	
	$menuNodes = ze\row::query('menu_nodes', ['id', 'section_id'], ['target_loc' => 'int', 'equiv_id' => $content['equiv_id'], 'content_type' => $cType]);
	while ($menuNode = ze\sql::fetchAssoc($menuNodes)) {
		
		//Convert section_id to a string
		$menuNode['section_id'] = ze\menu::sectionName($menuNode['section_id']);
		
		if (($menuText = ze\row::get('menu_text', ['name', 'descriptive_text'], ['menu_id' => $menuNode['id'], 'language_id' => $content['language_id']]))
		 || ($menuText = ze\row::get('menu_text', ['name', 'descriptive_text'], ['menu_id' => $menuNode['id'], 'language_id' => ze::$defaultLang]))) {
			zenario_pro_features::openTagStart($isXML, $f, 'menu');
			zenario_pro_features::addAtt($isXML, $f, 'id', $menuNode['id']);
			zenario_pro_features::addAtt($isXML, $f, 'section_id', $menuNode['section_id']);
			zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f, $menuText['name']);
			zenario_pro_features::closeTag($isXML, $f, 'menu');
			
			if ($menuText['descriptive_text']) {
				zenario_pro_features::openTagStart($isXML, $f, 'menu_desc');
				zenario_pro_features::addAtt($isXML, $f, 'id', $menuNode['id']);
				zenario_pro_features::addAtt($isXML, $f, 'section_id', $menuNode['section_id']);
				zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f);
				zenario_pro_features::setTagContents($isXML, $f, $menuText['descriptive_text']);
				zenario_pro_features::closeTag($isXML, $f, 'menu_desc', false);
			}
		}
	}
	
	zenario_pro_features::openTagStart($isXML, $f, 'template');
	zenario_pro_features::addAtt($isXML, $f, 'family_name', $template['family_name']);
	zenario_pro_features::addAtt($isXML, $f, 'file_base_name', $template['file_base_name']);
	zenario_pro_features::addAtt($isXML, $f, 'name', $template['name']);
	zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f);
	zenario_pro_features::closeTag($isXML, $f, 'template');
	
	$slotContents = [];
	ze\plugin::slotContents($slotContents, $cID, $cType, $cVersion, false, false, false, false, false, false, $runPlugins = false);
	$slotsOnTemplate = zenario_pro_features::getSlotsOnTemplate($template['family_name'], $template['file_base_name']);
	
	foreach ($slotsOnTemplate as $slotName) {
		if (!empty($slotContents[$slotName]['content_id'])
		 && !empty($slotContents[$slotName]['instance_id'])
		 && ($instance = ze\plugin::details($slotContents[$slotName]['instance_id']))) {
			zenario_pro_features::openTagStart($isXML, $f, 'plugin');
			zenario_pro_features::addAtt($isXML, $f, 'class', $instance['class_name']);
			zenario_pro_features::addAtt($isXML, $f, 'slot', $slotName);
			zenario_pro_features::addAtt($isXML, $f, 'framework', $instance['framework']);
			zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f);
			
			$sql = "
				SELECT ps.*, psd.default_value
				FROM ". DB_PREFIX. "plugin_settings AS ps
				INNER JOIN ". DB_PREFIX. "plugin_instances AS pi
				   ON pi.id = ps.instance_id
				LEFT JOIN ". DB_PREFIX. "plugin_setting_defs AS psd
				   ON psd.module_id = pi.module_id
				  AND psd.name = ps.name
				WHERE ps.instance_id = ". (int) $slotContents[$slotName]['instance_id']. "
				  AND ps.egg_id = 0";		//Add support for Nests..?
			
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				
				//There's no need to include any settings set to their default values
				if ($row['default_value'] !== null && $row['value'] == $row['default_value']) {
					continue;
				}
				
				$writeAsContents =
					$row['format'] == 'html'
				 || $row['format'] == 'translatable_text'
				 || $row['format'] == 'translatable_html';
				
				zenario_pro_features::openTagStart($isXML, $f, 'setting', true);
				zenario_pro_features::addAtt($isXML, $f, 'name', $row['name']);
				zenario_pro_features::addAtt($isXML, $f, 'is_content', $row['is_content']);
				zenario_pro_features::addAtt($isXML, $f, 'format', $row['format']);
				
				if ($row['foreign_key_to']) {
					zenario_pro_features::addAtt($isXML, $f, 'foreign_key_to', $row['foreign_key_to']);
				}
				
				if (!$writeAsContents) {
					zenario_pro_features::addAtt($isXML, $f, 'value', $row['value']);
				}
				
				zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f);
				
				if ($writeAsContents) {
					zenario_pro_features::setTagContents($isXML, $f, $row['value'], $row['format'] == 'text' || $row['format'] == 'translatable_text', true);
					zenario_pro_features::closeTag($isXML, $f, 'setting', true);
				} else {
					zenario_pro_features::closeTag($isXML, $f, 'setting');
				}
			}
			
			
			$sql = "
				SELECT id, slide_num, ord, module_id, framework, is_slide, name_or_title
				FROM ". DB_PREFIX. "nested_plugins
				WHERE instance_id = ". (int) $slotContents[$slotName]['instance_id']. "
				ORDER BY slide_num, is_slide DESC, ord";
			
			$eggsResult = ze\sql::select($sql);
			while ($egg = ze\sql::fetchAssoc($eggsResult)) {
				if ($egg['is_slide']) {
					zenario_pro_features::openTagStart($isXML, $f, 'slide', true);
					zenario_pro_features::addAtt($isXML, $f, 'slideNum', $egg['slide_num']);
					zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f);
					zenario_pro_features::setTagContents($isXML, $f, $egg['name_or_title'], true, true);
					zenario_pro_features::closeTag($isXML, $f, 'slide');
					
				} else {
					zenario_pro_features::openTagStart($isXML, $f, 'egg', true);
					zenario_pro_features::addAtt($isXML, $f, 'slideNum', $egg['slide_num']);
					zenario_pro_features::addAtt($isXML, $f, 'ord', $egg['ord']);
					zenario_pro_features::addAtt($isXML, $f, 'class', ze\module::className($egg['module_id']));
					zenario_pro_features::addAtt($isXML, $f, 'framework', $egg['framework']);
					zenario_pro_features::addAtt($isXML, $f, 'name_or_title', $egg['name_or_title']);
					zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f);
					zenario_pro_features::closeTag($isXML, $f, 'egg');
					
					$sql = "
						SELECT ps.*, psd.default_value
						FROM ". DB_PREFIX. "plugin_settings AS ps
						LEFT JOIN ". DB_PREFIX. "plugin_setting_defs AS psd
						   ON psd.module_id = ". (int) $egg['module_id']. "
						  AND psd.name = ps.name
						WHERE ps.instance_id = ". (int) $slotContents[$slotName]['instance_id']. "
						  AND ps.egg_id = ". (int) $egg['id'];
					
					$nestedResult = ze\sql::select($sql);
					while ($row = ze\sql::fetchAssoc($nestedResult)) {
						
						//There's no need to include any settings set to their default values
						if ($row['default_value'] !== null && $row['value'] == $row['default_value']) {
							continue;
						}
						
						$writeAsContents =
							$row['format'] == 'html'
						 || $row['format'] == 'translatable_text'
						 || $row['format'] == 'translatable_html';
						
						zenario_pro_features::openTagStart($isXML, $f, 'setting', true);
						zenario_pro_features::addAtt($isXML, $f, 'slideNum', $egg['slide_num']);
						zenario_pro_features::addAtt($isXML, $f, 'ord', $egg['ord']);
						zenario_pro_features::addAtt($isXML, $f, 'name', $row['name']);
						zenario_pro_features::addAtt($isXML, $f, 'is_content', $row['is_content']);
						zenario_pro_features::addAtt($isXML, $f, 'format', $row['format']);
						
						if ($row['foreign_key_to']) {
							zenario_pro_features::addAtt($isXML, $f, 'foreign_key_to', $row['foreign_key_to']);
						}
						
						if (!$writeAsContents) {
							zenario_pro_features::addAtt($isXML, $f, 'value', $row['value']);
						}
						
						zenario_pro_features::openTagEnd($isXML, $encodeHTMLAtt, $f);
						
						if ($writeAsContents) {
							zenario_pro_features::setTagContents($isXML, $f, $row['value'], $row['format'] == 'text' || $row['format'] == 'translatable_text', true);
							zenario_pro_features::closeTag($isXML, $f, 'setting', true);
						} else {
							zenario_pro_features::closeTag($isXML, $f, 'setting');
						}
					}
				}
			}
			
			zenario_pro_features::closeTag($isXML, $f, 'plugin', false);
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