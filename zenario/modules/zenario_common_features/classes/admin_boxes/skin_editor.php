<?php
/*
 * Copyright (c) 2023, Tribal Limited
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
ze\module::incSubclass('zenario_common_features', 'admin_boxes', 'plugin_settings');

class zenario_common_features__admin_boxes__skin_editor extends zenario_common_features__admin_boxes__plugin_settings {
	
	protected $filesFirstHalf = [
		'0.reset.css' => "Enter CSS here to reset the browser's default styles.",
		'1.colorbox.css' => "This should contain styles for the Colorbox library.",
		'1.fonts.css' => 'This should contain styles for fonts.',
		'1.forms.css' => 'This should contain styles for form elements, e.g. <code>&lt;input&gt;s<code>, <code>&lt;select&gt;s<code> and <code>&lt;textareas&gt;s<code>.',
		'1.jquery_ui.css' => "This should contain styles for the JQuery library.",
		'1.layout.css' => 'This should contain styles that relate to the layout of the page and slots.'
	];

	protected $filesSecondHalf = [
		'3.misc.css' => "This should contain styles for anything that doesn't fit in another category, e.g. pagination.",
		'3.misc_zfea.css' => "This should contain styles for FEA plugins",
		'4.responsive.css' => 'This should contain rules for mobile devices.',
		'print.css' => 'This should contain rules for printing.'
	];
	
	protected function addSlide(&$box, &$fields, &$values, $file, $desc) {
		
		if (isset($box['tabs'][$file])) {
			return;
		}
		
		$filepath = $this->skinWritableDir. $file;
		
		$box['tabs'][$file] = json_decode(json_encode($box['tabs']['template']), true);
		
		$box['tabs'][$file]['hidden'] = false;
		$box['tabs'][$file]['label'] = $file;
		$box['tabs'][$file]['ord'] = ++$box['key']['newTabOrd'];
		$box['tabs'][$file]['custom__filepath'] = $filepath;
		$box['tabs'][$file]['custom__description'] = ze\admin::phrase($desc);
		
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
		
		if (!ze\priv::check('_PRIV_EDIT_CSS') || !ze\row::get('skins', 'enable_editable_css', $box['key']['skinId'])) {
			unset($box['tabs'][$file]['edit_mode']);
		}
		
		if (empty($box['tabs'][$file]['edit_mode']['enabled'])) {
			$box['tabs'][$file]['label'] .= ' '. ze\admin::phrase('(read-only)');
		} else {
			$box['tabs'][$file]['fields']['css_filename']['value'] = $file;
		}
		
		$fields = [];
		$values = [];
		$changes = [];
		ze\tuix::readValues($box, $fields, $values, $changes, $filling = true, $resetErrors = false);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//I'm calling this function to initialise some variables in the extended class
		$this->getPluginCSSFilepath($box, false);
		
		if (!$box['key']['skinId'] || !($skin = ze\row::get('skins', ['name', 'display_name'], $box['key']['skinId']))) {
			echo ze\admin::phrase('Skin not found!');
			exit;
		}
		
		
		$box['title'] = ze\admin::phrase('Editing the skin "[[display_name]]"', $skin['display_name']);
		
		$content = ze\row::get('content_items', true, ['id' => $box['key']['cID'], 'type' => $box['key']['cType']]);
		$mrg = [
			'version' => $box['key']['cVersion'],
			'versionStatus' => ze\contentAdm::versionStatus($box['key']['cVersion'], $content['visitor_version'], $content['admin_version'], $content['status'])
		];
		$box['custom__update_preview_message'] = ze\admin::phrase('Update preview (v[[version]] [[versionStatus]])', $mrg);
		
		
		$files = self::getFilesArray($skin);
		//Add a tab for each editable file.
		foreach ($files as $file => &$desc) {
			$this->addSlide($box, $fields, $values, $file, $desc);
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
		if ($box['key']['skinId'] && ze\priv::check('_PRIV_EDIT_CSS') && ($skin = ze\row::get('skins', ['name', 'enable_editable_css'], $box['key']['skinId']))) {
			$files = self::getFilesArray($skin);

			foreach ($files as $file => &$desc) {
				$filepath = CMS_ROOT. $this->skinWritableDir. $file;
				
				if (file_exists($filepath)) {
					if (is_writable($filepath)) {
						file_put_contents($filepath, $values[$file. '/css_source']);
					}
				} else {
					if (is_writable(CMS_ROOT. $this->skinWritableDir)) {
						file_put_contents($filepath, $values[$file. '/css_source']);
						\ze\cache::chmod($filepath, 0666);
					}
				}
			}
			
			ze\skinAdm::checkForChangesInFiles($runInProductionMode = true, $forceScan = true);
		}
	}

	public function getFilesArray($skin) {
		if (!$skin) {
			return [];
		}

		$files = [];

		//Add a tab for each editable file (names starting with 0-1)...
		foreach ($this->filesFirstHalf as $file => $desc) {
			$files[$file] = $desc;
		}

		//... then add skin editable CSS files (names starting with 2)...
		$editableCssFiles = [];
		$skinPath = CMS_ROOT . ze\content::skinPath($skin['name']) . 'editable_css/';
		if ($handle = opendir($skinPath)) {
			while (($entry = readdir($handle)) !== false) {
				if ($entry != "." && $entry != ".." && strpos($entry, '2.') === 0) {
					$editableCssFiles[] = $entry;
				}
			}
		
			closedir($handle);

			asort($editableCssFiles);
		}

		if (!empty($editableCssFiles)) {
			$desc = 'Editable CSS file used by the skin.';
			
			foreach ($editableCssFiles as $file) {
				$files[$file] = $desc;
			}
		}

		//... and finally add the rest (names starting with 3+).
		foreach ($this->filesSecondHalf as $file => $desc) {
			$files[$file] = $desc;
		}

		return $files;
	}
	
	protected function getPluginCSSName(&$box, $thisPlugin) {
		return 'dummy';
	}
}