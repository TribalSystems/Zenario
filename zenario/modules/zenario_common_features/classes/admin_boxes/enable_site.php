<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


class zenario_common_features__admin_boxes__enable_site extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if (setting('site_enabled')) {
			if (setting('site_mode') == 'production') {
				$box['tabs']['site']['fields']['enable_site_production']['value'] = 1;
			} else {
				$box['tabs']['site']['fields']['enable_site_development']['value'] = 1;
			}
		} else {
			$box['tabs']['site']['fields']['disable_site']['value'] = 1;
		}
		$box['tabs']['site']['fields']['site_disabled_title']['value'] = setting('site_disabled_title');
		$box['tabs']['site']['fields']['site_disabled_message']['value'] = setting('site_disabled_message');
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$box['tabs']['site']['notices']['checked']['show'] = false;
		if (!empty($fields['site/clear_cache']['pressed'])) {
			$box['tabs']['site']['notices']['checked']['show'] = true;
			
			zenarioClearCache();
			
			$box['tabs']['site']['show_errors_after_field'] = 'desc2';
		} else {
			unset($box['tabs']['site']['show_errors_after_field']);
		}
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if ($values['site/enable_site_production']
		 || $values['site/enable_site_development']) {
		 	$moduleErrors = '';
			if (checkIfDBUpdatesAreNeeded($moduleErrors, $andDoUpdates = false)) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('You must apply database updates before you can enable your site.');
			}
			
			if (!checkRowExists('languages', array())) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('You must enable a Language before you can enable your site.');
			
			//If the site isn't currently live, force people to review and publish all of the special pages before doing so
			} elseif (!setting('site_enabled')) {
				$tags = '';
				
				$result = getRows(
					'special_pages',
					array('equiv_id', 'content_type'),
					array('logic' => array('create_and_maintain_in_default_language','create_and_maintain_in_all_languages')),
					array('page_type'));
				
				while ($row = sqlFetchAssoc($result)) {
					if (!getRow('content_items', 'visitor_version', array('id' => $row['equiv_id'], 'type' => $row['content_type']))) {
						$tags .= ($tags? ', ' : ''). '"'. formatTag($row['equiv_id'], $row['content_type']). '"';
					}
				}
				
				if ($tags) {
					$box['tabs']['site']['errors'][] =
						adminPhrase('You must publish every Special Page needed by the CMS before you can enable your site. Please publish the following pages: [[tags]].', array('tags' => $tags));
				}
			}
		
		} else {
			if (!$values['site/site_disabled_title']) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('Please enter a browser title.');
			}
			if (!$values['site/site_disabled_message']) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('Please enter a message.');
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	
		if (checkPriv('_PRIV_EDIT_SITE_SETTING')) {
			setSetting('site_disabled_title', $values['site/site_disabled_title']);
			setSetting('site_disabled_message', $values['site/site_disabled_message']);
			
			if ($values['site/enable_site_production']) {
				setSetting('site_mode', 'production');
				setSetting('site_enabled', 1);
				$box['key']['id'] = 'site_enabled';
			
			} elseif ($values['site/enable_site_development']) {
				setSetting('site_mode', 'development');
				setSetting('site_enabled', 1);
				$box['key']['id'] = 'site_enabled';
			
			} else {
				setSetting('site_mode', 'development');
				setSetting('site_enabled', '');
				$box['key']['id'] = 'site_disabled';
			}
		}
	}
}
