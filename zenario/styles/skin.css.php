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

require '../cacheheader.inc.php';
header('Content-Type: text/css; charset=UTF-8');

//Ensure that the site name and subdirectory are part of the ETag, as Skins can have different ids on different servers
$ETag = 'zenario-skin-'. $_SERVER['HTTP_HOST']. '-'. LATEST_REVISION_NO. '-'. (int) $_GET['id'];

if (isset($_GET['editor'])) {
	$ETag .= '-editor';

} elseif (isset($_GET['print'])) {
	$ETag .= '-print';
}

//Cache this combination of running Plugin JavaScript
useCache($ETag);


//Run pre-load actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/wrapper.pre_load.php', true)) {
		require $action;
	}
}


useGZIP(!empty($_GET['gz']));
require CMS_ROOT. 'zenario/liteheader.inc.php';
require CMS_ROOT. 'zenario/includes/cms.inc.php';
require CMS_ROOT. 'zenario/includes/wrapper.inc.php';
loadSiteConfig();


includeSkinFiles($_GET);


//Run post-display actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/wrapper.post_display.php', true)) {
		require $action;
	}
}