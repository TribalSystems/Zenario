<?php
/*
 * Copyright (c) 2025, Tribal Limited
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


//Step 5 is a special case that runs every new version of Zenario.
//It has it's own special numbering system that is formed from the major and minor version number.



//Clear out the "public/downloads/" directory every new version update, just in case there
//are any bad symlinks left in there after a move.
$downloadsDir = CMS_ROOT. 'public/downloads/';
if (is_dir($downloadsDir)
 && is_writable($downloadsDir)) {
	
	ze\cache::deleteDir($downloadsDir, 1, $deleteSymlinks = true);
	
	//Try and regenerate these if we can
	$errors = $exampleFile = false;
	ze\document::checkAllPublicLinks($forceRemake = true, $errors, $exampleFile);
}


//Also clear out a few other directories, either to remove old symlinks or just to ensure
//any old cache files are deleted.
//Don't worry about regenerating them though, Zenario will regenerate them as needed when they're used
foreach ([
	CMS_ROOT. 'cache/bundles/',
	CMS_ROOT. 'cache/fabs/',
	CMS_ROOT. 'cache/frameworks/',
	CMS_ROOT. 'cache/layouts/',
	CMS_ROOT. 'cache/pages/',
	CMS_ROOT. 'cache/plugins/',
	CMS_ROOT. 'cache/tuix/',
	CMS_ROOT. 'private/downloads/',
	CMS_ROOT. 'public/css/',
	CMS_ROOT. 'public/js/'
] as $downloadsDir) {
	if (is_dir($downloadsDir)
	 && is_writable($downloadsDir)) {
		ze\cache::deleteDir($downloadsDir, 1, $deleteSymlinks = true);
	}
}


//Run the cleanDirs() function if it's not been run already.
//(Note: if a previous revision has just called this function, the second call will not do anything further.)
ze\cache::cleanDirs($force = true);




//When we've run all of these updates, store the current version in the revision numbers table so this file won't re-run
//until the next version of Zenario.
ze\dbAdm::revision(ze\dbAdm::majorMinorInteger());