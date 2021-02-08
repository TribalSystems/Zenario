<?php
/*
 * Copyright (c) 2021, Tribal Limited
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


class zenario_common_features__organizer__backups extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__administration/panels/backups') return;
		
		if ($errorsAndWarnings = ze\dbAdm::initialiseBackupFunctions(true)) {
			$panel['no_items_message'] = '';
			foreach ($errorsAndWarnings as $errorOrWarning) {
				$panel['no_items_message'] .= $errorOrWarning . '<br />';
			}
			
			$panel['no_items_message'] = str_replace('<br />', "\n", $panel['no_items_message']);
			$panel['collection_buttons'] = false;
			return;
		}
		
		if (file_exists($dirpath = ze::setting('backup_dir'))) {
			$panel['items'] = [];
			foreach (scandir($dirpath) as $i => $file) {
				if (is_file($dirpath. '/'. $file) && substr($file, 0, 1) != '.') {
					$panel['items'][ze\ring::encodeIdForOrganizer($file)] = ['filename' => $file, 'size' => filesize($dirpath. '/'. $file)];
				}
			}
		}
		
		
		if (isset($panel['item_buttons']['restore'])) {
			
			if (\ze\dbAdm::restoreEnabled()) {
				if (\ze\db::hasGlobal() || \ze\db::hasDataArchive()) {
					$restoreMsg = '<p>'. ze\admin::phrase("This tool allows you to restore a backup of your site's local database."). '</p>';
				} else {
					$restoreMsg = '<p>'. ze\admin::phrase("This tool allows you to restore a backup of your site's database."). '</p>';
				}
				if (\ze\db::hasGlobal()) {
					$restoreMsg .= '<p>'. ze\admin::phrase("All content on the site will be replaced with the content from the database backup; all of the current local administrators, content and users on your site will be overwritten. This may take several minutes to complete."). '</p>';
				} else {
					$restoreMsg .= '<p>'. ze\admin::phrase("All content on the site will be replaced with the content from the database backup; all of the current administrators, content and users on your site will be overwritten. This may take several minutes to complete."). '</p>';
				}
				if (\ze\db::hasDataArchive()) {
					$restoreMsg .= '<p>'. ze\admin::phrase("This will not affect the contents of data archive database. You should back up and restore this separately."). '</p>';
				}
		
				$panel['item_buttons']['restore']['ajax']['confirm']['message'] = 
					$restoreMsg.
					$panel['item_buttons']['restore']['ajax']['confirm']['message'];
			
			} else {
				$panel['item_buttons']['restore']['disabled'] = true;
				$panel['item_buttons']['restore']['disabled_tooltip'] = ze\dbAdm::restoreEnabledMsg();
			}
        }
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__administration/panels/backups') return;
		
		//Check to see if we can proceed
		if ($errors = ze\dbAdm::initialiseBackupFunctions(false)) {
			exit;
		}
		
		if ($ids) {
			$filename = ze::setting('backup_dir') . '/'. ze\file::safeName(ze\ring::decodeIdForOrganizer($ids));
		}
		
		if (($_POST['delete'] ?? false) && ze\priv::check('_PRIV_RESTORE_SITE')) {
			unlink($filename);
		
		} elseif (($_POST['upload'] ?? false) && ze\priv::check('_PRIV_BACKUP_SITE')) {
			
			ze\fileAdm::exitIfUploadError(true, false, false, 'Filedata');
			
			$filename = $_FILES['Filedata']['name'];
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if (!in_array($ext, ['sql', 'gz', 'encrypted'])) {
				echo '<!--Message_Type:Error-->Only .sql, .gz, or .encrypted files can be uploaded as database backups';
			} elseif (file_exists(ze::setting('backup_dir') . '/'. $_FILES['Filedata']['name'])) {
				echo '<!--Message_Type:Error-->A database backup with the same name already exists';
			} elseif (\ze\fileAdm::moveUploadedFile($_FILES['Filedata']['tmp_name'], ze::setting('backup_dir') . '/'. $_FILES['Filedata']['name'])) {
				echo '<!--Message_Type:Success-->Successfully uploaded the database backup';
				return ze\ring::encodeIdForOrganizer($filename);
			} else {
				echo '<!--Message_Type:Error-->Unable to upload the database backup';
			}
		
		} elseif (($_POST['restore'] ?? false) && ze\priv::check('_PRIV_RESTORE_SITE')) {
			//Restore a database backup from the file system
			$failures = [];
			if (ze\dbAdm::restoreFromBackup($filename, $failures, true)) {
				echo '<!--Reload_Organizer-->';
			} else {
				foreach ($failures as $text) {
					echo $text;
				}
			}
		}
	}
}