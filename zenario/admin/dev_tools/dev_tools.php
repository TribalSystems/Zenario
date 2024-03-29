<?php

require '../../adminheader.inc.php';
if (!ze\admin::setting('show_dev_tools')) {
	exit;
}

//require CMS_ROOT. 'zenario/admin/dev_tools/dev_tools.inc.php';






echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>';
	
		switch (ze::get('mode')) {
			case 'zenarioAB':
				echo ze\admin::phrase('TUIX Inspector for an Admin Box');
				break;
			case 'zenarioAT':
				echo ze\admin::phrase('TUIX Inspector for the Admin Toolbar');
				break;
			case 'zenarioO':
				if (ze\ring::engToBoolean(ze::get('orgMap'))) {
					echo ze\admin::phrase("TUIX Inspector for Organizer's map");
				} else {
					echo ze\admin::phrase('TUIX Inspector for an Organizer panel');
				}
				break;
			default:
				echo ze\admin::phrase('TUIX Inspector for a Front-End Administration plugin');
				break;
		}

echo '
	</title>';

$prefix = '../../';
ze\content::pageHead($prefix);
$v = ze\db::codeVersion();

echo '
	<link rel="stylesheet" type="text/css" href="../../styles/dev_tools.css?v=', $v, '" media="screen"/>';


echo '</head>';
ze\content::pageBody();
ze\content::pageFoot($prefix, false, false, false);


?>


<div id="toolbar"></div>
<div id="editor"></div>
<div id="lowerbar"></div>
<div id="sidebar" class="sidebarEmpty">
	<div class="vscroll" id="sidebar_inner">
	</div>
</div>


<?php




switch (ze::get('mode')) {
	case 'zenarioAB':
		$schemaName = 
		$schemaNameForURL = 'admin_box_schema';
		break;
	
	case 'zenarioAT':
		$schemaName = 
		$schemaNameForURL = 'admin_toolbar_schema';
		break;
	
	case 'zenarioO':
		$schemaName = 
		$schemaNameForURL = 'organizer_schema';
		break;
	
	default:
		$schemaName = 'fea_schema';
		$schemaNameForURL = '';
		break;
}


$schema = ze\tuix::readFile(CMS_ROOT. $schemaPath = 'zenario/reference/'. $schemaName. '.yaml');

//Copy the some definitions from the FAB toolkit to the FEA toolkit
//(This is a hack to save me from writing all of that out twice!)
if ($schemaName == 'fea_schema') {
	$fabSchema = ze\tuix::readFile(CMS_ROOT. 'zenario/reference/admin_box_schema.yaml');
	
	unset($fabSchema['additionalProperties']['properties']['tabs']['additionalProperties']['properties']['fields']['additionalProperties']['properties']['pick_items']);
	
	$schema['additionalProperties']['properties']['tabs']['additionalProperties']['properties']['fields'] = 
		array_merge_recursive(
			$fabSchema['additionalProperties']['properties']['tabs']['additionalProperties']['properties']['fields'],
			$schema['additionalProperties']['properties']['tabs']['additionalProperties']['properties']['fields']);
	
	$schema['additionalProperties']['properties']['lovs'] = 
		array_merge_recursive(
			$fabSchema['additionalProperties']['properties']['lovs'],
			$schema['additionalProperties']['properties']['lovs']);
	
	//On Assetwolf sites, add some specific Assetwolf definitions
	if (ze\module::isRunning('assetwolf_2')) {
		$awSchema = ze\tuix::readFile(CMS_ROOT. 'zenario/reference/assetwolf_fea_schema.yaml');
		
		$schema['additionalProperties']['properties'] = 
			array_merge_recursive(
				$schema['additionalProperties']['properties'],
				$awSchema['additionalProperties']['properties']);
		
	}
	
}

unset($schema['common_definitions']);


echo '
<script type="text/javascript">
	var schema = ', json_encode($schema), ';
	var schemaPath = ', json_encode($schemaPath), ';
</script>
<script type="text/javascript" src="../../libs/manually_maintained/apache/docson/lib/marked.js?v=', $v, '"></script>
<script type="text/javascript" src="../../libs/manually_maintained/mit/js-yaml/js-yaml.js?v=', $v, '"></script>
<script type="text/javascript" src="../../libs/manually_maintained/public_domain/tv4/tv4.js?v=', $v, '"></script>
<script type="text/javascript" src="../../libs/manually_maintained/public_domain/tv4/customised_messages.js?v=', $v, '"></script>
<script type="text/javascript" src="doc_tools.js?v=', $v, '"></script>
<script type="text/javascript" src="../../js/dev_tools.min.js?v=', $v, '"></script>
<script type="text/javascript">
	devTools.init(\'', ze\escape::js(ze::get('mode')), '\', \'', ze\escape::js($schemaNameForURL), '\', schema, ', ze\ring::engToBoolean(ze::get('orgMap')), ');
</script>
</body>
</html>';