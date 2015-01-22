<?php

require '../../../adminheader.inc.php';
require 'tree_explorer.fun.php';

$cachingRestrictions = false;
$allowCaching = true;

if (get("section_id")) {
	if (get("menu_id")) {
		if ($menuArray = getMenuStructure($cachingRestrictions, get("section_id"),false,get("menu_id"),0,100,false,false,false,false,true)) {
			$levelNodesCount = array();
		
			generateMenuForJSON($menuArray, $levelNodesCount,get('sk'), get('language'));
		}
		
		$menuNode = getMenuNodeDetails(get("menu_id"),"en-gb");

		if (!issetArrayKey($menuNode,'name')) {
			$menuNode = getMenuNodeDetails(get("menu_id"),"en");
		}

		$menuNodeAttributes = getRow("menu_nodes",array("redundancy","invisible"),array("id" => get("menu_id")));
	
		$redundancy = $menuNodeAttributes['redundancy'];
		$visibility = $menuNodeAttributes['invisible'] ? "invisible" : "visible";
		
		$top = $menuNode['name'];

		$subMenuArray = false;

		if (!empty($menuArray)) {
			$subMenuArray = $menuArray;
		}

		$menuArray = array('name' => $top, 'redundancy' => $redundancy, 'visibility' => $visibility,'children' => $subMenuArray);
	} else {
		if ($menuArray = getMenuStructure($cachingRestrictions,get("section_id"),false,0,0,100,false,false,false,false,true)) {
			$levelNodesCount = array();
			
			generateMenuForJSON($menuArray, $levelNodesCount, get('sk'), get('language'));
		}
		
		$top = menuSectionName(get("section_id"));
		$menuArray = array('name' => $top, 'children' => $menuArray, "section" => true);
	}
}


echo json_encode($menuArray);

?>