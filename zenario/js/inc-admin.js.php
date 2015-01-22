<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

header('Content-Type: text/javascript; charset=UTF-8');
require '../cacheheader.inc.php';

useCache('zenario-inc-admin-js-'. LATEST_REVISION_NO);
useGZIP(!empty($_GET['gz']));


require CMS_ROOT. 'zenario/includes/cms.inc.php';

function incJS($file) {
	if (file_exists($file. '.pack.js')) {
		require $file. '.pack.js';
	} elseif (file_exists($file. '.min.js')) {
		require $file. '.min.js';
	} elseif (file_exists($file. '.js')) {
		require $file. '.js';
	}
	
	echo "\n/**/\n";
}


//Include a function that uses eval, and shouldn't be minified
echo '
	zenarioA.eval = function(c, tuixObject, item, id) {
		var ev;
		c += "";
		
		if (c.search(/^\s*function/) === 0) {
			ev = eval("(" + c + ")");
		} else {
			ev = eval(c);
		}
		
		if (typeof ev == "function") {
			ev = ev(tuixObject, item, id);
		}
		
		return zenario.engToBoolean(ev);
	};
';


//Get a few phrases used in admin mode
$phrases = array();
foreach(array(

	'_BYTES' => ' B',
	'_GBYTES' => ' GB',
	'_KBYTES' => ' KB',
	'_MBYTES' => ' MB',
	'_TBYTES' => ' TB',
	'abandonChanges' => 'Abandon changes',
	'abandonChangesConfirm' => 'Are you sure you wish to abandon the changes you have made to this field?',
	'aboutzenario' => 'About Zenario',
	'addToNest' => 'Add to nest',
	'advancedSearchClear' => 'Clear advanced search',
	'advancedSearches' => 'Your advanced searches',
	'advancedSearchNotOn' => 'No advanced rearch running|Click the down-arrow to create a new search.',
	'after' => 'On or after:',
	'am' => 'AM',
	'applyChanges' => 'Apply changes',
	'atCurrentSize' => 'at current window size',
	'before' => 'On or before:',
	'branchNum' => 'Branch #',
	'cancel' => 'Cancel',
	'changed' => 'Changed',
	'choose' => 'Choose',
	'clear' => 'Clear',
	'clkToViewLinkedCItem' => 'Click to see the linked content item',
	'clkToViewLinkedMenuNode' => 'Click to see the linked menu node',
	'clkToViewLinkInNewWindow' => 'Click to open this URL in a new window',
	'clientSide' => 'Client Side',
	'close' => 'Close',
	'closeEditorWarning' => 'Are you sure you wish to close the editor? You will lose any unsaved changes.',
	'colDisplaySort' => 'Adjust your view of this panel',
	'colon' => ': ',
	'compressed' => 'Compressed',
	'contentSaved' => 'Content saved',
	'copy' => 'Copy',
	'core' => 'Core Features',
	'couldNotOpenBox' => 'Sorry, this admin box does not exist, or you do not have the permissions needed to access it.',
	'createSearch' => 'Create a search',
	'csv' => 'CSV',
	'deleteSearch' => 'Delete',
	'deleteSearchConfirm' => 'Are you sure you wish to delete the &quot;[[name]]&quot; advanced search?',
	'dropboxDotDotDot' => 'Choose from Dropbox...',
	'dropToUpload' => 'Drop files here to upload',
	'edit' => 'Edit',
	'editorOpen' => 'You currently have an editor open, please close this before continuing.',
	'error404' => 'Could not access a file on the server. Please check that you have uploaded all of the CMS files to the server, and that you have no misconfigured rewrite rules in your Apache config or .htaccess file that might cause a 404 error.',
	'error404Dev' => 'Could not access a file on the server. Please check that you have uploaded all of the CMS files to the server, and that you have no misconfigured rewrite rules in your Apache config or .htaccess file that might cause a 404 error.',
	'error500' => "Something on the server is incorrectly set up or misconfigured.",
	'error500Dev' => "Something on the server is incorrectly set up or misconfigured.\n\nNo error message was given, but most likely there is a syntax error in your code somewhere.\n\nFurther information may be available in the server's error log.",
	'errorTimedOut' => "There was no reply or a blank reply from the server.\n\nThis could be a temporary network problem, or could be a bug in the application.",
	'errorTimedOutDev' => "There was no reply or a blank reply from the server.\n\nThis could be a temporary network problem, or could be because your php code crashed or exited without giving an error message.",
	'fal' => 'False',
	'fileSaved' => 'File saved',
	'filterByCol' => 'Click here to filter by this column',
	'filterByColStop' => 'Click here to stop filtering by this column',
	'goToNextPage' => 'Go to next page',
	'goToPrevPage' => 'Go to previous page',
	'hideExport' => 'Hide CSV export options',
	'informationForModuleDevelopers' => 'Information for module developers:',
	'insertReusablePlugin' => 'Insert plugin',
	'invertFilter' => 'Invert filter',
	'is' => 'Is:',
	'isnt' => 'Is not:',
	'item' => 'Item',
	'items' => 'Items',
	'leaveAdminBoxWarning' => 'You are currently editing this floating admin box. If you leave now you will lose any unsaved changes.',
	'leavePageWarning' => 'You are currently editing this page. If you leave now you will lose any unsaved changes.',
	'like' => 'Like:',
	'logIn' => 'Log In',
	'mode' => 'Mode',
	'module' => 'Module',
	'moreActions' => 'More actions',
	'moreActionsTooltip' => 'Click for more actions',
	'moveColBack' => 'Move column back',
	'moveColForward' => 'Move column forward',
	'movePlugin' => 'Move plugin',
	'movePluginDesc' => "Click on the \"target\" icon in the slot to which you want to move this plugin.\n\nIf the slot is empty the plugin will be moved; if it’s populated with another plugin, the plugins will be swapped.",
	'no' => 'No',
	'noItems' => 'There is nothing to display in this view.',
	'noItemsInSearch' => 'There is nothing that matches your search.',
	'nothing_selected' => 'Nothing selected',
	'notChanged' => 'Not changed',
	'notCompressed' => 'Not compressed',
	'notLike' => 'Not like:',
	'OK' => 'OK',
	'overwriteContentsConfirm' => 'Are you sure you wish to paste? This will overwrite the contents here.',
	'pluginNeedsReload' => 'This plugin wants to <a href="[[href]]">reload the page</a> and may not display correctly until you do so.',
	'pm' => 'PM',
	'publish' => 'Publish immediately',
	'refined' => ' (filtered)',
	'remove' => 'Remove',
	'reset' => 'Reset to default view',
	'revert' => 'Revert',
	'revertConfirm' => '<p>Are you sure you wish to abandon any changes made to this plugin since the previous version?</p><p>Only settings/content in this slot will be affected.</p>',
	'save' => 'Save',
	'saveDontSyncSummary' => 'No, just save here',
	'saveDontUpdateSummary' => 'No, just save here',
	'saveSyncSummary' => 'Yes, save and update Summary',
	'saveSyncSummaryPrompt' => 'This content item does not have a Summary. Do you wish to start syncing the Summary with the text you have entered here?',
	'saveUpdateSummary' => 'Yes, save and update Summary',
	'saveUpdateSummaryPrompt' => 'The text you are editing is synced with this content item\'s Summary. Do you wish to continue to update the Summary with the changes made here?',
	'selectDotDotDot' => 'Select...',
	'selectAll' => 'Multi-select is available.<br/>Click to select all visible items in this panel.',
	'selectListSelect' => ' -- Select -- ',
	'serverSide' => 'Server-side',
	'serverTime' => 'Server time ',
	'show' => 'Show',
	'showCol' => 'Click here to show or hide this column',
	'showExport' => 'Show CSV export options...',
	'skin' => 'Skin',
	'sort' => 'Sort',
	'sortByCol' => 'Click here to sort by this column',
	'swapContentsConfirm' => 'The contents you previously copied will appear here, and the contents that were here will be copied. Are you sure you wish to swap?',
	'tru' => 'True',
	'undoChanges' => 'Undo changes',
	'upload' => 'Upload',
	'uploadDotDotDot' => 'Upload...',
	'uploadTooLarge' => 'Your file is too large, please upload a file that is smaller than [[maxUploadF]].',
	'viewModuleFolder' => 'View module swatch folder in Organizer',
	'viewSkinFolder' => 'View skin folder in Organizer',
	'viewTrash' => 'View&nbsp;Trash',
	'yes' => 'Yes',
	
	'skLoading' => 'Loading...',
	'skViewFrontend' => 'View content item in front-end',
	'skViewBox' => 'View content item in floating box',
	'skGoToContentItems' => 'Go to content items in Organizer',
	'skQuickSearch' => 'Quick search',
	'skSearch' => 'Search',
	'skAdjustView' => 'Adjust view',
	'skAdvancedSearch' => 'Advanced search',
	'skRefreshView' => 'Refresh view',
	'skListView' => 'List View',
	'skSummaryView' => 'Summary view',
	'skGridView' => 'Grid view',
	'skBackTo' => 'Back to ',
	'skOf' => ' of ',
	
	'debugHelpMode' => <<<_help
		<p>Depending on how Organizer is currently being accessed by the Admin, it can operate in a different "mode". Organizer has six different modes, and each mode has a lowercase codename.</p>
		<p>You can check the current mode using the <code>$mode</code> parameter of your <code>fillStorekeeper()</code> and <code>lineStorekeeper()</code> methods, or the <code>[[ORGANIZER_MODE]]</code> constant:</p>
		<p><strong>full</strong></p>
		<p>This is the "normal" mode of operation; Organizer has been opened in its own browser window and is running full screen.</p>
		<p><strong>select</strong></p>
		<p>This is when the Admin is selecting something from Organizer; Organizer is inside an iframe which is covering the majority of the screen.</p>
		<p>The left-hand navigation is hidden, and navigation may be restricted to a certain area or panel. Depending on the Panel there may be some degree of control to edit or create items.</p>
		<p><strong>quick</strong></p>
		<p>This is when the Admin is editing something on a page using Organizer; Organizer is inside an iframe which is covering the bottom half of the screen, leaving what they are working on still visible at the top.</p>
		<p>The left-hand navigation is hidden, and navigation may be restricted to a certain area or panel. However there is full control to edit or create items.</p>
_help
	,
	'debugHelpTagPath' => <<<_help
		<p>The tag path to a panel is the direct path in the data to the panel - i.e. from the top of the <code>.yaml</code> file to the <code>panel:</code> definition.</p>
		<p>When you create a <code>link</code> to a panel, you will need to specify its tag path.</p>
		<p>When the CMS calls one of your module's methods (e.g. <code>fillOrganizerPanel()</code>) it will specify the tag path of the panel that is being accessed.</p>
_help
	,
	'debugHelpNavigationPath' => <<<_help
		<p>If an administrator clicks a link that uses a refiner, then their current location can no longer be specified using a direct tag path. Instead, a more complicated type of link called a navigation path will appear in the URL bar.</p>
		<p>Navigation paths also work by listing the path taken, however the navigation path will go from the top of the <code>.yaml</code> file to the link that was clicked on.</p>
		<p>If the link was inside an <code>item_button</code> or an <code>inline_button</code>, and an item on the panel was selected, the id of the item will be included in the navigation path.</p>
		<p>As the administrator goes through multiple refiners, the tag path between each link and the id of each item will be added to the navigation path in turn.</p>
_help
	,
	'debugHelpRefiner' => <<<_help
		<p>Refiners modify a panel and change which items that are displayed.</p>
		<p>For example, if you view the "All content items" panel, by default it will show you every content item that isn't trashed.
			However if you to go "Content by language" and click on a language, you will only see content items that are in that language.</p>
		<p>In order to create a working refiner you will need to write some code in SQL and/or PHP.
			You can access your refiners using <code>request('refiner__my_refiner_name')</code> in PHP, and <code>[[REFINER__MY_REFINER_NAME]]</code> in SQL.</p>
_help
	,
	
	
	//Phrases used by the grid maker
	'gridAdd' => 'Add...',
	'growlSlotAdded' => 'A slot has been added',
	'growlSlotDeleted' => 'The slot has been deleted',
	'growlSlotMoved' => 'The slot has been moved',
	'growlSpaceAdded' => 'A space cell has been added',	
	'growlChildrenAdded' => 'Slots have been added',	
	'growlGridBreakAdded' => 'A grid break has been added',	
	'gridAddChildren' => 'Add multiple slots in a grouping',
	'gridAddGridBreak' => 'Add a grid break',
	'gridAddGridBreakWithSlot' => 'Add a slot outside the grid',
	'gridAddSlot' => 'Add a slot',
	'gridAddSpace' => 'Add whitespace',
	//Removed as no longer needed; fixed anf fluid grids now work the same way!
	//'gridChangeGridWarning' => '<p>You are changing a grid which already has slots. This may disrupt the placement of the slots.</p><p>If you proceed you should check and if need be adjust all of your slots, or undo.</p>',
	'gridCols' => 'Columns',
	'gridConfirmClose' => 'You have unsaved changes. Are you sure you wish to close and abandon these changes?',
	'gridContentWidth' => 'Content width:',
	'gridContentWidthTooltip' => 'Content width|This is the largest possible thing you could place in the grid',
	'gridCSSClass' => 'CSS class name(s):',
	'gridDelete' => 'Delete slot',
	'gridDesktop' => 'Desktop',
	'gridDisplayingAt' => 'Displaying at [[pixels]] pixels wide',
	'gridDotTplDotPHP' => '.tpl.php',
	'gridDownloadCSS' => 'CSS',
	'gridDownloadHTML' => 'tpl file',
	'gridDownloadImage' => 'png',
	'gridDownloadTitle' => 'Downloads <small>Save to your local disk</small>',
	'gridDownloadZip' => 'zip',
	'gridEditPlaceholder' => '<em>[Edit]</em>',
	'gridEditProperties' => 'Edit properties',
	'gridEditSlots' => 'Slot view',
	'gridEmptySpace' => 'Empty Space|Drag to move or drag the corder to resize',
	'gridErrorNameFormat' => 'The slot name may only contain the letters a-z, digits or underscores',
	'gridErrorNameIncomplete' => 'Please enter a name for this slot.',
	'gridErrorNameInUse' => 'This slot name is already in use',
	'gridExportToFSDisabled' => "Saving to the server's file system is disabled because you don't have the required administrator permission.",
	'gridFixed' => 'Fixed width',
	'gridFixedTooltip' => 'Fixed|In a fixed grid, all of the widths are specified in pixels.',
	'gridFluid' => 'Fluid',
	'gridFluidTooltip' => 'Fluid|In a fluid grid, all of the widths are specified in percentages, and the size of your columns and gutters will vary depending on the screen size. Fluid grids can be prone to pixel rounding errors, especially in Internet Explorer 6 and 7.',
	'gridFullWidth' => 'Full width',
	'gridGridBreak' => 'Grid break|Drag to move',
	'gridGridBreakWithSlot' => 'Slot outside the grid|Drag to move',
	'gridGridCSSClass' => 'CSS class name(s) for the next grid:',
	'gridGutter' => 'Gutter:',
	'gridGutterAndWidth' => 'Column / gutter:',
	'gridGutterLeftEdge' => 'L gutter:',
	'gridGutterRightEdge' => 'R gutter:',
	'gridHtml' => 'Custom HTML:',
	'gridIncNestedRules' => 'CSS rules for nested cells:',
	'gridIncNestedRulesTooltip' => "Include CSS rules for cells with nested cells|Cells with nested cells need additional CSS rules. If you're not using nested cells, you can omit these rules for a smaller download.",
	'gridMirror' => 'Create RTL version:',
	'gridMirrorTooltip' => 'Create right-to-left version|Check this option to generate a right-to-left version of your normal Skin, e.g. for the Arabic, Hebrew or Urdu languages. The resulting CSS will be adjusted so that the slots display right-to-left rather than left-to-right, and the <code>direction: rtl;</code> rule will also be added. The HTML generated remains the same. (This effect is not displayed when managing slots in the editor.)',
	'gridProperties' => 'Properties',
	'gridLayoutName' => 'Layout name:',
	'gridMaxWidth' => 'Max-width:',
	'gridMinWidth' => 'Min-width:',
	'gridMirror' => 'Create RTL version',
	'gridMirrorTooltip' => 'Create right-to-left version|Check this option to generate a right-to-left version of your normal Skin, e.g. for the Arabic, Hebrew or Urdu languages. The resulting CSS will be adjusted so that the slots display right-to-left rather than left-to-right, and the <code>direction: rtl;</code> rule will also be added. The HTML generated remains the same. (This effect is not displayed when managing slots in the editor.)',
	'gridMobile' => 'Mobile',
	'gridNewSkinMessage' => 'You have created a new Grid Skin. Before you can see this Skin on a page, you must edit the settings of a Layout and select it. All content items using that Layout will then use your new Skin.',
	'gridPreviewGrid' => 'Grid view',
	'gridRedo' => 'Redo',
	'gridSaveProperties' => 'Save properties',
	'gridSlotHtmlBefore' => 'HTML before slot:',
	'gridSlotHtmlAfter' => 'HTML after slot:',
	'gridSlotName' => 'Enter a unique slot name:',
	'gridResizeNestedCells' => 'Resize the boundary for these nested cells',
	'gridResizeSlot' => 'Resize slot',
	'gridResponsive' => 'Responsive',
	'gridResponsiveTooltip' => 'Responsive|Your grid has a minimum size. If your grid is not responsive, visitors with smaller screens than the minimum size will see scrollbars on your site. If your gris is responsive, it will turn itself off below the minimum size, and visitors with smaller screens than the minimum size will see your slots one after the other taking up all of the available space. (This effect is not displayed when managing slots in the editor.)',
	'gridResp_always' => 'Show normally',
	'gridResp_first' => 'Move to start of row on small screens',
	'gridResp_hide' => 'Hide on small screens',
	'gridResp_only' => 'Only show on small screens',
	'gridSave' => 'Save',
	'gridSaveAs' => 'Save as...',
	'gridSaveTemplate' => 'Save your layout',
	'gridSaveCSS' => 'Save your CSS (grid)',
	'gridSaveTemplateFile' => 'Save your layout',
	'gridSaveText' => 'Enter a name for your new layout.',
	'gridWarningSaveWithoutSlots' => 'Your layout doesn\'t have any slots. You probably only want to save a layout without slots if you are going to edit the template and CSS files manually on the file system. Are you sure you wish to proceed?',
	'gridSlot' => 'Slot|Drag to move or drag the corner to resize',
	'gridTablet' => 'Tablet',
	'gridTemplateFileName' => 'Template filename:',
	'gridUndo' => 'Undo'

) as $code => $phrase) {
	$phrases[$code] = adminPhrase($phrase);
}

echo '
	zenarioA.phrase = ', json_encode($phrases), ';';



//Include all of the standard JavaScript Admin libraries for the CMS
incJS('zenario/api/javascript_admin');
incJS('zenario/js/admin');
incJS('zenario/js/admin_box');

//Include Ace Editor extensions
incJS('zenario/libraries/bsd/ace/src-min-noconflict/ext-modelist');

//Include jQuery modules
//Include a small pre-loader library for TinyMCE (the full code is load-on-demand)
incJS('zenario/libraries/lgpl/tinymce_4_0_19b/jquery.tinymce');
//Include the selectboxes library for moving items between select lists
incJS('zenario/libraries/mit/jquery/jquery.selectboxes');
//Include the jQuery Slider code in Admin Mode
incJS('zenario/libraries/mit/jquery/jquery-ui.slider');
//Just testing - include the intro library
incJS('zenario/libraries/mit/intro/intro');
//Include the mousehold library
incJS('zenario/libraries/public_domain/mousehold/mousehold');

echo '
zenarioA.tinyMCEPath = "zenario/libraries/lgpl/tinymce_4_0_19b/tinymce.jquery.min.js"';


//Fix for the hasLayout bug and transparent slots in IE 6 and 7
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6') !== false) {
	echo '
$(document).ready(function () {
	$(".zenario_slotOuter > div").each(function (i, el) {
		el.style.zoom = 1;
	});
});';
}

if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7') !== false) {
	echo '
$(document).ready(function () {
	$(".zenario_slotOuter > div").each(function (i, el) {
		el.style.minHeight = 0;
	});
});';
}



//Include any templates (for underscore.js) from Module directories
foreach (moduleDirs('js/microtemplates/') as $dir) {
	foreach (scandir($dir = CMS_ROOT. $dir) as $file) {
		if (substr($file, 0, 1) != '.' && substr($file, -5) == '.html' && is_file($dir. $file)) {
			echo "\n\n", 'zenarioA.microTemplates[', json_encode(substr($file, 0, -5)), '] = ', json_encode(file_get_contents($dir. $file)), ';';
		}
	}
}
