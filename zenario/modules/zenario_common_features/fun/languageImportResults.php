<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

if ($error) {
	echo $error;

} elseif ($numberOf['wrong_language']) {
	echo adminPhrase("_VLP_IMPORT_FOR_WRONG_LANGUAGE");

} elseif ($numberOf['upload_error']) {
	echo adminPhrase("There was an error with your file upload. Please make sure you have provided a valid file, in the format required by this tool.").
			adminPhrase("Language Pack imported. [[added]] phrase(s) were added and [[updated]] phrase(s) have been updated. [[protected]] phrase(s) were protected and not overwritten.", $numberOf);
	
} elseif ($numberOf['added'] || $numberOf['updated']) {
	echo '<!--Message_Type:Success-->';
	if ($changeButtonHTML) {
		echo '<!--Button_HTML:<input type="button" class="submit" value="', adminPhrase('OK'), '" onclick="zenarioO.reloadPage(\'zenario__languages/panels/languages\');"/>-->';
	}
	echo adminPhrase("Language pack imported. [[added]] phrase(s) were added and [[updated]] phrase(s) have been updated. [[protected]] phrase(s) were protected and not overwritten.", $numberOf);
	
} else {
	echo '<!--Message_Type:Warning-->';
	echo adminPhrase("No phrases were imported.");
	
	if ($numberOf['protected'] > 0) {
		echo adminPhrase(" [[protected]] phrase(s) were protected and not overwritten.", $numberOf);
	}
}

return false;