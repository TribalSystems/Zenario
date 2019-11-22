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


namespace ze;

class link {


	const absoluteFromTwig = true;
	//Formerly "absCMSDirURL()"
	public static function absolute() {
		return \ze\link::protocol(). \ze\link::host(). SUBDIRECTORY;
	}

	//Formerly "absURLIfNeeded()"
	public static function absoluteIfNeeded($cookieFree = true) {
	
		if ($cookieFree && $cookieFreeDomain = \ze\link::cookieFreeDomain()) {
			return $cookieFreeDomain;
	
		} elseif (\ze::$mustUseFullPath) {
			return \ze\link::protocol(). \ze\link::host(). SUBDIRECTORY;
	
		} else {
			return '';
		}
	}

	//Check if this is https
	//Formerly "isHttps()"
	public static function isHttps() {
		return
			(isset($_SERVER['HTTPS']) && \ze\ring::engToBoolean($_SERVER['HTTPS']))
		 || (defined('USE_FORWARDED_IP')
		  && constant('USE_FORWARDED_IP')
		  && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
		  && substr($_SERVER['HTTP_X_FORWARDED_PROTO'], 0, 5) == 'https')
		 || (!empty($_SERVER['SCRIPT_URI'])
		  && substr($_SERVER['SCRIPT_URI'], 0, 5) == 'https');
	}

	const hostFromTwig = true;
	//Formerly "httpHost()"
	public static function host() {
		if (!empty($_SERVER['HTTP_HOST'])) {
			return $_SERVER['HTTP_HOST'];
		} else {
			return \ze\link::primaryDomain();
		}
	}

	//Formerly "httpHostWithoutPort()"
	public static function hostWithoutPort($host = false) {
		if ($host === false) {
			$host = \ze\link::host();
		}
	
		if (($pos = strpos($host, ':')) !== false) {
			$host = substr($host, 0, $pos);
		}
	
		return $host;
	}

	//Attempt to check whether we are in http or https, and return a value appropriately
	//If the USE_FORWARDED_IP constant is set we should try to check the HTTP_X_FORWARDED_PROTO variable.
	const protocolFromTwig = true;
	//Formerly "httpOrhttps()", "httpOrHttps()"
	public static function protocol() {
		if (\ze\link::isHttps()) {
			return 'https://';
		} else {
			return 'http://';
		}
	}

	//Deprecated, please just use DIRECTORY_INDEX_FILENAME instead!
	//Formerly "indexDotPHP()"
	public static function index($noBasePath = false) {
		if (defined('DIRECTORY_INDEX_FILENAME')) {
			$indexFile = DIRECTORY_INDEX_FILENAME;
		} else {
			$indexFile = 'index.php';
		}
	
		if ($indexFile) {
			return $indexFile;
		} elseif ($noBasePath) {
			return SUBDIRECTORY;
		} else {
			return '';
		}
	}


	const adminDomainFromTwig = true;
	//Formerly "adminDomain()"
	public static function adminDomain() {
		if (\ze::setting('admin_domain')) {
			return \ze::setting('admin_domain');
	
		} elseif (!empty($_SERVER['HTTP_HOST'])) {
			return $_SERVER['HTTP_HOST'];
	
		} else {
			return \ze\link::primaryDomain();
		}
	}

	//Formerly "adminDomainIsPrivate()"
	public static function adminDomainIsPrivate() {
		return \ze::setting('admin_domain') && !\ze::setting('admin_domain_is_public');
	}

	const primaryDomainFromTwig = true;
	//Formerly "primaryDomain()"
	public static function primaryDomain() {
		if (\ze::setting('primary_domain')) {
			return \ze::setting('primary_domain');
	
		} elseif (!empty($_SERVER['HTTP_HOST'])) {
			return $_SERVER['HTTP_HOST'];
	
		} elseif (\ze::setting('last_primary_domain')) {
			return \ze::setting('last_primary_domain');
	
		} else {
			return false;
		}
	}

	//Warning: this is deprecated, please use \ze\cookie::set() instead!
	//Formerly "cookieDomain()"
	public static function cookieDomain() {
		if (COOKIE_DOMAIN) {
			return COOKIE_DOMAIN;
	
		} else {
			return \ze\link::host();
		}
	}


	//Formerly "cookieFreeDomain()"
	public static function cookieFreeDomain() {
		if (\ze\link::protocol() == 'http://' && \ze::setting('use_cookie_free_domain') && \ze::setting('cookie_free_domain') && !\ze\priv::check()) {
			return 'http://'. \ze::setting('cookie_free_domain'). SUBDIRECTORY;
		} else {
			return false;
		}
	}


	//Formerly "importantGetRequests()"
	public static function importantGetRequests($includeCIDAndType = false) {

		$importantGetRequests = [];
		foreach(\ze::$importantGetRequests as $getRequest => $defaultValue) {
			if (isset($_GET[$getRequest]) && $_GET[$getRequest] != $defaultValue) {
				$importantGetRequests[$getRequest] = $_GET[$getRequest];
			}
		}
	
		if ($includeCIDAndType && \ze::$cID && \ze::$cType) {
			$importantGetRequests['cID'] = \ze::$cID;
			$importantGetRequests['cType'] = \ze::$cType;
		}
	
		return $importantGetRequests;
	}






	const toEquivalentItemFromTwig = true;
	//Formerly "linkToEquivalentItem()"
	public static function toEquivalentItem(
		$cID, $cType = 'html', $languageId = false, $fullPath = false, $request = '', $forceAliasInAdminMode = false
	) {

		if (\ze\content::langEquivalentItem($cID, $cType, $languageId)) {
			return \ze\link::toItem(
				$cID, $cType, $fullPath, $request, false,
				false, $forceAliasInAdminMode,
				false, $languageId
			);
		} else {
			return false;
		}
	}


	const toItemInVisitorsLanguageFromTwig = true;
	//Build a link to a content item
	//n.b. \ze\link::toItem() and \ze\content::resolveFromRequest() are essentially opposites of each other...
	//Formerly "linkToItemStayInCurrentLanguage()"
	public static function toItemInVisitorsLanguage(
		$cID, $cType = 'html', $fullPath = false, $request = '', $alias = false,
		$autoAddImportantRequests = false, $forceAliasInAdminMode = false,
		$equivId = false, $languageId = false
	) {
	
		$stayInCurrentLanguage =
			\ze::$visLang
		 &&	\ze::$visLang != \ze::$defaultLang
		 && (\ze::$langs[\ze::$visLang]['show_untranslated_content_items'] ?? false);

		return \ze\link::toItem(
			$cID, $cType, $fullPath, $request, $alias,
			$autoAddImportantRequests, $forceAliasInAdminMode,
			$equivId, $languageId, $stayInCurrentLanguage
		);
	}


	const toItemFromTwig = true;
	//Build a link to a content item
	//n.b. \ze\link::toItem() and \ze\content::resolveFromRequest() are essentially opposites of each other...
	//Formerly "linkToItem()"
	public static function toItem(
		$cID, $cType = 'html', $fullPath = false, $request = '', $alias = false,
		$autoAddImportantRequests = false, $forceAliasInAdminMode = false,
		$equivId = false, $languageId = false, $stayInCurrentLanguage = false,
		$useHierarchicalURLsIfEnabled = true, $overrideAlias = false, $overrideLangId = false
	) {
	
		//Catch the case where a tag id is entered, not a cID and cType
		if (!is_numeric($cID)) {
			$tagId = $cID;
			\ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
		}
	
		//From version 6.1 onwards, we're no longer allowing this function to be called
		//by placing the alias in the $cID variable
		if (!$cID || !is_numeric($cID) || !$cType) {
			return false;
		}
	
		//If there are slashes in the alias, we need to make sure to return a full URL, not a relative one.
		//But let the caller specifically override this by passing NEVER in.
		if ($fullPath === 'never') {
			$fullPath = false;
		} elseif (\ze::$mustUseFullPath) {
			$fullPath = true;
		}
	
		if (is_array($request)) {
			$request = http_build_query($request);
		}
	
	
		$adminMode = \ze::isAdmin();
		$mod_rewrite_enabled = \ze::setting('mod_rewrite_enabled');
		$mod_rewrite_slashes = \ze::setting('mod_rewrite_slashes');
		$mod_rewrite_suffix = \ze::setting('mod_rewrite_suffix');
		$useAlias = !$adminMode || $forceAliasInAdminMode || \ze::setting('mod_rewrite_admin_mode');;
		$multilingual = \ze\lang::count() > 1;
		$returnSlashForHomepage = false;
		$langSpecificDomain = false;
		$needToUseLangCode = false;
		$usingAlias = false;
		$content = false;
		$domain = false;
	
	
		//If this is a link to the current page, we can get some of the metadata from the ze variables
		//without needing to use the database to look it up
		if ($cID == \ze::$cID
		 && $cType == \ze::$cType) {
			$alias = \ze::$alias;
			$equivId = \ze::$equivId;
			$languageId = \ze::$langId;
	
		} elseif (!$multilingual) {
			//For single-lingual sites, the cID is always equal to the alias
			$equivId = $cID;
		}
	
		$autoAddImportantRequests =
			$autoAddImportantRequests
		 && $cType == \ze::$cType
		 && !empty(\ze::$importantGetRequests)
		 && is_array(\ze::$importantGetRequests);
		
		//Attempt to look up the alias/language if they weren't provided, and we might need them.
		if (($stayInCurrentLanguage && $multilingual && $languageId === false)
		 || (($useAlias || ($autoAddImportantRequests && !$equivId))
		  && ($multilingual
		   || $alias === false
		   || ($mod_rewrite_slashes && ($equivId === false || $languageId === false))
		))) {
			$result = \ze\sql::select("
				SELECT alias, equiv_id, language_id, lang_code_in_url
				FROM ". DB_PREFIX. "content_items
				WHERE id = ". (int) $cID. "
				  AND type = '". \ze\escape::sql($cType). "'"
			);
			if ($content = \ze\sql::fetchRow($result)) {
				$alias = $content[0];
				$equivId = $content[1];
				$languageId = $content[2];
				$lang_code_in_url = $content[3];
			} else {
				return false;
			}
		}
		
		//For admin previews
		if ($overrideAlias !== false) {
			$alias = $overrideAlias;
		}
		if ($overrideLangId !== false) {
			$languageId = $overrideLangId;
		}

		//Add important requests to the URL, if the content item being linked to is the current content item,
		//or a translation
		if ($autoAddImportantRequests
		 && $equivId == \ze::$equivId) {
			foreach(\ze::$importantGetRequests as $getRequest => $defaultValue) {
				if (isset($_GET[$getRequest]) && $_GET[$getRequest] != $defaultValue) {
					$request .= '&'. urlencode($getRequest). '='. urlencode($_GET[$getRequest]);
				}
			}
		}
	
		//On multi-lingual sites, use a language-specific domain if one is set up
		if ($useAlias && $multilingual && !empty(\ze::$langs[$languageId]['domain'])) {
			$domain = \ze::$langs[$languageId]['domain'];
	
			//If we're using a language specific domain, we don't need to add the language code into the URL later
			$langSpecificDomain = true;
	
		//Always try to use the admin domain in admin mode
		} elseif ($adminMode && !$useAlias && ($adminDomain = \ze::setting('admin_domain'))) {
			$domain = $adminDomain;

		} else {
			$domain = \ze\link::primaryDomain();
		}
	
		//If there is nothing in the request then links to the homepage
		//should always use just the domain name (with maybe the language code for multilingual sites)
		if ($useAlias
		 && $equivId == \ze::$homeEquivId
		 && $cType == \ze::$homeCType) {
			$fullPath = true;
			$returnSlashForHomepage = true;
		}
	
		//If this isn't the correct domain, use the full path to switch to it
		if ($fullPath
		 || empty($_SERVER['HTTP_HOST'])
		 || ($domain && $domain != $_SERVER['HTTP_HOST'])) {
		
			$fullPath = \ze\link::protocol(). $domain. SUBDIRECTORY;
		} else {
			$fullPath = '';
		}
	
		//If we're linking to a homepage, if possible, just use a slash and never show the alias
		if ($returnSlashForHomepage) {
		
			//If the site isn't multilingual, or if there is one domain per language, we can just use the domain and subdirectory
			if (!$multilingual || $langSpecificDomain) {
				return $fullPath. ($request? \ze\ring::addQu($request) : '');
		
			//If slashes are enabled in the URL, we'll make a sub-directory on a per-language basis
			} elseif ($mod_rewrite_slashes) {
				return $fullPath. $languageId. '/'. ($request? \ze\ring::addQu($request) : '');
			}
		
			//For any other cases we'll need to show the alias
		}

	
		//Link to the item using either the cID or the alias.
		if ($useAlias && $alias) {
			$aliasOrCID = $alias;
			$usingAlias = true;
		
			//If multiple languages are enabled on this site, check to see if we need to add the language code to the alias.
			if ($multilingual) {
				//We don't need to add the language code again if we've already used a language-specific domain
				if ($langSpecificDomain) {
					$needToUseLangCode = false;
				
				//Otherwise we will need to add the language code if the alias is used more than once,
				//the settings for that Content Item say so, or if the settings for the Content Item are left on
				//default and the Site Settings say so.
				} elseif ($lang_code_in_url == 'show' || ($lang_code_in_url == 'default' && !\ze::setting('translations_hide_language_code'))) {
					$needToUseLangCode = true;
			
				} else {
					$sql = "
						SELECT 1
						FROM ". DB_PREFIX. "content_items
						WHERE alias = '". \ze\escape::sql($alias). "'
						LIMIT 2";
					$result = \ze\sql::select($sql);
					$needToUseLangCode = \ze\sql::fetchRow($result) && \ze\sql::fetchRow($result);
				}
			
				//If we're not allowed slashes in the URL, and we need to add the language code,
				//add it to the end after a comma.
				if ($needToUseLangCode && !$mod_rewrite_slashes) {
					$aliasOrCID .= ','. $languageId;
				}
			}
		
		} else {
			$aliasOrCID = $cType. '_'. $cID;
		}
	
		//If enabled in the site settings, attempt to add the full menu tree into the friendly URL
		if ($useAlias && $mod_rewrite_slashes && $useHierarchicalURLsIfEnabled) {
			$aliasOrCID = \ze\link::hierarchicalAlias($equivId, $cType, $languageId, $aliasOrCID);
		}
	
		//If we're allowed slashes in the URL, and we need to add the language code,
		//then add it as a slash at the start of the URL
		if ($needToUseLangCode && $mod_rewrite_slashes) {
			$aliasOrCID = $languageId. '/'. $aliasOrCID;
		}
	
		//If a translation isn't available, have an option to show the page in the default
		//language
		if ($stayInCurrentLanguage
		 && \ze::$visLang != $languageId
		 && \ze::$visLang != \ze::$defaultLang) {
			$request .= '&visLang='. rawurlencode(\ze::$visLang);
		}
	
		//"Download now" format for old documents
		if ($useAlias
		 && $cType == 'document'
		 && $mod_rewrite_enabled) {
		
			switch (\ze\ring::addAmp($request)) {
				case '&download=1':
				case '&download=true':
				case '&download=1&cType=document':
				case '&download=true&cType=document':
				case '&cType=document&download=1':
				case '&cType=document&download=true':
					return $fullPath. $aliasOrCID. '.download';
			}
		}
	
		//"RSS link" shortcut. Note that this only works if there is only one plugin on a page with an RSS feed.
		//If there are two, this link will link to the first one on the page that we found.
		if ($useAlias && $request === '&method_call=showRSS' && $mod_rewrite_enabled) {
			return $fullPath. $aliasOrCID. '.rss';
	
		} elseif ($useAlias && $mod_rewrite_enabled) {
			return $fullPath. $aliasOrCID. $mod_rewrite_suffix. ($request? \ze\ring::addQu($request) : '');
	
		} else {
			$basePath = $fullPath. DIRECTORY_INDEX_FILENAME;
			if ($basePath === '') {
				$basePath = SUBDIRECTORY;
			}
			return $basePath. '?cID='. $aliasOrCID. ($request? \ze\ring::addAmp($request) : '');
		}
	}

	//Formerly "addHierarchicalAlias()"
	public static function hierarchicalAlias($equivId, $cType, $languageId, $alias) {
	
		//Try to get the menu node that this content item is for, and check if it has a parent to follow
		$sql = "
			SELECT id, parent_id, section_id
			FROM ". DB_PREFIX. "menu_nodes AS m
			WHERE m.equiv_id = ". (int) $equivId. "
			  AND m.content_type = '" . \ze\escape::sql($cType) . "'
			  AND m.target_loc = 'int'
			ORDER BY m.redundancy = 'primary' DESC
			LIMIT 1";
		$result = \ze\sql::select($sql);
	
		if (($menu = \ze\sql::fetchAssoc($result))
		 && ($menu['parent_id'])) {
		
			//Loop through the menu structure above. Where a content item has an alias,
			//add it into the URL.
			//Note that we should not add the same alias twice in a row - this may happen
			//if a content item has a secondary menu node
			$sql = "
				SELECT c.alias
				FROM ". DB_PREFIX. "menu_hierarchy AS mh
				INNER JOIN ". DB_PREFIX. "menu_nodes AS m
				   ON m.id = mh.ancestor_id
				  AND m.target_loc = 'int'
				INNER JOIN ". DB_PREFIX. "content_items AS c
				   ON c.equiv_id = m.equiv_id
				  AND c.type = m.content_type
				  AND c.language_id = '" . \ze\escape::sql($languageId) . "'
				WHERE mh.section_id = ". (int) $menu['section_id']. "
				  AND mh.child_id = ". (int) $menu['parent_id']. "
				ORDER BY mh.separation ASC";
			$result = \ze\sql::select($sql);
		
			$lastAlias = $alias;
			while ($menu = \ze\sql::fetchAssoc($result)) {
				if ($menu['alias'] != '') {
					if ($menu['alias'] != $lastAlias) {
						$alias = $menu['alias']. '/'. $alias;
						$lastAlias = $menu['alias'];
					}
				}
			}
		}
	
		return $alias;
	}
}