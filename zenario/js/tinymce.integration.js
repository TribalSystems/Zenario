
/*
 * Copyright (c) 2024, Tribal Limited
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



tinymce.PluginManager.add('zenario_save', function(editor) {
	
	editor.ui.registry.addButton('zenario_save_and_continue', {
		icon: 'checkmark',
		text: 'Save & continue',
		tooltip: "Save changes and continue editing",
		onSetup: function(buttonApi) {
			//TinyMCE lacks a way to add a specific CSS class name to our buttons,
			//so we have to use this very hacky work-around...
			$('button.tox-tbtn[title="Save changes and continue editing"]')
				.addClass('z_mce_integration_save z_mce_integration_save_and_continue');
		},
		onAction: function() {
			//editor.insertContent('Button was clicked!');
			
			window.zenarioEditorSaveAndContinue(editor);
			
		}
	});

	editor.ui.registry.addButton('zenario_save_and_close', {
		icon: 'checkmark',
		text: 'Save & exit',
		tooltip: "Save changes and close the editor",
		onSetup: function(buttonApi) {
			//TinyMCE lacks a way to add a specific CSS class name to our buttons,
			//so we have to use this very hacky work-around...
			$('button.tox-tbtn[title="Save changes and close the editor"]')
				.addClass('z_mce_integration_save z_mce_integration_save_and_close');
		},
		onAction: function() {
			//editor.insertContent('Button was clicked!');
			
			window.zenarioEditorSave(editor);
			
		}
	});

	editor.ui.registry.addButton('zenario_abandon', {
		classes: 'z_mce_integration_abandon',
		icon: 'close',
		text: 'Abandon',
		tooltip: "Abandon unsaved changes and close the editor",
		onSetup: function(buttonApi) {
			//TinyMCE lacks a way to add a specific CSS class name to our buttons,
			//so we have to use this very hacky work-around...
			$('button.tox-tbtn[title="Abandon unsaved changes and close the editor"]')
				.addClass('z_mce_integration_abandon');
		},
		onAction: function() {
			//editor.insertContent('Button was clicked!');
			
			window.zenarioEditorCancel(editor);
			
		}
	});
});
