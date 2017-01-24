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




// This plugin shows some static content


class zenario_twig_snippet extends zenario_html_snippet {
	
	protected $twigVars = array();
	protected $enablePhraseCodeReplace = false;
	
	function init() {
		if (cms_core::$isTwig) return;
		
		$init = zenario_html_snippet::init();
		
		switch ($this->setting('cache')) {
			case 'maximise':
				$this->allowCaching(
					$atAll = true, $this->setting('if_user_logged_in'),
					$this->setting('if_get_set'), $this->setting('if_post_set'),
					$this->setting('if_session_set'), $this->setting('if_cookie_set'));
				$this->clearCacheBy(
					$this->setting('clear_by_content'), $this->setting('clear_by_menu'), $this->setting('clear_by_user'),
					$this->setting('clear_by_file'), $this->setting('clear_by_module'));
				break;
			
			case 'safely':
				$this->allowCaching(
					$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = false, $ifCookieSet = false);
				$this->clearCacheBy(
					$clearByContent = true, $clearByMenu = true, $clearByUser = true, $clearByFile = true, $clearByModuleData = true);
				break;
			
			default:
				$this->allowCaching(
					$atAll = false);
		}
		
		if ($init) {
			$this->setTwigVars();
			$this->raw_html = $this->twigFramework($this->twigVars, true, $this->raw_html);
		}
		
		return $init;
	}
	
	protected function setTwigVars() {
		if (cms_core::$isTwig) return;
		
		//...
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (cms_core::$isTwig) return;
		
		//...
	}

}
