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


//Note that this uses some of the same logic from the "Plugin Settings" FAB.
includeModuleSubclass('zenario_common_features', 'admin_boxes', 'plugin_settings');

class zenario_common_features__admin_boxes__skin_editor extends zenario_common_features__admin_boxes__plugin_settings {
	
	protected $files = array(
		'0.reset.css' => "Enter CSS here to reset the browser's default styles.",
		'1.fonts.css' => 'This should contain styles for fonts.',
		'1.forms.css' => 'This should contain styles for form elements, e.g. <code>&lt;input&gt;s<code>, <code>&lt;select&gt;s<code> and <code>&lt;textareas&gt;s<code>.',
		'1.layout.css' => 'This should contain styles that relate to the layout of the page and slots.',
		'3.misc.css' => "This should contain styles anything that doesn't fit in another category, e.g. pagination.",
		'4.responsive.css' => 'This should contain rules for mobile devices.',
		'print.css' => 'This should contain rules for printing.'
	);
	
	protected function addTab(&$box, &$fields, &$values, $file, $desc) {
		
		if (isset($box['tabs'][$file])) {
			return;
		}
		
		$filepath = $this->skinWritableDir. $file;
		
		$box['tabs'][$file] = json_decode(json_encode($box['tabs']['template']), true);
		
		$box['tabs'][$file]['hidden'] = false;
		$box['tabs'][$file]['label'] = $file;
		$box['tabs'][$file]['ord'] = ++$box['key']['newTabOrd'];
		$box['tabs'][$file]['tooltip'] =
			'<p><code>'. htmlspecialchars($filepath). '</code></p>'.
			'<p>'. adminPhrase($desc). '</p>';
		
		if (file_exists(CMS_ROOT. $filepath)) {
			if (is_readable(CMS_ROOT. $filepath)) {
				$box['tabs'][$file]['fields']['css_source']['value'] = file_get_contents(CMS_ROOT. $filepath);
				
				if (!is_writable(CMS_ROOT. $filepath)) {
					unset($box['tabs'][$file]['edit_mode']);
				}
			} else {
				unset($box['tabs'][$file]['edit_mode']);
			}
		} else {
			if (!is_writable(CMS_ROOT. $this->skinWritableDir)) {
				unset($box['tabs'][$file]['edit_mode']);
			}
		}
		
		if (!checkPriv('_PRIV_EDIT_CSS') || !getRow('skins', 'enable_editable_css', $box['key']['skinId'])) {
			unset($box['tabs'][$file]['edit_mode']);
		}
		
		if (empty($box['tabs'][$file]['edit_mode']['enabled'])) {
			$box['tabs'][$file]['label'] .= ' '. adminPhrase('(read-only)');
		} else {
			$box['tabs'][$file]['fields']['css_filename']['value'] = $file;
		}
		
		$fields = array();
		$values = array();
		$changes = array();
		readAdminBoxValues($box, $fields, $values, $changes, $filling = true, $resetErrors = false);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//I'm calling this function to initialise some variables in the extended class
		$this->getPluginCSSFilepath($box, false);
		
		if (!$box['key']['skinId'] || !($skin = getRow('skins', array('display_name'), $box['key']['skinId']))) {
			echo adminPhrase('Skin not found!');
			exit;
		}
		
		$box['title'] = adminPhrase('Editing the skin "[[display_name]]"', $skin);
		
		//Add a tab for each editable file
		foreach ($this->files as $file => &$desc) {
			$this->addTab($box, $fields, $values, $file, $desc);
		}
	}
	

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//I'm calling this function to initialise some variables in the extended class
		$this->getPluginCSSFilepath($box, false);
		
		//Save the CSS files, if they were there
		if ($box['key']['skinId'] && checkPriv('_PRIV_EDIT_CSS') && getRow('skins', 'enable_editable_css', $box['key']['skinId'])) {
			foreach ($this->files as $file => &$desc) {
				$filepath = CMS_ROOT. $this->skinWritableDir. $file;
				
				if (file_exists($filepath)) {
					if (is_writable($filepath)) {
						file_put_contents($filepath, $values[$file. '/css_source']);
					}
				} else {
					if (is_writable(CMS_ROOT. $this->skinWritableDir)) {
						file_put_contents($filepath, $values[$file. '/css_source']);
						@chmod($filepath, 0666);
					}
				}
			}
			
			checkForChangesInCssJsAndHtmlFiles($runInProductionMode = true, $forceScan = true);
		}
	}
	
	protected function getPluginCSSName(&$box, $thisPlugin) {
		return 'dummy';
	}
}