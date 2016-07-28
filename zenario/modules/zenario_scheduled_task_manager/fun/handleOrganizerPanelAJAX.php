<?php
/*
 * Copyright (c) 2016, Tribal Limited
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
	case 'zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks':
		if (post('rerun') && checkPriv('_PRIV_MANAGE_SCHEDULED_TASK')) {
			updateRow('jobs', array('status' => 'rerun_scheduled'), $ids);
			return $ids;
		
		} elseif (post('enable_all') && checkPriv('_PRIV_MANAGE_SCHEDULED_TASK')) {
			setSetting('jobs_enabled', 1);
			echo '<!--Reload_Organizer-->';
			return;
		
		} elseif (post('suspend_all') && checkPriv('_PRIV_MANAGE_SCHEDULED_TASK')) {
			setSetting('jobs_enabled', 0);
			echo '<!--Reload_Organizer-->';
			return;
			
		} elseif (post('enable') && checkPriv('_PRIV_MANAGE_SCHEDULED_TASK')) {
			foreach (explode(',', $ids) as $id) {
				updateRow('jobs', array('enabled' => 1), $id);
			}
			return $ids;
			
		} elseif (post('suspend') && checkPriv('_PRIV_MANAGE_SCHEDULED_TASK')) {
			foreach (explode(',', $ids) as $id) {
				updateRow('jobs', array('enabled' => 0), $id);
			}
			return $ids;
		
			
		} elseif (post('get_code')) {
			echo '<!--Message_Type:None-->',
				adminPhrase('To enable Scheduled Tasks to run, please add the following command into your Crontab:'),
				'<br/><br/>
				<form>
					<input type="text" readonly="readonly" style="width: 100%;"
					 value="* * * * *  php ', htmlspecialchars(CMS_ROOT. moduleDir('zenario_scheduled_task_manager', 'cron/run_every_minute.php')), ' 1"/>
				</form>';
			return;
		}
		
		break;
		
		
	case 'zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks/hidden_nav/log/panel':
		if (post('delete') && checkPriv('_PRIV_MANAGE_SCHEDULED_TASK')) {
			foreach (explode(',', $ids) as $id) {
				deleteRow('job_logs', $id);
			}
			return '';
		
		} elseif (post('truncate') && checkPriv('_PRIV_MANAGE_SCHEDULED_TASK')) {
			deleteRow('job_logs', array('job_id' => post('refiner__job')));
			return '';
		}
		
		break;
}
