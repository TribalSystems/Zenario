<?php


if (!empty($_REQUEST['compress']) && !empty($_REQUEST['data'])) {
	require '../../basicheader.inc.php';
	echo strtr(base64_encode(
			gzcompress($_REQUEST['data'])
		), ' +/=', '~-_,');
	exit;

} elseif (!empty($_POST['save']) || !empty($_POST['saveas'])) {
	require '../../adminheader.inc.php';
	ze\priv::exitIfNot('_PRIV_EDIT_TEMPLATE');

} elseif (!empty($_REQUEST['zip'])) {
	require '../../adminheader.inc.php';
	ze\priv::exitIfNot('_PRIV_VIEW_TEMPLATE');

} elseif (!empty($_GET['thumbnail'])) {

	require '../../basicheader.inc.php';

	//If a checksum was given, we can cache this file
	if (!empty($_GET['checksum'])) {
		$ETag = 'zenario-layout_thumbnail-'. $_SERVER['HTTP_HOST']. '-'. preg_replace('@[^\w\.-]@', '', http_build_query($_GET));
		ze\cache::useBrowserCache($ETag);
	}
 	
	require CMS_ROOT. 'zenario/adminheader.inc.php';

} else {
	require '../../visitorheader.inc.php';
}

$html = $css = '';
$layoutId = 0;
$data = false;
if (!empty($_REQUEST['data'])) {
	$data = json_decode($_REQUEST['data'], true);

} elseif (!empty($_REQUEST['cdata'])) {
	if (($data = strtr($_REQUEST['cdata'], '~-_,', ' +/='))
	 && ($data = base64_decode($data))
	 && ($data = gzuncompress($data))) {
		$data = json_decode($data, true);
	}

} elseif (!empty($_REQUEST['loadDataFromLayout'])) {
	if (!empty($_REQUEST['id'])) {
		$data = ze\gridAdm::getLayoutData($_REQUEST['id']);
	} else {
		$data = ze\gridAdm::getLayoutData($_REQUEST['loadDataFromLayout']);
	}
}

if (is_array($data) && ze\gridAdm::validateData($data)) {

 	//Save a Skin or a Template file to the filesystem
	if (($_POST['save'] ?? false) && ($_POST['saveas'] ?? false)) {
		exit;
	}
	if (!empty($_POST['save']) || !empty($_POST['saveas'])) {
		header('Content-Type: text/javascript; charset=UTF-8');
		$a = [];
		$preview = empty($_POST['confirm']);
		$layoutName = false;
		
		//Do some validation on the Template file before trying to save
		if ($_POST['saveas'] ?? false) {
			if (!($_POST['layoutName'] ?? false)) {
				$a['error'] = ze\admin::phrase('Please enter a name for your Layout.');
			
			} elseif (($_POST['saveas'] ?? false) && ze\row::exists('layouts', ['name' => ($_POST['layoutName'] ?? false)])) {
				$a['error'] = ze\admin::phrase('A Layout with that name already exists. Please enter a different name.');
			
			} else {
				$layoutName = $_POST['layoutName'] ?? false;
			}
			
			$layoutId = $_REQUEST['layoutId'] ?? false;
			
		} else {
			if ((!$layoutId = $_REQUEST['layoutId'] ?? false)
			 || (!$layout = ze\content::layoutDetails($layoutId))) {
				echo ze\admin::phrase('Could not save layout.');
				exit;
			}
		}

		$fileName = ze\layoutAdm::codeName($layoutId);
		
		//Not all of the validation above is relevant when previewing what will happen
		if ($preview) {
			unset($a['error']);
		}
		
		if (empty($a)) {
			
			//Attempt to save, and report on what happened
			$slots = [];
			
			if (empty($a)) {
				$a['layoutId'] = (int) $layoutId;
				
				if ($preview) {
					$a['message'] = ze\admin::phrase('Are you sure you wish to modify layout [[layout]]?',['layout' => $fileName]);
					if ($_REQUEST['layoutId'] ?? false) {
						$a['oldLayoutName'] = ze\row::get('layouts', 'name', ($_REQUEST['layoutId'] ?? false));
					} 
				} else {
					$a['success'] = '';
					if ($_POST['save'] ?? false) {
						
						$dataDetails['json_data'] = $data;
						$dataDetails['json_data_hash'] = ze::hash64(json_encode($data), 8);
						
						ze\row::update('layouts', $dataDetails, $layoutId);
						$a['success'] .= ze\admin::phrase('Your layout has been saved.');
					}
					
					$renameSlotsInDatabase = true;
					//If using the "Save As" option, create a new layout
					if ($_POST['saveas'] ?? false) {
						
						if (!$layoutId
						 || !($submission = ze\row::get('layouts', true, $layoutId))) {
							//If we're not copying a layout, set some default options
							$submission = [];
							$submission['content_type'] = 'html';
							
							//Get the default Skin for this Template Family
							$submission['skin_id'] = 1;
							
							//If we're making a layout from scratch and are not copying a layout,
							//there's no need to check for renamed slots
							$renameSlotsInDatabase = false;
						}
						if ($layoutId) {
							//If using "Save a copy" from grid maker remember the old id
							$oldLayoutId = $layoutId;
						}
						
						
						$submission['name'] = $layoutName;
						
						$submission['json_data'] = $data;
						$submission['json_data_hash'] = ze::hash64(json_encode($data), 8);
				
						$a['layoutId'] = false;
						ze\layoutAdm::save($submission, $a['layoutId']);

						$layout = ze\content::layoutDetails($a['layoutId']);

						$a['success'] .= ze\admin::phrase('Your layout has been created.');
					}
						
					
					if (!empty($layout)) {
						
						//Look for any renamed slots
						if ($renameSlotsInDatabase) {
						
							//If this is from an existing Layout, check what the slot names originally were and what they are now.
							$newNames = [];
							$oldToNewNames = [];
							ze\gridAdm::checkForRenamedSlots($data, $newNames, $oldToNewNames);
						
							foreach ($oldToNewNames as $oldName => $newName) {
								//Try to catch the case where two slots have their names switched.
								//Don't change the data in the database if this has happened.
								if (empty($oldToNewNames[$newName])
								 && !ze\row::exists(
										'layout_slot_link',
										[
											'layout_id' => $layout['layout_id'],
											'slot_name' => $newName]
								)) {
									//Switch the slot names in the system
									$sql = "
										UPDATE IGNORE ".  DB_PREFIX. "plugin_layout_link
										SET slot_name = '". ze\escape::asciiInSQL($newName). "'
										WHERE slot_name = '". ze\escape::asciiInSQL($oldName). "'
										  AND layout_id = ". (int) $layout['layout_id'];
									ze\sql::update($sql);
								
									$sql = "
										UPDATE IGNORE ".  DB_PREFIX. "layout_slot_link
										SET slot_name = '". ze\escape::asciiInSQL($newName). "'
										WHERE slot_name = '". ze\escape::asciiInSQL($oldName). "'
										  AND layout_id = ". (int) $layout['layout_id'];
									ze\sql::update($sql);
								
									$sql = "
										UPDATE IGNORE ".  DB_PREFIX. "content_item_versions AS v
										INNER JOIN ".  DB_PREFIX. "plugin_instances AS pi
										   ON pi.content_id = v.id
										  AND pi.content_type = v.type
										  AND pi.content_version = v.version
										SET pi.slot_name = '". ze\escape::asciiInSQL($newName). "'
										WHERE pi.slot_name = '". ze\escape::asciiInSQL($oldName). "'
										  AND v.layout_id = ". (int) $layout['layout_id'];
									ze\sql::update($sql);
								
									$sql = "
										UPDATE IGNORE ".  DB_PREFIX. "content_item_versions AS v
										INNER JOIN ".  DB_PREFIX. "plugin_item_link AS pil
										   ON pil.content_id = v.id
										  AND pil.content_type = v.type
										  AND pil.content_version = v.version
										SET pil.slot_name = '". ze\escape::asciiInSQL($newName). "'
										WHERE pil.slot_name = '". ze\escape::asciiInSQL($oldName). "'
										  AND v.layout_id = ". (int) $layout['layout_id'];
									ze\sql::update($sql);
								}
							}
						}
					
						//Update the new slots in the DB
						ze\gridAdm::updateMetaInfoInDB($data, $layout);
					}
				}
			}
		}
		
		ze\skinAdm::checkForChangesInFiles($runInProductionMode = true, $forceScan = true);
		//If saving a copy keep the old Id selected in Organizer panel
		if (isset($oldLayoutId)){
			$a['layoutId'] = $oldLayoutId;
			
		}
		echo json_encode($a);
		exit;
	
	} elseif ($_GET['thumbnail'] ?? false) {
		ze\gridAdm::generateThumbnail($data, ($_GET['highlightSlot'] ?? false), ($_GET['width'] ?? false), ($_GET['height'] ?? false));
		exit;
	
	} elseif (!empty($_REQUEST['zip'])) {
		$slots = [];
 		$status = ze\gridAdm::generateDirectory($data, $slots, $writeToFS = false);
 		if (ze::isError($status)) {
 			echo ze\admin::phrase($status);
 		} else {
			header('Content-Type: application/zip; charset=UTF-8');
			header('Location: '. ze\link::absolute(). $status);
 		}
		exit;
 	
 	} else {
		
		$imgBg = 'grid_bg.php?fluid='. (int) $data['fluid']. '&gColWidth='. (int) $data['gColWidth']. '&minWidth='. (int) $data['minWidth']. '&maxWidth='. (int) $data['maxWidth']. '&gCols='. (int) $data['gCols']. '&gGutter='. (float) $data['gGutter'];
		
		if ($data['mirror']) {
			$imgBg .= '&gGutterLeftEdge='. (float) $data['gGutterRightEdge']. '&gGutterRightEdge='. (float) $data['gGutterLeftEdge'];
		} else {
			$imgBg .= '&gGutterLeftEdge='. (float) $data['gGutterLeftEdge']. '&gGutterRightEdge='. (float) $data['gGutterRightEdge'];
		}
		
		
		if ($_REQUEST['image'] ?? false) {
			header('Location: '. $imgBg. '&save=1');
			exit;
		}
	
		
		if (($_REQUEST['html'] ?? false) || !($_REQUEST['css'] ?? false)) {
			$slots = [];
			ze\gridAdm::generateHTML($html, $data, $slots);
		}
		if (($_REQUEST['css'] ?? false) || !($_REQUEST['html'] ?? false)) {
			ze\gridAdm::generateCSS($css, $data);
		}
		
		if ($_REQUEST['css'] ?? false) {
			if ($_REQUEST['download'] ?? false) {
				header('Content-Type: text/css; charset=UTF-8');
				header('Content-Disposition: attachment; filename="'. ze\gridAdm::calcSkinFileName($data). '.css"');
			} else {
				header('Content-Type: text/html; charset=UTF-8');
			}
			
			if ($_REQUEST['copy'] ?? false) {
				echo '<textarea>', htmlspecialchars($css), '</textarea>';
			} else {
				echo $css;
			}
			exit;
		
		} elseif ($_REQUEST['html'] ?? false) {
			
			if ($_REQUEST['download'] ?? false) {
				header('Content-Type: application/x-httpd-php; charset=UTF-8');
				header('Content-Disposition: attachment; filename="'. ze\gridAdm::calcTemplateFileName($data). '.tpl.php"');
			} else {
				header('Content-Type: text/html; charset=UTF-8');
			}
			
			if ($_REQUEST['copy'] ?? false) {
				echo '<textarea>', htmlspecialchars($html), '</textarea>';
			} else {
				echo $html;
			}
			exit;
		
		} else {
			header('Content-Type: text/html; charset=UTF-8');
			
			echo
			'<!DOCTYPE HTML>
			<html>
				<head>
					<title>', ('Grid Preview'), '</title>
					<link rel="stylesheet" type="text/css" href="../../styles/grid.min.css" media="screen" />
					<link rel="stylesheet" type="text/css" href="../../styles/admin_grid_maker.min.css" media="screen" />
					<style type="text/css">';
			
			echo $css;
			
			echo '
				div.main_container_preview {
					background-image: url('. $imgBg. ') !important;
				}';
			
			
			echo '
					</style>
				</head>',
				
				ze\content::pageBody('iframe', '
					onload="
						if (self.parent && self.parent.zenarioGM) {
							self.parent.zenarioGM.resizePreview();
						} else
						if (window.opener && window.opener.zenarioGM) {
							window.opener.zenarioGM.resizePreview();
						}
					"
				'),
				
				'<script type="text/javascript">
					window.zenarioL.init = function() {
						//...
					};
				</script>';
			
			
		
			
			$previewBG = '<div class="main_container_preview zenario_grid_bg_preview container container_';
			
			echo
				str_replace(
					[
						'<div class="container container_',
						'<div class="container-fluid container_'
					],
					[
						'<div class="main_container_preview zenario_grid_bg_preview container container_',
						'<div class="main_container_preview zenario_grid_bg_preview container-fluid container_'
					],
					str_replace(
						"<!--php ze\\plugin::slot('",
						'<div class="zenario_grid_border">',
						str_replace(
							[
								"', 'grid'); -->",
								"', 'outside_of_grid'); -->",
								"'); -->"
							],
							'</div>',
							str_replace(
								'<'. '?',
								'<!--',
								str_replace(
									'?'. '>',
									'-->', 
									$html
					)))));
			
			echo
				'</body>
			</html>';	
		}
	}

} elseif (!empty($_GET['thumbnail'])) {
	header('location: '. ze\link::absolute(). 'zenario/admin/images/icon-layout-on-state-v2.svg');

} else {
	echo ze\admin::phrase('This grid design is invalid.');
}