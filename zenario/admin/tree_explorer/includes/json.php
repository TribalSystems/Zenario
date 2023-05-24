<?php

require '../../../adminheader.inc.php';
require 'tree_explorer.fun.php';

$cachingRestrictions = 0;
$allowCaching = true;

if ($_GET["section_id"] ?? false) {
	if ($_GET["menu_id"] ?? false) {
		if ($menuArray = ze\menu::getStructure($cachingRestrictions, ($_GET["section_id"] ?? false),false,($_GET["menu_id"] ?? false),0,100,false,false,false,true)) {
			$levelNodesCount = [];
		
			generateMenuForJSON($menuArray, $levelNodesCount,ze::get('sk'), ze::get('language'));
		}
		
		$menuNode = ze\menu::details($_GET["menu_id"] ?? false,"en-gb");

		if (!ze\ray::issetArrayKey($menuNode,'name')) {
			$menuNode = ze\menu::details($_GET["menu_id"] ?? false,"en");
		}

		$menuNodeAttributes = ze\row::get("menu_nodes",["redundancy","invisible"],["id" => ($_GET["menu_id"] ?? false)]);
	
		$redundancy = $menuNodeAttributes['redundancy'];
		$visibility = $menuNodeAttributes['invisible'] ? "invisible" : "visible";
		
		$top = $menuNode['name'];

		$subMenuArray = false;

		if (!empty($menuArray)) {
			$subMenuArray = $menuArray;
		}

		$menuArray = ['name' => $top, 'redundancy' => $redundancy, 'visibility' => $visibility,'children' => $subMenuArray];
	} else {
		if ($menuArray = ze\menu::getStructure($cachingRestrictions,($_GET["section_id"] ?? false),false,0,0,100,false,false,false,true)) {
			$levelNodesCount = [];
			
			generateMenuForJSON($menuArray, $levelNodesCount, ze::get('sk'), ze::get('language'));
		}
		
		$top = ze\menu::sectionName($_GET["section_id"] ?? false);
		$menuArray = ['name' => $top, 'children' => $menuArray, "section" => true];
	}
}


echo json_encode($menuArray);

?>