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

class zenario_email_template_manager extends module_base_class {

	public static function getTemplateNames($rv=array()){
		$sql = 'SELECT 
					code,
					template_name 
				FROM '
					. DB_NAME_PREFIX.  'email_templates';
		$result = sqlQuery($sql);
		while($row=sqlFetchAssoc($result))
			$rv[$row['code']]=$row['template_name'];
		return $rv;
	}

	public static function getTemplateNamesOrderedByName($rv=array(''=> '-- Please select an Email Template --')){
		$sql = 'SELECT 
					code,
					template_name 
				FROM '
					. DB_NAME_PREFIX.  'email_templates
				ORDER BY template_name';
		$result = sqlQuery($sql);
		while($row=sqlFetchAssoc($result))
			$rv[$row['code']]=$row['template_name'];
		//sqlArraySort($rv);
		return $rv;
	}
	
	public static function getTemplates(){
		$rv=array();
		$sql = 'SELECT 
					id,
					code,
					template_name,
					subject,
					email_address_from,
					email_name_from,
					body
				FROM '
					. DB_NAME_PREFIX.  'email_templates';
		$result = sqlQuery($sql);
		while($row=sqlFetchAssoc($result))
			$rv[$row['template_name']]=$row;
		return $rv;
	}


	public static function getTemplateById($id){
		$rv=array();
		$sql = 'SELECT 
					id,
					code,
					template_name,
					subject,
					email_address_from,
					email_name_from,
					body, 
					created_by_id,
					modified_by_id
				FROM '
					. DB_NAME_PREFIX.  'email_templates
				WHERE 
					id=' . (int) $id;
				
		$result = sqlQuery($sql);
		if (sqlNumRows($result)===1)
			$rv=sqlFetchAssoc($result);
		
		return $rv;
	}

	public static function getTemplateByCode($code){
		$rv=array();
		$sql = "SELECT 
					id,
					code,
					template_name,
					subject,
					email_address_from,
					email_name_from,
					body, 
					created_by_id,
					modified_by_id
				FROM "
					. DB_NAME_PREFIX.  "email_templates
				WHERE 
					code='" . sqlEscape($code) . "'";
				
		$result = sqlQuery($sql);
		if (sqlNumRows($result)===1)
			$rv=sqlFetchAssoc($result);
		
		return $rv;
	}

	public static function getTemplatesByNameIndexedByCode($name,$strict=true){
		$rv=array();
		$sql = "SELECT 
					id,
					code,
					template_name,
					subject,
					email_address_from,email_name_from,
					body, 
					created_by_id,
					modified_by_id
				FROM "
					. DB_NAME_PREFIX.  "email_templates
				WHERE 
					template_name " . ($strict? "='":" like '%")  . sqlEscape($name) . ($strict?"'":"%'");
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)){
			$rv[$row['code']] = $row;
		}
		return $rv;
	}
	
	public static function getLogRecordById($ID){

		$rv = array();

		$sql ="SELECT 
					id,
					module_id,
					instance_id,
					content_id,
					content_type,
					content_version,
					email_template_id,
					email_template_name,
					email_subject,
					email_address_to,
					email_address_replyto,
					email_name_replyto,
					email_address_from,
					email_name_from,
					email_body,
					sent_datetime
				FROM
					" . DB_NAME_PREFIX . ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX . "email_template_sending_log 
				WHERE
					id = " . (int) $ID;
		$result = sqlQuery($sql);
		if ($row = sqlFetchAssoc($result)){
			$rv=$row;
		}
		return $rv;
		
	}
	
		
	
	public static function sendEmails(	$rcpts, $subject, $addressFrom, $nameFrom, $body, $mergeFields=array(), 
										$attachments=array(), $attachmentFilenameMappings=array(), 
										$templateNo = 0, $disableHTMLEscaping = false, 
										$addressReplyTo = false, $nameReplyTo = false){

		mb_regex_encoding('UTF-8');
		foreach($mergeFields as $K=>$V){

				$search = '\[\[' . $K . '\]\]';
				$replace = $disableHTMLEscaping?$V:nl2br(htmlspecialchars($V));
				$body = mb_ereg_replace($search, $replace, $body);
				$subject = mb_ereg_replace($search, $replace, $subject);
				$nameFrom = mb_ereg_replace($search, $replace, $nameFrom);
				$addressFrom = mb_ereg_replace($search, $replace, $addressFrom);
		}
		
		$regex_filter = '\[\[[^\]]*\]\]';
		$body = mb_ereg_replace ($regex_filter,'',$body);
		$subject = mb_ereg_replace ($regex_filter,'',$subject);
		$nameFrom = mb_ereg_replace ($regex_filter,'',$nameFrom);
		$addressFrom = mb_ereg_replace ($regex_filter,'',$addressFrom);
		
		if($addressReplyTo) {
			$addressReplyTo = mb_ereg_replace ($regex_filter,'',$addressReplyTo);
		}
		if($nameReplyTo) {
			$nameReplyTo = mb_ereg_replace ($regex_filter,'',$nameReplyTo);
		}
		
		$addresses =preg_split("/[\,\;]+/",$rcpts);	
		$uniqAddresses = array();
		foreach ($addresses as $addr){
			if ($addr = trim($addr)) {
				$uniqAddresses[$addr] = $addr;
			}
		}
		$result = true;
		foreach ($uniqAddresses as $addr){
			
			if (sendEmail($subject, $body, $addr, $addrOverriddenBy, false,$addressFrom,$nameFrom,$attachments,$attachmentFilenameMappings,'bulk',
					true, false, $addressReplyTo, $nameReplyTo)){
				self::logEmail($subject, $body, $addr, $addrOverriddenBy, $addressFrom,$nameFrom,$attachments,$attachmentFilenameMappings,$templateNo,true,$_POST, 
						$addressReplyTo, $nameReplyTo);
			} else {
				$result = false;
				self::logEmail($subject, $body, $addr, $addrOverriddenBy, $addressFrom,$nameFrom,$attachments,$attachmentFilenameMappings,$templateNo,false,$_POST, 
						$addressReplyTo, $nameReplyTo);
			}
		}
		return $result;
	}
	
	public static function logEmail($subject, $body, $addr, $addrOverriddenBy,$addressFrom,$nameFrom,$attachments,
			$attachmentFilenameMappings,$templateNo,$status,$senderCmsObjectArray = array(), $addressReplyTo=false, $nameReplyTo=false){
		
		$sql = "INSERT INTO 
					" . DB_NAME_PREFIX . ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX . "email_template_sending_log
				SET 
					module_id = (
								SELECT 
									module_id 
								FROM 
									" . DB_NAME_PREFIX . "plugin_instances
								WHERE 
									id = " . (int) arrayKey($senderCmsObjectArray,'instanceId') . "
								LIMIT 1
									),
					instance_id = NULLIF(" . (int) arrayKey($senderCmsObjectArray,'instanceId') . ",0),
					content_id = NULLIF(" . (int) arrayKey($senderCmsObjectArray,'cID') . ",0),
					content_type = NULLIF('" . sqlEscape(arrayKey($senderCmsObjectArray,'cType')) . "',''),
					content_version = NULLIF(" . (int) arrayKey($senderCmsObjectArray,'cVersion') . ",0),
					email_template_id =NULLIF(" . (int) $templateNo . ",0),
					email_template_name = (
									SELECT 
										template_name 
									FROM 
										" . DB_NAME_PREFIX . "email_templates
									WHERE 
										id = " . (int) $templateNo  . " 
									LIMIT 1
										),
					email_subject ='" . sqlEscape($subject) . "' ,
					email_address_to ='" .sqlEscape($addr) . "',
					email_address_to_overridden_by ='" .sqlEscape($addrOverriddenBy) . "',
					email_address_from ='" .sqlEscape($addressFrom) . "',
					email_name_from ='" . sqlEscape($nameFrom) . "',
					email_body ='" . sqlEscape($body) . "',
					attachment_present = " . (count($attachments)?1:0) . ",
					sent_datetime = NOW(), 
					`status` = '" . ($status?'success':'failure') . "'
				";
		
		if($addressReplyTo) {
			$sql .= ", email_address_replyto ='" . sqlEscape($addressReplyTo) . "'";
		}
		if($nameReplyTo) {
			$sql .= ", email_name_replyto ='" . sqlEscape($nameReplyTo) . "'";
		}
		
		sqlQuery($sql);

	}
	
	
	
	public static function sendEmailsUsingTemplate($rcpts,$templateCode,$mergeFields=array(),$attachments=array(),$attachmentFilenameMappings=array(),
			$disableHTMLEscaping = false, $addressReplyTo = false, $nameReplyTo = false){
		$template = self::getTemplateByCode($templateCode);
		if (count($template)!==0){
			if (self::sendEmails( 	$rcpts,
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
									$nameReplyTo)) {

				$sql = "UPDATE "
							. DB_NAME_PREFIX . "email_templates
						SET 
							last_sent = NOW()
						WHERE
							id = " . (int) $template['id'];
				sqlQuery($sql);

				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require funIncPath(__FILE__, __FUNCTION__);
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...your PHP code...//
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
}