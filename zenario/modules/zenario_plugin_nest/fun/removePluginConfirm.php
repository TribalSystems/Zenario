<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

$pluginName = null;
foreach (explode(',', $eggIds) as $eggId) {
	if (($nestedItem = getNestDetails($eggId))
	 && (!$nestedItem['is_tab'])) {
		if ($pluginName === null) {
			$pluginName = $nestedItem['name_or_title'];
		} else {
			$pluginName = false;
		}
	}
}

//Display a confirmation box, asking the admin if they want to delete the plugin(s)
if (!$pluginName) {
	$message =
		'<p>'. adminPhrase('Are you sure you wish to remove the selected Plugins from the Nest?'). '</p>'.
		'<p>'. adminPhrase('Their settings will be deleted but any attached images will be left in the Media panel.'). '</p>';

} else {
	$message =
		'<p>'. adminPhrase('Are you sure you wish to remove the &quot;[[name]]&quot; Plugin from the Nest?', array('name' => htmlspecialchars($pluginName))). '</p>'.
		'<p>'. adminPhrase('Its settings will be deleted but any attached images will be left in the Media panel.'). '</p>';
}


$usage = checkInstancesUsage($instanceId, false);
$usagePublished = checkInstancesUsage($instanceId, true);

if ($usage > 1 || $usagePublished > 0) {
	$message .=
		'<p>'. adminPhrase(
			'This will affect <span class="zenario_x_published_items">[[published]] Published Content Item(s)</span> <span class="zenario_y_items">(<a href="[[link]]" target="_blank">[[pages]] Content Item(s) in total</a>).</span>',
			array(
				'pages' => (int) $usage,
				'published' => (int) $usagePublished,
				'link' => htmlspecialchars(getPluginInstanceUsageStorekeeperDeepLink($instanceId)))
		). '</p>';
}

return $message;