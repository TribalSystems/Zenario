/**
 * A replacement for the Preview/Save Plugins that come with TinyMCE.
 * Allows TinyMCE to save to zenarioCMS.
 * Requires Zenario to be running, and a content editor to be on the page.
 * (This is a partial copy of the normal Save Plugin.)
 */

tinymce.PluginManager.add('zenario_save', function(editor) {
	
	function _zenario_save() {
		window.zenarioEditorSave(editor);
	}

	function _zenario_save_and_continue() {
		window.zenarioEditorSaveAndContinue(editor);
	}

	function _zenario_cancel() {
		window.zenarioEditorCancel(editor);
	}

	editor.addCommand('mcezenarioSave', _zenario_save);
	editor.addCommand('mcezenarioSaveAndContinue', _zenario_save_and_continue);
	editor.addCommand('mcezenarioCancel', _zenario_cancel);

	editor.addButton('save', {
		text: '',
		tooltip: 'Save changes and continue editing',
		cmd: 'mcezenarioSaveAndContinue'
	});

	editor.addButton('save_and_close', {
		icon: 'save',
		text: 'Save',
		tooltip: 'Save changes and close the editor',
		cmd: 'mcezenarioSave'
	});

	editor.addButton('cancel', {
		icon: false,
		text: 'Abandon',
		tooltip: 'Abandon unsaved changes and close the editor',
		cmd: 'mcezenarioCancel'
	});

	editor.addShortcut('ctrl+s', '', 'mcezenarioSaveAndContinue');
});