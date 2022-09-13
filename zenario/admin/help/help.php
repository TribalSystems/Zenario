<?php

require '../../adminheader.inc.php';

$topic = $_GET['topic'] ?? false;


echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>';
	
		switch ($topic) {
			case 'body_classes':
				echo ze\admin::phrase('Zenario help: CSS classes that appear on the <body> tag');
				break;
			default:
				echo ze\admin::phrase('Zenario help');
				break;
		}

echo '
	</title>';

$prefix = '../../';
ze\content::pageHead($prefix);
$v = ze\db::codeVersion();

echo '
	<link rel="stylesheet" type="text/css" href="../../styles/admin_help.min.css?v=', $v, '" media="screen"/>';


echo '</head>';

ze\content::pageBody();


if ($topic == 'body_classes') {
	?>
		<h1>Zenario help: CSS classes that appear on the <code>&lt;body&gt;</code> tag</h1>
		<p>
			Zenario adds the following CSS classes to the <code>&lt;body&gt;</code> tag.
		</p>
		
		<pre>body.fluid, body.fixed</pre>
		<p>
			Set according to whether the layout is set to Fluid or Fixed width.
		</p>
		
		<pre>body.desktop</pre>
		<p>
			Set when the layout is Responsive, and the client's current window width is wide enough to show the grid.
		</p>
		
		<pre>body.mobile</pre>
		<p>
			Set when the layout is Responsive, and the client's current window width is narrower than the minimum width of the layout's grid
			(such as when a mobile device is used), so that the grid becomes disabled.
		</p>
		
		<pre>body.js, body.no_js</pre>
		<p>
			Set according to whether JavaScript is enabled in the client browser.
		</p>
		
		<pre>body.retina, body.not_retina</pre>
		<p>
			Set according to whether client has a high pixel density screen, and a browser that supports it.
			<code>1px</code> in your code may represent two (or more) pixels on the screen.
		</p>
		
		<pre>body.touchscreen, body.non_touchscreen</pre>
		<p>
			Set according to whether the visitor is using a touchscreen, and a browser that supports it.
		</p>
		
	<?php
}






ze\content::pageFoot($prefix, false, false, false);


echo '
</body>
</html>';
