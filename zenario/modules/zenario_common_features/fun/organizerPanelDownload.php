<?php
/*
 * Copyright (c) 2015, Tribal Limited
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

switch ($path) {
	case 'zenario__content/panels/content':
	case 'zenario__content/panels/chained':
	case 'zenario__content/panels/language_equivs':
		$cID = $cType = false;
		if (post('download') && getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
			//Offer a download for a file being used for a Content Item
			header('location: '. absCMSDirURL(). 'zenario/file.php?usage=content&cID='. $cID. '&cType='. $cType);
			exit;
		}
		
		break;
		
		
	case 'zenario__content/panels/documents':
	case 'zenario__content/panels/chained':
	case 'zenario__content/panels/language_equivs':
		$file =  getRow('files', true, getRow('documents', 'file_id', $ids));
		$fileName = getRow('documents', 'filename', $ids);
		if ($file['path']) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			//header('Content-Disposition: attachment; filename="'.basename(docstoreFilePath($file['id'])).'"'); //<<< Note the " " surrounding the file name
			header('Content-Disposition: attachment; filename="'.$fileName.'"');
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			header('Content-Transfer-Encoding: binary');
			header('Connection: Keep-Alive');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize(docstoreFilePath($file['id'])));
			readfile(docstoreFilePath($file['id']));
			exit;
		} else {
			header('location: '. absCMSDirURL(). 'zenario/file.php?adminDownload=1&download=1&id='. getRow('files', 'id', getRow('documents', 'file_id', $ids)));
			exit;
		}
		
		break;
	
	case 'zenario__administration/panels/backups':
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
				$filename = decodeItemIdForStorekeeper($ids);
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
		
		break;
}

return false;