<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

class pageCache {
	
	// Functionality for Page/Plugin Caching
	
	
	//protected static $debug = '';
	//protected static $debug2 = '';
	protected static $clearCacheBy = [];
	protected static $clearTags = [];
	protected static $clearOnShutdownRegistered = false;
	protected static $syncUsersOnShutdownRegistered = false;
	protected static $localDB = false;
	
	
	
	
	//Wrapper function for clearContentItem2() that adds a different database connection
	public static function clearContentItem($cID, $cType, $cVersion = false, $force = false, $clearEquivs = false) {
		
		//This function needs to run SQL queries, but doing that during/after another SQL query
		//would messi with the affected rows/insert id.
		//To avoid this, create a new connection.
		if (!self::$localDB) {
			self::$localDB = new \ze\db(DB_PREFIX, DBHOST, DBNAME, DBUSER, DBPASS, DBPORT);
		}
		
		$activeConnection = \ze::$dbL;
		\ze::$dbL = self::$localDB;
			
			//Run the checks for clearing the cache
			self::clearContentItem2($cID, $cType, $cVersion, $force, $clearEquivs);
		
		//Connect back to the local database when done
		\ze::$dbL = $activeConnection;
	}
	
	private static function clearContentItem2($cID, $cType, $cVersion, $force, $clearEquivs) {
		//Clear the cache for a specific Content Item
		if ($cID && $cType) {
			
			if ($clearEquivs) {
				foreach (\ze\content::equivalences($cID, $cType) as $equiv) {
					self::clearContentItem2($equiv['id'], $equiv['type'], false, $force, false);
				}
			
			} else {
				//If we've got exact information on the Content Item, clear the cache intelligently
				//(Note that if $cVersion was not set, this will check if any version of this Content Item is published)
				if ($force || \ze\content::isPublished($cID, $cType, $cVersion)) {
					self::$clearCacheBy['content'] = true;
					self::$clearTags[$cType. '_'. $cID] = true;
					
					//Clear the Menu as well if there is a Menu Node linked to this Content Item
					$equivId = \ze\content::equivId($cID, $cType);
					if (\ze\row::exists('menu_nodes', ['target_loc' => 'int', 'equiv_id' => $equivId, 'content_type' => $cType])) {
						self::$clearCacheBy['menu'] = true;
					}
				
					//if ($force)
						//self::$debug2 .= "\nclearing ". $cType. '_'. $cID. ", forced\n";
					//else
						//self::$debug2 .= "\nclearing ". $cType. '_'. $cID. "\n";
				}
			}
		}
	}
	
	//Attempt to check which table or tables are being changed, and clear the page cache accordingly.
	public static function reviewQuery(&$sql, &$ids, &$values, $table = false, $runSql = false) {
		
		
		//For some queries, I'd like to run the cache logic before the rows are changed;
		//e.g. if a row is deleted then it's too late to see what was there afterwards.
		//However if there is no change in state after the query is run, I don't want the cache to change!
		//Note that setting $runSql to true should cause this function to return the results of a \ze\sql::affectedRows() call.
		if ($runSql) {
			//If the $runSql flag is set, check the cache, then try the update, and revert back to the old values if nothing happened
			//$debug = self::$debug;
			//$debug2 = self::$debug2;
			$clearCacheBy = self::$clearCacheBy;
			$clearTags = self::$clearTags;
			
			self::reviewQuery($sql, $ids, $values, $table);
			
			\ze\sql::cacheFriendlyUpdate($sql);
			$affectedRows = \ze\sql::affectedRows();
			
			if ($affectedRows == 0) {
				//self::$debug = $debug;
				//self::$debug2 = $debug2;
				self::$clearCacheBy = $clearCacheBy;
				self::$clearTags = $clearTags;
			}
			
			return $affectedRows;
		}
		
		//Check if we need to check the cache.
		//(Note that if we've already declared that we're wiping everything in the cache, then there's no need to keep checking it.)
		$checkCache = \ze::setting('caching_enabled') && empty(self::$clearCacheBy['all']);
		
		//If there's nothing we need to do, stop here.
		if (!$checkCache) {
			return;
		}
		
		//If this is a flat SQL statement, attempt to read the table name from it.
		//Alas we can't be sure of the ids, so the clearing of the cache may be more destructive than if we knew them
		if (!$table && $sql) {
			//Tables that are being changed must be listed before certain keywords in SQL, so there's no need to search the entire
			//SQL query, just the bit of the query before these words
			$matches = [];
			if (preg_match('/\b(LIMIT|ORDER|SELECT|SET|VALUE|VALUES|WHERE)\b/i', $sql, $matches, PREG_OFFSET_CAPTURE)) {
				$test = substr($sql, 0, $matches[0][1]);
			} else {
				$test = $sql;
			}
			
			//Loop through any words in the SQL query that start with the DB_PREFIX
			$matches = [];
			if (preg_match_all('/\b'. preg_quote(DB_PREFIX). '(\w+)\b/', $test, $matches)) {
				if (!empty($matches[1])) {
					
					foreach ($matches[1] as $table) {
						if ($table) {
							//Call this function with table name to continue to the logic below
							//Unfortunately we have no array of keys though, so we can't clear the cache for specific Content Items
							self::reviewQuery($sql, $ids, $values, $table);
						}
					}
				}
			}
			
			return;
		}
		
		//If we still couldn't find a table name, then there's nothing else we can do
		if (!$table) {
			return;
		}
		
		
		
		//Clear the cache according to the table that is being updated
		//Possibly we'll have an array of keys as well, which will help clear the cache more specifically for changes to Content Items
		if ($checkCache) {
			//self::$debug .= ' '. $table;
			if (!empty($ids)) {
				//self::$debug2 .= "\n\n". $table. print_r($ids, true);
			} else {
				//self::$debug2 .= "\n\n". $table. "\n". $sql;
			}
			if (substr($table, 0, 3) == 'mod' && ($moduleId = (int) preg_replace('/mod(\d*)_.*/', '\1', $table))) {
				//Module table
				self::$clearCacheBy['module'] = true;
				
			} else {
				switch ($table) {
					
					//Admin tables; ignore these as they don't effect the output
					case 'action_admin_link':
					case 'admins':
					case 'admin_actions':
					case 'admin_roles':
					case 'admin_organizer_prefs':
					
					//Tables for other types of cache; again ignore these
					case 'content_cache':
					case 'plugin_instance_store':
					
					//These tables are all used in Admin Mode, but not really used to display anything to Visitors; ignore these as well
					case 'document_types':
					case 'email_templates':
					case 'inline_images':
					case 'jobs':
					case 'job_logs':
					case 'local_revision_numbers':
					case 'menu_hierarchy':
					case 'menu_positions':
					case 'modules':
					case 'module_dependencies':
					case 'plugin_setting_defs':
					case 'signals':
					case 'skins':
					case 'spare_domain_names':
					case 'spare_aliases':
					case 'layout_slot_link':
					case 'custom_datasets':
					case 'custom_dataset_tabs':
					case 'user_content_accesslog':
					
					//Anything that relies on group-membership or private items should never be cached, so we can ignore these tables too
					case 'group_link':
					case 'translation_chain_privacy':
						return;
					
					//File
					case 'files':
						self::$clearCacheBy['file'] = true;
						break;
					
					//Documents
					case 'documents':
						//If a document id changed, clear anything that links to a file
						self::$clearCacheBy['file'] = true;
						//If we ever implement code snippets instead of links to documents, we will need
						//to clear the contents of WYSIWYG Editors as well
						//self::$clearCacheBy['content'] = true;
						break;
					
					//Menu
					case 'menu_nodes':
					case 'menu_sections':
					case 'menu_text':
						self::$clearCacheBy['menu'] = true;
						break;
					
					//User
					case 'custom_dataset_values_link':
					case 'groups':
					case 'users':
						self::$clearCacheBy['user'] = true;
						break;
					
					//These tables relate to Content, and should clear anything that ties into Content
					case 'categories':
						self::$clearCacheBy['content'] = true;
						break;
					
					
					//These tables can relate to specific Content Items
						//If this is a Content Item that is not published, don't clear anything.
						//If this is a published Content Item, clear the cache for that Content Item and and anything that ties into Content.
						//If this is not related to a Content Item, or we can't resolve which Content Item they link to, clear the entire cache
					case 'category_item_link':
						if (!empty($ids['equiv_id']) && !empty($ids['content_type'])
						 && !is_array($ids['equiv_id']) && !is_array($ids['content_type'])) {
							//If we've got exact information on the Content Item, clear the cache intelligently
							self::clearContentItem($ids['equiv_id'], $ids['content_type'], false, false, $clearEquivs = true);
							self::$clearCacheBy['content'] = true;
						
						} else {
							//Otherwise clear the whole cache
							self::$clearCacheBy['all'] = true;
							//self::$debug2 .= "\nclear all\n";
						}
						break;
						
					case 'content_items':
						if (!empty($ids['id']) && !empty($ids['type'])
						 && !is_array($ids['id']) && !is_array($ids['type'])) {
							//If we've got exact information on the Content Item, clear the cache intelligently
							if ((isset($ids['status']) && $status = $ids['status'])
							 || (isset($values['status']) && $status = $values['status'])) {
								//Special case: if we are changing the status of a Content Item, there's no need to look the status up
								if (\ze::in($status, 'published', 'hidden', 'trashed')) {
									//The live version is being changed to published, hidden, trashed
									self::clearContentItem($ids['id'], $ids['type'], false, true);
								} else {
									//a draft is being created or deleted; no need to do anything with the cache
								}
							
							} else {
								self::clearContentItem($ids['id'], $ids['type']);
							}
						
						} else {
							//Otherwise clear the whole cache
							self::$clearCacheBy['all'] = true;
							//self::$debug2 .= "\nclear all\n";
						}
						break;
					
					case 'content_item_versions':
						if (!empty($ids['id']) && !empty($ids['type']) && !empty($ids['version'])
						 && !is_array($ids['id']) && !is_array($ids['type']) && !is_array($ids['version'])) {
							//If we've got exact information on the Content Item, clear the cache intelligently
							self::clearContentItem($ids['id'], $ids['type'], $ids['version']);
						
						} else {
							//Otherwise clear the whole cache
							self::$clearCacheBy['all'] = true;
							//self::$debug2 .= "\nclear all\n";
						}
						break;
					
					case 'nested_plugins':
					case 'plugin_settings':
						if (\ze::in($table, 'nested_plugins', 'plugin_settings')) {
							//If we can get the instance id we'll continue into the logic for the plugin_instances table
							
							//Grab the instance id if it is in the array
							if (!empty($ids['instance_id'])
							 && !is_array($ids['instance_id'])) {
								$table = 'plugin_instances';
								$ids = ['id' => $ids['instance_id']];
							
							//Attempt to look up an instance id from a nested Plugin
							} else
							if ($table == 'plugin_settings'
							 && !empty($ids['id'])
							 && !is_array($ids['id'])) {
								$result = \ze\sql::select("SELECT instance_id FROM ". DB_PREFIX. $table. " WHERE id = ". (int) $ids['id']);
								
								if ($row = \ze\sql::fetchAssoc($result)) {
									$table = 'plugin_instances';
									$ids = ['id' => $row['instance_id']];
								
								} else {
									//If we couldn't find this setting/nested Plugin, then it may already have been deleted.
									//In this case there's no need to clear the cache again
									break;
								}
							
							} else {
								//Otherwise don't use the logic for another table, and clear the whole cache instead
								self::$clearCacheBy['all'] = true;
								//self::$debug2 .= "\nclear all\n";
								break;
							}
						}
						
					case 'plugin_instances':
					case 'plugin_item_link':
						//self::$debug2 .= "\n=>\n". $table. print_r($ids, true);
						
						//If we have an instance or link id, but no idea of what Content Item this is, try to look this up from the instances table
						if ((!isset($ids['content_id']) || !isset($ids['content_type']) || !isset($ids['content_version']))
						 && !empty($ids['id'])
						 && !is_array($ids['id'])) {
							$result = \ze\sql::select("
								SELECT id, content_id, content_type, content_version
								FROM ". DB_PREFIX. ($table == 'plugin_item_link'? 'plugin_item_link' : 'plugin_instances'). "
								WHERE id = ". (int) $ids['id']);
							
							if (!$ids = \ze\sql::fetchAssoc($result)) {
								//If we couldn't find this setting/nested Plugin, then it may already have been deleted.
								//In this case there's no need to clear the cache again
								break;
							}
						}
						
						if (!empty($ids['content_id']) && !empty($ids['content_type']) && !empty($ids['content_version'])
						 && !is_array($ids['content_id']) && !is_array($ids['content_type']) && !is_array($ids['content_version'])) {
							//If we've got exact information on the Content Item, clear the cache intelligently
							self::clearContentItem($ids['content_id'], $ids['content_type'], $ids['content_version']);
						
						} else {
							//Otherwise clear the whole cache
							self::$clearCacheBy['all'] = true;
							//self::$debug2 .= "\nclear all\n";
						}
						
						break;
					
					
					//Completely empty the cache if a Visitor Phrase changes
					case 'visitor_phrases':
					
					//Completely empty the cache if something changes on the Layout Layer
					case 'plugin_layout_link':
					case 'layouts':
					//Completely clear the cache if any of these change, as there's no better way to handle things
					case 'content_types':
					case 'custom_dataset_fields':
					case 'languages':
					case 'site_settings':
					case 'special_pages':
					
					//Also clear the cache for anything we don't recognise
					default:
						self::$clearCacheBy['all'] = true;
						//self::$debug2 .= "\nclear all\n";
				}
			}
			
			if (!self::$clearOnShutdownRegistered && (!empty(self::$clearCacheBy) || !empty(self::$clearTags))) {
				register_shutdown_function(['ze\\pageCache', 'clearOnShutdown']);
				self::$clearOnShutdownRegistered = true;
			}
		}
	}
	
	public static function clearOnShutdown($clearAll = false) {
		
		if ($clearAll) {
			self::$clearCacheBy['all'] = true;
		}
		
		//Loop through the page-cache directory
		if (is_dir(CMS_ROOT. 'cache/pages/')) {
			if ($dh = opendir(CMS_ROOT. 'cache/pages/')) {
				while (($file = readdir($dh)) !== false) {
					if (substr($file, 0, 1) != '.') {
						$dir = CMS_ROOT. 'cache/pages/'. $file. '/';
						
						//Remove any directory that is marked to be cleared by one of the types of thing that we are clearing by
						if (!$rmDir = !empty(self::$clearCacheBy['all'])) {
							foreach (self::$clearCacheBy as $clearBy => $notEmpty) {
								if ($clearBy != 'all' && file_exists($dir. $clearBy)) {
									$rmDir = true;
									break;
								}
							}
							
							if (!$rmDir) {
								//Remove any directory that is for a Content Item that we are clearing by
								if (file_exists($dir. 'tag_id')
								 && ($id = file_get_contents($dir. 'tag_id'))
								 && (!empty(self::$clearTags[$id]))) {
									$rmDir = true;
								}
							}
						}
						
						if ($rmDir) {
							\ze\cache::deleteDir($dir);
						}
					}
				}
				closedir($dh);
			}
		}
		
	}
	
	
	


}