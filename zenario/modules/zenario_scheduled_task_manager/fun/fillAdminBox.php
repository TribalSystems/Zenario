<?php
/*
 * Copyright (c) 2019, Tribal Limited
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


switch ($path) {
	case 'zenario_job':
		
		$sql = "
			SELECT
				manager_class_name,
				job_name, module_id, 0+months AS months, 0+days AS days, hours, minutes, first_n_days_of_month,
				log_on_action, log_on_no_action, email_on_action,
				email_on_no_action, email_address_on_no_action, email_address_on_action, email_address_on_error, run_every_minute,
				last_run_started, last_run_finished, last_successful_run, last_action
			FROM ". DB_PREFIX. "jobs
			WHERE id = ". (int) $box['key']['id'];
		
		$result = ze\sql::select($sql);
		if (!$details = ze\sql::fetchAssoc($result)) {
			exit;
		}
		
		$box['key']['manager_class_name'] = $details['manager_class_name'];
		
		$details['mon'] = 0x1 & (int) $details['days'];
		$details['tue'] = 0x2 & (int) $details['days'];
		$details['wed'] = 0x4 & (int) $details['days'];
		$details['thr'] = 0x8 & (int) $details['days'];
		$details['fri'] = 0x10 & (int) $details['days'];
		$details['sat'] = 0x20 & (int) $details['days'];
		$details['sun'] = 0x40 & (int) $details['days'];
		$details['jan'] = 0x1 & (int) $details['months'];
		$details['feb'] = 0x2 & (int) $details['months'];
		$details['mar'] = 0x4 & (int) $details['months'];
		$details['apr'] = 0x8 & (int) $details['months'];
		$details['may'] = 0x10 & (int) $details['months'];
		$details['jun'] = 0x20 & (int) $details['months'];
		$details['jul'] = 0x40 & (int) $details['months'];
		$details['aug'] = 0x80 & (int) $details['months'];
		$details['sep'] = 0x100 & (int) $details['months'];
		$details['oct'] = 0x200 & (int) $details['months'];
		$details['nov'] = 0x400 & (int) $details['months'];
		$details['dec'] = 0x800 & (int) $details['months'];
		
		foreach (['time_and_day', 'month', 'reporting'] as $tab) {
			foreach ($details as $field => $value) {
				if (isset($box['tabs'][$tab]['fields'][$field])) {
					$box['tabs'][$tab]['fields'][$field]['value'] = $value;
				}
			}
		}
		
		$fields['time_and_day/frequency']['values'] = zenario_scheduled_task_manager::$frequencyOptions;
		$fields['time_and_day/start_at_hours']['values'] = zenario_scheduled_task_manager::$startAtHoursOptions;
		$fields['time_and_day/start_at_minutes']['values'] = zenario_scheduled_task_manager::$startAtMinutesOptions;
		
		$hours = explode(',',$details['hours']);
		$minutes = explode(',',$details['minutes']);
		$frequency = '';
		if (count($minutes) == 1) {
			if (count($hours) > 1) {
				$frequency = ((int)rtrim($hours[1], 'h') - (int)rtrim($hours[0], 'h')).'h';
			} else {
				$frequency = '24h';
			}
		} else {
			$frequency = ((int)rtrim($minutes[1], 'm') - (int)rtrim($minutes[0], 'm')) .'m';
			// Display warning if old format is found
			if (((count($hours) == 1) && ($hours[0] != '23h'))
				|| ((count($hours) > 1) && ((int)rtrim($hours[1], 'h') - (int)rtrim($hours[0], 'h') > 1))
				|| count($minutes) == 13) {
				$fields['time_and_day/old_format_warning']['hidden'] = false;
			}
		}
		$values['time_and_day/start_at_hours'] = rtrim($hours[0], 'h');
		$values['time_and_day/start_at_minutes'] = rtrim($minutes[0], 'm');
		if ($details['run_every_minute']) {
			$frequency = '1m';
		}
		$values['time_and_day/frequency'] = $frequency;
		
		$box['tabs']['month']['fields']['first_n_days_of_month']['values'] = zenario_scheduled_task_manager::$firstNOptions;
		$box['tabs']['reporting']['fields']['email_address_on_error']['note_below'] = 
			ze\admin::phrase(
				$box['tabs']['reporting']['fields']['email_address_on_error']['note_below'],
				['email' => htmlspecialchars(EMAIL_ADDRESS_GLOBAL_SUPPORT)]);
		
		
		if ($box['key']['manager_class_name'] == 'zenario_scheduled_task_manager') {
			$details['module_display_name'] = ze\module::displayName($details['module_id']);
			$box['title'] = ze\admin::phrase('Editing scheduled task "[[job_name]]" for the module [[module_display_name]]', $details);
		}
		
		$values['last_run/last_run_started'] = $details['last_run_started'];
		$values['last_run/last_run_finished'] = $details['last_run_finished'];
		$values['last_run/last_successful_run'] = $details['last_successful_run'];
		$values['last_run/last_action'] = $details['last_action'];
		break;
}
