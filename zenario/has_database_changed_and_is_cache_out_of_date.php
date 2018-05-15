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


//This is used to check if the session storage is out of date
header('Content-Type: text/plain; charset=UTF-8');
require 'basicheader.inc.php';



//Check a few basic tables have been created. Exit if not.
if (!defined('DBHOST')
 || (!ze\db::connectLocal())
 || (!ze::$dbL->checkTableDef(DB_PREFIX. 'local_revision_numbers', true))
 || (!ze::$dbL->checkTableDef(DB_PREFIX. 'site_settings', true))) {
 	
 	var_dump(ze\db::connectLocal());
 	echo 'The CMS is either not installed or up to date.';
	exit;
}


//Echo the checksum on CSS/JavaScript files
echo ze::setting('css_js_version'), '___';
echo ze::setting('yaml_version'), '___';
echo ze\db::codeLastUpdated(), '___';

//Echo the the current data revision number. (This is incremented every time data is changed in the database.)
echo ze\row::get('local_revision_numbers', 'revision_no', ['path' => 'data_rev']);