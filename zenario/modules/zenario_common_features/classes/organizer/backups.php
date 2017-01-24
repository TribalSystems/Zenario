<?php
/*
 * Copyright (c) 2017, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


class zenario_common_features__organizer__backups extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__administration/panels/backups') return;
		
		if ($errorsAndWarnings = initialiseBackupFunctions(true)) {
			$panel['no_items_message'] = '';
			foreach ($errorsAndWarnings as $errorOrWarning) {
				$panel['no_items_message'] .= $errorOrWarning . '<br />';
			}
			
			$panel['no_items_message'] = str_replace('<br />', "\n", $panel['no_items_message']);
			$panel['collection_buttons'] = false;
			return;
		}
		
		if (file_exists($dirpath = setting('backup_dir'))) {
			$panel['items'] = array();
			foreach (scandir($dirpath) as $i => $file) {
				if (is_file($dirpath. '/'. $file) && substr($file, 0, 1) != '.') {
					$panel['items'][encodeItemIdForOrganizer($file)] = array('filename' => $file, 'size' => filesize($dirpath. '/'. $file));
				}
			}
		}
		
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__administration/panels/backups') return;
		
		//Check to see if we can proceed
		if ($errors = initialiseBackupFunctions(false)) {
			exit;
		}
		
		if ($ids) {
			$filename = setting('backup_dir') . '/'. decodeItemIdForOrganizer($ids);
		}
		
		if (post('create') && checkPriv('_PRIV_BACKUP_SITE')) {
			//Create a new file in the backup directory, and write the backup into it
			$backupPath = setting('backup_dir'). '/' . ($fileName = generateFilenameForBackups());
			
			$g = gzopen($backupPath, 'wb');
			createDatabaseBackupScript($g);
			gzclose($g);
			
			@chmod($backupPath, 0666);
			
			return encodeItemIdForOrganizer($fileName);
		
		} elseif (post('delete') && checkPriv('_PRIV_RESTORE_SITE')) {
			unlink($filename);
		
		} elseif (post('upload') && checkPriv('_PRIV_BACKUP_SITE')) {
			
			$filename = $_FILES['Filedata']['name'];
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if (!in_array($ext, array('sql', 'gz'))) {
				echo '<!--Message_Type:Error-->Only .sql or .gz files can be uploaded as backups';
			} elseif (file_exists(setting('backup_dir') . '/'. $_FILES['Filedata']['name'])) {
				echo '<!--Message_Type:Error-->A backup with the same name already exists';
			} elseif (move_uploaded_file($_FILES['Filedata']['tmp_name'], setting('backup_dir') . '/'. $_FILES['Filedata']['name'])) {
				echo '<!--Message_Type:Success-->Successfully uploaded backup';
			} else {
				echo '<!--Message_Type:Error-->Unable to upload backup';
			}
		
		} elseif (post('restore') && checkPriv('_PRIV_RESTORE_SITE')) {
			//Restore a backup from the file system
			$failures = array();
			if (restoreDatabaseFromBackup(
					$filename,
					//Attempt to check whether gzip compression has been used, or if this is a plain sql file
					strtolower(substr($filename, -3)) != '.gz',
					DB_NAME_PREFIX, $failures
			)) {
				echo '<!--Reload_Organizer-->';
			} else {
				foreach ($failures as $text) {
					echo $text;
				}
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if ($path != 'zenario__administration/panels/backups') return;
		
		//Functionality for Downloading Backups
		
		//Check permissions for downloading backups
		exitIfNotCheckPriv('_PRIV_BACKUP_SITE');
		
		//Check to see if we can proceed
		if ($errors = initialiseBackupFunctions(false)) {
			foreach($errors as $error) {
				echo $error, '<br />';
			}
			exit;
		}
		
		
		//Offer a backup of the current state of the site for download 
		if (!$ids) {
		
			//Create a gz file in the temp directory...
			$filepath = tempnam(sys_get_temp_dir(), 'tmpfiletodownload');
			
			//...write the backup into it...
			$g = gzopen($filepath, 'wb');
			createDatabaseBackupScript($g);
			gzclose($g);
		
			//...and finally offer it for download
			header('Content-Disposition: attachment; filename="'. generateFilenameForBackups(). '"');
			header('Content-Length: '. filesize($filepath)); 
			readfile($filepath);
			
			//Remove the file from the temp directory
			@unlink($filepath);
		
		
		//Offer one of the previously saved backups for download	
		} else {
			//Add some security to stop the user putting nasty things into their requested filename
			//I'm doing this by stripping out anything not in the usual backup filename format
			if ($ids) {
				$filename = decodeItemIdForOrganizer($ids);
				if (preg_match('/[^a-zA-Z0-9\._-]/', $filename)) {
					exit;
				}
			}
			
			$filepath = setting('backup_dir'). '/'. $filename;
			
			//Make this page into a download
			header('Content-Disposition: attachment; filename="'. $filename. '"');
			header('Content-Length: '. filesize($filepath)); 
			
			readfile($filepath);
		
		}
	}
}