<?php

function generateMenuForJSON (&$array, &$nodesCount, $sk = false, $languageId = false, $recurseCount = 20) {
	if (--$recurseCount && is_array($array)) {
		$array = array_values($array);
		
		foreach($array as &$menu) {
			
			if (!isset($nodesCount[(20-$recurseCount)])) {
				$nodesCount[(20-$recurseCount)] = 0;
			}
			
			$nodesCount[(20-$recurseCount)]++;			
			
			foreach($menu as $k => $v) {
				switch ($k) {
					case 'mID':
						$menuNodeAttributes = getRow("menu_nodes",array("redundancy","invisible","target_loc"),array("id" => $v));
					
						$menu['redundancy'] = $menuNodeAttributes['redundancy'];
						$menu['visibility'] = $menuNodeAttributes['invisible'] ? "invisible" : "visible";
						$menu['target_loc'] = $menuNodeAttributes['target_loc'];
						
						if ($sk) {
							$menu['storekeeper_href'] = getMenuItemStorekeeperDeepLink($v, $languageId);
						
						} elseif ($menu['cID']) {
							$menu['content_href'] = linkToItem($menu['cID'],$menu['cType'],true);
						}
					
						break;
					case 'children':
						if (is_array($menu[$k])) {
							generateMenuForJSON($menu[$k], $nodesCount, $sk, $languageId, $recurseCount);
						} else {
							unset($menu[$k]);
						}
						
						break;
					
					case 'name':
					case 'hide_private_item':
					case 'content_href':
					case 'storekeeper_href':
					case 'redundancy':
					case 'visibility':
					case 'target_loc':
						break;
					
					default:
						unset($menu[$k]);
				}
			}
		}
	}
}

?>