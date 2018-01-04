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


if (!isset(cms_core::$apcFoundCodes[$lang]) || !is_array(cms_core::$apcFoundCodes[$lang])) {
	cms_core::$apcFoundCodes[$lang] = array();
	cms_core::$apcDirs[$lang] = moduleDirs('admin_phrase_codes/');

} elseif (isset(cms_core::$apcFoundCodes[$lang][$target])) {
	return cms_core::$apcFoundCodes[$lang][$target];
}



foreach (cms_core::$apcDirs[$lang] as $moduleDir) {
	if (file_exists($path = CMS_ROOT. $moduleDir. $lang. '.txt')) {
		//Find a specific phrase code from admin_phrase_codes/en.txt.
		//This uses binoimal search, so relies on the phrases in the file being in strict binary sort-order
		$size = filesize($path);
		$f = fopen($path, 'r');
		$step = $pos = (int) ($size / 2);
		$text = $code = $lastCode = '';
		
		//Use binomial search until we get to under 256 bytes
		while ($step > 256) {
			$lastCode = $code;
			if (!adminPhraseLine($lang, $f, $code, $text, $pos)) {
				return 'error';
			} elseif (($strcmp = strcasecmp($code, $target)) == 0) {
				return $text;
			} elseif ($step <= 256) {
				
			} elseif ($strcmp < 0) {
				$pos += ($step = (int) ($step / 2));
			} else {
				$pos -= ($step = (int) ($step / 2));
			}
		}
		
		//Move the cursor back a bit.
		$pos -= $step*2;
		$pos = max($pos, 0);
		adminPhraseLine($lang, $f, $code, $text, $pos);
		
		//Sweep linearly through the code, looking for the phrase
		while((strcasecmp($code, $target) < 0) && adminPhraseLine($lang, $f, $code, $text));
		
		if ($code == $target) {
			//Cache every code we find in memory
			return cms_core::$apcFoundCodes[$lang][$code] = $text;
		}
	}
}

return 'Missing '. $lang. ' Admin Phrase: '. $target;