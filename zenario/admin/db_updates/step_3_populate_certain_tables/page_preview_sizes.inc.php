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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


//Warning:
	//This update will always be run with each update; no matter what the version numbers are!


//Populate the page_preview_sizes table with some initial values, if it is empty
if (!ze\sql::numRows("SELECT 1 FROM ". DB_PREFIX. "page_preview_sizes LIMIT 1")) {
	
	ze\sql::update("
		INSERT INTO ". DB_PREFIX. "page_preview_sizes
		(width, height, description, is_default, ordinal, type)
		VALUES 
		(1680, 1050, 'Current computers', 0, 1, 'desktop'),
		(1280, 1024, 'Not that old computers', 0, 2, 'desktop'),
		(1024, 768, 'Old computers', 0, 3, 'desktop'),
		(1440, 900, 'Other laptops', 0, 4, 'laptop'),
		(1366, 769, 'Laptop 15.7\"', 0, 5, 'laptop'),
		(1280, 800, 'Laptop 15.4\"', 0, 6, 'laptop'),
		(1024, 600, 'Netbook', 1, 7, 'laptop'),
		(768, 1024, 'iPad portrait', 0, 8, 'tablet'),
		(320, 480, 'HVGA - iPhone, Android, Palm Pre', 0, 9, 'smartphone')
	");
}
