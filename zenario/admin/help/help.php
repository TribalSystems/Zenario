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
			Zenario adds several CSS classes to the <code>&lt;body&gt;</code> tag of the page to help you
			add styles in certain situations.
			You can use these classes when writing styles.
		</p>
		
		<pre>body.responds ... { ... }</pre>
		<p>
			This is set when using a grid layout that is responsive.
			When the page is viewed on a mobile device,
			the grid will be disabled and the slots will be displayed in one column.
		</p>
		
		<pre>body.unresponsive ... { ... }</pre>
		<p>
			The opposite of the above, this is set when using a grid layout that is not responsive.
			When the page is viewed on a mobile device,
			the Grid layout will still be enabled and the viewer will be forced to scroll around to read the entire page.
		</p>
		
		<pre>body.mobile ... { ... }</pre>
		<p>
			This is set when using a responsive grid layout, and the grid is disabled because
			the current width of the device is smaller than the minimum width of the grid.
		</p>
		
		<pre>body.desktop ... { ... }</pre>
		<p>
			The opposite of the above, this is set when the current width of the device is large enough
			to show the grid.
		</p>
		
		<pre>body.underBP1 ... { ... }</pre>
		<p>
			If you set a custom break-point on your layout,
			this is set when current width of the device is smaller than the custom break-point.
		</p>
		
		<pre>body.overBP1 ... { ... }</pre>
		<p>
			The opposite of the above,
			this is set when current width of the device is greater than or equal to the custom break-point.
		</p>
		
		<pre>body.underBP2 ... { ... }</pre>
		<p>
			If you set a second custom break-point on your layout,
			this is set when current width of the device is smaller than the second custom break-point.
		</p>
		
		<pre>body.overBP2 ... { ... }</pre>
		<p>
			The opposite of the above,
			this is set when current width of the device is greater than or equal to the second custom break-point.
		</p>
		
		<pre>body.js ... { ... }</pre>
		<p>
			This is set when JavaScript is enabled in the visitor's browser.
		</p>
		
		<pre>body.no_js ... { ... }</pre>
		<p>
			This is set when JavaScript is disabled in the visitor's browser.
		</p>
		
		<pre>body.retina ... { ... }</pre>
		<p>
			This is set when a visitor has a high pixel density screen, and a browser that supports it.
			<code>1px</code> in your code may represent two (or more) pixels on the screen.
		</p>
		
		<pre>body.not_retina ... { ... }</pre>
		<p>
			The opposite of the above,
			this is set when a visitor has a standard pixel density
			(i.e. <code>1px</code> in your code represents one pixel on the screen).
		</p>
		
		<pre>body.touch ... { ... }</pre>
		<p>
			This is set when the visitor has a touch-screen, and a browser that supports it.
		</p>
		
		<pre>body.no_touching ... { ... }</pre>
		<p>
			The opposite of the above,
			this is set when there is no touch screen available.
		</p>
		
	<?php
}






ze\content::pageFoot($prefix, false, false, false);


echo '
</body>
</html>';