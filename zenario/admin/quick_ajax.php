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

require '../basicheader.inc.php';
ze\cookie::startSession();

if (!empty($_REQUEST['keep_session_alive'])) {
	//This request has no purpose other than to start the session and keep the session
	//from timing out

//Remember a toast message that was being displayed shortly before a page reload
} elseif (isset($_POST['_remember_toast']) && json_decode($_POST['_remember_toast'])) {
	//N.b. json_decode() above is just to validate the data
	
	$_SESSION['_remember_toast'] = $_POST['_remember_toast'];

} elseif (isset($_POST['_draft_set_callback'])) {
	
	$_SESSION['zenario_draft_callback'] = $_POST['_draft_set_callback'];
	$_SESSION['zenario_draft_callback_scroll_pos'] = $_POST['_scroll_pos'] ?? 0;
	$_SESSION['page_toolbar'] = $_POST['_save_page_toolbar'] ?? '';
	$_SESSION['page_mode'] = $_POST['_save_page_mode'] ?? '';


} elseif (isset($_POST['_save_page_mode'])) {
	
	$_SESSION['page_toolbar'] = $_POST['_save_page_toolbar'];
	$_SESSION['page_mode'] = $_POST['_save_page_mode'];

} elseif (isset($_REQUEST['_get_link_statuses'])) {
    $statuses = [];
    if (isset($_POST['links']) && ($links = $_POST['links'])) {
        
        
        ze\db::loadSiteConfig();
        ze\priv::exitIfNot();
        
        $data = [];
        $spareAliases = ze\row::getArray('spare_aliases', 'alias', []);
        
        foreach ($links as $i => $link) {
            $linkStatus = 'content_not_found';
            
            $urlParts = parse_url($link);
            $get = [];
            if (!empty($urlParts['query'])) {
                parse_str($urlParts['query'], $get);
            }
            
            //Fix a bug where parse_url() and parse_str() sometimes leaves a slash at the start
            if (isset($get['cID'])
             && is_string($get['cID'])
             && $get['cID'][0] === '/') {
            	$get['cID'] = substr($get['cID'], 1);
            }
            
            $request = $get;
            $post = [];
            $cID = $cType = $redirectNeeded = $aliasInURL = false;
            ze\content::resolveFromRequest($cID, $cType, $redirectNeeded, $aliasInURL, $get, $request, $post);
            
            if (in_array($aliasInURL, $spareAliases)) {
            	$linkStatus = 'spare_alias';
        	}
        	
            if ($cID && $cType) {
            	
            	//This is a bit of a hack, but I need to change the $_GET and $_REQUEST to the $get from each link,
            	//as some permission checks look at these variables directly
            	$_GET = $get;
            	$_REQUEST = $get;
            	$_POST = [];
            	
            	//Check the permisions of the content item being linked to, and get the content item's status.
            	//Also cache this, as often we'll get many links to the same page
            	$tagId = $cType. '_'. $cID;
            	if (!isset($data[$tagId])) {
            		$data[$tagId] = ['content' => [], 'version' => []];
            		$chain = false;
					$data[$tagId]['perms'] = ze\content::getShowableContent($data[$tagId]['content'], $chain, $data[$tagId]['version'], $cID, $cType, $requestVersion = false, $checkRequestVars = true, $adminMode = true, $adminsSee400Errors = true);
            	}
           		$perms = $data[$tagId]['perms'];
	            
	            //Check to see if this looks like a link to a slide
	            if ($perms
	             && (!empty($get['state'])
	              || !empty($get['slideId'])
	              || !empty($get['slideNum']))) {
	            	
	            	do {
						$key = ['is_slide' => 1];
					
						//Work out which slide this is... something that's either easy or hard depending on what variables
						//we find in the URL!
						if (!empty($get['slideId'])) {
							//Easy case: the slide's id is in the URL
							$key['id'] = $get['slideId'];
					
						} else {
							if (!empty($get['instanceId'])) {
								//Slightly harder case: we only have the instance id.
								$key['instance_id'] = $get['instanceId'];
							
							} else {
								//Very hard case: don't know the instance id, need to call ze\plugin::slotContents()
								//Again we'll try to cache this
								$slotName = $get['slotName'] ?? false;
								$slotCode = 'instanceId`'. $slotName;
								
								if (!isset($data[$tagId][$slotCode])) {
									$data[$tagId][$slotCode] = false;
								
									if ($layout = ze\row::get('layouts', ['family_name', 'file_base_name'], $data[$tagId]['version']['layout_id'])) {
										$slotContents = [];
										ze\plugin::slotContents(
											$slotContents,
											$cID, $cType, $data[$tagId]['version']['version'],
											$data[$tagId]['version']['layout_id'], $layout['family_name'], $layout['file_base_name'],
											false, $slotName, $ajaxReload = false, $runPlugins = false);
									
										foreach ($slotContents as $slot) {
											if (!empty($slot['instance_id'])
											 && !empty($slot['class_name'])
											 && ($slot['class_name'] == 'zenario_plugin_nest'
											  || $slot['class_name'] == 'zenario_slideshow')) {
												$data[$tagId][$slotCode] = $slot['instance_id'];
												break;
											}
										}
									}
								}
								
								if (!$key['instance_id'] = $data[$tagId][$slotCode]) {
									continue;
								}
							}
							
							//If we have the instance id, we'll need to work out which slide this is for
							if (!empty($get['slideNum'])) {
								//Easy case: the slide's number is in the URL
								$key['slide_num'] = $get['slideNum'];
							} else {
								//Slightly harder case: we have the state in the URL, and need to look up which slide has that state
								$key['states'] = [$get['state']];
							}
						}
						
						//Attempt to find the slide that matches the variables we found above
						if ($privacy = ze\row::get('nested_plugins', ['privacy', 'at_location', 'smart_group_id', 'module_class_name', 'method_name', 'param_1', 'param_2'], $key)) {
							//Check the privacy rules for that slide!
							$perms = ze\content::checkItemPrivacy($privacy, $privacy, $cID, $cType, $data[$tagId]['version']['version']);
						}
					} while (false);
				}
                
                if (isset($data[$tagId]['content']['status'])) {
					switch ($data[$tagId]['content']['status']) {
						case 'published':
						case 'published_with_draft':
							$linkStatus = $data[$tagId]['content']['status'];
						
							if ($perms === ZENARIO_401_NOT_LOGGED_IN) {
								$linkStatus .= '_401';
						
							} elseif (!$perms) {
								$linkStatus .= '_403';
							}
							break;
					
						case 'first_draft':
						case 'hidden_with_draft':
						case 'hidden':
							$linkStatus = 'hidden';
							break;
						case 'spare_alias':
							$linkStatus = 'spare_alias';
							break;
					}
				}
            } 
            
            $statuses[$i] = $linkStatus;
        }
    }
    
    echo json_encode($statuses, true);

//Quickly look up the name of a file
} elseif (!empty($_REQUEST['lookupFileDetails'])) {
	
	
	
	ze\db::connectLocal();
	
	if (ze::isAdmin()) {
		$sql = "
			SELECT id, filename, width, height, checksum, `usage`
			FROM ". DB_PREFIX. "files
			WHERE id = ". (int) $_REQUEST['lookupFileDetails'];
		
		if ($result = ze\sql::select($sql)) {
			header('Content-Type: text/javascript; charset=UTF-8');
			echo json_encode(ze\sql::fetchAssoc($result));
		}
	}


//Check, load or save an admin's Storekeeper preferences
} elseif (!empty($_REQUEST['_manage_prefs'])) {
	
	
	
	ze\db::connectLocal();
	
	if (ze::isAdmin()) {
		if (!empty($_POST['_save_prefs']) && !empty($_POST['prefs'])) {
			$sql = "
				REPLACE INTO ". DB_PREFIX. "admin_organizer_prefs SET
					prefs = '". ze\escape::sql($_POST['prefs']). "',
					checksum = '". ze\escape::sql(substr($_POST['checksum'], 0, 22)). "',
					admin_id = ". (int) $_SESSION['admin_userid'];
			ze\sql::update($sql);
		
		} else {
			if (!empty($_REQUEST['_get_checksum'])) {
				$sql = "SELECT checksum";
			
			} elseif (!empty($_REQUEST['_load_prefs'])) {
				$sql = "SELECT prefs";
			
			} else {
				exit;
			}
			
			$sql .= "
				FROM ". DB_PREFIX. "admin_organizer_prefs
				WHERE admin_id = ". (int) $_SESSION['admin_userid'];
			$result = ze\sql::select($sql);
			
			if ($row = ze\sql::fetchRow($result)) {
				echo $row[0];
			} else {
				echo '{}';
			}
		}
	}


} elseif (isset($_REQUEST['password_suggestion'])) {
	
	require CMS_ROOT. 'zenario/visitorheader.inc.php';
	echo ze\userAdm::createPassword();



} elseif (isset($_POST['screen_name_suggestion'])) {
	
	require CMS_ROOT. 'zenario/adminheader.inc.php';
	echo ze\userAdm::generateIdentifier(false, [
		'first_name' => ($_POST['first_name'] ?? false),
		'last_name' => ($_POST['last_name'] ?? false),
		'email' => ($_POST['email'] ?? false),
		'screen_name' => ''
	]);


} elseif (isset($_POST['_validate_alias'])) {
	
	require CMS_ROOT. 'zenario/adminheader.inc.php';
	
	
	if ($alias = $_POST['alias'] ?? false) {
		
		$lines = ze\contentAdm::validateAlias($alias, ($_POST['cID'] ?? false), ($_POST['cType'] ?? false), ($_POST['equivId'] ?? false));
		
		if (!$equivId = $_POST['equivId'] ?? false) {
			$equivId = ze\content::equivId($_POST['cID'] ?? false, ($_POST['cType'] ?? false));
		}
		
		if (empty($lines) && $alias) {
			$sql = "
				SELECT lang_code_in_url, language_id, alias
				FROM ". DB_PREFIX. "content_items
				WHERE alias != ''
				  AND alias < '". ze\escape::sql($alias). "'
				  AND (equiv_id, type) NOT IN ((". (int) $equivId. ", '". ze\escape::sql($_POST['cType'] ?? false). "'))
				ORDER BY alias DESC, language_id DESC";
			$result = ze\sql::select($sql);
			$lastAlias = ze\sql::fetchRow($result);
			
			if (($_POST['cID'] ?? false) && ($_POST['cType'] ?? false)) {
				$sql = "
					SELECT lang_code_in_url, language_id, alias
					FROM ". DB_PREFIX. "content_items
					WHERE id = ". (int) ($_POST['cID'] ?? false). "
					  AND type = '". ze\escape::sql($_POST['cType'] ?? false). "'";
				$result = ze\sql::select($sql);
				$thisAlias = ze\sql::fetchRow($result);
				$thisAlias[2] = $alias;
			
			} else {
				$thisAlias = ['default', ($_POST['langId'] ?? false), $alias];
			}
			
			if ($_POST['lang_code_in_url'] ?? false) {
				$thisAlias[0] = $_POST['lang_code_in_url'] ?? false;
			}
			
			$sql = "
				SELECT lang_code_in_url, language_id, alias
				FROM ". DB_PREFIX. "content_items
				WHERE alias != ''
				  AND alias > '". ze\escape::sql($alias). "'
				  AND (equiv_id, type) NOT IN ((". (int) $equivId. ", '". ze\escape::sql($_POST['cType'] ?? false). "'))
				ORDER BY alias, language_id";
			$result = ze\sql::select($sql);
			$nextAlias = ze\sql::fetchRow($result);
			
			
			$lines = [];
			$i = 0;
			foreach ([ze\admin::phrase('Prev:') => $lastAlias, ze\admin::phrase('This:') => $thisAlias, ze\admin::phrase('Next:') => $nextAlias] as $phrase => $content) {
				++$i;
				if ($content) {
					$line = $phrase. ' '. ($i == 2? '<i>' : '');
					
					//If multiple languages are enabled on this site, check to see if we need to add the language code to the alias.
					if (ze\lang::count() > 1) {
						//We will need to add the language code if the alias is used more than once,
						//the settings for that Content Item say so, or if the settings for the Content Item are left on
						//default and the Site Settings say so.
						if ($content[0] == 'show' || ($content[0] == 'default' && !ze::setting('translations_hide_language_code'))) {
							$needToUseLangCode = true;
						} else {
							$sql = "
								SELECT 1
								FROM ". DB_PREFIX. "content_items
								WHERE alias = '". ze\escape::sql($content[2]). "'
								LIMIT 2";
							$result = ze\sql::select($sql);
							$needToUseLangCode = ze\sql::fetchRow($result) && ($i == 2 || ze\sql::fetchRow($result));
						}
						
						if ($needToUseLangCode) {
							$aliasOrCID = $content[2]. ','. $content[1];
							if (ze::setting('mod_rewrite_enabled')) {
								$link = $aliasOrCID. ze::setting('mod_rewrite_suffix');
							} else {
								$link = DIRECTORY_INDEX_FILENAME. '?cID='. $aliasOrCID;
							}
							$line .= '<u>'. htmlspecialchars($link). '</u>, ';
						}
					}
					
					$aliasOrCID = $content[2];
					if (ze::setting('mod_rewrite_enabled')) {
						$link = $aliasOrCID. ze::setting('mod_rewrite_suffix');
					} else {
						$link = DIRECTORY_INDEX_FILENAME. '?cID='. $aliasOrCID;
					}
					$line .= '<u>'. htmlspecialchars($link). '</u>';
				
					$lines[] = $line. ($i == 2? '</i>' : '');
					
				} else {
					$lines[] =
						$phrase. ' '. ze\admin::phrase('[none]');
				}
			}
		}
	
	} else {
		$lines = [];
	}
	
	
	echo json_encode($lines);

} elseif (isset($_REQUEST['_show_help_tour_next_time'])) {
	require CMS_ROOT. 'zenario/adminheader.inc.php';
	ze\admin::setSetting('show_help_tour_next_time', (bool) $_REQUEST['_show_help_tour_next_time']);
}




exit;