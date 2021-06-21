<?php
/*
 * Copyright (c) 2021, Tribal Limited
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

if (ze\priv::check()) {
	
	$cID = ze::request('cID');
	$cType = ze::request('cType');
	$cVersion = ze::request('cVersion');
	$slotName = ze::request('slotName');
	$level = ze::request('level');
	
	
	
	$layoutId = $slotKey = false;
	if ($cID && $cType && $cVersion
	 && $cID != -1) {
		$layoutId = ze\content::layoutId($cID, $cType, $cVersion);
		$slotKey = [
			'content_id' => $cID,
			'content_type' => $cType,
			'content_version' => $cVersion,
			'slot_name' => $slotName];
	
	} elseif (ze::request('layoutId')) {
		$layoutId = (int) ze::request('layoutId');
	}
	
	if ($tagId = ze::request('slidedown_content_item_req')) {
		
		$content = ze\row::get('content_items', true, ['tag_id' => $tagId]);
		
		
		//$cID = $cType = false;
		//ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
		$result_array = [];
                
                $sql = "SELECT version, created_datetime, 
                        (SELECT username FROM " . DB_PREFIX . "admins as a WHERE a.id = v.creating_author_id) as creating_author,
                        last_modified_datetime, 
                        (SELECT username FROM " . DB_PREFIX . "admins as a WHERE a.id = v.last_author_id) as last_author,
                        published_datetime, 
                        (SELECT username FROM " . DB_PREFIX . "admins as a WHERE a.id = v.publisher_id) as publisher
                    FROM " . DB_PREFIX . "content_item_versions as v 
                    WHERE v.tag_id = '" . ze\escape::sql($tagId) . "'
                    ORDER BY v.version desc
                    LIMIT 5";
                
                $rv = [];
                if($result = ze\sql::select($sql)) {
                    while($row = ze\sql::fetchAssoc($result)) {
						$row['last_modified_datetime'] = ze\admin::formatDateTime($row['last_modified_datetime'], 'vis_date_format_med');
						$row['published_datetime'] = ze\admin::formatDateTime($row['published_datetime'], 'vis_date_format_med');
						$row['created_datetime'] = ze\admin::formatDateTime($row['created_datetime'],'vis_date_format_med');
						$row['status'] = ze\contentAdm::getContentItemVersionStatus($content, $row['version']);
						if($row['status'] == 'draft') {
							if($content['lock_owner_id']) {
								$admin_details = ze\admin::details($content['lock_owner_id']);
								$row['comments'] = ze\admin::phrase('Locked by [[username]]', $admin_details);
							}
						}
                        $rv[] = $row;
                    }
                }
                $result_array['versions'] = &$rv;
		
		
		header('Content-Type: text/javascript; charset=UTF-8');
		echo json_encode($result_array);
		exit;
	
	//Get the current SVN number
	} elseif (ze::get('infoBox')) {
		
		$realDir = realpath($logicalDir = CMS_ROOT. 'zenario');
		
		$infoBox = ['title' => ze\admin::phrase('About Zenario'), 'sections' => []];
		$section = ['title' => ze\admin::phrase('Software Information'), 'fields' => []];
		
		$section['fields'][] = ['label' => ze\admin::phrase('Edition:'), 'value' => ze\site::description('edition')];
		$section['fields'][] = ['label' => ze\admin::phrase('License:'), 'value' => ze\site::description('license_info')];
		$section['fields'][] = ['label' => ze\admin::phrase('Version:'), 'value' => ze\site::versionNumber()];
		
		if ($svninfo = ze\welcome::svnInfo()) {
			$section['fields'][] = ['label' => ze\admin::phrase('SVN revision no:'), 'value' => $svninfo['Revision']];
		
			if (!empty($svninfo['Last Changed Date'])) {
			
				if ($date = ze\admin::formatDateTime($svninfo['Last Changed Date'], false)) {
					$section['fields'][] = ['label' => ze\admin::phrase('Last SVN commit applied to this site:'), 'value' => $date];
				}
			}
		}
		$infoBox['sections'][] = $section;
		
		
		
		if (!(defined('ZENARIO_IS_DEMO_SITE') && ZENARIO_IS_DEMO_SITE)) {
			$section = ['title' => ze\admin::phrase('Installation Information'), 'fields' => []];
			
			if (ze\admin::setting('show_dev_tools')) {
				if ((function_exists('gethostname') && ($hostName = @gethostname()))
				 || ($hostName = @php_uname('n'))) {
					$section['fields'][] = ['label' => ze\admin::phrase('Server name:'), 'value' => $hostName];
				}
				
				$section['fields'][] = ['label' => ze\admin::phrase('Server IP:'), 'value' => $_SERVER['SERVER_ADDR']];
				
				if ($realDir == $logicalDir) {
					$section['fields'][] = ['label' => ze\admin::phrase('Directory:'), 'value' => CMS_ROOT, 'class' => 'zenario_infoBoxDirectory', 'type' => 'textarea'];
				} else {
					$section['fields'][] = ['label' => ze\admin::phrase('Client directory:'), 'value' => CMS_ROOT, 'class' => 'zenario_infoBoxDirectory', 'type' => 'textarea'];
					$section['fields'][] = ['label' => ze\admin::phrase('Install directory:'), 'value' => dirname($realDir), 'class' => 'zenario_infoBoxDirectory', 'type' => 'textarea'];
				}
				
				if (($row = ze\sql::fetchRow('SHOW VARIABLES LIKE "version"'))
				 && (false !== stripos($row[1], 'MariaDB'))) {
					$mrg = ['dbms' => 'MariaDB'];
				} else {
					$mrg = ['dbms' => 'MySQL'];
				}
				
				if ($size = ze\sql::fetchValue('
					SELECT SUM(data_length + index_length)
					FROM information_schema.tables
					WHERE table_schema = "'. ze\escape::sql(DBNAME). '"'
				)) {
					$formattedSize = ze\lang::formatFilesizeNicely($size, 1, true);
				}
				
				if (ze\db::hasGlobal() || ze\db::hasDataArchive()) {
					
					if (ze\db::hasGlobal()) {
						$section['fields'][] = ['label' => ze\admin::phrase('Global [[dbms]] database:', $mrg), 'value' => 
							ze\admin::phrase('[[DBNAME_GLOBAL]] on [[DBHOST_GLOBAL]], prefix [[DB_PREFIX_GLOBAL]]', get_defined_constants()), 'type' => 'textarea'];
					}
					
					$section['fields'][] = ['label' => ze\admin::phrase('Local [[dbms]] database:', $mrg), 'value' => 
						ze\admin::phrase('[[DBNAME]] on [[DBHOST]], prefix [[DB_PREFIX]]', get_defined_constants()), 'type' => 'textarea'];
					
					if (ze\db::hasDataArchive()) {
						$section['fields'][] = ['label' => ze\admin::phrase('Data archive [[dbms]] database:', $mrg), 'value' => 
							ze\admin::phrase('[[DBNAME_DA]] on [[DBHOST_DA]], prefix [[DB_PREFIX_DA]]', get_defined_constants()), 'type' => 'textarea'];
					}
				
					if ($size) {
						$section['fields'][] = ['label' => ze\admin::phrase('Local database size:', $mrg), 'value' => $formattedSize];
					}
					
					if (ze\db::hasDataArchive()) {
						if ($daSize = ze\sql\da::fetchValue('
							SELECT SUM(data_length + index_length)
							FROM information_schema.tables
							WHERE table_schema = "'. ze\escape::sql(DBNAME_DA). '"'
						)) {
							$daFormattedSize = ze\lang::formatFilesizeNicely($daSize, 1, true);
							$section['fields'][] = ['label' => ze\admin::phrase('Data archive size:', $mrg), 'value' => $daFormattedSize];
						}
					}
					
				} else {
					$section['fields'][] = ['label' => ze\admin::phrase('[[dbms]] database:', $mrg), 'value' =>
						ze\admin::phrase('[[DBNAME]] on [[DBHOST]], prefix [[DB_PREFIX]]', get_defined_constants()), 'type' => 'textarea'];
					
					if ($size) {
						$section['fields'][] = ['label' => ze\admin::phrase('[[dbms]] size:', $mrg), 'value' => $formattedSize];
					}
				}
				
				if (defined('MONGODB_DBNAME') && MONGODB_DBNAME) {
					
					$host = 'localhost';
					if (defined('MONGODB_CONNECTION_URI')) {
						$host = MONGODB_CONNECTION_URI;
						
						$pos = strrpos($host, '@');
						if ($pos !== false) {
							$host = substr($host, $pos + 1);
						}
						
						$pos = strrpos($host, '://');
						if ($pos !== false) {
							$host = substr($host, $pos + 3);
						}
					}
					
					$section['fields'][] = ['label' => ze\admin::phrase('MongoDB database (deprecated):'), 'value' =>
						ze\admin::phrase('[[DBNAME]] on [[DBHOST]]', ['DBNAME' => MONGODB_DBNAME, 'DBHOST' => $host])];
				}
				
				$infoBox['sections'][] = $section;
			
			} elseif (ze\priv::check('_PRIV_EDIT_ADMIN')) {
				$section['fields'][] = ['label' => ze\admin::phrase('Enable developer tools to see full info')/*, 'value' => ''*/];
				$infoBox['sections'][] = $section;
			}
		}
		
		
		header('Content-Type: text/javascript; charset=UTF-8');
		echo json_encode($infoBox);
		exit;
	
	//Attempt to load this list from an xml file description to add choices in for swatches from the Skin
	} elseif (ze::get('skinId')) {
		$tags = [];
		ze\skinAdm::loadDescription($_GET['skinId'] ?? false, $tags);
		ze\ray::jsonDump($tags);
	
	//Look up a Plugin ID
	} elseif (ze::get('getmoduleIdFromInstanceId')) {
		$instance = ze\plugin::details(ze::get('getmoduleIdFromInstanceId'));
		echo $instance['module_id'];
	
	} elseif (ze::get('getmoduleIdFromClassName')) {
		echo ze\module::id(ze::get('getmoduleIdFromClassName'));
		
	} elseif (ze::post('getMenuItemStorekeeperDeepLink')) {
		echo ze\menuAdm::organizerLink(ze::post('getMenuItemStorekeeperDeepLink'), ze::request('languageId'));
		
	//Handle getting the URLs for items
	} elseif (ze::post('getItemURL')) {
		$request = '';
		$cID = $cType = false;
		ze\content::getCIDAndCTypeFromTagId($cID, $cType, ze::post('id'));
		
		//Links for documents should be a download-now link by default
		if ($cType == 'document') {
			$request = '&download=1';
		}
		
		echo ze\link::toItem($cID, $cType, false, $request, false, false, true);
		exit;
		
	//Get a preview of a date format
	} elseif (ze::get('previewDateFormat')) {
		echo ze\admin::formatDate(ze\date::now(), (ze::get('previewDateFormat')), true);
		exit;
	
	//Toggle dev tools on/off
	} elseif (isset($_POST['show_dev_tools']) && ze\priv::check('_PRIV_EDIT_ADMIN')) {
		ze\admin::setSetting('show_dev_tools', (int) !empty($_POST['show_dev_tools']));
	
	//Otherwise handle requests for slots
	} else {
		//Update the last modification date if making a change to a Content Item
		if ($cID && $cType && $cVersion
		 && $cID != -1
		 && ze\priv::check(false, $cID, $cType, $cVersion)) {
			ze\contentAdm::updateVersion($cID, $cType, $cVersion);
		}
	
		//Insert a Reuasble Plugin into a slot
		if (ze::post('addPluginInstance') && $level == 1 && ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $cID, $cType, $cVersion)) {
			ze\pluginAdm::updateItemSlot(ze::post('addPluginInstance'), $slotName, $cID, $cType, $cVersion);
	
		} elseif (ze::post('addPluginInstance') && $level == 2 && ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT') && $layoutId) {
			ze\pluginAdm::updateLayoutSlot(ze::post('addPluginInstance'), $slotName, $layoutId);
		
			//To avoid confusin, also remove the "hide plugin on this content item" option
			//for this slot on this version of this content item if it has been set.
			//(But don't touch any other versions/content items, even if they're also hidden.)
			ze\pluginAdm::unhide($cID, $cType, $cVersion, $slotName);
	
		//Insert a version-controlled plugin into a slot
		} elseif (ze::get('addPlugin')) {
			
			$mrg = ['pages' => ze\layoutAdm::usage($layoutId, false),
							'published' => ze\layoutAdm::usage($layoutId, true),
							'displayName' => htmlspecialchars(ze\module::displayName(ze::get('addPlugin'))),
							'slotName' => htmlspecialchars(ze::get('slotName'))];
			
			if ($mrg['pages'] == 1) {
				echo ze\admin::phrase(
					'Insert a version-controlled [[displayName]] into slot [[slotName]] on this layout?
					<br/><br/>
					The content or settings of this plugin will then be editable via the Edit tab.
					<br/><br/>
					This will affect just this content item, so <b>[[published]] published</b> content items.'
				, $mrg);
			} else {
				echo ze\admin::phrase(
					'Insert a version-controlled [[displayName]] into slot [[slotName]] on this layout?
					<br/><br/>
					The content or settings of this plugin will then be editable via the Edit tab.
					<br/><br/>
					This will affect [[pages]] content items, including <b>[[published]] published</b> content items.'
				, $mrg);
			}
	
		} elseif (ze::post('addPlugin') && ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT') && $layoutId) {
			ze\pluginAdm::updateLayoutSlot(0, $slotName, $layoutId, ze::post('addPlugin'));
		
			//To avoid confusin, also remove the "hide plugin on this content item" option
			//for this slot on this version of this content item if it has been set.
			//(But don't touch any other versions/content items, even if they're also hidden.)
			ze\pluginAdm::unhide($cID, $cType, $cVersion, $slotName);
		
		
		
		//Handle copying/cutting/pasting/etc.
		} elseif (ze::post('copyContents') || ze::post('cutContents')) {
			$_SESSION['admin_copied_contents'] =
				ze\contentAdm::getPluginContent($slotKey);
			
			$_SESSION['admin_copied_contents']['allowed'] = [];
			foreach (ze\ray::explodeAndTrim(ze::post('allowedModules')) as $module) {
				$_SESSION['admin_copied_contents']['allowed'][$module] = true;
			}
			
			if (ze::post('cutContents') && ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType, $cVersion)) {
				ze\contentAdm::setPluginContent($slotKey);
			}
			
		} elseif ((ze::post('pasteContents') || ze::post('overwriteContents') || ze::post('swapContents')) && ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType, $cVersion)) {
			$oldContent = ze\contentAdm::getPluginContent($slotKey);
			
			if (empty($_SESSION['admin_copied_contents'])) {
				echo ze\admin::phrase('Nothing has been copied');
				exit;
			
			} elseif (!$oldContent) {
				echo ze\admin::phrase('Could not load slot');
				exit;
			
			} elseif (!isset($_SESSION['admin_copied_contents']['allowed'][$oldContent['class_name']])) {
				echo ze\admin::phrase('Content copied from a [[displayName]] cannot be used here', ['displayName' => ze\module::getModuleDisplayNameByClassName($oldContent['class_name'])]);
				exit;
			
			} else {
				ze\contentAdm::setPluginContent($slotKey, $_SESSION['admin_copied_contents']);
				
				if (ze::post('swapContents')) {
					$oldContent['allowed'] = $_SESSION['admin_copied_contents']['allowed'];
					$_SESSION['admin_copied_contents'] = $oldContent;
				}
			}
		
		
		
		//Hide a plugin on this page
		} elseif (ze::post('hidePlugin') && ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $cID, $cType, $cVersion)) {
			ze\pluginAdm::updateItemSlot(
				0,
				$slotName, $cID, $cType, $cVersion);
	
		//Handle removing modules
		//(Get the number of Content Items that use this template/template family)
		} elseif ((ze::get('removePlugin') || ze::get('movePlugin')) && $level == 2) {
			
			$mrg = [
				'codeName' => ze\layoutAdm::codeName($layoutId),
				'pages' => ze\layoutAdm::usage($layoutId, false),
				'published' => ze\layoutAdm::usage($layoutId, true),
				'slotName' => $slotName
			];
			
			//Show how many items use a specific to slotName, and display links if possible.
			$usageContentItems = ze\layoutAdm::slotUsage($layoutId, $slotName);	
			$usage = [
				'content_item' => $usageContentItems[0] ?? null,
				'content_items' => count($usageContentItems)
			];
	
			$mrg['usedPages'] = implode('; ', ze\miscAdm::getUsageText($usage, true));
			
			$usagePluginItems = ze\layoutAdm::usage($layoutId, false, false, $countItems = false);

			$pluginusage = [
				'content_item' => $usagePluginItems[0] ?? null,
				'content_items' => count($usagePluginItems)
			];
	
			$pluginusageLinks = [
				'content_items' => 'zenario__layouts/panels/layouts/item_buttons/view_content//'. (int) $layoutId. '//'
			];
			$mrg['pluginusedPages'] = implode('; ', ze\miscAdm::getUsageText($pluginusage, true));
			
			$organizerPath = 'zenario/admin/organizer.php#zenario__layouts/panels/layouts/item_buttons/view_content//'. (int) $layoutId. '//';
			$mrg['organizerLink'] = '<a href="'. htmlspecialchars($organizerPath). '" target="blank">'. ze\admin::phrase('view the content items using this layout'). '</a>';
			
				

			$placement = ze\row::get(
				'plugin_layout_link',
				['module_id', 'instance_id'],
				[
					'slot_name' => $slotName,
					'layout_id' => $layoutId]);
					
			if (!empty($placement['module_id']) && !$placement['instance_id']) {
				$mrg['displayName'] = htmlspecialchars(ze\module::displayName($placement['module_id']));
				
				if (ze::get('movePlugin')) {
					echo ze\admin::phrase('Are you sure you wish to move the [[displayName]]?<br/><br/>This will affect [[pages]] content item(s), <b>[[published]] of them is published</b>.', $mrg);
				} else {
					if (isset($usageContentItems[0]) && !$usageContentItems[0]) {
						echo ze\admin::phrase('Are you sure you wish to remove the plugin [[displayName]] from the layout [[codeName]]? <br/><br/>No content items have version-controlled content in this slot.', $mrg);
					} else {
						echo ze\admin::phrase('Are you sure you wish to remove the plugin [[displayName]] from the layout [[codeName]]? <br/><br/>This slot contains content on [[usedPages]], [[organizerLink]].<br/><br/>You should review, edit and remove the content before removing the plugin from the layout, or else the content will be lost!', $mrg);
					}
				}
			} else {
				if (ze::get('movePlugin')) {
					echo ze\admin::phrase('Are you sure you wish to move this plugin?<br/><br/>This will affect [[pages]] content item(s), <b>[[published]] of them is published</b>.', $mrg);
				} else {
					$mrg['pluginName'] = htmlspecialchars(ze\plugin::name($placement['instance_id']));
					$mrg['displayName'] = htmlspecialchars(ze\module::displayName($placement['module_id']));
					echo ze\admin::phrase('Plugin [[pluginName]] ("[[displayName]]") is in slot [[slotName]] on layout [[codeName]], used on [[pluginusedPages]]. [[organizerLink]]<br/><br/> Are you sure you wish to remove this plugin from the layout?', $mrg);
				}
			}
	
		} elseif ((ze::post('removePlugin') && $level == 1 && ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $cID, $cType, $cVersion))
				|| (ze::post('showPlugin') && ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $cID, $cType, $cVersion))) {
			ze\pluginAdm::updateItemSlot(
				'',
				$slotName, $cID, $cType, $cVersion);
	
		} elseif (ze::post('removePlugin') && $level == 2 && ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT')) {
			ze\pluginAdm::updateLayoutSlot(
				'',
				$slotName, $layoutId);
		
			//To avoid confusin, also remove the "hide plugin on this content item" option
			//for this slot on this version of this content item if it has been set.
			//(But don't touch any other versions/content items, even if they're also hidden.)
			ze\pluginAdm::unhide($cID, $cType, $cVersion, $slotName);
	
		//Handle moving modules
		//Move a Plugin from one slot to another, at a specific level.
		//Swapping two modules around is allowed, so we'll need logic that completely switches the Contents of two slots around.
		//We also need to carefully update the slotnames on the instances table for Wireframe modules
		} elseif (ze::post('movePlugin')) {
			//Create arrays containing which tables to move data ze::in (this will always be the plugin_instances table and one of the link tables,
			//depending on the level) and which Content Items are affected.
			$tables = [];
		
			//To move at an item level, we need only check this Content Item
			if ($level == 1 && ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $cID, $cType, $cVersion)) {
				$version = [['content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion]];
				$tables['plugin_item_link'] = $version;
				$tables['plugin_instances'] = $version;
		
			//For layouts, we need to check which Content Items use the selected Layout
			} elseif ($level == 2 && ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT')) {
				$tables['plugin_layout_link'] = [['layout_id' => $layoutId]];
				$tables['plugin_instances'] = [];
				if ($result = ze\row::query('content_item_versions', ['id', 'type', 'version'], ['layout_id' => $layoutId])) {
					while ($row = ze\sql::fetchAssoc($result)) {
						//if (!ze\row::exists('plugin_item_link'
						$tables['plugin_instances'][] =
							['content_id' => $row['id'], 'content_type' => $row['type'], 'content_version' => $row['version']];
					}
				}
		
			} else {
				exit;
			}
		
			//The above logic will have given us one of the linking tables, a key to match that linking table, and an array of Content Items
			//Loop through all of that, updating slot names
			foreach ($tables as $table => $ids) {
				foreach ($ids as $id) {
				
					//If there are reusable modules in Slots, they can simply be switched without worrying about maintaing the relationship
					//between slots and settings.
					//However we have to be very careful to move the right Settings for Wireframe modules
					if ($table == 'plugin_item_link' || $table == 'plugin_layout_link') {
						//Firstly, check the linking tables to see which modules we are supposed to be moving, and whether they are wireframe modules
						$id['slot_name'] = ze::post('slotNameSource');
						$sourcePlugin = ze\row::get($table, ['module_id', 'instance_id'], $id);
					
						$id['slot_name'] = ze::post('slotNameDestination');
						$destinationPlugin = ze\row::get($table, ['module_id', 'instance_id'], $id);
					
						//Whatever was in the linking tables won't stop us moving the values of the linking tables, so now we continue with the move.
						//But remember what the values were for when we are moving Wireframe Plugin Settings
						unset($id['slot_name']);
				
					} elseif ($table == 'plugin_instances') {
						//For each Content Item, check to see if there is a Wireframe Plugin in a level above the level that we're trying to move
						$sourcePluginItem = $destinationPluginItem = $sourcePluginTemplate = $destinationPluginTemplate = false;
					
						//If this move should be on a Layout, check which Plugin is in at an Item level for each Content Item
						if ($level == 2) {
							$sourcePluginItem =
								ze\row::get('plugin_item_link',
									['module_id', 'instance_id'],
									['content_id' => $id['content_id'], 'content_type' => $id['content_type'], 'content_version' => $id['content_version'], 'slot_name' => ze::post('slotNameSource')]);
							$destinationPluginItem =
								ze\row::get('plugin_item_link',
									['module_id', 'instance_id'],
									['content_id' => $id['content_id'], 'content_type' => $id['content_type'], 'content_version' => $id['content_version'], 'slot_name' => ze::post('slotNameDestination')]);
						}
					}
				
					$i = 0;
					foreach ([
						ze::post('slotNameSource') => '%%%',
						ze::post('slotNameDestination') => ze::post('slotNameSource'),
						'%%%' => ze::post('slotNameDestination')
					] as $from => $to) {
						if ($table == 'plugin_instances') {
							//The settings for Wireframe modules are stored by Content Item, Version, Slot and Plugin ID. But not Level.
							//To work around the possibly problem of moving the settings at the wrong level due to no level information,
							//we'll only move settings that match the Plugin ID
							$module = (++$i % 2? $sourcePlugin : $destinationPlugin);
						
							//There's no need to move settings that will not be for Wireframe modules, or that will be for different modules
							if (!(!empty($module['module_id']) && empty($module['instance_id']))) {
								continue;
							}
						
							//Don't attempt to move a setting that is actually for a Wireframe set at a higher level to the level we are moving
							foreach ([$sourcePluginItem, $sourcePluginTemplate, $destinationPluginItem, $destinationPluginTemplate] as $pluginB) {
								if (!empty($pluginB['module_id']) && empty($pluginB['instance_id']) && $module['module_id'] == $pluginB['module_id']) {
									continue;
								}
							}
						
							//If the above logic is followed, there should never be anything in the way, but just in case there is
							//then this statement is here to remove junk data before it causes a bug
							$id['slot_name'] = $to;
							ze\row::delete($table, $id);
						
							//Ensure that only settings for this plugin will be moved
							$id['module_id'] = $module['module_id'];
						}
					
						//Move the Plugin's Placement in the linking tables, or the Wireframe Plugin's settings in the plugin instance table, to the new slot
						$id['slot_name'] = $from;
						ze\row::update($table, ['slot_name' => $to], $id);
					}
				}
			}
		}
	}

} else {
	header('Zenario-Admin-Logged_Out: 1');
	echo '<!--Logged_Out-->', ze\admin::phrase('You have been logged out.');
}

return false;
