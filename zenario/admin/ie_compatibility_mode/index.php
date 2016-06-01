<?php
require '../../visitorheader.inc.php';

if (get('isWizard')) {
	unset($_GET['isWizard']);
	$return = 'zenario/wizard.php?';
} else {
	$return = 'zenario/admin/index.php?';
}

?>

<html>
	<head>
		<script type="text/javascript">
			if (typeof JSON !== "undefined") {
				document.location = "<?php echo jsEscape(absCMSDirURL(). $return. http_build_query($_GET)); ?>";
			}
		</script>
		<style type="text/css">
			p, h1 {
				text-align: center;
			}
		</style>
	</head>
	<body>
		<p><img src="images/compatibility_mode.png" width="283" height="395" alt="compatibility mode"/></p>
		
		<h1>It looks like you have Compatibility Mode turned on in your Internet Explorer browser.</h1>
		<p>In administration mode, Zenario requires Internet Explorer 10 or later,
			and that Compatibility Mode is turned off.</p>
		<p>Alternatively please use another browser such as Chrome or Firefox.</p>
	</body>
</html>