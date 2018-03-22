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




// This plugin shows some static content


class zenario_html_snippet extends ze\moduleBaseClass {

	protected $mergeFields = [];
	protected $sections = [];
	protected $empty = false;
	protected $raw_html = '';
	protected $enablePhraseCodeReplace = true;
	
	//When the plugin is set up, also get the content item's status and the content section to display
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$this->raw_html = $this->setting('html');
		
		//If there is no content then don't display the plugin in visitor mode, except if this is in a Nest
		if (!$this->raw_html && !$this->eggId) {
			$this->empty = true;
			return false;
		}
		
		$this->frameworkOutputted = true;
		
		//Check to see if consent cookies is needed/required
		switch ($this->setting('cookie_consent')) {
			case 'required':
				ze\cookie::requireConsent();
			case 'needed':
				if (!ze\cookie::canSet()) {
					$this->raw_html = '';
					return false;
				}
		}
		
		if (!$this->isVersionControlled && $this->enablePhraseCodeReplace) {
			$this->replacePhraseCodesInString($this->raw_html);
		}
		
		return true;
	}
	
	
	function showSlot() {
		if (ze::$isTwig) return;
		
		$this->showContent();
	}
	
	
	function showContent() {
		if (ze::$isTwig) return;
		
		$javascript = '';
		if ($this->setting('hide_in_admin_mode') && ze\priv::check()) {
			echo '<p>&nbsp;</p>';
		} else {
			echo $this->raw_html;
			$this->addJSOnAJAX();
		}
		
	}

	public function addToPageFoot() {
		if (ze::$isTwig) return;
		
		$javascript = '';
		if ($this->setting('hide_in_admin_mode') && ze\priv::check()) {
		
		} elseif ($this->hasJS($javascript)) {
			echo '
<script type="text/javascript">', $javascript, '
</script>';
		}
	}
	
	protected function addJSOnAJAX() {
		if ($this->methodCallIs('refreshPlugin')
		 && $this->hasJS($javascript)) {
			$this->callScript('window', 'eval', $javascript);
		}
	}
	
	protected function hasJS(&$javascript) {
		if (($javascript = trim($this->setting('minified_javascript')))
		 && (self::canMinifyJavaScript())) {
		
		} elseif ($javascript = trim($this->setting('javascript'))) {
		
		} else {
			$javascript = '';
			return false;
		}
		
		$javascript = '
var slotName = '. json_encode($this->slotName). ', containerId = '. json_encode($this->containerId). ';
'. $javascript;
		return true;
	}
	
	protected static function canMinifyJavaScript() {
		return !ze\server::isWindows() && ze\server::execEnabled();
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (self::canMinifyJavaScript()) {
			if (!empty($fields['javascript/minify']['pressed'])) {
				require_once CMS_ROOT. 'zenario/includes/js_minify.inc.php';
				define('IGNORE_REVERTS', true);
				define('RECOMPRESS_EVERYTHING', true);
				$values['javascript/minified_javascript'] = minifyString($values['javascript/javascript']);
			}
			$fields['javascript/minify']['hidden'] =
			$fields['javascript/minified_javascript']['hidden'] = false;
		} else {
			$fields['javascript/minify']['hidden'] =
			$fields['javascript/minified_javascript']['hidden'] = true;
		}
	}
	
	public function fillAdminSlotControls(&$controls) {
		if (ze::$isTwig) return;
		
		//If this is a version controlled plugin and the current administrator is an author,
		//show the cut/copy/patse options
		if ($this->isVersionControlled && ze\priv::check('_PRIV_EDIT_DRAFT')) {
			
			//Check whether something compatible was previously copied
			$copied =
				!empty($_SESSION['admin_copied_contents']['class_name'])
			 && ze::in($_SESSION['admin_copied_contents']['class_name'], 'zenario_html_snippet', 'zenario_twig_snippet', 'zenario_wysiwyg_editor');
			
			//If something has been entered, show the copy button
			if (!$this->empty) {
				$controls['actions']['copy_contents']['hidden'] = false;
				$controls['actions']['copy_contents']['onclick'] =
					str_replace('list,of,allowed,modules', 'zenario_html_snippet,zenario_twig_snippet,zenario_wysiwyg_editor',
						$controls['actions']['copy_contents']['onclick']);
			}
			
			//Check to see if this is the most recent version and the current administrator can make changes
			if (ze::$cVersion == ze::$adminVersion
			 && ze\priv::check('_PRIV_EDIT_DRAFT', ze::$cID, ze::$cType)) {
				
				if (!$this->empty) {
					$controls['actions']['cut_contents']['hidden'] = false;
					$controls['actions']['cut_contents']['onclick'] =
						str_replace('list,of,allowed,modules', 'zenario_html_snippet,zenario_twig_snippet,zenario_wysiwyg_editor',
							$controls['actions']['cut_contents']['onclick']);
				}
			
				//If there is no contents here and something was copied, show the paste option
				if ($this->empty && $copied) {
					$controls['actions']['paste_contents']['hidden'] = false;
				}
			
				//If there is contents here and something was copied, show the swap and overwrite options
				if (!$this->empty && $copied) {
					$controls['actions']['overwrite_contents']['hidden'] = false;
					$controls['actions']['swap_contents']['hidden'] = false;
				}
			}
		}
	}

}
