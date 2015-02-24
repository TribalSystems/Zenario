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

function stripslashes_deep(&$object, $allowArrayLevel = 1) {
	if (is_array($object)) {
		if ($allowArrayLevel) {
			foreach ($object as &$child) {
				stripslashes_deep($child, $allowArrayLevel - 1);
			}
		} else {
			$object = false;
		}
	} else {
		if (get_magic_quotes_gpc()) {
			$object = trim(stripslashes($object));
		} else {
			$object = trim($object);
		}
	}
}

if (!empty($_GET)) stripslashes_deep($_GET);
if (!empty($_POST)) stripslashes_deep($_POST, 2);
if (!empty($_COOKIE)) stripslashes_deep($_COOKIE);
if (!empty($_REQUEST)) stripslashes_deep($_REQUEST);

if (get_magic_quotes_gpc()) {
	if (!empty($_FILES)) {
		foreach ($_FILES as &$_uploadedFile_) {
			if (isset($_uploadedFile_['name'])) {
				$_uploadedFile_['name'] = stripslashes($_uploadedFile_['name']);
			}
		}
		unset($_uploadedFile_);
	}
}

?>