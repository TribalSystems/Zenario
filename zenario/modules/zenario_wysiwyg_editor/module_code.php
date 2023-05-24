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

// This plugin shows some static content
class zenario_wysiwyg_editor extends zenario_html_snippet {
	
	protected $editing = false;
	protected $editorId = '';
	
	public static function generateSummary($body) {
		$body = str_replace("\n", "~n", str_replace('~', '~s', $body));
	
		if ((preg_match('@(<p.*?)<h\d\b@', $body, $matches)) && ($summary = trim($matches[1]))) {
	
		} elseif ((preg_match('@(<p.*)@', $body, $matches)) && ($summary = trim($matches[1]))) {
	
		} else {
			$summary = '';
		}
		
		return str_replace('~s', '~', str_replace("~n", "\n", $summary));
	}
	
	protected function openEditor() {
		//$text = str_replace([" ", "\n", "\r", "\t", ""], '', strip_tags($this->setting('html')));
		$text = $this->setting('html');
		$contentItemSummary = ze\row::get('content_item_versions', 'content_summary', ['id' => $this->cID, 'type' => $this->cType, 'version' => $this->cVersion]);
		if ($contentItemSummary) {
			$summary = trim(strip_tags($contentItemSummary));
		} else {
			$summary = '';
		}

		$summaryMatches =
			$summary == trim(strip_tags($text))
		 || $summary == trim(strip_tags(zenario_wysiwyg_editor::generateSummary($text)));
		
		$this->callScript(
			'zenario_wysiwyg_editor', 'open', $this->containerId, $this->editorId,
			$this->summaryLocked($this->cID, $this->cType, $this->cVersion),
			!$summary,
			$summaryMatches);
	}
	
	protected function displayEditor() {
		//Display the content area in an editor, so the admin can change its contents
		echo
			'<form>
				<input type="hidden" id="', $this->containerId, '_save_link" value="', htmlspecialchars($this->showFloatingBoxLink()), '" />
				<div id="', $this->editorId, '" rows="4" style="width: 100%; visibility: hidden;" class="tinymce_content">',
					$this->setting('html'),
				'</div>
			</form>';
	}

	public function addToPageFoot() {
		
		//...your PHP code...//
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				
				//For version controlled WYIWYG Editors, only let someone edit the content inline on the page itself,
				//or in the metadata FAB.
				//Don't allow them to edit in the content in the plugin settings, even though it technically
				//is a plugin setting.
				if ($box['key']['isVersionControlled']) {
					$box['tabs']['first_tab']['hidden'] = 
					$fields['first_tab/html']['hidden'] = true;
					
					//Hidden plugin settings are usually deleted upon being saved,
					//so as a workaround to stop this from happening,
					//clear the flags that say the content is a plugin setting.
					unset($fields['first_tab/html']['plugin_setting']);
				
				} else {
					//Try and ensure that we use relative URLs where possible
					ze\contentAdm::stripAbsURLsFromAdminBoxField($box['tabs']['first_tab']['fields']['html']);
				}
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'plugin_settings':
				if (!$box['key']['isVersionControlled']) {
					//Try and ensure that we use relative URLs where possible
					ze\contentAdm::stripAbsURLsFromAdminBoxField($box['tabs']['first_tab']['fields']['html']);
				}
				
				break;
		}
	}
	
	
	protected function summaryLocked($cID, $cType, $cVersion) {
		return
			!ze\row::get('content_types', 'enable_summary_auto_update', $cType)
		 || ze\row::get('content_item_versions', 'lock_summary', ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
	}
	
	protected function syncSummary($cID, $cType, $cVersion, $html) {
		ze\row::set('content_item_versions', ['content_summary' => $html], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
	}
	
	
	//When the plugin is set up, also get the content item's status and the content section to display
	function init() {
		$canEdit = false;
		
		//Alow allow editing of inline content if this is a version controlled Plugin
		if ($this->isVersionControlled) {
			
			//Handle submitting revisions by AJAX
			if (ze::post('_zenario_save_content_')) {
				$this->saveContent();
				exit;
			}
			
			$canEdit = !empty($_SESSION['admin_userid']) && ze\priv::check('_PRIV_EDIT_DRAFT', ze::$cID, ze::$cType);
			
			//Open the editor if it has been requested, and the current Admin has permissions
			if ($canEdit && ze::$isDraft) {
				
				$this->editorId = $this->containerId. '_tinymce_content_'. str_replace('.', '', microtime(true));
				
				//Open the editor immediately if it is in the URL
				if (ze::request('content__edit_container') == $this->containerId) {
					$this->editing = true;
					$this->markSlotAsBeingEdited();
					$this->openEditor();
				}
			}
			
			// Enable double click access to editor
			if ($canEdit) {
				$buttonSelector = '#zenario_slot_control__'.$this->slotName.'__actions__'.$this->moduleClassName.'__edit_inline';
				$this->callScript('zenario_wysiwyg_editor', 'listenForDoubleClick', $this->slotName, $this->containerId, $buttonSelector);
			}
		}
		
		return zenario_html_snippet::init() || $canEdit;
	}
	
	public function showLayoutPreview() {
		if (!$this->shouldShowLayoutPreview()) {
			$this->showSlot();
		} else {
			echo "\n\t", '<div class="zenario_wysiwyg">', "\n";
				echo ze\admin::phrase('<h2>WYSIWYG editor</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>');
			echo "\n\t", '</div>';
		}
	}
	
	
	function showSlot() {
		if (ze::$isTwig) return;
		
		echo "\n\t", '<div class="zenario_wysiwyg">', "\n";
			$this->showContent();
		echo "\n\t", '</div>';
	}
	
	function showContent() {
		
		if (!$this->editing && ze\priv::check() && !trim($this->setting('html'))) {
			if (ze::$isDraft) {
				echo
					'<div class="zenario_editor_placeholder_text only_show_in_edit_mode">',
						ze\admin::phrase('<h2>Write something here</h2><p>This is a WYSIWYG editor, but it\'s empty.</p><p>Double-click in this panel to edit.</p>', ['slotName' => $this->slotName]),
					'</div>';
			} else {
				echo
					'<div class="zenario_editor_placeholder_text only_show_in_edit_mode">',
						ze\admin::phrase('<h2>Write something here</h2><p>This is a WYSIWYG editor, but it\'s empty.</p><p>With the Edit tab selected, click &quot;Start editing&quot; to edit.</p>', ['slotName' => $this->slotName]),
					'</div>';
			}
		}
		
		//In visitor mode? Admin doesn't have permissions to edit? Just display the content and then exit.
		//Also don't let Content be Edited if it looks like another editor is open, or if this is not a Wireframe Plugin
		if (!ze::$isDraft
		 || !ze\priv::check('_PRIV_EDIT_DRAFT', ze::$cID, ze::$cType)
		 || !$this->isVersionControlled
		 || (!$this->editing && ze::request('content__edit_container'))) {
			echo $this->setting('html');
			return;
		}
		
		
		if (!$this->editing) {
		 	if (isset($_POST['_zenario_save_content_'])) {
				if ($_POST['content__content']) {
					$this->frameworkOutputted = true;
				}
				
				echo $_POST['content__content'];
			
			} else {
				echo $this->setting('html');
			}
		
		} else {
			$this->displayEditor();
		}
	}
		
		
	
	protected function editInlineButton() {
		//If this is on a draft, then it only needs to reload the Plugin.
		//However if not, then it needs to click the "Create a Draft" button on the admin toolbar, reload the page then click this button again.
		return '
			href="#"
			id="'. $this->containerId. '_edit_inline"
			onclick="'. $this->editInlineButtonOnClick(). '"';
	}
	
	protected function editInlineButtonOnClick() {
		return 'if (zenarioA.checkForEdits() && zenarioA.draft(this.id)) { '. $this->refreshPluginSlotJS('&no_cache=1&content__edit_container='. $this->containerId, false). ' } return false;';
	}
	
	public function fillAdminSlotControls(&$controls) {
	 	//Add an "Edit Inline" option for Wireframe HTML areas
		if ($this->isVersionControlled
		 && ze::$cVersion == ze::$adminVersion
		 && (ze::$isDraft || ze::$status == 'published')
		 && ze\priv::check('_PRIV_EDIT_DRAFT', ze::$cID, ze::$cType)) {
			if (!$this->editing) {
				$controls['actions']['zenario_wysiwyg_editor__edit_inline'] = [
					'ord' => 0,
					'page_modes' => ['edit' => true],
					'label' => ze\admin::phrase('Edit content inline'),
					'onclick' => htmlspecialchars_decode($this->editInlineButtonOnClick())];
				
				//To avoid confusion, you sholudn't be able to edit the contents of a Wireframe WYSIWYG Editor inline AND in the settings box
				if (isset($controls['actions']['settings'])) {
					$controls['actions']['settings']['hidden'] = true;
				}
			
			//You also shouldn't be able to access any of the slot controls whilst the WYSIWYG Editor is open!
			} else {
				$controls['actions'] = [];
			}
		}
	}
	
	protected function saveContent() {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}

}
