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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

$requiredFields = array('name');

$categoryFields = array();
$categoryFields['name'] = array(
	'Label' => 'Internal Name:',
	'Type' => 'varchar(50)',
	'Table' => 'categories');
$categoryFields['public'] = array(
	'Label' => 'Category is Public:',
	'Type' => 'tinyint',
	'Note Below' => adminPhrase('When a Category is Public, its name will be visible to visitors where appropriate. Public Categories will automatically be given a name in all Enabled Languages. You can manage its Public Name(s) via the Languages and Phrases system.'));
$categoryFields['landing_page'] = array(
	'Label' => 'Landing Page:',
	'Type' => 'content',
	'Requires' => 'public');

$categoryDBDetails = array();

if (post('catId')) {
	//Load category details fro database if an id has been given
	$categoryDBDetails = getRow('categories', array('parent_id', 'name', 'public', 'landing_page_equiv_id', 'landing_page_content_type'), post('catId'));
	
	if ($categoryDBDetails['landing_page_equiv_id']) {
		$categoryDBDetails['landing_page'] = $categoryDBDetails['landing_page_content_type']. '_'. $categoryDBDetails['landing_page_equiv_id'];
	} else {
		$categoryDBDetails['landing_page'] = '';
	}
} else {
	//No need to show the show the "Edit"/"Cancel" button for new categories
	$hasEditPermission = false;
}


echo dynamicThickboxFormInner($editMode, $categoryFields, $requiredFields, $_POST, $categoryDBDetails);

return false;