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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

if (checkPriv()) {
	$layoutId = $templateFamily = $slotKey = false;
	if (($_REQUEST['cID'] ?? false) && ($_REQUEST['cType'] ?? false) && ($_REQUEST['cVersion'] ?? false)
	 && ($_REQUEST['cID'] ?? false) != -1) {
		$layoutId = contentItemTemplateId($_REQUEST['cID'] ?? false, ($_REQUEST['cType'] ?? false), ($_REQUEST['cVersion'] ?? false));
		$slotKey = array(
			'content_id' => ($_REQUEST['cID'] ?? false),
			'content_type' => ($_REQUEST['cType'] ?? false),
			'content_version' => ($_REQUEST['cVersion'] ?? false),
			'slot_name' => ($_REQUEST['slotName'] ?? false));
	
	} elseif ($_REQUEST['layoutId'] ?? false) {
		$layoutId = (int) ($_REQUEST['layoutId'] ?? false);
	}
	
	if ($layoutId) {
		$templateFamily = getRow('layouts', 'family_name', $layoutId);
	}
	
	
	if ($tagId = $_REQUEST['slidedown_content_item_req'] ?? false) {
		
		$content = getRow('content_items', true, array('tag_id' => $tagId));
		
		
		//$cID = $cType = false;
		//getCIDAndCTypeFromTagId($cID, $cType, $tagId);
		$result_array = array();
                
                $sql = "SELECT version, created_datetime, 
                        (SELECT username FROM " . DB_NAME_PREFIX . "admins as a WHERE a.id = v.creating_author_id) as creating_author,
                        last_modified_datetime, 
                        (SELECT username FROM " . DB_NAME_PREFIX . "admins as a WHERE a.id = v.last_author_id) as last_author,
                        published_datetime, 
                        (SELECT username FROM " . DB_NAME_PREFIX . "admins as a WHERE a.id = v.publisher_id) as publisher
                    FROM " . DB_NAME_PREFIX . "content_item_versions as v 
                    WHERE v.tag_id = '" . sqlEscape($tagId) . "'
                    ORDER BY v.version desc
                    LIMIT 5";
                
                $rv = array();
                if($result = sqlQuery($sql)) {
                    while($row = sqlFetchAssoc($result)) {
						$row['last_modified_datetime'] = formatDateTimeNicely($row['last_modified_datetime'], 'vis_date_format_med');
						$row['published_datetime'] = formatDateTimeNicely($row['published_datetime'], 'vis_date_format_med');
						$row['created_datetime'] = formatDateTimeNicely($row['created_datetime'],'vis_date_format_med');
						$row['status'] = getContentItemVersionStatus($content, $row['version']);
						if($row['status'] == 'draft') {
							if($content['lock_owner_id']) {
								$admin_details = getAdminDetails($content['lock_owner_id']);
								$row['comments'] = adminPhrase('Locked by [[username]]', $admin_details);
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
	} elseif ($_GET['infoBox'] ?? false) {
		
		$realDir = realpath($logicalDir = CMS_ROOT. 'zenario');
		
		$infoBox = array('title' => adminPhrase('About Zenario'), 'sections' => array());
		$section = array('title' => adminPhrase('Software Information'), 'fields' => array());
		
		$section['fields'][] = array('label' => adminPhrase('Edition:'), 'value' => siteDescription('edition'));
		$section['fields'][] = array('label' => adminPhrase('License:'), 'value' => siteDescription('license_info'));
		$section['fields'][] = array('label' => adminPhrase('Version:'), 'value' => getCMSVersionNumber());
		
		require_once CMS_ROOT. 'zenario/includes/welcome.inc.php';
		if ($svninfo = getSVNInfo()) {
			$section['fields'][] = array('label' => adminPhrase('SVN revision no:'), 'value' => $svninfo['Revision']);
		
			if (!empty($svninfo['Last Changed Date'])) {
			
				if ($date = formatDateTimeNicely($svninfo['Last Changed Date'], false, languageIdForDatesInAdminMode())) {
					$section['fields'][] = array('label' => adminPhrase('Last SVN commit applied to this site:'), 'value' => $date);
				}
			}
		}
		$infoBox['sections'][] = $section;
		
		
		
		if (!(defined('ZENARIO_IS_DEMO_SITE') && ZENARIO_IS_DEMO_SITE)) {
			$section = array('title' => adminPhrase('Installation Information'), 'fields' => array());
			
			if (adminSetting('show_dev_tools')) {
				if ((function_exists('gethostname') && ($hostName = @gethostname()))
				 || ($hostName = @php_uname('n'))) {
					$section['fields'][] = array('label' => adminPhrase('Server name:'), 'value' => $hostName);
				}
				
				$section['fields'][] = array('label' => adminPhrase('Server IP:'), 'value' => $_SERVER['SERVER_ADDR']);
				
				if ($realDir == $logicalDir) {
					$section['fields'][] = array('label' => adminPhrase('Directory:'), 'value' => CMS_ROOT, 'class' => 'zenario_infoBoxDirectory');
				} else {
					$section['fields'][] = array('label' => adminPhrase('Client directory:'), 'value' => CMS_ROOT, 'class' => 'zenario_infoBoxDirectory');
					$section['fields'][] = array('label' => adminPhrase('Install directory:'), 'value' => dirname($realDir), 'class' => 'zenario_infoBoxDirectory');
				}
				
				if (($row = sqlFetchRow('SHOW VARIABLES LIKE "version"'))
				 && (false !== stripos($row[1], 'MariaDB'))) {
					$mrg = ['dbms' => 'MariaDB'];
				} else {
					$mrg = ['dbms' => 'MySQL'];
				}
				
				if ($size = sqlFetchValue('
					SELECT SUM(data_length + index_length)
					FROM information_schema.tables
					WHERE table_schema = "'. sqlEscape(DBNAME). '"'
				)) {
					$formattedSize = formatFilesizeNicely($size, 1, true);
				}
				
				if (globalDBDefined()) {
					$section['fields'][] = array('label' => adminPhrase('Global [[dbms]] database:', $mrg), 'value' => 
						adminPhrase('[[DBNAME_GLOBAL]] on [[DBHOST_GLOBAL]], prefix [[DB_NAME_PREFIX_GLOBAL]]', get_defined_constants()));
					$section['fields'][] = array('label' => adminPhrase('Local [[dbms]] database:', $mrg), 'value' => 
						adminPhrase('[[DBNAME]] on [[DBHOST]], prefix [[DB_NAME_PREFIX]]', get_defined_constants()));
					
					if ($size) {
						$section['fields'][] = array('label' => adminPhrase('Local database size:', $mrg), 'value' => $formattedSize);
					}
					
				} else {
					$section['fields'][] = array('label' => adminPhrase('[[dbms]] database:', $mrg), 'value' =>
						adminPhrase('[[DBNAME]] on [[DBHOST]], prefix [[DB_NAME_PREFIX]]', get_defined_constants()));
					
					if ($size) {
						$section['fields'][] = array('label' => adminPhrase('[[dbms]] size:', $mrg), 'value' => $formattedSize);
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
					
					$section['fields'][] = array('label' => adminPhrase('MongoDB database:'), 'value' =>
						adminPhrase('[[DBNAME]] on [[DBHOST]]', array('DBNAME' => MONGODB_DBNAME, 'DBHOST' => $host)));
				}
				
				$infoBox['sections'][] = $section;
			
			} elseif (checkPriv('_PRIV_EDIT_ADMIN')) {
				$section['fields'][] = array('label' => adminPhrase('Enable developer tools to see full info')/*, 'value' => ''*/);
				$infoBox['sections'][] = $section;
			}
		}
		
		
		header('Content-Type: text/javascript; charset=UTF-8');
		echo json_encode($infoBox);
		exit;
	
	//Attempt to load this list from an xml file description to add choices in for swatches from the Skin
	} elseif ($_GET['skinId'] ?? false) {
		$tags = array();
		loadSkinDescription($_GET['skinId'] ?? false, $tags);
		jsonEncodeForceObject($tags);
	
	//Look up a Plugin ID
	} elseif ($_GET['getmoduleIdFromInstanceId'] ?? false) {
		$instance = getPluginInstanceDetails($_GET['getmoduleIdFromInstanceId'] ?? false);
		echo $instance['module_id'];
	
	} elseif ($_GET['getmoduleIdFromClassName'] ?? false) {
		echo getModuleIdByClassName($_GET['getmoduleIdFromClassName'] ?? false);
		
	} elseif ($_POST['getMenuItemStorekeeperDeepLink'] ?? false) {
		echo getMenuItemStorekeeperDeepLink($_POST['getMenuItemStorekeeperDeepLink'] ?? false, ($_REQUEST['languageId'] ?? false));
		
	//Handle getting the URLs for items
	} elseif ($_POST['getItemURL'] ?? false) {
		$request = '';
		$cID = $cType = false;
		getCIDAndCTypeFromTagId($cID, $cType, ($_POST['id'] ?? false));
		
		//Links for documents should be a download-now link by default
		if ($cType == 'document') {
			$request = '&download=1';
		}
		
		echo linkToItem($cID, $cType, false, $request, false, false, true);
		exit;
		
	//Get a preview of a date format
	} elseif ($_GET['previewDateFormat'] ?? false) {
		echo formatDateNicely(now(), ($_GET['previewDateFormat'] ?? false), true);
		exit;
	
	//Toggle dev tools on/off
	} elseif (isset($_POST['show_dev_tools']) && checkPriv('_PRIV_EDIT_ADMIN')) {
		setAdminSetting('show_dev_tools', (int) !empty($_POST['show_dev_tools']));
	
	//Otherwise handle requests for slots
	} else {
		//Update the last modification date if making a change to a Content Item
		if (($_POST['cID'] ?? false) && ($_POST['cType'] ?? false) && ($_POST['cVersion'] ?? false)
		 && ($_POST['cID'] ?? false) != -1
		 && checkPriv(false, ($_POST['cID'] ?? false), ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false))) {
			updateVersion($_POST['cID'] ?? false, ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false));
		}
	
		//Insert a Reuasble Plugin into a slot
		if (($_POST['addPluginInstance'] ?? false) && ($_POST['level'] ?? false) == 1 && checkPriv('_PRIV_MANAGE_ITEM_SLOT', ($_REQUEST['cID'] ?? false), ($_REQUEST['cType'] ?? false), ($_REQUEST['cVersion'] ?? false))) {
			updatePluginInstanceInItemSlot($_POST['addPluginInstance'] ?? false, ($_POST['slotName'] ?? false), ($_POST['cID'] ?? false), ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false));
	
		} elseif (($_POST['addPluginInstance'] ?? false) && ($_POST['level'] ?? false) == 2 && checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT') && $layoutId && $templateFamily) {
			updatePluginInstanceInTemplateSlot($_POST['addPluginInstance'] ?? false, ($_POST['slotName'] ?? false), $templateFamily, $layoutId);
		
			//To avoid confusin, also remove the "hide plugin on this content item" option
			//for this slot on this version of this content item if it has been set.
			//(But don't touch any other versions/content items, even if they're also hidden.)
			unhidePlugin($_POST['cID'] ?? false, ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false), ($_POST['slotName'] ?? false));
	
		//Insert a version-controlled plugin into a slot
		} elseif ($_GET['addPlugin'] ?? false) {
			
			$mrg = array('pages' => checkTemplateUsage($layoutId, false, false),
							'published' => checkTemplateUsage($layoutId, false, true),
							'display_name' => htmlspecialchars(getModuleDisplayName($_GET['addPlugin'] ?? false)),
							'slotName' => htmlspecialchars($_GET['slotName'] ?? false));
			
			if ($mrg['pages'] == 1) {
				echo adminPhrase(
					'Are you sure you wish to insert a [[display_name]] into slot [[slotName]] on this layout?
					<br/><br/>
					This will affect [[pages]] content item, <b>[[published]] published</b>.
					<br/><br/>
					The content item will gain its own version-controlled [[display_name]], which you can modify on the Edit tab.'
				, $mrg);
			} else {
				echo adminPhrase(
					'Are you sure you wish to insert a [[display_name]] into slot [[slotName]] on this layout?
					<br/><br/>
					This will affect [[pages]] content items, <b>[[published]] published</b>.
					<br/><br/>
					Each content item will gain its own version-controlled [[display_name]], which you can modify on the Edit tab.'
				, $mrg);
			}
	
		} elseif (($_POST['addPlugin'] ?? false) && checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT') && $layoutId && $templateFamily) {
			updatePluginInstanceInTemplateSlot(0, ($_POST['slotName'] ?? false), $templateFamily, $layoutId, ($_POST['addPlugin'] ?? false));
		
			//To avoid confusin, also remove the "hide plugin on this content item" option
			//for this slot on this version of this content item if it has been set.
			//(But don't touch any other versions/content items, even if they're also hidden.)
			unhidePlugin($_POST['cID'] ?? false, ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false), ($_POST['slotName'] ?? false));
		
		
		
		//Handle copying/cutting/pasting/etc.
		} elseif (($_POST['copyContents'] ?? false) || ($_POST['cutContents'] ?? false)) {
			$_SESSION['admin_copied_contents'] =
				getPluginContent($slotKey);
			
			$_SESSION['admin_copied_contents']['allowed'] = array();
			foreach (explodeAndTrim($_POST['allowedModules'] ?? false) as $module) {
				$_SESSION['admin_copied_contents']['allowed'][$module] = true;
			}
			
			if (($_POST['cutContents'] ?? false) && checkPriv('_PRIV_EDIT_DRAFT', ($_REQUEST['cID'] ?? false), ($_REQUEST['cType'] ?? false), ($_REQUEST['cVersion'] ?? false))) {
				setPluginContent($slotKey);
			}
			
		} elseif ((($_POST['pasteContents'] ?? false) || ($_POST['overwriteContents'] ?? false) || ($_POST['swapContents'] ?? false)) && checkPriv('_PRIV_EDIT_DRAFT', ($_REQUEST['cID'] ?? false), ($_REQUEST['cType'] ?? false), ($_REQUEST['cVersion'] ?? false))) {
			$oldContent = getPluginContent($slotKey);
			
			if (empty($_SESSION['admin_copied_contents'])) {
				echo adminPhrase('Nothing has been copied');
				exit;
			
			} elseif (!$oldContent) {
				echo adminPhrase('Could not load slot');
				exit;
			
			} elseif (!isset($_SESSION['admin_copied_contents']['allowed'][$oldContent['class_name']])) {
				echo adminPhrase('Content copied from a [[display_name]] cannot be used here', array('display_name' => getModuleDisplayNameByClassName($oldContent['class_name'])));
				exit;
			
			} else {
				setPluginContent($slotKey, $_SESSION['admin_copied_contents']);
				
				if ($_POST['swapContents'] ?? false) {
					$oldContent['allowed'] = $_SESSION['admin_copied_contents']['allowed'];
					$_SESSION['admin_copied_contents'] = $oldContent;
				}
			}
		
		
		
		//Hide a plugin on this page
		} elseif (($_POST['hidePlugin'] ?? false) && checkPriv('_PRIV_MANAGE_ITEM_SLOT', ($_POST['cID'] ?? false), ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false))) {
			updatePluginInstanceInItemSlot(
				0,
				($_POST['slotName'] ?? false), ($_POST['cID'] ?? false), ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false));
	
		//Handle removing modules
		//(Get the number of Content Items that use this template/template family)
		} elseif ((($_GET['removePlugin'] ?? false) || ($_GET['movePlugin'] ?? false)) && ($_GET['level'] ?? false) == 2) {
			
			$mrg = array('pages' => checkTemplateUsage($layoutId, false, false), 'published' => checkTemplateUsage($layoutId, false, true));

			$placement = getRow(
				'plugin_layout_link',
				array('module_id', 'instance_id'),
				array(
					'slot_name' => ($_GET['slotName'] ?? false),
					'family_name' => $templateFamily,
					'layout_id' => $layoutId));
			
			if (!empty($placement['module_id']) && !$placement['instance_id']) {
				$mrg['display_name'] = htmlspecialchars(getModuleDisplayName($placement['module_id']));
				
				if ($_GET['movePlugin'] ?? false) {
					echo adminPhrase('Are you sure you wish to move the [[display_name]]?<br/><br/>This will affect [[pages]] Content Item(s), <b>[[published]] Published</b>.', $mrg);
				} else {
					echo adminPhrase('Are you sure you wish to remove the [[display_name]] from the layout?<br/><br/>This will affect [[pages]] Content Item(s), <b>[[published]] Published</b>.', $mrg);
				}
			} else {
				if ($_GET['movePlugin'] ?? false) {
					echo adminPhrase('Are you sure you wish to move this plugin?<br/><br/>This will affect [[pages]] Content Item(s), <b>[[published]] Published</b>.', $mrg);
				} else {
					echo adminPhrase('Are you sure you wish to remove this plugin from the layout?<br/><br/>This will affect [[pages]] Content Item(s), <b>[[published]] Published</b>.', $mrg);
				}
			}
	
		} elseif ((($_POST['removePlugin'] ?? false) && ($_POST['level'] ?? false) == 1 && checkPriv('_PRIV_MANAGE_ITEM_SLOT', ($_POST['cID'] ?? false), ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false)))
				|| (($_POST['showPlugin'] ?? false) && checkPriv('_PRIV_MANAGE_ITEM_SLOT', ($_POST['cID'] ?? false), ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false)))) {
			updatePluginInstanceInItemSlot(
				'',
				($_POST['slotName'] ?? false), ($_POST['cID'] ?? false), ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false));
	
		} elseif (($_POST['removePlugin'] ?? false) && ($_POST['level'] ?? false) == 2 && checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT')) {
			updatePluginInstanceInTemplateSlot(
				'',
				($_POST['slotName'] ?? false), $templateFamily, $layoutId);
		
			//To avoid confusin, also remove the "hide plugin on this content item" option
			//for this slot on this version of this content item if it has been set.
			//(But don't touch any other versions/content items, even if they're also hidden.)
			unhidePlugin($_POST['cID'] ?? false, ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false), ($_POST['slotName'] ?? false));
	
		//Handle moving modules
		//Move a Plugin from one slot to another, at a specific level.
		//Swapping two modules around is allowed, so we'll need logic that completely switches the Contents of two slots around.
		//We also need to carefully update the slotnames on the instances table for Wireframe modules
		} elseif ($_POST['movePlugin'] ?? false) {
			//Create arrays containing which tables to move data in (this will always be the plugin_instances table and one of the link tables,
			//depending on the level) and which Content Items are affected.
			$tables = array();
		
			//To move at an item level, we need only check this Content Item
			if (($_POST['level'] ?? false) == 1 && checkPriv('_PRIV_MANAGE_ITEM_SLOT', ($_POST['cID'] ?? false), ($_POST['cType'] ?? false), ($_POST['cVersion'] ?? false))) {
				$version = array(array('content_id' => ($_POST['cID'] ?? false), 'content_type' => ($_POST['cType'] ?? false), 'content_version' => ($_POST['cVersion'] ?? false)));
				$tables['plugin_item_link'] = $version;
				$tables['plugin_instances'] = $version;
		
			//For layouts, we need to check which Content Items use the selected Layout
			} elseif (($_POST['level'] ?? false) == 2 && checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT')) {
				$tables['plugin_layout_link'] = array(array('layout_id' => $layoutId, 'family_name' => $templateFamily));
				$tables['plugin_instances'] = array();
				if ($result = getRows('content_item_versions', array('id', 'type', 'version'), array('layout_id' => $layoutId))) {
					while ($row = sqlFetchAssoc($result)) {
						//if (!checkRowExists('plugin_item_link'
						$tables['plugin_instances'][] =
							array('content_id' => $row['id'], 'content_type' => $row['type'], 'content_version' => $row['version']);
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
						$id['slot_name'] = $_POST['slotNameSource'] ?? false;
						$sourcePlugin = getRow($table, array('module_id', 'instance_id'), $id);
					
						$id['slot_name'] = $_POST['slotNameDestination'] ?? false;
						$destinationPlugin = getRow($table, array('module_id', 'instance_id'), $id);
					
						//Whatever was in the linking tables won't stop us moving the values of the linking tables, so now we continue with the move.
						//But remember what the values were for when we are moving Wireframe Plugin Settings
						unset($id['slot_name']);
				
					} elseif ($table == 'plugin_instances') {
						//For each Content Item, check to see if there is a Wireframe Plugin in a level above the level that we're trying to move
						$sourcePluginItem = $destinationPluginItem = $sourcePluginTemplate = $destinationPluginTemplate = false;
					
						//If this move should be on a Layout, check which Plugin is in at an Item level for each Content Item
						if (($_POST['level'] ?? false) == 2) {
							$sourcePluginItem =
								getRow('plugin_item_link',
									array('module_id', 'instance_id'),
									array('content_id' => $id['content_id'], 'content_type' => $id['content_type'], 'content_version' => $id['content_version'], 'slot_name' => ($_POST['slotNameSource'] ?? false)));
							$destinationPluginItem =
								getRow('plugin_item_link',
									array('module_id', 'instance_id'),
									array('content_id' => $id['content_id'], 'content_type' => $id['content_type'], 'content_version' => $id['content_version'], 'slot_name' => ($_POST['slotNameDestination'] ?? false)));
						}
					}
				
					$i = 0;
					foreach (array(
						($_POST['slotNameSource'] ?? false) => '%%%',
						($_POST['slotNameDestination'] ?? false) => ($_POST['slotNameSource'] ?? false),
						'%%%' => ($_POST['slotNameDestination'] ?? false)
					) as $from => $to) {
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
							foreach (array($sourcePluginItem, $sourcePluginTemplate, $destinationPluginItem, $destinationPluginTemplate) as $pluginB) {
								if (!empty($pluginB['module_id']) && empty($pluginB['instance_id']) && $module['module_id'] == $pluginB['module_id']) {
									continue;
								}
							}
						
							//If the above logic is followed, there should never be anything in the way, but just in case there is
							//then this statement is here to remove junk data before it causes a bug
							$id['slot_name'] = $to;
							deleteRow($table, $id);
						
							//Ensure that only settings for this plugin will be moved
							$id['module_id'] = $module['module_id'];
						}
					
						//Move the Plugin's Placement in the linking tables, or the Wireframe Plugin's settings in the plugin instance table, to the new slot
						$id['slot_name'] = $from;
						updateRow($table, array('slot_name' => $to), $id);
					}
				}
			}
		}
	}

} else {
	header('Zenario-Admin-Logged_Out: 1');
	echo '<!--Logged_Out-->', adminPhrase('You have been logged out.');
}

return false;