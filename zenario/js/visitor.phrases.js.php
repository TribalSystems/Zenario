<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

header('Content-Type: text/javascript; charset=UTF-8');
require '../basicheader.inc.php';

if (empty($_GET['langId'])) {
	exit;
}
$v = preg_replace('@[^\w\.-]@', '', $_GET['v'] ?? '1');
$langId = preg_replace('@[^\w\.-]@', '', $_GET['langId']);

$ETag = 'zenario-visitor-phrases-'. $langId. '-'. $v. '-';
ze\cache::useBrowserCache($ETag);

if (ze::$canCache) require CMS_ROOT. 'zenario/includes/bundle.pre_load.inc.php';


ze\db::loadSiteConfig();

//Enable the phrases translation system for this script
\ze::$trackPhrases = true;





//Output a few commonly used phrases for visitors
$output = '';
foreach([
	'cancel' => 'Cancel',
	'copy' => 'Copy to clipboard',
	'copied' => 'Copied',
	'copiedToClipboard' => 'Copied to clipboard'
] as $code => $phrase) {
	$output .= ze\cache::esctick($code). '~'. ze\cache::esctick(
		ze\lang::phrase($phrase, false, 'zenario_common_features', $langId, $backtraceOffset = 1)
	). '~';
}

echo 'zenario._mkd(zenario.vphrase,', json_encode($output), ');';
	//N.b. zenario._mkd() is the short-name for zenario.unpackAndMerge()
	//(For shorter lists than this, consider using callScript() and calling the zenario.readyPhrasesOnBrowser() function)






if (ze::$canCache) require CMS_ROOT. 'zenario/includes/bundle.post_display.inc.php';