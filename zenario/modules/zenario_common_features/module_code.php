<?php
/*
 * Copyright (c) 2026, Tribal Limited
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


class zenario_common_features extends ze\moduleBaseClass {
	
	
	
	
	// Centralised list for user status
	public static function userStatus($mode, $value = false) {
		switch ($mode) {
			case ze\dataset::LIST_MODE_INFO:
				return ['can_filter' => false];
			case ze\dataset::LIST_MODE_LIST:
				return [
					'pending' => 'Pending extranet user', 
					'active' => 'Active extranet user', 
					'suspended' => 'Suspended extranet user', 
					'contact' => 'Contact'
				];
		}
	}
	
	
	protected function drawPageLink($pageName, $request, $page, $currentPage, $prevPage, $nextPage, $css = 'pag_page', &$links = [], $extraAttributes = []) {
		$link = [];
		
		$link['active'] = ($page === $currentPage) ? true : false;
		$link['request'] = $request ? $this->refreshPluginSlotAnchor($request) : '';
		$link['rel'] = $page === $prevPage ? 'prev' : ($page === $nextPage? 'next' : '');
		$link['text'] = $pageName;
		
		$links[] = $link;
		
		$extraAttributes = isset($extraAttributes[$page]) ? $extraAttributes[$page] : '';
		
		return '
			<span class="'. $css. ($page === $currentPage? '_on' : ''). '" ' . $extraAttributes . '><span>
				' . ($page && $page !== $currentPage? '<a ' : '<span ') .
					($page && $page !== $currentPage? ($request? $this->refreshPluginSlotAnchor($request) : '') : '').
					($page === $prevPage? ' rel="prev"' : ($page === $nextPage? ' rel="next"' : '')).
				'>'.
					$pageName.
				($page && $page !== $currentPage? '</a>' : '</span>') . '
			</span></span>';
	}
		
	public function pagCloseWithFNPLAlwaysVisible($currentPage, &$pages, &$html, &$links = [], $extraAttributes = []) {
		$this->pageNumbers($currentPage, $pages, $html, $links, $extraAttributes);
	}
	protected function pageNumbers($currentPage, &$pages, &$html, &$links = [], $extraAttributes = []) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	

	
	//
	//	Admin functions
	//
	
	
	
	
	public function handleAJAX() {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAllAdminSlotControls(
		&$controls,
		$cID, $cType, $cVersion,
		$slotName, $containerId,
		$level, $moduleId, $instanceId, $isVersionControlled
	) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($c = $this->runSubClass(static::class, false, $path)) {
			return $c->fillAdminBox($path, $settingGroup, $box, $fields, $values);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(static::class, false, $path)) {
			return $c->formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($c = $this->runSubClass(static::class, false, $path)) {
			return $c->validateAdminBox($path, $settingGroup, $box, $fields, $values, $changes, $saving);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(static::class, false, $path)) {
			return $c->saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(static::class, false, $path)) {
			return $c->adminBoxSaveCompleted($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(static::class, false, $path)) {
			return $c->adminBoxDownload($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public static function setMenuPath(&$fields, $field, $value) {
		
		if (!empty($fields[$field][$value])) {
			
			if (!empty($fields['parent_path_of__'. $field]['value'])) {
				$fields['path_of__'. $field][$value] =
					$fields['parent_path_of__'. $field]['value']. ' › '. $fields[$field][$value];
			
			} else {
				$fields['path_of__'. $field][$value] =
					$fields[$field][$value];
			}
		
		} else {
			unset($fields['path_of__'. $field][$value]);
		}
	}
	
	public static function sortFieldsByOrd($a, $b) {
		if (empty($a['ord']) || empty($b['ord']) || $a['ord'] == $b['ord']) {
			return 0;
		}
		return ($a['ord'] < $b['ord']) ? -1 : 1;
	}
	
	
	
	
	
	
	
	
	
	
	//
	//	Organizer functions
	//
	
	public function fillOrganizerNav(&$nav) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(static::class, false, $path)) {
			return $c->preFillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(static::class, false, $path)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(static::class, 'organizer', $path)) {
			return $c->handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(static::class, 'organizer', $path)) {
			return $c->organizerPanelDownload($path, $ids, $refinerName, $refinerId);
		}
	}
	
	public function fillAdminToolbar(&$adminToolbar, $cID, $cType, $cVersion) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function handleAdminToolbarAJAX($cID, $cType, $cVersion, $ids) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}

	public static function deleteCategory($id, $recurseCount = 0) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function jobCleanDirectories($serverTime) {
		return ze\cache::cleanDirs(true);
	}
	
	public static function jobPublishContent($serverTime) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}

	public static function jobUnpinContent($serverTime) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	//A scheduled task to delete stored content
	public static function jobDataProtectionCleanup($serverTime) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}

	public static function jobFetchDocumentExtract($serverTime) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	//
	//	Email functions
	//
	
	
	public static function getTemplateNames() {
		$rv = [];
		$result = ze\row::query('email_templates', ['code', 'template_name'], [], 'template_name');
		while ($row = ze\sql::fetchAssoc($result)) {
			$rv[$row['code']] = $row['template_name'];
		}
		return $rv;
	}
	
	//Deprecated, don't call!
	//Might get deleted
	public static function getTemplateNamesOrderedByName() {
		return static::getTemplateNames();
	}
	
	//Deprecated, don't call!
	public static function getTemplates() {
		$rv=[];
		$sql = '
			SELECT 
				id,
				code,
				template_name,
				subject,
				body
			FROM ' . DB_PREFIX.  'email_templates';
		$result = ze\sql::select($sql);
		while($row=ze\sql::fetchAssoc($result))
			$rv[$row['template_name']]=$row;
		return $rv;
	}
	
	public static function getTemplateById($id) {
		return ze\row::get('email_templates', true, ['id' => $id]);
	}
	
	public static function getTemplateByCode($code) {
		return ze\row::get('email_templates', true, ['code' => $code]);
	}
	
	public static function getLogRecordById($id) {
		return ze\row::get('email_template_sending_log', true, ['id' => $id]);
	}
	
	
	//If this email template has HTML in the <head>, we'll need to send the email as a full webpage
	public static function putHeadOnBody(&$head, &$body) {
		
		if ($head && trim($head)) {
			$body =
'<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<style>
'. $head. '
</style>
</head>
<body>
'. $body. '
</body>
</html>';
		}
	}
	
	public static function putBodyInTemplate(&$body) {
		$template = ze::setting('standard_email_template');
		ze\lang::applyMergeFields($template, ['email_body_content' => $body, 'cms_url' => ze\link::absolute()]);
		$body = $template;
	}
	
	
	
	public static function sendEmails(
		$rcpts, $subject, $addressFrom, $nameFrom, $body, $mergeFields = [], 
		$attachments=[], $attachmentFilenameMappings=[], 
		$templateNo = 0, $disableHTMLEscaping = false, 
		$addressReplyTo = false, $nameReplyTo = false,
		$ccs = '', $bccs = '', $debugOverride = '',
		$ignoreDebugMode = false, $formResponseId = 0
	) {
		
		if (!empty($mergeFields)) {
			$showTheWordEmptyInsteadOfBlanks = true;
			
			ze\lang::applyMergeFields($body, $mergeFields, '[[', ']]', !$disableHTMLEscaping, $showTheWordEmptyInsteadOfBlanks);
			ze\lang::applyMergeFields($subject, $mergeFields, '[[', ']]', !$disableHTMLEscaping, $showTheWordEmptyInsteadOfBlanks);
			ze\lang::applyMergeFields($nameFrom, $mergeFields, '[[', ']]', !$disableHTMLEscaping, $showTheWordEmptyInsteadOfBlanks);
			ze\lang::applyMergeFields($addressFrom, $mergeFields, '[[', ']]', !$disableHTMLEscaping, $showTheWordEmptyInsteadOfBlanks);
		}
		
		$result = true;
		
		foreach (array_unique(ze\ray::explodeAndTrim(str_replace(';', ',', $rcpts))) as $addressTo) {
			
			if($debugOverride){
				//template in debug mode
				$debugEmails = array_unique(ze\ray::explodeAndTrim(str_replace(';', ',', $debugOverride)));
				foreach($debugEmails as $debugEmail){
					$addressToOverriddenBy = false;
					$thisResult = ze\server::sendEmailAdvanced(
						$subject, $body, $debugEmail, $addressToOverriddenBy,
						false, $addressFrom, $nameFrom,
						$attachments, $attachmentFilenameMappings,
						'bulk', $isHTML = true, false,
						$addressReplyTo, $nameReplyTo,
						$ccs, $bccs, $action = 'To', $ignoreDebugMode
					);
					
					self::logEmail(
						$subject, $body, $addressTo, $addressToOverriddenBy? $addressToOverriddenBy : $debugOverride,
						$addressFrom, $nameFrom,
						$attachments, $attachmentFilenameMappings,
						$templateNo, $thisResult,
						$_POST, 
						$addressReplyTo, $nameReplyTo,
						$ccs, $formResponseId
					);
					
				}
			}else{
				//$debugOverride? $debugOverride : $addressTo, $addressToOverriddenBy
				$thisResult = ze\server::sendEmailAdvanced(
					$subject, $body,$addressTo, $addressToOverriddenBy,
					false, $addressFrom, $nameFrom,
					$attachments, $attachmentFilenameMappings,
					'bulk', $isHTML = true, false,
					$addressReplyTo, $nameReplyTo,
					$ccs, $bccs, $action = 'To', $ignoreDebugMode
				);
			
				self::logEmail(
					$subject, $body, $addressTo, $addressToOverriddenBy? $addressToOverriddenBy : $debugOverride,
					$addressFrom, $nameFrom,
					$attachments, $attachmentFilenameMappings,
					$templateNo, $thisResult,
					$_POST, 
					$addressReplyTo, $nameReplyTo,
					$ccs, $formResponseId
				);
			}
			
			$result &= $thisResult;
		}
		return $result;
	}
	
	public static function sendEmailsUsingTemplate(
		$rcpts, $templateCode, $mergeFields = [],
		$attachments = [], $attachmentFilenameMappings = [],
		$disableHTMLEscaping = false, $addressReplyTo = false, $nameReplyTo = false,
		$ignoreDebugMode = false, $customBody = null,
		$commentByAboveEmail = '', $commentAboveEmail = '', $formResponseId = 0
	) {
		if ($template = self::getTemplateByCode($templateCode)) {
			
			if (!is_null($customBody)) {
				$template['body'] = $customBody;
			}
			
			//Have the option to use Twig code in an email template
			if ($template['use_standard_email_template'] == 2) {
				//Call twig on the body, with the merge fields provided.
				$template['body'] = ze\twig::render("\n". $template['body'], $mergeFields);
				
				//Clear the merge fields so we don't do the merge field's string replacements later.
				$mergeFields = [];
			}
			
			if ($template['apply_css_rules']) {
				$cssRules = ze::setting('email_css_rules');
				static::putHeadOnBody($cssRules, $template['body']);
			}
			
			if ($template['use_standard_email_template']) {
				static::putBodyInTemplate($template['body']);
			}
			
			if ($commentByAboveEmail || $commentAboveEmail) {
				$template['body'] = $commentByAboveEmail . $commentAboveEmail . '<hr />' . $template['body'];
			}
			
			$template['email_address_from'] = ze::setting('email_address_from');
			$template['email_name_from'] = ze::setting('email_name_from');
			
			if ($template['include_a_fixed_attachment'] && $template['selected_attachment']) {
				$document = ze\row::get('documents', ['file_id', 'privacy'], ['id' => $template['selected_attachment']]);
				
				if (!empty($document) && is_array($document) && !empty($document['privacy']) && $document['privacy'] != 'offline') {
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
				$template['debug_override'] ? $template['debug_email_address'] : '',
				$ignoreDebugMode, $formResponseId
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
		$rcpts, $templateId, $emailSubject = '', $emailBody = '',
		$attachments = [], $attachmentFilenameMappings = [],
		$disableHTMLEscaping = false, $addressReplyTo = false, $nameReplyTo = false,
		$ignoreDebugMode = false
	) {
		if ($template = self::getTemplateById($templateId)) {
			if (!$emailSubject) {
				$emailSubject = $template['subject'];
			}
			
			if (!$emailBody) {
				$emailBody = $template['body'];
			}
			
			if ($template['apply_css_rules']) {
				$cssRules = ze::setting('email_css_rules');
				static::putHeadOnBody($cssRules, $emailBody);
			}
		
			if ($template['use_standard_email_template']) {
				static::putBodyInTemplate($emailBody);
			}
			
			$template['email_address_from'] = ze::setting('email_address_from');
			$template['email_name_from'] = ze::setting('email_name_from');
			
			if ($template['include_a_fixed_attachment'] && $template['selected_attachment']) {
				$document = ze\row::get('documents', ['file_id', 'privacy'], ['id' => $template['selected_attachment']]);
				
				if (!empty($document) && is_array($document) && !empty($document['privacy']) && $document['privacy'] != 'offline') {
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
				$template['debug_override'] ? $template['debug_email_address'] : '',
				$ignoreDebugMode
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
	
	public static function testSendEmailTemplate($id, $body, $adminDetails, $email, $subject, $emailAddresFrom, $emailNameFrom, $attachments = []) {
		//Identify this as a test email
		$subject .= ' | TEST SEND (ID ' . (int) $id . ')';
		
		//Attempt to send the email
		$emailOverriddenBy = false;
		return ze\server::sendEmailAdvanced(
			$subject,
			$body,
			$email,
			$emailOverriddenBy,
			$adminDetails['admin_first_name']. ' '. $adminDetails['admin_last_name'],
			$emailAddresFrom,
			$emailNameFrom,
			$attachments
		);
	}
	
	public static function logEmail(
		$subject, &$body, $addressTo, $addressToOverriddenBy,
		$addressFrom = false, $nameFrom = false,
		$attachments = [], $attachmentFilenameMappings = [],
		$templateNo = 0, $status = 'success',
		$senderCmsObjectArray = [],
		$addressReplyTo = false, $nameReplyTo = false,
		$ccs = false, $formResponseId = 0
	) {
		self::clearOldData();
		
		if ($addressFrom === false) {
			$addressFrom = \ze::setting('email_address_from');
		}
		if (!$nameFrom) {
			$nameFrom = \ze::setting('email_name_from');
		}
		
		//Check if this email should be logged
		$template = false;
		if ($templateNo) {
			$template = ze\row::get('email_templates', ['template_name', 'period_to_delete_log_headers', 'period_to_delete_log_content'], $templateNo);
			if ($template) {
				if ($template['period_to_delete_log_headers'] === '0'
				 || ($template['period_to_delete_log_headers'] === '' && ze::setting('period_to_delete_the_email_template_sending_log_headers') === '0')) {
					return false;
				}
			}
		}
		
		$sql = "
			INSERT INTO ". DB_PREFIX. "email_template_sending_log SET";
		
		if (!empty($senderCmsObjectArray['instanceId'])) {
			$sql .= "
				module_id = ". (int) ze\row::get('plugin_instances', 'module_id', ['id' => $senderCmsObjectArray['instanceId']]). ",
				instance_id = ". (int) ($senderCmsObjectArray['instanceId'] ?? 0). ",";
		}
		
		if (!empty($senderCmsObjectArray['cID'])) {
			$sql .= "
				content_id = ". (int) $senderCmsObjectArray['cID']. ",
				`content_type` = '". ze\escape::asciiInSQL($senderCmsObjectArray['cType'] ?? ''). "',
				content_version = ". (int) ($senderCmsObjectArray['cVersion'] ?? 0). ",";
		}
		
		if ($template) {
			$sql .= "
				email_template_id = ". (int) $templateNo. ",
				email_template_name = '". ze\escape::sql(($template['template_name'] ?? '')). "',";
		}
		
		$sql .= "
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
		if ($template) {
			if ($template['period_to_delete_log_content'] === '0') {
				$body = ze\admin::phrase('Body not saved because the email template setting for data deletion is set to "Don\'t save".');
			} elseif ($template['period_to_delete_log_content'] === '' && ze::setting('period_to_delete_the_email_template_sending_log_content') === '0') {
				$body = ze\admin::phrase('Body not saved because the site-wide setting for email data deletion is set to "Don\'t save".');
			}
		}

		if (strlen($body) < 100000) {
			$sql .= ",
				email_body = '". ze\escape::sql($body). "'";
		} else {
			$sql .= ",
				email_body = '". ze\escape::sql(ze\admin::phrase('Body too large to save')). "'";
		}
		
		if ($formResponseId) {
			$sql .= ",
				form_response_id = " . (int) $formResponseId;
		}

		ze\sql::update($sql, false, false);
		return true;
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
	
	
	public static function clearOldData($logResult = false) {
		$cleared = 0;
		
		$days = ze::setting('period_to_delete_error_log');
		if ($days && is_numeric($days)) {
			$logged = date('Y-m-d H:i:s');
			$date = date('Y-m-d', strtotime('-' . $days . ' day', strtotime($logged)));
			$sql = '
				DELETE FROM '. DB_PREFIX. 'error_404_log
				WHERE logged <= "' . ze\escape::sql($date) . '"';
			ze\sql::update($sql);
			
			$deletedErrorLogEntries= ze\sql::affectedRows();
			
			if ($logResult) {
				if ($deletedErrorLogEntries == 0) {
					echo ze\admin::phrase('Deleting error log entries: no action taken.');
				} elseif ($deletedErrorLogEntries > 0) {
					echo ze\admin::nPhrase(
						'Deleted 1 entry in the error log.',
						'Deleted [[count]] entries in the error log.',
						$deletedErrorLogEntries,
						['count' => $deletedErrorLogEntries]
					);
				}
				
				echo "\n";
			}
			
			$cleared += $deletedErrorLogEntries;
		}
		
		
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
					DELETE FROM '. DB_PREFIX. 'email_template_sending_log
					WHERE email_template_id = ' . (int)$row['id'];
				if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
					$sql .= '
						AND sent_datetime < "' . ze\escape::sql($date) . '"';
				}
				ze\sql::update($sql);
				
				$deletedHeadersForTemplatesWithIndividualSettings = ze\sql::affectedRows();
				
				if ($logResult) {
					if ($deletedHeadersForTemplatesWithIndividualSettings == 0) {
						echo ze\admin::phrase('Deleting headers for sent emails with individual settings: no action taken.');
					} elseif ($deletedHeadersForTemplatesWithIndividualSettings > 0) {
						echo ze\admin::nPhrase(
							'Deleted headers for 1 sent email with individual settings.',
							'Deleted headers for [[count]] sent emails with individual settings.',
							$deletedHeadersForTemplatesWithIndividualSettings,
							['count' => $deletedHeadersForTemplatesWithIndividualSettings]
						);
					}
					
					echo "\n";
				}
				
				$cleared += $deletedHeadersForTemplatesWithIndividualSettings;
			}
		}
		
		//Clear email template sending log for the rest
		$days = ze::setting('period_to_delete_the_email_template_sending_log_headers');
		if (is_numeric($days)) {
			$sql = '
				DELETE etsl.*
				FROM '. DB_PREFIX. 'email_template_sending_log etsl
				LEFT JOIN ' . DB_PREFIX . 'email_templates et
					ON etsl.email_template_id = et.id
				WHERE (et.period_to_delete_log_headers IS NULL OR et.period_to_delete_log_headers = "")';
			if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
				$sql .= '
					AND etsl.sent_datetime < "' . ze\escape::sql($date) . '"';
			}
			ze\sql::update($sql);
			
			$deletedTemplateHeaders = ze\sql::affectedRows();
			
			if ($logResult) {
				if ($deletedTemplateHeaders == 0) {
					echo ze\admin::phrase('Deleting headers for sent emails without individual settings: no action taken.');
				} elseif ($deletedTemplateHeaders > 0) {
					echo ze\admin::nPhrase(
						'Deleted headers for 1 sent email without individual settings.',
						'Deleted headers for [[count]] sent emails without individual settings.',
						$deletedTemplateHeaders,
						['count' => $deletedTemplateHeaders]
					);
				}
				
				echo "\n";
			}
			
			$cleared += $deletedTemplateHeaders;
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
					UPDATE '. DB_PREFIX. 'email_template_sending_log
					SET email_body = "[Email body deleted]"
					WHERE email_template_id = ' . (int)$row['id'];
				if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
					$sql .= '
						AND sent_datetime < "' . ze\escape::sql($date) . '"';
				}
				ze\sql::update($sql);
				
				$deletedTemplatesWithIndividualSettings = ze\sql::affectedRows();
				
				if ($logResult) {
					if ($deletedTemplatesWithIndividualSettings == 0) {
						echo ze\admin::phrase('Deleting content of sent emails with individual settings: no action taken.');
					} elseif ($deletedTemplatesWithIndividualSettings > 0) {
						echo ze\admin::nPhrase(
							'Deleted content of 1 sent email with individual settings.',
							'Deleted content of [[count]] sent emails with individual settings.',
							$deletedTemplatesWithIndividualSettings,
							['count' => $deletedTemplatesWithIndividualSettings]
						);
					}
					
					echo "\n";
				}
				
				$cleared += $deletedTemplatesWithIndividualSettings;
			}
		}
		
		//Clear email template sending log for the rest
		$days = ze::setting('period_to_delete_the_email_template_sending_log_content');
		if (is_numeric($days)) {
			$sql = '
				UPDATE '. DB_PREFIX. 'email_template_sending_log etsl
				LEFT JOIN ' . DB_PREFIX . 'email_templates et
					ON etsl.email_template_id = et.id
				SET etsl.email_body = "[Email body deleted]"
				WHERE (et.period_to_delete_log_content IS NULL OR et.period_to_delete_log_content = "")';
			if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
				$sql .= '
					AND etsl.sent_datetime < "' . ze\escape::sql($date) . '"';
			}
			ze\sql::update($sql);
			
			$deletedTemplates = ze\sql::affectedRows();
			
			if ($logResult) {
				if ($deletedTemplates == 0) {
					echo ze\admin::phrase('Deleting content of sent emails without individual settings: no action taken.');
				} elseif ($deletedTemplates > 0) {
					echo ze\admin::nPhrase(
						'Deleted content of 1 sent email without individual settings.',
						'Deleted content of [[count]] sent emails without individual settings.',
						$deletedTemplates,
						['count' => $deletedTemplates]
					);
				}
				
				echo "\n";
			}
			
			$cleared += $deletedTemplates;
		}
		
		return $cleared;
	}
	
	public static function deleteUserDataGetInfo($userIds) {
		if ($userIds) {
			$recordCount = ' ([[count]] found)';
		} else {
			$recordCount = '';
		}
		
		$sql = '
			SELECT COUNT(id)
			FROM ' . DB_PREFIX . 'user_signin_log
			WHERE user_id IN (' . ze\escape::in($userIds) . ')';
		$result = ze\sql::select($sql);
		$count = ze\sql::fetchValue($result);
		
		$userSignInLog = ze\admin::phrase('User sign-in log' . $recordCount, ['count' => $count]);
		
		$sql = '
			SELECT COUNT(id)
			FROM ' . DB_PREFIX . 'user_content_accesslog
			WHERE user_id IN (' . ze\escape::in($userIds) . ')';
		$result = ze\sql::select($sql);
		$count = ze\sql::fetchValue($result);
		
		$userContentAccessLog = ze\admin::phrase('User content access log' . $recordCount, ['count' => $count]);
		
		$totalCount = 0;
		if (!empty($userIds)) {
			foreach ($userIds as $userId) {
				if ($userEmail = ze\row::get('users', 'email', $userId)) {
					$sql = '
						SELECT COUNT(id)
						FROM '. DB_PREFIX. 'email_template_sending_log
						WHERE email_address_to = "' . ze\escape::sql($userEmail) . '"';
					$result = ze\sql::select($sql);
					$count = ze\sql::fetchValue($result);
					
					$totalCount += $count;
				}
			}
		}
		
		$sentEmailLogResults = ze\admin::phrase('Log of sent emails' . $recordCount, ['count' => $totalCount]);
		
		return implode('<br />', [$userSignInLog, $userContentAccessLog, $sentEmailLogResults]);
	}
	
	public static function eventUserDeleted($userId, $deleteAllData) {
		//When deleting all data about a user, delete their sent email log message content but keep the header
		if ($deleteAllData) {
			if ($userEmail = ze\row::get('users', 'email', $userId)) {
				$sql = '
					UPDATE '. DB_PREFIX. 'email_template_sending_log
					SET email_body = "[Email body deleted]", email_address_to = "[User deleted]"
					WHERE email_address_to = "' . ze\escape::sql($userEmail) . '"';
				ze\sql::update($sql);
			}
		}
	}
	
	
	
	public static function canCreateAdditionalAdmins() {
		$limit = ze\site::description('max_local_administrators');
		return !$limit || ze\row::count('admins', ['is_client_account' => 1, 'status' => 'active']) < $limit;
	}
	
	//Get the salutation LoV
	public static function getSalutations($mode, $value = false) {
		switch ($mode) {
			case ze\dataset::LIST_MODE_INFO:
				return ['can_filter' => false];
			case ze\dataset::LIST_MODE_LIST:
				return ze\row::getValues('lov_salutations', 'name', [], 'name', 'name');
			case ze\dataset::LIST_MODE_VALUE:
				return $value;
		}
	}
	
	public static function getTranslationsAndPluginsLinkingToThisContentItem($ids, &$box, &$fields, &$values, $panelName, $totalTranslationCount, $getPlugins, $getTranslations) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function addToMessage(&$message, $plugabbleCount, $versionControlledCount, $row, $linkToLibraryPlugin, $linkToVersionControlledPlugin) {
		if ($plugabbleCount) {
			$message .= ze\admin::nPhrase(
				'<p>There is [[count]] [[display_name]] plugin linking to this content item: [[link]].</p>',
				'<p>There are [[count]] [[display_name]] plugins linking to this content item: [[link]] and [[count2]] other[[s]].</p>', 
				$plugabbleCount,
				[
					'count' => $plugabbleCount, 
					'count2' => $plugabbleCount - 1, 
					'display_name' => $row['display_name'], 
					'link' => $linkToLibraryPlugin,
					's' => ($plugabbleCount - 1) == 1 ? '' : 's']);
		}
		if ($versionControlledCount) {
			$message .= ze\admin::nPhrase(
				'<p>There is [[count]] [[display_name]] version controlled plugin linking to this content item: [[link]].</p>',
				'<p>There are [[count]] [[display_name]] version controlled plugins linking to this content item: [[link]] and [[count2]] other plugin[[s]].</p>',
				$versionControlledCount,
				[
					'count' => $versionControlledCount, 
					'count2' => $versionControlledCount - 1,
					'display_name' => $row['display_name'], 
					'link' => $linkToVersionControlledPlugin,
					's' => ($versionControlledCount - 1) == 1 ? '' : 's']);
		}
	}
	
	public static function deleteOrTrashTranslations(&$fields, &$values, $tabName) {
		$startAt = 1;
		foreach (ze\tuix::loopThroughMultipleRows($fields, $startAt, $tabName, 'translation') as $suffix => $deletePressed) {
			
			$tagId = $values[$tabName . '/translation'. $suffix];
			
			ze\content::removeFormattingFromTag($tagId);
			
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			
			switch ($values[$tabName . '/action'. $suffix]) {
				case 'delete':
					if (ze\contentAdm::allowDelete($cID, $cType) && ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType)) {
						if (ze::in($cType, 'audio', 'document', 'picture', 'video')) {
							ze\contentAdm::deleteContentItem($cID, $cType);
						} else {
							ze\contentAdm::deleteDraft($cID, $cType);
						}
					}
					break;
					
				case 'trash':
					if (ze\contentAdm::allowTrash($cID, $cType) && ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
						ze\contentAdm::trashContent($cID, $cType);
					}
					break;
			}
		}
	}
	
	public static function processAdminToolbarLanguage(&$buttonsArray, $lang, $translation, &$ord, &$n, $isOG, $isDefault, $addWordDefaultToLabel, $exists, $importantGetRequests) {
		$buttonsArray = json_decode(str_replace('znz', ++$n, str_replace('zlangIdz', preg_replace('/[^a-z0-9_-]/', '', $lang['id']), json_encode($buttonsArray))), true);
			//N.b. language codes can only contain the symbols "a-z0-9_-".
			//The only reason this replacement is in any way safe is because they can't contain special characters.
		foreach ($buttonsArray as &$button) {
			$button['ord'] = ++$ord;
			
			//Have a slightly different message when talking about the original content item
			if ($isOG && isset($button['label_og'])) {
				$button['label'] = $button['label_og'];
			}
			unset($button['label_og']);
			
			//Apply merge fields to the label
			if (isset($button['label'])) {
				if ($isDefault && $addWordDefaultToLabel) {
					$button['label'] .= ' original';
				}
				ze\lang::applyMergeFields($button['label'], $lang);
			}
			
			if (isset($button['css_class'])) {
				ze\lang::applyMergeFields($button['css_class'], $lang);
			}
			
			if ($exists) {
				//Set a link to the content item
				if (isset($button['frontend_link'])) {
					$button['frontend_link'] =
						ze\link::toItem($translation['id'], $translation['type'], false, $importantGetRequests, $translation['alias']);
						//Note: The ze\link::toItem() function has the option to automatically add the $importantGetRequests.
						//However as we're actually handling an AJAX request, and are not on the page itself, this
						//option won't work here so we need to manually pass in the $importantGetRequests.
				}
			
			}
		}
		unset($button);
	}
	
	public static function getEmailTextContentItemPublished() {
		$emailText = "
Dear [[admin_first_name]] [[admin_last_name]],

You are receiving this email because a content item ([[content_type]], \"[[content_item_title]]\") has been published.

Content item: [[content_item]]
Date and time for publishing: [[date_and_time]]
Requesting admin: [[requesting_admin]]

<p style=\"text-align: center;\"><a style=\"background: #015ca1; color: white; text-decoration: none; padding: 20px 40px; font-size: 16px;\" href=\"[[content_item_url]]\">View content item</a></p>";
		
		return nl2br($emailText);
	}
	
	//Called when a 404 error is triggered to log it
	public static function log404Error($pageAlias, $httpReferer = '') {
		
		$pageAlias = ze\escape::utf8($pageAlias);
		
		$logged = date('Y-m-d H:i:s');
		if (strlen($pageAlias) > 255) {
			$pageAlias = mb_substr($pageAlias, 0, 252, 'UTF-8') . '...';
		}
		if (strlen($httpReferer) > 65535) {
			$httpReferer = mb_substr($httpReferer, 0, 65532, 'UTF-8') . '...';
		}
		ze\row::insert('error_404_log', ['logged' => $logged, 'page_alias' => $pageAlias, 'referrer_url' => $httpReferer]);
		
		//Delete old log entries according to site setting. Only do this when the scheduled task cannot run.
		if (
			!ze\module::inc('zenario_scheduled_task_manager')
			|| !zenario_scheduled_task_manager::checkScheduledTaskRunning('jobDataProtectionCleanup')
		) {
			$days = ze::setting('period_to_delete_error_log');
			if ($days && is_numeric($days)) {
				$date = date('Y-m-d', strtotime('-' . $days . ' day', strtotime($logged)));
				$sql = '
					DELETE FROM '. DB_PREFIX. 'error_404_log
					WHERE logged <= "' . ze\escape::sql($date) . '"';
				ze\sql::update($sql);
			}
		}
	}
	
	public static function getExportWindowFilters() {
		$selectedFilters = [];
		
		if (isset($_GET['_cms_filters'])) {	
			$filters = json_decode($_GET['_cms_filters'], true);
			if (!empty($filters)) {
				foreach ($filters as $filterColumn => $filterValues) {
					if (!empty($filterValues)) {
						$filterValue = $filters[$filterColumn]['v'] ?? '';
						
						if ($filterValue) {
							//Check if this is a range of dates
							if (strpos($filterValue, ',') !== false) {
								$dates = explode(',', $filterValue);
								$replace = ['filter_column' => $filterColumn];
								
								if ($dates[0]) {
									$phrase = '[[filter_column]] is on or after "[[filter_value_start]]"';
									$replace['filter_value_start'] = $dates[0];
									
									if ($dates[1]) {
										$phrase .= ', and on or before "[[filter_value_end]]"';
										$replace['filter_value_end'] = $dates[1];
									}
								} elseif ($dates[1]) {
									$phrase = '[[filter_column]] is on or before "[[filter_value_end]]"';
									$replace['filter_value_end'] = $dates[1];
								}
							} else {
								if (!empty($filters[$filterColumn]['not'])) {
									$phrase = '[[filter_column]] is not "[[filter_value]]"';
								} else {
									$phrase = '[[filter_column]] is "[[filter_value]]"';
								}
								
								$replace = ['filter_column' => $filterColumn, 'filter_value' => $filterValue];
							}
						}
						
						$selectedFilters[] = ze\admin::phrase($phrase, $replace);
					}
				}
			}
		}
		
		if (isset($_GET['_cms_searchTerm'])) {
			$searchTerms = $_GET['_cms_searchTerm'];
			$selectedFilters[] = ze\admin::phrase('records matching the search term(s) "[[search_terms]]"', ['search_terms' => htmlspecialchars($searchTerms)]);
		}
		
		if ($selectedFilters) {
			return ze\admin::phrase('Selected filters:') . '<ul><li>' . implode('</li><li>', $selectedFilters) . '</li></ul>';
		}
		
		return '';
	}
	
	public static function languageImportResults($languageId, $numberOf, $error = false, $changeButtonHTML = false) {
		$fileImportedString = '';
		$replace = $numberOf;
		if ($languageId && ($language = ze\lang::name($languageId))) {
			$fileImportedString = 'File with [[language_name]] translations imported.';
			$replace['language_name'] = $language;
		}
		
		$replace['default_language'] = ze\lang::name(ze::$defaultLang);
		
		$fileImportedString .= '
			
			Translations for [[added]] phrase(s) were added.
			
			Translations for [[updated]] phrase(s) were updated.
			
			Translations of [[protected]] phrase(s) were skipped because they were protected.
			
			Translations of [[skipped]] phrase(s) were skipped because they do not exist in [[default_language]].
			
			Translations of [[restored_from_archive]] phrase(s) were restored from archive.';
		
		
		
		if ($error) {
			echo $error;

		} elseif ($numberOf['wrong_language']) {
			echo ze\admin::phrase("The language pack you are trying to import is for a different language.");
			
		} elseif ($numberOf['language_not_enabled']) {
			echo ze\admin::phrase("The language pack you are trying to import is for a language that is not enabled on this site.");

		} elseif ($numberOf['upload_error']) {
			echo ze\admin::phrase("There was an error with your file upload. Please make sure you have provided a valid file, in the format required by this tool.").
					ze\admin::phrase($fileImportedString, $replace);
	
		} elseif ($numberOf['added'] || $numberOf['updated']) {
			ze\escape::bFlag('MESSAGE_TYPE', 'success');
			
			if ($changeButtonHTML) {
				ze\escape::bFlag('BUTTON_HTML', '<input type="button" class="submit zenario_gp_button" value="'. ze\admin::phrase('OK'). '" onclick="zenarioO.reloadPage(\'zenario__languages/panels/languages\');"/>');
			}
			
			echo ze\admin::phrase($fileImportedString, $replace);
	
		} else {
			ze\escape::bFlag('MESSAGE_TYPE', 'warning');
			echo ze\admin::phrase("No phrases were imported.");
	
			if ($numberOf['protected'] > 0) {
				echo ze\admin::phrase(" [[protected]] phrase(s) were protected and not overwritten.", $numberOf);
			}
		}
	}
}