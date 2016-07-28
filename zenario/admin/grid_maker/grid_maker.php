<?php

require '../../adminheader.inc.php';
require CMS_ROOT. 'zenario/admin/grid_maker/grid_maker.inc.php';

$gzf = setting('compress_web_pages')? '?gz=1' : '?gz=0';
$gz = setting('compress_web_pages')? '&amp;gz=1' : '&amp;gz=0';

$homeLink = $backLink = '';



echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>',  adminPhrase('Grid Maker'), '</title>';

$prefix = '../../';
CMSWritePageHead($prefix);


checkForChangesInCssJsAndHtmlFiles($runInProductionMode = true);
$v = zenarioCodeVersion();

echo '
	<link rel="stylesheet" type="text/css" href="../../styles/admin_grid_maker.min.css?v=', $v, '" media="screen"/>';


echo '</head>';
CMSWritePageBody();


?>


 
<?php
CMSWritePageFoot($prefix, false, false, false);

echo '
<script type="text/javascript" src="../../libraries/mit/jquery/jquery-ui.button.min.js?v=', $v, '"></script>
<script type="text/javascript" src="../../libraries/mit/jquery/jquery-ui.spinner.min.js?v=', $v, '"></script>
<script type="text/javascript" src="../../js/admin_grid_maker.min.js?v=', $v, '"></script>
<script type="text/javascript">
	window.zenarioAdminHasZipPerms = ', engToBoolean(checkPriv('_PRIV_VIEW_TEMPLATE_FAMILY')), ';
	window.zenarioAdminHasSavePerms = ', engToBoolean(checkPriv('_PRIV_EDIT_TEMPLATE_FAMILY')), ';
</script>';

?>


<div class="grid_maker_wrap">
	<div id="close_button"></div>
	<div id="settings" style="margin:auto;"></div>
	<div class="grid_panel_wrap"><div class="grid_panel" id="grid" style="margin:auto;"></div></div>
	<div id="download_links" style="margin:auto;"></div>
</div>


<script type="text/javascript">
	zenarioG.init(<?php
		
		//Load a Template file's details into Grid Maker
			//There are two different formats here:
				//We could be passed the numeric id of a Layout, in which case we need to look up the Family Name and Filename
				//Or we could be passed the Family Name and Filename as a string encoded with encodeItemIdForOrganizer()
			//Then we need to take the Family Name and Filename, form a path, check that file exists, open it,
			//read its contents and then check to see if there is Grid data in there.
		$layoutId = 0;
		if (request('loadTemplateFile')
		 && ((is_numeric(request('id'))
		   && ($layout = getTemplateDetails(request('id')))
		   && ($layoutId = $layout['layout_id'])
		   && ($path = $layout['family_name']. '/'. $layout['filename'])
		   )
		  || (request('c')
		   && ($path = decodeItemIdForOrganizer(request('c')). '.tpl.php')
		   && ($layout = explode('/', $path, 2))
		   && (!empty($layout[1]))
		   && ($layout['family_name'] = $layout[0])
		   && ($layout['filename'] = $layout[1])
		   )
		  )
		 && ($fileContents = is_file(CMS_ROOT. zenarioTemplatePath(). $path))
		 && ($fileContents = @file_get_contents(CMS_ROOT. zenarioTemplatePath(). $path))
		 && ($data = zenario_grid_maker::readCode($fileContents))) {
			
			//Combine the settings for the Grid Skin with the array of Slots, and pass it to Grid Maker
			echo json_encode($data);
			
			//Pass the Skin Id if one was successfully loaded, and also pass the Filename
			echo ", ". (int) $layoutId,  ", '". jsEscape(substr($layout['filename'], 0, -8)), "', '". jsEscape($layout['family_name']), "'";
		
		} else {
			echo "'{}'";
		}
	?>);
	$(document).ready(function() { zenarioG.draw(); });
</script>


</body>
</html>