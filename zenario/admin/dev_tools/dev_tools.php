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
	
		switch ($_GET['mode'] ?? false) {
			case 'zenarioAB';
				echo ze\admin::phrase('Dev Tools: Specification for Admin Box');
				break;
			case 'zenarioAT';
				echo ze\admin::phrase('Dev Tools: Specification for Admin Toolbar');
				break;
			case 'zenarioO';
				if (ze\ring::engToBoolean($_GET['orgMap'] ?? false)) {
					echo ze\admin::phrase('Dev Tools: Specification for Storekeeper Map');
				} else {
					echo ze\admin::phrase('Dev Tools: Specification for Storekeeper Panel');
				}
				break;
			default:
				echo ze\admin::phrase('Dev Tools: Specification for Front-End Administration');
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

echo '
<script type="text/javascript" src="../../libs/manually_maintained/apache/docson/lib/marked.js?v=', $v, '"></script>
<script type="text/javascript" src="../../libs/manually_maintained/mit/js-yaml/js-yaml.js?v=', $v, '"></script>
<script type="text/javascript" src="../../libs/manually_maintained/public_domain/tv4/tv4.js?v=', $v, '"></script>
<script type="text/javascript" src="../../libs/manually_maintained/public_domain/tv4/customised_messages.js?v=', $v, '"></script>';

/*
echo '
<script type="text/javascript">
	window.zenarioAdminHasZipPerms = ', ze\ring::engToBoolean(ze\priv::check('_PRIV_VIEW_TEMPLATE')), ';
	window.zenarioAdminHasSavePerms = ', ze\ring::engToBoolean(ze\priv::check('_PRIV_EDIT_TEMPLATE')), ';
</script>';
*/

?>


<div id="toolbar"></div>
<div id="editor"></div>
<div id="sidebar" class="sidebarEmpty">
	<div class="vscroll" id="sidebar_inner">
	</div>
</div>


<?php




switch ($_GET['mode'] ?? false) {
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


$schema = ze\tuix::readFile(CMS_ROOT. 'zenario/api/'. $schemaName. '.yaml');

//Copy the some definitions from the FAB toolkit to the FEA toolkit
//(This is a hack to save me from writing all of that out twice!)
if ($schemaName == 'fea_schema') {
	$fabSchema = ze\tuix::readFile(CMS_ROOT. 'zenario/api/admin_box_schema.yaml');
	
	$schema['additionalProperties']['properties']['tabs']['additionalProperties']['properties']['fields'] = 
		array_merge_recursive(
			$fabSchema['additionalProperties']['properties']['tabs']['additionalProperties']['properties']['fields'],
			$schema['additionalProperties']['properties']['tabs']['additionalProperties']['properties']['fields']);
	
	$schema['additionalProperties']['properties']['lovs'] = 
		array_merge_recursive(
			$fabSchema['additionalProperties']['properties']['lovs'],
			$schema['additionalProperties']['properties']['lovs']);
}

unset($schema['common_definitions']);


echo '
<script type="text/javascript" src="doc_tools.js?v=', $v, '"></script>
<script type="text/javascript" src="../../js/dev_tools.min.js?v=', $v, '"></script>
<script type="text/javascript">
	var schema = ', json_encode($schema), ';
	devTools.init(\'', ze\escape::js($_GET['mode'] ?? false), '\', \'', ze\escape::js($schemaNameForURL), '\', schema, ', ze\ring::engToBoolean($_GET['orgMap'] ?? false), ');
	var sshPath = "', ze\escape::js(ze\link::hostWithoutPort(). CMS_ROOT), '";
</script>
</body>
</html>';