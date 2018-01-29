<?php
/*
 * Copyright (c) 2018, Tribal Limited
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


class zenario_common_features__admin_boxes__backup extends ze\moduleBaseClass {
	
	 public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$values['details/username'] = $_SESSION['admin_username'] ?? false;
		
		$contains = ze\admin::phrase('This backup will contain:');
		$contains .= '<ul>';
		
		if (\ze\db::hasGlobal()) {
			$contains .= '<li>'. ze\admin::phrase("The site's local database."). '</li>';
		} else {
			$contains .= '<li>'. ze\admin::phrase("The site's database."). '</li>';
		}
		
		$contains .= '</ul>';
		$contains .= ze\admin::phrase('This backup will <u>not</u> contain:');
		$contains .= '<ul>';
		
		if (\ze\db::hasGlobal()) {
			$contains .= '<li>'. ze\admin::phrase('The global database.'). '</li>';
		}
		
		$contains .= '<li>'. ze\admin::phrase('The <code>docstore/</code> directory.'). '</li>';
		$contains .= '<li>'. ze\admin::phrase('The <code>zenario_custom/</code> directory.'). '</li>';
		
		$contains .= '</ul>'. ze\admin::phrase('You should back up and restore these separately to preserve your custom frameworks, custom modules, documents, layouts and skins.');
		
		$fields['details/desc2']['snippet']['html'] = $contains;
		
		
		if (!ze\zewl::loadClientKey()) {
			$fields['details/encrypt']['disabled'] = true;
			$fields['details/encrypt']['side_note'] = ze\admin::phrase('Encryption is not enabled on this site.');
		} else {
			$values['details/encrypt'] = 1;
		}
		
		if ($box['key']['server']) {
			$box['key']['id'] = false;
			$box['title'] = ze\admin::phrase('Create a database backup on site');
			$box['save_button_message'] = ze\admin::phrase('Create a database backup on site');
		
		} else {
			$box['download'] = true;
			if ($box['key']['id']) {
				$box['title'] = ze\admin::phrase('Download database backup');
				$box['save_button_message'] = ze\admin::phrase('Download database backup');
		
			} else {
				$box['title'] = ze\admin::phrase('Create and download a database backup');
				$box['save_button_message'] = ze\admin::phrase('Create and download a database backup');
			}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		ze\priv::exitIfNot('_PRIV_BACKUP_SITE');
		$details = array();
		
		if (!$box['key']['server']) {
			if (!$values['details/password']) {
				$box['tabs']['details']['errors'][] =
					ze\admin::phrase('Please enter your password.');
		
			} elseif (!ze\ring::engToBoolean(ze\admin::checkPassword($_SESSION['admin_username'] ?? false, $details, $values['details/password']))) {
				$box['tabs']['details']['errors'][] =
					ze\admin::phrase('Your password was not recognised. Please check and try again.');
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Functionality for saving a new database backup to the server
		
		//Check permissions
		ze\priv::exitIfNot('_PRIV_BACKUP_SITE');
		
		if (!$box['key']['server']) {
			return;
		}
		
		
		$encrypt = (bool) $values['details/encrypt'];
		$gzip =  (bool) $values['details/gzip'];
		
		//Create a new file in the backup directory, and write the backup into it
		$backupPath = ze::setting('backup_dir'). '/' . ($fileName = ze\dbAdm::generateFilenameForBackups($gzip, $encrypt));
		
		ze\dbAdm::createBackupScript($backupPath, $gzip, $encrypt);
		
		@chmod($backupPath, 0666);
		
		$box['key']['id'] = ze\ring::encodeIdForOrganizer($fileName);
	}
	
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Functionality for downloading a database backup
		
		//Check permissions
		ze\priv::exitIfNot('_PRIV_BACKUP_SITE');
		
		if ($box['key']['server']) {
			return;
		}
		
		
		//Offer a database backup of the current state of the site for download 
		if (!$box['key']['id']) {
			
			$encrypt = (bool) $values['details/encrypt'];
			$gzip =  (bool) $values['details/gzip'];
			
			$filename = ze\dbAdm::generateFilenameForBackups($gzip, $encrypt);
			
			//Create a gz file in the temp directory...
			$filepath = tempnam(sys_get_temp_dir(), 'tmpfiletodownload');
			
			//...write the database backup into it...
			ze\dbAdm::createBackupScript($filepath, $gzip, $encrypt);
			
			//...and finally offer it for download
			header('Content-Disposition: attachment; filename="'. $filename. '"');
			header('Content-Length: '. filesize($filepath)); 
			readfile($filepath);
			
			//Remove the file from the temp directory
			@unlink($filepath);
		
		
		//Offer one of the previously saved database backups for download	
		} else {
			$filename = ze\file::safeName(ze\ring::decodeIdForOrganizer($box['key']['id']));
			$filepath = ze::setting('backup_dir'). '/'. $filename;
			
			if (!is_file($filepath)
			 || !is_readable($filepath)) {
				echo ze\admin::phrase('File is not readable');
				exit;
			}
			
			//Make this page into a download
			header('Content-Disposition: attachment; filename="'. $filename. '"');
			header('Content-Length: '. filesize($filepath)); 
			readfile($filepath);
		}
	}
	
}
