<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

class zenario_scheduled_task_manager extends ze\moduleBaseClass {





	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
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
	
	
	private static $frequencyOptions = [
		'1m' => ['label' => 'Every minute', 'ord' => 0.5],
		'5m' => ['label' => 'Every 5 minutes', 'ord' => 1],
		'10m' => ['label' => 'Every 10 minutes', 'ord' => 2],
		'15m' => ['label' => 'Every 15 minutes', 'ord' => 3],
		'20m' => ['label' => 'Every 20 minutes', 'ord' => 4],
		'30m' => ['label' => 'Every 30 minutes', 'ord' => 5],
		'1h' => ['label' => 'Every hour', 'ord' => 6],
		'2h' => ['label' => 'Every 2 hours', 'ord' => 7],
		'3h' => ['label' => 'Every 3 hours', 'ord' => 8],
		'4h' => ['label' => 'Every 4 hours', 'ord' => 9],
		'6h' => ['label' => 'Every 6 hours', 'ord' => 10],
		'12h' => ['label' => 'Every 12 hours', 'ord' => 11],
		'24h' => ['label' => 'Every day', 'ord' => 12]];
	
	private static $startAtHoursOptions = [
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
		'23' => '23'];
	
	private static $startAtMinutesOptions = [
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
		'55' => '55'];
	
	protected static $firstNOptions = [
		0 => 'Every day of the month',
		1 => 'First day of the month',
		7 => 'First 7 days of the month',
		-7 => 'Last 7 days of the month',
		-1 => 'Last day of the month'];
	
	protected static $lastRunStatuses = [
		'action_taken' => 'Action taken',
		'crashed' => 'Crashed',
		'error' => 'Error',
		'in_progress' => 'In progress',
		'never_run' => 'Never run',
		'not_running' => 'Not running',
		'no_action_taken' => 'No action taken',
		'rerun_scheduled' => 'Rerun scheduled',
		'restarted' => 'Restarted',
		'restarting' => 'Restarting',
		'running' => 'Running',
		'starting' => 'Starting',
		'stopping' => 'Stopping'
	];
	
	
	
	public static function getServerTime() {
		if (ze\server::isWindows() || !ze\server::execEnabled()) {
			return false;
		}
		
		return trim(exec('date +"%Y-%m-%d %H:%M:%S"'));
	}
	
	//Get a code that should be unique to this site on the server.
	protected static function getJobCode() {
		//I would just use the site id, but sometimes people run multiple copies of a site on the same server,
		//and also we want to keep the site id a little hidden, so add in the CMS root directory as well
		//when generating the code.
		return 'zenario_job_code_'. base_convert(md5(CMS_ROOT. ze::setting('site_id')), 16, 36). '_';
	}
	
	//Look for any processes that are still running by checking the process list for their job codes and job ids
	public static function getRunningJobs() {
		
		//Look for any processes that match this site's code
		$siteCode = static::getJobCode();
		$runningProcesses = [];
		if (PHP_OS == 'Darwin') {
			exec($exec = 'ps -ax | grep '. escapeshellarg($siteCode), $runningProcesses);
		} else {
			exec($exec = 'ps aux | grep '. escapeshellarg($siteCode), $runningProcesses);
		}
		
		//Parse the list of processes, and extract the job ids
		//Note I'm using explode(, , 2) for this rather than preg_match as it's faster
		$runningJobIds = [];
		foreach ($runningProcesses as $process) {
			$process = explode($siteCode, $process, 2);
			if (!empty($process[1])) {
				$process = explode('_', $process[1], 3);
				if (!empty($process[0]) && is_numeric($process[0])) {
					
					//Make this an array of arrays, so we can note down multiple processes for
					//each job, just in case there end up being multiple processes
					if (!isset($runningJobIds[$process[0]])) {
						$runningJobIds[$process[0]] = [];
					}
					
					$runningJobIds[$process[0]][] = (int) $process[1];
					//Note $process[0] is the id of the job from the jobs table,
					//and $process[1] is when it was started.
				}
			}
		}
		
		return $runningJobIds;
	}
	
	//Restart every single background task that's currently running
	public static function restartAllBackgroundTasks() {
		static::checkBackgroundTasks(null, true);
	}
	
	//Go through all of the background tasks, starting/stopping/restarting them as needed
	public static function checkBackgroundTasks($runningJobIds = null, $forceRestart = false) {
		
		if (is_null($runningJobIds)) {
			$runningJobIds = static::getRunningJobs();
		}
		$siteCode = static::getJobCode();
		$timeInSeconds = time();
		
		
		
		//Check on all of the background tasks
		$sql = "
			SELECT
				id,
				job_name,
				module_class_name,
				script_path,
				script_restart_time,
				`enabled`,
				`paused`
			FROM ". DB_PREFIX. "jobs
			WHERE manager_class_name = 'zenario_scheduled_task_manager'
			  AND job_type = 'background'";
	
		$result = ze\sql::select($sql);
		while ($job = ze\sql::fetchAssoc($result)) {
			
			//Work out how many processes are currently running for this script.
			//(Normally this is just one but I'm writing the code to check for multiple processes
			// and to tidy them up if needed.)
			$runningThreads = [];
			$threadsToStop = [];
			
			if (isset($runningJobIds[$job['id']])) {
				foreach ($runningJobIds[$job['id']] as $startTime) {
					
					//For each running script, check if there's a stop flag already set
					$stopFlag = CMS_ROOT. 'cache/stop_flags/'. $siteCode. $job['id']. '_'. $startTime. '_';
					if (file_exists($stopFlag)) {
						//If so, there's nothing to do other than wait for the script to see it's stop flag
						//and remove itself.
					
						//If there's not already a stop flag, check if we should set one.
					} elseif ($forceRestart) {
						//If the force restart flag is set, we'll want to stop all threads that are running.
						$threadsToStop[] = $stopFlag;
					
					} elseif (!$job['enabled'] || $job['paused']) {
						//Stop anything that's not supposed to be enabled
						$threadsToStop[] = $stopFlag;
					
					} elseif ($startTime < $timeInSeconds - 3600) {
						//Restart anything that's older than one hour
						$threadsToStop[] = $stopFlag;
					
					} else
					if ($job['script_restart_time']
					 && $job['script_restart_time'] > $startTime) {
						//Check if the restart time flag is set after this script started,
						//which would mean a restart was requested for this script.
						$threadsToStop[] = $stopFlag;
					
					//Otherwise just note down that this process is running.
					} else {
						$runningThreads[] = $stopFlag;
					}
				}
			}
			
			//Catch the case if multiple processes are running
			//(I'm not sure if this is technically possible but I'm writing code to handle it just in case!)
			if (count($runningThreads) > 1) {
				//Handle this case by assuming all of the running threads are in error, and telling them to stop.
				$threadsToStop = array_merge($threadsToStop, $runningThreads);
				$runningThreads = [];
			}
			
			//If a process is supposed to be stopped, set the stop flag to tell them to stop.
			//Note if they've already got a stop flag then there's nothing to do.
			foreach ($threadsToStop as $stopFlag) {
				touch($stopFlag);
				@chmod($stopFlag, 0666);
			}
			
			//Start a process for this script if needed
			if ($job['enabled'] && !$job['paused'] && empty($runningThreads)) {
				//Wait 0.1 seconds between running each task, to space things out a little further
				$r = 100000;
				usleep($r);
				
				$script = '';
				foreach (explode(' ', $job['script_path']) as $i => $arg) {
					if ($i == 0) {
						$script .= escapeshellarg(CMS_ROOT. $arg);
					} else {
						$script .= ' '. escapeshellarg($arg);
					}
				}
				
				exec('php '.
							$script.
						' '.
							//Add the site code, the job id, and the current time to the command, just so we can track them later
							escapeshellarg($siteCode. $job['id']. '_'. $timeInSeconds. '_').
						' > /dev/null &');
			}
		}
	}
	
	public static function step1($managerClassName = 'zenario_scheduled_task_manager') {
		
		$timeInSeconds = time();
		$serverTime = zenario_scheduled_task_manager::getServerTime();
		$times = explode(' ', exec('date +"%Y %m %u %H %M %d "'));
		
		foreach ($times as &$time) {
			$time = (int) $time;
		}
		
		$offtime = false;
		$warnAboutStuckTasksEvery15Mins = false;
		if ($times[4] == 59) {
			$times[4] = 12;
		} elseif ($times[4] % 5) {
			$offtime = true;
		} else {
			if ($times[4] % 15) {
				$warnAboutStuckTasksEvery15Mins = true;
			}
			$times[4] = (int) ($times[4] / 5);
		}
		
		
		$siteCode = static::getJobCode();
		$runningJobIds = static::getRunningJobs();
		
		
		
		//Check on all of the background tasks
		if ($managerClassName == 'zenario_scheduled_task_manager') {
			static::checkBackgroundTasks($runningJobIds);
		}
		
		
		//Go through all of the scheduled tasks that should be run this minute
		$sql = "
			SELECT
				id,
				job_name,
				module_class_name,
				static_method,
				log_on_action,
				log_on_no_action,
				email_on_action,
				email_address_on_action,
				email_address_on_error,
				status,
				last_run_started,
				last_run_started IS NOT NULL AND last_run_started <= DATE_SUB(NOW(), INTERVAL 4 HOUR) AS stuck
			FROM ". DB_PREFIX. "jobs
			WHERE manager_class_name = '". ze\escape::asciiInSQL($managerClassName). "'
			  AND job_type = 'scheduled'
			  AND `enabled` = 1
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
		
		$jobsRun = false;
		$result = ze\sql::select($sql);
		while ($job = ze\sql::fetchAssoc($result)) {
			$jobsRun = true;
			
			//Check if a job is still running
			if (isset($runningJobIds[$job['id']])) {
				//If we see that they've been in progress for more than four hours then send an email
				if ($warnAboutStuckTasksEvery15Mins && $job['stuck']) {
					self::sendLogEmails(
						$managerClassName, $serverTime,
						$job['job_name'], $job['id'], 'error',
						'This has been "in progress" for over four hours and may be stuck!',
						$job['email_address_on_error']);
				}
				
				//Jobs that are still running should not have a second copy run.
				continue;
			}
			
			//Wait 0.1 seconds between running each task, to space things out a little further
			$r = 100000;
			usleep($r);
			
			exec('php '.
						escapeshellarg(CMS_ROOT. ze::moduleDir('zenario_scheduled_task_manager', 'cron/run_every_minute.php')).
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
						escapeshellarg($job['email_address_on_action']).
					' '.
						escapeshellarg($job['email_address_on_error']).
					' '.
						//Add the site code, the job id, and the current time to the command, just so we can track them later
						escapeshellarg($siteCode. $job['id']. '_'. $timeInSeconds. '_').
					' > /dev/null &');
		}
		
		ze\site::setSetting('jobs_last_run', time(), $updateDB = true, $encrypt = false, $clearCache = false);
		
		return $jobsRun;
	}
	
	public static function step2(
		$managerClassName,
		$serverTime, $jobId, $jobName, $moduleClassName, $staticMethod,
		$logActions, $logInaction, $emailActions,
		$emailAddressAction, $emailAddressError
	) {
		
		//Lock the job, set some fields
		ze\row::update('jobs', ['last_run_started' => $serverTime, 'status' => 'in_progress'], $jobId);
		
		$output = [];
		$result = 
			exec('php '.
					escapeshellarg(CMS_ROOT. ze::moduleDir('zenario_scheduled_task_manager', 'cron/run_every_minute.php')).
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
					escapeshellarg($staticMethod),
				$output);
		
		zenario_scheduled_task_manager::logResult(
			$result, $output, true,
			$managerClassName,
			$serverTime, $jobId, $jobName,
			$logActions, $logInaction, $emailActions,
			$emailAddressAction, $emailAddressError);
	}
		
	public static function logResult(
		$result, &$output, $unlockWhenDone,
		$managerClassName,
		$serverTime, $jobId, $jobName,
		$logActions, $logInaction, $emailActions,
		$emailAddressAction, $emailAddressError,
		$dontSendLogEmail = false
	) {
		$logId = false;
		$job = [];
		$log = [];
		$log['note'] = '';
		$log['job_id'] = $jobId;
		$log['started'] = $serverTime;
		$log['finished'] = $job['last_run_finished'] = zenario_scheduled_task_manager::getServerTime();
		
		if ($result == '<!--action_taken-->') {
			$job['last_action'] = $serverTime;
			$job['last_successful_run'] = $serverTime;
			$job['status'] = $log['status'] = 'action_taken';
			
		} elseif ($result == '<!--no_action_taken-->') {
			$job['last_successful_run'] = $serverTime;
			$job['status'] = $log['status'] = 'no_action_taken';
		
		} else {
			$job['status'] = $log['status'] = 'error';
		}
		
		//Unlock the job, set some more fields
		if ($unlockWhenDone) {
			ze\row::update('jobs', $job, ['id' => $jobId]);
		} else {
			ze\row::update('jobs', $job, ['id' => $jobId, 'status' => ['!' => 'in_progress']]);
		}
		
		//Have an option to only update the job record, and not add a log.
		if ($unlockWhenDone === 'only') {
			return;
		}
		
		if (!empty($output)) {
			foreach ($output as $line) {
				if ($line != '<!--action_taken-->' && $line != '<!--no_action_taken-->') {
					$log['note'] .= ($log['note']? "\n" : ''). str_replace(['<br>', '<br/>', '<br />'], "\n", $line);
				}
			}
		}
		
		//Add a log entry, if needed
		if ($log['status'] == 'error'
		 || ($log['status'] == 'action_taken' && $logActions)
		 || ($log['status'] == 'no_action_taken' && $logInaction)) {
			
			self::clearOldData();
			$logId = ze\row::insert('job_logs', $log);
		}
		
		//Email the log entry, if needed
		if (!$dontSendLogEmail
			&& ($log['status'] == 'error'
		 		|| ($log['status'] == 'action_taken' && $emailActions)
			)
		) {
			$emailList =  self::getLogEmailList($result, $emailAddressAction, $emailAddressError);
		 	
		 	self::sendLogEmails(
		 		$managerClassName, $serverTime,
		 		$jobName, $jobId, $log['status'], $log['note'],
		 		$emailList);
		}
		
		return $logId;
	}
	
	
	public static function clearOldData($logResult = false) {
		$days = ze::setting('period_to_delete_job_logs');
		if ($days && is_numeric($days)) {
			$date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d'))));
			$sql = " 
				DELETE FROM ". DB_PREFIX. "job_logs
				WHERE started < '".ze\escape::sql($date)."'";
			ze\sql::update($sql);
			
			$deletedJobLogs = ze\sql::affectedRows();
			
			if ($logResult) {
				if ($deletedJobLogs == 0) {
					echo ze\admin::phrase('Deleting job logs: no action taken.');
				} elseif ($deletedJobLogs > 0) {
					echo ze\admin::nPhrase(
						'Deleted 1 job log.',
						'Deleted [[count]] job logs.',
						$deletedJobLogs,
						['count' => $deletedJobLogs]
					);
				}
				
				echo "\n";
			}
			
			return $deletedJobLogs;
		}
		return false;
	}
	
	
	public static function getTaskStatus($result) {
		if ($result == '<!--action_taken-->') {
			return 'action_taken';
		} elseif ($result == '<!--no_action_taken-->') {
			return 'no_action_taken';
		} else {
			return 'error';
		}
	}
	
	public static function getLogEmailList($result, $emailAddressAction, $emailAddressError) {
		if ($result == '<!--action_taken-->') {
			return $emailAddressAction;
		} else {
			return $emailAddressError;
		}
	}
	
	public static function sendLogEmails(
		$managerClassName, $serverTime, $jobName, $jobId, $status, $note, $emailList
	) {
		
		ze\module::inc($managerClassName);
		$class = new $managerClassName;
		
		$headers = $subject = $body = '';
		$class->logEmail(
			$subject, $body,
			$serverTime, $jobName, $jobId,
			ze\ray::value(zenario_scheduled_task_manager::$lastRunStatuses, $status), $note);
		
		$emails = ze\ray::valuesToKeys(ze\ray::explodeAndTrim($emailList));
		
		//For errors, if error emails are enabled, also include one to the support address
		if ($status == 'error' && ze\db::errorEmailsEnabled()) {
			$emails[EMAIL_ADDRESS_GLOBAL_SUPPORT] = true;
		}
		
		foreach ($emails as $email => $dummy) {
			\ze\server::sendEmailSimple($subject, $body, $isHTML = false, $ignoreDebugMode = true, $email);
				//Scheduled task logs should always be sent to the intended recipient,
				//even if debug mode is on.
		}
	}
	
	public function logEmail(
		&$subject, &$body,
		$serverTime, $jobName, $jobId,
		$status, &$logMessage
	) {
		$subject = 'Scheduled Task '. $jobName. ': '. $status. ' at '. ze\link::primaryDomain();
		$body = 'Report from: '. ze\link::primaryDomain(). "\n";
		$body .= 'Directory: '. CMS_ROOT. "\n";
		$body .= 'Database Name: '. DBNAME. "\n";
		$body .= 'Database Host: '. DBHOST. "\n";
		$body .= 'Scheduled Task: '. $jobName. "\n";
		$body .= 'Organizer Link: '. ze\link::protocol(). ze\link::host(). SUBDIRECTORY. 'organizer.php#zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks//'. $jobId. "\n";
		$body .= 'Run on: '. $serverTime. "\n";
		$body .= 'Status: '. $status. "\n\n";
		$body .= 'Message:'. "\n". $logMessage;
	}
	
	public static function step3(
		$managerClassName,
		$serverTime, $jobId, $jobName, $moduleClassName, $staticMethod
	) {
		if (!ze\module::inc($moduleClassName)) {
			echo ze\admin::phrase('This Module is not currently running.');
			exit;
		}
		
		if ($staticMethod) {
			$module = $moduleClassName;
		} else {
			$module = new $moduleClassName;
		}
		
		$returnValue = call_user_func([$module, $jobName], $serverTime);
		
		if ($returnValue) {
			echo "\n<!--action_taken-->";
		} else {
			echo "\n<!--no_action_taken-->";
		}
	}
	
	//This function can be used to obtain the arrays that the stopStartOrRestartThreads() function needs as an input
	public static function getThreadsList($inUse, $maxPossible = 16) {
		//Given how many threads a process is supposed to use, return a list of which of the total maximum threads
		//would be active and which would be inactive
		$allThreads = [];
		$pickedThreads = [];
		$unpickedThreads = [];
	
		for ($i = 1; $i <= $maxPossible; ++$i) {
			if ($i <= $inUse) {
				$pickedThreads[] = $i;
			} else {
				$unpickedThreads[] = $i;
			}
			$allThreads[] = $i;
		}
		
		return [
			$allThreads,
			$pickedThreads,
			$unpickedThreads
		];
	}
	
	
	//Stop, start or restart some of the processes/threads used by assetwolf
	//(Note that this just queues the change; you need to call zenario_scheduled_task_manager::checkBackgroundTasks()
	// after calling this if you wish the changes to happen immediately.)
	public static function stopStartOrRestartThreads($moduleClassName, $processName, $threads, $action, $forceUnpause = false) {
		
		if (empty($threads)) {
			return;
		}
		
		//Input should be an array, but accept single values as well
		if (!is_array($threads)) {
			$threads = [$threads];
		}
		
		//Accept a list of thread numbers, but convert them to the correct names
		foreach ($threads as &$thread) {
			if (is_numeric($thread)) {
				$thread = $processName. 'T'. str_pad($thread, 2, '0', STR_PAD_LEFT);
			}
		}
		
		switch ($action) {
			case 'stop':
				$sql = "
					UPDATE ". DB_PREFIX. "jobs
					SET `enabled` = 0,
						script_restart_time = 0";
				break;
			case 'start':
				$sql = "
					UPDATE ". DB_PREFIX. "jobs
					SET `enabled` = 1";
				break;
			case 'restart':
			case 'restart_if_running':
				$sql = "
					UPDATE ". DB_PREFIX. "jobs
					SET `enabled` = 1,
						script_restart_time = ". (int) time();
				break;
			default:
				return;
		}
		
		if ($forceUnpause) {
			$sql .= ",
				paused = 0";
		}
		
		$sql .= "
			WHERE manager_class_name = 'zenario_scheduled_task_manager'
			  AND job_type = 'background'
			  AND module_class_name = '". ze\escape::asciiInSQL($moduleClassName). "'
			  AND job_name IN (". ze\escape::in($threads, 'sql'). ")";
		
		switch ($action) {
			case 'restart_if_running':
				$sql .= "
			  AND `enabled` = 1";
		}
		
		ze\sql::update($sql);
	}
	
	
	public static function checkScheduledTaskRunning($jobName = false, $checkPulse = false, $managerClassName = 'zenario_scheduled_task_manager') {
		
		if (!ze::setting('jobs_enabled')
		|| !ze::$dbL->checkTableDef(DB_PREFIX. 'jobs', true)) {
			return false;
		}
		
		if ($jobName) {
			$key = ['manager_class_name' => $managerClassName, 'enabled' => 1, 'job_name' => $jobName];
			if (!ze\row::exists('jobs', $key)) {
				return false;
			}
		}
		
		if ($checkPulse) {
			if ((!$lastRun = ze::setting('jobs_last_run'))
			 || ($lastRun + 600 < time())) {
				return false;
			}
		}
		
		return true;
	}
	
}
