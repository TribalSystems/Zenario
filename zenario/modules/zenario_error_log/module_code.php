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

class zenario_error_log extends module_base_class {
	
	public static function log404Error($pageAlias, $httpReferer = '') {
		$logged = date('Y-m-d H:i:s');
		if (strlen($pageAlias) > 255) {
			$pageAlias = substr($pageAlias, 0, 252).'...';
		}
		if (strlen($httpReferer) > 255) {
			$pageAlias = substr($pageAlias, 0, 252).'...';
		}
		insertRow(ZENARIO_ERROR_LOG_PREFIX.'error_log', array('logged' => $logged, 'page_alias' => $pageAlias, 'referrer_url' => $httpReferer));
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch($path) {
			case 'zenario__administration/panels/error_log':
				if (post('clear_log')) {
					$sql = 'DELETE FROM '.DB_NAME_PREFIX. ZENARIO_ERROR_LOG_PREFIX. 'error_log';
					sqlQuery($sql);
				}
				break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch($path) {
			case 'zenario__administration/panels/error_log':
				$spareAliases = array();
				$sql = '
					SELECT el.id, sa.target_loc, sa.content_id, sa.content_type, sa.ext_url
					FROM '.DB_NAME_PREFIX.'spare_aliases sa
					INNER JOIN '.DB_NAME_PREFIX. ZENARIO_ERROR_LOG_PREFIX. 'error_log el
						ON sa.alias = el.page_alias';
				$result = sqlSelect($sql);
				while($row = sqlFetchAssoc($result)) {
					$spareAliases[$row['id']] = $row;
				}
				foreach($panel['items'] as $key => &$item) {
					if (isset($spareAliases[$key])) {
						if ($spareAliases[$key]['target_loc'] == 'int') {
							$formattedTagId = formatTag($spareAliases[$key]['content_id'], $spareAliases[$key]['content_type'], false, false, true);
							$item['connected_spare_alias_destination'] = $formattedTagId;
						} else {
							$item['connected_spare_alias_destination'] = $spareAliases[$key]['ext_url'];
						}
					}
				}
				break;
		}
	}
	
	// Scheduled task to report yesterdays errors
	public static function jobReportErrors() {
		$yesterday = new DateTime();
		$yesterday->sub(new DateInterval('P1D'));
		
		// Get all errors from yesterday
		$sql = '
			SELECT logged, page_alias, referrer_url
			FROM ' . DB_NAME_PREFIX . ZENARIO_ERROR_LOG_PREFIX . 'error_log
			WHERE logged BETWEEN "' . sqlEscape($yesterday->format('Y-m-d 00:00:00')) . '" AND "' . sqlEscape($yesterday->format('Y-m-d 23:59:59')) . '"
			ORDER BY logged DESC';
		$errors = sqlSelect($sql);
		// Send report
		if (sqlNumRows($errors) > 0) {
			echo sqlNumRows($errors) . " 404 Error(s):\n";
			while ($error = sqlFetchAssoc($errors)) {
				echo "\n---------------\n";
				echo adminPhrase('Logged:') . ' ' . $error['logged'] . "\n";
				echo adminPhrase('Requested page alias:') . ' ' . $error['page_alias'] . "\n";
				echo adminPhrase('Referrer URL:') . ' ' . $error['referrer_url'] . "\n";
			}
			return true;
		// If last action is over 1 week old and no errors send a message
		} else {
			$moduleId = getModuleId('zenario_error_log');
			$job = getRow(
				'jobs', 
				array('last_action'), 
				array(
					'manager_class_name' => 'zenario_scheduled_task_manager',
					'job_name' => 'jobReportErrors',
					'module_id' => $moduleId
				)
			);
			if (!$job['last_action']) {
				echo 'No errors to report';
				return true;
			}
			
			$now = new DateTime();
			$lastAction = new DateTime($job['last_action']);
			$lastWeek = $now->sub(new DateInterval('P1W'));
			$interval = $lastAction->diff($lastWeek);
			if (!$interval->invert) {
				echo 'No errors to report';
				return true;
			}
			
		}
		return false;
	}
}