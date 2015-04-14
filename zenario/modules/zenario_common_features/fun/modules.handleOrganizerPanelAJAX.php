<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


if (!$module = getModuleDetails($ids)) {
	echo adminPhrase('Module not found!');
	exit;
}

$reload = false;
if (get('upgrade') && checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN') && $module['status'] == 'module_running') {
	if (!count($instances = succeedModule($ids, $preview = true))) {
		echo
			'<!--Message_Type:Error-->',
			'<!--Button_HTML:<input type="button" class="submit" value="', adminPhrase('OK'), '"/>-->',
			'<p>', adminPhrase(
				'There are no plugins from other Modules that the Module &quot;[[moduleName]]&quot; can handle.',
				array('moduleName' => htmlspecialchars($module['display_name']))
			), '</p>';
	
	} else {
		echo
			'<p>', adminPhrase(
				'The following plugins will now be handled by the &quot;[[moduleName]]&quot; Module:',
				array('moduleName' => htmlspecialchars($module['display_name']))
			), '</p>';
		
		echo '<ul>';
		foreach($instances as $instance) {
			echo '<li>', htmlspecialchars($instance), '</li>';
		}
		echo '</ul>';
	}

} elseif (post('upgrade') && checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN') && $module['status'] == 'module_running') {
	succeedModule($ids);

} elseif (post('suspend') && checkPriv('_PRIV_SUSPEND_MODULE') && $module['status'] == 'module_running') {
	$reload = true;
	suspendModule($ids);

} elseif (get('remove') || get('uninstall')) {
	$module = getModuleDetails($ids);
	
	if (get('remove')) {
		echo adminPhrase('Are you sure that you wish to remove the Module "[[display_name]]"?', $module);
	} else {
		echo adminPhrase('Are you sure that you wish to uninitialise the Module "[[display_name]]"?', $module);
	}
	
	echo "\n\n";
	
	echo adminPhrase('All of its data, ');
	
	if ($module['vlp_class'] && $module['vlp_class'] == $module['class_name']) {
		echo adminPhrase('all of its Phrases, ');
	}
	
	if ($module['is_pluggable']) {
		echo adminPhrase('all Plugins derived from this Module, ');
	}
	
	if (checkRowExists('special_pages', array('module_class_name' => $module['class_name']))) {
		echo adminPhrase('all Special Pages for this Module, ');
	}
	
	$result = getRows('content_types', 'content_type_name_en', array('module_id' => $ids), 'content_type_name_en');
	while ($contentType = sqlFetchAssoc($result)) {
		echo adminPhrase('all Content Items of the "[[content_type_name_en]]" Content Type, ', $contentType);
	}
	
	echo adminPhrase('WILL BE DELETED.');
	
	echo "\n\n";
	
	echo adminPhrase('This cannot be undone.');
	

} elseif (post('remove') && checkPriv("_PRIV_RESET_MODULE") && (!file_exists(CMS_ROOT . 'modules/'. $module['class_name']. '/module_code.php'))) {
	uninstallModule($ids, true);

} elseif (post('uninstall') && checkPriv("_PRIV_RESET_MODULE") && $module['status'] == 'module_suspended') {
	uninstallModule($ids);
}

//Send a command to reload Storekeeper, if the XML map may have changed.
if ($reload && moduleDir($module['class_name'], 'tuix/organizer', true)) {
	echo '<!--Reload_Storekeeper-->';
}

return false;