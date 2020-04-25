<?php 

require '../basicheader.inc.php';
ze\cookie::startSession();

if (!empty($_SESSION['admin_logged_in'])) {
	require 'zenario/adminheader.inc.php';
} else {
	require 'zenario/visitorheader.inc.php';
}

if (!ze\user::can('design', 'schema', ze::$vars['schemaId'] ?? $_REQUEST['schemaId'])) {
   exit;
}


//Don't open unless we have an existing slide layout
if (empty($_REQUEST['id'])) {
	exit;
}


echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>Work in progress!</title>';

$v = ze\db::codeVersion();
ze\content::pageHead('../');

echo '
    <link rel="stylesheet" type="text/css" href="../libs/yarn/toastr/build/toastr.min.css?v=', $v, '">
    <link rel="stylesheet" type="text/css" href="zenario/libs/manually_maintained/mit/jquery/css/jquery_ui/jquery-ui.css">
    
    <link rel="stylesheet" type="text/css" href="../styles/fea/fea_common.css?v=', $v, '">
    <link rel="stylesheet" type="text/css" href="../styles/fea/fea_font_awesome.css?v=', $v, '">
    <link rel="stylesheet" type="text/css" href="../styles/fea/assetwolf_portal_common.css?v=', $v, '">
    
    <link rel="stylesheet" type="text/css" href="../styles/grid.min.css?v=', $v, '">
    <link rel="stylesheet" type="text/css" href="../styles/slide_designer.min.css?v=', $v, '">
    
	<style type="text/css">
	</style>
</head>';


ze\content::pageBody();
ze\content::pageFoot('../', false, false, false);

$data = ['cells' => []];

//Pick a layout to use the basic grid information from. Preferably the default layout.
$sql = "
	SELECT layout_id, file_base_name, cols, min_width, max_width, fluid, responsive
	FROM ". DB_PREFIX. "layouts AS l
	ORDER BY
		l.layout_id = (
			SELECT default_layout_id
			FROM ". DB_PREFIX. "content_types AS ct
			WHERE ct.content_type_id = 'html'
		) DESC,
		l.content_type = 'html' DESC,
		l.cols DESC";

if ($layout = ze\sql::fetchAssoc($sql)) {
   
    $data['layout_id'] = $layout['layout_id'];
    $data['file_base_name'] = $layout['file_base_name'];
	$data['cols'] = $layout['cols'];
	$data['minWidth'] = $layout['min_width'];
	$data['maxWidth'] = $layout['max_width'];
	$data['fluid'] = $layout['fluid'];
	$data['responsive'] = $layout['responsive'];
}

//Load details on this slide layout
$slideLayout = ze\row::get('slide_layouts', ['data', 'name', 'layout_for', 'layout_for_id'], ['id' =>(int)$_REQUEST['id']]);
$data['id'] = (int) $_REQUEST['id'];
$data['name'] = $slideLayout['name'];
$data['layout_for'] = $slideLayout['layout_for'];
$data['layout_for_id'] = $slideLayout['layout_for_id'];

//Check to see if this slide layout has any slots/plugins defined
$cells = json_decode($slideLayout['data'], true);
//Convert the data format used by the mini grid to the format used by grid.js, as the editor works in this format
if (!empty($cells)){

	$groupingPos;
	$groupingLength = 0;
    $lastWidth = 0;
    
    foreach ($cells as $cell){
		//Catch a case where a property has a different name, and convert it
		$cell['slot'] = true;
		$cell['width'] = $cell['cols'];
		unset($cell['cols']);
        
        //-1 means that this slot should be in a grouping with previous slot(s)
        if ($cell['width'] == -1) {
            
			$cell['width'] = $lastWidth;
            
            if ($groupingLength == 0) {
	            //If we've not made a group before, remove the last element and turn it into a grouping
				$lastCell = array_pop($data['cells']);
				
				$data['cells'][] = [
					'width' => $lastWidth,
					'cells' => [$lastCell, $cell]
				];

			} else {
	            //If we've already done this before, just add this cell onto the end of the last grouping
				end($data['cells']);
				$ci = key($data['cells']);
				
				$data['cells'][$ci]['cells'][] = $cell;
			}
			$groupingLength++;
        
        //Normal slots. The format used is quite similar so nothing else needs to change.
        } else {
            $groupingLength = 0;
            $lastWidth = $cell['width'];
            
            $data['cells'][] =  $cell;
        }
    }
}

?>

<form >
<textarea  id="data" style="display:none;"><?php echo htmlspecialchars(json_encode($data)); ?></textarea>

</form>

<div class="grid_maker_wrap">
	<div id="close_button"></div>
	    <div id="settings" style="margin:auto;"></div>
	    <div class="grid_add_toolbar" id="grid_add_toolbar"></div>
	    <div class="grid_panel_wrap">
	    <div class="grid_panel" id="grid" style="margin:auto;">
	    </div>
	    </div>
	<!--<div id="download_links" style="margin:auto;"></div>-->
</div>


<?php


$phrases = [
	'growlSlotAdded' => 'A slot has been added',
	'growlSlotDeleted' => 'The slot has been deleted',
	'growlSlotMoved' => 'The slot has been moved',	
	'gridAddSlot' => 'Plugin',
	'gridAddChildren' => 'Plugin group',
	'growlChildrenAdded' => 'Slots have been added',	
	'gridCols' => 'Cols:',
	'gridConfirmClose' => 'You have unsaved changes. Are you sure you wish to close and abandon these changes?',
	'gridContentWidth' => 'Content width:',
	'gridContentWidthTooltip' => 'Content width|This is the largest possible thing you could place in the grid',
	'gridCSSClass' => 'Extra CSS class name(s):',
	'gridDelete' => 'Delete slot',
	'gridDesktop' => 'Desktop',
	'gridDisplayingAt' => 'Displaying at [[pixels]] pixels wide',
	'gridDotTplDotPHP' => '.tpl.php',
	'gridEditPlaceholder' => '<em>[Edit]</em>',
	'gridEditProperties' => 'Edit properties',
	'gridErrorNameFormat' => 'The slot class name may only contain the letters a-z, digits or underscores',
	'gridErrorNameIncomplete' => 'Please enter a name for this slot.',
	'gridErrorNameInUse' => 'This slot class name is already in use',
	'gridFixed' => 'Fixed width',
	'gridFixedTooltip' => 'Fixed|In a fixed grid, all of the widths are specified in &quot;px&quot;. On a normal computer screen, one &quot;px&quot; will be one pixel tall and one pixel wide. On a retina screen, one &quot;px&quot; may be two pixels tall and two pixels wide, or sometimes higher.',
	'gridFluid' => 'Fluid',
	'gridFluidTooltip' => 'Fluid|In a fluid grid, all of the widths are specified in percentages, and the size of your columns and gutters will vary depending on the screen size. Fluid grids can be prone to pixel rounding errors, especially in Internet Explorer 6 and 7.',
	'gridGridCSSClass' => 'CSS class name(s) for the next grid:',
	'gridHtml' => 'Custom HTML:',
	'gridLayoutName' => 'Layout name:',
	'gridMirror' => 'Right-to-left',
	'gridMirrorTooltip' => 'Display right-to-left|Check this option to display slots from the right to the left, e.g. for creating an Arabic, Hebrew or Urdu language version of an English language site.<br/><br/>The slots will appear right-to-left rather than left-to-right, and the <code>direction: rtl;</code> rule will be added. (This effect is not displayed when managing slots in &quot;Slots&quot;.)',
	'gridMobile' => 'Mobile',
	'gridNewSkinMessage' => 'You have created a new Grid Skin. Before you can see this Skin on a page, you must edit the settings of a Layout and select it. All content items using that Layout will then use your new Skin.',
	'gridNoPluginMessage' => 'No Plugin',
	'gridProperties' => 'Properties',
	'gridRedo' => 'Redo',
	'gridSaveProperties' => 'Save properties',
	'gridSlotName' => 'Enter a unique slot class name:',
	'gridResizeNestedCells' => 'Resize the boundary for these nested cells',
	'gridResizeSlot' => 'Resize slot',
	'gridResponsive' => 'Responsive',
	'gridResponsiveTooltip' => 'Responsive|Your grid has a minimum size. If your grid is not responsive, visitors with smaller screens than the minimum size will see scrollbars on your site. If your grid is responsive, it will turn itself off below the minimum size, and visitors with smaller screens than the minimum size will see your slots one after the other taking up all of the available space. (This effect is not displayed when managing slots in the editor.)',
	'gridResp_always' => 'Show on desktop and mobile',
	'gridResp_first' => 'Move to start of row on mobile',
	'gridResp_hide' => 'Show on desktop only',
	'gridResp_only' => 'Show on mobile only',
	'gridSave' => 'Save',
	'gridSaveAs' => 'Save as...',
	'gridSaveConfirmMessage' => 'Are you sure you want to save changes to your slide layout?',
	'gridSaveSuccessMessage' => 'This slide has been saved.',
	'gridSaveTemplate' => '',
	'gridSaveCSS' => 'Save your CSS (grid)',
	'gridSaveTemplateFile' => 'Save your layout',
	'gridSaveText' => 'Enter a name for your new layout.',
	'gridWarningSaveWithoutSlots' => 'Your layout doesn\'t have any slots. You probably only want to save a layout without slots if you are going to edit the template and CSS files manually on the file system. Are you sure you wish to proceed?',
	'gridSlot' => 'Slot|Drag to move or drag the corner to resize',
	'gridTablet' => 'Tablet',
	'gridUndo' => 'Undo'
];

foreach ($phrases as &$phrase) {
    //to do - translate phrases
    $phrase = ze\lang::phrase($phrase, true);
}



echo '
   <script src="../libs/yarn/toastr/toastr.min.js?', $v, '" type="text/javascript" charset="utf-8"></script>
   <script src="../js/tuix.wrapper.js.php?', $v, '" type="text/javascript" charset="utf-8"></script>
   <script src="../libs/manually_maintained/mit/jquery/jquery-ui.interactions.min.js?', $v, '" type="text/javascript" charset="utf-8"></script>
   <script src="../js/plugin.wrapper.js.php?', $v, '&amp;ids=', ze\module::id('zenario_abstract_fea'), '" type="text/javascript" charset="utf-8"></script>
   <script src="../modules/zenario_abstract_fea/js/form.min.js?', $v, '" type="text/javascript" charset="utf-8"></script>
   <script src="../js/grid.min.js?', $v, '" type="text/javascript" charset="utf-8"></script>
   <script src="../js/slide_designer.min.js?', $v, '" type="text/javascript" charset="utf-8"></script>
   <script src="../js/slide_designer_microtemplates.js.php?', $v, '" type="text/javascript" charset="utf-8"></script>
   <script type="text/javascript" charset="utf-8">
        _.extend(zenarioA.phrase, ', json_encode($phrases), ');
		zenarioSD.load();
		zenarioSD.draw();
   </script>';
?>
</body>
</html>