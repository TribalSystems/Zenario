<?php

require '../../adminheader.inc.php';
if (!adminSetting('show_dev_tools')) {
	exit;
}

switch (get('mode')) {
	case 'zenarioAB';
		$type = 'admin_boxes';
		break;
	case 'zenarioAT';
		$type = 'admin_toolbar';
		break;
	case 'zenarioO';
		$type = 'organizer';
	default:
		$type = 'visitor';
		break;
}

if (!empty($_POST['load_tuix_files']) && $data = json_decode($_POST['load_tuix_files'], true)) {
	if (!empty($data) && is_array($data)) {
		foreach ($data as $paths => &$dataForFile) {
			$paths = explode('.', $paths);
			
			
			if ((validateScreenName($module = arrayKey($paths, 0)))
			 && (validateScreenName($file = arrayKey($paths, 1)))
			 && (validateScreenName($ext = arrayKey($paths, 2)))
			 && ($path = moduleDir($module, 'tuix/'. $type. '/'. $file. '.'. $ext, true))) {
			
				if ($tags = zenarioReadTUIXFile($path)) {
					$dataForFile = array(
						'tags' => $tags,
						'source' => file_get_contents($path));
				}	
			}
		}
		
		header('Content-Type: text/javascript; charset=UTF-8');
		jsonEncodeForceObject($data);
	}
}
