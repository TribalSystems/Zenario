<?php
/*
 * Copyright (c) 2019, Tribal Limited
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
 
//Add the admin id and type up as constants
define('ADMIN_ID', (int) ($_SESSION['admin_userid'] ?? false));


function searchOrganizerColumn(&$whereStatement, $columnName, $searchText, $exactMatch, $not, $col) {
	
	$asciiCharactersOnly = ze\ring::engToBoolean($col['ascii_only'] ?? false);
	
	if (isset($col['chop_prefix_from_search'])) {
		$searchText = ze\ring::chopPrefix($col['chop_prefix_from_search'], $searchText, $returnStringOnFailure = true, $caseInsensitive = true);
	}
	
	if ($exactMatch) {
		if ($not) {
			$whereStatement .= "
				(". $columnName. " IS NULL OR ". $columnName. " != ";
		} else {
			$whereStatement .= "
				". $columnName. " = ";
		}
	
		if ($asciiCharactersOnly) {
			$whereStatement .= "_ascii";
		}
	
		$whereStatement .= "'". ze\escape::sql($searchText). "'";
	
	} else {
		if ($not) {
			$whereStatement .= "
				(". $columnName. " IS NULL OR ". $columnName. " NOT LIKE ";
		} else {
			$whereStatement .= "
				". $columnName. " LIKE ";
		}
	
		if ($asciiCharactersOnly) {
			$whereStatement .= "_ascii";
		}
	
		$whereStatement .= "'%". ze\escape::like($searchText, true). "%'";
	}
	
	if ($not) {
		$whereStatement .= ")";
	}
}


$mode = false;
$tagPath = '';
$modules = [];
$debugMode = (bool) ($_GET['_debug'] ?? false);
$customJoin = false;
$organizerQueryIds = false;
$organizerQueryDetails = false;
$settingGroup = '';
$compatibilityClassNames = [];
ze::$tuixType = $type = 'organizer';

//Work out which mode this should be for Organizer
if ($_GET['_select_mode'] ?? false) {
	define('ORGANIZER_MODE', $mode = 'select');
} elseif ($_GET['_quick_mode'] ?? false) {
	define('ORGANIZER_MODE', $mode = 'quick');
} elseif ($_GET['_typeahead_search'] ?? false) {
	define('ORGANIZER_MODE', $mode = 'typeahead_search');
} elseif ($_GET['_get_item_name'] ?? false) {
	define('ORGANIZER_MODE', $mode = 'get_item_name');
} elseif (!empty($_REQUEST['_get_item_links'])) {
	define('ORGANIZER_MODE', $mode = 'get_item_links');
} elseif ($_GET['_get_item_data'] ?? false) {
	define('ORGANIZER_MODE', $mode = 'get_item_data');
} elseif ($_GET['_get_matched_ids'] ?? false) {
	define('ORGANIZER_MODE', $mode = 'get_matched_ids');
} else {
	define('ORGANIZER_MODE', $mode = 'full');
}

//Require admin permissions
if (!ze\priv::check()) {
	header('Zenario-Admin-Logged_Out: 1');
	echo '<!--Logged_Out-->', ze\admin::phrase('You have been logged out.');
	exit;
}


define('FOCUSED_LANGUAGE_ID__NO_QUOTES', preg_replace('@[^\w-]@', '', $_REQUEST['languageId'] ?? $_GET['refiner__language'] ?? ze::$defaultLang ?? 'en'));
define('FOCUSED_LANGUAGE_ID', "'". ze\escape::sql(FOCUSED_LANGUAGE_ID__NO_QUOTES). "'");





//See if there is a requested path.
$requestedPath = false;
if (($_GET['method_call'] ?? false) == 'showSitemap') {
	$requestedPath = 'zenario__content/hidden_nav/sitemap/panel';

} elseif ($_REQUEST['path'] ?? false) {
	$requestedPath = preg_replace('/[^\w\/]/', '', ($_REQUEST['path'] ?? false));
}
ze::$tuixPath = $requestedPath;

class zenario_organizer {
	
	public static $filters = [];
	
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
			zenario_organizer::$filters[$columnName] = [
				's' => 0
			];
		} else {
			zenario_organizer::$filters[$columnName] = [
				's' => 1,
				'v' => $value,
				'not' => $not? 1 : 0
			];
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
			unset($join);
	
		} else {
			$tableJoin = preg_replace('/\s\s+/', ' ', trim($tableJoin));
			$words = preg_split('/\W+/', strtoupper($tableJoin), 4);
	
			if (($words[1] ?? false) != 'JOIN'
			 && ($words[2] ?? false) != 'JOIN'
			 && ($words[1] ?? false) != 'STRAIGHT_JOIN'
			 && ($words[2] ?? false) != 'STRAIGHT_JOIN') {
				$tableJoin = 'LEFT JOIN '. $tableJoin;
			}
	
			$existingJoins[$tableJoin] = true;
		}
	}
}

if ($_GET['_filters'] ?? false) {
	zenario_organizer::$filters = json_decode($_GET['_filters'] ?? false, true);
}



//Scans the Module directory for Modules with the relevant TUIX files, read them, and get a php array
$moduleFilesLoaded = [];
$tags = [];
$originalTags = [];
ze\tuix::load($moduleFilesLoaded, $tags, $type, $requestedPath, $settingGroup, $compatibilityClassNames);


//If we had a requested path, drill straight down to that level
if ($requestedPath) {
	
	foreach(explode('/', $requestedPath) as $path) {
		if (isset($tags[$path]) && is_array($tags[$path])) {
			$tags = $tags[$path];
			$tagPath .= '/'. $path;
		
		} else {
			echo ze\admin::phrase('The requested path "[[path]]" was not found in the system. If you have just updated or added files to the CMS, you will need to reload the page.', ['path' => $requestedPath]);
			exit;
		}
	}
}

if ($debugMode) {
	$staticTags = $tags;
}

//For organizer, if there is no path, that means we need to load the top level nav
//(Some basic information on the panels is also included).
if (!$requestedPath || empty($tags['class_name'])) {
	
	//Check which modules have the fill_organizer_nav property set and should be run to customise
	//the top level navigation
	foreach(ze\row::getAssocs(
		'modules',
		['class_name'],
		['fill_organizer_nav' => 1, 'status' => ['module_running', 'module_is_abstract']]
	) as $moduleWithNav) {
		ze\tuix::includeModule($modules, $moduleWithNav, $type, $requestedPath, $settingGroup);
	}
	foreach ($modules as $className => &$module) {
		$module->fillOrganizerNav($tags);
	}


//If this is a request for a specific path, run that Module and let it manage its output in PHP
} else {
	
	ze\tuix::checkOrganizerPanel($tags);
	
	if (isset($tags['priv']) && !ze\priv::check($tags['priv'])) {
		echo ze\admin::phrase('You do not have permissions to see this Panel.');
		exit;
	}
	
	
	if (!ze\tuix::includeModule($modules, $tags, $type, $requestedPath, $settingGroup)) {
		echo ze\admin::phrase('Could not activate the [[class_name]] Module.', ['class_name' => $tags['class_name']]);
		exit;
	}
	
	
	//Add definitions for any refiners supplied in the request
	$refinersPresent = ['refinerId' => 'REFINER_ID', '_combineItem' => 'COMBINE_ITEM'];
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
			foreach (explode(',', ($_GET[$req] ?? false)) as $i => $refiner) {
				$refiners .= $i? ',' : '';
				$refiner = ze\ring::decodeIdForOrganizer($refiner);
				
				if (is_numeric($refiner)) {
					$refiners .= (int) $refiner;
				} else {
					$refiners .= "'". ze\escape::sql($refiner). "'";
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
	
	if ($_REQUEST['_combineItem'] ?? false) {
		define('COMBINE_ITEM__NO_QUOTES', ($_REQUEST['_combineItem'] ?? false));
	}
	
	//Start to populate the Organizer Panel:
	//Firstly, see if other modules have added buttons/columns/refiners.
	//If so, they'll need their own placeholder methods executing as well
	
	//Note down if any buttons or refiners need any code from a different Module
	foreach (['collection_buttons', 'item_buttons', 'inline_buttons', 'refiners'] as $buttonType) {
		if (isset($tags[$buttonType]) && is_array($tags[$buttonType])) {
			foreach ($tags[$buttonType] as &$button) {
				if (is_array($button) && !empty($button['class_name'])) {
					ze\tuix::includeModule($modules, $button, $type, $requestedPath, $settingGroup);
				}
			}
			unset($button);
		}
	}
	
	$queued = !empty($_GET['_queued']);
	$sortCol = $_GET['_sort_col'] ?? false;
	
	//Have any columns been added that need formatting from their own Module?
	if (isset($tags['columns']) && is_array($tags['columns'])) {
		foreach ($tags['columns'] as $colName => &$col) {
			if (is_array($col) && !empty($col['class_name'])) {
				if (!$queued || $colName == $sortCol) {
					ze\tuix::includeModule($modules, $col, $type, $requestedPath, $settingGroup);
				}
			}
		}
	}
	
	//Run the modules' lining method to add/modify/remove columns/refiners/other tags
	foreach ($modules as $className => &$module) {
		
		//Handle the old name if it's not been changed yet
		if (method_exists($module, 'lineStorekeeper')) {
			$module->lineStorekeeper($requestedPath, $tags, ($_REQUEST['refinerName'] ?? false), ($_REQUEST['refinerId'] ?? false), $mode);
		}
		
		$module->preFillOrganizerPanel($requestedPath, $tags, ($_REQUEST['refinerName'] ?? false), ($_REQUEST['refinerId'] ?? false), $mode);
	}
	
	
	//Secondly, run any SQL queries that are needed to populate the items.
	//Refiners and pagination are also applied at this point
	if (empty($tags['items'])
	 && !empty($tags['db_items']['table'])
	 && !empty($tags['db_items']['id_column'])) {
		
		$orderBy = "";
		$whereStatement = "";
		$whereStatementWhenSearcingForChildren = "WHERE TRUE";
		$extraTables = [];
		$sortExtraTables = [];
		
		//Allow a Panel with no columns as a hack to get several complicated refiner chains working
		if (empty($tags['columns'])) {
			$tags['columns'] = [];
		}
		
		$encodeItemIdForOrganizer = ze\ring::engToBoolean($tags['db_items']['encode_id_column'] ?? false);
		
		if (!isset($tags['items']) || !is_array($tags['items'])) {
			$tags['items'] = [];
		}
		
		//Look for any custom fields/tabs
		if ($dataset = ze\row::get('custom_datasets', true, ['extends_organizer_panel' => $requestedPath])) {
			
			//Look up the name of the primary/foreign key column
			$datasetIdColumn = "custom.`". ze\escape::sql(ze\row::idColumnOfTable($dataset['table'], true)). "`";
			
			//We'll need a join to the custom table
			$customJoin = "
				LEFT JOIN `". ze\escape::sql(DB_PREFIX. $dataset['table']). "` AS custom
				ON ". $tags['db_items']['id_column']. " = ". $datasetIdColumn;
			
			//Bugfix: the join should always be added, even if the admin doesn't have the permissions,
			//just to stop any Modules that assumed the join was there from erroring
			zenario_organizer::noteTableJoin($extraTables, $customJoin);
			
			if (!$dataset['view_priv'] || ze\priv::check($dataset['view_priv'])) {
				
				//Customise system fields
				foreach (ze\row::getAssocs(
					'custom_dataset_fields',
					['field_name', 'label', 'organizer_visibility'],
					['dataset_id' => $dataset['id'], 'is_system_field' => 1]
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
				foreach (ze\row::getAssocs(
					'custom_dataset_fields',
					true,
					['dataset_id' => $dataset['id'], 'organizer_visibility' => ['!' => 'none'], 'is_system_field' => 0],
					['tab_name', 'ord']
				) as $cfield) {
					$cCol = [];
					$cCol['table_join'] = [$customJoin];
					$cCol['db_column'] = "custom.`". $cfield['db_column']. "`";
					$cCol['searchable'] = $cfield['searchable'] || $cfield['filterable'];
					if (!$cfield['searchable']) {
						$cCol['disallow_quicksearch'] = true;
					}
					if (!$cfield['filterable']) {
						$cCol['disallow_filtering'] = true;
					}
					
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
								FROM ". DB_PREFIX. "custom_dataset_values_link AS cdvl
								INNER JOIN ". DB_PREFIX. "custom_dataset_field_values AS cdfv
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
									FROM ". DB_PREFIX. "custom_dataset_field_values AS cdfv
									WHERE cdfv.id = ". $cCol['db_column']. "
									  AND cdfv.field_id = ". (int) $cfield['id']. "
									)";
					
								//If they are also searchable/filterable then we need a select list in this format
								if ($cfield['searchable']) {
									$cCol['format'] = 'enum';
									$cCol['values'] = [];
									foreach (ze\dataset::fieldLOV($cfield, true) as $displayValue) {
										$cCol['values'][$displayValue] = $displayValue;
									}
								}
								break;
							}
			
						//...otherwise we can just load the real value and format it on the client
						case 'centralised_radios':
						case 'centralised_select':
							$cCol['format'] = 'enum';
							$cCol['values'] = ze\dataset::fieldLOV($cfield, true);
							break;
			
						//Handle links to other datasets
						case 'dataset_select':
						case 'dataset_picker':
							//Try to look up all of the information we need, and come up with a LEFT JOIN
							if ($labelDetails = ze\dataset::labelFieldDetails($cfield['dataset_foreign_key_id'])) {
								
								$otherPrefix = "ot". (int) $cfield['id'];
							
								$cCol['table_join'][] = "
									LEFT JOIN `". ze\escape::sql(DB_PREFIX. $labelDetails['table']). "` AS ". $otherPrefix. "
									ON ". $otherPrefix. ".`". ze\escape::sql($labelDetails['id_column']). "` = ". $cCol['db_column'];
								
								$cCol['db_column'] = $otherPrefix. ".`". ze\escape::sql($labelDetails['db_column']). "`";
								
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
		
					$cFieldName = '__custom_field__'. ($cfield['db_column'] ?: $cfield['id']);
					$tags['columns'][$cFieldName] = $cCol;
				}
				unset($cfield);
				
				ze\tuix::flagEncryptedColumns($tags, 'custom', $dataset['table']);
			}
		}
		
		
		
		//Apply a refiners, if this panel has any and one has been selected
		if (($_GET['refinerName'] ?? false) && !empty($tags['refiners'][($_GET['refinerName'] ?? false)])) {
			
			$refinerWhere = false;
			if (isset($_GET['_search']) && !empty($tags['refiners'][($_GET['refinerName'] ?? false)]['sql_when_searching'])) {
				$refinerWhere = ltrim($tags['refiners'][($_GET['refinerName'] ?? false)]['sql_when_searching']);
			
			} elseif (!empty($tags['refiners'][($_GET['refinerName'] ?? false)]['sql'])) {
				$refinerWhere = ltrim($tags['refiners'][($_GET['refinerName'] ?? false)]['sql']);
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
			if (isset($_GET['_search']) && !empty($tags['refiners'][($_GET['refinerName'] ?? false)]['table_join_when_searching'])) {
				$refinerJoin = $tags['refiners'][($_GET['refinerName'] ?? false)]['table_join_when_searching'];
			
			} elseif (!empty($tags['refiners'][($_GET['refinerName'] ?? false)]['table_join'])) {
				$refinerJoin = $tags['refiners'][($_GET['refinerName'] ?? false)]['table_join'];
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
		
		} elseif (ze\ring::engToBoolean($tags['refiner_required'] ?? false)) {
			echo 'A refiner was required, but none was set.';
			exit;
		}
		
		
		//Loop through each database-column defined in the YAML files
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
				if ($colName == $sortCol && !ze\ring::engToBoolean($col['disallow_sorting'] ?? false)) {
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
					
					
					
					if ($colName == $sortCol
					 || (($isFiltered || isset($_GET['_search'])) && ze\ring::engToBoolean($col['searchable'] ?? false))
					 || ($isFiltered && $filterFormat && ze::in(
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
				if (!empty($col['db_column']) && ze\ring::engToBoolean($col['searchable'] ?? false) && !ze\ring::engToBoolean($col['disallow_quicksearch'] ?? false)) {
					//Group functions can't be used in a query
					if (!preg_match('/COUNT\s*\(/i', $col['db_column'])) {
						
						if ($first) {
							$first = false;
						} else {
							$whereStatement .= " OR";
						}
						
						if (!empty($col['encrypted']['hashed'])) {
							$whereStatement .= "
								". $col['encrypted']['hashed_column']. " = '". ze\escape::sql(ze\db::hashDBColumn($_GET['_search'])). "'";
						
						} else {
							
							$exactMatch = !empty($col['format']) && $col['format'] == 'id';
							$not = false;
							
							searchOrganizerColumn($whereStatement, $col['search_column'] ?? $col['db_column'], $_GET['_search'], $exactMatch, $not, $col);
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
			 && !ze\ring::engToBoolean($col['disallow_filtering'] ?? false)) {
				
				$value_ = zenario_organizer::$filters[$colName]['v'];
				
				//Special case for encrypted columns with hashed values
				if (!empty($col['encrypted']['hashed'])) {
					
					$whereStatement .= "
						AND ". $col['encrypted']['hashed_column'];
					
					if (empty(zenario_organizer::$filters[$colName]['not'])) {
						$whereStatement .= " = '";
					} else {
						$whereStatement .= " != '";
					}
					
					$whereStatement .= ze\escape::sql(ze\db::hashDBColumn($value_)). "'";
				
				} else {
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
				
					$exactMatch = false;
					$searchable = false;
					$filterFormat = false;
					if (!empty($col['format'])) {
						$filterFormat = $col['format'];
					} elseif (!empty($col['filter_format'])) {
						$filterFormat = $col['filter_format'];
					} 
				
					switch ($filterFormat) {
						case 'date':
						case 'datetime':
						case 'datetime_with_seconds':
							$dates = explode(',', $value_);
						
							if (!empty($dates[0]) && preg_replace('/\d{4}-\d{2}-\d{2}/', '', $dates[0]) == '') {
								$whereStatement .= "
								  AND ". $columnName.
									  " >= '". ze\escape::sql($dates[0]). "'";
							}
						
							if (!empty($dates[1]) && preg_replace('/\d{4}-\d{2}-\d{2}/', '', $dates[1]) == '') {
								$whereStatement .= "
								  AND ". $columnName.
									" < DATE_ADD('". ze\escape::sql($dates[1]). "', INTERVAL 1 DAY)";
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
					
							//A value of "*" should match all values (or all empty values if not is set)
							if ($value_ == '*') {
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
								break;
							}
							
						case 'id':
							$exactMatch = true;
							$searchable = true;
						
						default:
							if ($searchable || ze\ring::engToBoolean($col['searchable'] ?? false)) {
								
								$not = !empty(zenario_organizer::$filters[$colName]['not']);
								
								$whereStatement .= "
								  AND ";
								searchOrganizerColumn($whereStatement, $columnName, $value_, $exactMatch, $not, $col);
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
			if ($_GET['_sort_desc'] ?? false) {
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
			
			$organizerQueryIds = ze\dbAdm::addConstantsToString($sql);
			$result = ze\sql::select($organizerQueryIds);
			
			unset($sql);
			unset($tags);
			unset($organizerQueryIds);
			
			$first = true;
			$_GET['id'] = '';
			while ($row = ze\sql::fetchRow($result)) {
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
				if (!($_REQUEST['__pluginClassName__'] ?? false)
				 || empty($modules[($_REQUEST['__pluginClassName__'] ?? false)])) {
					echo 'Error, could not find the module for this button on this panel.';
					exit;
				}
			
				if ($_POST['_download'] ?? false) {
					$modules[($_REQUEST['__pluginClassName__'] ?? false)]->organizerPanelDownload($requestedPath, $_GET['id'], ($_REQUEST['refinerName'] ?? false), ($_REQUEST['refinerId'] ?? false));
			
				} else {
					$newIds = $modules[($_REQUEST['__pluginClassName__'] ?? false)]->handleOrganizerPanelAJAX($requestedPath, $_GET['id'], '', ($_REQUEST['refinerName'] ?? false), ($_REQUEST['refinerId'] ?? false));

					if ($newIds && !is_array($newIds)) {
						$newIds = explode(',', $newIds);
					}
			
					if ($newIds) {
						if (!is_array($_SESSION['sk_new_ids'] ?? false)) {
							$_SESSION['sk_new_ids'] = [];
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
				$in .= is_numeric($id)? (int) $id : "'". ze\escape::sql($id). "'";
			}
			
		} elseif ($groupBy === $idColumn && $mode == 'get_item_name') {
			if (isset($_REQUEST['_item'])) {
				foreach(explode(',', $_REQUEST['_item']) as $i => $id) {
					$in .= $in? ", " : "IN (";
					$in .= is_numeric($id)? (int) $id : "'". ze\escape::sql($id). "'";
				}
			
			} else {
				$limitForAJAXLookups = ze\sql::limit(1, 30);
			}
		
		} elseif ($mode == 'typeahead_search') {
			$limitForAJAXLookups = ze\sql::limit(1, 30);
		
		} elseif ($hierarchyColumn && (isset($_REQUEST['_openItemsInHierarchy']) || isset($_REQUEST['_openToItemInHierarchy']))) {
			
			//Remember the current WHERE statement so far
			$whereStatementWhenSearcingForChildren = $whereStatement;
			$openItemsInHierarchy = [];
			
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
						$sql .= "'". ze\escape::sql($id). "'";
					}
				
					if (($result = ze\sql::select(ze\dbAdm::addConstantsToString($sql)))
					 && ($row = ze\sql::fetchRow($result))
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
				$whereStatement .= ", ". (is_numeric($id)? (int) $id : "'". ze\escape::sql($id). "'");
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
			
			$organizerQueryIds = ze\dbAdm::addConstantsToString($sql);
			$result = ze\sql::select($organizerQueryIds);
			
			if (!$debugMode) {
				unset($organizerQueryIds);
			}
			
			//For panel requests that are part of a queue, don't attempt to fetch any ids, just get a count.
			if ($queued) {
				$count = ze\sql::numRows($result);
			
			//Otherwise get the list of ids in the correctly sorted order
			} else {
				$count = 0;
				$tags['__item_sort_order__'] = [];
				
				while ($row = ze\sql::fetchRow($result)) {
					
					if (++$count > $itemCountMaxLimit) {
						$count = $itemCountMaxLimit;
						$tags['db_items']['item_count_max_limit_hit'] = true;
						break;
					
					} else {
						if ($encodeItemIdForOrganizer) {
							$row[0] = ze\ring::encodeIdForOrganizer($row[0]);
						}
					
						$tags['__item_sort_order__'][] = $row[0];
					}
				}
				
				//If "_limit" is in the request, this means that server side sorting/pagination is being used
				if ($_GET['_limit'] ?? false) {
					//Apply pagination using the limit
					$start = (int) ($_GET['_start'] ?? false);
				
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
							$start = $pos - ($pos % (int) ($_GET['_limit'] ?? false));
						}
					}
				
					//Set which page this should be
					$tags['__page__'] = 1 + (int) ($start / (int) ($_GET['_limit'] ?? false));
					$stop = $start + (int) ($_GET['_limit'] ?? false);
				
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
				
					$new__item_sort_order__ = [];
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
								$thisId = ze\ring::decodeIdForOrganizer($thisId);
							}
						
							if (is_numeric($thisId)) {
								$in .= (int) $thisId;
							} else {
								$in .= "'". ze\escape::sql($thisId). "'";
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
							$thisId = ze\ring::decodeIdForOrganizer($thisId);
						}
					
						if (is_numeric($thisId)) {
							$in .= (int) $thisId;
						} else {
							$in .= "'". ze\escape::sql($thisId). "'";
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
		
		//if (($_REQUEST['new_csv_mode'] ?? false) {
		//	foreach ($tags['__item_sort_order__'] as $id) {
		//		some_module::some_function($requestedPath, $id);
		//	}
		//} else
		
		if (!$queued) {
			
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
				
				$sql .= $limitForAJAXLookups;
			
				//Loop through the results adding them into the items array (or alternately into the CSV file for CSV exports)
				$organizerQueryDetails = ze\dbAdm::addConstantsToString($sql);
			
				if ($debugMode) {
					ze\tuix::displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath, $organizerQueryIds, $organizerQueryDetails);
					exit;
				}
				$result = ze\sql::select($organizerQueryDetails);
				unset($organizerQueryDetails);
				
				if (empty($tags['db_items']['max_limit'])
				 || !($maxLimit = (int) $tags['db_items']['max_limit'])) {
					$maxLimit = $tags['db_items']['max_limit'] = 500;
				}
				$tags['db_items']['max_limit_hit'] = false;
				
				$countOfRowsToSend = 0;
				while ($row = ze\sql::fetchRow($result)) {
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
							$id = ze\ring::encodeIdForOrganizer($id);
						}
				
						$tags['items'][$id] = [];
				
						foreach ($tags['columns'] as $colName => &$col) {
							if (is_array($col) && !empty($col['db_column'])) {
								if ($col['db_column'] != 'NULL') {
									if (empty($col['encrypted'])) {
										$tags['items'][$id][$colName] = $row[++$i];
									} else {
										$tags['items'][$id][$colName] = ze\zewl::decrypt($row[++$i]);
									}
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
									  AND ". $hierarchyColumn. " = ". (is_numeric($row[0])? (int) $row[0] : "'". ze\escape::sql($row[0]). "'"). "
									LIMIT 1";
							
								$cresult = ze\sql::select(ze\dbAdm::addConstantsToString($csql));
								$tags['__item_parents__'][$row[0]] = (bool) ze\sql::fetchRow($cresult);
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
	}
	
	//Debug mode - show the TUIX before it's been modified
	if ($debugMode) {
		ze\tuix::displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath, $organizerQueryIds);
		exit;
	}
	
	//Thirdly, run each modules' fill method to add formating, and some other required attributes
	foreach ($modules as $className => &$module) {
		
		//Handle the old name if it's not been changed yet
		if (method_exists($module, 'fillStorekeeper')) {
			$module->fillStorekeeper($requestedPath, $tags, ($_REQUEST['refinerName'] ?? false), ($_REQUEST['refinerId'] ?? false), $mode);
		}
		
		$module->fillOrganizerPanel($requestedPath, $tags, ($_REQUEST['refinerName'] ?? false), ($_REQUEST['refinerId'] ?? false), $mode);
	}
	
	//Set the current item count
	if (isset($count)) {
		$tags['__item_count__'] = $count;
	} elseif (!empty($tags['items'])) {
		$tags['__item_count__'] = count($tags['items']);
	} else {
		$tags['__item_count__'] = 0;
	}
}

//Output the data in debug mode
if ($debugMode) {
	ze\tuix::displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath);
	exit;
}


//Tidy away some organizer tags
//Remove anything the current admin has no access to, count each tags' children
$removedColumns = false;
ze\tuix::parse2($tags, $removedColumns, $type, $requestedPath, $mode);

//Don't send any SQL to the client
if (isset($tags['items']) && is_array($tags['items']) && is_array($removedColumns)) {
	foreach ($tags['items'] as &$item) {
		foreach ($removedColumns as $unset) {
			unset($item[$unset]);
		}
	}
	unset($item);
}


//Only get the raw data when getting item data
if ($mode == 'get_item_data') {
	$tags = [
		'items' => $tags['items'],
		'title' => $tags['title'],
		'__item_count__' => $tags['__item_count__'],
		'__item_sort_order__' => $tags['__item_sort_order__']];


//When just fetching an item's name, strip away everything that we don't need to calculate the item name
//I also need to set the path for Menu Nodes
} elseif ($mode == 'get_item_name' || $mode == 'get_item_links' || $mode == 'typeahead_search') {
	//Add information on the item
	$output = ['item' => ($tags['item'] ?? false), 'columns' => []];
	
	if (isset($tags['items'])) {
		$output['items'] = $tags['items'];
	}
	
	if (!empty($tags['item'])) {
		$output['item'] = $tags['item'];
	}
	
	$tagString = '[[name]]';
	$usedcolumns = ['css_class' => true, 'image' => true];
	
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
					$item['name'] = ze\menuAdm::path($id, FOCUSED_LANGUAGE_ID__NO_QUOTES, $separator = ' -> ');
				}
			}
		}
	}
	
	//Add the format information for any column that has format information and is in the label tag
	if ($tagString) {
		if (isset($tags['columns']) && is_array($tags['columns'])) {
			foreach ($tags['columns'] as $colName => &$col) {
				if (!empty($usedcolumns[$colName]) && !empty($col['format'])) {
					$output['columns'][$colName] = ['format' => $col['format']];
					
					if (!empty($col['values'])) {
						$output['columns'][$colName]['values'] = $col['values'];
					}
				}
			}
		}
	}
	
	$tags = $output;

} elseif ($requestedPath) {
	//Send the filters back to the client, just in case they were adjusted in php
	$tags['_filters'] = zenario_organizer::$filters;
}



$doExport = $requestedPath && !empty($_POST['_export']) && !empty($_POST['_exportCols']);


if ($doExport) {
	//Simple export feature
	//Allow users to request anything they could see as CSV or Excel
	$isExcel = !empty($_POST['_excelExport']);
	$keys = ze\ray::explodeAndTrim($_POST['_exportCols']);
	$title = preg_replace('@\W@', '-', ($tags['title'] ?? '') ?: 'Export');
	
	//Create a new Excel document or CSV file
	if ($isExcel) {
		require_once CMS_ROOT.'zenario/libs/manually_maintained/lgpl/PHPExcel/Classes/PHPExcel.php';
		$objPHPExcel = new PHPExcel();
	} else {
		$filename = tempnam(sys_get_temp_dir(), 'csvfile');
		$f = fopen($filename, 'wb');
	}
	
	//Get the column headers
	$row = [];
	foreach ($keys as $key) {
		$row[] = $tags['columns'][$key]['title'] ?? $key;
	}
	
	//Write the column headers
	if ($isExcel) {
		$rowNum = 1;
		$objPHPExcel->getActiveSheet()->fromArray($row, NULL, 'A1');
	} else {
		fputcsv($f, $row);
	}
	
	//Loop through each row
	if (!empty($tags['items']) && is_array($tags['items'])) {
	
		if (!empty($tags['__item_sort_order__'])) {
			$ids = $tags['__item_sort_order__'];
		} else {
			$ids = array_keys($tags['items'] ?? []);
		}
		
		foreach ($ids as $id) {
			$item = $tags['items'][$id] ?? [];
			
			//For each row, get the fields that are currently visible
			$row = [];
			foreach ($keys as $key) {
				$row[] = $item[$key] ?? '';
			}
			
			//Output the row
			if ($isExcel) {
				$objPHPExcel->getActiveSheet()->fromArray($row, NULL, 'A'. ++$rowNum);
			} else {
				fputcsv($f, $row);
			}
		}
	}
	
	//Offer the file as download
	if ($isExcel) {
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename="'. $title. '.xls"');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
	
	} else {
		fclose($f);
		header('Content-Type: text/x-csv');
		header('Content-Disposition: attachment; filename="'. $title. '.csv"');
		header('Content-Length: '. filesize($filename));
		readfile($filename);
		@unlink($filename);
	}


} else {

	//Display the output as JSON to send to the client
	header('Content-Type: text/javascript; charset=UTF-8');

	if ($_REQUEST['_script'] ?? false) {
		echo 'zenarioO.lookForBranches(zenarioO.map = ';
	}

	ze\ray::jsonDump($tags);

	if ($_REQUEST['_script'] ?? false) {
		echo ');';
	}
}