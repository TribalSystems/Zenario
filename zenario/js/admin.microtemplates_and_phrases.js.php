<?php
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

header('Content-Type: text/javascript; charset=UTF-8');
require '../basicheader.inc.php';

ze\cache::useBrowserCache('zenario-inc-admin-microtemplates-phrases-'. LATEST_REVISION_NO);

if (ze::$canCache) require CMS_ROOT. 'zenario/includes/bundle.pre_load.inc.php';


$output = '';


//Get a few phrases used in admin mode
foreach([

	'_BYTES' => ' B',
	'_GBYTES' => ' GB',
	'_KBYTES' => ' KB',
	'_MBYTES' => ' MB',
	'_TBYTES' => ' TB',
	'abandonChanges' => 'Abandon changes',
	'abandonChangesConfirm' => 'Are you sure you wish to abandon the changes you have made to this field?',
	'aboutzenario' => 'About Zenario',
	'addToNest' => 'Add to nest',
	'after' => 'On or after:',
	'am' => 'AM',
	'applyChanges' => 'Apply changes',
	'atMax' => 'at max',
	'before' => 'On or before:',
	'branchNum' => 'Branch #',
	'cancel' => 'Cancel',
	'changed' => 'Changed',
	'changesSaved' => 'Your changes have been saved!',
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
	'column' => 'column',
	'columns' => 'columns',
	'compressed' => 'Compressed',
	'contentSaved' => 'Content saved',
	'continueAnyway' => 'Continue',
	'copy' => 'Copy',
	'copied' => 'Copied to clipboard',
	'copiedCommand' => 'Commands and file paths copied to the clipboard. You should now paste this into your terminal window on your local machine.',
	'copiedCommandCD' => 'Commands and file paths copied to the clipboard. In your terminal window, <code>cd</code> to your Zenario directory and then paste the copied code.',
	'core' => 'Core Features',
	'couldNotOpenBox' => 'This admin box could not be displayed because the "tabs" property is missing.',
	'createAnother' => 'Save & create another',
	'createdAnother' => 'Created &quot;[[name]]&quot;',
	'csv' => 'CSV',
	'cType' => 'Content type:',
	'deleteSearch' => 'Delete',
	'deleteSearchConfirm' => 'Are you sure you wish to delete the &quot;[[name]]&quot; advanced search?',
	'dimensions' => 'Dimensions (w × h):',
	'dockClose' => 'Close preview',
	'dockFull' => 'Show preview at the top, full page width',
	'dockTop' => 'Show preview at the top, slot width',
	'dockMobile' => 'Show mobile preview',
	'dockNoJS' => 'JavaScript disabled; image may not be visible.',
	'dockRight' => 'Show preview to the right, slot width',
	'dropboxDotDotDot' => 'Choose from Dropbox...',
	'dropToUpload' => 'Drop files here to upload',
	'edit' => 'Edit',
	'editorOpen' => 'You have a WYSIWYG editor open, please close this before continuing.',
	'empty' => 'Empty',
	'editorStripsTagsWarning' => "You can't use <code>&lt;iframe&gt;</code> or <code>&lt;script&gt;</code> tags in a WYSIWYG Editor; please use a Raw HTML Snippet plugin to embed HTML like this.",
	'error404' => 'Could not access a file on the server. Please check that you have uploaded all of the Zenario files to the server, and that you have no misconfigured rewrite rules in your Apache config or .htaccess file that might cause a 404 error.',
	'error404Dev' => 'Could not access a file on the server. Please check that you have uploaded all of the Zenario files to the server, and that you have no misconfigured rewrite rules in your Apache config or .htaccess file that might cause a 404 error.',
	'error500' => "Something on the server is incorrectly set up or misconfigured.",
	'error500Dev' => "Something on the server is incorrectly set up or misconfigured.\n\nNo error message was given, but most likely there is a syntax error in your code somewhere.\n\nFurther information may be available in the server's error log.",
	'errorOnForm' => 'Please check below for errors.',
	'errorTimedOut' => "There was no reply or a blank reply from the server.\n\nThis could be a temporary network problem, or could be a bug in the application.",
	'errorTimedOutDev' => "There was no reply or a blank reply from the server.\n\nThis could be a temporary network problem, or could be because your PHP code crashed or exited without giving an error message.",
	'fal' => 'False',
	'fileSaved' => 'File saved',
	'filterByCol' => 'Click here to search on this column',
	'filterByColStop' => 'Click here to stop searching on this column',
	'goToNextPage' => 'Go to next page',
	'goToPrevPage' => 'Go to previous page',
	'hideExport' => 'Hide CSV export options',
	'informationForModuleDevelopers' => 'Information for module developers:',
	'insertNest' => 'Insert nest',
	'insertPlugin' => 'Insert plugin',
	'insertSlideshow' => 'Insert slideshow',
	'invertFilter' => 'Invert filter',
	'id' => 'ID:',
	'is' => 'Is:',
	'isnt' => 'Is not:',
	'item' => 'Item',
	'items' => 'Items',
	'oneItem' => '1 item',
	'nItems' => '[[count]] items',
	'leaveAdminBoxWarning' => 'You are currently editing this floating admin box. If you leave now you will lose any unsaved changes.',
	'leavePageWarning' => 'You are currently editing this page. If you leave now you will lose any unsaved changes.',
	'like' => 'Like:',
	'loading' => 'Loading',
	'login' => 'Login',
	'logout' => 'Logout',
	
	'link_status__content_not_found' => 'Link is broken',
	'link_status__hidden' => 'Links to a content item that is hidden',
	'link_status__unpublished' => 'Links to a content item that is not published',
	'link_status__published_with_draft' => 'Links to a content item with a draft that is unpublished',
	'link_status__published_with_draft_401' => 'Links to a private content item with an unpublished draft (but you can access it as an administrator)',
	'link_status__published_with_draft_403' => 'Links to a private content item with an unpublished draft, with a higher level of access than your current extranet user account',
	'link_status__published_401' => 'Links to a private content item (but you can access it as an administrator)',
	'link_status__published_403' => 'Links to a private content item with a higher level of access than your current extranet user account (but you can access it as an administrator)',
	'link_status__unlisted_with_draft' => 'Links to an unlisted content item with a draft that is unpublished',
	'link_status__unlisted_with_draft_401' => 'Links to an unlisted private content item with an unpublished draft (but you can access it as an administrator)',
	'link_status__unlisted_with_draft_403' => 'Links to an unlisted private content item with an unpublished draft, with a higher level of access than your current extranet user account',
	'link_status__unlisted_401' => 'Links to an unlisted private content item (but you can access it as an administrator)',
	'link_status__unlisted_403' => 'Links to an unlisted private content item with a higher level of access than your current extranet user account (but you can access it as an administrator)',
	'link_status__spare_alias' => 'This link points to a spare alias; you probably should change this to the real alias',
	
	'menuFeatureImage' => "Menu node's promotional image",
	'menuImage' => "Menu node's thumbnail image",
	'menuRolloverImage' => "Menu node's rollover image",
	
	'missingId' => 'Missing ID',
	'missingSlots' => 'This content item has plugins in slots that aren\'t supported by layout [[layout]]. Click the Edit tab, check the plugins in the "Missing Slots" section the bottom of the page, and either remove them or move them to slots that exist.',
	'mode' => 'Mode',
	'module' => 'Module',
	'moreActions' => 'More actions',
	'moreActionsTooltip' => 'Click for more actions',
	'moveColBack' => 'Move column back',
	'moveColForward' => 'Move column forward',
	'movePlugin' => 'Move plugin',
	'movePluginDesc' => "Click on the \"target\" icon in the slot to which you want to move this plugin.\n\nIf the slot is empty the plugin will be moved; if it’s populated with another plugin, the plugins will be swapped.",
	'next' => 'Next',
	'no' => 'No',
	'noItems' => 'There is nothing to display in this view.',
	'noItemsInSearch' => 'There is nothing that matches your search.',
	'noSlots' => 'This layout has no slots, please add slots using Gridmaker.',
	'nothing_selected' => 'Nothing selected',
	'notChanged' => 'Not changed',
	'notCompressed' => 'Not compressed',
	'notLike' => 'Not like:',
	'notOnAdminDomain' => 'The admin domain for this site ([[admin_domain]]) does not match the domain you are currently on ([[current_domain]]).',
	'notOnPrimaryDomain' => 'The primary domain for this site ([[primary_domain]]) does not match the domain you are currently on ([[current_domain]]).',
	'OK' => 'OK',
	'outOfGrid' => 'Outside of the grid layout',
	'overwriteContentsConfirm' => 'Are you sure you wish to paste? This will overwrite the contents here.',
	'pluginNeedsReload' => 'This plugin wants to <a href="[[href]]">reload the page</a> and may not display correctly until you do so.',
	'pm' => 'PM',
	'prev' => 'Prev',
	'preview' => 'Preview',
	'previewFullPage' => 'Show preview at full page width',
	'previewFullWidth' => 'Show preview at full width',
	'previewOnPage' => 'Show preview on page',
	'publish' => 'Publish immediately',
	'refined' => ' (filtered)',
	'readonly' => 'Read-only',
	'remove' => 'Remove',
	'reset' => 'Reset to default view',
	'retry' => 'Retry request',
	'revert' => 'Revert',
	'revertConfirm' => '<p>Are you sure you wish to abandon any changes made to this plugin since the previous version?</p><p>Only settings/content in this slot will be affected.</p>',
	'save' => 'Save',
	'saveAndClose' => 'Save & close',
	'saveAndNext' => 'Save & next',
	'savedButNotShown' => 'Item saved, but your filter prevents it from appearing',
	'saveDontSyncSummary' => 'No, just save here',
	'saveDontUpdateSummary' => 'No, just save here',
	'saveSyncSummary' => 'Yes, save and update Summary',
	'saveSyncSummaryPrompt' => 'This content item does not have a Summary. Do you wish to start syncing the Summary with the text you have entered here?',
	'saveUpdateSummary' => 'Yes, save and update Summary',
	'saveUpdateSummaryPrompt' => 'The text you are editing is synced with this content item\'s Summary. Do you wish to continue to update the Summary with the changes made here?',
	'saving' => 'Saving',
	'selectAll' => 'Multi-select is available.<br/>Click to select all visible items in this panel.',
	'selectDotDotDot' => 'Select...',
	'selected' => 'selected',
	'selectListSelect' => ' -- Select -- ',
	'serverSide' => 'Server-side',
	'serverTime' => 'Server time ',
	'show' => 'Show',
	'showCol' => 'Click here to show or hide this column',
	'showExport' => 'Show CSV export options...',
	'siteSettingProtected' => 'This setting is protected during a site reset',
	'size' => 'Size:',
	'skin' => 'Skin',
	'skipToNext' => 'Skip to next',
	'sort' => 'Sort',
	'sortByCol' => 'Click here to sort by this column',
	'swapContentsConfirm' => 'Are you sure you wish to paste? The contents you previously copied will appear here, and the contents that were here will be copied.',
	'test' => 'Test',
	'translatedField' => 'Uses phrases',
	'tru' => 'True',
	'undoChanges' => 'Undo changes',
	'updatePreview' => 'Update preview',
	'upload' => 'Upload',
	'uploadDotDotDot' => 'Upload...',
	'uploadTooLarge' => 'This file is too large, and exceeds the file size limit of [[maxUploadF]]. (This limit is determined in site settings, "Documents, images and file handling".)',
	'usedOn' => 'Used on:',
	'versionControlled' => 'version controlled',
	'viewModuleFolder' => 'View module swatch folder in Organizer',
	'viewSkinFolder' => 'View skin folder in Organizer',
	'viewTrash' => 'View&nbsp;Trash',
	'wwttPlaceholder' => 'Find services...',
	'yes' => 'Yes',
	
	'skLoading' => 'Loading...',
	'skViewFrontend' => 'View content item in front-end',
	'skViewBox' => 'View content item in floating box',
	'skGoToContentItems' => 'Go to content items in Organizer',
	'skQuickSearch' => 'Search this panel',
	'skSearch' => 'Search',
	'skAdjustView' => 'Adjust view',
	'skRefreshView' => 'Refresh view',
	'skListView' => 'List View',
	'skSummaryView' => 'Summary view',
	'skGridView' => 'Preview',
	'skBackTo' => 'Back to ',
	'skOf' => ' of ',
	
	'debugHelpMode' => <<<_help
		<p>Depending on how Organizer is currently being accessed by the Admin, it can operate in a different "mode". Organizer has six different modes, and each mode has a lowercase codename.</p>
		<p>You can check the current mode using the <code>$</code><code>mode</code> parameter of your <code>fillStorekeeper()</code> and <code>lineStorekeeper()</code> methods, or the <code>[[ORGANIZER_MODE]]</code> constant:</p>
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
	
	
	//Phrases specifically used by Gridmaker
	'gridAdd' => 'Add...',
	'growlSlotAdded' => 'A slot has been added, use the resize handle in the lower right corner to change its size',
	'growlSpaceAdded' => 'Whitespace has been added; drag the bottom-right resize tool to change its width',	
	'growlChildrenAdded' => 'Slots have been added; drag the bottom-right resize tool to change their width',	
	'growlGridBreakAdded' => 'A gridbreak has been added',	
	'growlGridBreakWithSlotAdded' => 'A gridbreak + slot have been added',
	
	'growlSlotDeleted' => 'The slot has been deleted',
	'growlSpaceDeleted' => 'The whitespace has been deleted',
	'growlGridBreakDeleted' => 'The gridbreak has been deleted',
	'growlGridBreakWithSlotDeleted' => 'The gridbreak + slot have been deleted',
	'growlSlotAndGroupingDeleted' => 'The slot and its grouping have been deleted',
	'growlSpaceAndGroupingDeleted' => 'The whitespace and its grouping have been deleted',
	
	'growlSlotMoved' => 'The slot has been moved',
	'growlSpaceMoved' => 'The whitespace has been moved',
	'growlGridBreakMoved' => 'The gridbreak has been moved',
	'growlGridBreakWithSlotMoved' => 'The gridbreak + slot have been moved',
	'growlGroupingMoved' => 'The grouping has been moved',
	
	'gridAddChildren' => 'Multiple slots in a grouping',
	'gridAddGridBreak' => 'Gridbreak',
	'gridAddGridBreakWithSlot' => 'Gridbreak + slot',
	'gridAddSlot' => 'Slot',//Changed "Add a slot" to "Slot"
	'gridAddSpace' => 'Whitespace',//Changed "Add whitespace" to "Whitespace"
	'gridConfirmClose' => 'You have unsaved changes. Are you sure you wish to close and abandon these changes?',
	'gridContentWidth' => 'Content width:',
	'gridContentWidthTooltip' => 'Content width|This is the largest possible thing you could place in the grid',
	'gridCSSClass' => 'Additional class name(s):',
	'gridDelete' => 'Delete slot',
	'gridDesktop' => 'Desktop',
	'gridDisplayingAt' => 'Displaying at [[pixels]] pixels wide',
	'gridDotTplDotPHP' => '.tpl.php',
	'gridEditPlaceholder' => '<em>[Edit]</em>',
	'gridEditProperties' => 'Edit properties',
	'gridEmptySpace' => 'Empty Space|Drag to move or drag the corder to resize',
	'gridErrorNameFormat' => 'The slot class name may only contain the letters a-z, digits or underscores',
	'gridErrorNameIncomplete' => 'Please enter a name for this slot.',
	'gridErrorNameInUseFooter' => 'This slot name is already in use in the site-wide footer.',
	'gridErrorNameInUseHeader' => 'This slot name is already in use in the site-wide header.',
	'gridErrorNameInUseLayout' => 'This slot name is already in use in a layout.',
	'gridErrorNameInUseThisLayout' => 'This slot name is already in use on this layout.',
	'gridExportToFSDisabled' => "Saving to the server's file system is disabled because you don't have the required administrator permission.",
	'gridGridBreak' => 'Gridbreak|Drag to move',
	'gridGridBreakWithSlot' => 'Gridbreak + slot|Drag to move',
	'gridGridCSSClass' => 'CSS class name(s) for the following container:',
	'gridHtml' => 'Custom HTML:',
	'gridIncNestedRules' => 'CSS rules for nested cells:',
	'gridIncNestedRulesTooltip' => "Include CSS rules for cells with nested cells|Cells with nested cells need additional CSS rules. If you're not using nested cells, you can omit these rules for a smaller download.",
	'gridLayoutName' => 'Layout name:',
	'gridMirror' => 'Right-to-left',
	'gridMobile' => 'Mobile',
	'gridNewSkinMessage' => 'You have created a new Grid Skin. Before you can see this Skin on a page, you must edit the settings of a Layout and select it. All content items using that Layout will then use your new Skin.',
	'gridPlusAdd' => '+ Add',
	'gridSaveProperties' => 'Save',
	'gridSlotHtmlBefore' => 'HTML before slot:',
	'gridSlotHtmlAfter' => 'HTML after slot:',
	'gridSlotName' => 'Name (which is also its CSS class name):',
	'gridResizeNestedCells' => 'Resize the boundary for these nested cells',
	'gridResizeSlot' => 'Resize slot',
	'gridResp_always' => 'Show on desktop and mobile',
	'gridResp_first' => 'Move to start of row on mobile',
	'gridResp_hide' => 'Show on desktop only',
	'gridResp_only' => 'Show on mobile only',
	'gridResp_slot_always' => 'Show the slot on desktop and mobile',
	'gridResp_slot_hide' => 'Show the slot on desktop only',
	'gridResp_slot_only' => 'Show the slot on mobile only',
	'gridSave' => 'Save',
	'gridSaveAs' => 'Save a copy',
	'gridSaveCSS' => 'Save your CSS (grid)',
	'gridSaveTemplateFile' => 'Save your layout',
	'gridSaveText' => 'Enter a name for your new layout.',
	'gridWarningSaveWithoutSlots' => 'Your layout doesn\'t have any slots. You probably only want to save a layout without slots if you are going to edit the template and CSS files manually on the file system. Are you sure you wish to proceed?',
	'gridSlot' => 'Slot|Drag to move or drag the corner to resize',
	'gridTablet' => 'Tablet',
	'gridTemplateFileName' => 'Template filename:',
	'gridTitle' => 'Editing [[layoutName]] with Gridmaker',
	'password_score_4_matches_requirements' => 'Password matches the requirements (score 4, max)',
	'password_score_4_exceeds_requirements' => 'Password is very strong and exceeds requirements (score 4, max)',
	'password_score_3_matches_requirements' => 'Password matches the requirements (score 3)',
	'password_score_3_too_easy_to_guess' => 'Password is too easy to guess (score 3)',
	'password_score_2_matches_requirements_but_easy_to_guess' => 'Password is easy to guess. Make your password stronger if this will be a production site.',
	'password_score_2_too_easy_to_guess' => 'Password is too easy to guess (score 2)',
	'password_score_1_too_easy_to_guess' => 'Password is too easy to guess (score 1)',
	'password_score_0_too_easy_to_guess' => 'Password is too easy to guess (score 0)',
	'password_does_not_match_the_requirements' => 'Password does not match the requirements',
	'enter_password' => 'Please enter a password'

] as $code => $phrase) {
	$output .= ze\cache::esctick($code). '~'. ze\cache::esctick($phrase). '~';
}

echo 'zenario._mkd(zenarioA.phrase,', json_encode($output), ');';
	//N.b. zenario._mkd() is the short-name for zenario.unpackAndMerge()
	//(For shorter lists than this, consider using callScript() and calling the zenario.readyPhrasesOnBrowser() function)



echo ze\bundle::outputMicrotemplates(ze::moduleDirs('admin_microtemplates/'), 'zenarioT.microTemplates={}');


if (ze::$canCache) require CMS_ROOT. 'zenario/includes/bundle.post_display.inc.php';
