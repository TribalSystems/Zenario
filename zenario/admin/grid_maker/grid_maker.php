<?php

require '../../adminheader.inc.php';


$homeLink = $backLink = '';



echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>',  ze\admin::phrase('Gridmaker'), '</title>';

$prefix = '../../';
ze\content::pageHead($prefix);


ze\skinAdm::checkForChangesInFiles($runInProductionMode = true);
$v = ze\db::codeVersion();

echo '
	<link rel="stylesheet" type="text/css" href="../../styles/grid.min.css?v=', $v, '" media="screen"/>
	<link rel="stylesheet" type="text/css" href="../../styles/admin_grid_maker.min.css?v=', $v, '" media="screen"/>';


echo '</head>';
ze\content::pageBody();


?>


 
<?php
ze\content::pageFoot($prefix, false, false, false);

echo '
<script type="text/javascript" src="../../libs/manually_maintained/mit/jquery/jquery-ui.spinner.min.js?v=', $v, '"></script>
<script type="text/javascript" src="../../js/grid.min.js?v=', $v, '"></script>
<script type="text/javascript" src="../../js/admin_grid_maker.min.js?v=', $v, '"></script>
<script type="text/javascript">
	window.zenarioAdminHasZipPerms = ', ze\ring::engToBoolean(ze\priv::check('_PRIV_VIEW_TEMPLATE')), ';
	window.zenarioAdminHasSavePerms = ', ze\ring::engToBoolean(ze\priv::check('_PRIV_EDIT_TEMPLATE')), ';
</script>';

?>


<div class="grid_maker_wrap">
	<div id="close_button"></div>
	<div id="settings" style="margin:auto;"></div>
	<div class="grid_panel_wrap">
		<div class="grid_add_toolbar" id="grid_add_toolbar"></div>
		<div class="grid_panel" id="grid" style="margin:auto;"></div>
	</div>
	<div id="download_links" style="margin:auto;"></div>
</div>


<script type="text/javascript">
	zenarioGM.init(<?php
		
		//Load a Template file's details into Gridmaker
			//There are two different formats here:
				//We could be passed the numeric id of a Layout, in which case we need to look up the Family Name and Filename
				//Or we could be passed the Family Name and Filename as a string encoded with ze\ring::encodeIdForOrganizer()
			//Then we need to take the Family Name and Filename, form a path, check that file exists, open it,
			//read its contents and then check to see if there is Grid data in there.
		$layoutId = 0;
		if (($_REQUEST['loadTemplateFile'] ?? false)
		 && ((is_numeric($_REQUEST['id'] ?? false)
		   && ($layout = ze\content::layoutDetails($_REQUEST['id'] ?? false))
		   && ($layoutId = $layout['layout_id'])
		   )
		  )
		 ) {
			
			//Combine the settings for the Grid Skin with the array of Slots, and pass it to Gridmaker
			$data = ze\row::get('layouts', 'json_data', $layoutId);
			echo json_encode($data);
			
			//Pass the Skin Id if one was successfully loaded, and also pass the Filename
			echo ", ". (int) $layoutId,  ", '". ze\layoutAdm::codeName($layoutId),  "'";
		
		} else {
			//When creating a new layout, these will be the default values.
			echo "'{\"cols\": 12, \"fluid\": true, \"gCols\": 12, \"mirror\": false, \"gGutter\": 1, \"maxWidth\": 1240, \"minWidth\": 769, \"gutterFlu\": 1, \"responsive\": true, \"gGutterLeftEdge\": 1.6, \"gGutterRightEdge\": 1.6, \"gutterLeftEdgeFlu\": 1.6, \"gutterRightEdgeFlu\": 1.6}'";
		}
	?>);
	$(document).ready(function() { zenarioGM.draw(); });
</script>


</body>
</html>