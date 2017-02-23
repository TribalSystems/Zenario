<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

/*
	This file is used to handle AJAX requests for Organizer.
	It reads all relevant yaml files, then merge them together into a PHP array, calls module methods to process
	that array, and then finally sends them via JSON to the client.
	
	It's main features are:
		Generating a complete "Map" of everything in Organizer
		"Focusing" on a specific Panel in Organizer, and sending detailed information on that
	
	It can also output data in XML format, e.g. for generating a site map
*/

require '../visitorheader.inc.php';
require CMS_ROOT. 'zenario/includes/admin.inc.php';
require CMS_ROOT. 'zenario/includes/tuix.inc.php';
useGZIP();

//Add the admin id and type up as constants
define('ADMIN_ID', (int) session('admin_userid'));


$mode = false;
$tagPath = '';
$modules = array();
$debugMode = (bool) get('_debug');
$customJoin = false;
$organizerQueryIds = false;
$organizerQueryDetails = false;
$loadDefinition = true;
$settingGroup = '';
$compatibilityClassNames = array();
cms_core::$skType = $type = 'organizer';

//Work out which mode this should be for Organizer
if (get('_xml') || get('method_call') == 'showSitemap') {
	define('ORGANIZER_MODE', $mode = 'xml');
} elseif (get('_select_mode')) {
	define('ORGANIZER_MODE', $mode = 'select');
} elseif (get('_quick_mode')) {
	define('ORGANIZER_MODE', $mode = 'quick');
} elseif (get('_typeahead_search')) {
	define('ORGANIZER_MODE', $mode = 'typeahead_search');
} elseif (get('_get_item_name')) {
	define('ORGANIZER_MODE', $mode = 'get_item_name');
} elseif (!empty($_REQUEST['_get_item_links'])) {
	define('ORGANIZER_MODE', $mode = 'get_item_links');
} elseif (get('_get_item_data')) {
	define('ORGANIZER_MODE', $mode = 'get_item_data');
} elseif (get('_get_matched_ids')) {
	define('ORGANIZER_MODE', $mode = 'get_matched_ids');
} else {
	define('ORGANIZER_MODE', $mode = 'full');
}

//Always require Admin Permissions, except for Organizer which has a feature where feeds from some panels can be made public
if ($mode != 'xml') {
	if (!checkPriv()) {
		header('Zenario-Admin-Logged_Out: 1');
		echo '<!--Logged_Out-->', adminPhrase('You have been logged out.');
		exit;
	}
}


define('FOCUSED_LANGUAGE_ID__NO_QUOTES', ifNull(ifNull(request('languageId'), get('refiner__language')), setting('default_language'), 'en'));
define('FOCUSED_LANGUAGE_ID', "'". sqlEscape(FOCUSED_LANGUAGE_ID__NO_QUOTES). "'");





//See if there is a requested path.
$requestedPath = false;
if (get('method_call') == 'showSitemap') {
	$requestedPath = 'zenario__content/hidden_nav/sitemap/panel';

} elseif (request('path')) {
	$requestedPath = preg_replace('/[^\w\/]/', '', request('path'));
}
cms_core::$skPath = $requestedPath;

class zenario_organizer {
	
	public static $filters = array();
	
	public static function filterValue($columnName) {
	
		//Return null if the filter was never set
		if (!isset(zenario_organizer::$filters[$columnName])) {
			return null;
	
		//Return false if the filter was set before, but isn't now
		} elseif (empty(zenario_organizer::$filters[$columnName]['s']) || empty(zenario_organizer::$filters[$columnName]['v'])) {
			return false;
	
		//Otherwise return the value of the filter
		} else {
			return zenario_organizer::$filters[$columnName]['v'];
		}
	}
	
	public static function filterIsNot($columnName) {
		return !empty(zenario_organizer::$filters[$columnName]['not']);
	}
	
	public static function setFilterValue($columnName, $value, $not = false) {
		
		//If a value is specified, set the filter to that value
		//(Or if the value was false, turn the filter off)
		if (!$value) {
			zenario_organizer::$filters[$columnName] = array(
				's' => 0
			);
		} else {
			zenario_organizer::$filters[$columnName] = array(
				's' => 1,
				'v' => $value,
				'not' => $not? 1 : 0
			);
		}
	}

	//Process a table join, and add the words "LEFT JOIN" to the start if they are missing
	public static function noteTableJoin(&$existingJoins, $tableJoin) {
		if (empty($tableJoin)) {
			return;
	
		} elseif (is_array($tableJoin)) {
			foreach ($tableJoin as &$join) {
				zenario_organizer::noteTableJoin($existingJoins, $join);
			}
	
		} else {
			$tableJoin = preg_replace('/\s\s+/', ' ', trim($tableJoin));
			$words = preg_split('/\W+/', strtoupper($tableJoin), 4);
	
			if (arrayKey($words, 1) != 'JOIN'
			 && arrayKey($words, 2) != 'JOIN'
			 && arrayKey($words, 1) != 'STRAIGHT_JOIN'
			 && arrayKey($words, 2) != 'STRAIGHT_JOIN') {
				$tableJoin = 'LEFT JOIN '. $tableJoin;
			}
	
			$existingJoins[$tableJoin] = true;
		}
	}
}

if (get('_filters')) {
	zenario_organizer::$filters = json_decode(get('_filters'), true);
}



if ($loadDefinition) {
	//Scans the Module directory for Modules with the relevant TUIX files, read them, and get a php array
	$moduleFilesLoaded = array();
	$tags = array();
	$originalTags = array();
	loadTUIX($moduleFilesLoaded, $tags, $type, $requestedPath, $settingGroup, $compatibilityClassNames);
	
	
	//If we had a requested path, drill straight down to that level
	if ($requestedPath) {
		
		foreach(explode('/', $requestedPath) as $path) {
			if (isset($tags[$path]) && is_array($tags[$path])) {
				$tags = $tags[$path];
				$tagPath .= '/'. $path;
			
			} else {
				echo adminPhrase('The requested path "[[path]]" was not found in the system. If you have just updated or added files to the CMS, you will need to reload the page.', array('path' => $requestedPath));
				exit;
			}
		}
	}
}

if ($debugMode) {
	$staticTags = $tags;
}

//Always require Admin Permissions, except for Organizer which has a feature where feeds from some panels can be made public
if (!checkPriv() && !($mode == 'xml' && engToBooleanArray($tags, 'xml', 'allow_unauthenticated_xml_access'))) {
	header('Zenario-Admin-Logged_Out: 1');
	echo '<!--Logged_Out-->', adminPhrase('You have been logged out.');
	exit;
}

//For organizer, if there is no path, that means we need to load the top level nav
//(Some basic information on the panels is also included).
if (!$requestedPath || empty($tags['class_name'])) {
	
	//Check which modules have the fill_organizer_nav property set and should be run to customise
	//the top level navigation
	foreach(getRowsArray(
		'modules',
		array('class_name'),
		array('fill_organizer_nav' => 1, 'status' => array('module_running', 'module_is_abstract'))
	) as $moduleWithNav) {
		zenarioAJAXIncludeModule($modules, $moduleWithNav, $type, $requestedPath, $settingGroup);
	}
	foreach ($modules as $className => &$module) {
		$module->fillOrganizerNav($tags);
	}


//If this is a request for a specific path, run that Module and let it manage its output in PHP
} else {
	
	if (isset($tags['priv']) && !checkPriv($tags['priv'])) {
		echo adminPhrase('You do not have permissions to see this Panel.');
		exit;
	}
	
	
	if (!zenarioAJAXIncludeModule($modules, $tags, $type, $requestedPath, $settingGroup)) {
		echo adminPhrase('Could not activate the [[class_name]] Module.', array('class_name' => $tags['class_name']));
		exit;
	}
	
	
	//Add definitions for any refiners supplied in the request
	$refinersPresent = array('refinerId' => 'REFINER_ID', '_combineItem' => 'COMBINE_ITEM');
	foreach ($_REQUEST as $key => $value) {
		if (substr($key, 0, 9) == 'refiner__') {
			$refinersPresent[$key] = strtoupper($key);
		}
	}
	
	//Add definitions for any refiners defined for this panel
	if (isset($tags['refiners']) && is_array($tags['refiners'])) {
		foreach ($tags['refiners'] as $key => $refiner) {
			$refinersPresent['refiner__'. $key] = strtoupper('refiner__'. $key);
		}
	}
	
	foreach ($refinersPresent as $req => $def) {
		if (isset($_GET[$req])) {
			$refiners = '';
			foreach (explode(',', get($req)) as $i => $refiner) {
				$refiners .= $i? ',' : '';
				$refiner = decodeItemIdForOrganizer($refiner);
				
				if (is_numeric($refiner)) {
					$refiners .= (int) $refiner;
				} else {
					$refiners .= "'". sqlEscape($refiner). "'";
				}
				
				if (!$i) {
					define($def, $refiners);
				}
			}
		} else {
			define($def, $refiners = 'NULL');
		}
		
		if ($def == 'REFINER_ID' || $def == 'COMBINE_ITEM') {
			define($def. 'S', '('. $refiners. ')');
		} else {
			define($def. '__S', '('. $refiners. ')');
		}
	}
	
	if (request('_combineItem')) {
		define('COMBINE_ITEM__NO_QUOTES', request('_combineItem'));
	}
	
	//Start to populate the Organizer Panel:
	//Firstly, see if other modules have added buttons/columns/refiners.
	//If so, they'll need their own placeholder methods executing as well
	
	//Note down if any buttons or refiners need any code from a different Module
	foreach (array('collection_buttons', 'item_buttons', 'inline_buttons', 'refiners') as $buttonType) {
		if (isset($tags[$buttonType]) && is_array($tags[$buttonType])) {
			foreach ($tags[$buttonType] as &$button) {
				if (is_array($button) && !empty($button['class_name'])) {
					zenarioAJAXIncludeModule($modules, $button, $type, $requestedPath, $settingGroup);
				}
			}
		}
	}
	
	//Have any columns been added that need formatting from their own Module?
	if (!get('_queued')) {
		if (isset($tags['columns']) && is_array($tags['columns'])) {
			foreach ($tags['columns'] as $colName => &$col) {
				if (is_array($col) && !empty($col['class_name'])) {
					zenarioAJAXIncludeModule($modules, $col, $type, $requestedPath, $settingGroup);
				}
			}
		}
	}
	
	//Run the modules' lining method to add/modify/remove columns/refiners/other tags
	foreach ($modules as $className => &$module) {
		
		//Handle the old name if it's not been changed yet
		if (method_exists($module, 'lineStorekeeper')) {
			$module->lineStorekeeper($requestedPath, $tags, request('refinerName'), request('refinerId'), $mode);
		}
		
		$module->preFillOrganizerPanel($requestedPath, $tags, request('refinerName'), request('refinerId'), $mode);
	}
	
	
	//Secondly, run any SQL queries that are needed to populate the items.
	//Refiners and pagination are also applied at this point
	if (empty($tags['items'])
	 && !empty($tags['db_items']['table'])
	 && !empty($tags['db_items']['id_column'])) {
		
		$orderBy = "";
		$whereStatement = "";
		$whereStatementWhenSearcingForChildren = "WHERE TRUE";
		$extraTables = array();
		$sortExtraTables = array();
		
		//Allow a Panel with no columns as a hack to get several complicated refiner chains working
		if (empty($tags['columns'])) {
			$tags['columns'] = array();
		}
		
		$encodeItemIdForOrganizer = engToBooleanArray($tags, 'db_items', 'encode_id_column');
		
		if (!isset($tags['items']) || !is_array($tags['items'])) {
			$tags['items'] = array();
		}
		
		//Look for any custom fields/tabs
		if ($dataset = getRow('custom_datasets', true, array('extends_organizer_panel' => $requestedPath))) {
			
			//Look up the name of the primary/foreign key column
			$datasetIdColumn = "custom.`". sqlEscape(getIdColumnOfTable($dataset['table'], true)). "`";
			
			//We'll need a join to the custom table
			$customJoin = "
				LEFT JOIN `". sqlEscape(DB_NAME_PREFIX. $dataset['table']). "` AS custom
				ON ". $tags['db_items']['id_column']. " = ". $datasetIdColumn;
			
			//Bugfix: the join should always be added, even if the admin doesn't have the permissions,
			//just to stop any Modules that assumed the join was there from erroring
			zenario_organizer::noteTableJoin($extraTables, $customJoin);
			
			if (!$dataset['view_priv'] || checkPriv($dataset['view_priv'])) {
				
				//Customise system fields
				foreach (getRowsArray(
					'custom_dataset_fields',
					array('field_name', 'label', 'organizer_visibility'),
					array('dataset_id' => $dataset['id'], 'is_system_field' => 1)
				) as $cfield) {
					if ($cfield['field_name'] && isset($tags['columns'][$cfield['field_name']])) {
						$sField = &$tags['columns'][$cfield['field_name']];
						
						if ($cfield['organizer_visibility'] == 'hide') {
							$sField['hidden'] = true;
						} elseif ($cfield['organizer_visibility'] == 'show_by_default') {
							$sField['show_by_default'] = true;
						} elseif ($cfield['organizer_visibility'] == 'always_show') {
							$sField['always_show'] = true;
						}
						
						if ($cfield['label']) {
							$sField['title'] = trim(trim($cfield['label']), ':');
						}
					}
				}
				unset($sField);
				
				//Add custom fields
				$ord = 1000;
				foreach (getRowsArray(
					'custom_dataset_fields',
					true,
					array('dataset_id' => $dataset['id'], 'organizer_visibility' => array('!' => 'none'), 'is_system_field' => 0),
					array('tab_name', 'ord')
				) as $cfield) {
					$cCol = array();
					$cCol['table_join'] = array($customJoin);
					$cCol['db_column'] = "custom.`". $cfield['db_column']. "`";
					$cCol['searchable'] = $cfield['searchable'];
					$cCol['disallow_sorting'] = !$cfield['sortable'];
					$cCol['show_by_default'] = ($cfield['organizer_visibility'] == 'show_by_default');
					$cCol['always_show'] = ($cfield['organizer_visibility'] == 'always_show');
					
					
					switch ($cfield['type']) {
						case 'editor':
						case 'file_picker':
							//Never show editor or file picker fields in Organizer
							continue 2;
			
						case 'group':
						case 'checkbox':
							$cCol['format'] = 'yes_or_no';
							break;
			
						case 'date':
							$cCol['format'] = 'date';
							break;
			
						case 'checkboxes':
							//For checkboxes, there could be multiple values, so we'll just load the data as a string
							$cCol['db_column'] = "(
								SELECT GROUP_CONCAT(cdfv.label SEPARATOR ', ')
								FROM ". DB_NAME_PREFIX. "custom_dataset_values_link AS cdvl
								INNER JOIN ". DB_NAME_PREFIX. "custom_dataset_field_values AS cdfv
								   ON cdfv.field_id = ". (int) $cfield['id']. "
								  AND cdfv.id = cdvl.value_id
								WHERE cdvl.linking_id = ". $tags['db_items']['id_column']. "
								)";
							break;
			
						case 'radios':
						case 'select':
							//For radios/select lists, if they are searchable or sortable then
							//we need to load the data as a string...
							if ($cfield['searchable'] || $cfield['sortable']) {
								$cCol['db_column'] = "(
									SELECT cdfv.label
									FROM ". DB_NAME_PREFIX. "custom_dataset_field_values AS cdfv
									WHERE cdfv.id = ". $cCol['db_column']. "
									  AND cdfv.field_id = ". (int) $cfield['id']. "
									)";
					
								//If they are also searchable/filterable then we need a select list in this format
								if ($cfield['searchable']) {
									$cCol['format'] = 'enum';
									$cCol['values'] = array();
									foreach (getDatasetFieldLOV($cfield, true) as $displayValue) {
										$cCol['values'][$displayValue] = $displayValue;
									}
								}
								break;
							}
			
						//...otherwise we can just load the real value and format it on the client
						case 'centralised_radios':
						case 'centralised_select':
							$cCol['format'] = 'enum';
							$cCol['values'] = getDatasetFieldLOV($cfield, true);
							break;
			
						//Handle links to other datasets
						case 'dataset_select':
						case 'dataset_picker':
							//Try to look up all of the information we need, and come up with a LEFT JOIN
							if ($labelDetails = getDatasetLabelFieldDetails($cfield['dataset_foreign_key_id'])) {
								
								$otherPrefix = "ot". (int) $cfield['id'];
							
								$cCol['table_join'][] = "
									LEFT JOIN `". sqlEscape(DB_NAME_PREFIX. $labelDetails['table']). "` AS ". $otherPrefix. "
									ON ". $otherPrefix. ".`". sqlEscape($labelDetails['id_column']). "` = ". $cCol['db_column'];
								
								$cCol['db_column'] = $otherPrefix. ".`". sqlEscape($labelDetails['db_column']). "`";
								
								break;
							
							} else {
								//If we couldn't find the information we need, don't show this column in Organizer
								continue 2;
							}
					}
		
					$cCol['ord'] = ++$ord;
					$cCol['title'] = $cfield['label'];
					if (substr($cCol['title'], -1) == ':') {
						$cCol['title'] = substr($cCol['title'], 0, -1);
					}
		
					$cFieldName = '__custom_field__'. ifNull($cfield['db_column'], $cfield['id']);
					$tags['columns'][$cFieldName] = $cCol;
				}
				unset($cfield);
			}
		}
		
		
		
		//Apply a refiners, if this panel has any and one has been selected
		if (get('refinerName') && !empty($tags['refiners'][get('refinerName')])) {
			
			//allow_unauthenticated_xml_access must be repeated on a refiner if they are both used
			if (!checkPriv() && !($mode == 'xml' && engToBooleanArray($tags, 'refiners', get('refinerName'), 'allow_unauthenticated_xml_access'))) {
				header('Zenario-Admin-Logged_Out: 1');
				echo '<!--Logged_Out-->', adminPhrase('You have been logged out.');
				exit;
			}
			
			$refinerWhere = false;
			if (isset($_GET['_search']) && !empty($tags['refiners'][get('refinerName')]['sql_when_searching'])) {
				$refinerWhere = ltrim($tags['refiners'][get('refinerName')]['sql_when_searching']);
			
			} elseif (!empty($tags['refiners'][get('refinerName')]['sql'])) {
				$refinerWhere = ltrim($tags['refiners'][get('refinerName')]['sql']);
			}
			if ($refinerWhere !== false) {
				if (substr($refinerWhere, 0, 3) == 'AND') {
					$whereStatement .= "
						". $refinerWhere;
				} else {
					$whereStatement .= "
						AND ". $refinerWhere;
				}
				
				//Normally we don't add the join to the custom table into the first SQL query, but if a refiner references it then we will need to!
				if ($customJoin
				 && (false !== strpos($refinerWhere, 'custom.') || false !== strpos($refinerWhere, '`custom`.'))) {
					zenario_organizer::noteTableJoin($sortExtraTables, $customJoin);
				}
			}
			unset($refinerWhere);
			
			//Add any table-joins for refiners
			$refinerJoin = false;
			if (isset($_GET['_search']) && !empty($tags['refiners'][get('refinerName')]['table_join_when_searching'])) {
				$refinerJoin = $tags['refiners'][get('refinerName')]['table_join_when_searching'];
			
			} elseif (!empty($tags['refiners'][get('refinerName')]['table_join'])) {
				$refinerJoin = $tags['refiners'][get('refinerName')]['table_join'];
			}
			if ($refinerJoin !== false) {
				zenario_organizer::noteTableJoin($sortExtraTables, $refinerJoin);
				
				//Normally we don't add the join to the custom table into the first SQL query, but if a refiner references it then we will need to!
				if ($customJoin
				 && (false !== strpos($refinerJoin, 'custom.') || false !== strpos($refinerJoin, '`custom`.'))) {
					zenario_organizer::noteTableJoin($sortExtraTables, $customJoin);
				}
			}
			unset($refinerJoin);
		
		} elseif (engToBooleanArray($tags, 'refiner_required')) {
			echo 'A refiner was required, but none was set.';
			exit;
		}
		
		
		//Loop through each database-column defined in the XML schema
		$columns = "";
		$sortColumn = false;
		$sortColumnDesc = false;
		
		$i = 0;
		foreach ($tags['columns'] as $colName => &$col) {
			if (is_array($col) && !empty($col['db_column'])) {
				//Add it to the SELECT
				if ($col['db_column'] != 'NULL') {
					$columns .= ",
						". $col['db_column'];
				}
				
				//Add it to the sort if we're sorting by it
				if ($colName == get('_sort_col') && !engToBooleanArray($col, 'disallow_sorting')) {
					if (!empty($col['sort_column'])) {
						$sortColumn = $col['sort_column'];
					} else {
						$sortColumn = $col['db_column'];
					}
					
					if (!empty($col['sort_column_desc'])) {
						$sortColumnDesc = $col['sort_column_desc'];
					} else {
						$sortColumnDesc = $sortColumn. ' DESC';
					}
				}
				
				//If it's from a different table, join to that table
				if (!empty($col['table_join'])) {
					
					//Check whether this column is being filtered, and what
					//the filter format is
					$filterFormat = false;
					if ($isFiltered = !empty(zenario_organizer::$filters[$colName]['v'])) {
						if (!empty($col['format'])) {
							$filterFormat = $col['format'];
						} elseif (!empty($col['filter_format'])) {
							$filterFormat = $col['filter_format'];
						} 
					}
					
					
					
					if ($colName == get('_sort_col')
					 || (($isFiltered || isset($_GET['_search'])) && engToBooleanArray($col, 'searchable'))
					 || ($isFiltered && $filterFormat && in(
							$filterFormat,
							'enum', 'yes_or_no',
							'language_english_name', 'language_english_name_with_id',
							'language_local_name', 'language_local_name_with_id',
							'date', 'datetime', 'datetime_with_seconds'
					))) {
						zenario_organizer::noteTableJoin($sortExtraTables, $col['table_join']);
					} else {
						zenario_organizer::noteTableJoin($extraTables, $col['table_join']);
					}
				}
			}
		}
		
		
		//Apply a search from the search box
		if (isset($_GET['_search']) && !empty($tags['columns'])) {
			$whereStatement .= "
			  AND (";
			
			$first = true;
			foreach ($tags['columns'] as $colName => &$col) {
				if (!empty($col['db_column']) && engToBooleanArray($col, 'searchable')) {
					//Group functions can't be used in a query
					if (!preg_match('/COUNT\s*\(/i', $col['db_column'])) {
						
						$asciiCharactersOnly = engToBooleanArray($col, 'ascii_only');
						
						if ($first) {
							$first = false;
						} else {
							$whereStatement .= " OR";
						}
						
						$whereStatement .= "
							". ifNull(arrayKey($col, 'search_column'), $col['db_column']);
						
						if (!empty($col['format']) && $col['format'] == 'id') {
							$whereStatement .= " = '". sqlEscape(get('_search')). "'";
						} else {
							$whereStatement .= " LIKE '%". likeEscape(get('_search'), true, $asciiCharactersOnly). "%'";
						}
					}
				}
			}
			
			if ($first) {
				$whereStatement .= "TRUE";
			}
			
			$whereStatement .= "
			  )";
		}
		
		
		
		//Apply filters
		foreach ($tags['columns'] as $colName => &$col) {
			
			
			if (is_array($col)
			 && !empty(zenario_organizer::$filters[$colName]['v'])
			 && !engToBooleanArray($col, 'disallow_filtering')) {
				
				//Try to get the column to filter on
				if (!empty($col['filter_column'])) {
					$columnName = $col['filter_column'];
				} elseif (!empty($col['search_column'])) {
					$columnName = $col['search_column'];
				} elseif (!empty($col['db_column'])) {
					$columnName = $col['db_column'];
				} else {
					continue;
				}
				
				$value_ = zenario_organizer::$filters[$colName]['v'];
				
				$filterFormat = false;
				if (!empty($col['format'])) {
					$filterFormat = $col['format'];
				} elseif (!empty($col['filter_format'])) {
					$filterFormat = $col['filter_format'];
				} 
				$isDateColumn = $filterFormat == 'date' || $filterFormat == 'datetime' || $filterFormat == 'datetime_with_seconds';
				
				switch ($filterFormat) {
					case 'date':
					case 'datetime':
					case 'datetime_with_seconds':
						$dates = explode(',', $value_);
						
						if (!empty($dates[0]) && preg_replace('/\d{4}-\d{2}-\d{2}/', '', $dates[0]) == '') {
							$whereStatement .= "
							  AND ". $columnName.
								  " >= '". sqlEscape($dates[0]). "'";
						}
						
						if (!empty($dates[1]) && preg_replace('/\d{4}-\d{2}-\d{2}/', '', $dates[1]) == '') {
							$whereStatement .= "
							  AND ". $columnName.
								" < DATE_ADD('". sqlEscape($dates[1]). "', INTERVAL 1 DAY)";
						}
						
						break;
					
					//Yes/No type filters on tinyint columns
					case 'yes_or_no':
					
						if (empty(zenario_organizer::$filters[$colName]['not'])) {
							$whereStatement .= "
							  AND ". $columnName. " != 0";
					
						} else {
							$whereStatement .= "
							  AND (". $columnName. " = 0
								OR ". $columnName. " IS NULL)";
						}
						
						break;
						
						
					//enum filters
					case 'enum':
					case 'language_english_name':
					case 'language_english_name_with_id':
					case 'language_local_name':
					case 'language_local_name_with_id':
					case 'id':
					
						//A value of "*" should match all values (or all empty values if not is set)
						if ($filterFormat != 'id'
						 && zenario_organizer::$filters[$colName]['v'] == '*') {
							if (empty(zenario_organizer::$filters[$colName]['not'])) {
								$whereStatement .= "
								  AND ". $columnName. " != 0
								  AND ". $columnName. " != ''";
					
							} else {
								$whereStatement .= "
								  AND (". $columnName. " = 0
									OR ". $columnName. " = ''
									OR ". $columnName. " IS NULL)";
							}
					
						//Otherwise do a normal match
						} else {
							if (empty(zenario_organizer::$filters[$colName]['not'])) {
								$whereStatement .= "
								  AND ". $columnName. " = '". sqlEscape(zenario_organizer::$filters[$colName]['v']). "'";
					
							} else {
								$whereStatement .= "
								  AND (". $columnName. " != '". sqlEscape(zenario_organizer::$filters[$colName]['v']). "'
									OR ". $columnName. " IS NULL)";
							}
						}
						
						break;
						
						
					default:
						//Do a text search for filters on text fields
						if (engToBooleanArray($col, 'searchable')) {
					
							$asciiCharactersOnly = engToBooleanArray($col, 'ascii_only');
					
							if (empty(zenario_organizer::$filters[$colName]['not'])) {
								$whereStatement .= "
									AND ". $columnName. " LIKE '%". likeEscape(zenario_organizer::$filters[$colName]['v'], true, $asciiCharactersOnly). "%'";
					
							} else {
								$whereStatement .= "
									AND (". $columnName. " IS NULL OR ". $columnName. " NOT LIKE '%". likeEscape(zenario_organizer::$filters[$colName]['v'], true, $asciiCharactersOnly). "%')";
							}
						}
				}
			}
		}
		
		
		
		
		//Check to see if a hierarchy column is set
		$hierarchyColumn = false;
		if (!empty($tags['hierarchy'])) {
			//It can be set directly in $tags['hierarchy']['db_column'],
			//or looked up by checking for the definition of $tags['hierarchy']['column']
			//in the columns array.
			if (!empty($tags['hierarchy']['db_column'])) {
				$hierarchyColumn = $tags['hierarchy']['db_column'];
			} else {
				if (!empty($tags['hierarchy']['column'])) {
					$hierarchyColumnName = $tags['hierarchy']['column'];
				} else {
					$hierarchyColumnName = 'parent_id';
				}
				
				if (!empty($tags['columns'][$hierarchyColumnName]['db_column'])) {
					$hierarchyColumn = $tags['columns'][$hierarchyColumnName]['db_column'];
				}
			}
		}
		$idColumn = $tags['db_items']['id_column'];
		
		if (!empty($tags['db_items']['group_by'])) {
			$groupBy = $tags['db_items']['group_by'];
		} else {
			$groupBy = $idColumn;
		}
		
		//Load the standard where-statement, if this panel has one
		if (!empty($tags['db_items']['where_statement'])) {
			$whereStatement = $tags['db_items']['where_statement']. $whereStatement;
		} else {
			$whereStatement = "WHERE TRUE". $whereStatement;
		}
		
		//Order by the sort column
		if ($sortColumn) {
			if (get('_sort_desc')) {
				$orderBy = $sortColumnDesc. ", ". $groupBy;
			} else {
				$orderBy = $sortColumn. ", ". $groupBy;
			}
		} else {
			$orderBy = $groupBy;
		}
		
		$in = "";
		$limitForAJAXLookups = "";
		$noResults = false;
		
		
		//Handle buttons that ask for a list of matched ids
		if ($mode == 'get_matched_ids') {

			//Get a list of all of the matched ids
			$sql = "
				SELECT ". $idColumn. "
				FROM ". $tags['db_items']['table'];
		
			foreach ($sortExtraTables as $join => $dummy) {
				$sql .= "
					". $join;
			}
			
			$sql .= "
				". $whereStatement. "
				GROUP BY ". $groupBy. "
				ORDER BY ". $orderBy;
			
			$organizerQueryIds = addConstantsToString($sql);
			$result = sqlSelect($organizerQueryIds);
			
			unset($sql);
			unset($tags);
			unset($organizerQueryIds);
			
			$first = true;
			$_GET['id'] = '';
			while ($row = sqlFetchRow($result)) {
				if ($first) {
					$first = false;
				} else {
					$_GET['id'] .= ',';
				}
				$_GET['id'] .= $row[0];
			}
			
			if (!empty($_REQUEST['_fab_path'])) {
				//Handle FABs that wanted the list of ids.
				//We'll fake a few things in the GET/POST, then call admin_boxes.ajax.php
				//to run the logic for opening the FAB
				$_REQUEST['_fill'] = $_POST['_fill'] = $_GET['_fill'] = true;
				$_REQUEST['path'] = $_POST['path'] = $_GET['path'] = $_REQUEST['_fab_path'];
				require CMS_ROOT. 'zenario/admin/admin_boxes.ajax.php';
			
			} else {
				//Handle AJAX buttons that wanted the list of ids
				//Note that this code is similar to the logic in zenario/ajax.php that normally handles
				//the handleOrganizerPanelAJAX() and organizerPanelDownload() methods, except this version
				//also runs preFillOrganizerPanel() and includes a list of ids
				if (!request('__pluginClassName__')
				 || empty($modules[request('__pluginClassName__')])) {
					echo 'Error, could not find the module for this button on this panel.';
					exit;
				}
			
				if (post('_download')) {
					$modules[request('__pluginClassName__')]->organizerPanelDownload($requestedPath, $_GET['id'], request('refinerName'), request('refinerId'));
			
				} else {
					$newIds = $modules[request('__pluginClassName__')]->handleOrganizerPanelAJAX($requestedPath, $_GET['id'], '', request('refinerName'), request('refinerId'));

					if ($newIds && !is_array($newIds)) {
						$newIds = explode(',', $newIds);
					}
			
					if ($newIds) {
						if (!is_array(session('sk_new_ids'))) {
							$_SESSION['sk_new_ids'] = array();
						}
						foreach ($newIds as $id) {
							$_SESSION['sk_new_ids'][$id] = true;
						}
					}
			
				}
			}
			
			exit;
		
		
		} elseif ($groupBy === $idColumn && $mode == 'get_item_links') {
			//Only look for a few specific items
				//(Somewhat of a bespoke feature at the moment, it's not in the schema)
			foreach(explode(',', $_REQUEST['_get_item_links']) as $i => $id) {
				$in .= $in? ", " : "IN (";
				$in .= is_numeric($id)? (int) $id : "'". sqlEscape($id). "'";
			}
			
		} elseif ($groupBy === $idColumn && $mode == 'get_item_name') {
			if (isset($_REQUEST['_item'])) {
				foreach(explode(',', $_REQUEST['_item']) as $i => $id) {
					$in .= $in? ", " : "IN (";
					$in .= is_numeric($id)? (int) $id : "'". sqlEscape($id). "'";
				}
			
			} else {
				$limitForAJAXLookups = paginationLimit(1, 30);
			}
		
		} elseif ($mode == 'typeahead_search') {
			$limitForAJAXLookups = paginationLimit(1, 30);
		
		} elseif ($hierarchyColumn && (isset($_REQUEST['_openItemsInHierarchy']) || isset($_REQUEST['_openToItemInHierarchy']))) {
			
			//Remember the current WHERE statement so far
			$whereStatementWhenSearcingForChildren = $whereStatement;
			$openItemsInHierarchy = array();
			
			//Display every item in the hierarchy that is open, and their parents.
			if (!empty($_REQUEST['_openItemsInHierarchy'])) {
				foreach (explode(',', $_REQUEST['_openItemsInHierarchy']) as $id) {
					if ($id) {
						$openItemsInHierarchy[$id] = true;
					}
				}
			
			//Alternately, if this is the first load, we might know an id that we wish to display,
			//but be unsure as to what its parents are
			} elseif (!empty($_REQUEST['_openToItemInHierarchy'])) {
				
				$limit = 30;
				$id = $_REQUEST['_openToItemInHierarchy'];
				
				//Look up the selected item's parent, and that's parent, and so on
				//until we reach the top
				do {
					$sql = "
						SELECT ". $hierarchyColumn. "
						FROM ". $tags['db_items']['table']. "
						WHERE ". $idColumn. " = ";
				
					if (is_numeric($id)) {
						$sql .= (int) $id;
					} else {
						$sql .= "'". sqlEscape($id). "'";
					}
				
					if (($result = sqlSelect(addConstantsToString($sql)))
					 && ($row = sqlFetchRow($result))
					 && ($row[0])
					 && (!isset($openItemsInHierarchy[$row[0]]))) {
						$id = $row[0];
						$openItemsInHierarchy[$id] = true;
					} else {
						break;
					}
				} while (--$limit > 0);
			}
			
			//Always fetch the top level items, i.e. things with a 0 or null parent id
			$whereStatement .= "
				AND (". $hierarchyColumn. " IS NULL
				 OR ". $hierarchyColumn. " IN (0";
			
			//Also fetch the items that are parents of any item that is open
			foreach ($openItemsInHierarchy as $id => $dummy) {
				$whereStatement .= ", ". (is_numeric($id)? (int) $id : "'". sqlEscape($id). "'");
			}
			
			$whereStatement .= "))";
			
		
		} else {
			
			if (empty($tags['db_items']['item_count_max_limit'])
			 || !($itemCountMaxLimit = (int) $tags['db_items']['item_count_max_limit'])) {
				$itemCountMaxLimit = $tags['db_items']['item_count_max_limit'] = 100000;
			}
			$tags['db_items']['item_count_max_limit_hit'] = false;
			
			//Get a count of all the rows, and get each id in the correct order
			$sql = "
				SELECT ". $idColumn;
			
			if ($hierarchyColumn) {
				$sql .= ", ". $hierarchyColumn;
			}
			
			$sql .= "
				FROM ". $tags['db_items']['table'];
		
			foreach ($sortExtraTables as $join => $dummy) {
				$sql .= "
					". $join;
			}
			
			$sql .= "
				". $whereStatement. "
				GROUP BY ". $groupBy. "
				ORDER BY ". $orderBy. "
				LIMIT ". ($itemCountMaxLimit + 1);
			
			$organizerQueryIds = addConstantsToString($sql);
			$result = sqlSelect($organizerQueryIds);
			
			if (!$debugMode) {
				unset($organizerQueryIds);
			}
			
			//For panel requests that are part of a queue, don't attempt to fetch any ids, just get a count.
			if (get('_queued')) {
				$count = sqlNumRows($result);
			
			//Otherwise get the list of ids in the correctly sorted order
			} else {
				$count = 0;
				$tags['__item_sort_order__'] = array();
				
				while ($row = sqlFetchRow($result)) {
					
					if (++$count > $itemCountMaxLimit) {
						$count = $itemCountMaxLimit;
						$tags['db_items']['item_count_max_limit_hit'] = true;
						break;
					
					} else {
						if ($encodeItemIdForOrganizer) {
							$row[0] = encodeItemIdForOrganizer($row[0]);
						}
					
						$tags['__item_sort_order__'][] = $row[0];
					}
				}
				
				//If "_limit" is in the request, this means that server side sorting/pagination is being used
				if (get('_limit')) {
					//Apply pagination using the limit
					$start = (int) get('_start');
				
					if ($start >= $count) {
						$start = 0;
					}
				
					//If we are using pagination, and have had specific item(s) requested, only show the page that item is on.
					//In the case of a multiple selection, show the earliest page with items on
					if (isset($_GET['_item'])) {
						$pos = false;
						foreach (explode(',', $_GET['_item']) as $item) {
							$itemPos = array_search($item, $tags['__item_sort_order__']);
						
							if ($itemPos !== false && ($pos === false || $itemPos < $pos)) {
								$pos = $itemPos;
							}
						}
					
						//Change the start position appropriately
						if ($pos !== false) {
							$start = $pos - ($pos % (int) get('_limit'));
						}
					}
				
					//Set which page this should be
					$tags['__page__'] = 1 + (int) ($start / (int) get('_limit'));
					$stop = $start + (int) get('_limit');
				
					$startV = $start;
					$stopV = $stop;
					//Don't sent the whole collection of ids if we are using server-side pagination.
					//But add an id either side to help with forward/back buttons in close-up view
					if ($start > 0) {
						--$start;
					}
					if ($stop < $count) {
						++$stop;
					}
				
					$new__item_sort_order__ = array();
					for ($i = $start; $i < $stop; ++$i) {
						if (isset($tags['__item_sort_order__'][$i]) && $tags['__item_sort_order__'][$i] !== null) {
							$new__item_sort_order__[$i] = $tags['__item_sort_order__'][$i];
						} else {
							break;
						}
					
						if ($i >= $startV && $i < $stopV) {
							$in .= $in? ", " : "IN (";
						
							$thisId = $tags['__item_sort_order__'][$i];
							if ($encodeItemIdForOrganizer) {
								$thisId = decodeItemIdForOrganizer($thisId);
							}
						
							if (is_numeric($thisId)) {
								$in .= (int) $thisId;
							} else {
								$in .= "'". sqlEscape($thisId). "'";
							}
						}
					}
				
					//We don't need to send the ids of every single item to the client
					unset($tags['__item_sort_order__']);
					$tags['__item_sort_order__'] = $new__item_sort_order__;
					unset($new__item_sort_order__);
				
				//A simplier version of the above that doesn't worry about pagination
				} else {
					foreach ($tags['__item_sort_order__'] as $i => $thisId) {
						
						$in .= $in? ", " : "IN (";
					
						if ($encodeItemIdForOrganizer) {
							$thisId = decodeItemIdForOrganizer($thisId);
						}
					
						if (is_numeric($thisId)) {
							$in .= (int) $thisId;
						} else {
							$in .= "'". sqlEscape($thisId). "'";
						}
					}
				}
				
				//Flag if no rows matched the search query
				if (!$in) {
					$noResults = true;
				}
				
				//Remove the $whereStatement as it's not needed as well as an IN() statement
				$whereStatement = "WHERE TRUE";
			}
		}
		
		//When I do the work to add a new type of CSV export, the code to handle it should probably go here!
		
		//if (request('new_csv_mode') {
		//	foreach ($tags['__item_sort_order__'] as $id) {
		//		some_module::some_function($requestedPath, $id);
		//	}
		//} else
		
		if (!get('_queued')) {
			
			//If we've not been using pagination, count the number of items
			if (!$in) {
				$count = 0;
			}
			
			//If we know there will be no results, don't actually bother doing the query
			if (!$noResults) {
				if ($in) {
					$in .= ")";
				
					define('SQL_IN', $in);
				
					$in = "AND ". $idColumn. " ". $in;
			
				} else {
					define('SQL_IN', '');
				}
			
			
				//Get all of the rows on the current page
				$sql = "
					SELECT
						". $idColumn. 
						$columns;
			
				if ($hierarchyColumn) {
					$sql .= ", ". $hierarchyColumn;
				}
			
				$sql .= "
					FROM ". $tags['db_items']['table'];
			
				foreach ($sortExtraTables as $join => $dummy) {
					$sql .= "
						". $join;
				}
			
				foreach ($extraTables as $join => $dummy) {
					if (!isset($sortExtraTables[$join])) {
						$sql .= "
							". $join;
					}
				}
			
				$sql .= "
					". $whereStatement. "
					". $in. "
					GROUP BY ". $groupBy;
			
				//In XML mode, we need to make sure we add the order-by logic in when running the query to get the data
				//In normal mode, we only need to order things when applying pagination logic as there is client-side sorting for the actual data
				if ($mode == 'xml') {
					$sql .= "
					ORDER BY ". $orderBy;
				}
				
				$sql .= $limitForAJAXLookups;
			
				//Loop through the results adding them into the items array (or alternately into the CSV file for CSV exports)
				$organizerQueryDetails = addConstantsToString($sql);
			
				if ($debugMode) {
					displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath, $organizerQueryIds, $organizerQueryDetails);
					exit;
				}
				$result = sqlSelect($organizerQueryDetails);
				unset($organizerQueryDetails);
				
				if (empty($tags['db_items']['max_limit'])
				 || !($maxLimit = (int) $tags['db_items']['max_limit'])) {
					$maxLimit = $tags['db_items']['max_limit'] = 500;
				}
				$tags['db_items']['max_limit_hit'] = false;
				
				$countOfRowsToSend = 0;
				while ($row = sqlFetchRow($result)) {
					//If we've not previously done it, we need to count the number of items
					if (!$in) {
						++$count;
					}
					
					//Make sure that we don't send any more rows than the maximum download limit
					if (++$countOfRowsToSend > $maxLimit) {
						$tags['db_items']['max_limit_hit'] = true;
						
						//We can stop looping now, unless we still need to count all of the items.
						if ($in) {
							break;
						}
					
					} else {
						$id = $row[$i = 0];
				
						if ($encodeItemIdForOrganizer) {
							$id = encodeItemIdForOrganizer($id);
						}
				
						$tags['items'][$id] = array();
				
						foreach ($tags['columns'] as $colName => &$col) {
							if (is_array($col) && !empty($col['db_column'])) {
								if ($col['db_column'] != 'NULL') {
									$tags['items'][$id][$colName] = $row[++$i];
								}
							}
						}
					
						//If we're doing a lazy load, we need to look up whether
						//an item has children so we know whether to show a "+" next to it or not
						if ($hierarchyColumn && (isset($_REQUEST['_openItemsInHierarchy']) || isset($_REQUEST['_openToItemInHierarchy']))) {
							if (!isset($tags['__item_parents__'][$row[0]])) {
								$csql = "
									SELECT 1
									FROM ". $tags['db_items']['table']. "
									". $whereStatementWhenSearcingForChildren. "
									  AND ". $hierarchyColumn. " = ". (is_numeric($row[0])? (int) $row[0] : "'". sqlEscape($row[0]). "'"). "
									LIMIT 1";
							
								$cresult = sqlQuery(addConstantsToString($csql));
								$tags['__item_parents__'][$row[0]] = (bool) sqlFetchRow($cresult);
							}
						}
				
						++$i;
						if ($hierarchyColumn && !empty($row[$i])) {
							$tags['__item_parents__'][$row[$i]] = true;
						}
					}
				}
			}
		}
	
	} else {
		if (get('refinerName') && !empty($tags['refiners'][get('refinerName')])) {
			//allow_unauthenticated_xml_access must repeated it on a refiner 
			if (!checkPriv() && !($mode == 'xml' && engToBooleanArray($tags, 'refiners', get('refinerName'), 'allow_unauthenticated_xml_access'))) {
				header('Zenario-Admin-Logged_Out: 1');
				echo '<!--Logged_Out-->', adminPhrase('You have been logged out.');
				exit;
			}
		}
	}
	
	//Debug mode - show the TUIX before it's been modified
	if ($debugMode) {
		displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath, $organizerQueryIds);
		exit;
	}
	
	//Thirdly, run each modules' fill method to add formating, and some other required attributes
	foreach ($modules as $className => &$module) {
		
		//Handle the old name if it's not been changed yet
		if (method_exists($module, 'fillStorekeeper')) {
			$module->fillStorekeeper($requestedPath, $tags, request('refinerName'), request('refinerId'), $mode);
		}
		
		$module->fillOrganizerPanel($requestedPath, $tags, request('refinerName'), request('refinerId'), $mode);
	}
	
	//Set the current item count
	if ($mode != 'xml') {
		if (isset($count)) {
			$tags['__item_count__'] = $count;
		} elseif (!empty($tags['items'])) {
			$tags['__item_count__'] = count($tags['items']);
		} else {
			$tags['__item_count__'] = 0;
		}
	}
}

//Output the data in debug mode
if ($debugMode) {
	displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath);
	exit;
}


//Tidy away some organizer tags
//Remove anything the current admin has no access to, count each tags' children
$removedColumns = false;
if ($loadDefinition) {
	zenarioParseTUIX2($tags, $removedColumns, $type, $requestedPath, $mode);
}

//Don't send any SQL to the client
if (isset($tags['items']) && is_array($tags['items']) && is_array($removedColumns)) {
	foreach ($tags['items'] as &$item) {
		foreach ($removedColumns as $unset) {
			unset($item[$unset]);
		}
	}
}


//Only get the raw data when getting item data
if ($mode == 'get_item_data') {
	$tags = array(
		'items' => $tags['items'],
		'title' => $tags['title'],
		'__item_count__' => $tags['__item_count__'],
		'__item_sort_order__' => $tags['__item_sort_order__']);


//When just fetching an item's name, strip away everything that we don't need to calculate the item name
//I also need to set the path for Menu Nodes
} elseif ($mode == 'get_item_name' || $mode == 'get_item_links' || $mode == 'typeahead_search') {
	//Add information on the item
	$output = array('item' => arrayKey($tags, 'item'), 'columns' => array());
	
	if (isset($tags['items'])) {
		$output['items'] = $tags['items'];
	}
	
	if (!empty($tags['item'])) {
		$output['item'] = $tags['item'];
	}
	
	$tagString = '[[name]]';
	$usedcolumns = array('css_class' => true, 'list_image' => true);
	
	if ($mode == 'get_item_links') {
		$usedcolumns['name'] = true;
		$usedcolumns['navigation_path'] = true;
		$needToSetMenuName = $requestedPath == 'zenario__menu/panels/menu_nodes';
	} else {
		$needToSetMenuName = false;
	}
	
	if (!empty($tags['default_sort_column'])) {
		$tagString = '[['. ($output['default_sort_column'] = $tags['default_sort_column']). ']]';
	}
	if (!empty($tags['label_format_for_grid_view'])) {
		$tagString .= $output['label_format_for_grid_view'] = $tags['label_format_for_grid_view'];
	}
	if (!empty($tags['label_format_for_picked_items'])) {
		$tagString .= $output['label_format_for_picked_items'] = $tags['label_format_for_picked_items'];
	}
	
	foreach (preg_split('@\[\[([^\[\]]+)\]\]@s', $tagString, -1,  PREG_SPLIT_DELIM_CAPTURE) as $i => $colName) {
		if ($i % 2) {
			$usedcolumns[$colName] = true;
		}
	}
	
	//Strip out any column that is not used in the label
	if (!empty($output['items'])) {
		foreach ($output['items'] as $id => &$item) {
			if (is_array($item)) {
				foreach ($item as $colName => $column) {
					if (empty($usedcolumns[$colName])) {
						unset($item[$colName]);
					}
				}
				unset($item['cell_css_classes']);
			
				if ($needToSetMenuName) {
					$item['name'] = getMenuPath($id, FOCUSED_LANGUAGE_ID__NO_QUOTES, $separator = ' -> ');
				}
			}
		}
	}
	
	//Add the format information for any column that has format information and is in the label tag
	if ($tagString) {
		if (isset($tags['columns']) && is_array($tags['columns'])) {
			foreach ($tags['columns'] as $colName => &$col) {
				if (!empty($usedcolumns[$colName]) && !empty($col['format'])) {
					$output['columns'][$colName] = array('format' => $col['format']);
					
					if (!empty($col['values'])) {
						$output['columns'][$colName]['values'] = $col['values'];
					}
				}
			}
		}
	}
	
	$tags = $output;

} elseif ($mode != 'xml' && $requestedPath) {
	//Send the filters back to the client, just in case they were adjusted in php
	$tags['_filters'] = zenario_organizer::$filters;
}


//Display the output, either as JSON to send to the Organizer's JavaScript engine, or as a XML if this is a sitemap
if ($mode == 'xml') {
	
	header('Content-Type: text/xml; charset=UTF-8');
	
	$xml = new XMLWriter();
	$xml->openURI('php://output');
	$xml->startDocument('1.0', 'UTF-8');
	$xml->setIndent(4);
	$xml->startElement(ifNull(arrayKey($tags, 'xml', 'outer_tag'), 'organizer'));
	
	
	if (!empty($tags['xml']['outer_tag_attributes']) && is_array($tags['xml']['outer_tag_attributes'])) {
		foreach ($tags['xml']['outer_tag_attributes'] as $key => $value) {
			$xml->writeAttribute($key, $value);
		}
	}
	
	
	if (engToBooleanArray($tags, 'xml', 'only_show_items_tag')) {
		$tags = $tags['items'];
	} else {
		//Remove a few things we don't need for an XML feed
		unset($tags['_path_here']);
		unset($tags['view_mode']);
		unset($tags['db_items']);
		unset($tags['refiners']);
		unset($tags['default_sort_column']);
		unset($tags['columns']);
		unset($tags['item']);
		unset($tags['collection_buttons']);
		unset($tags['item_buttons']);
		unset($tags['inline_buttons']);
		unset($tags['hidden_nav']);
		unset($tags['link']);
		unset($tags['xml']);
	}
	
	
	function XMLWriterRecurse(&$xml, &$tags) {
		//Remove a few things we don't need for an XML feed
		unset($tags['back_link']);
		unset($tags['panel']);
		unset($tags['__item_count__']);
		unset($tags['ord']);
		
		foreach ($tags as $key => &$child) {
			
			if (is_array($child) && !empty($child['xml_tag_name'])) {
				$key = $child['xml_tag_name'];
				unset($child['xml_tag_name']);
				
			} elseif (is_numeric($key)) {
				$key = '_'. $key;
			}
			
			if (is_array($child)) {
				$xml->startElement($key);
				XMLWriterRecurse($xml, $child);
				$xml->endElement();
			} else {
				$xml->writeElement($key, $child);
			}
		}
	}
	
	XMLWriterRecurse($xml, $tags);
	$xml->endElement();
	$xml->endDocument();
	$xml->flush();
	
} else {
	header('Content-Type: text/javascript; charset=UTF-8');
	
	if (request('_script')) {
		echo 'zenarioO.lookForBranches(zenarioO.map = ';
	}
	
	jsonEncodeForceObject($tags);
	
	if (request('_script')) {
		echo ');';
	}
}
