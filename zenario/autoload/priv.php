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

namespace ze;

class priv {




	//	Permission functions  //

	const checkFromTwig = true;

	//Check to see if an Admin has a certain privilege
	//Formerly "checkPriv()"
	public static function check($action = false, $editCID = false, $editCType = false, $editCVersion = 'latest', $welcomePage = false) {
	
		//If the Admin is not logged in to this site, then they shouldn't have Admin rights here
		if (!empty($_SESSION['admin_userid'])
		 && !empty($_SESSION['admin_permissions'])
		 && !empty($_SESSION['admin_logged_into_site'])
		 && $_SESSION['admin_logged_into_site'] == COOKIE_DOMAIN. SUBDIRECTORY. \ze::setting('site_id')
	
		//If an admin looks at an embedded link, show them what it would look like if they were logged out.
		//(N.b. if we didn't do this, they'd see a security error due to the X-Frame-Options logic in index.php.)
		 && !isset($_REQUEST['zembedded'])
	
		//If the Admin hasn't passed the welcome page, then they shouldn't be able to use their
		//Admin rights anywhere except the welcome page
		 && ($welcomePage || !empty($_SESSION['admin_logged_in']))) {
		
			//If this is a check to edit a Content Item, also check to see if it is unlocked to
			//the current admin and able to be edited
			if (!$welcomePage
			 && $editCID && $editCType
		
			//Permissions to view languages, menu nodes, plugins and export content items are exceptions and
			//should be granted even if the admin could not edit a content item
			 && $action != '_PRIV_VIEW_LANGUAGE'
			 && $action != '_PRIV_VIEW_MENU_ITEM'
			 && $action != '_PRIV_EXPORT_CONTENT_ITEM'
			 && $action != '_PRIV_VIEW_REUSABLE_PLUGIN') {
			
				//If this is a check on the current content item, there's no need to query the
				//database for info we already have
				if ($editCID === \ze::$cID
				 && $editCType === \ze::$cType) {
					$status = \ze::$status;
					$equivId = \ze::$equivId;
					$adminVersion = \ze::$adminVersion;
					$langId = \ze::$langId;
					$locked = \ze::$locked;
			
				//Otherwise look up the details from the database
				} else {
					if (!$content = \ze\row::get(
						'content_items',
						['equiv_id', 'language_id', 'status', 'admin_version', 'lock_owner_id'],
						['id' => $editCID, 'type' => $editCType]
					)) {
						return false;
					}
				
					$status = $content['status'];
					$equivId = $content['equiv_id'];
					$adminVersion = $content['admin_version'];
					$langId = $content['language_id'];
					$locked = $content['lock_owner_id'] && $content['lock_owner_id'] != $_SESSION['admin_userid'];
				}
			
				//Deleted or locked content items cannot be edited
				if ($status == 'deleted' || $locked) {
					return false;
		
				//If a specific version is given, check that version is a draft
				} elseif ($editCVersion !== 'latest') {
					if (($editCVersion !== true && $editCVersion != $adminVersion)
					 || $status == 'published'
					 || $status == 'hidden'
					 || $status == 'trashed') {
						return false;
					}
				}
			
				if ($_SESSION['admin_permissions'] == 'specific_areas') {
				
					//If this admin can only edit specific content items,
					//or can only edit content items of a specific content type,
					//or can only edit content items of a specific language,
					//check that this is one of those content items.
					if (empty($_SESSION['admin_specific_languages'][$langId])
					 && empty($_SESSION['admin_specific_content_types'][$editCType])
					 && empty($_SESSION['admin_specific_content_items'][$editCType. '_'. $editCID])) {
						return false;
					}
				}
			}
		
			//No action specified? Just check if the admin is logged in.
			if ($action === false) {
				return true;
		
			//Otherwise run different logic depending on this admin's permissions
			} else {
				switch ($_SESSION['admin_permissions']) {
					//Always return true if the admin has every permission
					case 'all_permissions':
						return true;
				
					//If the admin has specific actions, check they have the specified action
					case 'specific_actions':
						return !empty($_SESSION['privs'][$action]);
				
					//Translators/microsite admins can only have a few set permissions,
					//anything else should be denied.
					case 'specific_areas':
						switch ($action) {
							case 'perm_author':
							case 'perm_editmenu':
							case 'perm_publish':
							case '_PRIV_VIEW_SITE_SETTING':
							case '_PRIV_VIEW_CONTENT_ITEM_SETTINGS':
							case '_PRIV_VIEW_MENU_ITEM':
							case '_PRIV_EDIT_MENU_TEXT':
							case '_PRIV_CREATE_TRANSLATION_FIRST_DRAFT':
							case '_PRIV_EDIT_DRAFT':
							case '_PRIV_CREATE_REVISION_DRAFT':
							case '_PRIV_DELETE_DRAFT':
							case '_PRIV_EDIT_CONTENT_ITEM_TEMPLATE':
							case '_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE':
							case '_PRIV_IMPORT_CONTENT_ITEM':
							case '_PRIV_EXPORT_CONTENT_ITEM':
							case '_PRIV_HIDE_CONTENT_ITEM':
							case '_PRIV_PUBLISH_CONTENT_ITEM':
							case '_PRIV_VIEW_LANGUAGE':
							case '_PRIV_MANAGE_LANGUAGE_PHRASE':
								return true;
							
							//Allow admins to create content items of certain types, if they have that content type enabled
							case '_PRIV_CREATE_FIRST_DRAFT':
								if ($editCType) {
									return !empty($_SESSION['admin_specific_content_types'][$editCType]);
								} else {
									return true;
								}
						}
				}
			}
		}
	
		return false;
	}
	
	
	//A more agressive version of check priv that stops all execution if an admin does not have the requested privilege
	//Formerly "exitIfNotCheckPriv()"
	public static function exitIfNot($action = false, $editCID = false, $editCType = false, $editCVersion = 'latest', $welcomePage = false) {
		if (!\ze\priv::check($action, $editCID, $editCType, $editCVersion, $welcomePage)) {
			exit;
		}
		return true;
	}






	//Check to see if an admin can edit a specific menu node
	//Formerly "checkPrivForMenuText()"
	public static function onMenuText($action, $menuNodeId, $langId, $sectionId = false) {
	
		//Run the usual \ze\priv::check() function first
		if (\ze\priv::check($action)) {
			switch ($_SESSION['admin_permissions']) {
				case 'all_permissions':
				case 'specific_actions':
					//Most normal administrators can edit menu text if \ze\priv::check() says they can
					return true;
			
				case 'specific_areas':
					//If an admin can only edit certain languages, allow them to edit the menu text if it
					//is specificially for this language
					if (!empty($_SESSION['admin_specific_languages'][$langId])) {
						return true;
					}
				
					//If an admin can only edit certain content items, allow them to edit the menu text if it
					//is for this one
					foreach (\ze\sql::select("
						SELECT c.tag_id, c.type
						FROM ". DB_PREFIX. "menu_nodes AS mn
						INNER JOIN ". DB_PREFIX. "content_items AS c
						   ON c.equiv_id = mn.equiv_id
						  AND c.type = mn.content_type
						  AND c.language_id = '". \ze\escape::asciiInSQL($langId). "'
						WHERE mn.id = ". (int) $menuNodeId
					) as $cItem) {
						if (!empty($_SESSION['admin_specific_content_types'][$cItem['type']])
						 || !empty($_SESSION['admin_specific_content_items'][$cItem['tag_id']])) {
							return true;
						}
					}
			}
		}
	
		return false;
	}

	//Check to see if an admin can edit a specific language
	//Formerly "checkPrivForLanguage()"
	public static function onLanguage($action, $langId) {
	
		//Run the usual \ze\priv::check() function first
		if (\ze\priv::check($action)) {
			switch ($_SESSION['admin_permissions']) {
				case 'all_permissions':
				case 'specific_actions':
					//Most normal administrators can edit menu text if \ze\priv::check() says they can
					return true;
			
				case 'specific_areas':
					//If an admin can only edit certain languages, allow them to edit the menu text if it
					//is specificially for this language
					if (!empty($_SESSION['admin_specific_languages'][$langId])) {
						return true;
					}
			}
		}
	
		return false;
	}


}