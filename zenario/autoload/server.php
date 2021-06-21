<?php
/*
 * Copyright (c) 2021, Tribal Limited
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



namespace ze;

class server {


	//Formerly "sendEmail()"
	public static function sendEmail(
		$subject, $body, $addressTo, &$addressToOverriddenBy,
		$nameTo = false, $addressFrom = false, $nameFrom = false, 
		$attachments = [], $attachmentFilenameMappings = [],
		$precedence = 'bulk', $isHTML = true, $exceptions = false,
		$addressReplyTo = false, $nameReplyTo = false, $warningEmailCode = false,
		$ccs = '', $bccs = '', $action = 'To', $ignoreDebugMode = false
	) {
	
		$debug = \ze::setting('debug_override_enable');
		if ($debug) {
			$sendToDebugAddressOrDontSentAtAll = \ze::setting('send_to_debug_address_or_dont_send_at_all');
			if ($sendToDebugAddressOrDontSentAtAll == 'dont_send_at_all' && $ignoreDebugMode == false) {
				return false;
			}
		}
		
		// If this is a warning email only send it as oftern as the site setting "warning_email_frequency" allows
		if ($warningEmailCode && \ze::setting('warning_email_frequency') && (\ze::setting('warning_email_frequency') != 'no_limit')) {
			// If no record is set create one
			if (!\ze\row::exists('last_sent_warning_emails', ['warning_code' => $warningEmailCode])) {
				\ze\row::insert('last_sent_warning_emails', ['timestamp' => \ze\date::now(), 'warning_code' => $warningEmailCode]);
			// If a record is found check when it was last sent
			} else {
				$lastSent = \ze\row::get('last_sent_warning_emails', 'timestamp', ['warning_code' => $warningEmailCode]);
				$lastSent = strtotime($lastSent);
				// If email was sent within the frequency time, return false
				if (strtotime('+ '.\ze::setting('warning_email_frequency'), $lastSent) > time()) {
					return false;
				}
				// Otherwise send email and update last sent time
				\ze\row::update('last_sent_warning_emails', ['timestamp' => \ze\date::now()], ['warning_code' => $warningEmailCode]);
			}
		}
	
	
		if ($addressFrom === false) {
			$addressFrom = \ze::setting('email_address_from');
		}
		if (!$nameFrom) {
			$nameFrom = \ze::setting('email_name_from');
		}
	
		if ($body === '' || $body === null || $body === false) {
			$body = ' ';
		}
	
		if (!$precedence) {
			$precedence = 'bulk';
		}
	
	
		$mail = new \PHPMailer\PHPMailer\PHPMailer($exceptions);
		$mail->CharSet = 'UTF-8';
		
		if (\ze::setting('base64_encode_emails')) {
			$mail->Encoding = 'base64';
			//Options for this are "8bit", "7bit", "binary", "base64", and "quoted-printable"
			//8bit is the default, and apparrently will auto-switch to quoted-printable if needed.
		}
		
	
		$mail->Subject = $subject;
		$mail->Body = $body;
	
		if ($addressFrom) {
			$mail->Sender = $addressFrom;
			$mail->From = $addressFrom;
		}
		if ($nameFrom) {
			$mail->FromName = $nameFrom;
		}
		if($addressReplyTo && $nameReplyTo) {
			$mail->AddReplyTo($addressReplyTo, $nameReplyTo);
		} else {
			$mail->AddReplyTo($mail->From, $mail->FromName);
		}
	
		if ($debug
		 && ($sendToDebugAddressOrDontSentAtAll == 'send_to_debug_email_address')
		 && ($overrideEmailAddress = \ze::setting('debug_override_email_address'))
		 && $ignoreDebugMode == false) {
		
			if ($isHTML) {
				$mail->Body .= '<br/><br/>';
			} else {
				$mail->Body .= "\n\n";
			}
		
			$mail->Body .= \ze\admin::phrase('Zenario debug mode enabled: original email "[[action]]" [[addressTo]]', ['action' => $action, 'addressTo' => $addressTo]);
			
			$mail->Subject .= ' | DEBUG MODE';
			
			$mail->AddAddress($overrideEmailAddress);
			$addressToOverriddenBy = $overrideEmailAddress;
	
		} elseif ($nameTo === false) {
			$mail->AddAddress($addressTo);
			$addressToOverriddenBy = '';
	
		} else {
			$mail->AddAddress($addressTo, $nameTo);
			$addressToOverriddenBy = '';
		}
	
		if ($ccs) {
			foreach (array_unique(\ze\ray::explodeAndTrim(str_replace(';', ',', $ccs))) as $emailAddress) {
				if ($debug) {
					\ze\server::sendEmail(
						$subject, $body, $emailAddress, $addressToOverriddenBy,
						$nameTo, $addressFrom, $nameFrom, 
						$attachments, $attachmentFilenameMappings,
						$precedence, $isHTML, $exceptions,
						$addressReplyTo, $nameReplyTo, $warningEmailCode,
						'', '', 'CC');
				} else {
					$mail->AddCC($emailAddress);
				}
			}
		}
		if ($bccs) {
			foreach (array_unique(\ze\ray::explodeAndTrim(str_replace(';', ',', $bccs))) as $emailAddress) {
				if ($debug) {
					\ze\server::sendEmail(
						$subject, $body, $emailAddress, $addressToOverriddenBy,
						$nameTo, $addressFrom, $nameFrom, 
						$attachments, $attachmentFilenameMappings,
						$precedence, $isHTML, $exceptions,
						$addressReplyTo, $nameReplyTo, $warningEmailCode,
						'', '', 'BCC');
				} else {
					$mail->AddBCC($emailAddress);
				}
			}
		}
	
		if ($isHTML) {
			$mail->IsHTML(true);
			$mail->AltBody = html_entity_decode(strip_tags($body), ENT_COMPAT, 'UTF-8');
		} else {
			$mail->IsHTML(false);
		}
	
		if (\ze::setting('smtp_specify_server')) {
			$mail->Mailer = 'smtp';
			$mail->isSMTP();
			$mail->Host = \ze::setting('smtp_host');
			$mail->Port = \ze::setting('smtp_port');
			$mail->SMTPSecure = \ze::setting('smtp_security');
			$mail->SMTPAutoTLS = false;
		
			if ($mail->SMTPAuth = (bool) \ze::setting('smtp_use_auth')) {
				$mail->Username = \ze::setting('smtp_username');
				$mail->Password = \ze::secretSetting('smtp_password');
			}		
			$mail->SMTPDebug = false;
		}
	
		$mail->AddCustomHeader('Precedence: '. $precedence);
	
		if (!empty($attachments)) {
			foreach ($attachments as $fieldName => $fileToAttach) {
				if (file_exists($fileToAttach)){
					if (!empty($attachmentFilenameMappings[$fieldName])) {
						$mail->AddAttachment($fileToAttach, $attachmentFilenameMappings[$fieldName]);	
					} else {
						$mail->AddAttachment($fileToAttach);	
					}
				}
			}
		}
	
		return $mail->Send();
	}
	
	
	
	
	

	//Check whether we are allowed to call exec()
	//Formerly "execEnabled()"
	public static function execEnabled() {
	
		if (is_null(\ze::$execEnabled)) {
			\ze::$execEnabled = \ze\server::checkFunctionEnabled('exec');
		}
	
		return \ze::$execEnabled;
	}

	//Formerly "checkFunctionEnabled()"
	public static function checkFunctionEnabled($name) {
		try {
			return @is_callable($name)
				&& !(($disable_functions = ini_get('disable_functions')) && (preg_match('/\b'. $name. '\b/i', $disable_functions) !== 0));
		} catch (\Exception $e) {
			return false;
		}
	}
	




	//Formerly "windowsServer()"
	public static function isWindows() {
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}


	//Create a symlink if this is a UN*X server, otherwise just copy the file on a Windows server
	public static function symlinkOrCopy($pathFrom, $pathTo, $chmod = null) {
		
		if (!file_exists($pathFrom)) {
			return false;
		}
		
		if (\ze\server::isWindows()) {
			copy($pathFrom, $pathTo);
		} else {
			symlink($pathFrom, $pathTo);
		}
		
		if ($chmod !== null) {
			\ze\cache::chmod($pathTo, $chmod);
		}
		
		return true;
	}






	//Formerly "programPathForExec()"
	public static function programPathForExec($path, $program, $checkExecutable = false) {
	
		if ($checkExecutable) {
			$path = \ze\server::programPathForExec($path, $program, false);
			if ($path && is_executable($path)) {
				return $path;
			}
	
		} else {
			if (!\ze\server::isWindows() && \ze\server::execEnabled()) {
				switch ($path) {
					case 'PATH':
						return $program;
					case '/usr/bin/':
						return '/usr/bin/'. $program;
					case '/usr/local/bin/':
						return '/usr/local/bin/'. $program;
				}
			}
		}
	
		return false;
	}
	
	//Wrapper function for the phpMQTT library.
	public static function mqttConnect($host, $port, $username, $password, $clientId = null) {
		require_once CMS_ROOT. 'zenario/libs/manually_maintained/mit/phpmqtt/phpMQTT.php';
		
		$mqtt = new \Bluerhinos\phpMQTT($host, $port, $clientId ?? $username);
		if ($mqtt->connect(true, NULL, $username, $password)) {
			return $mqtt;
		} else {
			return false;
		}
	}
	
	
	//Call ClamAV to scan a file for a virus, if it is installed
		//Returns true if a file is safe
		//Returns false if a virus was found
		//Returns null if ClamAV wasn't installed or enabled in the site settings, or the ClamAV daemon was not running
	public static function antiVirusScan($filepath, $autoDelete = false) {
		
		if ($programPath = \ze\server::programPathForExec(\ze::setting('clamscan_tool_path'), 'clamdscan')) {
			
			//If this file is in the temp directory and has its permissions set to 0600, the virus scanner won't
			//be able to read it. Temporarily set the permissions to 0644
			$changePermsForScan =
				substr($filepath, 0, 5) == '/tmp/'
			 && (fileperms($filepath) & 0777) === 0600;
			
			if ($changePermsForScan) {
				@chmod($filepath, 0644);
			}
			
				//Run the virus scan
				$output = [];
				$returnValue = 2;
				exec(escapeshellarg($programPath). ' --quiet '. escapeshellarg($filepath), $output, $returnValue);
			
			if ($changePermsForScan) {
				@chmod($filepath, 0600);
			}
			
			//A return value of 0 means no virus
			if ($returnValue == 0) {
				return true;
			
			//A return value of 1 means a virus was detected!
			} elseif ($returnValue == 1) {
				if ($autoDelete) {
					@unlink($filepath);
				}
				return false;
			
			//A return value of 2 means clamd was not running, or another error occurred
			}
		}
		
		return null;
	}
}