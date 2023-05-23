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

namespace ze;

class dbAdm {




	//Formerly "MYSQL_CHUNK_SIZE"
	const CHUNK_SIZE = 3000;
	
	//Formerly "ZENARIO_BU_READ_CHUNK_SIZE"
	const READ_CHUNK_SIZE = 10000;
	
	//Formerly "RUN_EVERY_UPDATE"
	const RUN_EVERY_UPDATE = 'RUN_EVERY_UPDATE';




	//Get an array containing the patch levels of all installed modules.
	//Formerly "getAllCurrentRevisionNumbers()"
	public static function getAllCurrentRevisionNumbers() {

		$modulesToRevisionNumbers = [];
	
		//Attempt to get all of the rows from the revision numbers table
		$sql = "
			SELECT path, patchfile, revision_no
			FROM ". DB_PREFIX. "local_revision_numbers
			WHERE patchfile != 'data_archive.inc.php'";
	
		//If we fail, rather than exist with an error message and crash the entire admin section,
		//just return that there are no updates
		if (!($result = @\ze\sql::select($sql))) {
			return $modulesToRevisionNumbers;
		}
	
		//Put all of the revision numbers we found into an array, and return it
		while ($row = \ze\sql::fetchAssoc($result)) {
			//Convert anything saying "zenario_extra_modules/" or "zenario_custom/modules/" to "zenario/modules/"
			//Also account for the directory path rearrangements that happened in zenario 6
			if (($chop = \ze\ring::chopPrefix('plugins/', $row['path']))
			 || ($chop = \ze\ring::chopPrefix('zenario/plugins/', $row['path']))
			 || ($chop = \ze\ring::chopPrefix('zenario_extra_modules/', $row['path']))
			 || ($chop = \ze\ring::chopPrefix('zenario_custom/modules/', $row['path']))) {
				$row['path'] = 'zenario/modules/'. $chop;
			}
		
			if (!\ze\ring::chopPrefix('zenario/', $row['path'])) {
				$row['path'] = 'zenario/'. $row['path'];
			}
		
			//Note down this number
			//If there are overlaps, resolve this bug by picking the biggest number of the two
			if (!isset($modulesToRevisionNumbers[$row['path']. '/'. $row['patchfile']])
			 || $modulesToRevisionNumbers[$row['path']. '/'. $row['patchfile']] < $row['revision_no']) {
				$modulesToRevisionNumbers[$row['path']. '/'. $row['patchfile']] = $row['revision_no'];
			}
		}
		
		//If this site has a data archive, and it's been populated, attempt to run checks on that as well.
		if (\ze\db::connectDataArchive()
		 && \ze::$dbD->checkTableDef(DB_PREFIX_DA. 'data_archive_revision_numbers', true)) {
	
			//Attempt to get all of the rows from the revision numbers table
			$sql = "
				SELECT path, patchfile, revision_no
				FROM ". DB_PREFIX_DA. "data_archive_revision_numbers
				WHERE patchfile = 'data_archive.inc.php'";
			
			$result = \ze\sql\da::select($sql);
			
			while ($row = \ze\sql\da::fetchAssoc($result)) {
			
				//Copy-paste of the above
				if (($chop = \ze\ring::chopPrefix('plugins/', $row['path']))
				 || ($chop = \ze\ring::chopPrefix('zenario/plugins/', $row['path']))
				 || ($chop = \ze\ring::chopPrefix('zenario_extra_modules/', $row['path']))
				 || ($chop = \ze\ring::chopPrefix('zenario_custom/modules/', $row['path']))) {
					$row['path'] = 'zenario/modules/'. $chop;
				}
		
				if (!\ze\ring::chopPrefix('zenario/', $row['path'])) {
					$row['path'] = 'zenario/'. $row['path'];
				}
		
				if (!isset($modulesToRevisionNumbers[$row['path']. '/'. $row['patchfile']])
				 || $modulesToRevisionNumbers[$row['path']. '/'. $row['patchfile']] < $row['revision_no']) {
					$modulesToRevisionNumbers[$row['path']. '/'. $row['patchfile']] = $row['revision_no'];
				}
			}
		}
	
		return $modulesToRevisionNumbers;
	}


	//Check the current revisions as recorded in the revision_numbers tables
	//to see if database updates are needed from the updates directory
	//Formerly "checkIfDBUpdatesAreNeeded()"
	public static function checkIfUpdatesAreNeeded(&$moduleErrors, $andDoUpdates = false, $uninstallPluginOnFail = false, $quickCheckForUpdates = true) {
		
		if ($andDoUpdates) {
			\ze\dbAdm::getTableEngine();
		}
		
		
		//As part of the migration process, modules can be enabled, or modules can be switched for other modules
		//There's an issue where if this happens, and the new module(s) need their own database updates, they won't get them
		//So as a workaround, for database updates, we'll loop round the main body of this function twice.

		//This can also be used as a work-around for Modules that want to insert data in other Modules tables;
		//The tables will be created in the first loop, then the data can be inserted in the second loop in a run-every-update file
		for ($sweep = 1; $sweep <= ($andDoUpdates? 2 : 1); ++$sweep) {
			$revisionsNeeded = false;
			$currentRevisionOut = false;
			$modules = [];
	
			//Query the database, get all of the current revision numbers
			$currentRevisions = \ze\dbAdm::getAllCurrentRevisionNumbers();
	
			//Get a list of patch directories, and their associated revision numbers
			//Work out every directory that might have patches in them, and get the latest
			//revision number for each patch
	
			//Add in some patch file paths which should always be there
			$directoriesAndTheirRevisionNumbers = [
	
				//Add in updates for the database updater itself
				'zenario/admin/db_updates/step_1_update_the_updater_itself'	=> LATEST_REVISION_NO,
		
				//Add in updates for the Core CMS
				'zenario/admin/db_updates/step_2_update_the_database_schema'		=> LATEST_REVISION_NO,
		
				//Refresh the permissions tables, and check some others are populates
				//These should be run every update
				'zenario/admin/db_updates/step_3_populate_certain_tables'	=> \ze\dbAdm::RUN_EVERY_UPDATE,
		
				//Major updates may need table data converted to the correct format
				'zenario/admin/db_updates/step_4_migrate_the_data'	=> LATEST_REVISION_NO
			];
	
			$desc = false;
			$unorderedModules = \ze\module::modules($onlyGetRunningPlugins = false, $ignoreUninstalledPlugins = true, $dbUpdateSafemode = true);
			$orderedModules = [];
			do {
				$progressMade = false;
				foreach ($unorderedModules as &$module) {
					if (empty($orderedModules[$module['class_name']])) {
				
						//Load the Module's description.
						//Cache it just in case we need to look it up twice
						if (!isset($module['_description_'])) {
							if ($module['_description_'] = \ze\moduleAdm::loadDescription($module['class_name'], $desc)) {
								$module['_description_'] = $desc;
							}
						}
				
						if ($module['_description_']) {
							//Don't add a Module into the list until its dependancies have been added
							foreach (\ze\moduleAdm::readDependencies($module['class_name'], $module['_description_']) as $dependancyClassName) {
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
					foreach (\ze\moduleAdm::readDependencies($module['class_name'], $module['_description_']) as $dependancyClassName) {
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
				if ($path = \ze::moduleDir($module['class_name'], 'latest_revision_no.inc.php', true)) {
					require_once CMS_ROOT. $path;
			
					//Get the latest revision number
					if (defined(\ze::moduleName($path). '_LATEST_REVISION_NO')) {
						$revisionNo = constant(\ze::moduleName($path). '_LATEST_REVISION_NO');
					}
				}
		
				//Read the description.xml file if it is there
					//(This lists just the file, using the is_file() logic in scanDBUpdateDirectoryForPatches() above.)
				if ($path = \ze\moduleAdm::descriptionFilePath($module['class_name'])) {
					$directoriesAndTheirRevisionNumbers['zenario/modules/'. $module['class_name']. '/'. basename($path)] = $revisionNo;
				}
		
				//Check if the db_updates directory is there, and include this module's updates
				if ($path = \ze::moduleDir($module['class_name'], 'db_updates', true)) {
					$directoriesAndTheirRevisionNumbers['zenario/modules/'. $module['class_name']. '/db_updates'] = $revisionNo;
				}
		
				//Check if the run_every_update updates directory is there, and include it if so
				if ($path = \ze::moduleDir($module['class_name'], 'db_updates/run_every_update', true)) {
					$directoriesAndTheirRevisionNumbers['zenario/modules/'. $module['class_name']. '/db_updates/run_every_update'] = \ze\dbAdm::RUN_EVERY_UPDATE;
				}
			}
	
			foreach ($directoriesAndTheirRevisionNumbers as $path => $latestRevisionNumber) {
		
				//Look through each file in each directory
				//Given a path to a directory - which should have two subdirectories (local and global), each of which with 
				//patch files inside - this function scans the local or global sub-directories, looking for patch files or folders
				$files = [];
		
				//Account for any Modules in the zenario_custom/modules or zenario_extra_modules directories;
				//these should override modules in the zenario/modules directory
				$actualPath = $path;
				if ($chop = \ze\ring::chopPrefix('zenario/modules/', $path)) {
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
				
						//If this is a patchfile for the data archive, and the data archive was not set up,
						//then skip this update.
						} elseif ($file == 'data_archive.inc.php' && !\ze\db::hasDataArchive()) {
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
						if ($latestRevisionNumber === \ze\dbAdm::RUN_EVERY_UPDATE) {
							//If not, ignore when checking versions, and always run when running
							//the exporter
							if ($andDoUpdates) {
								//The zenario/admin/db_updates/step_3_populate_certain_tables path should be run on the first sweep.
								//(But the "run_every_update" directories for modules should be run on the second sweep)
								if ($path == 'zenario/admin/db_updates/step_3_populate_certain_tables' XOR $sweep == 2) {
									\ze\dbAdm::performUpdate($actualPath, $update, $uninstallPluginOnFail);
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
						
								//If we are doing the updates, run \ze\dbAdm::performUpdate().
								if ($andDoUpdates) {
									\ze\dbAdm::performUpdate($actualPath, $update, $uninstallPluginOnFail, $currentRevision, $latestRevisionNumber);
							
									//If this was an update to the updater, recalculate all of the revision numbers again just in case some have changed
									if ($path == 'zenario/admin/db_updates/step_1_update_the_updater_itself') {
										$currentRevisions = \ze\dbAdm::getAllCurrentRevisionNumbers();
									}
						
								//If we are only reporting then note down any needed updates.
								} else {
									//If we only need to know yes or no, then we can stop after the first one
									if ($quickCheckForUpdates) {
										return true;
									}
							
									$revisionsNeeded = true;
									//If this is a core update, note down the revision number details
									if (\ze\ring::chopPrefix('zenario/admin/', $path)) {
										//Try to be vaugely smart about which number we choose if the core numbers are different;
										//for preference we should pick the smallest non-zero number
										if (!$currentRevisionOut || ($currentRevision && $currentRevision < $currentRevisionOut)) {
											$currentRevisionOut = $currentRevision;
										}
							
									//If this is a module update, note down which module this is
									} elseif (\ze\ring::chopPrefix('zenario/modules/', $path)) {
										$paths = explode('/', $path, 4);
								
										$modules[$paths[2]] = [$currentRevision, $latestRevisionNumber];
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
			\ze\dbAdm::resetTableDefs();
			
			\ze\site::setSetting('css_js_html_files_last_changed', '');
			\ze\site::setSetting('css_js_version', '');
			\ze\site::setSetting('yaml_files_last_changed', '');
			\ze\site::setSetting('yaml_version', '');
			\ze\site::setSetting('zenario_version', \ze\site::versionNumber());
		}

		if (!$revisionsNeeded) {
			return $andDoUpdates? true : false;
		} else {
			//print_r($modules);
			return [$currentRevisionOut, $modules];
		}
	}


	//Update the revision number for a module
	//Formerly "setModuleRevisionNumber()"
	public static function setModuleRevisionNumber($revisionNumber, $path, $updateFile) {
	
		//Ignore updates in some cases
		if ($revisionNumber === \ze\dbAdm::RUN_EVERY_UPDATE) {
			return;
		}
	
		//Account for the directory path rearrangements that happened in zenario 6
		//Convert back to the old format when saving
		if ($chop = \ze\ring::chopPrefix('zenario/', $path)) {
			$path = $chop;
	
		//Convert back from the my_zenario_module format as well
		} else
		if (($chop = \ze\ring::chopPrefix('zenario_extra_modules/', $path))
		 || ($chop = \ze\ring::chopPrefix('zenario_custom/modules/', $path))) {
			$path = 'modules/'. $chop;
		}
	
		if ($updateFile == 'data_archive.inc.php') {
			$sql = "
				REPLACE INTO  ". DB_PREFIX_DA. "data_archive_revision_numbers SET
				  path = '". \ze\escape::sql($path). "',
				  patchfile = '". \ze\escape::sql($updateFile). "',
				  revision_no = ". (int) $revisionNumber;
	
			\ze\sql\da::update($sql);
		
		} else {
			$sql = "
				REPLACE INTO  ". DB_PREFIX. "local_revision_numbers SET
				  path = '". \ze\escape::sql($path). "',
				  patchfile = '". \ze\escape::sql($updateFile). "',
				  revision_no = ". (int) $revisionNumber;
	
			\ze\sql::update($sql);
		}
	}
	
	private static $dbupPath = false;
	private static $dbupUpdateFile = false;
	private static $dbupCurrentRevision = false;
	private static $dbupUninstallPluginOnFail = false;


	//Run a patch file, making the revisions needed
	//If $currentRevision and $latestRevisionNumber are set, it will use revision control for updates;
	//i.e. updates that have already been applied can be skipped
	//Formerly "performDBUpdate()"
	public static function performUpdate($path, $updateFile, $uninstallPluginOnFail, $currentRevision = \ze\dbAdm::RUN_EVERY_UPDATE, $latestRevisionNumber = \ze\dbAdm::RUN_EVERY_UPDATE) {
	
	
		//Check the extension, to see if this is a description for a module
		if ($updateFile == 'description.yaml'
		 || $updateFile == 'description.yml'
		 || $updateFile == 'description.xml') {
		
			//The path will be of the form 
			//	zenario/modules/'. $module['class_name']. '/...
			//Get the module's directory name from the path!
			if (\ze\ring::chopPrefix('zenario/modules/', $path)
			 || \ze\ring::chopPrefix('zenario_custom/modules/', $path)) {
				$paths = explode('/', $path, 4);
				$moduleName = $paths[2];
		
			} elseif (\ze\ring::chopPrefix('zenario_extra_modules/', $path)) {
				$paths = explode('/', $path, 3);
				$moduleName = $paths[1];
		
			} else {
				echo 'Could not work out which Module ', $path, $updateFile. ' is for.';
				exit;
			}
		
			//Attempt to apply the XML file
			if (!\ze\moduleAdm::setupFromDescription($moduleName)) {
				exit;
			}
		
			//If this is an installed module, update any content types settings too
			if (!$uninstallPluginOnFail) {
				\ze\moduleAdm::setupContentTypesFromDescription($moduleName);
			}
	
		//Otherwise assume the file will be a php file with a series of revisions
		} else {
			//Clear any cached information on the existing database tables, as this can cause database errors if it's used when out-of-date
			\ze\dbAdm::resetTableDefs();
		
			//Set the inputs into global variables, so we can remember them for this revision
			//without needing to add extra parameters to every function (which would make the update files look messy!)
			\ze::$dbUpdating = true;
			self::$dbupPath = $path;
			self::$dbupUpdateFile = $updateFile;
			self::$dbupCurrentRevision = $currentRevision;
			self::$dbupUninstallPluginOnFail = $uninstallPluginOnFail;
		
			//Run the update file
			require_once CMS_ROOT. $path. '/'. $updateFile;
		
			$path = self::$dbupPath;
			$updateFile = self::$dbupUpdateFile;
			$currentRevision = self::$dbupCurrentRevision;
			$uninstallPluginOnFail = self::$dbupUninstallPluginOnFail;
			
			\ze::$dbUpdating = false;
			self::$dbupPath = false;
			self::$dbupUpdateFile = false;
			self::$dbupCurrentRevision = false;
			self::$dbupUninstallPluginOnFail = false;
		
			//Clear any cached information on the existing database tables, as this can cause database errors if it's used when out-of-date
			\ze\dbAdm::resetTableDefs();
		}
	
		//Update the current revision in the database to the latest, so this will not be triggered again.
		\ze\dbAdm::setModuleRevisionNumber($latestRevisionNumber, $path, $updateFile);
	}

	//Formerly "needRevision()"
	public static function needRevision($revisionNumber) {

		//Check the latest revision number, and if we have applied this revision yet
		//If we have already applied the revision, we can stop without processing it any further
	
		//Note that there is functionality to override this and always apply a revision!
		if (self::$dbupCurrentRevision !== \ze\dbAdm::RUN_EVERY_UPDATE && $revisionNumber <= self::$dbupCurrentRevision) {
			return false;
		} else {
			return true;
		}
	}

	//Formerly "resetDatabaseStructureCache()"
	public static function resetTableDefs() {
		if (isset(\ze::$dbL)) {
			\ze::$dbL->cols = [];
			\ze::$dbL->pks = [];
		}
		if (isset(\ze::$dbD)) {
			\ze::$dbD->cols = [];
			\ze::$dbD->pks = [];
		}
	}

	//This function is used for database revisions. It's called from the patch files.
	//WARNING: It expects to already be connected to the correct database, and to have
	//the self::$dbupPath, self::$dbupUpdateFile and self::$dbupCurrentRevision global variables set
	//Formerly "revision()"
	public static function revision($revisionNumber) {

		//The first arguement to this function should be the revision number.
		//All remaining arguements will be the SQL statements for that revision.
	
		//Check the latest revision number, and if we have applied this revision yet
		//If we have already applied the revision, we can stop without processing it any further
	
		//Note that there is functionality to override this and always apply a revision!
		if (!\ze\dbAdm::needRevision($revisionNumber)) {
			return;
		}
		//If the above wasn't true, then we'll need to apply the update
		
		if (self::$dbupUpdateFile == 'data_archive.inc.php') {
			$db = \ze::$dbD;
		} else {
			$db = \ze::$dbL;
		}
	
	
		//Loop through all of the arguments given after the first
		$i = 1;
		$count = func_num_args();
		while ($i < $count && ($sql = func_get_arg($i++))) {
		
			//Run the SQL, using str_replace to subsitute in the values of DB_PREFIX
			$sql = \ze\dbAdm::addConstantsToString($sql, false);
			$result = @$db->con->query($sql);
		
			//Handle errors
			if ($result === false) {
				$errNo = $db->con->errno;
			
				//Ignore "column already exists" errors
				if ($errNo == 1060 && !preg_match('/\s*CREATE\s*TABLE\s*/i', $sql)) {
					continue;
			
				//Ignore errors if we try to drop columns or keys that do not exist
				} elseif ($errNo == 1091) {
					continue;
				}
			
			
				//Otherwise we can't recover from this error
			
				//Report the error
				echo 'Database query error: '. $errNo. ', '. $db->con->error. ', '. $sql;
			
				//If this was the installation of a Module, then remove everything that the Module has installed
				if (self::$dbupUninstallPluginOnFail) {
					\ze\moduleAdm::uninstall(self::$dbupUninstallPluginOnFail, true);
				}
			
				//Stop
				exit;
			}
		}
	
		//Update the revision number for this module, or set it if it was not there.
		//I'm doing this with each revision, just in case we get an error in one
		//- the previous revisions won't be applied.
		if ($revisionNumber && self::$dbupCurrentRevision !== \ze\dbAdm::RUN_EVERY_UPDATE) {
			\ze\dbAdm::setModuleRevisionNumber($revisionNumber, self::$dbupPath, self::$dbupUpdateFile);
	
		} else {
			\ze\db::updateDataRevisionNumber();
		}
	}

	//Take a string, and add any defined constants in using the [[CONSTANT_NAME]] format
	//Formerly "addConstantsToString()"
	public static function addConstantsToString($sql, $replaceUnmatchedConstants = true) {
		
		$content = explode('[[', $sql);
		$sql = '';
		$first = true;
		
		foreach ($content as &$str) {
			if ($first) {
				$first = false;
			
			} elseif (false !== ($sbe = strpos($str, ']]'))) {
				$mf = substr($str, 0, $sbe);
				$str = substr($str, $sbe + 2);
				
				if (defined($mf)) {
					$sql .= constant($mf);
				
				} elseif ($replaceUnmatchedConstants) {
					if ($mf !== 'SQL_IN') {
						$sql .= 'NULL';
					}
				
				} else {
					$sql .= '[['. $mf. ']]';
				}
			}
			
			$sql .= $str;
		}
		
		return $sql;
	}


	//Organizer needs reloading if a module that adds to Organizer, or has a content type, is installed or uninstalled
	//Formerly "needToReloadOrganizerWhenModuleIsInstalled()"
	public static function needToReloadOrganizerWhenModuleIsInstalled($moduleName) {
		$tags = [];
		return \ze::moduleDir($moduleName, 'tuix/organizer', true)
		 || (\ze\moduleAdm::loadDescription($moduleName, $tags) && (!empty($tags['content_types'])));
	}




	//Functions for site backups and restores

	//Formerly "initialiseBackupFunctions()"
	public static function initialiseBackupFunctions($includeWarnings = false) {
	
		$errors = [];
		$warnings = [];
	
		//Check the docstore directory is correctly defined, exists, and has the correct permissions
		if (!\ze::setting('docstore_dir')) {
			$errors[] = \ze\admin::phrase('_NOT_DEFINED_DOCSTORE_DIR'). '<br />'. \ze\admin::phrase('_FIX_DOCSTORE_DIR_TO_BACKUP');
	
		} else {
			//$docpath = \ze::setting('docstore_dir');
		
			//if (!file_exists($docpath)) {
				//$mrg = ['dirpath' => $docpath];
				//$errors[] = \ze\admin::phrase('_FIX_DOCSTORE_DIR_TO_BACKUP'). '<br />'. \ze\admin::phrase('_DIRECTORY_DOES_NOT_EXIST', $mrg);
		
			//} elseif (!is_readable($docpath) || !is_writeable($docpath)) {
				//$mrg = ['dirpath' => $docpath];
				//$errors[] = \ze\admin::phrase('_FIX_DOCSTORE_DIR_TO_BACKUP'). '<br />'. \ze\admin::phrase('_DIRECTORY_NOT_READ_AND_WRITEABLE', $mrg);
			//}
		}
	
		//Check the backup directory is correctly defined, exists, and has the correct permissions
		if (!\ze::setting('backup_dir')) {
			$errors[] = \ze\admin::phrase('_NOT_DEFINED_BACKUP_DIR');
	
		} else {
			$dirpath = \ze::setting('backup_dir');
		
			if (!file_exists($dirpath)) {
				$mrg = ['dirpath' => $dirpath];
				$errors[] = \ze\admin::phrase('_DIRECTORY_DOES_NOT_EXIST', $mrg);
		
			} elseif (!is_readable($dirpath) || !is_writeable($dirpath)) {
				$mrg = ['dirpath' => $dirpath];
				$errors[] = \ze\admin::phrase('_DIRECTORY_NOT_READ_AND_WRITEABLE', $mrg);
			}
		}
	
		//Check if there are any admins with management rights in the database
		$sql = "
			SELECT 1
			FROM ". DB_PREFIX. "admins AS a
			INNER JOIN ". DB_PREFIX. "action_admin_link AS aal
			   ON aal.admin_id = a.id
			WHERE a.status = 'active'
			  AND aal.action_name IN ('_ALL', '_PRIV_EDIT_ADMIN')
			LIMIT 1";
	
		$result = \ze\sql::select($sql);
		if (!\ze\sql::fetchRow($result)) {
			$warnings[] = \ze\admin::phrase('_NO_ADMINS_TO_BACKUP');
		}
	
	
		if ($includeWarnings) {
			$errors = array_merge($errors, $warnings);
		}
	
		return count($errors)? $errors : false;

	}


	//Formerly "generateFilenameForBackups()"
	public static function generateFilenameForBackups($gzip = true, $encrypt = false) {
		//Get the current date and time, and create a filename with that timestamp
		return preg_replace('/[^\w\\.]+/', '-',
			\ze\link::host(). '-'. SUBDIRECTORY. '-backup-'. \ze\sql::fetchValue("SELECT DATE_FORMAT(NOW(), '%Y-%m-%d-%H.%i')"). '-'.
			ZENARIO_VERSION. '-r'. LATEST_REVISION_NO.
			'.sql'. ($gzip? '.gz' : ''). ($encrypt? '.encrypted' : ''));
	}


	//Look up the name of every table in the database which matches a certain pattern,
	//and return them in an array.
	//We're interested in returning tables with the patten:
		//PREFIX - i_m_p_ - table_name
	//Formerly "lookupImportedTables()"
	public static function lookupImportedTables($refiner) {
	
		$refiner .= 'i_m_p_';
	
		$prefixLength = strlen($refiner);

		$importedTables = [];
		$sql = "SHOW TABLES";
		$result = \ze\sql::select($sql);
	
		while($row = \ze\sql::fetchRow($result)) {
			if ($refiner === false || substr($row[0], 0, $prefixLength) === $refiner) {
				$importedTables[] = $row[0];
			}
		}
	
		return $importedTables;
	}

	//Look up the name of every CMS table in the database, and return them in an array.
	//Formerly "lookupExistingCMSTables()"
	public static function lookupExistingCMSTables($dbUpdateSafeMode = false) {
	
		//Get a list of Modules that are installed on the site
		//Note - don't do this if the modules table might not be present
		$modules = [];
		if (!$dbUpdateSafeMode) {
			$modules = \ze\ray::valuesToKeys(\ze\row::getAssocs(
				'modules',
				'id',
				['status' => ['!' => 'module_not_initialized']])
			);
		}
	

		$prefixLength = strlen(DB_PREFIX);
	
		$existingTables = [];
		$sql = "SHOW TABLES";
		$result = \ze\sql::select($sql);
	
		while($row = \ze\sql::fetchRow($result)) {
			//Check whether this table matches the global or the local prefix
			$matchesLocal = substr($row[0], 0, $prefixLength) === DB_PREFIX;
		
			//If we get no matches, we're not interested
			if (!$matchesLocal) {
				continue;
			}
		
			//Strip the prefix off of the tablename
			$tableName = substr($row[0], $prefixLength);
			$prefix = 'DB_PREFIX';
		
		
			$moduleId = false;
			if (substr($tableName, 0, 3) == 'mod') {
				$moduleId = (int) preg_replace('/mod(\d*)_.*/', '\1', $tableName);
			}
		
			//A few tables should be dropped by the "reset site" feature; mark these
			if ($moduleId) {
				//Any module tables should be just dropped
				$reset = 'drop';
		
			} else {
				//Any other tables should be ignored
				$reset = 'no';
			}
		
		
			//Add the table to our list
			$existingTables[] = [
				'name' => $tableName,
				'actual_name' => $row[0],
				'module_id' => $moduleId,
				'prefix' => $prefix,
				'reset' => $reset
			];
		}
	
		return $existingTables;
	}




	//Suggest what the path of the backup/docstore/dropbox
	//Formerly "suggestDir()"
	public static function suggestDir($dir) {
		$root = CMS_ROOT;
	
		if (\ze\server::isWindows() && strpos(CMS_ROOT, '\\') !== false && strpos(CMS_ROOT, '/') === false) {
			$s = '\\';
		} else {
			$s = '/';
		}
	
		if (defined('SUBDIRECTORY') && substr($root, -strlen(SUBDIRECTORY)) == SUBDIRECTORY) {
			$root = substr($root, 0, -strlen(SUBDIRECTORY));
		}
	
		$docroot_arr = explode($s, $root);
		array_pop($docroot_arr);
		$suggestedDir = implode($s, $docroot_arr) . $s;
	
		$suggestedDir .= $dir;
	
		return $suggestedDir;
	}


	//Formerly "apacheMaxFilesize()"
	public static function apacheMaxFilesize() {
		$postMaxSize = (int) preg_replace('/\D/', '', ini_get('post_max_size'));
		$postMaxSizeMag = strtoupper(preg_replace('/\d/', '', ini_get('post_max_size')));
	
		$uploadMaxFilesize = (int) preg_replace('/\D/', '', ini_get('upload_max_filesize'));
		$uploadMaxFilesizeMag = strtoupper(preg_replace('/\d/', '', ini_get('upload_max_filesize')));
	
		switch ($postMaxSizeMag) {
			case 'G':
				$postMaxSize *= 1024;
			case 'M':
				$postMaxSize *= 1024;
			case 'K':
				$postMaxSize *= 1024;
		}
	
		switch ($uploadMaxFilesizeMag) {
			case 'G':
				$uploadMaxFilesize *= 1024;
			case 'M':
				$uploadMaxFilesize *= 1024;
			case 'K':
				$uploadMaxFilesize *= 1024;
		}
	
		if ($postMaxSize < $uploadMaxFilesize) {
			return $postMaxSize;
		} else {
			return $uploadMaxFilesize;
		}
	}


	//Scan and Write the Docstore Directory
		//Note: back when the backups included the docstore directory, this used to be used to add the files
		//into the backup
	//function writeDocstoreDirectory(&$gzFile, $dir = '.[[SITENAME]].') {
	//	
	//	//Scan the current directory
	//	foreach (scandir(docstoreDirectoryPath($dir)) as $name) {
	//		$part = $dir. '/'. $name;
	//		
	//		if ($name != '.' && $name != '..') {
	//			//If we find a directory, write it down then scan it too.
	//			if (is_dir(docstoreDirectoryPath($part))) {
	//				gzwrite($gzFile, "DIR;\n". $part. ";\n");
	//				writeDocstoreDirectory($gzFile, $part);
	//			
	//			//If we find a file, write down it's name and path, then write down
	//			//its contents (in hexadecimal)
	//			} elseif (is_file(docstoreDirectoryPath($part))) {
	//				gzwrite($gzFile, "FILE;\n". $part. ";\n");
	//				
	//				$f = fopen(docstoreDirectoryPath($part), 'rb');
	//				while ($chunk = fread($f, 1000)) {
	//					gzwrite($gzFile, bin2hex($chunk));
	//				}
	//				fclose($f);
	//				
	//				gzwrite($gzFile, ";\n");
	//			}
	//		}
	//	}
	//
	//}


	//Create a backup of the database
	//Formerly "createDatabaseBackupScript()"
	public static function createBackupScript($backupPath, $gzip = true, $encrypt = false) {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}

	//Formerly "callMySQL()"
	public static function callMySQL($mysqldump, $args = '', $input = '') {

		if ($mysqldump) {
			$programPath = \ze\server::programPathForExec(\ze::setting('mysqldump_path'), 'mysqldump');
		} else {
			$programPath = \ze\server::programPathForExec(\ze::setting('mysql_path'), 'mysql');
		}
	
		if ($programPath) {
			$return_var = $output = false;
			$lastOutput = exec(
				$input.
				escapeshellarg($programPath).
				$args,
			$output, $return_var);
		
			if ($return_var == 0) {
				return $lastOutput;
			}
		}
	
		return false;
	}

	//Formerly "testMySQL()"
	public static function testMySQL($mysqldump) {
		$result = \ze\dbAdm::callMySQL($mysqldump, ' --version');
	
		return $result && (
			strpos($result, ($mysqldump? 'mysqldump' : 'mysql'). '  Ver') !== false
		 || strpos($result, 'Distrib ') !== false
		);
	}




	//Check if restoring a database backup is allowed
	public static function restoreEnabled() {
		return defined('RESTORE_POLICY')
			&& (RESTORE_POLICY === 'always' || RESTORE_POLICY == date('Y-m-d'));
	}
	
	public static function restoreEnabledMsg() {
		$date = date('Y-m-d');
		return \ze\admin::phrase("Before you can restore site backups using Organizer,
you must first edit your <code>zenario_siteconfig.php file</code> and add either the following definition:
<br/>
<br/><code>&nbsp; define('RESTORE_POLICY', 'always');</code>
<br/>
<br/>or the following definition:
<br/>
<br/><code>&nbsp; define('RESTORE_POLICY', '$date');</code>
<br/>
<br/>(Where <code>'$date'</code> must be the current date.)");
	}



	//Given a backup, restore the database from it
	//Formerly "restoreDatabaseFromBackup()"
	public static function restoreFromBackup($backupPath, &$failures, $restoringOverExistingSite) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}


	//Reset a site, putting all of its tables back to an initial state
	//Formerly "resetSite()"
	public static function resetSite() {
		\ze\dbAdm::getTableEngine();
		
		\ze\dbAdm::rememberLocationalSiteSettings();
		
		//Make sure to load the values of site_disabled_title and site_disabled_message,
		//which aren't usually loaded into memory, so we can restore them later.
		$site_disabled_title = \ze::setting('site_disabled_title');
		$site_disabled_message = \ze::setting('site_disabled_message');
		//Also save the values of email_address_admin, email_address_from and email_name_from
		//which \ze\dbAdm::restoreLocationalSiteSettings() doesn't cover.
		$email_address_admin = \ze::setting('email_address_admin');
		$email_address_from = \ze::setting('email_address_from');
		$email_name_from = \ze::setting('email_name_from');
		//Don't change a site's default timezone when doing a site reset
		$zenario_timezones__default_timezone = \ze::setting('zenario_timezones__default_timezone');
	
		//Delete all module tables
		foreach (\ze\dbAdm::lookupExistingCMSTables() as $table) {
			if ($table['reset'] == 'drop') {
				$sql = "DROP TABLE `". $table['actual_name']. "`";
				\ze\sql::update($sql);
			}
		}
	
		//look up the revision numbers of the admin tables from the local_revision_numbers table
		$sql = "
			SELECT `path`, revision_no
			FROM ". DB_PREFIX. "local_revision_numbers
			WHERE patchfile = 'admin_tables.inc.php'";
		$revisions = \ze\sql::fetchAssocs($sql);
	
		//Rerun some of the scripts from the installer to give us a blank site
		$error = false;
		(\ze\welcome::runSQL(false, 'local-DROP.sql', $error)) &&
		(\ze\welcome::runSQL(false, 'local-CREATE.sql', $error)) &&
		(\ze\welcome::runSQL(false, 'local-INSERT.sql', $error));
		
		//Insert the starter images from the starter_images folder, read from the yaml file
		\ze\welcome::addStarterImagesToDB();

	
		//Reset the cached table details, in case any of the definitions are out of date
		\ze\dbAdm::resetTableDefs();
	
		//Add the admin-related revision numbers back in
		foreach ($revisions as &$revision) {
			$sql = "
				REPLACE INTO ". DB_PREFIX. "local_revision_numbers SET
					patchfile = 'admin_tables.inc.php',
					`path` = '". \ze\escape::sql($revision['path']). "',
					revision_no = ". (int) $revision['revision_no'];
			@\ze\sql::cacheFriendlyUpdate($sql);
		}
	
		//Populate the Modules table with all of the Modules in the system,
		//and install and run any Modules that should running by default.
		\ze\moduleAdm::addNew($skipIfFilesystemHasNotChanged = false, $runModulesOnInstall = true, $dbUpdateSafeMode = true);
		
		//Try to restore the settings we saved earlier in the site settings table.
		//Note: this might fail to restore all of the data if some columns in the site settings table
		//haven't been created yet.
		\ze\dbAdm::restoreLocationalSiteSettings();
		
		\ze\site::setSetting('site_disabled_title', $site_disabled_title);
		\ze\site::setSetting('site_disabled_message', $site_disabled_message);
		\ze\site::setSetting('email_address_admin', $email_address_admin);
		\ze\site::setSetting('email_address_from', $email_address_from);
		\ze\site::setSetting('email_name_from', $email_name_from);
		\ze\site::setSetting('zenario_timezones__default_timezone', $zenario_timezones__default_timezone);
	
		if ($error) {
			echo $error;
			exit;
	
		} else {
			//Give the newly reset site a new key, and log the admin in
			\ze\site::setSetting('site_id', \ze\dbAdm::generateRandomSiteIdentifierKey());
			\ze\admin::setSession($_SESSION['admin_userid'] ?? false, ($_SESSION['admin_global_id'] ?? false));
		
		
			//Apply database updates
			$moduleErrors = '';
			\ze\dbAdm::checkIfUpdatesAreNeeded($moduleErrors, $andDoUpdates = true);
			
			//Try to restore the settings again. This should work fully this time.
			\ze\dbAdm::restoreLocationalSiteSettings();
			
			//T12215, When resetting a site, delete all admins other than current user
			\ze\adminAdm::reallyDelete($_SESSION['admin_userid'], $onlyDeleteAdminsThatHaveNeverLoggedIn = false, $deleteAllButThisAdmin = true);
			
		
			\ze\welcome::postInstallTasks();
		
			return true;
		}
	}
	
	
	//These two functions are used before restoring a backup or site reset.
	//They preserve any site setting with the "protect_from_database_restore" flag set.
	private static $lss;
	public static function rememberLocationalSiteSettings() {
		$protectColExists = \ze::$dbL->checkTableDef(DB_PREFIX. 'site_settings', 'protect_from_database_restore');
		
		if ($protectColExists) {
			self::$lss = \ze\row::getAssocs('site_settings',
				['value', 'default_value', 'encrypted', 'secret'],
				['protect_from_database_restore' => 1]
			);
		} else {
			self::$lss = [];
		}
	}
	
	
	//Formerly "restoreLocationalSiteSettings()"
	public static function restoreLocationalSiteSettings() {
		
		foreach (self::$lss as $name => $row) {
			if (\ze\row::exists('site_settings', $name)) {
				unset($row['default_value']);
				unset($row['secret']);
				\ze\row::update('site_settings', $row, $name, false, $ignoreMissingColumns = true);
			} else {
				\ze\row::set('site_settings', $row, $name, false, $ignoreMissingColumns = true);
			}
		}
		
		//Delete the settings to do with checking the version/checksums of the code-base
		$sql = "
			DELETE FROM ". DB_PREFIX. "site_settings
			WHERE name IN (
				'css_js_version', 'css_js_html_files_last_changed',
				'yaml_version', 'yaml_files_last_changed',
				'zenario_version', 'module_description_hash'
			)";
		\ze\sql::cacheFriendlyUpdate($sql);
	}

	//This function generates a random key which can be used to identify a site.
	//Not intended to be secure; it's more to prevent Admins accidently causing bad data
	//(e.g. by restoring a backup on which they don't have an account and continuing to use the site,
	//or installing two sites in different directories on the same domain, and switching between the two)
	//Formerly "generateRandomSiteIdentifierKey()"
	public static function generateRandomSiteIdentifierKey() {
		return substr(base64_encode(microtime()), 3);
	}
	
	//If running on MySQL 5.6 or later, use InnoDB. Otherwise use MyISAM.
	public static function getTableEngine() {
		if (!defined('ZENARIO_TABLE_ENGINE')) {
			if (($mysqlVersion = \ze\sql::fetchValue('SELECT version()'))
			 && (version_compare($mysqlVersion, '5.7.0', '>='))) {
				define('ZENARIO_TABLE_ENGINE', 'InnoDB');
			} else {
				define('ZENARIO_TABLE_ENGINE', 'MyISAM');
			}
			
			//We don't support versions of MySQL 5.5 or older any more,
			//so our default character-set will always be utf8mb4.
			define('ZENARIO_TABLE_CHARSET', 'utf8mb4');
			define('ZENARIO_TABLE_COLLATION', 'utf8mb4_unicode_ci');
		}
		
		return ZENARIO_TABLE_ENGINE;
	}


	//Formerly "configFileSize()"
	public static function configFileSize($size) {
		//Define labels to use
		$labels = ['', 'K', 'M', 'G', 'T'];
		$precision = 0;
	
		//Work out which of the labels to use, based on how many powers of 1024 go into the size, and
		//how many labels we have
		$order = min(
					floor(
						log($size) / log(1024)
					),
				  count($labels)-1);
	
		return round($size / pow(1024, $order), $precision). $labels[$order];
	}
}