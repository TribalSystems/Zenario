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



if (ze\dbAdm::needRevision(131)) {
	
	if (!($dataset = ze\dataset::details('users'))) {
		echo ze\admin::phrase('The Newsletter Module could not install correctly as the users data-view is not correctly set up.');
		exit;
	}
	
	$key = [
		'db_column' => 'all_newsletters_opt_out',
		'dataset_id' => $dataset['id']];
	$cols = [
			'tab_name' => 'details',
			'ord' => 999,
			'label' => 'Opt out of newsletters',
			'type' => 'checkbox',
			'organizer_visibility' => 'hide',
			'sortable' => 1];
	
	if (!ze\row::exists('custom_dataset_fields', $key)) {
		$optOutFieldId = ze\row::set('custom_dataset_fields', $cols, $key);
		ze\datasetAdm::createFieldInDB($optOutFieldId);
	}
	
	if ($optOutField = ze\dataset::fieldDetails('all_newsletters_opt_out', 'users')) {
		ze\site::setSetting('zenario_newsletter__all_newsletters_opt_out', $optOutField['id']);
	}
	
	ze\dbAdm::revision(131);
}