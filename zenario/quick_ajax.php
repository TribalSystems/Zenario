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


header('Content-Type: text/plain; charset=UTF-8');
require 'liteheader.inc.php';


//Check a few basic tables have been created. Exit if not.
if (!defined('DBHOST')
 || (!connectLocalDB())
 || (!checkTableDefinition(DB_NAME_PREFIX. 'local_revision_numbers'))
 || (!checkTableDefinition(DB_NAME_PREFIX. 'tuix_file_contents'))) {
 	echo 'The CMS is either not installed or up to date.';
	exit;
}

loadSiteConfig();


//This is used to check if the local storage is out of date
if (!empty($_REQUEST['_get_data_revision'])) {
	

	//Include the checksum on CSS/JavaScript files
	echo setting('css_js_version'), '___';
	
	//Add a calculation of the Module hash, as if there are code changes we'll want this to report a different number
	//In admin mode this is calcuated each time; in Visitor mode we'll just use the cached value
	if (!empty($_REQUEST['admin'])) {
		checkForChangesInYamlFiles();
		checkForChangesInPhpFiles();
	}
	echo setting('yaml_version'), '___';
	echo setting('php_version'), '___';
	
	//Report the current data revision to the client
	$sql = "
		SELECT revision_no
		FROM ". DB_NAME_PREFIX. "local_revision_numbers
		WHERE path = 'data_rev'";
	$result = sqlQuery($sql);
	if ($row = sqlFetchRow($result)) {
		echo $row[0];
	}


} elseif (isset($_REQUEST['accept_cookies'])) {
	session_start();
	
	require CMS_ROOT. 'zenario/api/system_functions.inc.php';
	require_once CMS_ROOT. 'zenario/api/link_path_and_url_core_functions.inc.php';
	
	if ($_REQUEST['accept_cookies']) {
		setCookieConsent();
	} else {
		setCookieNoConsent();
	}
	
	if (empty($_REQUEST['ajax'])) {
		if (!empty($_SERVER['HTTP_REFERER'])) {
			header('location: '. $_SERVER['HTTP_REFERER']);
		} else {
			header('location: ../');
		}
	}
}

exit;