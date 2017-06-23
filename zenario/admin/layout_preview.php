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


require '../adminheader.inc.php';

$gzf = setting('compress_web_pages')? '?gz=1' : '?gz=0';
$gz = setting('compress_web_pages')? '&amp;gz=1' : '&amp;gz=0';






require CMS_ROOT. 'zenario/includes/twig.inc.php';

if (!$layout = getRow('layouts', true, request('id'))) {
	exit;
}

$content = array(
	'equiv_id' => -1,
	'id' => -1,
	'type' => $layout['content_type'],
	'alias' => '',
	'status' => 'hidden',
	'language_id' => ifNull(setting('default_language'), 'en'),
	'admin_version' => 1,
	'visitor_version' => 0,
	'lock_owner_id' => 0);


$version = array(
	'version' => 1,
	'title' => adminPhrase('Layout Preview'),
	'description' => '',
	'keywords' => '',
	'feature_image_id' => 0,
	'css_class' => '',
	'publication_date' => '',
	'published_datetime' => '',
	'created_datetime' => now(),
	'rss_nest' => '',
	'rss_slot_name' => '',
	'version' => 1,
	'layout_id' => $layout['layout_id']);


setShowableContent($content, $version);



getSlotContents(
	cms_core::$slotContents,
	cms_core::$cID, cms_core::$cType, cms_core::$cVersion,
	cms_core::$layoutId, cms_core::$templateFamily, cms_core::$templateFileBaseName);
useGZIP(setting('compress_web_pages'));



echo 
'<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="', $_SESSION["user_lang"], '" lang="', $_SESSION["user_lang"], '">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>', htmlspecialchars(cms_core::$pageTitle), '</title>';


$mode = 'layout_preview';
$prefix = '../';
CMSWritePageHead($prefix, $mode);

echo '</head>';


$contentItemDiv = '';

$templateDiv =
	"\n".
	'<div id="zenario_layout" class="'.
		'zenario_'. htmlspecialchars(cms_core::$cType). '_layout';

if (cms_core::$templateCSS) {
	$templateDiv .= ' '. htmlspecialchars(cms_core::$templateCSS);
}

$templateDiv .= '">';


$skinDiv =
	"\n".
	'<div id="zenario_skin" class="zenario_skin';

if (cms_core::$skinCSS) {
	$skinDiv .= ' '. htmlspecialchars(cms_core::$skinCSS). '';
}

$skinDiv .= '">';



CMSWritePageBody('zenario_layout_preview');
echo $skinDiv, $templateDiv, $contentItemDiv;

require CMS_ROOT. cms_core::$templatePath. cms_core::$templateFilename;

echo "\n", '</div></div></div>';
CMSWritePageFoot($prefix, $mode, $includeOrganizer = true, $includeAdminToolbar = false);


echo '
<script type="text/javascript" src="../js/admin_layout_preview.min.js?v=', $v, '"></script>';

?>

</body>
</html>