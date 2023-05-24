<?php

require '../../adminheader.inc.php';


$homeLink = $backLink = '';

if (!empty($_GET['edit_head_slots'])) {
	$mode = 'head';

} elseif (!empty($_GET['edit_foot_slots'])) {
	$mode = 'foot';

} else {
	$mode = 'body';
}



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
	<link rel="stylesheet" type="text/css" href="../../styles/admin_grid_maker.min.css?v=', $v, '" media="screen"/>';


echo '</head>';
ze\content::pageBody();


?>


 
<?php
ze\content::pageFoot($prefix, false, false, false);

echo '
<script type="text/javascript" src="../../js/admin_grid_maker.min.js?v=', $v, '"></script>';

?>


<div class="grid_maker_wrap">
	<div id="close_button"></div>
	<div id="settings" style="margin:auto;"></div>
	<div class="grid_panel_wrap" id="grid_panel_wrap">
		<div class="grid_add_toolbar" id="grid_add_toolbar"></div>
		<div class="grid_panel zenario_grid_slot_view" id="grid" style="margin:auto;"></div>
	</div>
</div>


<script type="text/javascript">
	zenarioGM.init(<?php
		
		$controls = ze\tuix::readFile(CMS_ROOT. 'zenario/admin/grid_maker/controls.yaml');
		ze\tuix::addOrdinalsToTUIX($controls['controlFields']);
		
		echo json_encode($controls), ",\n";
		echo json_encode($mode), ",\n";
		
		//$hf = ze\row::get('layout_head_and_foot', ['cols', 'min_width', 'max_width', 'fluid', 'responsive', 'head_json_data', 'foot_json_data'], ['for' => 'sitewide']);
		$hf = ze\row::get('layout_head_and_foot', true, ['for' => 'sitewide']);
		echo json_encode($hf), ",\n";
		
		if ($mode == 'body') {
			//Load a Template file's details into Gridmaker
				//There are two different formats here:
					//We could be passed the numeric id of a Layout, in which case we need to look up the Family Name and Filename
					//Or we could be passed the Family Name and Filename as a string encoded with ze\ring::encodeIdForOrganizer()
				//Then we need to take the Family Name and Filename, form a path, check that file exists, open it,
				//read its contents and then check to see if there is Grid data in there.
			$layoutId = 0;
			if (ze::request('loadTemplateFile')
			 && ((is_numeric(ze::request('id'))
			   && ($layout = ze\content::layoutDetails(ze::request('id')))
			   && ($layoutId = $layout['layout_id'])
			   )
			  )
			 ) {
			
				//Combine the settings for the Grid Skin with the array of Slots, and pass it to Gridmaker
				$data = ze\row::get('layouts', 'json_data', $layoutId);
				ze\gridAdm::trimData($data);
				ze\gridAdm::trimHeadAndFootSlots($data);
				echo json_encode($data);
			
				//Pass the Skin Id if one was successfully loaded, and also pass the Filename
				echo ", ". (int) $layoutId,  ", '". ze\layoutAdm::codeName($layoutId),  "'";
			
				$slotContents = [];
				$content = $dummyContentItem = ['id' => -1, 'type' => 'x', 'admin_version' => -1, 'head_html' => null, 'head_overwrite' => 0, 'foot_html' => null, 'foot_overwrite' => 0];
				ze\plugin::slotContents(
					$slotContents,
					$content['id'], $content['type'], $content['admin_version'],
					$layout['layout_id'],
					$specificInstanceId = false, $specificSlotName = false, $ajaxReload = false,
					$runPlugins = false);
			
				echo ', ', json_encode($slotContents, JSON_FORCE_OBJECT);

		
			} else {
				//When creating a new layout, these will be the default values.
				echo json_encode(ze\gridAdm::sensibleDefault(), JSON_FORCE_OBJECT);
			}
		
		} else {
			
			//Try to work out the slot contents for site-wide slots.
			//This will be a little bit of a hack, we'll need to find a layout that uses the site-wide header and footer and then check
			//its contents.
			$slotContents = [];
			if ($layoutId = ze\row::get('layouts', 'layout_id', ['header_and_footer' => 1])) {
				$layout = ze\content::layoutDetails($layoutId);
				$content = $dummyContentItem = ['id' => -1, 'type' => 'x', 'admin_version' => -1, 'head_html' => null, 'head_overwrite' => 0, 'foot_html' => null, 'foot_overwrite' => 0];
				ze\plugin::slotContents(
					$slotContents,
					$content['id'], $content['type'], $content['admin_version'],
					$layout['layout_id'],
					$specificInstanceId = false, $specificSlotName = false, $ajaxReload = false,
					$runPlugins = false);
			}
			
			echo 'undefined, undefined, undefined, ', json_encode($slotContents, JSON_FORCE_OBJECT). ', ';
			
			//When editing the head or foot slot, get a list of slots used across the site in the various layouts
			$sql = "
				SELECT DISTINCT lsl.slot_name
				FROM ". DB_PREFIX. "layouts AS l
				INNER JOIN ". DB_PREFIX. "layout_slot_link AS lsl
				   ON lsl.layout_id = l.layout_id
				  AND lsl.is_header = 0
				  AND lsl.is_footer = 0";
			echo json_encode(ze\sql::fetchValues($sql));
		}
	?>);
	$(document).ready(function() { zenarioGM.draw(); });
</script>


</body>
</html>