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




//Define the directory tree in the following format:
	//$mainDir => $subDir => $lifetime

$directories = array(
	'cache' => array(
		'downloads' => -2,
		'images' => -2,
		'fabs' => 4 * 60 * 60,
		'files' => -2,
		'frameworks' => -1,
		'pages' => 2 * 60 * 60,
		'stats' => -1,
		'tuix' => -1,
		'uploads' => 1 * 60 * 60
	), 
	'private' => array(
		'downloads' => 5 * 60 * 60,
		'images' => 2 * 60 * 60,
		'files' => 2 * 60 * 60
	), 
	'public' => array(
		'downloads' => -1,
		'images' => -1
	)
);

//If they are older than $lifetime, files and their containing directories should be deleted.
//A $lifetime of -1 means they should be permenant and never deleted
//A $lifetime of -2 is for old deprecated/unused directories that should be immediately deleted


//Loop through each temporary directory, looking to clean each up
foreach ($directories as $mainDir => $subDirs) {

	//Check each main dir is there, and attempt to create it if it is not
	if (!is_dir($dir = CMS_ROOT. $mainDir. '/')) {
		if (!@mkdir($dir)) {
			define('ZENARIO_CLEANED_DOWNLOADS', false);
			return false;
		}
		@chmod($dir, 0777);
	}

	foreach ($subDirs as $type => $lifetime) {
		
		//Check if the main directory is writable
		if (!is_writable(CMS_ROOT. $mainDir. '/')) {
			define('ZENARIO_CLEANED_DOWNLOADS', false);
			return false;
	
		//Check if the sub-directory is there
		} elseif (!is_dir($dir = CMS_ROOT. $mainDir. '/'. $type. '/')) {
			
			//If it's not there, and this was one of the old unused directories, then that's good!
			if ($lifetime == -2) {
				continue;
			
			//Otherwise we should try and create it.
			} else {
				if (!@mkdir($dir)) {
					define('ZENARIO_CLEANED_DOWNLOADS', false);
					return false;
				}
				@chmod($dir, 0777);
			}
	
		} elseif (!is_writable($dir) && $lifetime != -2) {
			define('ZENARIO_CLEANED_DOWNLOADS', false);
			return false;
	
		//Otherwise check the file times, looking for out of date files
		} elseif ($lifetime != -1) {
			foreach (scandir($dir) as $folder) {
				if ($folder != '.' && $folder != '..') {
				
					//Check the modification date of the file called "accessed", or just grab any other file if that's not present
					if (is_dir($dir. $folder)) {
						if (!is_file($accessed = $dir. $folder. '/accessed')) {
							foreach (scandir($dir. $folder) as $file) {
								if (substr($file, 0, 1) != '.') {
									$accessed = $dir. $folder. '/'. $file;
									break;
								}
							}
						}
					} else {
						$accessed = $dir. $folder;
					}
				
					$empty = true;
					if ($accessed) {
						//Use the last access time for preference, but default to the modified time otherwise
						$timeA = @fileatime($accessed);
						$timeM = @filemtime($accessed);
				
						if (!$timeA || $timeA < $timeM) {
							$timeA = $timeM;
						}
				
						$empty = $timeA < $time - $lifetime;
					}
			
					//If the file or directory is completely out of date, delete it
					if ($empty) {
						if (is_dir($dir. $folder)) {
							deleteCacheDir($dir. $folder);
						} else {
							@unlink($dir. $folder);
						}
					}
				}
			}
			
			//Attempt to tidy up and delete old/decprecated directories if they are empty so
			//people browsing the file system don't get confused by them
			if ($lifetime == -2) {
				deleteCacheDir($dir);
			}
		}
	}
}


define('ZENARIO_CLEANED_DOWNLOADS', true);
return true;