<?php
/*
 * Copyright (c) 2014, Tribal Limited
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


if ($floatingBoxName=='View Email Log Record'){
	
	$editMode = false;
	$showEditButton = false;

	$logRecord = self::getLogRecordById(arrayKey($primaryKey,'id'));
	$fields['email_template_name'] = array('Label'=>'Template name:','Type' => 'varchar(255)');
	$fields['email_subject'] = array('Label'=>'Email Subject:','Type' => 'varchar(255)');
	$fields['email_address_to'] = array('Label'=>'Address To:','Type' => 'varchar(255)');
	$fields['email_address_from'] = array('Label'=>'Address From:','Type' => 'varchar(255)');
	$fields['email_name_from'] = array('Label'=>'Name From:','Type' => 'varchar(255)');
	
	$fields['email_body_non_escaped'] = array('Label'=>'Body:','Type' => 'textarea','Pre Field HTML'=>nl2br(arrayKey($logRecord,'email_body')));

	echo dynamicThickboxFormInner($editMode, $fields, array(), $_POST, $logRecord);
}