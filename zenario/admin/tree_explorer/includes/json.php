<?php

require '../../../adminheader.inc.php';
require 'tree_explorer.fun.php';

$cachingRestrictions = false;
$allowCaching = true;

if ($_GET["section_id"] ?? false) {
	if ($_GET["menu_id"] ?? false) {
		if ($menuArray = getMenuStructure($cachingRestrictions, ($_GET["section_id"] ?? false),false,($_GET["menu_id"] ?? false),0,100,false,false,false,true)) {
			$levelNodesCount = array();
		
			generateMenuForJSON($menuArray, $levelNodesCount,($_GET['sk'] ?? false), ($_GET['language'] ?? false));
		}
		
		$menuNode = getMenuNodeDetails($_GET["menu_id"] ?? false,"en-gb");

		if (!issetArrayKey($menuNode,'name')) {
			$menuNode = getMenuNodeDetails($_GET["menu_id"] ?? false,"en");
		}

		$menuNodeAttributes = getRow("menu_nodes",array("redundancy","invisible"),array("id" => ($_GET["menu_id"] ?? false)));
	
		$redundancy = $menuNodeAttributes['redundancy'];
		$visibility = $menuNodeAttributes['invisible'] ? "invisible" : "visible";
		
		$top = $menuNode['name'];

		$subMenuArray = false;

		if (!empty($menuArray)) {
			$subMenuArray = $menuArray;
		}

		$menuArray = array('name' => $top, 'redundancy' => $redundancy, 'visibility' => $visibility,'children' => $subMenuArray);
	} else {
		if ($menuArray = getMenuStructure($cachingRestrictions,($_GET["section_id"] ?? false),false,0,0,100,false,false,false,true)) {
			$levelNodesCount = array();
			
			generateMenuForJSON($menuArray, $levelNodesCount, ($_GET['sk'] ?? false), ($_GET['language'] ?? false));
		}
		
		$top = menuSectionName($_GET["section_id"] ?? false);
		$menuArray = array('name' => $top, 'children' => $menuArray, "section" => true);
	}
}


echo json_encode($menuArray);

?>