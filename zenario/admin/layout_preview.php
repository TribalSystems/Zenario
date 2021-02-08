<?php 
/*
 * Copyright (c) 2021, Tribal Limited
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







if (!$layout = ze\row::get('layouts', true, ($_REQUEST['id'] ?? false))) {
	exit;
}

$content = [
	'equiv_id' => -1,
	'id' => -1,
	'type' => $layout['content_type'],
	'alias' => '',
	'status' => 'hidden',
	'language_id' => (ze::$defaultLang ?: 'en'),
	'admin_version' => 1,
	'visitor_version' => 0,
	'lock_owner_id' => 0];

$chain = [
	'equiv_id' => -1,
	'type' => $layout['content_type'],
	'privacy' => 'public',
	'smart_group_id' => 0];

$version = [
	'version' => 1,
	'title' => ze\admin::phrase('Layout Preview'),
	'description' => '',
	'keywords' => '',
	'feature_image_id' => 0,
	'css_class' => '',
	'release_date' => '',
	'published_datetime' => '',
	'created_datetime' => ze\date::now(),
	'rss_nest' => '',
	'rss_slot_name' => '',
	'layout_id' => $layout['layout_id']];


ze\content::setShowableContent($content, $chain, $version, false);



ze\plugin::slotContents(
	ze::$slotContents,
	ze::$cID, ze::$cType, ze::$cVersion,
	ze::$layoutId, ze::$templateFamily, ze::$templateFileBaseName);
 


echo 
'<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="', $_SESSION["user_lang"], '" lang="', $_SESSION["user_lang"], '">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>', htmlspecialchars(ze::$pageTitle), '</title>';


$mode = 'layout_preview';
$prefix = '../';
ze\content::pageHead($prefix, $mode);

echo '</head>';


$contentItemDiv = '';

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



ze\content::pageBody('zenario_layout_preview');
echo $skinDiv, $templateDiv, $contentItemDiv;

require CMS_ROOT. ze::$templatePath. ze::$templateFilename;

echo "\n", '</div></div></div>';
ze\content::pageFoot($prefix, $mode, $includeOrganizer = true, $includeAdminToolbar = false);


echo '
<script type="text/javascript" src="../js/admin_layout_preview.min.js?v=', $version['version'], '"></script>';

?>

</body>
</html>