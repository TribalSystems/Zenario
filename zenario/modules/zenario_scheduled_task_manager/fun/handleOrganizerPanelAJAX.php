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


if (!empty($_POST['rerun'])
 || !empty($_POST['enable'])
 || !empty($_POST['enable_all'])
 || !empty($_POST['get_code'])) {
	
	$pcntl = true;
	//$pcntl = extension_loaded('pcntl');
	$calendar = extension_loaded('calendar');

	if (!$pcntl && !$calendar) {
		echo ze\admin::phrase('To enable scheduled tasks, please ask your sysadmin to enable the calendar and pcntl extensions in PHP.');
		exit;

	} elseif (!$pcntl) {
		echo ze\admin::phrase('To enable scheduled tasks, please ask your sysadmin to enable the pcntl extension in PHP.');
		exit;

	} elseif (!$calendar) {
		echo ze\admin::phrase('To enable scheduled tasks, please ask your sysadmin to enable the calendar extension in PHP.');
		exit;
	}
}


switch ($path) {
	case 'zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks':
		if (!empty($_POST['rerun']) && ze\priv::check('_PRIV_MANAGE_SCHEDULED_TASK')) {
			ze\row::update('jobs', ['status' => 'rerun_scheduled'], $ids);
			return $ids;
		
		} elseif (!empty($_POST['enable_all']) && ze\priv::check('_PRIV_MANAGE_SCHEDULED_TASK')) {
			ze\site::setSetting('jobs_enabled', 1);
			echo '<!--Clear_Toast-->';
			echo '<!--Reload_Organizer-->';
			return;
		
		} elseif (!empty($_POST['suspend_all']) && ze\priv::check('_PRIV_MANAGE_SCHEDULED_TASK')) {
			ze\site::setSetting('jobs_enabled', 0);
			echo '<!--Clear_Toast-->';
			echo '<!--Reload_Organizer-->';
			return;
			
		} elseif (!empty($_POST['enable']) && ze\priv::check('_PRIV_MANAGE_SCHEDULED_TASK')) {
			foreach (explode(',', $ids) as $id) {
				ze\row::update('jobs', ['enabled' => 1], $id);
			}
			return $ids;
			
		} elseif (!empty($_POST['suspend']) && ze\priv::check('_PRIV_MANAGE_SCHEDULED_TASK')) {
			foreach (explode(',', $ids) as $id) {
				ze\row::update('jobs', ['enabled' => 0], $id);
			}
			return $ids;
		
			
		} elseif (!empty($_POST['get_code'])) {
			echo '<!--Message_Type:Info-->',
				ze\admin::phrase('To enable Scheduled Tasks to run, please add the following command into your crontab:'),
				'<br/><br/>
				<form>
					<input type="text" readonly="readonly" style="width: 98%;"
					 value="* * * * *  php ', htmlspecialchars(CMS_ROOT. ze::moduleDir('zenario_scheduled_task_manager', 'cron/run_every_minute.php')), ' 1"/>
				</form>';
			return;
		}
		
		break;
		
		
	case 'zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks/hidden_nav/log/panel':
		if (!empty($_POST['delete']) && ze\priv::check('_PRIV_MANAGE_SCHEDULED_TASK')) {
			foreach (explode(',', $ids) as $id) {
				ze\row::delete('job_logs', $id);
			}
			return '';
		
		} elseif (!empty($_POST['truncate']) && ze\priv::check('_PRIV_MANAGE_SCHEDULED_TASK')) {
			ze\row::delete('job_logs', ['job_id' => $_POST['refiner__job']]);
			return '';
		}
		
		break;
}
