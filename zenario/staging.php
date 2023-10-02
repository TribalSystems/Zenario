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


require 'visitorheader.inc.php';


//Disable caching
ze::$canCache = false;



if ($isAdmin = ze::isAdmin()) {
	ze\content::showStartSitePageIfNeeded('noStagingModeInAdminMode');
	exit;
}



//Try to find and load details on the content item version with this tagId and access code
$cID = $cType = $cVersion = false;

if (isset($_GET['code'])
 && !empty($_GET['id'])
 && (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $_GET['id']))
 && ($cVersion = ze\content::latestVersion($cID, $cType))
 && ($version = ze\sql::fetchAssoc("
		SELECT
			v.version,
			v.title, v.description, v.keywords,
			v.layout_id, v.css_class, v.feature_image_id,
			v.release_date, v.published_datetime, v.created_datetime,
			v.rss_slot_name, v.rss_nest
		FROM ". DB_PREFIX. "content_item_versions AS v
		WHERE v.id = ". (int) $cID. "
		  AND v.type = '". ze\escape::asciiInSQL($cType). "'
		  AND v.version = ". (int) $cVersion. "
		  AND v.access_code IS NOT NULL
		  AND v.access_code != ''
		  AND v.access_code = '". ze\escape::sql($_GET['code']). "'
		ORDER BY v.version DESC
		LIMIT 1"))
 && ($content = \ze\sql::fetchAssoc("
		SELECT
			equiv_id, id, type, language_id, alias,
			visitor_version, admin_version, status, lock_owner_id
		FROM ". DB_PREFIX. "content_items
		WHERE id = ". (int) $cID. "
		  AND type = '". \ze\escape::asciiInSQL($cType). "'"))
 && ($chain = \ze\sql::fetchAssoc("
		SELECT equiv_id, type, privacy, at_location, smart_group_id
		FROM ". DB_PREFIX. "translation_chains
		WHERE equiv_id = ". (int) $content['equiv_id']. "
		  AND type = '". \ze\escape::asciiInSQL($cType). "'"))

//
//	Some checks that need to be true to show a draft in staging mode.
//

//1. The content item must have a draft.
 && (ze\content::isDraft($content['status']))

//2. This only works on the draft version.
 && ($version['version'] == $content['admin_version'])

//2. This only works on public content items. 
 && ($chain['privacy'] == 'public')


) {
	//OK everything looks fine.

} else {
	//Show the no-access if this page does not exist
	header('HTTP/1.0 404 Not Found');
	ze\content::langSpecialPage('zenario_not_found', $cID, $cType);
	$status = ze\content::getShowableContent($content, $chain, $version, $cID, $cType);
}


//
//	The code/function calls for showing the content item normally, copied from index.php, follows:
//

ze\content::setShowableContent($content, $chain, $version, true);

$specialPage = ze\content::isSpecialPage(ze::$cID, ze::$cType);


ze\plugin::runSlotContents(
	ze::$slotContents,
	ze::$cID, ze::$cType, ze::$cVersion,
	ze::$layoutId
);


echo 
'<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="', ze::$langId, '" lang="', ze::$langId, '">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<base href="', ze\link::absolute(), '">';

echo '
<title>', htmlspecialchars(ze::$pageTitle), '</title>
<meta name="robots" content="noindex" />
<meta name="description" content="', (ze::$pageDesc ? htmlspecialchars(ze::$pageDesc) : ''), '" />
<meta name="generator" content="Zenario ', ze\site::versionNumber(), '" />
<meta name="keywords" content="', (ze::$pageKeywords ? htmlspecialchars(ze::$pageKeywords) : ''), '" />
<meta name="skin" content="' . ze::$skinName . '"/>';

ze\content::pageHead('zenario/');

echo "</head>";

$contentItemDiv =
	"\n".
	'<div id="zenario_citem" class="';

if ($specialPage) {
	$contentItemDiv .= htmlspecialchars($specialPage). ' ';
}

$contentItemDiv .= 'lang_'. preg_replace('/[^\w-]/', '', ze::$langId);

if (ze::$itemCSS) {
	$contentItemDiv .= ' '. htmlspecialchars(ze::$itemCSS);
}

$contentItemDiv .= '">';


$templateDiv =
	"\n".
	'<div id="zenario_layout" class="'.
		'zenario_'. htmlspecialchars(ze::$cType). '_layout';

if (ze::$templateCSS) {
	$templateDiv .= ' '. htmlspecialchars(ze::$templateCSS);
}

$templateDiv .= '">';


$skinDiv =
	"\n".
	'<div id="zenario_skin" class="zenario_skin';

if (ze::$skinCSS) {
	$skinDiv .= ' '. htmlspecialchars(ze::$skinCSS). '';
}

$skinDiv .= '">';

ze\content::pageBody('', '', true);
ze\cookie::showConsentBox(false, false, false);


//
//	Show a collapsable info-box, warning about staging mode.
//

echo '
<style type="text/css">
.zenario_staging_mode_warning {
	border: 2px solid grey;
	background-color: #f80;
	font-family: Verdana,Tahoma,Arial,Helvetica,sans-serif;
	font-size:13px;
	color: #fff;
	border-color: #a60;
	padding: 10px 10px 10px 45px;
	border-radius: 4px;
	z-index: 999999;
	position: fixed;
    left: 50%;
    bottom: 10px;
    transform: translate(-50%, 0);
	cursor: pointer;
	user-select: none;
	min-width:280px;
	
}

.zenario_staging_mode_warning:before {
	content:"";
	display:block;
	width:30px;
	height:100%;
	position:absolute;
	left:0;
	top:0;
	background: #fc8 no-repeat 8px center / 16px 16px;
	background-image: url(zenario/admin/images/icon-thumbs-up-shadow.svg);
    background-size: auto 16px;
}

.zenario_staging_mode_warning_closed {
	width: 1px;
	height: 19px;
	padding: 5px 5px 5px 40px;
	color: #eee;
	font-size: 6px;
}
	
</style>
<div
	class="zenario_staging_mode_warning"
	onclick="if (window.$) $(this).toggleClass(\'zenario_staging_mode_warning_closed\');"
>', ze\admin::phrase("You are viewing this page in staging mode. Note that some parts of the page may not be visible where they link to other unpublished pages."), '</div>';

echo $skinDiv, $templateDiv, $contentItemDiv;


if ($tplFile = ze\content::layoutHtmlPath(ze::$layoutId, true)) {
	require CMS_ROOT. $tplFile;
	ze\plugin::checkSlotsWereUsed();
}

echo "\n", '</div></div></div>';
ze\content::pageFoot('zenario/', false, false, false, true);

echo "\n</body>\n</html>";


exit;