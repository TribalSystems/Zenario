<?php

require CMS_ROOT . 'zenario/adminheader.inc.php';
require 'tree_explorer.fun.php';

$v = zenarioCodeVersion();

$levelNodesCount = array();

if (get("type")=="section") {
	$parameters = "?section_id=" . get("id") . "&language=" . get("language");
	$top = "Showing menu tree in menu section \"" . menuSectionName(get("id")) . "\"";

	if ($menuArray = getMenuStructure($cachingRestrictions,get("id"),false,0,0,100,false,false,false,false,true)) {
		generateMenuForJSON($menuArray, $levelNodesCount, get('og'), get('language'));
	}

} elseif (get("type")=="menu_node") {
	$sectionId = getRow("menu_nodes","section_id",array("id" => get("id")));

	$parameters = "?section_id=" . $sectionId . "&menu_id=" . get("id") . "&language=" . get("language");

	$menuNode = getMenuNodeDetails(get("id"),"en-gb");
	
	if (!issetArrayKey($menuNode,'name')) {
		$menuNode = getMenuNodeDetails(get("id"),"en");
	}
	
	$top = "Showing menu tree beneath menu node \"" . $menuNode['name'] . "\"";

	if ($menuArray = getMenuStructure($cachingRestrictions,$sectionId,false,get("id"),0,100,false,false,false,false,true)) {
		generateMenuForJSON($menuArray, $levelNodesCount, get('og'), get('language'));
	}

} else {
	exit;
}

if (get('og')) {
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