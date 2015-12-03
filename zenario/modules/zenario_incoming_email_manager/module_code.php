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


class zenario_incoming_email_manager extends module_base_class {
	
	
	//IMAP functions
	
	//connectToMailbox() connects to an IMAP mail server, enabling you to then call fetchEmail(), markEmailAsProcessed() and endConnection()
		//$server
			//A connection parameter to the server, e.g. {mail.example.com:143/ssl}'
		//$username
			//The username, typically an email address
		//$password
			//The password
		//$mailbox
			//A mailbox (e.g. "INBOX") to read.
		//$processingTimeLimit
			//If this is set, fetchEmail() will return false if called after the time limit is up. Note that this isn't a timeout.
		//$processedBox
			//A mailbox (e.g. "INBOX.Processed") that markEmailAsProcessed() should move processed emails into.
			//If you don't specify one then it will delete the emails instead.
			//But note that neither case will happen until you call endConnection().
		//$errorBox
			//A mailbox (e.g. "INBOX.Errors") to move emails with errors into.
		//This function returns false if the connection fails, otherwise it returns a connection number which you can use to call
		//fetchEmail(), markEmailAsProcessed() and endConnection(). 
	
	public static function connectToMailbox($server, $username, $password, $mailbox, $processingTimeLimit = false, $processedBox = false, $errorBox = false) {
		
		$con = zenario_incoming_email_manager::$con = ++zenario_incoming_email_manager::$numCons;
		zenario_incoming_email_manager::$cons[$con] = array();
		
		if (!zenario_incoming_email_manager::$cons[$con]['mbox'] = imap_open('{'. $server. '}'. $mailbox, $username, $password)) {
			throw new Exception('Could not connect to mailbox.');
		}
		
		zenario_incoming_email_manager::$cons[$con]['startTime'] = time();
		if (zenario_incoming_email_manager::$cons[$con]['timeLimit'] = $processingTimeLimit) {
			set_time_limit(zenario_incoming_email_manager::$cons[$con]['timeLimit'] + 10);
		}
		
		$exists = @imap_listmailbox(zenario_incoming_email_manager::$cons[$con]['mbox'], '{'. $server. '}', $mailbox);
		if (empty($exists)) {
			throw new Exception('Inbox was not found on account.');
		}
		
		if (zenario_incoming_email_manager::$cons[$con]['probox'] = $processedBox) {
			$exists = @imap_listmailbox(zenario_incoming_email_manager::$cons[$con]['mbox'], '{'. $server. '}', zenario_incoming_email_manager::$cons[$con]['probox']);
			if (empty($exists)) {
				throw new Exception('Processed sub-folder was not found on account.');
			}
		}
		if (zenario_incoming_email_manager::$cons[$con]['erbox'] = $errorBox) {
			$exists = @imap_listmailbox(zenario_incoming_email_manager::$cons[$con]['mbox'], '{'. $server. '}', zenario_incoming_email_manager::$cons[$con]['erbox']);
			if (empty($exists)) {
				throw new Exception('Error sub-folder was not found on account.');
			}
		}
		
		
		zenario_incoming_email_manager::$cons[$con]['id'] = 0;
		zenario_incoming_email_manager::$cons[$con]['ids'] = array();
		
		$c = imap_num_msg(zenario_incoming_email_manager::$cons[$con]['mbox']);
		for ($i = 1; $i <= $c; ++$i) {
			zenario_incoming_email_manager::$cons[$con]['ids'][$i] = imap_uid(zenario_incoming_email_manager::$cons[$con]['mbox'], $i);
		}
		
		
		return $con;
	}
	
	
	//fetchEmail() fetches an email from an IMAP mailbox
		//$con
			//A connection number returned from connectToMailbox(). Defaults to the most recent connection that was opened.
		//This function returns a path to the fetched email, which will be a file in the tmp directory.
		//This path can be then used as an input to getMessageHeader(), getMessageAddresses(), getMessagePlainText(),
		//getMessagePlainTextFile() and getMessageFiles()
	
	public static function fetchEmail($con = false) {
		//Use a specified connection, or the last connection if none is specified
		$con = ifNull($con, zenario_incoming_email_manager::$con);
		
		//Check the connection exists...
		if (empty(zenario_incoming_email_manager::$cons[$con]['mbox'])
		
		//...has not passed the time limit if one was set...
		 || (zenario_incoming_email_manager::$cons[$con]['timeLimit']
		  && (time() - zenario_incoming_email_manager::$cons[$con]['startTime'] >= zenario_incoming_email_manager::$cons[$con]['timeLimit']))
		
		//...that there is another email to fetch...
		 || (empty(zenario_incoming_email_manager::$cons[$con]['ids'][++zenario_incoming_email_manager::$cons[$con]['id']]))
		
		//...and that we can fetch it.
		 || (!$email = tempnam(sys_get_temp_dir(), 'eml'))
		 || (!imap_savebody(zenario_incoming_email_manager::$cons[$con]['mbox'], $email, zenario_incoming_email_manager::$cons[$con]['ids'][zenario_incoming_email_manager::$cons[$con]['id']], '', FT_UID))) {
			return false;
		
		} else {
			return $email;
		}
	}
	
	
	//markEmailAsProcessed() marks the most recently fetched email as processed. This will either delete or move it in the mailbox.
	//If you call this function, you *must* later call endConnection() to commit your changes.
		//$con
			//A connection number returned from connectToMailbox(). Defaults to the most recent connection that was opened.
	
	public static function markEmailAsProcessed($errors = false, $con = false) {
		//Use a specified connection, or the last connection if none is specified
		$con = ifNull($con, zenario_incoming_email_manager::$con);
		
		//Check the connection exists.
		if (empty(zenario_incoming_email_manager::$cons[$con]['mbox'])) {
			return;
		}
		
		$box = $errors? 'erbox' : 'probox';
		
		//Move or delete the email, depending on whether a processed box was specified when connecting
		if (zenario_incoming_email_manager::$cons[$con][$box]) {
			imap_mail_move(
				zenario_incoming_email_manager::$cons[$con]['mbox'],
				zenario_incoming_email_manager::$cons[$con]['ids'][zenario_incoming_email_manager::$cons[$con]['id']],
				zenario_incoming_email_manager::$cons[$con][$box],
				CP_UID);
		} else {
			imap_delete(
				zenario_incoming_email_manager::$cons[$con]['mbox'],
				zenario_incoming_email_manager::$cons[$con]['ids'][zenario_incoming_email_manager::$cons[$con]['id']],
				FT_UID);
		}
	}
	
	
	//endConnection() closes a connection to a mailbox, and commits any moves/deletes that were performed with markEmailAsProcessed()
		//$con
			//A connection number returned from connectToMailbox(). Defaults to the most recent connection that was opened.
	
	public static function endConnection($con = false) {
		//Use a specified connection, or the last connection if none is specified
		$con = ifNull($con, zenario_incoming_email_manager::$con);
		
		//Check the connection exists.
		if (empty(zenario_incoming_email_manager::$cons[$con]['mbox'])) {
			return;
		}
		
		imap_expunge(zenario_incoming_email_manager::$cons[$con]['mbox']);
		imap_close(zenario_incoming_email_manager::$cons[$con]['mbox']);
		unset(zenario_incoming_email_manager::$cons[$con]);
	}
	

	
	//getMessageHeader() fetches header information from an email
		//$email
			//The path to an email on the filesystem. You can get this from fetchEmail(), or if you are handling
			//an email that has been piped to your code you can use a value of 'php://stdin'
		//$header
			//An array of header information will be placed in this variable.
		//Will return false if the email could not be read.
	
	public static function getMessageHeader($email, &$header) {
		if (zenario_incoming_email_manager::decode($email)) {
			$header = array();
			
			foreach (zenario_incoming_email_manager::$decodedFiles[$email] as &$message) {
				if (!empty($message['Headers'])) {
					$header = array_merge_recursive($message['Headers'], $header);
				}
			}
			
			return true;
		} else {
			return $header = false;
		}
	}
	
	
	//getMessageAddresses() fetches email addresses (e.g. the from, to, cc and reply-to)
		//$email
			//The path to an email on the filesystem. You can get this from fetchEmail(), or if you are handling
			//an email that has been piped to your code you can use a value of 'php://stdin'
		//$addresses
			//An array of addresses will be placed in this variable.
		//Will return false if the email could not be read.
	
	public static function getMessageAddresses($email, &$addresses) {
		if (zenario_incoming_email_manager::decode($email)) {
			$addresses = array();
			
			foreach (zenario_incoming_email_manager::$decodedFiles[$email] as &$message) {
				if (!empty($message['ExtractedAddresses'])) {
					$addresses = array_merge_recursive($message['ExtractedAddresses'], $addresses);
				}
			}
			
			return true;
		} else {
			return $addresses = false;
		}
	}
	
	
	//getMessagePlainText() fetches a plain text version of an email.
	//If for some reason there are two plain-text versions in an email, only the first to be found will be read.
		//$email
			//The path to an email on the filesystem. You can get this from fetchEmail(), or if you are handling
			//an email that has been piped to your code you can use a value of 'php://stdin'
		//$text
			//The plain text version of the email will be placed in this variable.
		//$convertHTMLEmailsToPlainText
			//If an email has been provided in HTML format and this is set to true,
			//this function will attempt to convert it to plain text and then return that as a result.
		//Will return false if the email could not be read, or if no plain text version exists.
	
	public static function getMessagePlainText($email, &$text, $convertHTMLEmailsToPlainText = false) {
		$text = '';
		if (zenario_incoming_email_manager::decode($email)) {
			if (zenario_incoming_email_manager::searchForSomething(zenario_incoming_email_manager::$decodedFiles[$email], 'text', $text)) {
				return true;
			
			} else {
				$text = '';
				if ($convertHTMLEmailsToPlainText && zenario_incoming_email_manager::getMessageHTML($email, $text)) {
					$text = trim(html_entity_decode(strip_tags(str_ireplace(array('<div ', '<div>', '<p ', '<p>', '&nbsp;', '&nbsp'), array("\n<div ", "\n<div>", "\n<p ", "\n<p>", ' ', ' '), $text)), ENT_QUOTES, 'UTF-8'));
					return true;
				}
			}
		}
		
		return false;
	}
	
	
	//getMessagePlainTextFile() saves a plain text version of an email to a file in the tmp directory.
	//If for some reason there are two plain-text versions in an email, only the first to be found will be read.
		//$email
			//The path to an email on the filesystem. You can get this from fetchEmail(), or if you are handling
			//an email that has been piped to your code you can use a value of 'php://stdin'
		//Will return false if the email could not be read, or if no plain text version exists.
		//Otherwise it will return a path to a file in the tmp directory.
	
	public static function getMessagePlainTextFile($email) {
		$path = false;
		if (zenario_incoming_email_manager::decode($email)
		 && zenario_incoming_email_manager::searchForSomething(zenario_incoming_email_manager::$decodedFiles[$email], 'text_file', $path)) {
			return $path;
		} else {
			return false;
		}
	}
	
	
	//getMessageHTML() fetches the HTML source of emails in HTML format
	//If for whatever reason there are multiple html parts, a combination of the parts will be returned
		//$email
			//The path to an email on the filesystem. You can get this from fetchEmail(), or if you are handling
			//an email that has been piped to your code you can use a value of 'php://stdin'
		//$html
			//The HTML will be placed in this variable.
		//Will return false if the email could not be read, or if no html text was in the email.
	
	public static function getMessageHTML($email, &$html) {
		$html = '';
		if (zenario_incoming_email_manager::decode($email)) {
			return zenario_incoming_email_manager::searchForSomething(zenario_incoming_email_manager::$decodedFiles[$email], 'html', $html);
		} else {
			return false;
		}
	}
	
	
	//getMessageFiles() saves all of the attachments in an email to files in the tmp directory.
		//$email
			//The path to an email on the filesystem. You can get this from fetchEmail(), or if you are handling
			//an email that has been piped to your code you can use a value of 'php://stdin'
		//Will return an associative array of paths => filenames of to a file in the tmp directory.
	
	public static function getMessageFiles($email) {
		$files = array();
		if (zenario_incoming_email_manager::decode($email)) {
			zenario_incoming_email_manager::searchForSomething(zenario_incoming_email_manager::$decodedFiles[$email], 'files', $files);
		}
		return $files;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks':
				if ($refinerName == 'zenario_incoming_email_manager__incoming_emails') {
					$panel['title'] = adminPhrase('Incoming Email Handlers');
					$panel['item']['name'] = adminPhrase('Incoming Email Handler');
					$panel['item']['names'] = adminPhrase('Incoming Email Handlers');
					$panel['item']['css_class'] = 'zenario_incoming_email_manager__handler';
					$panel['no_items_message'] = adminPhrase('No Incoming Email Handlers exist. If you install and run a Module that has an Incoming Email Handler, it will appear here.');
					
					unset($panel['item_buttons']['enable']);
					unset($panel['item_buttons']['edit']);
					unset($panel['item_buttons']['rerun']);
					unset($panel['collection_buttons']['get_code']);
					unset($panel['collection_buttons']['enable_all']);
					unset($panel['collection_buttons']['suspend_all']);

					$panel['item_buttons']["suspend"]['ord']=2;
					$panel['item_buttons']['zenario_incoming_email_manager__edit_enabled']['ord']=1;
				
				} else {
					unset($panel['item_buttons']['zenario_incoming_email_manager__edit_enable']);
					unset($panel['item_buttons']['zenario_incoming_email_manager__edit_enabled']);
					unset($panel['item_buttons']['zenario_incoming_email_manager__rerun']);
					unset($panel['columns']['zenario_incoming_email_manager__script_enable']);
					unset($panel['columns']['zenario_incoming_email_manager__script_recipient_username']);
					unset($panel['columns']['zenario_incoming_email_manager__fetch_enable']);
					unset($panel['columns']['zenario_incoming_email_manager__fetch_server']);
					unset($panel['columns']['zenario_incoming_email_manager__fetch_username']);
				}
				
				break;
			
			
			case 'zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks/hidden_nav/log/panel':
				if (!get('refiner__zenario_incoming_email_manager__incoming_emails')) {
					unset($panel['columns']['zenario_incoming_email_manager__email_from']);
					unset($panel['columns']['zenario_incoming_email_manager__email_subject']);
					unset($panel['columns']['zenario_incoming_email_manager__email_sent']);
				}
				
				break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks':
				if ($refinerName == 'zenario_incoming_email_manager__incoming_emails') {
					foreach ($panel['items'] as &$item) {
						if (!$item['zenario_incoming_email_manager__fetch_enable']) {
							unset($item['traits']['can_rerun']);
						}
					}
				}
				
				break;
			
			
			case 'zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks/hidden_nav/log/panel':
				if (get('refiner__zenario_incoming_email_manager__incoming_emails')) {
					$panel['title'] = adminPhrase('Logs for Incoming Email Handler "[[job]]"', array('job' => getRow('jobs', 'job_name', $refinerId)));
				}
				
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks':
				if (post('rerun') && checkPriv('_PRIV_MANAGE_SCHEDULED_TASK') && $checkEmailId = getRow('jobs', 'id', array('job_name' => 'jobCheckEmails'))) {
					updateRow('jobs', array('status' => 'rerun_scheduled'), $checkEmailId);
					updateRow('jobs', array('status' => 'rerun_scheduled'), $ids);
				}
				
				if ((post('action') == 'enable_incoming_email_handler') && checkPriv('_PRIV_MANAGE_SCHEDULED_TASK') && $checkEmailId = getRow('jobs', 'id', array('job_name' => 'jobCheckEmails'))) {
					foreach (explode(',', $ids) as $id) {
						updateRow('jobs',array('enabled' => 1),$id);
					}
				}
		}
	}
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'zenario_job':
				if ($box['key']['manager_class_name'] == 'zenario_incoming_email_manager') {
					if ($details = getRow(ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. 'accounts', true, $box['key']['id'])) {
						foreach (array('zenario_incoming_email_manager__trigger', 'zenario_incoming_email_manager__fetch') as $tab) {
							foreach ($details as $field => $value) {
								if (isset($box['tabs'][$tab]['fields'][$field])) {
									$box['tabs'][$tab]['fields'][$field]['value'] = $value;
								}
							}
						}
						
						if (!getRow('jobs', 'enabled', $box['key']['id'])) {
							$box['tabs']['zenario_incoming_email_manager__trigger']['fields']['script_enable']['value'] = 0;
							$box['tabs']['zenario_incoming_email_manager__fetch']['fields']['fetch_enable']['value'] = 0;
						}
					}
					
					//Enter some suitable default values for the mailbox connection details, if they are not enabled
					/*if (empty($box['tabs']['zenario_incoming_email_manager__fetch']['fields']['fetch_enable']['value'])
					 && empty($box['tabs']['zenario_incoming_email_manager__fetch']['fields']['fetch_server']['value'])) {
						$box['tabs']['zenario_incoming_email_manager__fetch']['fields']['fetch_server']['value'] = 'mail.example.com:143/ssl';
						$box['tabs']['zenario_incoming_email_manager__fetch']['fields']['fetch_mailbox']['value'] = 'INBOX';
						$box['tabs']['zenario_incoming_email_manager__fetch']['fields']['fetch_processed_mailbox']['value'] = 'INBOX.Processed';
						$box['tabs']['zenario_incoming_email_manager__fetch']['fields']['fetch_error_mailbox']['value'] = 'INBOX.Errors';
					}*/
					
					if (!setting('jobs_enabled') || !getRow('jobs', 'enabled', array('job_name' => 'jobCheckEmails'))) {
						$box['tabs']['zenario_incoming_email_manager__fetch']['notices']['not_enabled']['show'] = true;
						$box['tabs']['zenario_incoming_email_manager__fetch']['edit_mode']['enabled'] = false;
					}
					
					/*
					commented
					$box['tabs']['zenario_incoming_email_manager__trigger']['fields']['desc2']['snippet']['html'] =
						str_replace('[[path]]', htmlspecialchars(CMS_ROOT. moduleDir('zenario_incoming_email_manager', 'mail/email_handler.php')),
							$box['tabs']['zenario_incoming_email_manager__trigger']['fields']['desc2']['snippet']['html']);
					*/
					
					$box['tabs']['time_and_day']['visible_if'] =
					$box['tabs']['month']['visible_if'] = "zenarioAB.value('fetch_enable', 'zenario_incoming_email_manager__fetch')";
					
					$details = getRow('jobs', array('job_name', 'module_id'), $box['key']['id']);
					$details['module_display_name'] = getModuleDisplayName($details['module_id']);
					$box['title'] = adminPhrase('Viewing/Editing Incoming Email Handler "[[job_name]]" for the Module [[module_display_name]]', $details);
					
					$box['tabs']['reporting']['fields']['log_on_error']['label'] = adminPhrase('Log errors when processing email:');
					$box['tabs']['reporting']['fields']['email_on_error']['label'] = adminPhrase('Send email notification when there is an error processing an email:');
					$box['tabs']['reporting']['fields']['log_on_action']['label'] = adminPhrase('Log processed email:');
					$box['tabs']['reporting']['fields']['email_on_action']['label'] = adminPhrase('Send email notification when an email is processed:');
					$box['tabs']['reporting']['fields']['log_on_no_action']['label'] = adminPhrase('Log email that could not be processed:');
					$box['tabs']['reporting']['fields']['email_on_no_action']['label'] = adminPhrase('Send email notification when an email could not be processed:');
				}
				
				//set enable always
				//fetch_enable
				$values['zenario_incoming_email_manager__fetch/fetch_enable']=1;
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_job':
				if ($box['key']['manager_class_name'] == 'zenario_incoming_email_manager') {
					/*
					commented
					if ($values['zenario_incoming_email_manager__trigger/script_enable']) {
						if (!$username = $values['zenario_incoming_email_manager__trigger/script_recipient_username']) {
							$box['tabs']['zenario_incoming_email_manager__trigger']['errors'][] =
								adminPhrase("Please enter the email recipient's username.");
						
						} elseif (preg_replace('/\S/', '', $username) || !validateScreenName($username)) {
							$box['tabs']['zenario_incoming_email_manager__trigger']['errors'][] =
								adminPhrase('Please enter the a valid username.');
						
						} elseif (checkRowExists(
							ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. 'accounts',
							array('script_recipient_username' => $username, 'job_id' => array('!' => $box['key']['id']))
						)) {
							//$box['tabs']['zenario_incoming_email_manager__trigger']['errors'][] =
							//	adminPhrase('A different Incoming Email Handler is already handling that username.');
						}
					}*/
					if ($values['zenario_incoming_email_manager__fetch/fetch_enable']) {
						if (!function_exists('imap_open')) {
							$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
								adminPhrase('The IMAP PHP extension is not enabled on this server. Please ask your server administrator to enable this extension. Please see http://uk.php.net/manual/en/imap.installation.php for more details.');
						
						} else {
							if (!$server = $values['zenario_incoming_email_manager__fetch/fetch_server']) {
								$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
									adminPhrase('Please enter a server.');
							}
							
							if (!$mailbox = $values['zenario_incoming_email_manager__fetch/fetch_mailbox']) {
								$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
									adminPhrase('Please enter a mailbox.');
							
							} elseif (preg_match('/[^\w \d\-\_\.\[\]\{\}\<\>\(\)]/u', $mailbox)) {
								$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
									adminPhrase('The mailbox name may only contain the letters a-z, digits, dots, hyphens, brackets and underscores.');
							}
							
							if (!$username = $values['zenario_incoming_email_manager__fetch/fetch_username']) {
								$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
									adminPhrase('Please enter a username.');
							}
							
							if ($server && $username
							 && checkRowExists(
								ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. 'accounts',
								array('fetch_server' => $server, 'fetch_username' => $username, 'job_id' => array('!' => $box['key']['id']))
							)) {
								//$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
								//	adminPhrase('A different Incoming Email Handler is already handling that user on that server.');
							}
							
							if ($keepMail = $values['zenario_incoming_email_manager__fetch/fetch_keep_mail']) {
								if (!$processedMailbox = $values['zenario_incoming_email_manager__fetch/fetch_processed_mailbox']) {
									$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
										adminPhrase('Please enter a sub-folder for processed email.');
								
								} elseif (preg_match('/[^\w \d\-\_\.\[\]\{\}\<\>\(\)]/u', $processedMailbox)) {
									$box['tabs']['zenario_incoming_email_manager__fetch']['errors']['sub_name'] =
										adminPhrase("A sub-folder's name may only contain the letters a-z, digits, dots, hyphens, brackets and underscores.");
								
								} elseif ($processedMailbox == $mailbox) {
									$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
										adminPhrase('Please enter a different mailbox than the inbox for processed email.');
								}
							}
							
							if (!$errorMailbox = $values['zenario_incoming_email_manager__fetch/fetch_error_mailbox']) {
								$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
									adminPhrase('Please enter a sub-folder for errors.');
							
							} elseif (preg_match('/[^\w \d\-\_\.\[\]\{\}\<\>\(\)]/u', $errorMailbox)) {
								$box['tabs']['zenario_incoming_email_manager__fetch']['errors']['sub_name'] =
									adminPhrase("A sub-folder's name may only contain the letters a-z, digits, dots, hyphens, brackets and underscores.");
							
							} elseif ($errorMailbox == $mailbox) {
								$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
									adminPhrase('Please enter a different mailbox than the inbox for errors.');
							}
							
							//Make a connection to test if this actually works. May be slow, so only do it while actually saving
							if (empty($box['tabs']['zenario_incoming_email_manager__fetch']['errors'])
							 && engToBooleanArray($box['tabs']['zenario_incoming_email_manager__fetch'], 'edit_mode', 'on')
							 && $saving) {
							 	imap_timeout(IMAP_OPENTIMEOUT, 10);
							 	imap_timeout(IMAP_READTIMEOUT, 10);
							 	imap_timeout(IMAP_WRITETIMEOUT, 10);
							 	imap_timeout(IMAP_CLOSETIMEOUT, 10);
								
								if (!$mbox = @imap_open('{'. $server. '}'. $mailbox, $username, $values['zenario_incoming_email_manager__fetch/fetch_password'])) {
									$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
										adminPhrase('A connection could not be made using these settings. Please check that they are correct.');

								} else {
									$exists = @imap_listmailbox($mbox, '{'. $server. '}', $mailbox);
									if ((empty($exists))) {
										$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
											adminPhrase('The mailbox "[[mailbox]]" was not found on the server.', array('mailbox' => $mailbox));
									}
									
									if ($keepMail) {
										$exists = @imap_listmailbox($mbox, '{'. $server. '}', $processedMailbox);
										if ((empty($exists))) {
											$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
											adminPhrase('The mailbox "[[mailbox]]" was not found on the server.', array('mailbox' => $processedMailbox));
										}
									}
									
									$exists = @imap_listmailbox($mbox, '{'. $server. '}', $errorMailbox);
									if ((empty($exists))) {
										$box['tabs']['zenario_incoming_email_manager__fetch']['errors'][] =
										adminPhrase('The mailbox "[[mailbox]]" was not found on the server.', array('mailbox' => $errorMailbox));
									}
									
									imap_close($mbox);
								}
							 	
								//Workaround for a bug in a more recent version of PHP.
								//We need to clear any errors that might otherwise appear in the output.
								$imapErrors = imap_errors();
							}
						}
					}
				}
				
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_job':
				if ($box['key']['manager_class_name'] == 'zenario_incoming_email_manager' && checkPriv('_PRIV_MANAGE_SCHEDULED_TASK')) {
					
					/*
					if (engToBooleanArray($box['tabs']['zenario_incoming_email_manager__trigger'], 'edit_mode', 'on')) {
						if ($values['zenario_incoming_email_manager__trigger/script_enable']) {
							
							setRow(ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. 'accounts',
								array(
									'script_enable' => 1,
									'script_recipient_username' => $values['zenario_incoming_email_manager__trigger/script_recipient_username']),
								$box['key']['id']);
						
						} else {
							//setRow(ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. 'accounts',
							//	array(
							//		'script_enable' => 0,
							//		'script_recipient_username' => null),
							//	$box['key']['id']);
								
								
								updateRow(ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. 'accounts', array('script_enable' => 0),$box['key']['id']);
								
								
						}
					}*/
					
					if (engToBooleanArray($box['tabs']['zenario_incoming_email_manager__fetch'], 'edit_mode', 'on')) {
						if ($values['zenario_incoming_email_manager__fetch/fetch_enable']) {
							
							setRow(ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. 'accounts',
								array(
									'fetch_enable' => 1,
									'fetch_server' => $values['zenario_incoming_email_manager__fetch/fetch_server'],
									'fetch_username' => $values['zenario_incoming_email_manager__fetch/fetch_username'],
									'fetch_password' => $values['zenario_incoming_email_manager__fetch/fetch_password'],
									'fetch_mailbox' => $values['zenario_incoming_email_manager__fetch/fetch_mailbox'],
									'fetch_keep_mail' => $values['zenario_incoming_email_manager__fetch/fetch_keep_mail'],
									'fetch_processed_mailbox' => $values['zenario_incoming_email_manager__fetch/fetch_processed_mailbox'],
									'fetch_error_mailbox' => $values['zenario_incoming_email_manager__fetch/fetch_error_mailbox']),
								$box['key']['id']);
						
						} else {
							/*setRow(ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. 'accounts',
								array(
									'fetch_enable' => 0,
									'fetch_server' => null,
									'fetch_username' => null,
									'fetch_password' => '',
									'fetch_mailbox' => '',
									'fetch_keep_mail' => 0,
									'fetch_processed_mailbox' => '',
									'fetch_error_mailbox' => ''),
								$box['key']['id']);*/
								
								
								updateRow(ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. 'accounts', array('fetch_enable' => 0),$box['key']['id']);
								
								
								
						}
					}
					/*
					updateRow('jobs',
						array(
							'enabled' =>
								
								commented
								//$values['zenario_incoming_email_manager__trigger/script_enable']||
								$values['zenario_incoming_email_manager__fetch/fetch_enable']),
						$box['key']['id']);
					*/
				}
				
				break;
		}
	}

	
	
	
	
	
	
	
	//Wrapper functions for mimeparser
	
	protected static $con;
	protected static $numCons = 0;
	protected static $cons = array();
	
	protected static $parser = false;
	protected static $decodedFiles = array();
	protected static $successfullydecodedFiles = array();
	
	protected static function initParser() {
		if (!zenario_incoming_email_manager::$parser) {
			require CMS_ROOT. moduleDir('zenario_incoming_email_manager', 'libraries/bsd/mimeparser/rfc822_addresses.php');
			require CMS_ROOT. moduleDir('zenario_incoming_email_manager', 'libraries/bsd/mimeparser/mime_parser.php');
			
			zenario_incoming_email_manager::$parser = new mime_parser_class;
		}
	}
	
	protected static function decode($email) {
		zenario_incoming_email_manager::initParser();
		
		if (!isset(zenario_incoming_email_manager::$decodedFiles[$email])) {
			zenario_incoming_email_manager::$decodedFiles[$email] = false;
			zenario_incoming_email_manager::$successfullydecodedFiles[$email] =
				zenario_incoming_email_manager::$parser->Decode(
					array('File' => $email, 'SaveBody' => sys_get_temp_dir()),
					zenario_incoming_email_manager::$decodedFiles[$email]);
		}
		
		return zenario_incoming_email_manager::$successfullydecodedFiles[$email];
	}
	
	//Two functions to help pass informaiton on decoded email between exec threads
	protected static function saveDecode($email) {
		if ((zenario_incoming_email_manager::decode($email))
		 && ($path = tempnam(sys_get_temp_dir(), 'dec'))
		 && (file_put_contents($path, serialize(zenario_incoming_email_manager::$decodedFiles[$email])))) {
			return $path;
		} else {
			return false;
		}
	}
	
	protected static function loadDecode($email, $path) {
		if (is_file($path)
		 && is_readable($path)
		 && zenario_incoming_email_manager::$decodedFiles[$email] = unserialize(file_get_contents($path))) {
			return zenario_incoming_email_manager::$successfullydecodedFiles[$email] = true;
		} else {
			return false;
		}
	}
	
		
		
	protected static function searchForSomething(&$decoded, $searchFor, &$output) {
		foreach ($decoded as &$message) {
			$results = false;
			if (zenario_incoming_email_manager::$parser->Analyze($message, $results)) {
				if ($searchFor == 'text' || $searchFor == 'text_file') {
					if ($results['Type'] == 'text' && empty($results['FileName']) && file_exists($results['DataFile'])) {
						if ($searchFor == 'text_file') {
							$output = $results['DataFile'];
						} elseif (empty($results['Encoding'])) {
							$output = file_get_contents($results['DataFile']);
						} else {
							$output = mb_convert_encoding(file_get_contents($results['DataFile']), 'UTF-8', $results['Encoding']);
						}
						
						return true;
					}
				
				} elseif ($searchFor == 'html') {
					if ($results['Type'] == 'html' && empty($results['FileName']) && file_exists($results['DataFile'])) {
						if (empty($results['Encoding'])) {
							$output .= file_get_contents($results['DataFile']);
						} else {
							$output .= mb_convert_encoding(file_get_contents($results['DataFile']), 'UTF-8', $results['Encoding']);
						}
					}
				
				} elseif ($searchFor == 'files') {
					if (!empty($results['FileName']) && file_exists($results['DataFile'])) {
						$output[$results['DataFile']] = $results['FileName'];
					} elseif (!empty($results['Attachments'])) {
						zenario_incoming_email_manager::getAttachmentsRecursive($results['Attachments'], $output);
					}
				}
			}
			
			if (!empty($message['Parts']) && is_array($message['Parts'])) {
				if (zenario_incoming_email_manager::searchForSomething($message['Parts'], $searchFor, $output)) {
					return true;
				}
			}
		}
		
		return $output !== '';
	}
	
	private static function getAttachmentsRecursive($attachments, &$output) {
		foreach($attachments as $attachment) {
			if (!empty($attachment['FileName']) && file_exists($attachment['DataFile'])) {
				$output[$attachment['DataFile']] = $attachment['FileName'];
			}
			if (isset($attachment['Attachments'])) {
				zenario_incoming_email_manager::getAttachmentsRecursive($attachment['Attachments'], $output);
			}
		}
	}
	
	//Code for handling jobs/scheduled tasks/incoming email handlers
	
	//Look for every email job, and launch a process for each using the same logic as for scheduled tasks
	public static function jobCheckEmails($serverTime) {
		if (inc('zenario_scheduled_task_manager')) {
			$jobsRun = zenario_scheduled_task_manager::step1('zenario_incoming_email_manager');
			
			if (!$jobsRun) {
				echo adminPhrase('No Email Handlers were scheduled to run at this time.');
				return false;
			
			} elseif ($jobsRun == 1) {
				echo adminPhrase('Ran an Email Handler, see its log for details.');
				return true;
			
			} else {
				echo adminPhrase('Ran [[jobs]] Email Handlers, see their logs for details.', array('jobs' => $jobsRun));
				return true;
			}
			
		} else {
			exit;
		}
	}
	
	
	//For an email job, check if fetch is enabled for it, and only run it if it is
	//Connect to the mailbox, keep fetching mail one-at-a-time until we are out of mails or three minutes have passed,
	//and then call the email job for each email, using another exec for protection in case it crashes
	public static function step2(
		$managerClassName,
		$serverTime, $jobId, $jobName, $moduleClassName, $staticMethod,
		$logActions, $logInaction, $emailActions, $emailInaction,
		$emailAddressAction, $emailAddressInaction, $emailAddressError
	) {
		if (!$fetch = getRow(
			ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. 'accounts',
			array('fetch_server', 'fetch_mailbox', 'fetch_username', 'fetch_password', 'fetch_keep_mail', 'fetch_processed_mailbox', 'fetch_error_mailbox'),
			array('job_id' => $jobId, 'fetch_enable' => 1)
		)) {
			exit;
		}
		
		
		
		//Lock the job, set some fields
		updateRow('jobs', array('last_run_started' => $serverTime, 'status' => 'in_progress'), $jobId);
		$overallResult = '<!--no_action_taken-->';
		
		
		try {
			zenario_incoming_email_manager::connectToMailbox(
				$fetch['fetch_server'], $fetch['fetch_username'], $fetch['fetch_password'], $fetch['fetch_mailbox'], 180,
				$fetch['fetch_keep_mail']? $fetch['fetch_processed_mailbox'] : false, $fetch['fetch_error_mailbox']);
			
			while ($email = zenario_incoming_email_manager::fetchEmail()) {
				
				if (!$path = zenario_incoming_email_manager::saveDecode($email)) {
					$output = array(adminPhrase('This email could not be read.'));
					$overallResult = 'error';
				
				} else {
					$output = array();
					$result = 
						exec('php '.
								escapeshellarg(CMS_ROOT. moduleDir('zenario_scheduled_task_manager', 'cron/run_every_minute.php')).
							' '. 
								'3'.
							' '.
								escapeshellarg($managerClassName).
							' '.
								escapeshellarg($serverTime).
							' '.
								escapeshellarg($jobId).
							' '.
								escapeshellarg($jobName).
							' '.
								escapeshellarg($moduleClassName).
							' '.
								escapeshellarg($staticMethod).
							' '.
								escapeshellarg($email).
							' '.
								escapeshellarg($path),
							$output);
				}
				
				switch ($result) {
					case '<!--action_taken-->':
						if ($overallResult != 'error') {
							$overallResult = '<!--action_taken-->';
						}
					case '<!--no_action_taken-->':
						zenario_incoming_email_manager::markEmailAsProcessed(false);
						break;
					
					default:
						$overallResult = 'error';
						zenario_incoming_email_manager::markEmailAsProcessed(true);
				}
				
				//Add a log entry
				zenario_incoming_email_manager::logResult(
					$email,
					$result, $output, $unlockWhenDone = false,
					$managerClassName,
					$serverTime, $jobId, $jobName,
					$logActions, $logInaction, $emailActions, $emailInaction,
					$emailAddressAction, $emailAddressInaction, $emailAddressError);
			}
			
			//Update the status of the job
			$output = array();
			zenario_scheduled_task_manager::logResult(
				$overallResult, $output, $unlockWhenDone = 'only',
				$managerClassName,
				$serverTime, $jobId, $jobName,
				$logActions, $logInaction, $emailActions, $emailInaction,
				$emailAddressAction, $emailAddressInaction, $emailAddressError);
		
		} catch (Exception $e) {
			$overallResult = 'error';
			$output = array($e->getMessage());
			zenario_scheduled_task_manager::logResult(
				$overallResult, $output, $unlockWhenDone = true,
				$managerClassName,
				$serverTime, $jobId, $jobName,
				$logActions, $logInaction, $emailActions, $emailInaction,
				$emailAddressAction, $emailAddressInaction, $emailAddressError);
		}
		
		zenario_incoming_email_manager::endConnection();
	}
	
	//Run an email job.
	public static function step3(
		$managerClassName,
		$serverTime, $jobId, $jobName, $moduleClassName, $staticMethod,
		$email, $path
	) {
		register_shutdown_function(array('zenario_incoming_email_manager', 'showErrorsOnShutDown'));
		
		if (!inc($moduleClassName)) {
			echo adminPhrase('This Module is not currently running.');
			exit;
		}
		
		if (!zenario_incoming_email_manager::loadDecode($email, $path)) {
			echo adminPhrase('This email could not be read.');
			exit;
		}
		
		if ($staticMethod) {
			$module = $moduleClassName;
		} else {
			$module = new $moduleClassName;
		}
		
		zenario_incoming_email_manager::$functionReturnValue = call_user_func(array($module, $jobName), $email);
		zenario_incoming_email_manager::$functionReturnedSuccessfully = true;
	}
	
	public static $functionReturnValue = false;
	public static $functionReturnedSuccessfully = false;
	
	public static function showErrorsOnShutDown() {
		if ($error = error_get_last()) {
			//An error; add the error message to the log
			echo adminPhrase('[[message]] in [[file]] on line [[line]]', $error);
		
		} elseif (!zenario_incoming_email_manager::$functionReturnedSuccessfully) {
			//An error; the message should already have been outputted so do nothing
		
		} elseif (zenario_incoming_email_manager::$functionReturnValue) {
			echo "\n<!--action_taken-->";
		
		} else {
			echo "\n<!--no_action_taken-->";
		}
	}
	
	
	
	//This function handles incoming emails that are piped to php
	//It checks the recipient's username, and checks to see if there is an email job assigned to that username.
	//If there is, it will call that job, and then log the result in a similar way to how Scheduled Tasks are logged.
	public static function emailHandler() {
		$jobId = getRow(
			ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. 'accounts',
			'job_id',
			array('script_recipient_username' => $_SERVER['USER'], 'script_enable' => 1));
		
		if ($jobId) {
			$job = getRow(
				'jobs',
				array(
					'job_name', 'module_class_name', 'static_method',
					'log_on_action', 'log_on_no_action', 'email_on_action', 'email_on_no_action',
					'email_address_on_action', 'email_address_on_no_action', 'email_address_on_error'),
				$jobId);
			
			if ($job && inc($job['module_class_name'])) {
				$serverTime = zenario_scheduled_task_manager::getServerTime();
				
				$email = 'php://stdin';
				if (!$path = zenario_incoming_email_manager::saveDecode($email)) {
					$output = array(adminPhrase('This email could not be read.'));
					$result = 'error';
				
				} else {
					$output = array();
					$result = 
						exec('php '.
								escapeshellarg(CMS_ROOT. moduleDir('zenario_scheduled_task_manager', 'cron/run_every_minute.php')).
							' '. 
								'3'.
							' '.
								escapeshellarg('zenario_incoming_email_manager').
							' '.
								escapeshellarg($serverTime).
							' '.
								escapeshellarg($jobId).
							' '.
								escapeshellarg($job['job_name']).
							' '.
								escapeshellarg($job['module_class_name']).
							' '.
								escapeshellarg($job['static_method']).
							' '.
								escapeshellarg($email).
							' '.
								escapeshellarg($path),
							$output);
				}
						
				zenario_incoming_email_manager::logResult(
					$email,
					$result, $output, $unlockWhenDone = false,
					'zenario_incoming_email_manager',
					$serverTime, $jobId, $job['job_name'],
					$job['log_on_action'], $job['log_on_no_action'], $job['email_on_action'], $job['email_on_no_action'], 
					$job['email_address_on_action'], $job['email_address_on_no_action'], $job['email_address_on_error']);
				
				return;
			}
		}
	}
	
	
	
	protected static function logResult(
		$email,
		$result, $output, $unlockWhenDone,
		$managerClassName,
		$serverTime, $jobId, $jobName,
		$logActions, $logInaction, $emailActions, $emailInaction,
		$emailAddressAction, $emailAddressInaction, $emailAddressError
	) {

		$logId =
			zenario_scheduled_task_manager::logResult(
				$result, $output, $unlockWhenDone,
				$managerClassName,
				$serverTime, $jobId, $jobName,
				$logActions, $logInaction, $emailActions, $emailInaction,
				$emailAddressAction, $emailAddressInaction, $emailAddressError);
		
		if ($logId) {
			$header = $addresses = false;
			zenario_incoming_email_manager::getMessageHeader($email, $header);
			zenario_incoming_email_manager::getMessageAddresses($email, $addresses);
			
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX. "account_logs SET
					job_id = ". (int) $jobId. ",
					log_id = ". (int) $logId. ",
					email_from = '". sqlEscape(arrayKey($addresses, 'from:', 0, 'address')). "',
					email_subject = '". sqlEscape(arrayKey($header, 'subject:')). "'";
			
			if (($date = arrayKey($header, 'date:'))
			 && ($date = @strtotime($date))) {
				$sql .= ",
					email_sent = FROM_UNIXTIME(". (int) $date. ")";
			}
			
			sqlQuery($sql);
		}
	}

	
	public function logEmail(
		&$subject, &$body,
		$serverTime, $jobName, $jobId,
		$status, &$logMessage
	) {
		$subject = 'Incoming Email Handler '. $jobName. ': '. $status. ' at '. primaryDomain();
		$body = 'Report from: '. primaryDomain(). "\n";
		$body .= 'Directory: '. CMS_ROOT. "\n";
		$body .= 'Database Name: '. DBNAME. "\n";
		$body .= 'Database Host: '. DBHOST. "\n";
		$body .= 'Incoming Email Handler: '. $jobName. "\n";
		$body .= 'Storekeeper Link: '. httpOrHttps(). httpHost(). SUBDIRECTORY. 'admin/organizer.php#zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks/refiners/zenario_incoming_email_manager__incoming_emails////'. $jobId. "\n";
		$body .= 'Run on: '. $serverTime. "\n";
		$body .= 'Status: '. $status. "\n\n";
		$body .= 'Message:'. "\n". $logMessage;
	}
	
	
}

