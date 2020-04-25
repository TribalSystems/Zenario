<?php

require '../../adminheader.inc.php';


$homeLink = $backLink = '';



echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>',  ze\admin::phrase('Grid Maker'), '</title>';

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
		
		//Load a Template file's details into Grid Maker
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
		   && ($path = $layout['family_name']. '/'. $layout['filename'])
		   )
		  || (($_REQUEST['c'] ?? false)
		   && ($path = ze\ring::decodeIdForOrganizer($_REQUEST['c'] ?? false). '.tpl.php')
		   && ($layout = explode('/', $path, 2))
		   && (!empty($layout[1]))
		   && ($layout['family_name'] = $layout[0])
		   && ($layout['filename'] = $layout[1])
		   )
		  )
		 && ($fileContents = is_file(CMS_ROOT. ze\content::templatePath(). $path))
		 && ($fileContents = @file_get_contents(CMS_ROOT. ze\content::templatePath(). $path))
		 && ($data = ze\gridAdm::readCode($fileContents))) {
			
			//Combine the settings for the Grid Skin with the array of Slots, and pass it to Grid Maker
			echo json_encode($data);
			
			//Pass the Skin Id if one was successfully loaded, and also pass the Filename
			echo ", ". (int) $layoutId,  ", '". ze\escape::js(substr($layout['filename'], 0, -8)), "', '". ze\escape::js($layout['family_name']), "'";
		
		} else {
			echo "'{}'";
		}
	?>);
	$(document).ready(function() { zenarioGM.draw(); });
</script>


</body>
</html>