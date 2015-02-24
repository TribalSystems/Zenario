<?php


if (!empty($_REQUEST['compress']) && !empty($_REQUEST['data'])) {
	require '../../cacheheader.inc.php';
	echo strtr(base64_encode(
			gzcompress($_REQUEST['data'])
		), ' +/=', '~-_,');
	exit;

} elseif (!empty($_POST['save']) || !empty($_POST['saveas'])) {
	require '../../adminheader.inc.php';
	require CMS_ROOT. 'zenario/admin/grid_maker/grid_maker.inc.php';
	exitIfNotCheckPriv('_PRIV_EDIT_TEMPLATE_FAMILY');

} elseif (!empty($_REQUEST['zip'])) {
	require '../../adminheader.inc.php';
	require CMS_ROOT. 'zenario/admin/grid_maker/grid_maker.inc.php';
	exitIfNotCheckPriv('_PRIV_VIEW_TEMPLATE_FAMILY');

} elseif (!empty($_GET['thumbnail'])) {

	require '../../cacheheader.inc.php';

	//If a checksum was given, we can cache this file
	if (!empty($_GET['checksum'])) {
		$ETag = 'zenario-layout_thumbnail-'. $_SERVER['HTTP_HOST']. '-'. http_build_query($_GET);
		useCache($ETag);
	}
	useGZIP();
	
	require CMS_ROOT. 'zenario/adminheader.inc.php';
	require CMS_ROOT. 'zenario/admin/grid_maker/grid_maker.inc.php';

} else {
	require '../../visitorheader.inc.php';
	require CMS_ROOT. 'zenario/admin/grid_maker/grid_maker.inc.php';
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
		$data = zenario_grid_maker::readLayoutCode($_REQUEST['id']);
	} else {
		$data = zenario_grid_maker::readLayoutCode($_REQUEST['loadDataFromLayout']);
	}
}

if (is_array($data) && zenario_grid_maker::validateData($data)) {
 	
 	//Save a Skin or a Template file to the filesystem
	if (post('save') && post('saveas')) {
		exit;
	}
	if (post('save') || post('saveas')) {
		header('Content-Type: text/javascript; charset=UTF-8');
		$a = array();
		$preview = !post('confirm');
		$layoutName = false;
		$fileBaseName = false; 
		
		//Do some validation on the Template file before trying to save
		if (post('saveas')) {
			if (!post('layoutName')) {
				$a['error'] = adminPhrase('Please enter a name for your Layout.');
			
			} elseif (post('saveas') && checkRowExists('layouts', array('name' => post('layoutName')))) {
				$a['error'] = adminPhrase('A Layout with that name already exists. Please enter a different name.');
			
			} else {
				$layoutName = post('layoutName');
				$fileBaseName = generateLayoutFileBaseName($layoutName);
			}
			
			$layoutId = request('layoutId');
			
		} else {
			if ((!$layoutId = request('layoutId'))
			 || (!$layout = getTemplateDetails($layoutId))
			 || (!$fileBaseName = $layout['file_base_name'])) {
				echo adminPhrase('Could not save layout.');
				exit;
			}
		}			
		$fileName = $fileBaseName. '.tpl.php';
		
		//Not all of the validation above is relevant when previewing what will happen
		if ($preview) {
			unset($a['error']);
		}
		
		if (empty($a)) {
			
			//If this is from an existing Layout, check what the slot names originally were and what they are now.
			$oldToNewNames = array();
			zenario_grid_maker::checkForRenamedSlots($data, $oldToNewNames);
			
			
			//Attempt to save, and report on what happened
			$status =
				zenario_grid_maker::generateDirectory($data, $writeToFS = true, $preview, $fileBaseName);
			
			if (isError($status)) {
				echo adminPhrase($status);
				exit;
			}
			
			if (post('save') && !$status['template_file_exists']) {
				echo adminPhrase('The template file you were trying to save has been deleted from the system. You may use "Save As" to save it as a new template file.');
				exit;
			
			} elseif (post('save') && $status['template_file_identical'] && $status['template_css_file_identical']) {
				$a['success'] = adminPhrase('Your template file has previously been saved to the filesystem.');
			
			} elseif (post('saveas') && $status['template_file_exists']) {
				$a['error'] = adminPhrase('A template file "[[fileName]]" already exists. Please enter a different name.', array('fileName' => $fileName));
			}
			
			if (empty($a)) {
				$a['layoutId'] = (int) $layoutId;
				
				if ($preview) {
					
					if (request('layoutId')) {
						$a['oldLayoutName'] = getRow('layouts', 'name', request('layoutId'));
					}
					
					$a['message'] = adminPhrase('You are about to write files to your filesystem:');
					
					if (!$status['template_file_exists']) {
						$a['message'] .= "\n\n". adminPhrase('The template file "[[template_file_path]]" will be created.', $status);
					
					} elseif ($status['template_file_modified']) {
						$a['message'] .= "\n\n". adminPhrase('The template file "[[template_file_path]]" has been manually modified. If you continue, it will be overwritten and the modifications will be lost.', $status);
					
					} elseif ($status['template_file_smaller']) {
						$a['message'] .= "\n\n". adminPhrase('The template file "[[template_file_path]]" will be overwritten, adding extra slots.', $status);
					
					} elseif ($status['template_file_larger']) {
						$a['message'] .= "\n\n". adminPhrase('The template file "[[template_file_path]]" will be overwritten, removing slots.', $status);
						
						if (checkRowExists('layouts', array('family_name' => $status['family_name'], 'file_base_name' => $status['file_base_name']))
						 && checkRowExists('template_slot_link', array('family_name' => $status['family_name'], 'file_base_name' => $status['file_base_name']))) {
							$a['message'] .= "\n\n". adminPhrase('This will affect your site, as any slots that are removed will no longer be visible.');
						}
					
					} elseif (!$status['template_file_identical']) {
						$a['message'] .= "\n\n". adminPhrase('The template file "[[template_file_path]]" will be overwritten.', $status);
					}
					
					if (!$status['template_css_file_exists']) {
						$a['message'] .= "\n\n". adminPhrase('The CSS file "[[template_css_file_path]]" will be created.', $status);
					
					} elseif (!$status['template_css_file_identical']) {
						$a['message'] .= "\n\n". adminPhrase('The CSS file "[[template_css_file_path]]" will be overwritten.', $status);
					}
					
					$a['message'] .= "\n\n". adminPhrase('(Path: [[CMS_ROOT]]).', array('CMS_ROOT' => CMS_ROOT));
				
				} else {
					$a['success'] = '';
					
					if (!$status['template_file_exists']) {
						$a['success'] = adminPhrase('The template file "[[template_file_path]]" has been created.', $status);
					
					} elseif (!$status['template_file_identical']) {
						$a['success'] = adminPhrase('The template file "[[template_file_path]]" has been overwritten.', $status);
					}
					
					if (!$status['template_css_file_exists']) {
						$a['success'] .= "\n\n". adminPhrase('The CSS file "[[template_css_file_path]]" has been created.', $status);
					
					} elseif (!$status['template_css_file_identical']) {
						$a['success'] .= "\n\n". adminPhrase('The CSS file "[[template_css_file_path]]" has been overwritten.', $status);
					}
					
					$renameSlotsInDatabase = true;
					
					//If using the "Save As" option, create a new layout
					if (post('saveas')) {
						
						if (!$layoutId
						 || !($submission = getRow('layouts', true, $layoutId))) {
							//If we're not copying a layout, set some default options
							$submission = array();
							$submission['content_type'] = 'html';
							
							//Get the default Skin for this Template Family
							$submission['skin_id'] = getRow('template_families', 'skin_id', $status['family_name']);
							
							//If we're making a layout from scratch and are not copying a layout,
							//there's no need to check for renamed slots
							$renameSlotsInDatabase = false;
						}
						
						
						$submission['name'] = $layoutName;
						
						$submission['file_base_name'] = $status['file_base_name'];
						$submission['family_name'] = $status['family_name'];
						
						$a['layoutId'] = false;
						saveTemplate($submission, $a['layoutId'], $layoutId);
						$layout = getTemplateDetails($a['layoutId']);
						
						$a['success'] .= ' '. adminPhrase('Your layout has been created.');
					}
					
					//Look for any renamed slots
					if ($renameSlotsInDatabase && !empty($layout)) {
						
						foreach ($oldToNewNames as $oldName => $newName) {
							//Try to catch the case where two slots have their names switched.
							//Don't change the data in the database if this has happened.
							if (empty($oldToNewNames[$newName])
							 && !checkRowExists(
									'template_slot_link',
									array(
										'family_name' => $layout['family_name'],
										'file_base_name' => $layout['file_base_name'],
										'slot_name' => $newName)
							)) {
								//Switch the slot names in the system
								$sql = "
									UPDATE IGNORE ".  DB_NAME_PREFIX. "plugin_layout_link
									SET slot_name = '". sqlEscape($newName). "'
									WHERE slot_name = '". sqlEscape($oldName). "'
									  AND layout_id = ". (int) $layout['layout_id'];
								sqlUpdate($sql);
								
								$sql = "
									UPDATE IGNORE ".  DB_NAME_PREFIX. "template_slot_link
									SET slot_name = '". sqlEscape($newName). "'
									WHERE slot_name = '". sqlEscape($oldName). "'
									  AND family_name = '". sqlEscape($layout['family_name']). "'
									  AND file_base_name = '". sqlEscape($layout['file_base_name']). "'";
								sqlUpdate($sql);
								
								$sql = "
									UPDATE IGNORE ".  DB_NAME_PREFIX. "versions AS v
									INNER JOIN ".  DB_NAME_PREFIX. "plugin_instances AS pi
									   ON pi.content_id = v.id
									  AND pi.content_type = v.type
									  AND pi.content_version = v.version
									SET pi.slot_name = '". sqlEscape($newName). "'
									WHERE pi.slot_name = '". sqlEscape($oldName). "'
									  AND v.layout_id = ". (int) $layout['layout_id'];
								sqlUpdate($sql);
								
								$sql = "
									UPDATE IGNORE ".  DB_NAME_PREFIX. "versions AS v
									INNER JOIN ".  DB_NAME_PREFIX. "plugin_item_link AS pil
									   ON pil.content_id = v.id
									  AND pil.content_type = v.type
									  AND pil.content_version = v.version
									SET pil.slot_name = '". sqlEscape($newName). "'
									WHERE pil.slot_name = '". sqlEscape($oldName). "'
									  AND v.layout_id = ". (int) $layout['layout_id'];
								sqlUpdate($sql);
							}
						}
					}
				}
			}
		}
		
		echo json_encode($a);
		exit;
	
	} elseif (get('thumbnail')) {
		zenario_grid_maker::generateThumbnail($data, get('highlightSlot'), get('width'), get('height'));
		exit;
	
	} elseif (!empty($_REQUEST['zip'])) {
 		$status = zenario_grid_maker::generateDirectory($data, $writeToFS = false);
 		if (isError($status)) {
 			echo adminPhrase($status);
 		} else {
			header('Content-Type: application/zip; charset=UTF-8');
			header('Location: '. absCMSDirURL(). $status);
 		}
		exit;
 	
 	} else {
		
		$imgBg = 'grid_bg.php?fluid='. (int) $data['fluid']. '&gColWidth='. (int) $data['gColWidth']. '&minWidth='. (int) $data['minWidth']. '&maxWidth='. (int) $data['maxWidth']. '&gCols='. (int) $data['gCols']. '&gGutter='. (float) $data['gGutter'];
		
		if ($data['mirror']) {
			$imgBg .= '&gGutterLeftEdge='. (float) $data['gGutterRightEdge']. '&gGutterRightEdge='. (float) $data['gGutterLeftEdge'];
		} else {
			$imgBg .= '&gGutterLeftEdge='. (float) $data['gGutterLeftEdge']. '&gGutterRightEdge='. (float) $data['gGutterRightEdge'];
		}
		
		
		if (request('image')) {
			header('Location: '. $imgBg. '&save=1');
			exit;
		}
	
		
		if (request('html') || !request('css')) {
			zenario_grid_maker::generateHTML($html, $data);
		}
		if (request('css') || !request('html')) {
			zenario_grid_maker::generateCSS($css, $data);
		}
		
		if (request('css')) {
			if (request('download')) {
				header('Content-Type: text/css; charset=UTF-8');
				header('Content-Disposition: attachment; filename="'. zenario_grid_maker::calcSkinFileName($data). '.css"');
			} else {
				header('Content-Type: text/html; charset=UTF-8');
			}
			
			if (request('copy')) {
				echo '<textarea>', htmlspecialchars($css), '</textarea>';
			} else {
				echo $css;
			}
			exit;
		
		} elseif (request('html')) {
			
			if (request('download')) {
				header('Content-Type: application/x-httpd-php; charset=UTF-8');
				header('Content-Disposition: attachment; filename="'. zenario_grid_maker::calcTemplateFileName($data). '.tpl.php"');
			} else {
				header('Content-Type: text/html; charset=UTF-8');
			}
			
			if (request('copy')) {
				echo '<textarea>', htmlspecialchars($html), '</textarea>';
			} else {
				echo $html;
			}
			exit;
		
		} else {
			header('Content-Type: text/html; charset=UTF-8');
			
			//require_once(CMS_ROOT. 'zenario/adminheader.inc.php');
			//checkForChangesInCssJsAndHtmlFiles();
			//$v = ifNull(setting('css_js_version'), ZENARIO_CMS_VERSION);
			
			echo
			'<!DOCTYPE HTML>
			<html>
				<head>
					<title>', ('Grid Preview'), '</title>
					<link rel="stylesheet" type="text/css" href="../../styles/admin_grid_maker.min.css" media="screen" />
					<style type="text/css">';
			
			echo $css;
			
			echo '
				div.main_container_preview {
					background-image: url('. $imgBg. ') !important;
				}';
			
			
			echo '
					</style>
				</head>
				<body class="iframe" onload="
					if (self.parent && self.parent.zenarioG) {
						self.parent.zenarioG.resizePreview();
					} else
					if (window.opener && window.opener.zenarioG) {
						window.opener.zenarioG.resizePreview();
					}
				">';
			
			
		
			
			$previewBG = '<div class="main_container_preview zenario_grid_bg_preview container container_';
			
			echo
					str_replace(
						array(
							'<div class="container container_',
							'<div class="container-fluid container_'),
						array(
							'<div class="main_container_preview zenario_grid_bg_preview container container_',
							'<div class="main_container_preview zenario_grid_bg_preview container-fluid container_'),
						str_replace("<!--php slot('", '<div class="zenario_grid_border">', str_replace(array("', 'grid'); -->", "', 'outside_of_grid'); -->", "'); -->"), '</div>',
							str_replace('<'. '?', '<!--', str_replace('?'. '>', '-->', 
								$html
					)))));
			
			echo
				'</body>
			</html>';	
		}
	}

} else {
	echo adminPhrase('This grid design is invalid.');
}