<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

class zenario_email_template_manager extends ze\moduleBaseClass {

	public static function getTemplateNames() {
		$rv = [];
		$result = ze\row::query('email_templates', ['code', 'template_name'], [], 'template_name');
		while ($row = ze\sql::fetchAssoc($result)) {
			$rv[$row['code']] = $row['template_name'];
		}
		return $rv;
	}


	public static function getTemplateById($id) {
		return ze\row::get('email_templates', true, ['id' => $id]);
	}

	public static function getTemplateByCode($code) {
		return ze\row::get('email_templates', true, ['code' => $code]);
	}

	public static function getTemplatesByNameIndexedByCode($name, $strict = true){
		
		if ($strict) {
			$where = ['template_name' => $name];
		} else {
			$where = ['template_name' => ['LIKE' => '%'. $name. '%']];
		}
		
		$result = ze\row::query('email_templates', true, $where);
		
		$rv = [];
		while ($row = ze\sql::fetchAssoc($result)){
			$rv[$row['code']] = $row;
		}
		return $rv;
	}
	
	public static function getLogRecordById($id) {
		return ze\row::get(ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX. 'email_template_sending_log', true, ['id' => $id]);
	}
	
	public static function testSendEmailTemplate($body, $adminDetails, $email, $subject, $emailAddresFrom, $emailNameFrom) {
		//Identify this as a test email
		$subject .= ' | TEST SEND';
		
		//Attempt to send the email
		$emailOverriddenBy = false;
		return ze\server::sendEmail(
			$subject,
			$body,
			$email,
			$emailOverriddenBy,
			$adminDetails['admin_first_name']. ' '. $adminDetails['admin_last_name'],
			$emailAddresFrom, $emailNameFrom
		);
	}
		
	
	public static function sendEmails(
		$rcpts, $subject, $addressFrom, $nameFrom, $body, $mergeFields=[], 
		$attachments=[], $attachmentFilenameMappings=[], 
		$templateNo = 0, $disableHTMLEscaping = false, 
		$addressReplyTo = false, $nameReplyTo = false,
		$ccs = '', $bccs = '', $debugOverride = ''
	){
		
		if (!empty($mergeFields)) {
			
			ze\lang::applyMergeFields($body, $mergeFields, '[[', ']]', !$disableHTMLEscaping);
			ze\lang::applyMergeFields($subject, $mergeFields, '[[', ']]', !$disableHTMLEscaping);
			ze\lang::applyMergeFields($nameFrom, $mergeFields, '[[', ']]', !$disableHTMLEscaping);
			ze\lang::applyMergeFields($addressFrom, $mergeFields, '[[', ']]', !$disableHTMLEscaping);
			
			
			#mb_regex_encoding('UTF-8');
			#foreach ($mergeFields as $K=>&$V) {
			#	$search = '\[\[' . $K . '\]\]';
			#
			#	if (is_array($disableHTMLEscaping)? !empty($disableHTMLEscaping[$K]) : $disableHTMLEscaping) {
			#		$replace = &$V;
			#	} else {
			#		$replace = nl2br(htmlspecialchars($V));
			#	}
			#
			#	$body = mb_ereg_replace($search, $replace, $body);
			#	$subject = mb_ereg_replace($search, $replace, $subject);
			#	$nameFrom = mb_ereg_replace($search, $replace, $nameFrom);
			#	$addressFrom = mb_ereg_replace($search, $replace, $addressFrom);
			#	unset($replace);
			#}
		
			//$regex_filter = '\[\[[^\]]*\]\]';
			//$body = mb_ereg_replace ($regex_filter,'',$body);
			//$subject = mb_ereg_replace ($regex_filter,'',$subject);
			//$nameFrom = mb_ereg_replace ($regex_filter,'',$nameFrom);
			//$addressFrom = mb_ereg_replace ($regex_filter,'',$addressFrom);
		
			#if($addressReplyTo) {
			#	$addressReplyTo = mb_ereg_replace ($regex_filter,'',$addressReplyTo);
			#}
			#if($nameReplyTo) {
			#	$nameReplyTo = mb_ereg_replace ($regex_filter,'',$nameReplyTo);
			#}
		}
		
		$result = true;
		
		foreach (array_unique(ze\ray::explodeAndTrim(str_replace(';', ',', $rcpts))) as $addressTo) {
			/*
			(
	$subject, $body, $addressTo, &$addressToOverriddenBy,
	$nameTo = false, $addressFrom = false, $nameFrom = false, 
	$attachments = [], $attachmentFilenameMappings = [],
	$precedence = 'bulk', $isHTML = true, $exceptions = false,
	$addressReplyTo = false, $nameReplyTo = false, $warningEmailCode = false,
	$ccs = '', $bccs = ''
)
			*/
			
			if($debugOverride){
				//template in debug mode
				$debugEmails = array_unique(ze\ray::explodeAndTrim(str_replace(';', ',', $debugOverride)));
				foreach($debugEmails as $debugEmail){
					$addressToOverriddenBy = false;
					$thisResult = ze\server::sendEmail(
						$subject, $body, $debugEmail, $addressToOverriddenBy,
						false, $addressFrom, $nameFrom,
						$attachments, $attachmentFilenameMappings,
						'bulk', $isHTML = true, false,
						$addressReplyTo, $nameReplyTo, false,
						$ccs, $bccs
					);
					
					self::logEmail(
						$subject, $body, $addressTo, $addressToOverriddenBy? $addressToOverriddenBy : $debugOverride,
						$addressFrom, $nameFrom,
						$attachments, $attachmentFilenameMappings,
						$templateNo, $thisResult,
						$_POST, 
						$addressReplyTo, $nameReplyTo,
						$ccs
					);
					
				}
			}else{
				//$debugOverride? $debugOverride : $addressTo, $addressToOverriddenBy
				$thisResult = ze\server::sendEmail(
					$subject, $body,$addressTo, $addressToOverriddenBy,
					false, $addressFrom, $nameFrom,
					$attachments, $attachmentFilenameMappings,
					'bulk', $isHTML = true, false,
					$addressReplyTo, $nameReplyTo, false,
					$ccs, $bccs
				);
			
				self::logEmail(
					$subject, $body, $addressTo, $addressToOverriddenBy? $addressToOverriddenBy : $debugOverride,
					$addressFrom, $nameFrom,
					$attachments, $attachmentFilenameMappings,
					$templateNo, $thisResult,
					$_POST, 
					$addressReplyTo, $nameReplyTo,
					$ccs
				);
			}
			
			$result &= $thisResult;
		}
		return $result;
	}
	
	public static function logEmail(
		$subject, &$body, $addressTo, $addressToOverriddenBy,
		$addressFrom, $nameFrom,
		$attachments, $attachmentFilenameMappings,
		$templateNo, $status,
		$senderCmsObjectArray = [],
		$addressReplyTo = false, $nameReplyTo = false,
		$ccs = false
	) {
		self::clearOldData();
		
		//Check if this email should be logged
		$template = ze\row::get('email_templates', ['template_name', 'period_to_delete_log_headers', 'period_to_delete_log_content'], $templateNo);
		if ($template['period_to_delete_log_headers'] === '0'
			|| ($template['period_to_delete_log_headers'] === '' && ze::setting('period_to_delete_the_email_template_sending_log_headers') === '0')
		) {
			return false;
		}
		
		$sql = "
			INSERT INTO ". DB_PREFIX. ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX. "email_template_sending_log SET
				module_id = ". (int) ze\row::get('plugin_instances', 'module_id', ['id' => ($senderCmsObjectArray['instanceId'] ?? false)]). ",
				instance_id = ". (int) ($senderCmsObjectArray['instanceId'] ?? false). ",
				content_id = ". (int) ($senderCmsObjectArray['cID'] ?? false). ",
				content_type = '". ze\escape::sql($senderCmsObjectArray['cType'] ?? false). "',
				content_version = ". (int) ($senderCmsObjectArray['cVersion'] ?? false). ",
				email_template_id = ". (int) $templateNo. ",
				email_template_name = '". ze\escape::sql($template['template_name']). "',
				email_subject = '". ze\escape::sql($subject). "',
				email_address_to = '". ze\escape::sql($addressTo). "',
				email_address_to_overridden_by = '". ze\escape::sql($addressToOverriddenBy). "',
				email_address_from = '". ze\escape::sql($addressFrom). "',
				email_name_from = '". ze\escape::sql($nameFrom). "',
				attachment_present = ". (int) !empty($attachments). ",
				sent_datetime = '". ze\escape::sql(ze\date::now()). "',
				debug_mode = ". (int)ze::setting('debug_override_enable'). ",
				`status` = '". ze\escape::sql($status? 'success' : 'failure'). "'";

		if ($addressReplyTo) {
			$sql .= ",
				email_address_replyto = '". ze\escape::sql($addressReplyTo). "'";
		}
		if ($nameReplyTo) {
			$sql .= ",
				email_name_replyto = '". ze\escape::sql($nameReplyTo). "'";
		}
		if ($ccs) {
			$sql .= ",
				email_ccs = '". ze\escape::sql($ccs). "'";
		}
		
		//Check if this email's content should be logged
		if ($template['period_to_delete_log_content'] === '0') {
			$body = ze\admin::phrase('Body not saved because the email template setting for data deletion is set to "Don\'t save".');
		} elseif ($template['period_to_delete_log_content'] === '' && ze::setting('period_to_delete_the_email_template_sending_log_content') === '0') {
			$body = ze\admin::phrase('Body not saved because the site-wide setting for email data deletion is set to "Don\'t save".');
		}
		if (strlen($body) < 100000) {
			$sql .= ",
				email_body = '". ze\escape::sql($body). "'";
		} else {
			$sql .= ",
				email_body = '". ze\escape::sql(ze\admin::phrase('Body too large to save')). "'";
		}

		ze\sql::update($sql, false, false);
		return true;
	}
	
	
	public static function clearOldData() {
		$cleared = 0;
		
		//Delete email log headers
		
		//Clear the sending log for templates with individual settings
		$sql = '
			SELECT id, period_to_delete_log_headers
			FROM ' . DB_PREFIX . 'email_templates
			WHERE period_to_delete_log_headers != ""';
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$days = $row['period_to_delete_log_headers'];
			if (is_numeric($days)) {
				$sql = '
					DELETE FROM ' . DB_PREFIX . ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX . 'email_template_sending_log
					WHERE email_template_id = ' . (int)$row['id'];
				if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
					$sql .= '
						AND sent_datetime < "' . ze\escape::sql($date) . '"';
				}
				ze\sql::update($sql);
				$cleared += ze\sql::affectedRows();
				
			}
		}
		
		//Clear email template sending log for the rest
		$days = ze::setting('period_to_delete_the_email_template_sending_log_headers');
		if (is_numeric($days)) {
			$sql = '
				DELETE etsl.*
				FROM ' . DB_PREFIX . ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX . 'email_template_sending_log etsl
				LEFT JOIN ' . DB_PREFIX . 'email_templates et
					ON etsl.email_template_id = et.id
				WHERE (et.period_to_delete_log_headers IS NULL OR et.period_to_delete_log_headers = "")';
			if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
				$sql .= '
					AND etsl.sent_datetime < "' . ze\escape::sql($date) . '"';
			}
			ze\sql::update($sql);
			$cleared += ze\sql::affectedRows();
		}
		
		
		//Delete email log content
		
		//Clear the sending log for templates with individual settings
		$sql = '
			SELECT id, period_to_delete_log_content
			FROM ' . DB_PREFIX . 'email_templates
			WHERE period_to_delete_log_content != ""';
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$days = $row['period_to_delete_log_content'];
			if (is_numeric($days)) {
				$sql = '
					UPDATE ' . DB_PREFIX . ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX . 'email_template_sending_log
					SET email_body = "[Email body deleted]"
					WHERE email_template_id = ' . (int)$row['id'];
				if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
					$sql .= '
						AND sent_datetime < "' . ze\escape::sql($date) . '"';
				}
				ze\sql::update($sql);
				$cleared += ze\sql::affectedRows();
			}
		}
		
		//Clear email template sending log for the rest
		$days = ze::setting('period_to_delete_the_email_template_sending_log_content');
		if (is_numeric($days)) {
			$sql = '
				UPDATE ' . DB_PREFIX . ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX . 'email_template_sending_log etsl
				LEFT JOIN ' . DB_PREFIX . 'email_templates et
					ON etsl.email_template_id = et.id
				SET etsl.email_body = "[Email body deleted]"
				WHERE (et.period_to_delete_log_content IS NULL OR et.period_to_delete_log_content = "")';
			if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
				$sql .= '
					AND etsl.sent_datetime < "' . ze\escape::sql($date) . '"';
			}
			ze\sql::update($sql);
			$cleared += ze\sql::affectedRows();
		}
		return $cleared;
	}
	
	public static function eventUserDeleted($userId, $deleteAllData) {
		//When deleting all data about a user, delete their sent email log message content but keep the header
		if ($deleteAllData) {
			if ($userEmail = ze\row::get('users', 'email', $userId)) {
				$sql = '
					UPDATE ' . DB_PREFIX . ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX . 'email_template_sending_log
					SET email_body = "[Email body deleted]", email_address_to = "[User deleted]"
					WHERE email_address_to = "' . ze\escape::sql($userEmail) . '"';
				ze\sql::update($sql);
			}
		}
	}
	
	
	public static function sendEmailsUsingTemplate(
		$rcpts, $templateCode, $mergeFields = [],
		$attachments = [], $attachmentFilenameMappings = [],
		$disableHTMLEscaping = false, $addressReplyTo = false, $nameReplyTo = false
	) {
		if ($template = self::getTemplateByCode($templateCode)) {
			
			//Have the option to use Twig code in an email template
			if ($template['use_standard_email_template'] == 2) {
				//Call twig on the body, with the merge fields provided.
				$template['body'] = ze\twig::render("\n". $template['body'], $mergeFields);
				
				//Clear the merge fields so we don't do the merge field's string replacements later.
				$mergeFields = [];
			}
			
			if ($template['head']) {
				static::putHeadOnBody($template['head'], $template['body']);
			}
			if ($template['use_standard_email_template']) {
				static::putBodyInTemplate($template['body']);
			}
			if ($template['from_details'] == 'site_settings') {
				$template['email_address_from'] = ze::setting('email_address_from');
				$template['email_name_from'] = ze::setting('email_name_from');
			}
			
			if ($template['include_a_fixed_attachment'] && $template['selected_attachment']) {
				$document = ze\row::get('documents', ['file_id', 'privacy'], ['id' => $template['selected_attachment']]);
				
				if ($document['privacy'] != 'offline') {
					$file = ze\file::link($document['file_id']);
					
					//For Docstore symlinks, get the real file path.
					$attachments[] = realpath(rawurldecode($file));
				}
			}
			
			if (self::sendEmails(
				$rcpts,
				$template['subject'],  
				$template['email_address_from'],
				$template['email_name_from'],
				$template['body'], 
				$mergeFields,
				$attachments,
				$attachmentFilenameMappings,
				$template['id'],
				$disableHTMLEscaping,
				$addressReplyTo,
				$nameReplyTo,
				!$template['debug_override'] && $template['send_cc'] ? $template['cc_email_address'] : '',
				!$template['debug_override'] && $template['send_bcc'] ? $template['bcc_email_address'] : '',
				$template['debug_override'] ? $template['debug_email_address'] : ''
			)) {
				
				$sql = "
					UPDATE ". DB_PREFIX. "email_templates SET 
						last_sent = NOW()
					WHERE id = ". (int) $template['id'];
				ze\sql::update($sql);

				return true;
			}
		}
		
		return false;
	}
	
	//This function allows to completely override an email template body and subject line.
	public static function sendEmailsUsingTemplateNoMerge(
		$rcpts, $templateCode, $emailSubject = '', $emailBody = '',
		$attachments = [], $attachmentFilenameMappings = [],
		$disableHTMLEscaping = false, $addressReplyTo = false, $nameReplyTo = false
	) {
		if ($template = self::getTemplateByCode($templateCode)) {
			if (!$emailSubject) {
				$emailSubject = $template['subject'];
			}
			
			if (!$emailBody) {
				$emailBody = $template['body'];
			}
			
			if ($template['head']) {
				static::putHeadOnBody($template['head'], $emailBody);
			}
		
			if ($template['use_standard_email_template']) {
				static::putBodyInTemplate($emailBody);
			}
			
			if ($template['from_details'] == 'site_settings') {
				$template['email_address_from'] = ze::setting('email_address_from');
				$template['email_name_from'] = ze::setting('email_name_from');
			}
			
			if ($template['include_a_fixed_attachment'] && $template['selected_attachment']) {
				$document = ze\row::get('documents', ['file_id', 'privacy'], ['id' => $template['selected_attachment']]);
				
				if ($document['privacy'] != 'offline') {
					$file = ze\file::link($document['file_id']);
					
					//For Docstore symlinks, get the real file path.
					$attachments[] = realpath(rawurldecode($file));
				}
			}
			
			if (self::sendEmails(
				$rcpts,
				$emailSubject,  
				$template['email_address_from'],
				$template['email_name_from'],
				$emailBody, 
				$mergeFields = [],
				$attachments,
				$attachmentFilenameMappings,
				$template['id'],
				$disableHTMLEscaping,
				$addressReplyTo,
				$nameReplyTo,
				!$template['debug_override'] && $template['send_cc'] ? $template['cc_email_address'] : '',
				!$template['debug_override'] && $template['send_bcc'] ? $template['bcc_email_address'] : '',
				$template['debug_override'] ? $template['debug_email_address'] : ''
			)) {
				
				$sql = "
					UPDATE ". DB_PREFIX. "email_templates SET 
						last_sent = NOW()
					WHERE id = ". (int) $template['id'];
				ze\sql::update($sql);

				return true;
			}
		}
		
		return false;
	}
	
	//If this email template has HTML in the <head>, we'll need to send the email as a full webpage
	public static function putHeadOnBody(&$head, &$body) {
		
		if ($head && trim($head)) {
			$body =
'<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
'. $head. '
</head>
<body>
'. $body. '
</body>
</html>';
		}
	}
	
	public static function putBodyInTemplate(&$body) {
		$template = ze::setting('standard_email_template');
		ze\lang::applyMergeFields($template, ['email_body_content' => $body]);
		$body = $template;
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	
	
	//Deprecated, don't call!
	public static function getTemplateNamesOrderedByName() {
		return static::getTemplateNames();
	}
	
	//Deprecated, don't call!
	public static function getTemplates(){
		$rv=[];
		$sql = 'SELECT 
					id,
					code,
					template_name,
					subject,
					email_address_from,
					email_name_from,
					body
				FROM '
					. DB_PREFIX.  'email_templates';
		$result = ze\sql::select($sql);
		while($row=ze\sql::fetchAssoc($result))
			$rv[$row['template_name']]=$row;
		return $rv;
	}
	
	public static function checkTemplateIsProtectedAndGetCreatedDetails($templateCode) {
		$template = self::getTemplateByCode($templateCode);
		$templateIsProtected = false;
		$createdByModuleClassName = '';
		$createdByModuleDisplayName = '';
		$createdByAdmin = '';
			
		if (!$template['module_class_name']){
			//If a template doesn't have a module class name, then it was created by an admin and is not protected.
			$createdByAdmin = ze\admin::formatName($template['created_by_id']);
		} else {
			//If a template's code is a string, check if the module exists and is running.
			if ($moduleDetails = ze\module::details($template['module_class_name'], $fetchBy = 'class')) {
				if ($moduleDetails['status'] != 'module_not_initialized') {
					$templateIsProtected = true;
				}
				
				$createdByModuleClassName = $moduleDetails['class_name'];
				$createdByModuleDisplayName = $moduleDetails['display_name'];
			}
		}
		
		$result = [
			'protected' => $templateIsProtected,
			'created_by_class_name' => $createdByModuleClassName,
			'created_by_display_name' => $createdByModuleDisplayName,
			'created_by_admin' => $createdByAdmin,
			'date_created' => ze\admin::formatDateTime($template['date_created'], '_MEDIUM')
		];
		return $result;
	}
}