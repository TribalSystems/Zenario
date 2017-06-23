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


//As part of the migration process, modules can be enabled, or modules can be switched for other modules
//There's an issue where if this happens, and the new module(s) need their own database updates, they won't get them
//So as a workaround, for database updates, we'll loop round the main body of this function twice.

//This can also be used as a work-around for Modules that want to insert data in other Modules tables;
//The tables will be created in the first loop, then the data can be inserted in the second loop in a run-every-update file

require_once CMS_ROOT. 'zenario/includes/welcome.inc.php';

for ($i = 0; $i < ($andDoUpdates? 2 : 1); ++$i) {
	$revisionsNeeded = false;
	$currentRevisionOut = false;
	$modules = array();
	
	//Query the database, get all of the current revision numbers
	$currentRevisions = getAllCurrentRevisionNumbers();
	
	//Get a list of patch directories, and their associated revision numbers
	//Work out every directory that might have patches in them, and get the latest
	//revision number for each patch
	
	//Add in some patch file paths which should always be there
	$directoriesAndTheirRevisionNumbers = array(
	
		//Add in updates for the database updater itself
		'zenario/admin/db_updates/step_1_update_the_updater_itself'	=> LATEST_REVISION_NO,
		
		//Add in updates for the Core CMS
		'zenario/admin/db_updates/step_2_update_the_database_schema'		=> LATEST_REVISION_NO,
		
		//Refresh the permissions tables, and check some others are populates
		//These should be run every update
		'zenario/admin/db_updates/step_3_populate_certain_tables'	=> RUN_EVERY_UPDATE,
		
		//Major updates may need table data converted to the correct format
		'zenario/admin/db_updates/step_4_migrate_the_data'	=> LATEST_REVISION_NO
	);
	
	$desc = false;
	$unorderedModules = getModules($onlyGetRunningPlugins = false, $ignoreUninstalledPlugins = true, $dbUpdateSafemode = true);
	$orderedModules = array();
	do {
		$progressMade = false;
		foreach ($unorderedModules as &$module) {
			if (empty($orderedModules[$module['class_name']])) {
				
				//Load the Module's description.
				//Cache it just in case we need to look it up twice
				if (!isset($module['_description_'])) {
					if ($module['_description_'] = loadModuleDescription($module['class_name'], $desc)) {
						$module['_description_'] = $desc;
					}
				}
				
				if ($module['_description_']) {
					//Don't add a Module into the list until its dependancies have been added
					foreach (readModuleDependencies($module['class_name'], $module['_description_']) as $dependancyClassName) {
						if (empty($orderedModules[$dependancyClassName])) {
							continue 2;
						}
					}
					
					//We don't need the Module's description any more so remove the cached copy
					unset($module['_description_']);
					
					$orderedModules[$module['class_name']] = $module;
					$progressMade = true;
				}
			}
		}
	} while ($progressMade);
	
	
	//Log any dependancy errors!
	foreach ($unorderedModules as &$module) {
		if (empty($orderedModules[$module['class_name']])) {
			//Don't add a Module into the list until its dependancies have been added
			foreach (readModuleDependencies($module['class_name'], $module['_description_']) as $dependancyClassName) {
				if (empty($orderedModules[$dependancyClassName])) {
					$moduleErrors .= 'The module "'. $module['class_name']. '" cannot run unless the "'. $dependancyClassName. "\" module is also running.\n";
				}
			}
		}
	}
	
	
	//Check for module updates. Check which modules we have in the database
	foreach ($orderedModules as $module) {
		//Check if the latest revision number file is there
		$revisionNo = 1;
		if ($path = moduleDir($module['class_name'], 'latest_revision_no.inc.php', true)) {
			require_once CMS_ROOT. $path;
			
			//Get the latest revision number
			if (defined(moduleName($path). '_LATEST_REVISION_NO')) {
				$revisionNo = constant(moduleName($path). '_LATEST_REVISION_NO');
			}
		}
		
		//Read the description.xml file if it is there
			//(This lists just the file, using the is_file() logic in scanDBUpdateDirectoryForPatches() above.)
		if ($path = moduleDescriptionFilePath($module['class_name'])) {
			$directoriesAndTheirRevisionNumbers['zenario/modules/'. $module['class_name']. '/'. basename($path)] = $revisionNo;
		}
		
		//Check if the db_updates directory is there, and include this module's updates
		if ($path = moduleDir($module['class_name'], 'db_updates', true)) {
			$directoriesAndTheirRevisionNumbers['zenario/modules/'. $module['class_name']. '/db_updates'] = $revisionNo;
		}
		
		//Check if the run_every_update updates directory is there, and include it if so
		if ($path = moduleDir($module['class_name'], 'db_updates/run_every_update', true)) {
			$directoriesAndTheirRevisionNumbers['zenario/modules/'. $module['class_name']. '/db_updates/run_every_update'] = RUN_EVERY_UPDATE;
		}
	}
	
	foreach ($directoriesAndTheirRevisionNumbers as $path => $latestRevisionNumber) {
		
		//Look through each file in each directory
		//Given a path to a directory - which should have two subdirectories (local and global), each of which with 
		//patch files inside - this function scans the local or global sub-directories, looking for patch files or folders
		$files = array();
		
		//Account for any Modules in the zenario_custom/modules or zenario_extra_modules directories;
		//these should override modules in the zenario/modules directory
		$actualPath = $path;
		if ($chop = chopPrefixOffString('zenario/modules/', $path)) {
			$altPath = 'zenario_custom/modules/'. $chop;
			if (file_exists(CMS_ROOT. $altPath)) {
				$actualPath = $altPath;
			
			} else {
				$altPath = 'zenario_extra_modules/'. $chop;
				if (file_exists(CMS_ROOT. $altPath)) {
					$actualPath = $altPath;
				}
			}
		}
		
		//Check the directory actually exists. It's not invalid if not; there might be
		//no db_updates of this type
		if (is_dir(CMS_ROOT. $actualPath)) {
			//Look through each file in the directory (in alphabetical order)
			foreach (scandir(CMS_ROOT. $actualPath, $sorting_order = 0) as $file) {
				
				//Ignore directory listings, svn folders, other hidden files
				//and the latest_revision_no.inc.php file if it's still in this directory.
				if (substr($file, 0, 1) == '.' || $file == 'latest_revision_no.inc.php') {
					continue;
				
				//If this is a patchfile for MongoDB, and if MongoDB wasn't loaded earlier
				//during the getAllCurrentRevisionNumbers() function, then we don't have a connected MongoDB,
				//and so skip this update.
				} elseif ($file == 'mongo.inc.php' && !isset(cms_core::$mongoDB)) {
					continue;
				
				} else {
					$files[$file] = $actualPath;
				}
			}
		
		//Alternately, allow for individual files to be entered by their full path.
		//In this case, don't scan the directory that the file is in, just return a list of one file
		//as if the file was the only runnable file in that directory
		} elseif (is_file(CMS_ROOT. $actualPath)) {
			$file = basename(CMS_ROOT. $actualPath);
			$files[$file] = substr($actualPath, 0, -strlen($file)-1);
			$path = substr($path, 0, -strlen($file)-1);
		}
		
	
		foreach ($files as $update => $actualPath) {
			if (is_file(CMS_ROOT. $actualPath. '/'. $update)) {
				
				//Does this update not track revision numbers?
				if ($latestRevisionNumber === RUN_EVERY_UPDATE) {
					//If not, ignore when checking versions, and always run when running
					//the exporter
					if ($andDoUpdates) {
						//The zenario/admin/db_updates/step_3_populate_certain_tables path should be run on the first sweep.
						//(But the "run_every_update" directories for modules should be run on the second sweep)
						if ($path == 'zenario/admin/db_updates/step_3_populate_certain_tables' XOR $i == 1) {
							performDBUpdate($actualPath, $update, $uninstallPluginOnFail);
						}
					}
				
				} else {
		
					//Get the lastest revision number applied from that update file
					$currentRevision = isset($currentRevisions[$path. '/'. $update])?
											$currentRevisions[$path. '/'. $update]
										:
											//If it's not listed, then assume no updates have happened
											//and set it to 0
											0;
					
					//Check whether the revision is up to date.
					if ($currentRevision < $latestRevisionNumber) {
						
						//If we are doing the updates, run performDBUpdate().
						if ($andDoUpdates) {
							performDBUpdate($actualPath, $update, $uninstallPluginOnFail, $currentRevision, $latestRevisionNumber);
							
							//If this was an update to the updater, recalculate all of the revision numbers again just in case some have changed
							if ($path == 'zenario/admin/db_updates/step_1_update_the_updater_itself') {
								$currentRevisions = getAllCurrentRevisionNumbers();
							}
						
						//If we are only reporting then note down any needed updates.
						} else {
							//If we only need to know yes or no, then we can stop after the first one
							if ($quickCheckForUpdates) {
								return true;
							}
							
							$revisionsNeeded = true;
							//If this is a core update, note down the revision number details
							if (chopPrefixOffString('zenario/admin/', $path)) {
								//Try to be vaugely smart about which number we choose if the core numbers are different;
								//for preference we should pick the smallest non-zero number
								if (!$currentRevisionOut || ($currentRevision && $currentRevision < $currentRevisionOut)) {
									$currentRevisionOut = $currentRevision;
								}
							
							//If this is a module update, note down which module this is
							} elseif (chopPrefixOffString('zenario/modules/', $path)) {
								$paths = explode('/', $path, 4);
								
								$modules[$paths[2]] = array($currentRevision, $latestRevisionNumber);
							}
						}
					}
				}
			}
		}
	}
}


if ($andDoUpdates) {
	//Reset the cached table details, in case any of the definitions are out of date
	cms_core::$dbCols = array();
	
	setSetting('last_successful_db_update', time());
}

if (!$revisionsNeeded) {
	return $andDoUpdates? true : false;
} else {
	//print_r($modules);
	return array($currentRevisionOut, $modules);
}