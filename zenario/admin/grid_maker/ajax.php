<?php


require '../../adminheader.inc.php';
ze\priv::exitIfNot('_PRIV_EDIT_TEMPLATE');

$html = $css = '';
$mode = ze::post('mode');
$saveAs = ze::post('saveas') && $mode == 'body';
$layoutId = ze::post('layoutId');
$layoutName = ze::post('layoutName');
$confirmed = ze::post('confirm');
$data = ze::post('data');
$originalLayoutId = false;

if (!$data
 || !($data = json_decode($_REQUEST['data'], true))
 || !(is_array($data))
 || !(ze\gridAdm::checkData($data))) {
	echo ze\admin::phrase('This grid design is invalid.');
	exit;
}

header('Content-Type: text/javascript; charset=UTF-8');
$a = [];

//Do some validation on the Template file before trying to save
if ($saveAs) {
	if (!$layoutName) {
		$a['error'] = ze\admin::phrase('Please enter a name for your Layout.');
	
	} elseif ($saveAs && ze\row::exists('layouts', ['name' => $layoutName])) {
		$a['error'] = ze\admin::phrase('A Layout with that name already exists. Please enter a different name.');
	}
}


if (empty($a)) {
	
	//Attempt to save, and report on what happened
	if (empty($a)) {
		
		//Track if the admin renamed a slot
		if ($confirmed) {
			$oldToNewNames = [];
			ze\gridAdm::checkForRenamedSlots($data, $oldToNewNames);
		}
		
		switch ($mode) {
			case 'head':
				ze\priv::exitIfNot('_PRIV_EDIT_SITEWIDE');
				
				if (!$confirmed) {
					$a['message'] = ze\admin::phrase('Are you sure you wish to modify the site-wide header?');
				
				} else {
					
					ze\gridAdm::trimData($data);
					ze\gridAdm::updateHeaderMetaInfoInDB($data);
					
					ze\gridAdm::updateHeadAndFootInAllLayouts($oldToNewNames);
					
					$a['success'] = ze\admin::phrase('Site-wide header updated.');
				}
				
				break;
			
			
			case 'foot':
				ze\priv::exitIfNot('_PRIV_EDIT_SITEWIDE');
				
				if (!$confirmed) {
					$a['message'] = ze\admin::phrase('Are you sure you wish to modify the site-wide footer?');
				
				} else {
					//Only save cell information for the footer, as the meta info will already be included in the header.
					$data = ['cells' => $data['cells'] ?? []];
					ze\gridAdm::trimData($data);
					ze\row::set('layout_head_and_foot', ['foot_json_data' => $data], ['for' => 'sitewide']);
					
					ze\gridAdm::updateHeadAndFootInAllLayouts($oldToNewNames);
					
					$a['success'] = ze\admin::phrase('Site-wide footer updated.');
				}
				
				break;
			
			
			case 'body':
				$a['layoutId'] = (int) $layoutId;
		
				if (!$confirmed) {
					$fileName = ze\layoutAdm::codeName($layoutId);
					$a['message'] = ze\admin::phrase('Are you sure you wish to modify layout [[layout]]?',['layout' => $fileName]);
					
					if ($layoutId) {
						$a['oldLayoutName'] = ze\row::get('layouts', 'name', $layoutId);
					} 
		
				} else {
					//If using the "Save As" option, create a new layout
					if ($saveAs) {
				
						if (!$layoutId
						 || !($layoutDetails = ze\row::get('layouts', true, $layoutId))) {
							//If we're not copying a layout, set some default options
							$layoutDetails = [];
							$layoutDetails['content_type'] = 'html';
							$layoutDetails['skin_id'] = ze\layoutAdm::mostCommonSkinId();
							
							//If we're making a layout from scratch and are not copying a layout,
							//there's no need to check for renamed slots
							$oldToNewNames = [];
						}
						
						if ($layoutId) {
							//If using "Save a copy" from Gridmaker remember the old id
							$originalLayoutId = $layoutId;
						}
				
				
						$layoutDetails['name'] = $layoutName;
				
						$newLayoutId = false;
						ze\layoutAdm::save($layoutDetails, $newLayoutId, $layoutId);
						$layoutId = $a['layoutId'] = $newLayoutId;
						
						ze\gridAdm::updateHeadAndFootAndSaveLayoutData($layoutId, $data, $oldToNewNames);
						$a['success'] = ze\admin::phrase('Your layout has been created.');
			
					} else {
						ze\gridAdm::updateHeadAndFootAndSaveLayoutData($layoutId, $data, $oldToNewNames);
						$a['success'] = ze\admin::phrase('Your layout has been saved.');
					}
				}
				
				break;
		}
	}
}

if ($confirmed) {
	ze\skinAdm::checkForChangesInFiles($runInProductionMode = true, $forceScan = true);
}

//If saving a copy keep the old Id selected in Organizer panel
if ($originalLayoutId) {
	$a['layoutId'] = $originalLayoutId;
	
}
echo json_encode($a);
exit;