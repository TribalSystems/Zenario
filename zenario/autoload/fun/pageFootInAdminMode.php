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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


if (\ze::setting('dropbox_api_key')) {
	echo '
		'. $scriptTag. ' src="https://www.dropbox.com/static/api/2/dropins.js" id="dropboxjs" data-app-key="', htmlspecialchars(\ze::setting('dropbox_api_key')), '"></script>';
}

if ($includeOrganizer) {
	$moduleCodeHash = \ze\db::codeLastUpdated(). '___'. \ze::setting('yaml_version');
	
	$jsModuleIds = '';
	foreach (\ze\module::runningModules() as $module) {
		if (\ze::moduleDir($module['class_name'], 'js/organizer.js', true)
		 || \ze::moduleDir($module['class_name'], 'js/organizer.min.js', true)
		 || \ze::moduleDir($module['class_name'], 'js/storekeeper.js', true)
		 || \ze::moduleDir($module['class_name'], 'js/storekeeper.min.js', true)) {
			$jsModuleIds .= ($jsModuleIds? ',' : ''). $module['id'];
		}
	}
	
	$sql = "
		SELECT DISTINCT tfc.panel_type
		FROM ". DB_PREFIX. "tuix_file_contents AS tfc
		INNER JOIN ". DB_PREFIX. "modules AS m
		   ON m.class_name = tfc.module_class_name
		  AND m.status IN ('module_running', 'module_is_abstract')
		WHERE tfc.panel_type IN ('google_map', 'google_map_or_list', 'network_graph')";
	
	$panelTypes = \ze\ray::valuesToKeys(\ze\sql::fetchValues($sql));
	
	if (isset($panelTypes['google_map']) || isset($panelTypes['google_map_or_list'])) {
		if (!defined('ZENARIO_GOOGLE_MAP_ON_PAGE')) {
			define('ZENARIO_GOOGLE_MAP_ON_PAGE', true);
			echo '
'. $scriptTag. ' src="https://maps.google.com/maps/api/js?libraries=geometry&key=' , urlencode(\ze::setting('google_maps_api_key')) , '"></script>';
		}
	}
	
	if (isset($panelTypes['network_graph'])) {
		echo '
'. $scriptTag. ' src="', $prefix, 'libs/manually_maintained/mit/cytoscape/cytoscape.min.js"></script>';
	}
	
	echo '
'. $scriptTag. ' src="', $prefix, 'js/organizer.wrapper.js.php?', $w, '"></script>';
	echo '
'. $scriptTag. ' src="', $prefix, 'admin/organizer.ajax.php?_script=1?v=', $moduleCodeHash, '"></script>';
	
	echo '
'. $scriptTag. '>';
	
	echo '
zenarioA.moduleCodeHash = "', $moduleCodeHash, '";';
	
	
	if ($apacheMaxFilesize = \ze\dbAdm::apacheMaxFilesize()) {
		echo '
zenarioA.maxUpload = ', (int) $apacheMaxFilesize, ';
zenarioA.maxUploadF = "', \ze\escape::js(\ze\lang::formatFilesizeNicely($apacheMaxFilesize, $precision = 0, $adminMode = true)), '";';
	}
	
		echo '
</script>
'. $scriptTag. ' src="', $prefix, 'js/plugin.wrapper.js.php?', $w, '&amp;ids=', $jsModuleIds, '&amp;organizer=1"></script>';
}


$settings = [];
if (!empty(\ze::$siteConfig)) {
	foreach (\ze::$siteConfig as $setting => &$value) {
		if ($value) {
			if (is_numeric($value)
			 || $setting == 'admin_domain'
			 || $setting == 'cookie_require_consent'
			 || $setting == 'default_language'
			 || $setting == 'organizer_title'
			 || $setting == 'organizer_date_format'
			 || $setting == 'primary_domain'
			 || $setting == 'site_in_dev_mode'
			 || $setting == 'vis_time_format') {
				$settings[$setting] = \ze::$siteConfig[$setting];
			
			} elseif (substr($setting, -5) == '_path') {
				$settings[$setting] = true;
			}
		}
	}
}
$adminSettings = [];
if (!empty(\ze::$adminSettings)) {
	foreach (\ze::$adminSettings as $setting => &$value) {
		if ($value
		 && (/*$setting == '...'
		  || $setting == '...'
		  || */is_numeric($value))) {
			$adminSettings[$setting] = \ze::$adminSettings[$setting];
		}
	}
}

//Add any privs here that you need to check for in JavaScript
$adminPrivs = [
	'_PRIV_EDIT_SITE_SETTING' => \ze\priv::check('_PRIV_EDIT_SITE_SETTING')
];

$importantGetRequests = \ze\link::importantGetRequests();
if (empty($importantGetRequests)) {
	$importantGetRequests = '{}';
} else {
	$importantGetRequests = json_encode($importantGetRequests);
}

$adminHasSpecificPermsOnThisPage = 0;
if ($adminHasSpecificPerms = \ze\admin::hasSpecificPerms()) {
	if (\ze::$cID && \ze::$cType) {
		$adminHasSpecificPermsOnThisPage = \ze\priv::check(false, \ze::$cID, \ze::$cType);
	}
}



//Get a list of language names and flags for use in the formatting options
//We only need enabled languages if this is not Organizer
$langs = [];
$onlyShowEnabledLanguages = (bool) \ze::$cID;
if (!$onlyShowEnabledLanguages) {
	$enabledLangs = \ze\lang::getLanguages();
}
foreach (\ze\lang::getLanguages(!\ze::$cID) as $lang) {
	$langs[$lang['id']] = ['name' => $lang['english_name']];
	
	if ($onlyShowEnabledLanguages || !empty($enabledLangs[$lang['id']])) {
		$langs[$lang['id']]['enabled'] = 1;
		$langs[$lang['id']]['translate_phrases'] = $lang['translate_phrases'];
	}
}

$spareDomains = [];
$sql = '
	SELECT requested_url FROM ' . DB_PREFIX . 'spare_domain_names';
$result = \ze\sql::select($sql);
while ($row = \ze\sql::fetchAssoc($result)) {
	$spareDomains[] = \ze\link::protocol() . $row['requested_url'];
}

$draftMessage = false;
if (\ze::$isDraft) {
	$draftMessage = \ze\admin::phrase('This will only affect the draft version ([[version]]) of this content item.', ['version' => \ze::$cVersion]);
}

echo '
'. $scriptTag. '>
zenarioA.init(
	', (int) \ze::$cVersion, ',
	', (int) ($_SESSION['admin_userid'] ?? false), ',
	"', \ze\escape::js(\ze::$templateFamily), '",
	"', \ze\escape::js((($_SESSION['page_toolbar'] ?? false) ?: 'preview')), '",
	"', \ze\escape::js((($_SESSION['page_mode'] ?? false) ?: 'preview')), '",
	', \ze\ring::engToBoolean($_SESSION['admin_show_grid'] ?? false), ',
	', json_encode($settings), ',
	', json_encode($adminSettings), ',
	', json_encode($adminPrivs), ',
	', $importantGetRequests, ',
	', (int) $adminHasSpecificPerms, ',
	', (int) $adminHasSpecificPermsOnThisPage, ',
	', json_encode($langs), ',
	', json_encode($spareDomains), ',
	', json_encode($draftMessage), '
);';


if (!empty($_SESSION['zenario__deleted_so_home'])) {
	unset($_SESSION['zenario__deleted_so_home']);
	echo '
		zenarioA.longToast(', json_encode(ze\admin::phrase("Content item deleted, you've been taken to the home page.")), ', "zenario_content_item_deleted");';
}
if (!empty($_SESSION['zenario__deleted_so_up'])) {
	unset($_SESSION['zenario__deleted_so_up']);
	echo '
		zenarioA.longToast(', json_encode(ze\admin::phrase("Content item deleted, you've been taken to the page above in the menu.")), ', "zenario_content_item_deleted");';
}


echo '
</script>';
