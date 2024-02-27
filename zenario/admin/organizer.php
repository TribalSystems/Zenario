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

//Attempt to include the basic header
//We need to check two different paths, as this file can be accessed from two different ways,
//depending on how the friendly URLs have been set up.
if (is_file('zenario/adminheader.inc.php')) {
	require 'zenario/adminheader.inc.php';
} else {
	require '../adminheader.inc.php';
}


//Catch the case where someone comes in on the old "unfriendly" URL (i.e. zenario/admin/welcome.php),
//and redirect them to the new "friendly" one (i.e. admin.php).
$uri = explode('?', $_SERVER['REQUEST_URI'] ?? '', 2)[0];
if (false !== ze\ring::chopSuffix($uri, 'zenario/admin/organizer.php')) {
	header ('Location: ../../organizer.php?'. $_SERVER['QUERY_STRING']);
	exit;
}


ze\skinAdm::checkForChangesInFiles();
 
$homeLink = $backLink = '';


//Check if Organizer was opened from a page
//(And actually check if the page exists, just in case the values in the URL are bogus)
if (!empty($_GET['fromCID'])
 && !empty($_GET['fromCType'])
 && ($fromCID = (int) $_GET['fromCID'])
 && ($fromCType = $_GET['fromCType'])
 && (ze\row::exists('content_items', ['id' => $fromCID, 'type' => $fromCType, 'status' => ['!' => 'deleted']]))) {
} else {
	$fromCID = false;
	$fromCType = false;
}





echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>Organizer for ', htmlspecialchars(empty($_SERVER['SERVER_NAME'])? ze\link::primaryDomain() : $_SERVER['SERVER_NAME']), '</title>
	<style type="text/css">
		body {
			margin: 3px;
			overflow: hidden;
		}
	</style>';


if (!ze\priv::check()) {
	echo '
		</head>
		<body>
			<script type="text/javascript">
				var hash = encodeURIComponent(("" + document.location.hash).replace("#", ""));
				document.location.href = "', ze\escape::js(ze\link::absolute()). 'admin.php?og=" + (hash? hash : "zenario__content/panels/content");
			</script>
			<p><a href="', ze\escape::js(ze\link::absolute()). 'admin.php">', ze\admin::phrase('Please log in'), '</a></p>
		</body>
		</html>';
	exit;
}

ze\miscAdm::checkForChangesInYamlFiles();

$prefix = 'zenario/';
ze\content::pageHead($prefix, 'organizer', true);
$v = ze\db::codeVersion();


echo '</head>';
ze\content::pageBody();


                        	
$homePageCID = $homePageCType = false;
ze\content::langSpecialPage('zenario_home', $homePageCID, $homePageCType, ze::$defaultLang);

if (!empty($_GET['openedInIframe'])) {
	$topLeftHTML =
	$topRightHTML = '';
	
} else {
	$topLeftHTML = '
		<div class="zenario_website_button top_left_button">
			<a id="zenario_website_button_link" data-position="right" data-intro="<p><strong>Link to Zenar.io</strong></p><p>Go to the zenar.io for information and support.</p>"
				href="https://zenar.io" target="_blank"
				title="Zenar.io"></a>
		</div>
	
		<div class="home_page_button top_left_button">
			<a id="home_page_button_link" data-step="1" data-position="right" data-highlightClass="step_1" data-intro="<p><strong>Home page link</strong></p><p>Go to the front-end at your website’s homepage.</p>"
				href="'. htmlspecialchars(($homeLink = ze\link::toItem($homePageCID, $homePageCType, true)) ?: 'index.php'). '"
				title="'. ze\admin::phrase('Back to&lt;br/&gt;Home Page'). '"></a>
		</div>';
	
	if ($fromCID && ($fromCID != $homePageCID || $fromCType != $homePageCType)) {
		$topLeftHTML .= '
			<div class="last_page_button top_left_button">
				<a id="last_page_button_link"
					href="'. htmlspecialchars($backLink = ze\link::toItem($fromCID, $fromCType, true)). '"
					title="'. ze\admin::phrase('Back to&lt;br/&gt;[[citem]]', ['citem' => htmlspecialchars(ze\content::formatTag($fromCID, $fromCType))]). '"></a>
			</div>';
	}
	$wipCount = ze\row::count('content_items', ['status' => ['first_draft','published_with_draft','hidden_with_draft','trashed_with_draft','unlisted_with_draft']]);
	$topLeftHTML .= '
		<div
			class="zenario_ywip top_left_button zenario_item_record_count_parent" data-step="2" data-position="right" data-intro="<p><strong>Work in progress</strong></p><p>Content items (web pages etc.) which are in draft form.</p>"
			onclick="zenarioO.updateYourWorkInProgress();"
			onmouseover="zenarioO.updateYourWorkInProgress();"
		>
			<a></a><div id="zenario_ywip_dropdown" class="zenario_ywip_loading"></div>';
			if($wipCount > 0) {
				$topLeftHTML .= '<span id="zenario_wip_recordCount" class="zenario_wip_recordCount zenario_item_record_count">'.$wipCount.'</span>';
			}
			$topLeftHTML .= '</div>';
	
	$topRightHTML = '
		<div class="logout_button top_right_button" data-step="4" data-position="left" data-intro="<p><strong>Logout</strong></p><p>Log out of administration mode.</p>">
			<a '. ze\admin::logoutOnclick(). '
				title="'. ze\admin::phrase('Logout'). '"></a>
		</div>
		<div id="organizer_top_right_buttons"></div>
		<div id="organizer_topRightIcons"></div>';
}


?>

<div id="fileQueue" style="display: none; position: absolute; top: 171px; z-index: 99;"></div>



<?php
ze\content::pageFoot($prefix, 'organizer', true, false);

echo '
	<script type="text/javascript">';

if (ze::get('openingInstance') && ze::get('openingPath')) {
	echo '
		window.zenarioOQuickMode = true;
		window.zenarioOOpeningInstance = ', (int) ze::get('openingInstance'), ';
		window.zenarioOOpeningPath = "', preg_replace('@[^\w-/]@', '', ze::get('openingPath')), '";';
}

$adminAuthType = ze\row::get('admins', 'authtype', ze\admin::id());
$show_help_tour_next_time = (($adminAuthType == 'local') && ze\admin::setting('show_help_tour_next_time'));

if ($fromCID) {
	echo '
		zenarioA.fromCID = ', (int) $fromCID, ';
		zenarioA.fromCType = "', ze\escape::js($fromCType), '";';
} else {
	echo '
		zenarioA.fromCID = false;
		zenarioA.fromCType = false;';
}

echo '
		zenarioA.openedInIframe = ', ze::get('openedInIframe')? 'true' : 'false', ';
		zenarioA.homeLink = "', ze\escape::js($homeLink), '";
		zenarioA.backLink = "', ze\escape::js($backLink), '";
		
		zenarioO.topLeftHTML = \'', ze\escape::js($topLeftHTML), '\';
		zenarioO.topRightHTML = \'', ze\escape::js($topRightHTML), '\';
		
		zenarioA.seen_help_tour = ', (int)isset($_SESSION['seen_help_tour']) ,'
		zenarioA.show_help_tour_next_time = ', (int)$show_help_tour_next_time, ';
		
		$(function() {
			zenarioO.open(zenarioA.getSKBodyClass(), undefined, undefined, undefined, 0, true, true, false, false);
			zenarioO.init();
			zenarioO.size();
		});
		
		zenarioA.isFullOrganizerWindow = true;';


echo '
	</script>';

if (!isset($_SESSION['seen_help_tour'])) {
	$_SESSION['seen_help_tour'] = true;
}

?>
</body>
</html>
