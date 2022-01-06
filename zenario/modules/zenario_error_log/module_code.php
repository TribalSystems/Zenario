<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

class zenario_error_log extends ze\moduleBaseClass {
	
	//Called when a 404 error is triggered to log it
	public static function log404Error($pageAlias, $httpReferer = '') {
		
		$pageAlias = ze\escape::utf8($pageAlias);
		
		$logged = date('Y-m-d H:i:s');
		if (strlen($pageAlias) > 255) {
			$pageAlias = mb_substr($pageAlias, 0, 252, 'UTF-8').'...';
		}
		if (strlen($httpReferer) > 65535) {
			$httpReferer = mb_substr($httpReferer, 0, 65532, 'UTF-8').'...';
		}
		ze\row::insert(ZENARIO_ERROR_LOG_PREFIX.'error_log', ['logged' => $logged, 'page_alias' => $pageAlias, 'referrer_url' => $httpReferer]);
		
		//Delete old log entries according to site setting
		if ($days = ze::setting('period_to_delete_error_log')) {
			$date = date('Y-m-d', strtotime('-' . $days . ' day', strtotime($logged)));
			$sql = '
				DELETE FROM ' . DB_PREFIX . ZENARIO_ERROR_LOG_PREFIX . 'error_log
				WHERE logged <= "' . ze\escape::sql($date) . '"';
			ze\sql::update($sql);
		}
	}

	
	//Scheduled task to report yesterdays errors
	public static function jobReportErrors() {
		$yesterday = new DateTime();
		$yesterday->sub(new DateInterval('P5D'));
		
		//Get all errors from yesterday
		$sql = '
			SELECT logged, page_alias, referrer_url
			FROM ' . DB_PREFIX . ZENARIO_ERROR_LOG_PREFIX . 'error_log
			WHERE logged BETWEEN "' . ze\escape::sql($yesterday->format('Y-m-d 00:00:00')) . '" AND "' . ze\escape::sql($yesterday->format('Y-m-d 23:59:59')) . '"
			ORDER BY logged DESC';
		$errors = ze\sql::select($sql);
		//Send report
		if (ze\sql::numRows($errors) > 0) {
			echo ze\admin::phrase('A visitor requested a page and saw a 404 Not Found error, here are the details:') . "\n";
			echo ze\admin::phrase("[[num_errors]] 404 Error(s):", ['num_errors' => ze\sql::numRows($errors)]);
			echo "\n";

			$domain = ze\link::protocol() . ze\link::primaryDomain() . SUBDIRECTORY;

			while ($error = ze\sql::fetchAssoc($errors)) {
				$probableUrl = $domain . $error['page_alias'];
				
				echo "\n---------------\n";
				echo ze\admin::phrase('Logged:') . ' ' . $error['logged'] . "\n";
				echo ze\admin::phrase('Visitor-requested page alias:') . ' ' . $error['page_alias'] . "\n";
				echo ze\admin::phrase('Probable URL:') . ' ' . $probableUrl . "\n";
				echo ze\admin::phrase('Referrer URL:') . ' ' . ($error['referrer_url'] ?: ze\admin::phrase("[no referrer]")) . "\n";

				//Process the alias and break it into words
				$pageAliasPartsFound = [];
				if (!function_exists('mb_ereg_replace') || !$error['page_alias'] = mb_ereg_replace('[^\w\s_\'"]', ' ', $error['page_alias'])) {
					//Fall back to traditional pattern matching if that fails
					$error['page_alias'] = preg_replace('/[^\w\s_\'"]/', ' ', $error['page_alias']);
				}

				preg_match_all('/(\S*\w\S*)/', trim($error['page_alias']), $pageAliasPartsFound, PREG_PATTERN_ORDER);
				$pageAliasPartsFound = array_filter($pageAliasPartsFound);

				$pageAliasParts = [];
				foreach ($pageAliasPartsFound as $set) {
					foreach ($set as $value) {
						$pageAliasParts[] = $value;
					}
				}

				$pageAliasParts = array_unique($pageAliasParts);
				$pageAliasString = '-' . implode('-', $pageAliasParts);
				
				$url = $domain . "organizer.php#zenario__administration/panels/error_log~" . htmlspecialchars($pageAliasString);

				echo ze\admin::phrase("To prevent further 404 errors at this URL, go to the site's 404 Error Log at [[url]] and select one item, then click \"Fix this error\" and select a content item. Zenario will create a spare alias to redirect future visitors to a more suitable page.", ['url' => $url]);
				echo "\n";
			}
			return true;
		//If last action is over 1 week old and no errors send a message
		} else {
			$moduleId = ze\module::id('zenario_error_log');
			$job = ze\row::get(
				'jobs', 
				['last_action'], 
				[
					'manager_class_name' => 'zenario_scheduled_task_manager',
					'job_name' => 'jobReportErrors',
					'module_id' => $moduleId
				]
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