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
	<title>JavaScript function short names</title>';

$prefix = '../../';
ze\content::pageHead($prefix);

ze\skinAdm::checkForChangesInFiles($runInProductionMode = true);
$v = ze\db::codeVersion();

echo '
<style type="text/css">
	html, body {
		height: 100%;
		overflow: hidden;
	}

	textarea {
		width: 100%;
		height: 95%;
	}
</style>

</head>';
ze\content::pageBody();
ze\content::pageFoot($prefix, false, $includeOrganizer = true, $includeAdminToolbar = false);

echo '
<script type="text/javascript">
	zenario.enc(-1, \'zenario_plugin_nest\', 1);
</script>
<script type="text/javascript" src="../../modules/zenario_plugin_nest/js/plugin.min.js?v=', $v, '"></script>
<script type="text/javascript" src="../../js/admin_grid_maker.min.js?v=', $v, '"></script>
<script type="text/javascript" src="../../js/dev_short_name_tools.min.js?v=', $v, '"></script>';
?>

<textarea id="output"></textarea>
<script type="text/javascript">
	snTools.reviewShortNames();
</script>
</body>
</html>