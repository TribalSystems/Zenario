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



//This file contains php scripts code for converting user data after some database structure changes

//Starting with 9.3, smart groups may now check whether a user has any or a specific active timer,
//or check if the user has no active timer.
//Please note: as this feature was backpatched to 9.3, check if that DB update is already applied.
if (ze\dbAdm::needRevision(56501) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'smart_group_rules LIKE "timer_template_id"')) ze\dbAdm::revision(56501
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]smart_group_rules`
	MODIFY COLUMN `type_of_check`
		enum('user_field', 'role', 'activity_band', 'in_a_group', 'not_in_a_group', 'has_a_current_timer', 'has_no_current_timer') NOT NULL default 'user_field',
	ADD COLUMN `timer_template_id` int unsigned NOT NULL DEFAULT '0' AFTER `role_id`
_sql
);

//Remove spaces from screen names
if (ze\dbAdm::needRevision(57770)) {
	$sql = "
		SELECT id, screen_name
		FROM " . DB_PREFIX . "users
		WHERE status <> 'contact'
		AND screen_name <> ''";
	$result = ze\sql::select($sql);

	while ($row = ze\sql::fetchAssoc($result)) {
		if ($row['screen_name']) {
			$screenNameWithoutSpaces = str_replace(' ', '', $row['screen_name']);
		
			//If the new screen name after removing the spaces happens to be identical to an already existing screen name used by another user,
			//add a few numbers to differentiate. Check if the resulting screen name is unique.
			if (ze\row::exists('users', ['screen_name' => $screenNameWithoutSpaces, 'id' => ['!' => $row['id']]])) {
				$screenNameIsNotUnique = true;
				do {
					$number = rand(0, 999);
					if (!ze\row::exists('users', ['screen_name' => $screenNameWithoutSpaces . $number])) {
						$screenNameIsNotUnique = false;
					}
				} while ($screenNameIsNotUnique);
				
				$updateSql = "
					UPDATE " . DB_PREFIX . "users
					SET screen_name = '" . ze\escape::sql($screenNameWithoutSpaces . $number) . "'
					WHERE id = " . (int) $row['id'];
			} else {
				$updateSql = "
					UPDATE " . DB_PREFIX . "users
					SET screen_name = REPLACE(screen_name, ' ', '')
					WHERE id = " . (int) $row['id'];
			}
		
			ze\sql::update($updateSql);
		}
	}
	
	ze\dbAdm::revision(57770);
}

//In 9.6, we're changing the required/read only checkboxes of the dataset editor
//to be in line with User Forms: there will now be a selector with the values
//mandatory/read only/mandatory on condition/mandatory if visible.
//This was already done in step 2, and step 4 addresses cases where a field was mandatory and read only
//at the same time. They will now be marked as read only.
ze\dbAdm::revision(58750
, <<<_sql
	UPDATE `[[DB_PREFIX]]custom_dataset_fields`
	SET
		`required` = 0,
		`required_message` = NULL
	WHERE `required` = 1 AND `readonly` = 1
_sql
);


//Loop through the users table, looking for rows without an email, and set the email_verified column to 
//the email_not_set option.
//This can't be done in pure SQL, it needs to be done in PHP using our database libraries as the email column may be encrypted.
if (ze\dbAdm::needRevision(59602)) {
	
	$sql = "
		SELECT id, email, email_verified
		FROM ". DB_PREFIX. "users";
	
	$result = ze\sql::select($sql);

	foreach ($result as $row) {
		if (empty($row['email'])) {
			ze\row::update('users', ['email_verified' => 'email_not_set'], $row['id']);
		}
	}
	
	ze\dbAdm::revision(59602);
}