<?php

require '../../adminheader.inc.php';
if (!ze\admin::setting('show_dev_tools')) {
	exit;
}

switch ($_GET['mode'] ?? false) {
	case 'zenarioAB';
		$type = 'admin_boxes';
		break;
	case 'zenarioAT';
		$type = 'admin_toolbar';
		break;
	case 'zenarioO';
		$type = 'organizer';
		break;
	default:
		$type = 'visitor';
		break;
}

if (!empty($_POST['load_tuix_files']) && $data = json_decode($_POST['load_tuix_files'], true)) {
	if (!empty($data) && is_array($data)) {
		foreach ($data as $paths => &$dataForFile) {
			$paths = explode('.', $paths);
			
			
			if ((ze\ring::validateScreenName($module = $paths[0] ?? false))
			 && (ze\ring::validateScreenName($file = $paths[1] ?? false))
			 && (ze\ring::validateScreenName($ext = $paths[2] ?? false))
			 && ($path = ze::moduleDir($module, 'tuix/'. $type. '/'. $file. '.'. $ext, true))) {
			
				if ($tags = ze\tuix::readFile($path)) {
					$dataForFile = array(
						'tags' => $tags,
						'source' => file_get_contents($path));
				}	
			}
		}
		
		header('Content-Type: text/javascript; charset=UTF-8');
		ze\ray::jsonDump($data);
	}
}
