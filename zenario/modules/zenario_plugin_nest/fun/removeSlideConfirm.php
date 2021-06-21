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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


$slideName = null;
foreach (explode(',', $eggIds) as $eggId) {
	if (($egg = ze\pluginAdm::getNestDetails($eggId))
	 && ($egg['is_slide'])) {
		if ($slideName === null) {
			$slideName = $egg['name_or_title'];
		} else {
			$slideName = false;
		}
	}
}

//Display a confirmation box, asking the admin if they want to delete the plugin(s)
if (!$slideName) {
	$message =
		'<p>'.
			ze\admin::phrase('Are you sure you wish to delete the selected slides?').
		'</p><p>'.
			ze\admin::phrase('Any plugins on these slides will also be deleted, but any attached images will be left in the image library.').
		'</p>';

} else {
	$message =
		'<p>'.
			ze\admin::phrase('Are you sure you wish to delete the &quot;[[slideName]]&quot; slide?',
				['slideName' => htmlspecialchars($slideName)]).
		'<p>'.
			ze\admin::phrase('Any plugins on this slide will also be deleted, but any attached images will be left in the image library.').
		'</p>';
}


$usage = ze\pluginAdm::usage($instanceId, false);
$usagePublished = ze\pluginAdm::usage($instanceId, true);

if ($usage > 1 || $usagePublished > 0) {
	$message .=
		'<p>'. ze\admin::phrase(
			'This will affect <span class="zenario_x_published_items">[[published]] published content item(s)</span> <span class="zenario_y_items">(<a href="[[link]]" target="_blank">[[pages]] content item(s) in total</a>).</span>',
			[
				'pages' => $usage,
				'published' => $usagePublished,
				'link' => htmlspecialchars(ze\pluginAdm::usageOrganizerLink($instanceId))]
		). '</p>';
}

return $message;
