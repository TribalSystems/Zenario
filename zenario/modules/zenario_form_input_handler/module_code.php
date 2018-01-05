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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


class zenario_form_input_handler extends ze\moduleBaseClass {
	
	var $mergeFields = [];
	
	
	function init(){
		return true;
		
	}
	
	private function checkRuleData($data,$ruleId=0){
		if (!ze\priv::check('_PRIV_SET_EMAIL_ROUTING'))
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
			$result = ze\sql::select($sql);
			if (ze\sql::numRows($result)==0)
				return "Error. Ruleset no longer exists in the database.";
		} else {
			$sql = 'SELECT 
						1 
					FROM ' 
						. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . "rule_sets 
					WHERE 
						name='" . ze\escape::sql($data['ruleset_name']) . "'";
			$result = ze\sql::select($sql);
			if (ze\sql::numRows($result)==1)
				return 'Error. Ruleset ' . $data['ruleset_name'] . ' already exists.';
		}

		$sets=[];
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
		if (ze\priv::check('_PRIV_SET_EMAIL_ROUTING')){
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
					$sql .= "'" . ze\escape::sql($data['ruleset_name']) . "',"  ;
					$sql .= '  NOW(), NOW()';
					$sql .= ') ON DUPLICATE KEY UPDATE ' ;
					$sql .= "name='" .ze\escape::sql($data['ruleset_name']) . "'";
					$sql .= ",modification_datetime=NOW()";
		
					ze\sql::update($sql);
					$ruleSetId = ze\sql::insertId();
	
					$sets=[];
					$indexes = $this->getIndexes($data);
					$i=1;
					foreach ($indexes as $idx){
						if ($idx!=0){
							$sets[$i-1]=$this->extractRuleSet($data,$idx);
							$i++;
						}
					}	
			
					$sql = 'DELETE FROM ' . DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX  . 'routing_rules WHERE rule_set_id=' .  (int)$ruleSetId;
					ze\sql::update($sql);
					foreach($sets as $K=>$set){
						$sql  = 'INSERT INTO '  . DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'routing_rules ';
						$sql .= '(`rule_set_id`,`rule_type_name`,`object_name`,`matching_value`,`email_template_number`,`destination_address`)' ;
						$sql .='VALUES';
						$sql .='(';
						$sql .= (int)$ruleSetId;
						$sql .= ",'" . ze\escape::sql($set['cmp_type']) . "'";
						$sql .= ",'" . ze\escape::sql($data['match_field']) . "'";
						$sql .= ",'" . ze\escape::sql($set['cmp_value']) . "'";
						$sql .= ",'" . ze\escape::sql($set['template_no']) . "'";
						$sql .= ",'" . ze\escape::sql($set['email_to']) . "'";
						$sql .=')';
						
						ze\sql::update($sql);
						if (!ze\sql::affectedRows()) {
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
		ze\sql::update($sql);

		$sql = 'DELETE FROM ' 
					. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets 
				WHERE 
					id=' . (int) $ruleSetId;
		
		ze\sql::update($sql);
		if (ze\sql::affectedRows()) {
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
		ze\sql::update($sql);
		if (ze\sql::affectedRows()) {
			return '';
		} else {
			return 'Error. Deleting rule query fail.';
		}
	}
	
	private function updateRuleLastUse($setId){
		ze\sql::update('UPDATE ' . DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets set last_rule_matching_datetime=NOW() WHERE id='. (int)$setId);
	}
	
	
	private function getRules($ruleSetId=0){
		$rv=[];
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
		$result = ze\sql::select($sql);
		while($row=ze\sql::fetchAssoc($result))
			$rv[$row['id']]=$row;
		return $rv;
	}


	private function getRuleSets($ruleSetId=0){
		$rv=[];
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
		$result = ze\sql::select($sql);
		while($row=ze\sql::fetchAssoc($result))
			$rv[$row['id']]=$row;
		return $rv;
	}

	private function getFormInstances($instanceId=0){
		$rv = [];
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
		
		$result = ze\sql::select($sql);
		while($row=ze\sql::fetchAssoc($result))
			$rv[$row['plugin_instance_id']]=$row;
		return $rv;
				
	}

	static function getFormSubmissionsByInstanceId($instanceId){
		$rv = [];
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
		$result=ze\sql::select($sql);
		
		if (ze\sql::numRows($result)>0) {
			while ($row = ze\sql::fetchArray($result)) {
				$rv[] = $row;
			}
			
			return $rv;
		} else {
			return false;
		}
	}

	static function getFormSubmissionsByContentItem($cID,$cType){
		$rv = [];
		$sql = 	'SELECT 
					id,
					plugin_instance_id,
					plugin_instance_name,
					submission_datetime,
					user_id 
				FROM ' . DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'form_submissions ';
		$sql .= ' WHERE content_id=' . (int) $cID . '
					AND content_type = "' . ze\escape::sql($cType) . '"';
		$result=ze\sql::select($sql);
		
		if (ze\sql::numRows($result)>0) {
			while ($row = ze\sql::fetchArray($result)) {
				$rv[] = $row;
			}
			
			return $rv;
		} else {
			return false;
		}
	}

	static function getFormSubmissionsDataByInstanceId ($instanceId) {
		$rv = [];
		if (ze\priv::check('_PRIV_VIEW_USER')){
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
					$result=ze\sql::select($sql);
					if (ze\sql::numRows($result)>0) {
						while ($row=ze\sql::fetchArray($result)){
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
			$labels = [];
		
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
						
				$result = ze\sql::select($sql);
				
				if (ze\sql::numRows($result)>0) {
					while ($row = ze\sql::fetchAssoc($result)) {
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
			$labels = [];
		
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
						
				$result = ze\sql::select($sql);
				
				if (ze\sql::numRows($result)>0) {
					while ($row = ze\sql::fetchAssoc($result)) {
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
		$rv = [];
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
		$result=ze\sql::select($sql);
		if ($it=ze\sql::fetchArray($result)){
			$rv=$it;
		}
		return $rv;
	}

	static function getSubmissionData($submissionId){
		$rv = [];
		if (ze\priv::check('_PRIV_VIEW_USER')){
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
			$result=ze\sql::select($sql);
			while ($it=ze\sql::fetchArray($result)){
				$rv[$it['ordinal']]=$it;
			}
		}
		return $rv;
	}

	
	
	private function cutTextInMiddleWholeWords($text,$len){
		$start = [];
		$end = [];
		
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
		$id = (int)($box['key']['id'] ?? false);
		
		switch ($path) {
			case 'zenario_form_input_handler__ruleset':
				if($id){
					$record = ze\row::get(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets', true, $id);
					if($record){
						$box['title'] = 'Editing email ruleset "' . htmlspecialchars($record['name']) . '"';
						$values['name'] = $record['name'];
					}
				} else {
					$box['title'] = 'Creating a new email ruleset';
				}
				break;
			
			case 'zenario_form_input_handler__ruleset_rule':
				$rule_set_id = (int)($_REQUEST['refinerId'] ?? false);
				$box['key']['rule_set_id'] = $rule_set_id;
				
				$record_ruleset = ze\row::get(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets', true, $rule_set_id);
				if($id){
					$box['title'] = 'Editing email rule for ruleset ';
					$record = ze\row::get(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'routing_rules', true, $id);
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
				$userDetails = ze\user::details($arr['user_id']);
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
											'" target="_blank"   >' . ze\admin::phrase('Download') . '</a>'));
					}
				}


				//because we create the admin_box dynamically we need to reread it
				$changes = [];
				ze\tuix::readValues($box, $fields, $values, $changes, $filling = true, $resetErrors = true);
				break;
		}		
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$id = (int)($box['key']['id'] ?? false);
		switch ($path) {
			case 'zenario_form_input_handler__ruleset':
				$record = ze\row::get(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets', true, ['name' => $values['name']]);
				if($record) {
					if($id && ($record['id'] == $id )) {
						return;
					}
					$box['tabs']['ruleset']['errors'][] = ze\admin::phrase("Error. This name already exists.");
				}
				break;
				
			case 'zenario_form_input_handler__ruleset_rule':
				$rule_set_id = (int)$box['key']['rule_set_id'];
				$record = ze\row::get(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'routing_rules', true, [
						'rule_set_id' => $rule_set_id,
						'rule_type_name' => $values['rule_type_name'],
						'object_name' => $values['object_name'],
						'matching_value' => $values['matching_value'],
						'email_template_number' => $values['email_template_number']
				]);
				if($record) {
					if($id && ($record['id'] == $id )) {
						return;
					}
					$box['tabs']['ruleset_rules']['errors'][] = ze\admin::phrase("Error. A rule with these parameters already exists.");
				}
				break;

			case 'zenario_form_input_handler__email_submission':
				if (!empty($values['email_details/email_address'])){
					foreach (explode(',',($values['email_details/email_address'] ?? false)) as $email){
						if (!ze\ring::validateEmailAddress(trim($email))){
							$box['tabs']['email_details']['errors'][] = ze\admin::phrase("Error. The email address entered is not valid.");				
						}
					}
				} else {
					$box['tabs']['email_details']['errors'][] = ze\admin::phrase("Error. Please enter an email address.");				
				}
				break;
		}
	}

	private static function dumpAttachmentFile($data,$filename) {
		ze\cache::cleanDirs();
		# folder creation
		# pass a string length and the document path to the function which creates a randomly generated folder name
		$randomDir = ze\cache::createRandomDir(15);

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
					$arr_ids = [];
					if($id) {
						$arr_ids['id'] = $id;
					}
					
					$box['key']['id'] = ze\row::set(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets', [
							'name' => $values['name']
						], $arr_ids);
				}
				break;

			case 'zenario_form_input_handler__ruleset_rule':
				$id = (int)$box['key']['id'];
				$arr_ids = [];
				if($id) {
					$arr_ids['id'] = $id;
				}
					
				$emailTemp = zenario_email_template_manager::getTemplateByCode($values['email_template_number']);
				
				$box['key']['id'] = ze\row::set(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'routing_rules', [
						'rule_set_id' => $box['key']['rule_set_id'],
						'rule_type_name' => $values['rule_type_name'],
						'object_name' => $values['object_name'],
						'matching_value' => $values['matching_value'],
						'email_template_number' => $emailTemp['id'],
						'destination_address' => $values['destination_address'],
					], $arr_ids);
				
				break;
				
			case 'zenario_form_input_handler__email_submission':
				foreach (explode(',',($box['key']['id'] ?? false)) as $key){
					if ($details = self::getSubmissionData($key)){
						$formFields = [];
						$attachments = [];
						$attachmentFilenameMappings = [];
						$bodyHTMLSafe = "";
			
						$submission = self::getFormSubmissions($key);
						$formFields['submission_id'] = $key;
						$formFields['user_id'] = $submission['user_id'];
						
			
						foreach ($details as $field){
							if (!empty($field['attachment'])){
								if ($fileDumpName = self::dumpAttachmentFile($field['attachment'] ?? false,($field['value'] ?? false))){
									$attachments[($field['label'] ?? false)] = $fileDumpName;
									$attachmentFilenameMappings[($field['label'] ?? false)] = $field['value'] ?? false;
								}
							} else {
								$formFields[($field['label'] ?? false)] = $field['value'] ?? false;
								$bodyHTMLSafe .= '<b>' . nl2br(htmlspecialchars($field['label'] ?? false)) . ':</b> ' . nl2br(htmlspecialchars($field['value'] ?? false)) . '<br/>';
							}
						}
						
						
						foreach (explode(',',($values['email_details/email_address'] ?? false)) as $email){
							if (!empty($values['email_details/email_template'])){
								zenario_email_template_manager::sendEmailsUsingTemplate($email,($values['email_details/email_template'] ?? false),$formFields,$attachments,$attachmentFilenameMappings);
							} else {
								$subject = ze\admin::phrase("Form submission");
								if ($submission = self::getFormSubmissions($key)){
									$subject = ze\admin::phrase('Form submission made at [[submission_datetime]]', $submission);
								}
								$bodyHTMLSafe .= '<b>submission_id:</b> ' . $formFields['submission_id'] . '<br/>';
								$bodyHTMLSafe .= '<b>user_id:</b> ' . $formFields['user_id'] . '<br/>';
								zenario_email_template_manager::sendEmails($email,$subject,ze::setting('email_address_from'),ze::setting('email_name_from'),$bodyHTMLSafe,$formFields,$attachments,$attachmentFilenameMappings);
							}
						}
					}
				}
				break;
		}
	}

	public function showFile() {
		if (ze\priv::check('_PRIV_VIEW_USER')){
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
				$ruleset_id = $_REQUEST['refinerId'] ?? false;
				$record = ze\row::get(ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'rule_sets', true, $ruleset_id);
				$panel['title'] .= ' for "' . htmlspecialchars($record['name']) . '"';
				break;
				
			case 'zenario__form_input_handler':
				foreach ($panel['items'] as $K=>$val){
					if (count($arr=explode('_',$val['form_submitted_from_page_name']))==2){
						if ($title = ze\content::title($arr[1],$arr[0])){
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
						$user = ze\user::details($refinerId);
						$panel['title'] = 'Form Submissions for user ' . $user['screen_name'];
					}
					else
					{
						unset($panel['columns']['form_submitted_from_page']);
						$arr = explode('_',$refinerId);
						if ((count($arr)==2) && (count(ze\content::getContentTypes($arr[0]))==1) && ($title=ze\content::title($arr[1],$arr[0]))) {
							$panel['title'] = 'Form Submissions from page "'. $title . '"';
						} else {
							$panel['title'] = 'Form Submissions';
						}
					}
					
					if (ze::setting("zenario_form_input_handler__sf_1_enable")) {
						$panel['columns']['zenario_form_input_handler__sf_1']['title'] = ze::setting("zenario_form_input_handler__sf_1_label");
					} else {
						unset($panel['columns']['zenario_form_input_handler__sf_1']);
					}

					if (ze::setting("zenario_form_input_handler__sf_2_enable")) {
						$panel['columns']['zenario_form_input_handler__sf_2']['title'] = ze::setting("zenario_form_input_handler__sf_2_label");
					} else {
						unset($panel['columns']['zenario_form_input_handler__sf_2']);
					}

					if (ze::setting("zenario_form_input_handler__sf_3_enable")) {
						$panel['columns']['zenario_form_input_handler__sf_3']['title'] = ze::setting("zenario_form_input_handler__sf_3_label");
					} else {
						unset($panel['columns']['zenario_form_input_handler__sf_3']);
					}

					if (ze::setting("zenario_form_input_handler__sf_4_enable")) {
						$panel['columns']['zenario_form_input_handler__sf_4']['title'] = ze::setting("zenario_form_input_handler__sf_4_label");
					} else {
						unset($panel['columns']['zenario_form_input_handler__sf_4']);
					}

					if (ze::setting("zenario_form_input_handler__sf_5_enable")) {
						$panel['columns']['zenario_form_input_handler__sf_5']['title'] = ze::setting("zenario_form_input_handler__sf_5_label");
					} else {
						unset($panel['columns']['zenario_form_input_handler__sf_5']);
					}
					
					foreach ($panel['items'] as $K=>$val){
						$arr = self::getSubmissionData($K);
						$fields=[];
						$currentVals=[];
						if (count($arr)>0){
							$html = '<h3> Submitted data: </h3>';
							$html .= '<table><tbody>';
							foreach ($arr as $it){
								$html .='<tr>';
								if ($it['attachment']){
										$html .= '<td><b>' . htmlspecialchars($it['label']) . ':</b></td><td> ' . htmlspecialchars($it['value'])  . ' <a href="'. $this->showFileLink('submission_id=' . $it['submission_id'] . '&ordinal=' . $it['ordinal']) .  '" target="_blank"   >' . ze\admin::phrase('Download') . '</a>' . '</td>'  ;
								} else {
										$html .= '<td><b>' . htmlspecialchars($it['label']) . ':</b></td><td> ' . htmlspecialchars($it['value']) . '</td>'  ;
								}
								$html .='</tr>';
								
								if (ze::setting("zenario_form_input_handler__sf_1_enable")) {
									if ($it['label']==ze::setting("zenario_form_input_handler__sf_1_field_name")) {
										$panel['items'][$K]['zenario_form_input_handler__sf_1'] = $it['value'];
									}
								}

								if (ze::setting("zenario_form_input_handler__sf_2_enable")) {
									if ($it['label']==ze::setting("zenario_form_input_handler__sf_2_field_name")) {
										$panel['items'][$K]['zenario_form_input_handler__sf_2'] = $it['value'];
									}
								}

								if (ze::setting("zenario_form_input_handler__sf_3_enable")) {
									if ($it['label']==ze::setting("zenario_form_input_handler__sf_3_field_name")) {
										$panel['items'][$K]['zenario_form_input_handler__sf_3'] = $it['value'];
									}
								}

								if (ze::setting("zenario_form_input_handler__sf_4_enable")) {
									if ($it['label']==ze::setting("zenario_form_input_handler__sf_4_field_name")) {
										$panel['items'][$K]['zenario_form_input_handler__sf_4'] = $it['value'];
									}
								}

								if (ze::setting("zenario_form_input_handler__sf_5_enable")) {
									if ($it['label']==ze::setting("zenario_form_input_handler__sf_5_field_name")) {
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
		$csv_data = [];
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
						AND FS.content_type=\'' . ze\escape::sql($content_type)
					. '\' AND FSD.submission_id=FS.id';
		
		if($ids !== false){
			$ar_ids = explode(',', $ids);
			for($i=0, $count=count($ar_ids); $i < $count; ++$i){
				$ar_ids[$i] = (int)$ar_ids[$i];
			}
			$selected_ids = implode(',', $ar_ids);
			$sql .= ' AND FSD.submission_id IN(' . $selected_ids . ')';
		}
		
		$result = ze\sql::select($sql);

		//we need two pass here to find all field names on all records
		$field_names = [];
		while ($rec = ze\sql::fetchAssoc($result)) {
			$field_names[$rec['label']] = true;
		}
			
		//If we do not have any rows stop here and do not process it
		if(empty($field_names)) return;
			
		@$result->data_seek(0);
			
		$csvBaseFields = ['submission_id', 'submission_datetime'];
		$csvCols = $csvBaseFields;
		//now on the second pass we write the csv
		foreach ($field_names as $k => $v){
			$csvCols[] = $k;
		}

		//Create a file in the temp directory to start writing a CSV file to.
		//$filename = tempnam(sys_get_temp_dir(), 'tmpfiletodownload');
		$randomDir = ze\cache::createRandomDir( '15');
		$filename = $randomDir . '/form_submition_data.csv';
			
		$fp = fopen($filename, 'wb');
		//Print the column headers for a CSV export
		fputcsv($fp, $csvCols);
			
		$last_subimission_id = 0;
		$form_data = [];
		while ($rec = ze\sql::fetchAssoc($result)) {
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
					$form_data = [];
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
			switch ($_POST['action'] ?? false)  {
				case 'delete_ruleset':
					if (ze\priv::check('_PRIV_SET_EMAIL_ROUTING')){
						$this->deleteRuleSet($id);
					}
					break;
				case 'delete_ruleset_rule':
					if (ze\priv::check('_PRIV_SET_EMAIL_ROUTING')){
						$this->deleteRuleSetRule($id, ($_REQUEST['refinerId'] ?? false));
					}
					break;
				case 'delete_form_submission':
					if (ze\priv::check('_PRIV_DELETE_FORM_SUBMISSION')){
						foreach (explode(',',$ids) as $id) {
							$this->deleteFormSubmission($id);
						}
					}
					break;
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if ($_POST['export_all_data'] ?? false) {
			$ar = explode('_', $refinerId);
			self::exportformSubmitionsData($path, $ar[1], $ar[0]);
			
		} elseif ($_POST['export_selected_data'] ?? false) {
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
					,plugin_instance_name = '" . ze\escape::sql($instanceName) . "'
					,submission_datetime = '" . date("Y-m-d H:i:s") . "'";
		
		if ($userId) {
			$sql .= ", user_id = ". (int) $userId;
		}
		if ($ipAddress) {
			$sql .= ", ip_address = '". ze\escape::sql($ipAddress). "'";
		}
		if ($cId) {
			$sql .= ", content_id = ". (int) $cId;
		}
		if ($cType) {
			$sql .= ", content_type = '". ze\escape::sql($cType). "'";
		}
		if ($cVersion) {
			$sql .= ", content_version = ". (int) $cVersion;
		}

		ze\sql::update($sql);
		$ID = ze\sql::insertId();
		if ($ID) {
			$sql = 'INSERT INTO ' 
						. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'form_submission_data  (submission_id,ordinal,label,value,attachment) 
					VALUES ';
			$i=1;
			$valuesPresent=0;
			foreach ($formFields as $key=>$val){
				$sql .= '(' . $ID  . ',' .$i++ .  ",'" . ze\escape::sql($key)  . "','" . ze\escape::sql($val) . "',NULL),";
				$valuesPresent=1;
			}
			foreach ($attachments as $key=>$val){
				if (file_exists($val)){
					$sql .= '(';
					$sql .= $ID;
					$sql .= ',' . $i++;
					$sql .= ",'" . ze\escape::sql($key) . "'";
					$sql .= ",'" . ze\escape::sql(ze::ifNull($attachmentFilenameMappings[$key] ?? false, basename($val))) ."'";
					$sql .= ",'" . ze\escape::sql(file_get_contents($val)) ."'";
					$sql .= "),";
					
					$valuesPresent=1;
				}
			}


			$sql = substr($sql, 0, -1);
			if ($valuesPresent){
				ze\sql::update($sql);
				if (!ze\sql::affectedRows()) {
					$sql = 'DELETE FROM '
								. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . 'form_submissions
							WHERE 
								id =' . (int)$ID;
					ze\sql::update($sql);
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
		
		ze\sql::update($sql);

		$sql = "DELETE FROM "
					. DB_NAME_PREFIX . ZENARIO_FORM_INPUT_HANDLER_PREFIX . "form_submissions
				WHERE
					id = " . (int) $submissionId;
		
		ze\sql::update($sql);
	}


	private function sendEmailsUsingTemplate($instanceId,$instanceName,$formFields,$attachments,$attachmentFilenameMappings){


		$ruleSets = $this->getRuleSets();
		foreach ($ruleSets as $set){
			$rulesInSet = $this->getRules($set['id']);
			foreach ($rulesInSet as $rule){
				
				$email = $rule['destination_address'];
				
				if (strrpos($email, '@') === false) {
					if (!empty($formFields[$email]) && ze\ring::validateEmailAddress($formFields[$email])) {
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
							$matched = strcmp(strtolower($matchingValue),strtolower($formFields[$rule['object_name']] ?? false))===0;
							break;
						
						case 'starts_with':
							$matched = strpos(strtolower($formFields[$rule['object_name']] ?? false),strtolower($matchingValue))===0;
							break;
						case 'match_regexp':
							$matched = preg_match ($matchingValue,($formFields[$rule['object_name']] ?? false))===1;
							break;
					}
					
					
					
					if ($inverse? !$matched : $matched) {
						if ($templateCode = ze\row::get('email_templates','code',['id'=>$rule['email_template_number']])) {
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
				$fields=[];
				$currentVals=[];
				if (count($arr)>0){
					foreach ($arr as $it){
						if (ze::setting("zenario_form_input_handler__sf_1_enable")) {
							if ($it['label']==ze::setting("zenario_form_input_handler__sf_1_field_name")) {
								$item['zenario_form_input_handler__sf_1'] = $it['value'];
							}
						}
		
						if (ze::setting("zenario_form_input_handler__sf_2_enable")) {
							if ($it['label']==ze::setting("zenario_form_input_handler__sf_2_field_name")) {
								$item['zenario_form_input_handler__sf_2'] = $it['value'];
							}
						}
		
						if (ze::setting("zenario_form_input_handler__sf_3_enable")) {
							if ($it['label']==ze::setting("zenario_form_input_handler__sf_3_field_name")) {
								$item['zenario_form_input_handler__sf_3'] = $it['value'];
							}
						}
		
						if (ze::setting("zenario_form_input_handler__sf_4_enable")) {
							if ($it['label']==ze::setting("zenario_form_input_handler__sf_4_field_name")) {
								$item['zenario_form_input_handler__sf_4'] = $it['value'];
							}
						}
		
						if (ze::setting("zenario_form_input_handler__sf_5_enable")) {
							if ($it['label']==ze::setting("zenario_form_input_handler__sf_5_field_name")) {
								$item['zenario_form_input_handler__sf_5'] = $it['value'];
							}
						}
		
					}
				}
				
				break;
		}
	}
	
}
