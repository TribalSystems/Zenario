<?php
/*
 * Copyright (c) 2018, Tribal Limited
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




//If there is no requested page
//(i.e. the CMS has been accessed using example.com/ rather than example.com/alias.html),
//try to check for a landing page set up for their country.
//If one is found, take them there rather than leaving them on the home page.
if (empty($_GET) && empty($_POST)) {
	
	//Check for a language-specific domain. If one is being used, don't do a redirect
	$languageSpecificDomain = false;
	foreach (ze::$langs as $langId => $lang) {
		if ($lang['domain']
		 && $lang['domain'] == $_SERVER['HTTP_HOST']) {
			$languageSpecificDomain = true;
			break;
		}
	}
	
	if (!$languageSpecificDomain
	 && ze\module::inc('zenario_geoip_lookup')
	 && ze\module::inc('zenario_country_manager')
	 && ze\module::inc('zenario_geo_landing_pages')) {
		
		$redirectToCID = $redirectToCType = false;
		
		//Don't allow this page to be cached if it could potentially have a redirect for someone
		ze::$locationDependant = true;
		
		if (zenario_geo_landing_pages::getPageForIp(ze\user::ip(), $redirectToCID, $redirectToCType)) {
			if ($redirectToCID != ze::$homeCID || $redirectToCType != ze::$homeCType) {
				header(
					'location: '. ze\link::toItem($redirectToCID, $redirectToCType),
					true, 302);
				exit;
			}
		}
		
		unset($redirectToCID);
		unset($redirectToCType);
	}
	unset($languageSpecificDomain);



//If there was an alias in the URL but it didn't resolve to a content item,
//check to see if this is actually a spare alias redirect to another page or URL.
} else
if (!$cID
 && $aliasInURL
 && !is_numeric($aliasInURL)
 && ($_SERVER['SCRIPT_FILENAME'] == CMS_ROOT. 'index.php' || $_SERVER['SCRIPT_FILENAME'] == CMS_ROOT. DIRECTORY_INDEX_FILENAME)
 && ($spareAlias = ze\row::get('spare_aliases', ['target_loc', 'content_id', 'content_type', 'ext_url'], ['alias' => $aliasInURL]))) {
	
	if ($spareAlias['target_loc'] == 'int' && $spareAlias['content_id']) {
		header(
			'location: '. ze\link::toItem($spareAlias['content_id'], $spareAlias['content_type']),
			true, 301);
		exit;
	
	} elseif ($spareAlias['target_loc'] == 'ext' && $spareAlias['ext_url']) {
		header(
			'location: '. $spareAlias['ext_url'],
			true, 301);
		exit;
	}
	unset($spareAlias);
}