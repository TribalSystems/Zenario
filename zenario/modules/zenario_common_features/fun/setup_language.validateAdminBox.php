<?php
/*
 * Copyright (c) 2016, Tribal Limited
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


if ($values['settings/detect']) {
	if (!$values['settings/detect_lang_codes']) {
		$box['tabs']['settings']['errors'][] = adminPhrase('Please enter a language code to detect');
	
	} else {
		$sql = "
			SELECT id, detect_lang_codes
			FROM ". DB_NAME_PREFIX. "languages
			WHERE detect = 1
			  AND id != '". sqlEscape($box['key']['id']). "'";
		
		$siteLangs = array();
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			$siteLangs[strtolower($row['id'])] = $row['id'];
			
			foreach (explode(',', $row['detect_lang_codes']) as $lang) {
				$siteLangs[strtolower(trim($lang))] = $row['id'];
			}
		}
		
		foreach(explode(',', $values['settings/detect_lang_codes']) as $lang) {
			$lang = trim($lang);
			
			if (isset($siteLangs[$lang])) {
				$box['tabs']['settings']['errors'][] =
					adminPhrase('The language code "[[code]]" is already used by another language.', array('code' => $lang));
			}
		}
	}
}

if ($values['settings/use_domain'] && setting('primary_domain') && getNumLanguages() > 1) {
	if (!$values['settings/domain']) {
		$box['tabs']['settings']['errors'][] = adminPhrase('Please enter a domain.');
	
	} elseif (!aliasURLIsValid($values['settings/domain'])) {
		$box['tabs']['settings']['errors'][] = adminPhrase('Please enter a valid domain.');
	
	} elseif (checkRowExists('languages', array('id' => array('!' => $box['key']['id']), 'domain' => $values['settings/domain']))) {
		$box['tabs']['settings']['errors'][] = adminPhrase('The domain "[[settings/domain]]" is already used by another language.', $values);
	
	} elseif (checkRowExists('spare_domain_names', $values['settings/domain'])) {
		$box['tabs']['settings']['errors'][] = adminPhrase('The domain "[[settings/domain]]" is already used as a spare domain name.', $values);
	
	} elseif ($values['settings/domain'] != primaryDomain()) {
		$path = 'zenario/quick_ajax.php';
		$post = array('_get_data_revision' => 1, 'admin' => 1);
		if ($thisDomainCheck = curl(absCMSDirURL(). $path, $post)) {
			if ($cookieFreeDomainCheck = curl(($domain = httpOrHttps(). $values['settings/domain']. SUBDIRECTORY). $path, $post)) {
				if ($thisDomainCheck == $cookieFreeDomainCheck) {
					//Success, looks correct
				} else {
					$box['tabs']['settings']['errors'][] = adminPhrase('[[domain]] is pointing to a different site, or possibly an out-of-date copy of this site.', array('domain' => $domain));
				}
			} else {
				$box['tabs']['settings']['errors'][] = adminPhrase('A CURL request to [[domain]] failed. Either this is an invalid URL or Zenario is not at this location.', array('domain' => $domain));
			}
		}
	}
}


$maxEnabledLanguageCount = siteDescription('max_enabled_languages');
$enabledLanguages = getLanguages();
if ($maxEnabledLanguageCount && (count($enabledLanguages) >= $maxEnabledLanguageCount)) {
	$box['tabs']['settings']['errors'][] = adminPhrase('The maximun number of enabled languages on this site is [[count]]', array('count' => $maxEnabledLanguageCount));
}

return false;