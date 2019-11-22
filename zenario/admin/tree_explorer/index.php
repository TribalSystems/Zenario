<?php

require '../../adminheader.inc.php';
require 'includes/tree_explorer.php';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Tree Explorer</title>
    <link rel="stylesheet" href="../../styles/admin_tree_explorer_styles.min.css?v=<?php echo $v;?>" />
    <script type="text/javascript">
    	var svgHeight = <?php echo $svgHeight;?>;
    	var JSONURL = 'includes/json.php<?php echo $parameters;?>';
	</script>
</head>
<body>
    <h1><?php echo $top;?></h1>
	<div id="controls">
		<div id="mode_container">
			<label for="mode">Show:</label>
			<select id="mode">
				<option value="redundancy" selected="selected">Redundancy</option>
				<option value="visibility">Visibility</option>
				<option value="privacy">Privacy</option>
			</select>
		</div>
		<div id="redundancy_key" class="mode_key">
			<div class="key_block key_primary"></div>
			<div class="key_block_label">Primary</div>
			<div class="key_block key_secondary"></div>
			<div class="key_block_label">Secondary</div>
		</div>
		<div id="visibility_key" class="mode_key">
			<div class="key_block key_visible"></div>
			<div class="key_block_label">Visible</div>
			<div class="key_block key_invisible"></div>
			<div class="key_block_label">Invisible</div>
		</div>
		<div id="privacy_key" class="mode_key">
			<div class="key_block key_privacy_0"></div>
			<div class="key_block_label">Show to visitors and extranet users, even if the target content item is private</div>
			<div class="key_block key_privacy_1"></div>
			<div class="key_block_label">Obey the privacy setting of the content item</div>
			<div class="key_block key_privacy_2"></div>
			<div class="key_block_label">Show to visitors, hide from extranet users</div>
			<div class="key_block key_privacy_3"></div>
			<div class="key_block_label">Only show to extranet users</div>
		</div>
	</div>
	<div style="clear: both;"></div>
    <div id="body"></div>
	<script type="text/javascript" src="../../libs/yarn/jquery/dist/jquery.min.js?v=<?php echo $v;?>"></script>
	<script type="text/javascript" src="../../libs/manually_maintained/bsd/d3/d3.min.js?v=<?php echo $v;?>"></script>
	<script type="text/javascript" src="../../libs/manually_maintained/public_domain/json/json2.min.js?v=<?php echo $v;?>"></script>
	<script type="text/javascript" src="../../js/admin_tree_explorer.min.js?v=<?php echo $v;?>"></script>
</body>
</html>