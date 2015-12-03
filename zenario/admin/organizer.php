<?php

require '../adminheader.inc.php';

$gzf = setting('compress_web_pages')? '?gz=1' : '?gz=0';
$gz = setting('compress_web_pages')? '&amp;gz=1' : '&amp;gz=0';
useGZIP(setting('compress_web_pages'));

$homeLink = $backLink = '';



echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>Organizer for ', htmlspecialchars(empty($_SERVER['SERVER_NAME'])? primaryDomain() : $_SERVER['SERVER_NAME']), '</title>
	<style type="text/css">
		body {
			margin: 3px;
			overflow: hidden;
		}
	</style>';


if (!checkPriv()) {
	echo '
		</head>
		<body>
			<script type="text/javascript">
				var hash = encodeURIComponent(("" + document.location.hash).replace("#", ""));
				document.location.href = "welcome.php?og=" + (hash? hash : "zenario__content/panels/content");
			</script>
			<p><a href="welcome.php">', adminPhrase('Please log in'), '</a></p>
		</body>
		</html>';
	exit;
}


checkForChangesInCssJsAndHtmlFiles();
$v = ifNull(setting('css_js_version'), ZENARIO_VERSION. '.'. LATEST_REVISION_NO);

$prefix = '../';
CMSWritePageHead($prefix, 'organizer');


echo '</head>';
CMSWritePageBody('', false);
//CMSWritePageBody(' onkeydown="return zenarioO.onKeyDown(event);"');


                        	
$homePageCID = $homePageCType = false;
langSpecialPage('zenario_home', $homePageCID, $homePageCType, setting('default_language'), true);

if (!empty($_GET['openedInIframe'])) {
	$topLeftHTML =
	$topRightHTML = '';
	
} else {
	$topLeftHTML = '
		<div class="home_page_button top_left_button">
			<a id="home_page_button_link" data-step="1" data-position="right" data-highlightClass="step_1" data-intro="<p><strong>Back to homepage</strong></p><p>This button takes you back to the ‘front-end’ view of your website’s homepage.</p>"
				href="'. htmlspecialchars(ifNull($homeLink = linkToItem($homePageCID, $homePageCType, true, 'zenario_sk_return=navigation_path'), '../../')). '"
				title="'. adminPhrase('Back to&lt;br/&gt;Home Page'). '"></a>
		</div>';
	
	if (get('fromCID') && get('fromCType') && (get('fromCID') != $homePageCID || get('fromCType') != $homePageCType)) {
		$topLeftHTML .= '
			<div class="last_page_button top_left_button">
				<a id="last_page_button_link"
					href="'. htmlspecialchars($backLink = linkToItem(get('fromCID'), get('fromCType'), true, 'zenario_sk_return=navigation_path')). '"
					title="'. adminPhrase('Back to&lt;br/&gt;[[citem]]', array('citem' => htmlspecialchars(formatTag(get('fromCID'), get('fromCType'))))). '"></a>
			</div>';
	}
	
	$topLeftHTML .= '
		<div
			class="zenario_ywip top_left_button" data-step="2" data-position="right" data-intro="<p><strong>Your work in progress</strong></p><p>This button displays pages/content items which are still being worked or edited, and thus not published.</p>"
			onclick="zenarioO.updateYourWorkInProgress();"
			onmouseover="zenarioO.updateYourWorkInProgress();"
		>
			<a></a>
			<div id="zenario_ywip_dropdown" class="zenario_ywip_loading"></div>
		</div>';
	
	$topRightHTML = '
		<div class="logout_button top_right_button" data-step="4" data-position="left" data-intro="<p><strong>Logout</strong></p><p>This button logs you out of the administration panel of the website.</p>">
			<a '. adminLogoutOnclick(). '
				title="'. adminPhrase('Logout'). '"></a>
		</div>
		<div
			class="zenario_admin_name top_right_button"
			data-step="3" data-position="down" data-intro="<p><strong>Administrator</strong></p><p>This area displays the details of the user logged in.</p>"
		>
			<span>'. htmlspecialchars(formatAdminName()). '</span>
			<ul>
				<li>
					<a onclick="zenarioA.openProfile(); return false;">
						<span>'. adminPhrase('View profile'). '</span>
					</a>
				</li>';
	
	if (!session('admin_global_id')) {
		$topRightHTML .= '
				<li>
					<a onclick="zenarioO.changePassword(); return false;">
						<span>'. adminPhrase('Change password'). '</span>
					</a>
				</li>';
	}
	
	$topRightHTML .= '
			</ul>
		</div>';
}


?>

<div id="fileQueue" style="display: none; position: absolute; top: 171px; z-index: 99;"></div>



<?php
CMSWritePageFoot($prefix, 'organizer', true, false);

echo '
	<script type="text/javascript">';

if (get('openingInstance') && get('openingPath')) {
	echo '
		window.zenarioOQuickMode = true;
		window.zenarioOOpeningInstance = ', (int) get('openingInstance'), ';
		window.zenarioOOpeningPath = "', preg_replace('@[^\w-/]@', '', get('openingPath')), '";';
}

$adminAuthType = getRow('admins', 'authtype', adminId());
$show_help_tour_next_time = (($adminAuthType == 'local') && adminSetting('show_help_tour_next_time'));

echo '
		zenarioA.fromCID = ', (int) get('fromCID'), ';
		zenarioA.fromCType = "', preg_replace('/\W/', '', get('fromCType')), '";
		zenarioA.openedInIframe = ', get('openedInIframe')? 'true' : 'false', ';
		zenarioA.homeLink = "', jsEscape($homeLink), '";
		zenarioA.backLink = "', jsEscape($backLink), '";
		
		zenarioO.topLeftHTML = \'', jsEscape($topLeftHTML), '\';
		zenarioO.topRightHTML = \'', jsEscape($topRightHTML), '\';
		
		zenarioA.seen_help_tour = ', (int)isset($_SESSION['seen_help_tour']) ,'
		zenarioA.show_help_tour_next_time = ', (int)$show_help_tour_next_time, ';
		
		$(function() {
			zenarioO.open(zenarioA.getSKBodyClass(), undefined, undefined, undefined, 0, true, true, false, false);
			zenarioO.init();
			zenarioO.size();
		});
		
		zenarioA.isFullOrganizerWindow = true;';


//If a toast was displayed shortly before Organizer was reloaded for some reason,
//atttempt to display it again.
if (!empty($_SESSION['_remember_toast'])
 && json_decode($_SESSION['_remember_toast'])) {
 	
 	echo '
 		zenarioA.toast(', $_SESSION['_remember_toast'], ');';
 	//N.b. this should already be json_encoded so I don't need to escape this, but I still need
 	//the call to json_decode() above to check it is actually valid JSON and not a XSS attack!
}
unset($_SESSION['_remember_toast']);


echo '
	</script>';

if (!isset($_SESSION['seen_help_tour'])) {
	$_SESSION['seen_help_tour'] = true;
}

?>
</body>
</html>
