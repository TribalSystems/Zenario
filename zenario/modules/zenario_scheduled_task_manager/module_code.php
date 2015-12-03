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

class zenario_scheduled_task_manager extends module_base_class {





	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	private static $frequencyOptions = array(
		'1m' => array('label' => 'Every minute', 'ord' => 0.5),
		'5m' => array('label' => 'Every 5 minutes', 'ord' => 1),
		'10m' => array('label' => 'Every 10 minutes', 'ord' => 2),
		'15m' => array('label' => 'Every 15 minutes', 'ord' => 3),
		'20m' => array('label' => 'Every 20 minutes', 'ord' => 4),
		'30m' => array('label' => 'Every 30 minutes', 'ord' => 5),
		'1h' => array('label' => 'Every hour', 'ord' => 6),
		'2h' => array('label' => 'Every 2 hours', 'ord' => 7),
		'3h' => array('label' => 'Every 3 hours', 'ord' => 8),
		'4h' => array('label' => 'Every 4 hours', 'ord' => 9),
		'6h' => array('label' => 'Every 6 hours', 'ord' => 10),
		'12h' => array('label' => 'Every 12 hours', 'ord' => 11),
		'24h' => array('label' => 'Every day', 'ord' => 12));
	
	private static $startAtHoursOptions = array(
		'0' => '00',
		'1' => '01',
		'2' => '02',
		'3' => '03',
		'4' => '04',
		'5' => '05',
		'6' => '06',
		'7' => '07',
		'8' => '08',
		'9' => '09',
		'10' => '10',
		'11' => '11',
		'12' => '12',
		'13' => '13',
		'14' => '14',
		'15' => '15',
		'16' => '16',
		'17' => '17',
		'18' => '18',
		'19' => '19',
		'20' => '20',
		'21' => '21',
		'22' => '22',
		'23' => '23');
	
	private static $startAtMinutesOptions = array(
		'0' => '00',
		'5' => '05',
		'10' => '10',
		'15' => '15',
		'20' => '20',
		'25' => '25',
		'30' => '30',
		'35' => '35',
		'40' => '40',
		'45' => '45',
		'50' => '50',
		'55' => '55');
	
	protected static $firstNOptions = array(
		0 => 'No Filter',
		1 => 'First Day of the Month only',
		7 => 'First 7 days of the Month only',
		-7 => 'Last 7 days of the Month only',
		-1 => 'Last Day of the Month only');
	
	protected static $lastRunStatuses = array(
		'never_run' => 'Never Run',
		'rerun_scheduled' => 'Rerun Scheduled',
		'in_progress' => 'In Progress',
		'action_taken' => 'Action Taken',
		'no_action_taken' => 'No Action Taken',
		'error' => 'Error');
	
	
	
	public static function getServerTime() {
		if (windowsServer() || !execEnabled()) {
			return false;
		}
		
		return trim(exec('date +"%Y-%m-%d %H:%M:%S"'));
	}
	
	public static function step1($managerClassName = 'zenario_scheduled_task_manager', $runSpecificJob = false, $args = '') {
	
		$serverTime = zenario_scheduled_task_manager::getServerTime();
		$times = explode(' ', exec('date +"%Y %m %u %H %M %d "'));
		
		foreach ($times as &$time) {
			$time = (int) $time;
		}
		
		$offtime = false;
		if ($times[4] == 59) {
			$times[4] = 12;
		} elseif ($times[4] % 5) {
			$offtime = true;
		} else {
			$times[4] = (int) ($times[4] / 5);
		}

		
		$sql = "
			SELECT
				id,
				job_name,
				module_class_name,
				static_method,
				log_on_action,
				log_on_no_action,
				email_on_action,
				email_on_no_action,
				email_address_on_action,
				email_address_on_no_action,
				email_address_on_error,
				status,
				last_run_started,
				last_run_started <= DATE_SUB(NOW(), INTERVAL 4 HOUR) AS stuck
			FROM ". DB_NAME_PREFIX. "jobs
			WHERE manager_class_name = '". sqlEscape($managerClassName). "'
			  AND `enabled` = 1";
		
		if ($runSpecificJob) {
			$sql .= "
			  AND job_name = '". sqlEscape($runSpecificJob). "'";
		
		} else {
			$sql .= "
			  AND (
				status = 'rerun_scheduled'
				OR run_every_minute = 1";
		
			if (!$offtime) {
				$sql .= "
				OR (	0+months & ". pow(2, (int) $times[1] - 1). "
					AND 0+days & ". pow(2, (int) $times[2] - 1). "
					AND 0+hours & ". pow(2, (int) $times[3]). "
					AND 0+minutes & ". pow(2, (int) $times[4]). "
					AND (first_n_days_of_month = 0
					  OR (first_n_days_of_month > 0 AND first_n_days_of_month >= ". (int) $times[5]. ")
					  OR (first_n_days_of_month < 0 AND first_n_days_of_month <= ". (
					  	(int) $times[5] - 1 - cal_days_in_month(CAL_GREGORIAN, (int) $times[1], (int) $times[0])
					  ). ")
					)
				)";
			}
		
			$sql .= "
			  )";
		}
		
		$jobsRun = false;
		$result = sqlQuery($sql);
		while($job = sqlFetchAssoc($result)) {
			$jobsRun = true;
			
			//Jobs that are still "in progress" should not have a second copy run.
			if ($job['status'] == 'in_progress') {
				//However if we see that they've been in progress for more than four hours then send an email
				if ($job['stuck']) {
					self::sendLogEmails(
						$managerClassName, $serverTime,
						$job['job_name'], $job['id'], 'error',
						'This has been "in progress" for over four hours and may be stuck!',
						$job['email_address_on_error']);
				}
				continue;
			}
			
			exec('php '.
						escapeshellarg(CMS_ROOT. moduleDir('zenario_scheduled_task_manager', 'cron/run_every_minute.php')).
					' '. 
						'2'.
					' '.
						escapeshellarg($managerClassName).
					' '.
						escapeshellarg($serverTime).
					' '.
						escapeshellarg($job['id']).
					' '.
						escapeshellarg($job['job_name']).
					' '.
						escapeshellarg($job['module_class_name']).
					' '.
						escapeshellarg((int) $job['static_method']).
					' '.
						escapeshellarg((int) $job['log_on_action']).
					' '.
						escapeshellarg((int) $job['log_on_no_action']).
					' '.
						escapeshellarg((int) $job['email_on_action']).
					' '.
						escapeshellarg((int) $job['email_on_no_action']).
					' '.
						escapeshellarg($job['email_address_on_action']).
					' '.
						escapeshellarg($job['email_address_on_no_action']).
					' '.
						escapeshellarg($job['email_address_on_error']).
					' '.
						escapeshellarg(serialize($args)).
					' &');
		}
		
		setSetting('jobs_last_run', time(), $updateDB = true, $clearCache = false);
		
		return $jobsRun;
	}
	
	public static function step2(
		$managerClassName,
		$serverTime, $jobId, $jobName, $moduleClassName, $staticMethod,
		$logActions, $logInaction, $emailActions, $emailInaction,
		$emailAddressAction, $emailAddressInaction, $emailAddressError,
		$serializedArgs = ''
	) {
		
		//Lock the job, set some fields
		updateRow('jobs', array('last_run_started' => $serverTime, 'status' => 'in_progress'), $jobId);
		
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
					escapeshellarg($serializedArgs),
				$output);
		
		zenario_scheduled_task_manager::logResult(
			$result, $output, true,
			$managerClassName,
			$serverTime, $jobId, $jobName,
			$logActions, $logInaction, $emailActions, $emailInaction,
			$emailAddressAction, $emailAddressInaction, $emailAddressError);
	}
		
	public static function logResult(
		$result, &$output, $unlockWhenDone,
		$managerClassName,
		$serverTime, $jobId, $jobName,
		$logActions, $logInaction, $emailActions, $emailInaction,
		$emailAddressAction, $emailAddressInaction, $emailAddressError
	) {
		$logId = false;
		$job = array();
		$log = array();
		$log['note'] = '';
		$log['job_id'] = $jobId;
		$log['started'] = $serverTime;
		$log['finished'] = $job['last_run_finished'] = zenario_scheduled_task_manager::getServerTime();
		
		if ($result == '<!--action_taken-->') {
			$emailList = $emailAddressAction;
			$job['last_action'] = $serverTime;
			$job['last_successful_run'] = $serverTime;
			$job['status'] = $log['status'] = 'action_taken';
			
		} elseif ($result == '<!--no_action_taken-->') {
			$emailList = $emailAddressInaction;
			$job['last_successful_run'] = $serverTime;
			$job['status'] = $log['status'] = 'no_action_taken';
		
		} else {
			$emailList = $emailAddressError;
			$job['status'] = $log['status'] = 'error';
		}
		
		if (!empty($output)) {
			foreach ($output as $line) {
				if ($line != '<!--action_taken-->' && $line != '<!--no_action_taken-->') {
					$log['note'] .= ($log['note']? "\n" : ''). str_replace(array('<br>', '<br/>', '<br />'), "\n", $line);
				}
			}
		}
		
		//Unlock the job, set some more fields
		if ($unlockWhenDone) {
			updateRow('jobs', $job, array('id' => $jobId));
		} else {
			updateRow('jobs', $job, array('id' => $jobId, 'status' => array('!' => 'in_progress')));
		}
		
		//Have an option to only update the job record, and not add a log.
		if ($unlockWhenDone === 'only') {
			return;
		}
		
		//Add a log entry, if needed
		if ($log['status'] == 'error'
		 || ($log['status'] == 'action_taken' && $logActions)
		 || ($log['status'] == 'no_action_taken' && $logInaction)) {
			$logId = insertRow('job_logs', $log);
		}
		
		//Email the log entry, if needed
		if ($log['status'] == 'error'
		 || ($log['status'] == 'action_taken' && $emailActions)
		 || ($log['status'] == 'no_action_taken' && $emailInaction)) {
		 	
		 	self::sendLogEmails(
		 		$managerClassName, $serverTime,
		 		$jobName, $jobId, $log['status'], $log['note'],
		 		$emailList);
		}
		
		return $logId;
	}
	
	protected static function sendLogEmails(
		$managerClassName, $serverTime, $jobName, $jobId, $status, $note, $emailList
	) {
		
		inc($managerClassName);
		$class = new $managerClassName;
		
		$headers = $subject = $body = '';
		$class->logEmail(
			$subject, $body,
			$serverTime, $jobName, $jobId,
			arrayKey(zenario_scheduled_task_manager::$lastRunStatuses, $status), $note);
		
		$emails = arrayValuesToKeys(explodeAndTrim($emailList));
		
		if ($status == 'error' && defined('EMAIL_ADDRESS_GLOBAL_SUPPORT')) {
			$emails[EMAIL_ADDRESS_GLOBAL_SUPPORT] = true;
		}
		
		foreach ($emails as $email => $dummy) {
			$addressToOverriddenBy = false;
			sendEmail(
				$subject, $body,
				$email,
				$addressToOverriddenBy,
				$nameTo = false,
				$addressFrom = false,
				$nameFrom = false,
				false, false, false,
				$isHTML = false);
		}
	}
	
	public function logEmail(
		&$subject, &$body,
		$serverTime, $jobName, $jobId,
		$status, &$logMessage
	) {
		$subject = 'Scheduled Task '. $jobName. ': '. $status. ' at '. primaryDomain();
		$body = 'Report from: '. primaryDomain(). "\n";
		$body .= 'Directory: '. CMS_ROOT. "\n";
		$body .= 'Database Name: '. DBNAME. "\n";
		$body .= 'Database Host: '. DBHOST. "\n";
		$body .= 'Scheduled Task: '. $jobName. "\n";
		$body .= 'Organizer Link: '. httpOrHttps(). httpHost(). SUBDIRECTORY. 'admin/organizer.php#zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks//'. $jobId. "\n";
		$body .= 'Run on: '. $serverTime. "\n";
		$body .= 'Status: '. $status. "\n\n";
		$body .= 'Message:'. "\n". $logMessage;
	}
	
	public static function step3(
		$managerClassName,
		$serverTime, $jobId, $jobName, $moduleClassName, $staticMethod,
		$serializedArgs = ''
	) {
		if (!inc($moduleClassName)) {
			echo adminPhrase('This Module is not currently running.');
			exit;
		}
		
		if ($staticMethod) {
			$module = $moduleClassName;
		} else {
			$module = new $moduleClassName;
		}
		
		$returnValue = call_user_func(array($module, $jobName), $serverTime);
		
		if ($returnValue) {
			echo "\n<!--action_taken-->";
		} else {
			echo "\n<!--no_action_taken-->";
		}
	}
	
	
	
	public static function checkScheduledTaskRunning($jobName = false, $checkPulse = false, $managerClassName = 'zenario_scheduled_task_manager') {
		
		if (!setting('jobs_enabled')) {
			return false;
		}
		
		$key = array('manager_class_name' => $managerClassName, 'enabled' => 1);
		
		if ($jobName) {
			$key['job_name'] = $jobName;
		}
		
		if (!checkRowExists('jobs', $key)) {
			return false;
		}
		
		if ($checkPulse) {
			if ((!$lastRun = setting('jobs_last_run'))
			 || ($lastRun + 600 < time())) {
				return false;
			}
		}
		
		return true;
	}
	
}
