<?php
/*
 * Copyright (c) 2023, Tribal Limited
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


//A simple function to check if a an external server program looks like it's installed
function checkProgramInstalled($path, $program) {
	if ($path == 'PATH') {
		return (bool) exec('which '. escapeshellarg($program));
	} else {
		return (bool) exec('which '. escapeshellarg($path. $program));
	}
}


//Loop through all of the external server programs, and try to make sure they are set properly
if (ze\dbAdm::needRevision(50520)) {
	
	if (!\ze\server::isWindows() && \ze\server::execEnabled()) {
	
		foreach ([
			'advpng_path' => 'advpng',
			'antiword_path' => 'antiword',
			'clamscan_tool_path' => 'clamdscan',
			'ghostscript_path' => 'gs',
			'jpegoptim_path' => 'jpegoptim',
			'jpegtran_path' => 'jpegtran',
			'mysql_path' => 'mysql',
			'mysqldump_path' => 'mysqldump',
			'optipng_path' => 'optipng',
			'pdftotext_path' => 'pdftotext',
			'phantomjs_path' => 'phantomjs',
			'wkhtmltopdf_path' => 'wkhtmltopdf'
		] as $settingName => $program) {
			
			//Disable the antivirus by default
			if ($program == 'clamdscan') {
				ze\site::setSetting($settingName, '');
			} else {
			
				//Check if the settings for each program is set to something, and if it is set to something that exists
				$settingValue = ze::setting($settingName);
				if (!$settingValue || !checkProgramInstalled($settingValue, $program)) {
				
					//Try to check each possible option that we could change it to, to see if we can find the right value
					foreach (['PATH', '/usr/bin/', '/usr/local/bin/'] as $path) {
						if ($path != $settingValue) {
							if (checkProgramInstalled($path, $program)) {
							
								//If we find the right option, update the site settings and finish looking at this program
								ze\site::setSetting($settingName, $path);
								continue 2;
							}
						}
					}
				
					//If we didn't get anything, flip the setting to the "do not use" selection
					ze\site::setSetting($settingName, '');
				}
			}
		}
	}
	
	
	ze\dbAdm::revision(50520);
}
