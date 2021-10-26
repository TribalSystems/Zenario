<?php
require '../adminheader.inc.php';
$getId =  $_GET['id'] ?? false;
$getPath =  $_GET['path'] ?? false;
$categoryPath = ze\link::absolute(). 'organizer.php';
if ( strpos( $getPath, 'zenario__content/panels/categories' ) !== false) {
	$sql = '
			SELECT id,name,parent_id
			FROM ' . DB_PREFIX . 'categories
			WHERE id = '.(int)$getId;

	$result = ze\sql::select($sql);
	$currentCategoryId = NULL;
	$categoryIds = [];
	while ($row = ze\sql::fetchAssoc($result)) {
		$currentCategoryId = $row['id'];
		$currentParentId = false;
		print_r ($row);
		if ($row['parent_id']) {
			$currentParentId = $row['parent_id'];
			
			while ($currentParentId) {
				$parent = ze\row::get('categories',['id','name','parent_id'],['id' => $currentParentId]);
				if ($parent) {
					$categoryIds[] = $parent['id'];
					$currentParentId = $parent['parent_id'];
				} else {
					$currentParentId = false;
				}
			}
		}
		
	}
	$categoryPath .= '#zenario__content/panels/categories';
	if (count($categoryIds) > 0) {
		krsort($categoryIds);
		$categoryIds[] = $currentCategoryId;
		$countId = count($categoryIds);

		if ($countId > 1) {
			$categoryPath .= "/";
			$iteration = 0;
			foreach($categoryIds as $categoryId) {
				$iteration++;
				//To prevent infinite loop
				if ($iteration >= 500) {
					break;
				}
				if ($iteration < $countId) {
					$categoryPath .= "item//" .$categoryId. "//";
				} elseif ($iteration == $countId) {
					$categoryPath .= $categoryId;
				} 
				
			}
			
		} 
	} else {
			$categoryPath .= "//" .$currentCategoryId;
		}
		header('Location: ' . $categoryPath);
		die();

	return '';
} else {
	$categoryPath .= '#'.$getPath;
	header('Location: ' . $categoryPath);
		die();
	return '';
}

?>