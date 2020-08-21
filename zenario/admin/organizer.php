<?php

require '../adminheader.inc.php';

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
				document.location.href = "welcome.php?og=" + (hash? hash : "zenario__content/panels/content");
			</script>
			<p><a href="welcome.php">', ze\admin::phrase('Please log in'), '</a></p>
		</body>
		</html>';
	exit;
}

ze\miscAdm::checkForChangesInYamlFiles();

$prefix = '../';
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
		<div class="home_page_button top_left_button">
			<a id="home_page_button_link" data-step="1" data-position="right" data-highlightClass="step_1" data-intro="<p><strong>Back to homepage</strong></p><p>This button takes you back to the ‘front-end’ view of your website’s homepage.</p>"
				href="'. htmlspecialchars(($homeLink = ze\link::toItem($homePageCID, $homePageCType, true, 'zenario_sk_return=navigation_path')) ?: '../../'). '"
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
			<a '. ze\admin::logoutOnclick(). '
				title="'. ze\admin::phrase('Logout'). '"></a>
		</div>
		<div id="organizer_top_right_buttons"></div>';
}


?>

<div id="fileQueue" style="display: none; position: absolute; top: 171px; z-index: 99;"></div>



<?php
ze\content::pageFoot($prefix, 'organizer', true, false);

echo '
	<script type="text/javascript">';

if (($_GET['openingInstance'] ?? false) && ($_GET['openingPath'] ?? false)) {
	echo '
		window.zenarioOQuickMode = true;
		window.zenarioOOpeningInstance = ', (int) ($_GET['openingInstance'] ?? false), ';
		window.zenarioOOpeningPath = "', preg_replace('@[^\w-/]@', '', ($_GET['openingPath'] ?? false)), '";';
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
		zenarioA.openedInIframe = ', ($_GET['openedInIframe'] ?? false)? 'true' : 'false', ';
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
