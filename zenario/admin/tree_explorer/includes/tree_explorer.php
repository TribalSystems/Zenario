<?php

require CMS_ROOT . 'zenario/adminheader.inc.php';
require 'tree_explorer.fun.php';

$v = zenarioCodeVersion();

$levelNodesCount = array();

if (($_GET["type"] ?? false)=="section") {
	$parameters = "?section_id=" . ($_GET["id"] ?? false) . "&language=" . ($_GET["language"] ?? false);
	$top = "Showing menu tree in menu section \"" . menuSectionName($_GET["id"] ?? false) . "\"";

	if ($menuArray = getMenuStructure($cachingRestrictions,($_GET["id"] ?? false),false,0,0,100,false,false,true)) {
		generateMenuForJSON($menuArray, $levelNodesCount, ($_GET['og'] ?? false), ($_GET['language'] ?? false));
	}

} elseif (($_GET["type"] ?? false)=="menu_node") {
	$sectionId = getRow("menu_nodes","section_id",array("id" => ($_GET["id"] ?? false)));

	$parameters = "?section_id=" . $sectionId . "&menu_id=" . ($_GET["id"] ?? false) . "&language=" . ($_GET["language"] ?? false);

	$menuNode = getMenuNodeDetails($_GET["id"] ?? false,"en-gb");
	
	if (!issetArrayKey($menuNode,'name')) {
		$menuNode = getMenuNodeDetails($_GET["id"] ?? false,"en");
	}
	
	$top = "Showing menu tree beneath menu node \"" . $menuNode['name'] . "\"";

	if ($menuArray = getMenuStructure($cachingRestrictions,$sectionId,false,($_GET["id"] ?? false),0,100,false,false,true)) {
		generateMenuForJSON($menuArray, $levelNodesCount, ($_GET['og'] ?? false), ($_GET['language'] ?? false));
	}

} else {
	exit;
}

if ($_GET['og'] ?? false) {
	$parameters .= '&og=1';
}

$levelNodeCount = 0;

foreach ($levelNodesCount as $count) {
	if ($count>$levelNodeCount) {
		$levelNodeCount = $count;
	}
}

$nodeHeightFactor = 30;
$defaultSVGHeight = 640;

/*
$svgHeight = (($levelNodeCount * $nodeHeightFactor) > $defaultSVGHeight) ? ($levelNodeCount * $nodeHeightFactor) : $defaultSVGHeight;
*/

$svgHeight = $defaultSVGHeight;

?>