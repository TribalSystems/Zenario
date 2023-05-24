<?php
require '../../visitorheader.inc.php';

$return = 'zenario/admin/index.php?';

$httpUserAgent = ($_SERVER['HTTP_USER_AGENT'] ?? '');

//In admin mode, if this is IE, require 11 or later. Direct 10 and earlier to the compatibility mode page.
if (strpos($httpUserAgent, 'MSIE') === false) {
	$oldIE = $notSupportedInAdminMode = false;

} else {
	$oldIE = strpos($httpUserAgent, 'MSIE 6') !== false
		|| strpos($httpUserAgent, 'MSIE 7') !== false
		|| strpos($httpUserAgent, 'MSIE 8') !== false;

	$notSupportedInAdminMode = $oldIE
		|| strpos($httpUserAgent, 'MSIE 9') !== false
		|| strpos($httpUserAgent, 'MSIE 10') !== false;
}

?>
<html>
	<head>
		<?php
			echo '
				<script type="text/javascript">
					if (typeof JSON === "undefined" || ', ze\ring::engToBoolean($notSupportedInAdminMode), ') {
					} else {
						document.location = "', ze\escape::js(ze\link::absolute(). $return. http_build_query($_GET)), '";
					}
				</script>';
		?>
		<style type="text/css">
			p, h1 {
				text-align: center;
			}
		</style>
	</head>
	<body>
		<p><img src="images/compatibility_mode.png" width="283" height="395" alt="compatibility mode"/></p>
		
		<h1>It looks like you have Compatibility Mode turned on in your Internet Explorer browser.</h1>
		<p>In administration mode, Zenario requires Internet Explorer 11 or later,
			and that Compatibility Mode is turned off.</p>
		<p>Alternatively please use another browser such as Chrome or Firefox.</p>
	</body>
</html>