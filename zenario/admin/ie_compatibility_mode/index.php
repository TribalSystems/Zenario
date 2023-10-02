<?php
require '../../visitorheader.inc.php';

$return = 'zenario/admin/index.php?';

$httpUserAgent = ($_SERVER['HTTP_USER_AGENT'] ?? '');

//This screen is linked to from zenario/autoload/fun/pageHead.php. It nags anyone who looks
//like they are still using IE to please switch.

?>
<html>
	<head>
		<?php
			echo '
				<script type="text/javascript">
					if (typeof JSON === "undefined" || ', ze\ring::engToBoolean(\ze\cache::browserIsIE()), ') {
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
		
		<h1>It looks like you are using Internet Explorer, or have Compatibility Mode for Internet Explorer enabled in your browser.</h1>
		<p>Internet Explorer is no longer supported. Zenario requires that you update to Microsoft Edge, and that Compatibility Mode is turned off.</p>
		<p>Alternatively please use another browser such as Chrome or Firefox.</p>
	</body>
</html>