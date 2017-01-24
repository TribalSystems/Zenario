<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


class zenario_form_input_handler extends module_base_class {
	
	var $mergeFields = array();
	
	
	function init(){
		return true;
		
	}
	
	private function checkRuleData($data,$ruleId=0){
		if (!checkPriv('_PRIV_SET_EMAIL_ROUTING'))
			return "Error. You are not allowed to manipulate this Rule.";
			
		if (!$data['ruleset_name'])
			return "Error. Please enter a name for this set of rules.";
		if (!$data['match_field'])
			return "Error. Please enter the name of the form key to match.";
	
		
		if ($ruleId) {
			$sql = 'SELECT 
						1 
					FROM ' 
						. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets 
					WHERE 
						id=' . (int) $ruleId;
			$result = sqlQuery($sql);
			if (sqlNumRows($result)==0)
				return "Error. Ruleset no longer exists in the database.";
		} else {
			$sql = 'SELECT 
						1 
					FROM ' 
						. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . "rule_sets 
					WHERE 
						name='" . sqlEscape($data['ruleset_name']) . "'";
			$result = sqlQuery($sql);
			if (sqlNumRows($result)==1)
				return 'Error. Ruleset ' . $data['ruleset_name'] . ' already exists.';
		}

		$sets=array();
		$indexes = $this->getIndexes($data);
		$i=1;
		foreach ($indexes as $idx){
			if ($idx!=0){
				$sets[$i-1]=$this->extractRuleSet($data,$idx);
				$i++;
			}
		}
			
		if (count($sets)==0)
				return "Error. Please define at least one rule." ;
		
		foreach ($sets as $K=>$S){
			$K++;
			if (!$S['cmp_value'])
				return "Error. Please enter the value of the form field to match for rule " . $indexes[$K] . "." ;
			if (!$S['email_to'])
				return "Error. Please enter a destination email address (or the name of a form field containing an email address) for Rule " . $indexes[$K] . "." ;
			// Removed email format validation from here, as we need to accept form input keys as well
			if (!$S['template_no'])
				return "Error. Please select an email template for rule " . $indexes[$K] . "." ;
			if ((strtolower($S['cmp_type']) !='equals') &&
				(strtolower($S['cmp_type']) !='not_equals') &&
				(strtolower($S['cmp_type']) !='starts_with') &&
				(strtolower($S['cmp_type']) !='match_regexp'))
				return "Error. Please select a comparison type for rule " . $indexes[$K] . "." ;
		}
	}
	
	private function addOrUpdateRuleCheck($data,$ruleId=0){
		return $this->checkRuleData($data,$ruleId);
	}


	private function addOrUpdateRule($data,&$ruleSetId=0) {
		if (checkPriv('_PRIV_SET_EMAIL_ROUTING')){
			if ($this->addOrUpdateRuleCheck($data,$ruleSetId)==''){
					$sql  = 'INSERT INTO '  . DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets ';
					$sql .= '(' ;
					if ($ruleSetId<>0)
						$sql .= ' `id`,';
					$sql .= '`name`,`creation_datetime`,`modification_datetime`';
					$sql .=' ) ';
					$sql .= " VALUES ";
					$sql .= "(";
					if ($ruleSetId<>0)
						$sql .= (int)$ruleSetId . ',';
					$sql .= "'" . sqlEscape($data['ruleset_name']) . "',"  ;
					$sql .= '  NOW(), NOW()';
					$sql .= ') ON DUPLICATE KEY UPDATE ' ;
					$sql .= "name='" .sqlEscape($data['ruleset_name']) . "'";
					$sql .= ",modification_datetime=NOW()";
		
					sqlUpdate($sql);
					$ruleSetId = sqlInsertId();
	
					$sets=array();
					$indexes = $this->getIndexes($data);
					$i=1;
					foreach ($indexes as $idx){
						if ($idx!=0){
							$sets[$i-1]=$this->extractRuleSet($data,$idx);
							$i++;
						}
					}	
			
					$sql = 'DELETE FROM ' . DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX  . 'routing_rules WHERE rule_set_id=' .  (int)$ruleSetId;
					sqlQuery($sql);
					foreach($sets as $K=>$set){
						$sql  = 'INSERT INTO '  . DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'routing_rules ';
						$sql .= '(`rule_set_id`,`rule_type_name`,`object_name`,`matching_value`,`email_template_number`,`destination_address`)' ;
						$sql .='VALUES';
						$sql .='(';
						$sql .= (int)$ruleSetId;
						$sql .= ",'" . sqlEscape($set['cmp_type']) . "'";
						$sql .= ",'" . sqlEscape($data['match_field']) . "'";
						$sql .= ",'" . sqlEscape($set['cmp_value']) . "'";
						$sql .= ",'" . sqlEscape($set['template_no']) . "'";
						$sql .= ",'" . sqlEscape($set['email_to']) . "'";
						$sql .=')';
						
						sqlUpdate($sql);
						if (!sqlAffectedRows()) {
							return 'Error. Query failed creating routing rule.';
						}
					}
				}
			}
		}

	private function deleteRuleSet($ruleSetId){
		$sql = 'DELETE FROM ' 
					. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'routing_rules 
				WHERE 
					rule_set_id=' . (int) $ruleSetId;
		sqlQuery($sql);

		$sql = 'DELETE FROM ' 
					. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets 
				WHERE 
					id=' . (int) $ruleSetId;
		
		sqlUpdate($sql);
		if (sqlAffectedRows()) {
			return '';
		} else {
			return 'Error. Deleting rule query fail.';
		}
	}

	private function deleteRuleSetRule($ruleId, $ruleSetId){
		$sql = 'DELETE FROM '
				. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'routing_rules
				WHERE
					rule_set_id=' . (int) $ruleSetId
					. ' AND id=' . (int) $ruleId;
		sqlUpdate($sql);
		if (sqlAffectedRows()) {
			return '';
		} else {
			return 'Error. Deleting rule query fail.';
		}
	}
	
	private function updateRuleLastUse($setId){
		sqlUpdate('UPDATE ' . DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets set last_rule_matching_datetime=NOW() WHERE id='. (int)$setId);
	}
	
	
	private function getRules($ruleSetId=0){
		$rv=array();
		$sql = 'SELECT 
					id,
					rule_set_id,
					rule_type_name,
					object_name,
					matching_value,
					email_template_number,
					destination_address 
				FROM ' 
					. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'routing_rules ';
		if ($ruleSetId)
			$sql .= " 
				WHERE 
					rule_set_id=" .  (int) $ruleSetId;
		$sql .= ' ORDER BY 
					id';
		$result = sqlQuery($sql);
		while($row=sqlFetchAssoc($result))
			$rv[$row['id']]=$row;
		return $rv;
	}


	private function getRuleSets($ruleSetId=0){
		$rv=array();
		$sql = 'SELECT 
					id,
					name,
					creation_datetime,
					modification_datetime,
					last_rule_matching_datetime 
				FROM '  
					. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets ';
		if ($ruleSetId)
			$sql .= " 
				WHERE 
					id=" .  (int) $ruleSetId;
		$sql .= '
				ORDER BY id';
		$result = sqlQuery($sql);
		while($row=sqlFetchAssoc($result))
			$rv[$row['id']]=$row;
		return $rv;
	}

	private function getFormInstances($instanceId=0){
		$rv = array();
		$sql = 'SELECT 
					plugin_instance_id,
					plugin_instance_name 
				FROM ' 
					.  DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'form_submissions ';
		if ($instanceId){
			$sql .= ' WHERE 
							plugin_instance_id='.(int) $instanceId ;
		}
		$sql .= ' GROUP BY 
						plugin_instance_id,
						plugin_instance_name';
		
		$result = sqlQuery($sql);
		while($row=sqlFetchAssoc($result))
			$rv[$row['plugin_instance_id']]=$row;
		return $rv;
				
	}

	static function getFormSubmissionsByInstanceId($instanceId){
		$rv = array();
		$sql = 	'SELECT 
					id,
					plugin_instance_id,
					plugin_instance_name,
					submission_datetime,
					user_id 
				FROM ' 
					. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'form_submissions ';
		$sql .= ' WHERE 
					plugin_instance_id=' . (int) $instanceId;
		$result=sqlQuery($sql);
		
		if (sqlNumRows($result)>0) {
			while ($row = sqlFetchArray($result)) {
				$rv[] = $row;
			}
			
			return $rv;
		} else {
			return false;
		}
	}

	static function getFormSubmissionsByContentItem($cID,$cType){
		$rv = array();
		$sql = 	'SELECT 
					id,
					plugin_instance_id,
					plugin_instance_name,
					submission_datetime,
					user_id 
				FROM ' . DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'form_submissions ';
		$sql .= ' WHERE content_id=' . (int) $cID . '
					AND content_type = "' . sqlEscape($cType) . '"';
		$result=sqlQuery($sql);
		
		if (sqlNumRows($result)>0) {
			while ($row = sqlFetchArray($result)) {
				$rv[] = $row;
			}
			
			return $rv;
		} else {
			return false;
		}
	}

	static function getFormSubmissionsDataByInstanceId ($instanceId) {
		$rv = array();
		if (checkPriv('_PRIV_VIEW_USER')){
			if ($formSubmissions = self::getFormSubmissionsByInstanceId($instanceId)) {
				foreach ($formSubmissions as $formSubmission) {
					$sql = 	'SELECT 
								submission_id,
								ordinal,label,
								value,
								attachment 
							FROM ' 
								. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'form_submission_data 
							WHERE 
								submission_id=' . (int) $formSubmission['id'];
					$result=sqlQuery($sql);
					if (sqlNumRows($result)>0) {
						while ($row=sqlFetchArray($result)){
							$rv[$formSubmission['id']][] = $row;
						}
					}
				}
				
				return $rv;
			} else {
				return false;
			}
		}
	}

	static function getFormSubmissionDataLabelsByInstanceId ($instanceId) {
		if ($formSubmissions = self::getFormSubmissionsByInstanceId($instanceId)) {
			$labels = array();
		
			foreach ($formSubmissions as $formSubmission) {
				$sql = "SELECT 
							label
						FROM " 
							. DB_NAME_PREFIX. ZENARIO_FORM_INPUT_HANDLER_PREFIX. "form_submission_data
						WHERE 
							submission_id = ". (int) $formSubmission['id'] . "
						GROUP BY 
							label
						ORDER BY 
							label";
						
				$result = sqlQuery($sql);
				
				if (sqlNumRows($result)>0) {
					while ($row = sqlFetchAssoc($result)) {
						if (!in_array($row['label'],$labels)) {
							$labels[] = $row['label'];
						}
					}
				}
			}
			
			return $labels;
		} else {
			return false;
		}
	}

	static function getFormSubmissionDataLabelsByContentItem ($cID, $cType) {
		if ($formSubmissions = self::getFormSubmissionsByContentItem($cID, $cType)) {
			$labels = array();
		
			foreach ($formSubmissions as $formSubmission) {
				$sql = "SELECT 
							label
						FROM "
							. DB_NAME_PREFIX. ZENARIO_FORM_INPUT_HANDLER_PREFIX. "form_submission_data
						WHERE 
							submission_id = ". (int) $formSubmission['id'] . "
						GROUP BY 
							label
						ORDER BY 
							label";
						
				$result = sqlQuery($sql);
				
				if (sqlNumRows($result)>0) {
					while ($row = sqlFetchAssoc($result)) {
						if (!in_array($row['label'],$labels)) {
							$labels[] = $row['label'];
						}
					}
				}
			}
			
			return $labels;
		} else {
			return false;
		}
	}

	static function getFormSubmissions($submissionId){
		$rv = array();
		$sql = 	'SELECT 
					id,
					plugin_instance_id,
					plugin_instance_name,
					submission_datetime,
					user_id 
				FROM ' 
					. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'form_submissions 
				WHERE 
					id=' . (int) $submissionId;
		$result=sqlQuery($sql);
		if ($it=sqlFetchArray($result)){
			$rv=$it;
		}
		return $rv;
	}

	static function getSubmissionData($submissionId){
		$rv = array();
		if (checkPriv('_PRIV_VIEW_USER')){
			$sql = 	'SELECT 
						submission_id,
						ordinal,
						label,
						value,
						attachment 
					FROM ' 
						. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'form_submission_data 
					WHERE 
						submission_id=' . (int) $submissionId;
			$result=sqlQuery($sql);
			while ($it=sqlFetchArray($result)){
				$rv[$it['ordinal']]=$it;
			}
		}
		return $rv;
	}

	
	
	private function cutTextInMiddleWholeWords($text,$len){
		$start = array();
		$end = array();
		
		$text = mb_ereg_replace("/[\s\t\n\r,]+$/"," ",$text);
		$text = explode(' ',$text);
		$i=0;
		$j=count($text)-1;
		$count =0;
		while($i<=$j){
			
			if ($count + mb_strlen($text[$i])>$len)
				break;
			$start[]=$text[$i];
			$count+=mb_strlen($text[$i++]);
			if ($i>$j) 
				break;
			if ($count+ mb_strlen($text[$j])>$len)
				break;
			$end[]=$text[$j];
			$count+=mb_strlen($text[$j--]);
		}
		if ($i===0){
			$text = implode(' ' , $text);
			return mb_substr($text,0,$len/2) . ' ... ' . mb_substr($text,mb_strlen($text)-$len/2,$len/2);
		} elseif ($i>$j){
			return implode(' ' , $text);
		} else {
			return implode(' ',$start) . ' ... ' . implode(' ',array_reverse($end));
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$id = (int)arrayKey($box, 'key', 'id');
		
		switch ($path) {
			case 'zenario_form_input_handler__ruleset':
				if($id){
					$record = getRow(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets', true, $id);
					if($record){
						$box['title'] = 'Editing email ruleset "' . htmlspecialchars($record['name']) . '"';
						$values['name'] = $record['name'];
					}
				} else {
					$box['title'] = 'Creating a new email ruleset';
				}
				break;
			
			case 'zenario_form_input_handler__ruleset_rule':
				$rule_set_id = (int)request('refinerId');
				$box['key']['rule_set_id'] = $rule_set_id;
				
				$record_ruleset = getRow(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets', true, $rule_set_id);
				if($id){
					$box['title'] = 'Editing email rule for ruleset ';
					$record = getRow(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'routing_rules', true, $id);
					if($record){
						foreach($record as $k => $v) {
							if(isset($values[$k])) {
								$values[$k] = htmlspecialchars($v);
							}
						}
					}
				} else {
					$box['title'] = 'Creating a new email rule for ruleset ';
				}
				$box['title'] .= '"' . htmlspecialchars($record_ruleset['name']) . '"';
				
				$emailTemp = zenario_email_template_manager::getTemplateById($values['email_template_number']);
				
				$fields['email_template_number']['values'] = zenario_email_template_manager::getTemplateNames();
				if ($emailTemp) {
					$values['email_template_number'] = $emailTemp['code'];
				}
				break;
			
				case 'zenario_form_input_handler__email_submission':
				break;
			
			case 'zenario_form_input_handler__view_email_submission':
				$submition_id = $id;
				$arr=$this->getFormSubmissions($submition_id);
				if (!empty($arr['plugin_instance_name'])){
					$box['title'] = 'Details of submission no. ' . htmlspecialchars($submition_id) . ' from "'. $arr['plugin_instance_name'] . '"';
				} else {
					$box['title'] = 'Details of submission no. ' . htmlspecialchars($submition_id);
				}
				$userDetails = getUserDetails($arr['user_id']);
				if ($userDisplay = $userDetails['email']) {
					$box['title'] .= ' by "' . $userDisplay . '"';
				}
				
				$arr = self::getSubmissionData($submition_id);
				$my_fields = &$box['tabs']['email_details']['fields'];
				foreach ($arr as $it){
					if (!$it['attachment']){
						$my_fields[$it['label']] = array('label' => $it['label'] . ':',
								'ord' => $it['ordinal'],
								'snippet' => array('html' => htmlspecialchars($it['value'])));
					} else {
						$my_fields[$it['label']]=array('label'=>htmlspecialchars($it['label']),
									'type' => 'text', 
									'ord' => $it['ordinal'], 
									'snippet' => array('html' => '<a href="'. $this->showFileLink('submission_id=' . 
											$it['submission_id'] . '&ordinal=' . $it['ordinal']) .  
											'" target="_blank"   >' . adminPhrase('Download') . '</a>'));
					}
				}


				//because we create the admin_box dynamically we need to reread it
				$changes = array();
				readAdminBoxValues($box, $fields, $values, $changes, $filling = true, $resetErrors = true);
				break;
		}		
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$id = (int)arrayKey($box, 'key', 'id');
		switch ($path) {
			case 'zenario_form_input_handler__ruleset':
				$record = getRow(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets', true, array('name' => $values['name']));
				if($record) {
					if($id && ($record['id'] == $id )) {
						return;
					}
					$box['tabs']['ruleset']['errors'][] = adminPhrase("Error. This name already exists.");
				}
				break;
				
			case 'zenario_form_input_handler__ruleset_rule':
				$rule_set_id = (int)$box['key']['rule_set_id'];
				$record = getRow(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'routing_rules', true, array(
						'rule_set_id' => $rule_set_id,
						'rule_type_name' => $values['rule_type_name'],
						'object_name' => $values['object_name'],
						'matching_value' => $values['matching_value'],
						'email_template_number' => $values['email_template_number']
				));
				if($record) {
					if($id && ($record['id'] == $id )) {
						return;
					}
					$box['tabs']['ruleset_rules']['errors'][] = adminPhrase("Error. A rule with these parameters already exists.");
				}
				break;

			case 'zenario_form_input_handler__email_submission':
				if (!empty($values['email_details/email_address'])){
					foreach (explode(',',arrayKey($values,'email_details/email_address')) as $email){
						if (!validateEmailAddress(trim($email))){
							$box['tabs']['email_details']['errors'][] = adminPhrase("Error. The email address entered is not valid.");				
						}
					}
				} else {
					$box['tabs']['email_details']['errors'][] = adminPhrase("Error. Please enter an email address.");				
				}
				break;
		}
	}

	private static function dumpAttachmentFile($data,$filename) {
		cleanDownloads();
		# folder creation
		# pass a string length and the document path to the function which creates a randomly generated folder name
		$randomDir = createRandomDir(15);

		if ($data) {
			if (file_put_contents($randomDir. "/". $filename, $data)!==false){
				return $randomDir . "/". $filename;		
			} else {
				return "";
			} 
		} else {
			return "";
		}
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_form_input_handler__ruleset':
				if($values['name']) {
					$id = (int)$box['key']['id'];
					$arr_ids = array();
					if($id) {
						$arr_ids['id'] = $id;
					}
					
					$box['key']['id'] = setRow(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets', array(
							'name' => $values['name']
						), $arr_ids);
				}
				break;

			case 'zenario_form_input_handler__ruleset_rule':
				$id = (int)$box['key']['id'];
				$arr_ids = array();
				if($id) {
					$arr_ids['id'] = $id;
				}
					
				$emailTemp = zenario_email_template_manager::getTemplateByCode($values['email_template_number']);
				
				$box['key']['id'] = setRow(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'routing_rules', array(
						'rule_set_id' => $box['key']['rule_set_id'],
						'rule_type_name' => $values['rule_type_name'],
						'object_name' => $values['object_name'],
						'matching_value' => $values['matching_value'],
						'email_template_number' => $emailTemp['id'],
						'destination_address' => $values['destination_address'],
					), $arr_ids);
				
				break;
				
			case 'zenario_form_input_handler__email_submission':
				foreach (explode(',',arrayKey($box,'key','id')) as $key){
					if ($details = self::getSubmissionData($key)){
						$formFields = array();
						$attachments = array();
						$attachmentFilenameMappings = array();
						$bodyHTMLSafe = "";
			
						$submission = self::getFormSubmissions($key);
						$formFields['submission_id'] = $key;
						$formFields['user_id'] = $submission['user_id'];
						
			
						foreach ($details as $field){
							if (!empty($field['attachment'])){
								if ($fileDumpName = self::dumpAttachmentFile(arrayKey($field,'attachment'),arrayKey($field,'value'))){
									$attachments[arrayKey($field,'label')] = $fileDumpName;
									$attachmentFilenameMappings[arrayKey($field,'label')] = arrayKey($field,'value');
								}
							} else {
								$formFields[arrayKey($field,'label')] = arrayKey($field,'value');
								$bodyHTMLSafe .= '<b>' . nl2br(htmlspecialchars(arrayKey($field,'label'))) . ':</b> ' . nl2br(htmlspecialchars(arrayKey($field,'value'))) . '<br/>';
							}
						}
						
						
						foreach (explode(',',arrayKey($values,'email_details/email_address')) as $email){
							if (!empty($values['email_details/email_template'])){
								zenario_email_template_manager::sendEmailsUsingTemplate($email,arrayKey($values,'email_details/email_template'),$formFields,$attachments,$attachmentFilenameMappings);
							} else {
								$subject = adminPhrase("Form submission");
								if ($submission = self::getFormSubmissions($key)){
									$subject = adminPhrase('Form submission made at [[submission_datetime]]', $submission);
								}
								$bodyHTMLSafe .= '<b>submission_id:</b> ' . $formFields['submission_id'] . '<br/>';
								$bodyHTMLSafe .= '<b>user_id:</b> ' . $formFields['user_id'] . '<br/>';
								zenario_email_template_manager::sendEmails($email,$subject,setting('email_address_from'),setting('email_name_from'),$bodyHTMLSafe,$formFields,$attachments,$attachmentFilenameMappings);
							}
						}
					}
				}
				break;
		}
	}

	public function showFile() {
		if (checkPriv('_PRIV_VIEW_USER')){
			$arr = self::getSubmissionData((int)$_GET['submission_id']);
			if ($arr[(int)$_GET['ordinal']]['attachment']){
					$it = $arr[(int)$_GET['ordinal']];
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="' . $it['value'] . '"');
					header('Content-Length:' . strlen($it['attachment'])); 
					echo $it['attachment'];
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "site_settings":
				$my_fields = &$box['tabs']['zenario_form_input_handler__sfs']['fields'];
				if ($values['zenario_form_input_handler__sfs/zenario_form_input_handler__sf_1_enable']) {
					$my_fields['zenario_form_input_handler__sf_1_label']['hidden'] = false;
					$my_fields['zenario_form_input_handler__sf_1_field_name']['hidden'] = false;
				} else {
					$my_fields['zenario_form_input_handler__sf_1_label']['hidden'] = true;
					$my_fields['zenario_form_input_handler__sf_1_field_name']['hidden'] = true;
				}

				if ($values['zenario_form_input_handler__sfs/zenario_form_input_handler__sf_2_enable']) {
					$my_fields['zenario_form_input_handler__sf_2_label']['hidden'] = false;
					$my_fields['zenario_form_input_handler__sf_2_field_name']['hidden'] = false;
				} else {
					$my_fields['zenario_form_input_handler__sf_2_label']['hidden'] = true;
					$my_fields['zenario_form_input_handler__sf_2_field_name']['hidden'] = true;
				}

				if ($values['zenario_form_input_handler__sfs/zenario_form_input_handler__sf_3_enable']) {
					$my_fields['zenario_form_input_handler__sf_3_label']['hidden'] = false;
					$my_fields['zenario_form_input_handler__sf_3_field_name']['hidden'] = false;
				} else {
					$my_fields['zenario_form_input_handler__sf_3_label']['hidden'] = true;
					$my_fields['zenario_form_input_handler__sf_3_field_name']['hidden'] = true;
				}

				if ($values['zenario_form_input_handler__sfs/zenario_form_input_handler__sf_4_enable']) {
					$my_fields['zenario_form_input_handler__sf_4_label']['hidden'] = false;
					$my_fields['zenario_form_input_handler__sf_4_field_name']['hidden'] = false;
				} else {
					$my_fields['zenario_form_input_handler__sf_4_label']['hidden'] = true;
					$my_fields['zenario_form_input_handler__sf_4_field_name']['hidden'] = true;
				}

				if ($values['zenario_form_input_handler__sfs/zenario_form_input_handler__sf_5_enable']) {
					$my_fields['zenario_form_input_handler__sf_5_label']['hidden'] = false;
					$my_fields['zenario_form_input_handler__sf_5_field_name']['hidden'] = false;
				} else {
					$my_fields['zenario_form_input_handler__sf_5_label']['hidden'] = true;
					$my_fields['zenario_form_input_handler__sf_5_field_name']['hidden'] = true;
				}

				break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__form_input_handler/rulesets':
				$ruleset = $this->getRuleSets();
				foreach ($ruleset as $K=>$ruleset){
					$html='';
					foreach ($this->getRules($K) as $R){
						$html .= '<b>if field value </b>' . htmlspecialchars(strtolower($R['rule_type_name'])) . ' &quot;' . htmlspecialchars($R['matching_value']) . '&quot;<b> send email to </b>' . htmlspecialchars($R['destination_address']) .'<br/>';
					}
					$panel['items'][$K]['close_up_view'] = $html;
				}
				break;
			case 'zenario__form_input_handler/nav/email_rulesets_rules/panel':
				$ruleset_id = request('refinerId');
				$record = getRow(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets', true, $ruleset_id);
				$panel['title'] .= ' for "' . htmlspecialchars($record['name']) . '"';
				break;
				
			case 'zenario__form_input_handler':
				foreach ($panel['items'] as $K=>$val){
					if (count($arr=explode('_',$val['form_submitted_from_page_name']))==2){
						if ($title = getItemTitle($arr[1],$arr[0])){
							$panel['items'][$K]['form_submitted_from_page_name'] = $title;
						} else {
							$panel['items'][$K]['form_submitted_from_page_name'] = 'Unknown Content Item';
						}
					} else {
						$panel['items'][$K]['form_submitted_from_page_name'] = 'Unknown Content Item';
					}
				}
				break;
			case 'zenario__form_input_handler/form_submissions/panel':
					if($refinerName == 'user_filter'){
						$user = getUserDetails($refinerId);
						$panel['title'] = 'Form Submissions for user ' . $user['screen_name'];
					}
					else
					{
						unset($panel['columns']['form_submitted_from_page']);
						$arr = explode('_',$refinerId);
						if ((count($arr)==2) && (count(getContentTypes($arr[0]))==1) && ($title=getItemTitle($arr[1],$arr[0]))) {
							$panel['title'] = 'Form Submissions from page "'. $title . '"';
						} else {
							$panel['title'] = 'Form Submissions';
						}
					}
					
					if (setting("zenario_form_input_handler__sf_1_enable")) {
						$panel['columns']['zenario_form_input_handler__sf_1']['title'] = setting("zenario_form_input_handler__sf_1_label");
					} else {
						unset($panel['columns']['zenario_form_input_handler__sf_1']);
					}

					if (setting("zenario_form_input_handler__sf_2_enable")) {
						$panel['columns']['zenario_form_input_handler__sf_2']['title'] = setting("zenario_form_input_handler__sf_2_label");
					} else {
						unset($panel['columns']['zenario_form_input_handler__sf_2']);
					}

					if (setting("zenario_form_input_handler__sf_3_enable")) {
						$panel['columns']['zenario_form_input_handler__sf_3']['title'] = setting("zenario_form_input_handler__sf_3_label");
					} else {
						unset($panel['columns']['zenario_form_input_handler__sf_3']);
					}

					if (setting("zenario_form_input_handler__sf_4_enable")) {
						$panel['columns']['zenario_form_input_handler__sf_4']['title'] = setting("zenario_form_input_handler__sf_4_label");
					} else {
						unset($panel['columns']['zenario_form_input_handler__sf_4']);
					}

					if (setting("zenario_form_input_handler__sf_5_enable")) {
						$panel['columns']['zenario_form_input_handler__sf_5']['title'] = setting("zenario_form_input_handler__sf_5_label");
					} else {
						unset($panel['columns']['zenario_form_input_handler__sf_5']);
					}
					
					foreach ($panel['items'] as $K=>$val){
						$arr = self::getSubmissionData($K);
						$fields=array();
						$currentVals=array();
						if (count($arr)>0){
							$html = '<h3> Submitted data: </h3>';
							$html .= '<table><tbody>';
							foreach ($arr as $it){
								$html .='<tr>';
								if ($it['attachment']){
										$html .= '<td><b>' . htmlspecialchars($it['label']) . ':</b></td><td> ' . htmlspecialchars($it['value'])  . ' <a href="'. $this->showFileLink('submission_id=' . $it['submission_id'] . '&ordinal=' . $it['ordinal']) .  '" target="_blank"   >' . adminPhrase('Download') . '</a>' . '</td>'  ;
								} else {
										$html .= '<td><b>' . htmlspecialchars($it['label']) . ':</b></td><td> ' . htmlspecialchars($it['value']) . '</td>'  ;
								}
								$html .='</tr>';
								
								if (setting("zenario_form_input_handler__sf_1_enable")) {
									if ($it['label']==setting("zenario_form_input_handler__sf_1_field_name")) {
										$panel['items'][$K]['zenario_form_input_handler__sf_1'] = $it['value'];
									}
								}

								if (setting("zenario_form_input_handler__sf_2_enable")) {
									if ($it['label']==setting("zenario_form_input_handler__sf_2_field_name")) {
										$panel['items'][$K]['zenario_form_input_handler__sf_2'] = $it['value'];
									}
								}

								if (setting("zenario_form_input_handler__sf_3_enable")) {
									if ($it['label']==setting("zenario_form_input_handler__sf_3_field_name")) {
										$panel['items'][$K]['zenario_form_input_handler__sf_3'] = $it['value'];
									}
								}

								if (setting("zenario_form_input_handler__sf_4_enable")) {
									if ($it['label']==setting("zenario_form_input_handler__sf_4_field_name")) {
										$panel['items'][$K]['zenario_form_input_handler__sf_4'] = $it['value'];
									}
								}

								if (setting("zenario_form_input_handler__sf_5_enable")) {
									if ($it['label']==setting("zenario_form_input_handler__sf_5_field_name")) {
										$panel['items'][$K]['zenario_form_input_handler__sf_5'] = $it['value'];
									}
								}

							}
							$html .= '</tbody></table><br/><br/>';
						}
					}
				break;
		}
	}
	
	static function write_csv_row($fp, $csvCols, $form_data){
		$csv_data = array();
		foreach ($csvCols as $k){
			$csv_data[$k] = isset($form_data[$k]) ? $form_data[$k] : '';
		}
		fputcsv($fp, $csv_data);
	}

	//Code to send every form submissions that needs sending
	//Ideally should be run as a job
	public static function exportformSubmitionsData($path, $content_id, $content_type, $ids=false) {
		$db_form_prefix = DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX;

		$sql = 'SELECT
						FS.submission_datetime,
						FSD.submission_id,
						FSD.ordinal, FSD.label,
						FSD.value,
						FSD.attachment
					FROM '
				. $db_form_prefix . 'form_submission_data AS FSD, '
						. $db_form_prefix . 'form_submissions AS FS
					WHERE
						FS.content_id=' .(int) $content_id .'
						AND FS.content_type=\'' . sqlEscape($content_type)
					. '\' AND FSD.submission_id=FS.id';
		
		if($ids !== false){
			$ar_ids = explode(',', $ids);
			for($i=0, $count=count($ar_ids); $i < $count; ++$i){
				$ar_ids[$i] = (int)$ar_ids[$i];
			}
			$selected_ids = implode(',', $ar_ids);
			$sql .= ' AND FSD.submission_id IN(' . $selected_ids . ')';
		}
		
		$result = sqlQuery($sql);

		//we need two pass here to find all field names on all records
		$field_names = array();
		while ($rec = sqlFetchAssoc($result)) {
			$field_names[$rec['label']] = true;
		}
			
		//If we do not have any rows stop here and do not process it
		if(empty($field_names)) return;
			
		@$result->data_seek(0);
			
		$csvBaseFields = array('submission_id', 'submission_datetime');
		$csvCols = $csvBaseFields;
		//now on the second pass we write the csv
		foreach ($field_names as $k => $v){
			$csvCols[] = $k;
		}

		//Create a file in the temp directory to start writing a CSV file to.
		//$filename = tempnam(sys_get_temp_dir(), 'tmpfiletodownload');
		$randomDir = createRandomDir( '15');
		$filename = $randomDir . '/form_submition_data.csv';
			
		$fp = fopen($filename, 'wb');
		//Print the column headers for a CSV export
		fputcsv($fp, $csvCols);
			
		$last_subimission_id = 0;
		$form_data = array();
		while ($rec = sqlFetchAssoc($result)) {
			$subimission_id = $rec['submission_id'];
			if($last_subimission_id != $subimission_id){
				if(!$last_subimission_id) {
					foreach($csvBaseFields as $k){
						$form_data[$k] = $rec[$k];
					}
					$last_subimission_id = $subimission_id;
				}
				else {
					//writecsv
					self::write_csv_row($fp, $csvCols, $form_data);
					$last_subimission_id = $subimission_id;
					$form_data = array();
					foreach($csvBaseFields as $k){
						$form_data[$k] = $rec[$k];
					}
				}
			}
			$form_data[$rec['label']] = $rec['value'];
		}
		//write the last form_data
		if(count($form_data)) self::write_csv_row($fp, $csvCols, $form_data);
		fclose($fp);
		
		//...and finally offer it for download
		header('Content-Type: text/x-csv');
		header('Content-Disposition: attachment; filename="'. str_replace('/', '_', $path). '.csv"');
		header('Content-Length: '. filesize($filename)); 
		readfile($filename);
		
		//Remove the file from the temp directory
		@unlink($filename);
		
		exit;
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		foreach (explode(',',$ids) as $id) {
			switch (post('action'))  {
				case 'delete_ruleset':
					if (checkPriv('_PRIV_SET_EMAIL_ROUTING')){
						$this->deleteRuleSet($id);
					}
					break;
				case 'delete_ruleset_rule':
					if (checkPriv('_PRIV_SET_EMAIL_ROUTING')){
						$this->deleteRuleSetRule($id, request('refinerId'));
					}
					break;
				case 'delete_form_submission':
					if (checkPriv('_PRIV_DELETE_FORM_SUBMISSION')){
						foreach (explode(',',$ids) as $id) {
							$this->deleteFormSubmission($id);
						}
					}
					break;
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if (post('export_all_data')) {
			$ar = explode('_', $refinerId);
			self::exportformSubmitionsData($path, $ar[1], $ar[0]);
			
		} elseif (post('export_selected_data')) {
			$ar = explode('_', $refinerId);
			self::exportformSubmitionsData($path, $ar[1], $ar[0], $ids);
		}
	}
	
	
	public function eventFlexibleFormSubmitted($instanceId,$instanceName,$formFields,$attachments,$attachmentFilenameMappings,$userId,$ipAddress,$cmsUrl,$cId,$cType,$cVersion){
		$submissionId = $this->storeSubmissionData($instanceId,$instanceName,$formFields,$attachments,$attachmentFilenameMappings,$userId,$ipAddress,$cId,$cType,$cVersion);
		$formFields['submission_id'] = $submissionId; 
		$formFields['user_id'] = $userId;
		$this->sendEmailsUsingTemplate($instanceId,$instanceName,$formFields,$attachments,$attachmentFilenameMappings);
	}
	
	
	private function storeSubmissionData($instanceId,$instanceName,$formFields,$attachments,$attachmentFilenameMappings,$userId,$ipAddress,$cId,$cType,$cVersion){
		$sql ="INSERT INTO "
					. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . "form_submissions
				SET 
					 plugin_instance_id = " . (int)$instanceId . "
					,plugin_instance_name = '" . sqlEscape($instanceName) . "'
					,submission_datetime = '" . date("Y-m-d H:i:s") . "'";
		
		if ($userId) {
			$sql .= ", user_id = ". (int) $userId;
		}
		if ($ipAddress) {
			$sql .= ", ip_address = '". sqlEscape($ipAddress). "'";
		}
		if ($cId) {
			$sql .= ", content_id = ". (int) $cId;
		}
		if ($cType) {
			$sql .= ", content_type = '". sqlEscape($cType). "'";
		}
		if ($cVersion) {
			$sql .= ", content_version = ". (int) $cVersion;
		}

		sqlUpdate($sql);
		$ID = sqlInsertId();
		if ($ID) {
			$sql = 'INSERT INTO ' 
						. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'form_submission_data  (submission_id,ordinal,label,value,attachment) 
					VALUES ';
			$i=1;
			$valuesPresent=0;
			foreach ($formFields as $key=>$val){
				$sql .= '(' . $ID  . ',' .$i++ .  ",'" . sqlEscape($key)  . "','" . sqlEscape($val) . "',NULL),";
				$valuesPresent=1;
			}
			foreach ($attachments as $key=>$val){
				if (file_exists($val)){
					$sql .= '(';
					$sql .= $ID;
					$sql .= ',' . $i++;
					$sql .= ",'" . sqlEscape($key) . "'";
					$sql .= ",'" . sqlEscape(ifNull(arrayKey($attachmentFilenameMappings, $key), basename($val))) ."'";
					$sql .= ",'" . sqlEscape(file_get_contents($val)) ."'";
					$sql .= "),";
					
					$valuesPresent=1;
				}
			}


			$sql = substr($sql, 0, -1);
			if ($valuesPresent){
				sqlUpdate($sql);
				if (!sqlAffectedRows()) {
					$sql = 'DELETE FROM '
								. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'form_submissions
							WHERE 
								id =' . (int)$ID;
					sqlQuery($sql);
					return false;
				}
				return $ID;
			}
			return false;
		}
		return false;
	}
	
	private function deleteFormSubmission($submissionId){
		$sql = "DELETE FROM "
					. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . "form_submission_data
				WHERE
					submission_id = " . (int) $submissionId;
		
		sqlQuery($sql);

		$sql = "DELETE FROM "
					. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . "form_submissions
				WHERE
					id = " . (int) $submissionId;
		
		sqlQuery($sql);
	}


	private function sendEmailsUsingTemplate($instanceId,$instanceName,$formFields,$attachments,$attachmentFilenameMappings){


		$ruleSets = $this->getRuleSets();
		foreach ($ruleSets as $set){
			$rulesInSet = $this->getRules($set['id']);
			foreach ($rulesInSet as $rule){
				
				$email = $rule['destination_address'];
				
				if (strrpos($email, '@') === false) {
					if (!empty($formFields[$email]) && validateEmailAddress($formFields[$email])) {
						$email = $formFields[$email];
					} else {
						continue;
					}
				}
				
				foreach (explode("\n",mb_ereg_replace("\r","\n",$rule['matching_value'])) as $matchingValue){
					$matched = false;
					$inverse = false;
				
					switch (strtolower($rule['rule_type_name'])){
						case 'not_equals':
							$inverse = true;
						case 'equals':
							$matched = strcmp(strtolower($matchingValue),strtolower(arrayKey($formFields,$rule['object_name'])))===0;
							break;
						
						case 'starts_with':
							$matched = strpos(strtolower(arrayKey($formFields,$rule['object_name'])),strtolower($matchingValue))===0;
							break;
						case 'match_regexp':
							$matched = preg_match ($matchingValue,arrayKey($formFields,$rule['object_name']))===1;
							break;
					}
					
					
					
					if ($inverse? !$matched : $matched) {
						if ($templateCode = getRow('email_templates','code',array('id'=>$rule['email_template_number']))) {
							zenario_email_template_manager::sendEmailsUsingTemplate($email,$templateCode,$formFields,$attachments,$attachmentFilenameMappings);
						}
						$this->updateRuleLastUse($set['id']);
					}
				}
			}
		}
	}
	
	public function formatStorekeeperCSV($path, &$item, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__form_input_handler/form_submissions/panel':
				$arr = self::getSubmissionData($item['submission_id']);
				$fields=array();
				$currentVals=array();
				if (count($arr)>0){
					foreach ($arr as $it){
						if (setting("zenario_form_input_handler__sf_1_enable")) {
							if ($it['label']==setting("zenario_form_input_handler__sf_1_field_name")) {
								$item['zenario_form_input_handler__sf_1'] = $it['value'];
							}
						}
		
						if (setting("zenario_form_input_handler__sf_2_enable")) {
							if ($it['label']==setting("zenario_form_input_handler__sf_2_field_name")) {
								$item['zenario_form_input_handler__sf_2'] = $it['value'];
							}
						}
		
						if (setting("zenario_form_input_handler__sf_3_enable")) {
							if ($it['label']==setting("zenario_form_input_handler__sf_3_field_name")) {
								$item['zenario_form_input_handler__sf_3'] = $it['value'];
							}
						}
		
						if (setting("zenario_form_input_handler__sf_4_enable")) {
							if ($it['label']==setting("zenario_form_input_handler__sf_4_field_name")) {
								$item['zenario_form_input_handler__sf_4'] = $it['value'];
							}
						}
		
						if (setting("zenario_form_input_handler__sf_5_enable")) {
							if ($it['label']==setting("zenario_form_input_handler__sf_5_field_name")) {
								$item['zenario_form_input_handler__sf_5'] = $it['value'];
							}
						}
		
					}
				}
				
				break;
		}
	}
	
}
