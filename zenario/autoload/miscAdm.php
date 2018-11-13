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


namespace ze;

class miscAdm {




	//Formerly "convertMySQLToJqueryDateFormat()"
	public static function convertMySQLToJqueryDateFormat($value) {
		return str_replace(
			['[[_MONTH_SHORT_%m]]', '%Y', '%y', '%c', '%m', '%e', '%d'],
			['M', 'yy', 'y', 'm', 'mm', 'd', 'dd'],
			$value);
	}

	//Format the values for the date-format select lists in the installer and the site-settings
	//Formerly "formatDateFormatSelectList()"
	public static function formatDateFormatSelectList(&$field, $addFormatInBrackets = false, $isJavaScriptFormat = false) {
	
		//	//Check the current date
		//	$dd = date('d');
		//	$mm = date('m');
		//	$yy = date('y');
		//
		//	//Would the current date be a good example date (i.e. the day/month/year must all be different numbers)?
		//	if ($dd == $mm || $dd == $yy || $yy == $mm) {
		//		//If not, use a different sample
		//		$exampleDate = '1999-09-19';
		//	} else {
		//		//If so, use the current date
		//		$exampleDate = date('Y-m-d');
		//	}
	
		$exampleDate = date('Y-m-d');
		$ddmmyyyyDate = '2222-03-04';
	
		//Ensure the chosen value is in the list of values!
		if (!empty($field['value'])
		 && empty($field['values'][$field['value']])) {
			$field['values'][$field['value']] = [];
		}
	
		foreach ($field['values'] as $value => &$details) {
			if ($isJavaScriptFormat) {
				$value = str_replace(
					['yy', 'y', 'm', '%c%c', 'd', '%e%e', 'M'],
					['%Y', '%y', '%c', '%m', '%e', '%d', '%b'],
					$value);
			}
			$value = str_replace(
				['[[_WEEKDAY_%w]]', '[[_MONTH_LONG_%m]]', '[[_MONTH_SHORT_%m]]'],
				['%W', '%M', '%b'],
				$value);
		
			$sql = "SELECT DATE_FORMAT('" . \ze\escape::sql($exampleDate) . "', '" . \ze\escape::sql($value) . "')";
			$result = \ze\sql::select($sql);
			$row = \ze\sql::fetchRow($result);
			$example = $row[0];
		
			if ($addFormatInBrackets) {
				$sql = "SELECT DATE_FORMAT('" . \ze\escape::sql($ddmmyyyyDate) . "', '" . \ze\escape::sql($value) . "')";
				$result = \ze\sql::select($sql);
				$row = \ze\sql::fetchRow($result);
				$ddmmyyy = str_replace(
					['04', '4', '03', '3', '2', 'Monday', 'Mon', 'March', 'Mar'],
					['dd', 'd', 'mm', 'm', 'y', 'Day', 'Day', 'Month', 'Mmm'],
					$row[0]);
		
				$details['label'] = $example. ' ('. $ddmmyyy. ')';
			} else {
				$details['label'] = $example;
			}
		}
	}








	
	//Make a sentence from the output of getPluginInstanceUsage
	//optionally, if an instanceId is parsed in $usage, it will display the plugin at the start.
	public static function getUsageText($usage, $usageLinks = [], $fullPath = null) {
		$usageText = [];
		
		//If this isn't full mode, make sure all Organizer links use the full path. Otherwise we can just use a #.
		if (is_null($fullPath)) {
			$fullPath = !empty($_GET['_quick_mode']) || !empty($_GET['_select_mode']);
		}
		
		if ($fullPath) {
			$prefix = \ze\link::absolute(). 'zenario/admin/organizer.php#';
		} else {
			$prefix = '#';
		}
		
		
		$includeLinks = $usageLinks !== false;
		
		
		
		//Check if this links to any plugins
		if (!empty($usage['plugins'])) {
			$instanceId = $usage['plugin'];
			$name = \ze\plugin::codeName($instanceId);
			
			if ($includeLinks) {
				$link = 'zenario__modules/panels/plugins//'. (int) $instanceId; 
				$name =
					'<a target="_blank" href="'. htmlspecialchars($prefix. $link). '">
						<span class="listicon organizer_item_image plugin_album_instance">
						</span>'. htmlspecialchars($name). '</a>';
			}
			
			//Add other item text
			$count = $usage['plugins'];
			if ($count > 1) {
				if (isset($usageLinks['plugins'])) {
					$text = \ze\admin::nphrase(
						'[[name]] and <a target="_blank" href="[[plugins]]">1 other plugin</a>', 
						'[[name]] and <a target="_blank" href="[[plugins]]">[[count]] other plugins</a>', 
						$count - 1, 
						['name' => $name, 'plugins' => $prefix. $usageLinks['plugins']]
					);
				} else {
					$text = \ze\admin::nphrase(
						'[[name]] and 1 other plugin', 
						'[[name]] and [[count]] other plugins',
						$count - 1, 
						['name' => $name]
					);
				}
			} else {
				$text = $name;
			}
			$usageText[] = $text;
		}
		
		
		//Check if this links to any nests
		if (!empty($usage['nests'])) {
			$instanceId = $usage['nest'];
			$name = \ze\plugin::codeName($instanceId, 'zenario_plugin_nest');
			
			if ($includeLinks) {
				$link = 'zenario__modules/panels/plugins/refiners/nests////'. (int) $instanceId;
				$name =
					'<a target="_blank" href="'. htmlspecialchars($prefix. $link). '">
						<span class="listicon organizer_item_image plugin_album_instance">
						</span>'. htmlspecialchars($name). '</a>';
			}
			
			//Add other item text
			$count = $usage['nests'];
			if ($count > 1) {
				if (isset($usageLinks['nests'])) {
					$text = \ze\admin::nphrase(
						'[[name]] and <a target="_blank" href="[[nests]]">1 other nest</a>', 
						'[[name]] and <a target="_blank" href="[[nests]]">[[count]] other nests</a>', 
						$count - 1, 
						['name' => $name, 'nests' => $prefix. $usageLinks['nests']]
					);
				} else {
					$text = \ze\admin::nphrase(
						'[[name]] and 1 other nest', 
						'[[name]] and [[count]] other nests',
						$count - 1, 
						['name' => $name]
					);
				}
			} else {
				$text = $name;
			}
			$usageText[] = $text;
		}
		
		
		//Check if this links to any slideshows
		if (!empty($usage['slideshows'])) {
			$instanceId = $usage['slideshow'];
			$name = \ze\plugin::codeName($instanceId, 'zenario_slideshow');
			
			if ($includeLinks) {
				$link = 'zenario__modules/panels/plugins/refiners/slideshows////'. (int) $instanceId;
				$name =
					'<a target="_blank" href="'. htmlspecialchars($prefix. $link). '">
						<span class="listicon organizer_item_image plugin_album_instance">
						</span>'. htmlspecialchars($name). '</a>';
			}
			
			//Add other item text
			$count = $usage['slideshows'];
			if ($count > 1) {
				if (isset($usageLinks['slideshows'])) {
					$text = \ze\admin::nphrase(
						'[[name]] and <a target="_blank" href="[[slideshows]]">1 other slideshow</a>', 
						'[[name]] and <a target="_blank" href="[[slideshows]]">[[count]] other slideshows</a>', 
						$count - 1, 
						['name' => $name, 'slideshows' => $prefix. $usageLinks['slideshows']]
					);
				} else {
					$text = \ze\admin::nphrase(
						'[[name]] and 1 other slideshow', 
						'[[name]] and [[count]] other slideshows',
						$count - 1, 
						['name' => $name]
					);
				}
			} else {
				$text = $name;
			}
			$usageText[] = $text;
		}
		
		
		
		//Check if this links to any content items
		if (!empty($usage['content_items'])) {
			$name = \ze\content::formatTagFromTagId($usage['content_item']);
			
			//Show a link to the content item
			if ($includeLinks) {
				$cID = $cType = false;
				\ze\content::getCIDAndCTypeFromTagId($cID, $cType, $usage['content_item']);
				
				if ($citem = \ze\sql::fetchAssoc('
					SELECT alias, equiv_id, language_id, status
					FROM '. DB_PREFIX. 'content_items
					WHERE type = \''. \ze\escape::sql($cType). '\'
					  AND id = '. (int) $cID
				)) {
					$name = 
						'<a target="_blank" href="'. htmlspecialchars(\ze\link::toItem($cID, $cType, true, '', $citem['alias'], false, false, $citem['equiv_id'], $citem['language_id'])). '">
							<span class="listicon organizer_item_image '. \ze\contentAdm::getItemIconClass($cID, $cType, true, $citem['status']). '">
							</span>'. htmlspecialchars(\ze\content::formatTag($cID, $cType, $citem['alias'], $citem['language_id'])). '
						</a>';
				} else {
					$name = htmlspecialchars($usage['content_item']);
				}
			}
			
			//Add other item text
			$count = $usage['content_items'];
			if ($count > 1) {
				if (isset($usageLinks['content_items'])) {
					$text = \ze\admin::nphrase(
						'[[name]] and <a target="_blank" href="[[content_items]]">1 other content item</a>', 
						'[[name]] and <a target="_blank" href="[[content_items]]">[[count]] other content items</a>',
						$count - 1, 
						['name' => $name, 'content_items' => $prefix. $usageLinks['content_items']]
					);
				} else {
					$text = \ze\admin::nphrase(
						'[[name]] and 1 other content item', 
						'[[name]] and [[count]] other content items', 
						$count - 1, 
						['name' => $name]
					);
				}
			} else {
				$text = $name;
			}
			$usageText[] = $text;
		}
		
		
		//Check if this links to any layouts
		if (!empty($usage['layouts'])) {
			$layoutId = $usage['layout'];
			$name = \ze\layoutAdm::codeName($layoutId);
			
			//Show a link to the layout in organizer
			if ($includeLinks) {
				$link = 'zenario__layouts/panels/layouts//'. (int) $layoutId;
				$name =
					'<a target="_blank" href="'. htmlspecialchars($prefix. $link). '">
						<span
							class="listicon organizer_item_image template"
							style="background-image: url(\''. htmlspecialchars(\ze\link::absolute(). 'zenario/admin/grid_maker/ajax.php?thumbnail=1&width=14&height=16&loadDataFromLayout='. $layoutId). '\');"
						></span>'. htmlspecialchars($name). '</a>';
			}
			
			//Add other item text
			$count = $usage['layouts'];
			if ($count > 1) {
				if (isset($usageLinks['layouts'])) {
					$text = \ze\admin::nphrase(
						'[[name]] and <a target="_blank" href="[[layouts]]">1 other layout</a>', 
						'[[name]] and <a target="_blank" href="[[layouts]]">[[count]] other layouts</a>', 
						$count - 1, 
						['name' => $name, 'layouts' => $prefix. $usageLinks['layouts']]
					);
				} else {
					$text = \ze\admin::nphrase(
						'[[name]] and 1 other layout', 
						'[[name]] and [[count]] other layouts', 
						$count - 1, 
						['name' => $name]
					);
				}
			} else {
				$text = $name;
			}
			$usageText[] = $text;
		}
		
		
		//Check if this links to any menu nodes
		if (!empty($usage['menu_nodes'])) {
			$menuNodeId = $usage['menu_node'];
			$name = $menuNodeId;
			
			//Look up details on this menu node.
			$menuDetails = \ze\sql::fetchAssoc('
				SELECT mn.section_id, mn.target_loc, mn.equiv_id, mn.redundancy, mn.parent_id, mt.name, mt.ext_url
				FROM '. DB_PREFIX. 'menu_nodes AS mn
				INNER JOIN '. DB_PREFIX. 'menu_text AS mt
				   ON mn.id = mt.menu_id
				WHERE mn.id = '. (int) $menuNodeId. '
				ORDER BY mt.language_id = \''. \ze\escape::sql(\ze::$defaultLang). '\'
				LIMIT 1'
			);
			
			if ($menuDetails) {
				//Check if any children exist, as this changes the menu node's icon slightly
				$menuDetails['children'] = \ze\sql::numRows('
					SELECT 1
					FROM '. DB_PREFIX. 'menu_hierarchy
					WHERE ancestor_id = '. (int) $menuNodeId. '
					LIMIT 1
				');
				
				//Work out how many arrows to show before the name to show what level this menu node is at
				$menuLevel = \ze\sql::numRows('
					SELECT MAX(separation)
					FROM '. DB_PREFIX. 'menu_hierarchy
					WHERE ancestor_id = '. (int) $menuNodeId
				);
				
				$name = $menuDetails['name'];
				if ($menuLevel) {
					$name = str_repeat(' -> ', $menuLevel). $name;
				}
				
				//Show a link to the menu node in organizer
				if ($includeLinks) {
					$link = \ze\menuAdm::organizerLink($menuNodeId, true, $menuDetails['section_id']);
					$name =
						'<a target="_blank" href="'. htmlspecialchars($prefix. $link). '">
							<span
								class="listicon organizer_item_image '. \ze\menuAdm::cssClass($menuDetails). '"
							></span>'. htmlspecialchars($name). '</a>';
				}
			}
			
			
			//Add other item text
			$count = $usage['menu_nodes'];
			if ($count > 1) {
				if (isset($usageLinks['menu_nodes'])) {
					$text = \ze\admin::nphrase(
						'[[name]] and <a target="_blank" href="[[menu_nodes]]">1 other menu node</a>', 
						'[[name]] and <a target="_blank" href="[[menu_nodes]]">[[count]] other menu nodes</a>', 
						$count - 1, 
						['name' => $name, 'menu_nodes' => $prefix. $usageLinks['menu_nodes']]
					);
				} else {
					$text = \ze\admin::nphrase(
						'[[name]] and 1 other menu node', 
						'[[name]] and [[count]] other menu nodes', 
						$count - 1, 
						['name' => $name]
					);
				}
			} else {
				$text = $name;
			}
			$usageText[] = $text;
		}
		
		
		//Check if this links to any email templates
		if (!empty($usage['email_templates']) && \ze\module::inc('zenario_email_template_manager')) {
			$etId = $usage['email_template'];
			
			if (!is_numeric($etId)) {
				$et = \ze\row::get('email_templates', ['code', 'template_name'], ['code' => $etId]);
			} else {
				$et = \ze\row::get('email_templates', ['code', 'template_name'], ['id' => $etId]);
			}
			$name = $et['template_name'];
			
			//Show a link to the email template in organizer
			if ($includeLinks) {
				$link = 'zenario__email_template_manager/panels/email_templates//'. $et['code'];
				$name =
					'<a target="_blank" href="'. htmlspecialchars($prefix. $link). '">
						<span
							class="listicon organizer_item_image zenario_email_template_panel"
						></span>'. htmlspecialchars($name). '</a>';
			}
			
			//Add other item text
			$count = $usage['email_templates'];
			if ($count > 1) {
				if (isset($usageLinks['email_templates'])) {
					$text = \ze\admin::nphrase(
						'[[name]] and <a target="_blank" href="[[email_templates]]">1 other email template</a>', 
						'[[name]] and <a target="_blank" href="[[email_templates]]">[[count]] other email templates</a>', 
						$count - 1, 
						['name' => $name, 'email_templates' => $prefix. $usageLinks['email_templates']]
					);
				} else {
					$text = \ze\admin::nphrase(
						'[[name]] and 1 other email template', 
						'[[name]] and [[count]] other email templates', 
						$count - 1, 
						['name' => $name]
					);
				}
			} else {
				$text = $name;
			}
			$usageText[] = $text;
		}
		
		
		//Check if this links to any newsletters
		if (!empty($usage['newsletters']) && \ze\module::inc('zenario_newsletter')) {
			$nlId = $usage['newsletter'];
			$nl = \ze\row::get(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', ['newsletter_name', 'status'], $nlId);
			$name = $nl['newsletter_name'];
			
			switch ($nl['status']) {
				case '_DRAFT':
					$iconCSS = 'zenario_newsletter_draft';
					$link = 'zenario__email_template_manager/panels/newsletters//'. (int) $nlId;
					break;
				case '_IN_PROGRESS':
					$iconCSS = 'zenario_newsletter_in_progress_newsletter';
					$link = 'zenario__email_template_manager/panels/newsletters/collection_buttons/process////'. (int) $nlId;
					break;
				case '_ARCHIVED':
					$iconCSS = 'zenario_newsletter_sent_newsletter';
					$link = 'zenario__email_template_manager/panels/newsletters/collection_buttons/archive////'. (int) $nlId;
					break;
			}
			
			//Show a link to the newsletter in organizer
			if ($includeLinks) {
				$name =
					'<a target="_blank" href="'. htmlspecialchars($prefix. $link). '">
						<span
							class="listicon organizer_item_image '. $iconCSS. '"
						></span>'. htmlspecialchars($name). '</a>';
			}
			
			//Add other item text
			$count = $usage['newsletters'];
			if ($count > 1) {
				if (isset($usageLinks['newsletters'])) {
					$text = \ze\admin::nphrase(
						'[[name]] and <a target="_blank" href="[[newsletters]]">1 other newsletter</a>', 
						'[[name]] and <a target="_blank" href="[[newsletters]]">[[count]] other newsletters</a>', 
						$count - 1, 
						['name' => $name, 'newsletters' => $prefix. $usageLinks['newsletters']]
					);
				} else {
					$text = \ze\admin::nphrase(
						'[[name]] and 1 other newsletter', 
						'[[name]] and [[count]] other newsletters', 
						$count - 1, 
						['name' => $name]
					);
				}
			} else {
				$text = $name;
			}
			$usageText[] = $text;
		}
		
		
		//Check if this links to any newsletter templates
		if (!empty($usage['newsletter_templates']) && \ze\module::inc('zenario_newsletter')) {
			$nlId = $usage['newsletter_templates'];
			$name = \ze\row::get(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', 'name', $nlId);
			
			//Show a link to the template in organizer
			if ($includeLinks) {
				$link = 'zenario__email_template_manager/panels/newsletters/collection_buttons/newletter_templates////'. (int) $nlId;
				$name =
					'<a target="_blank" href="'. htmlspecialchars($prefix. $link). '">
						<span
							class="listicon organizer_item_image zenario_newsletter_template"
						></span>'. htmlspecialchars($name). '</a>';
			}
			
			//Add other item text
			$count = $usage['newsletter_templates'];
			if ($count > 1) {
				if (isset($usageLinks['newsletter_templates'])) {
					$text = \ze\admin::nphrase(
						'[[name]] and <a target="_blank" href="[[newsletter_templates]]">1 other newsletter template</a>', 
						'[[name]] and <a target="_blank" href="[[newsletter_templates]]">[[count]] other newsletter templates</a>', 
						$count - 1, 
						['name' => $name, 'newsletter_templates' => $prefix. $usageLinks['newsletter_templates']]
					);
				} else {
					$text = \ze\admin::nphrase(
						'[[name]] and 1 other newsletter template', 
						'[[name]] and [[count]] other newsletter templates', 
						$count - 1, 
						['name' => $name]
					);
				}
			} else {
				$text = $name;
			}
			$usageText[] = $text;
		}
		
		
		
		if (empty($usageText)) {
			return [\ze\admin::phrase('Not used')];
		} else {
			return $usageText;
		}
	}











	//Generate a hierarchical select list from a table with a parent_id column
	//Formerly "generateHierarchicalSelectList()"
	public static function generateHierarchicalSelectList($table, $labelCol, $parentIdCol = 'parent_id', $ids = [], $orderBy = [], $flat = false, $parentId = 0, $pad = '    ') {
		$output = [];
		$cols = [$labelCol, $parentIdCol];
		\ze\miscAdm::generateHierarchicalListR($output, $table, $cols, $parentIdCol, $ids, $orderBy, $parentId, 0);
	
		$ord = 0;
		foreach ($output as &$row) {
			$row = [
				'ord' => ++$ord,
				'label' => str_repeat($pad, $row['level']). $row[$labelCol]];
		
			if ($flat) {
				$row = $row['label'];
			}
		}
	
		return $output;
	}

	//Generate a hierarchical list from a table with a parent_id column
	//Formerly "generateHierarchicalList()"
	public static function generateHierarchicalList($table, $cols = [], $parentIdCol = 'parent_id', $ids = [], $orderBy = [], $parentId = 0) {
		$output = [];
	
		if (!is_array($cols)) {
			$cols = [$cols];
		}
		if (!in_array($parentIdCol, $cols)) {
			$cols[] = $parentIdCol;
		}
	
		\ze\miscAdm::generateHierarchicalListR($output, $table, $cols, $parentIdCol, $ids, $orderBy, $parentId, 0);
	
		return $output;
	}

	//Formerly "generateHierarchicalListR()"
	public static function generateHierarchicalListR(&$output, $table, $cols, $parentIdCol, $ids, $orderBy, $parentId, $level) {
	
		$ids[$parentIdCol] = $parentId;
	
		foreach (\ze\row::getAssocs($table, $cols, $ids, $orderBy) as $id => $row) {
			//This line in theory shouldn't be needed if the data integrety is good,
			//but I have included it to stop infinite loops if it is not!
			if (!isset($output[$id])) {
				$row['level'] = $level;
				$output[$id] = $row;
			
				\ze\miscAdm::generateHierarchicalListR($output, $table, $cols, $parentIdCol, $ids, $orderBy, $id, $level + 1);
			}
		}
	
	}

	//Formerly "generateDocumentFolderSelectList()"
	public static function generateDocumentFolderSelectList($flat = false) {
		return \ze\miscAdm::generateHierarchicalSelectList('documents', 'folder_name', 'folder_id', ['type' => 'folder'], 'ordinal', $flat);
	}



	//Formerly "exportPanelItems()"
	public static function exportPanelItems($headers, $rows, $format = 'csv', $filename = 'export') {
		$filename = str_replace('"', '\'', str_replace('/', ' ', $filename));
		// Export as CSV file
		if ($format == 'csv') {
			// Create temp file to write CSV to
			$filepath = tempnam(sys_get_temp_dir(), 'tmpexportfile');
			$f = fopen($filepath, 'wb');
		
			// Write column headers and data
			fputcsv($f, $headers);
			foreach ($rows as $row) {
				fputcsv($f, $row);
			}
		
			// Output file
			header('Content-Type: text/x-csv');
			header('Content-Disposition: attachment; filename="'. $filename .'.csv"');
			header('Content-Length: '. filesize($filepath));
			readfile($filepath);
		
			// Remove file from temp directory
			@unlink($filepath);
		// Export as excel file
		} elseif ($format == 'excel') {
			require_once CMS_ROOT.'zenario/libs/manually_maintained/lgpl/PHPExcel/Classes/PHPExcel.php';
			// Create excel object
			$objPHPExcel = new \PHPExcel();
			// Write headers and data
			$objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
			$objPHPExcel->getActiveSheet()->fromArray($rows, NULL, 'A2');
			// Output file
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
			$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
		}
	}

	//Formerly "setupSlideDestinations()"
	public static function setupSlideDestinations(&$box, &$fields, &$values) {
		//Check if this plugin is in a slide in a conductor
		$box['key']['conductorState'] = '';
		if ($box['key']['usesConductor'] && $box['key']['slideNum']) {
	
			//Try and find the current state or states
			$slide = \ze\row::get('nested_plugins', ['states', 'show_back'], [
				'instance_id' => $box['key']['instanceId'],
				'slide_num' => $box['key']['slideNum'],
				'is_slide' => 1
			]);
	
			$currentStates = \ze\ray::explodeAndTrim($slide['states']);
			if (!empty($currentStates[0])) {
				$box['key']['conductorState'] = $currentStates[0];
		
				//Fill the list of slides
				$result = \ze\sql::select("
					SELECT id, slide_num, states, name_or_title
					FROM ". DB_PREFIX. "nested_plugins
					WHERE instance_id = ". (int) $box['key']['instanceId']. "
					  AND slide_num != ". (int) $box['key']['slideNum']. "
					  AND is_slide = 1");
	
				$ord = 0;
				while ($row = \ze\sql::fetchAssoc($result)) {
		
					$states = \ze\ray::explodeAndTrim($row['states']);
		
					foreach ($states as $state) {
						$row['state'] = $state;
						$box['lovs']['slides_and_states'][$state] = [
							'ord' => ++$ord,
							'label' => \ze\admin::phrase('Slide [[slide_num]][[state]]: [[name_or_title]]', $row)
						];
					}
				}
		
				//Load the existing destination for each path on this slide.
				$sql = "
					SELECT from_state, to_state, equiv_id, content_type, command
					FROM ". DB_PREFIX. "nested_paths
					WHERE from_state IN (". \ze\escape::in($currentStates). ")
					  AND instance_id = ". (int) $box['key']['instanceId']. "
					ORDER BY command, from_state";
		
				$i = 0;
				$lastTo = '';
				$lastCommand = '';
				$result = \ze\sql::select($sql);
				while ($row = \ze\sql::fetchAssoc($result)) {
				
					//Note that there may be more than one, in the case of multiple states. When this happens,
					//we'll show no more than two.
					if ($lastCommand != $row['command']) {
						$lastCommand = $row['command'];
						$i = 1;
					} else {
						//If both alternate states link to the same place, don't show it twice
						if ($lastTo == $row['content_type']. $row['equiv_id']. $row['to_state']) {
							continue;
				
						//Show at most two alternate states (as there is a limit of two fields per command)
						} elseif ($i < 2) {
							++$i;
						} else {
							continue;
						}
					}
			
					if (isset($fields['to_state'. $i. '.'. $row['command']])) {
						$fields['to_state'. $i. '.'. $row['command']]['hidden'] = false;
					
						if ($row['equiv_id']) {
							$codeName = $row['content_type']. '_'. $row['equiv_id']. '/'. $row['to_state'];
						
							$box['lovs']['slides_and_states'][$codeName] = \ze\content::formatTag($row['equiv_id'], $row['content_type'], -1, false, true);
						
							$values['to_state'. $i. '.'. $row['command']] = $codeName;
						} else {
							$values['to_state'. $i. '.'. $row['command']] = $row['to_state'];
						}
					
						$lastTo = $row['content_type']. $row['equiv_id']. $row['to_state'];
					}
				}
		
				//If the back button is enabled in the slide properties, show it as a read-only checkbox
				if (isset($fields['enable.back'])) {
					$values['enable.back'] = $slide['show_back'];
				}
		
				//If the back button has a path but the submit button doesn't have a path,
				//default the path to that of the back button
				if (isset($fields['enable.submit'])
				 && empty($values['to_state1.submit'])
				 && !empty($values['to_state1.back'])) {
					$values['to_state1.submit'] = $values['to_state1.back'];
				}
		
				//Loop through all of the command-fields, looking for any that didn't get values, and mark them with a warning symbol
				foreach ($fields as $key => &$field) {
					if (isset($field['values'])
					 && $field['values'] === 'slides_and_states'
					 && strpos($key, '/') === false //n.b. this line is because fields appear in the $fields shortcut-array twice
					 && empty($values[$key])
					 && empty($field['hidden'])
					 && \ze\ring::chopPrefix('to_state', $key)
					) {
						$field['css_class'] .= ' zfab_warning';
					}
				}
			}
		}
	}

	//Formerly "checkScheduledTaskRunning()"
	public static function checkScheduledTaskRunning($jobName) {
		return \ze\module::inc('zenario_scheduled_task_manager') && zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName, true);
	}






	//Update the values stored for something (e.g. a content item) in a linking table
	//Formerly "updateLinkingTable()"
	public static function updateLinkingTable($table, $key, $idCol, $ids = []) {
	
		if (!is_array($ids)) {
			$ids = \ze\ray::explodeAndTrim($ids);
		}
	
		//Delete anything that wasn't picked from the linking table
		//E.g. \ze\row::delete('group_content_link', ['equiv_id' => 42, 'content_type' => 'test', 'group_id' => ['!' => [1, 2, 3, 4, 5, 6, 7, 8]]]);
		$key[$idCol] = ['!' => $ids];
		\ze\row::delete($table, $key);
	
		//Make sure each new row exists
		foreach ($ids as $id) {
			$key[$idCol] = $id;
			\ze\row::set($table, [], $key);
		}
	}
	
	
	
	
	
	
	
	

	//Formerly "checkForChangesInYamlFiles()"
	public static function checkForChangesInYamlFiles($forceScan = false) {
	
		//Safety catch - do not try to do anything if there is no database connection!
		if (!\ze::$dbL) {
			return;
		}
	
		//Make sure we are in the CMS root directory.
		//This should already be done, but I'm being paranoid...
		chdir(CMS_ROOT);
	
		$time = time();
		$zenario_version = \ze\site::versionNumber();
	
		//Catch the case where someone just updated to a different version of the CMS
		if ($zenario_version != \ze::setting('zenario_version')) {
			//Clear everything that was cached if this has happened
			\ze\site::setSetting('css_js_html_files_last_changed', '');
			\ze\site::setSetting('css_js_version', '');
			$changed = true;
	
		//Get the date of the last time we ran this check and there was a change.
		} elseif (!($lastChanged = (int) \ze::setting('yaml_files_last_changed'))) {
			//If this has never been run before then it must be run now!
			$changed = true;
	
		} elseif ($forceScan) {
			$changed = true;
		
		//T11275: Trigger a rescan if someone changes the site description file.
		} elseif (file_exists(CMS_ROOT. 'zenario_custom/site_description.yaml')
			   && filemtime(CMS_ROOT. 'zenario_custom/site_description.yaml') > $lastChanged) {
			
			//Do a full rescan of everything.
			\ze\skinAdm::clearCache(true);
			
			//N.b. the \ze\skinAdm::clearCache() function includes a call to this one,
			//with $forceScan set to true, so we can stop running the current call.
			return;
	
		//In production mode, only run this check if it looks like there's
		//been a core software update since the last time we ran
		} elseif (\ze::setting('site_mode') == 'production' && \ze\db::codeLastUpdated(false) < $lastChanged) {
			$changed = false;
	
		//Otherwise, work out the time difference between that time and now
		} else {
			$changed = false;
			
			$tuixDirs = \ze::moduleDirs('tuix/');
			$tuixDirs[] = 'zenario/admin/welcome/';
			
			foreach ($tuixDirs as $tuixDir) {
		
				$RecursiveDirectoryIterator = new \RecursiveDirectoryIterator(CMS_ROOT. $tuixDir);
				$RecursiveIteratorIterator = new \RecursiveIteratorIterator($RecursiveDirectoryIterator);
		
				foreach ($RecursiveIteratorIterator as $file) {
					if ($file->isFile()
					 && $file->getMTime() > $lastChanged) {
						$changed = true;
						break 2;
					}
				}
			}
		}
	
	
		if ($changed) {
			
			//Remove all cached calls to \ze\tuix::load() if the YAML files have changed.
			//(I'm not going to the trouble of working out which individual files have changed
			// and selectively removing them, ad that would be too fiddly. This is a complete wipe.
			// But note that the \ze\tuix::readFile() function does use timestamps on a per-file basis for its caching.)
			\ze\cache::deleteDir(CMS_ROOT. 'cache/tuix/admin_boxes');
			\ze\cache::deleteDir(CMS_ROOT. 'cache/tuix/admin_toolbar');
			\ze\cache::deleteDir(CMS_ROOT. 'cache/tuix/organizer');
			\ze\cache::deleteDir(CMS_ROOT. 'cache/tuix/slot_controls');
			\ze\cache::deleteDir(CMS_ROOT. 'cache/tuix/visitor');
			\ze\cache::deleteDir(CMS_ROOT. 'cache/tuix/wizards');
			
			
			//Look to see what datasets are on the system, and which datasets extend which FABs
			$datasets = [];
			$datasetFABs = [];
			foreach (\ze\row::getAssocs('custom_datasets', 'extends_admin_box') as $datasetId => $extends_admin_box) {
				$datasetFABs[$extends_admin_box] = $datasetId;
			}
		
		
			//Scan the TUIX files, and come up with a list of what paths are in what files
			$tuixFiles = [];
			$result = \ze\row::query('tuix_file_contents', true, []);
			while ($tf = \ze\sql::fetchAssoc($result)) {
				$key = $tf['module_class_name']. '/'. $tf['type']. '/'. $tf['filename'];
				$key2 = $tf['path']. '//'. $tf['setting_group'];
		
				if (empty($tuixFiles[$key])) {
					$tuixFiles[$key] = [];
				}
				$tuixFiles[$key][$key2] = $tf;
			}
	
			$contents = [];
			foreach (['admin_boxes', 'admin_toolbar', 'slot_controls', 'organizer', 'visitor', 'wizards'] as $type) {
				foreach (\ze::moduleDirs('tuix/'. $type. '/') as $moduleClassName => $dir) {
			
					foreach (scandir($dir) as $file) {
						if (substr($file, 0, 1) != '.') {
							$key = $moduleClassName. '/'. $type. '/'. $file;
							$filemtime = null;
							$md5_file = null;
							$changes = true;
							$first = true;
					
							//Check the modification time and the checksum of the file. If either are the same as before,
							//there's no need to update this row.
							if (!empty($tuixFiles[$key])) {
								foreach ($tuixFiles[$key] as $key2 => &$tf) {
							
									//Note that this is an array of arrays, but I only need to check the first one
									if ($first) {
										$filemtime = filemtime($dir. $file);
								
										if ($tf['last_modified'] == $filemtime) {
											$changes = false;
								
										} else {
											$md5_file = md5_file($dir. $file);
									
											if ($tf['checksum'] == $md5_file) {
												$changes = false;
											}
										}
									}
							
									//Note that this is an array of arrays, but I only need to check the first one
									if (!$changes) {
										$tf['status'] = 'unchanged';
									}
								}
								unset($tf);
						
								if (!$changes) {
									continue;
								}
							} else {
								$tuixFiles[$key] = [];
							}
					
							//If there have been changes, or if this is the first time we've seen this file,
							//read it, then loop through it looking for all of the TUIX paths it contains
								//Note that as we know there are changes, I'm overriding the normal timestamp logic in \ze\tuix::readFile()
							if (($tags = \ze\tuix::readFile($dir. $file, false))
							 && (!empty($tags))
							 && (is_array($tags))) {
						
								if ($filemtime === null) {
									$filemtime = filemtime($dir. $file);
								}
								if ($md5_file === null) {
									$md5_file = md5_file($dir. $file);
								}
						
								$pathsFound = false;
								if ($type == 'organizer') {
									$paths = [];
									\ze\tuix::logFileContentsR($paths, $tags, $type);
							
									foreach ($paths as $path => $panelType) {
										$pathsFound = true;
										$settingGroup = '';
								
										$key2 = $path. '//'. $settingGroup;
										$tuixFiles[$key][$key2] = [
											'type' => $type,
											'path' => $path,
											'panel_type' => $panelType,
											'setting_group' => $settingGroup,
											'module_class_name' => $moduleClassName,
											'filename' => $file,
											'last_modified' => $filemtime,
											'checksum' => $md5_file,
											'status' => empty($tuixFiles[$key][$key2])? 'new' : 'updated'
										];
									}
								}
						
								if (!$pathsFound) {
									//For anything else, just read the top-level path
									//Note - also do this for Organizer if no paths were found above,
									//as \ze\tuix::logFileContentsR() will miss files that have navigation definitions but no panel definitions
									foreach ($tags as $path => &$tag) {
								
										$settingGroup = '';
										if ($type == 'admin_boxes') {
											if ($path == 'plugin_settings' && !empty($tag['module_class_name'])) {
												$settingGroup = $tag['module_class_name'];
									
											} elseif ($path == 'site_settings' && !empty($tag['setting_group'])) {
												$settingGroup = $tag['setting_group'];
										
											//Note down if we see any changes in a file for a FAB
											//that is used for a dataset.
											} elseif (!empty($datasetFABs[$path])) {
												$datasets[$datasetFABs[$path]] = $datasetFABs[$path];
											}
								
										} elseif ($type == 'slot_controls') {
											if (!empty($tag['module_class_name'])) {
												$settingGroup = $tag['module_class_name'];
											}
										}
								
										$key2 = $path. '//'. $settingGroup;
										$tuixFiles[$key][$key2] = [
											'type' => $type,
											'path' => $path,
											'panel_type' => '',
											'setting_group' => $settingGroup,
											'module_class_name' => $moduleClassName,
											'filename' => $file,
											'last_modified' => $filemtime,
											'checksum' => $md5_file,
											'status' => empty($tuixFiles[$key][$key2])? 'new' : 'updated'
										];
									}
								}
							}
							unset($tags);
						}
					}
				}
			}
		
		
		
			//Loop through the array we've generated, and take actions as appropriate
			foreach ($tuixFiles as $key => &$tuixFile) {
				foreach ($tuixFile as $key2 => $tf) {
			
					//Where we could no longer find files, delete them
					if (empty($tf['status'])) {
						$sql = "
							DELETE FROM ". DB_PREFIX. "tuix_file_contents
							WHERE type = '". \ze\escape::sql($tf['type']). "'
							  AND path = '". \ze\escape::sql($tf['path']). "'
							  AND setting_group = '". \ze\escape::sql($tf['setting_group']). "'
							  AND module_class_name = '". \ze\escape::sql($tf['module_class_name']). "'
							  AND filename = '". \ze\escape::sql($tf['filename']). "'";
						\ze\sql::cacheFriendlyUpdate($sql);
			
					//Add/update newly added/edited files
					} else if ($tf['status'] != 'unchanged') {
						$sql = "
							INSERT INTO ". DB_PREFIX. "tuix_file_contents
							SET type = '". \ze\escape::sql($tf['type']). "',
								path = '". \ze\escape::sql($tf['path']). "',
								panel_type = '". \ze\escape::sql($tf['panel_type']). "',
								setting_group = '". \ze\escape::sql($tf['setting_group']). "',
								module_class_name = '". \ze\escape::sql($tf['module_class_name']). "',
								filename = '". \ze\escape::sql($tf['filename']). "',
								last_modified = ". (int) $tf['last_modified']. ",
								checksum = '". \ze\escape::sql($tf['checksum']). "'
							ON DUPLICATE KEY UPDATE
								panel_type = VALUES(panel_type),
								last_modified = VALUES(last_modified),
								checksum = VALUES(checksum)";
						\ze\sql::cacheFriendlyUpdate($sql);
					}
				}
			}
		
			//Rescan the TUIX files for any datasets that have changed
			foreach ($datasets as $datasetId) {
				\ze\miscAdm::saveSystemFieldsFromTUIX($datasetId);
			}
		
		
			\ze\site::setSetting('yaml_files_last_changed', $time);
			\ze\site::setSetting('yaml_version', base_convert($time, 10, 36));
			\ze\site::setSetting('zenario_version', $zenario_version);
		}
	}
	
	
	
	

	//Formerly "saveSystemFieldsFromTUIX()"
	public static function saveSystemFieldsFromTUIX($datasetId) {
		$dataset = \ze\dataset::details($datasetId);
		//If this extends a system admin box, load the system tabs and fields
		if ($dataset['extends_admin_box']
		 && \ze\row::exists('tuix_file_contents', ['type' => 'admin_boxes', 'path' => $dataset['extends_admin_box']])) {
			$moduleFilesLoaded = [];
			$tags = [];
		
			\ze\tuix::load(
				$moduleFilesLoaded, $tags, $type = 'admin_boxes', $dataset['extends_admin_box'],
				$settingGroup = '', $compatibilityClassNames = false, $runningModulesOnly = false, $exitIfError = true
			);
		
			if (!empty($tags[$dataset['extends_admin_box']]['tabs'])
				 && is_array($tags[$dataset['extends_admin_box']]['tabs'])) {
				$tabCount = 0;
				foreach ($tags[$dataset['extends_admin_box']]['tabs'] as $tabName => $tab) {
					if (is_array($tab) && (!empty($tab['label']) || !empty($tab['dataset_label']))) {
						++$tabCount;
						$tabDetails = \ze\row::get('custom_dataset_tabs', true, ['dataset_id' => $datasetId, 'name' => $tabName]);
						$values = [
							'is_system_field' => 1,
							'default_label' => \ze::ifNull($tab['dataset_label'] ?? false, ($tab['label'] ?? false), '')
						];
						if (!$tabDetails || !$tabDetails['ord']) {
							$values['ord'] = (float)(($tab['ord'] ?? false) ?: $tabCount);
						}
						\ze\row::set('custom_dataset_tabs', 
							$values,
							[
								'dataset_id' => $datasetId, 
								'name' => $tabName]);
						if (!empty($tab['fields'])
							 && is_array($tab['fields'])) {
							$fieldCount = 0;
							foreach ($tab['fields'] as $fieldName => $field) {
								if (is_array($field)) {
									++$fieldCount;
								
									$fieldDetails = \ze\row::get('custom_dataset_fields', true, ['dataset_id' => $datasetId, 'tab_name' => $tabName, 'is_system_field' => 1, 'field_name' => $fieldName]);
									$values = [
										'default_label' => \ze::ifNull($field['dataset_label'] ?? false, ($field['label'] ?? false), ''),
										'is_system_field' => 1,
										'allow_admin_to_change_visibility' => !empty($field['allow_admin_to_change_visibility']),
										'allow_admin_to_change_export' => !empty($field['allow_admin_to_change_export'])
									];
									if (!$fieldDetails || !$fieldDetails['ord']) {
										$values['ord'] = (float) (($field['ord'] ?? false) ?: $fieldCount);
									}
									\ze\row::set('custom_dataset_fields',
										$values,
										[
											'dataset_id' => $datasetId, 
											'tab_name' => $tabName, 
											'field_name' => $fieldName]);
								}
							}
						}
					}
				}
			}
		}
	}

}