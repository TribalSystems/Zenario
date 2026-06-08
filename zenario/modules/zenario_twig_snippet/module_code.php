<?php
/*
 * Copyright (c) 2026, Tribal Limited
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




// This plugin shows some static content


class zenario_twig_snippet extends zenario_html_snippet {
	
	protected $enablePhraseCodeReplace = false;
	
	function init() {
		$hasContents = false;
		$twigVars = [];
		$twigPath = '';
		$twigSnippet = $this->setting('twig_snippet');
		
		if ($twigPath = \ze\plugin::twigSnippetPath($twigSnippet)) {
			$hasContents = true;
			
			if ($yaml = $this->setting('vars')) {
				$yaml = trim($yaml);
				if (!empty($yaml)) {
					try {
						$twigVars = \Spyc::YAMLLoadString($yaml);
					} catch (\Exception $e) {
						$twigVars = [];
					}
					
					if (empty($twigVars) || !is_array($twigVars)) {
						$twigVars = [];
					}
				}
			}
		}
		
		//If there is no content then don't display the plugin in visitor mode, except if this is in a Nest
		if (!$hasContents && !$this->eggId) {
			$this->empty = true;
			return false;
		}
		
		$this->frameworkOutputted = true;
		
		//Check to see if consent cookies is needed/required
		switch ($this->setting('cookie_consent')) {
			case 'needed':
				if (!ze\cookie::canSet('necessary')) {
					$this->raw_html = '';
					return false;
				}
				break;
			case 'specific_types':
				$cookieType = $this->setting('cookie_consent_specific_cookie_types');
				if (!(ze::in($cookieType, 'functionality', 'analytics', 'social_media') && ze\cookie::canSet($cookieType))) {
					$this->raw_html = '';
					return false;
				}
				break;
		}
		
		switch ($this->setting('cache')) {
			case 'maximise':
				$this->allowCaching(
					$atAll = true, $this->setting('if_user_logged_in'),
					$this->setting('if_get_or_post_var_set'),
					$this->setting('if_session_var_or_cookie_set')
				);
				$this->clearCacheBy(
					$this->setting('clear_by_content'), $this->setting('clear_by_menu'), $this->setting('clear_by_user'),
					$this->setting('clear_by_file'), $this->setting('clear_by_module')
				);
				break;
			
			case 'safely':
				$this->allowCaching(
					$atAll = true, $ifUserLoggedIn = false, $ifGetOrPostVarIsSet = false, $ifSessionVarOrCookieIsSet = false);
				$this->clearCacheBy(
					$clearByContent = true, $clearByMenu = true, $clearByFile = true, $clearByModuleData = true);
				break;
			
			default:
				$this->allowCaching(
					$atAll = false);
		}
		
		if ($hasContents) {
			$this->raw_html = $this->runTwigFromFile($twigVars, $twigPath);
		}
		
		return $hasContents;
	}
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
			
				//Move the warning about editing onto the second tab with the JavaScript on
				$box['tabs']['javascript']['notices'] = [];
				$box['tabs']['javascript']['notices']['note_about_editing_html'] =
					$box['tabs']['first_tab']['notices']['note_about_editing_html'];
				
				unset($box['tabs']['first_tab']['notices']['note_about_editing_html']);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		switch ($path) {
			case 'plugin_settings':
				
				//Look for defined twig snippets on the disk
				$fields['first_tab/twig_snippet']['values'] = ze\pluginAdm::listTwigSnippets($values['first_tab/twig_snippet']);
				
				if (($snippet = $fields['first_tab/twig_snippet']['values'][$values['first_tab/twig_snippet']] ?? false)
				 && (!empty($snippet['path']))) {
					$values['first_tab/html'] = file_get_contents(CMS_ROOT. $snippet['path']);
					$fields['first_tab/twig_path']['snippet']['label'] = $snippet['path'];
					
				} else {
					$values['first_tab/html'] = '';
					$fields['first_tab/twig_path']['snippet']['label'] = ' ';
				}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...
	}

}
