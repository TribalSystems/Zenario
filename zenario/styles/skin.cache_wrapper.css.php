<?php
/*
 * Copyright (c) 2019, Tribal Limited
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

require '../basicheader.inc.php';
header('Content-Type: text/css; charset=UTF-8');

//Ensure that the site name and subdirectory are part of the ETag, as Skins can have different ids on different servers
$ETag = 'zenario-skin-'. $_SERVER['HTTP_HOST']. '-'. LATEST_REVISION_NO. '-'. (int) $_GET['id'];

if (isset($_GET['editor'])) {
	$ETag .= '-editor';

} elseif (isset($_GET['print'])) {
	$ETag .= '-print';
}

//Cache this combination of running Plugin JavaScript
ze\cache::useBrowserCache($ETag);


//Run pre-load actions

if (ze::$canCache) require CMS_ROOT. 'zenario/includes/wrapper.pre_load.inc.php';


ze\cache::start();
ze\db::loadSiteConfig();

echo '
/*
	The CSS rules you see below are from multiple files that have been combined
	together to reduce the number of downloads.
	
	To make debugging easier, turn this off by going to
		Configuration -> Site Settings -> Optimization
	in Organizer and set the "CSS File Wrappers" setting to "On for visitors only"
	or "Always off".
*/

';


ze\wrapper::includeSkinFiles($_GET);


//Run post-display actions
if (ze::$canCache) require CMS_ROOT. 'zenario/includes/wrapper.post_display.inc.php';
