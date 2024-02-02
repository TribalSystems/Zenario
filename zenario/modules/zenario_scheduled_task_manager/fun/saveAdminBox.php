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


switch ($path) {
	case 'zenario_job':
		if (!ze\priv::check('_PRIV_MANAGE_SCHEDULED_TASK')) {
			exit;
		}
			
		if (ze\ring::engToBoolean($box['tabs']['time_and_day']['edit_mode']['on'] ?? false) && empty($box['tabs']['month']['hidden'])) {
			
			$columns = [];
			
			$days = '';
			foreach (['mon','tue','wed','thr','fri','sat','sun'] as $day) {
				if ($values['time_and_day/'. $day]) {
					$days .= ($days? ',' : ''). $day;
				}
			}
			$columns['days'] = $days;
			
			$hours = '';
			$minutes = '';
			$startAtHour = $values['time_and_day/start_at_hours'];
			$startAtMinutes = $values['time_and_day/start_at_minutes'];
			$frequency = $values['time_and_day/frequency'];
			
			// Special case for running every minute
			$run_every_minute = false;
			if ($frequency == '1m') {
				$frequency = '5m';
				$run_every_minute = true;
			}
			$columns['run_every_minute'] = $run_every_minute;
			
			if (substr($frequency, -1) == 'h') {
				$i = rtrim($frequency, 'h');
				$j = 60;
			} else {
				$j = rtrim($frequency, 'm');
				$i = 1;
			}
			for ($hour = $startAtHour; $hour < 24; $hour += $i) {
				$hours .= $hour.'h,';
			}
			for ($minute = $startAtMinutes; $minute < 60; $minute += $j) {
				$minutes .= $minute.'m,';
			}
			
			$columns['hours'] = rtrim($hours,',');
			$columns['minutes'] = rtrim($minutes, ',');
			
			
			
			
			ze\row::update('jobs', $columns, $box['key']['id']);
		
		} 
		
		if (ze\ring::engToBoolean($box['tabs']['month']['edit_mode']['on'] ?? false) && empty($box['tabs']['month']['hidden'])) {
			
			$months = '';
			foreach (['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'] as $month) {
				if ($values['month'. '/'. $month]) {
					$months .= ($months? ',' : ''). $month;
				}
			}
			
			ze\row::update('jobs',
				[
					'months' => $months,
					'first_n_days_of_month' => (int) $values['month/first_n_days_of_month']],
				$box['key']['id']);
		
		} 
		
		if (ze\ring::engToBoolean($box['tabs']['reporting']['edit_mode']['on'] ?? false)) {
			
			ze\row::update('jobs',
				[
					'log_on_action' => (int) $values['reporting/log_on_action'],
					'log_on_no_action' => (int) $values['reporting/log_on_no_action'],
					'email_on_action' => (int) $values['reporting/email_on_action'],
					'email_on_no_action' => (int) $values['reporting/email_on_no_action'],
					'email_address_on_no_action' => $values['reporting/email_address_on_no_action'],
					'email_address_on_action' => $values['reporting/email_address_on_action'],
					'email_address_on_error' => $values['reporting/email_address_on_error']],
				$box['key']['id']);
		}
		
		
		break;
}