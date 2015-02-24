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


switch ($path) {
	case 'zenario_plugin_nest__tab':
		if (!get('refiner__nest')) {
			exit;
		}
		
		$box['key']['instanceId'] = get('refiner__nest');
		$instance = getPluginInstanceDetails($box['key']['instanceId']);
		
		if (!$instance['content_id']) {
			exitIfNotCheckPriv('_PRIV_VIEW_REUSABLE_PLUGIN');
		}
		
		$details = array();
		if (!empty($box['key']['id'])) {
			$details = getNestDetails($box['key']['id']);
			
			if ($instance['content_id']) {
				if (!checkPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
					$box['tabs']['tab']['edit_mode']['enabled'] = false;
				}
			}
		
		} else {
			if ($instance['content_id']) {
				exitIfNotCheckPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
			}
			
			$box['tabs']['tab']['edit_mode']['always_on'] = true;
			$details['tab'] = 1 + (int) self::maxTab($box['key']['instanceId']);
			$details['name_or_title'] = adminPhrase('Tab [[num]]', array('num' => $details['tab']));
			$box['title'] = adminPhrase('Adding a new Tab to the Nest "[[nest]]"',
								array('nest' => htmlspecialchars($instance['instance_name'])));
		}
		
		$box['tabs']['tab']['fields']['tab']['value'] = $details['tab'];
		$box['tabs']['tab']['fields']['name_or_title']['value'] = $details['name_or_title'];
		
		break;
	
	
	case 'zenario_plugin_nest__convert_between':
		
		$instance = $nestable = $numPlugins = $moduleId = $onlyOneModule = $onlyBanners = false;
		if (!$this->setupConversionAdminBox($box['key']['id'], $box['tabs']['convert']['fields'], $instance, $nestable, $numPlugins, $moduleId, $onlyOneModule, $onlyBanners)) {
			exit;
		
		} elseif (!(
			$instance['content_id']?
				checkPriv('_PRIV_MANAGE_ITEM_SLOT') && checkInstanceIsWireframeOnItemLayer($box['key']['id'])
			:	checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')
		)) {
			exit;
		}
		
		$box['title'] = adminPhrase('Converting the "[[name]]" Plugin', array('name' => $instance['instance_name']));
		
		if (!$instance['content_id']) {
			$box['confirm']['show'] = true;
			$box['confirm']['message'] =
				'<p>'. adminPhrase(
					'Are you sure you wish to convert the &quot;[[name]]&quot; Plugin into a Nest?',
					array('name' => htmlspecialchars($instance['instance_name']))
				). '</p>';
			
			$usage = checkInstancesUsage($instance['instance_id'], false);
			$usagePublished = checkInstancesUsage($instance['instance_id'], true);
			
			if ($usage > 1 || $usagePublished > 0) {
				$box['confirm']['message'] .=
					'<p>'. adminPhrase(
						'This will affect <span class="zenario_x_published_items">[[published]] Published Content Item(s)</span> <span class="zenario_y_items">(<a href="[[link]]" target="_blank">[[pages]] Content Item(s) in total</a>).</span>',
						array(
							'pages' => (int) $usage,
							'published' => (int) $usagePublished,
							'link' => htmlspecialchars(getPluginInstanceUsageStorekeeperDeepLink($instance['instance_id'])))
					). '</p>';
			}
		}
		
		break;
}
