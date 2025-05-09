<?php
/*
 * Copyright (c) 2025, Tribal Limited
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


/*
	Any user-related tables should be created in this script.
	Reminder: every table you create here should also be listed in the local-DROP.sql file
*/






//
//	Zenario 9.3
//

//In 9.3, we're going through and fixing the character-set on several columns that should
//have been using "ascii"
	ze\dbAdm::revision(55140
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]user_country_link`
	MODIFY COLUMN `country_id` varchar(5) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql


//
//	Zenario 9.4
//

//Bugfixes: moved logic to add columns from:
//Email Template Manager, Common Features and Users.
//Also added checks to make sure the columns exist before attempting to add them again.
//Please note: this set of updates was backpatched from HEAD to 9.3.
//It was added to 9.3 as a post-branch fix.
);	if (ze\dbAdm::needRevision(57305) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'users LIKE "consent_hash"')) ze\dbAdm::revision(57305
, <<<_sql
	ALTER TABLE [[DB_PREFIX]]users 
	ADD COLUMN `consent_hash` varchar(28) NULL
_sql

//In addition to the previous comment, this update was in Zenario User Consent Forms.
//A core table column should not have different sizes depending on what module is or isn't running,
//so this will be standardised.
);	ze\dbAdm::revision( 57306
, <<<_sql
	ALTER TABLE [[DB_PREFIX]]users 
	MODIFY COLUMN `consent_hash` varchar(35) NULL
_sql




//
//	Zenario 9.6
//


);	ze\dbAdm::revision(59460
, <<<_sql
	 ALTER TABLE `[[DB_PREFIX]]users`
	 ADD COLUMN `email_verified_temp` enum('verified', 'not_verified', 'email_not_set') DEFAULT 'email_not_set' AFTER `email_verified`
_sql

, <<<_sql
	 UPDATE `[[DB_PREFIX]]users`
	 SET email_verified_temp = IF(email_verified, 'verified', 'not_verified')
_sql

, <<<_sql
	 ALTER TABLE `[[DB_PREFIX]]users`
	 DROP COLUMN `email_verified`
_sql

, <<<_sql
	 ALTER TABLE `[[DB_PREFIX]]users`
	 CHANGE COLUMN `email_verified_temp` `email_verified` enum('verified', 'not_verified', 'email_not_set') DEFAULT 'email_not_set'
_sql


//
//	Zenario 9.7
//

//In 9.7, we changed the way we handle email changes and verification. A module-specific table was dropped
//and the logic was merged into the users table.
);	ze\dbAdm::revision(59800
, <<<_sql
	 ALTER TABLE `[[DB_PREFIX]]users`
	 ADD COLUMN `email_new` varchar(100) NOT NULL DEFAULT '' AFTER `email_verified`,
	 ADD COLUMN `hash_change_email` varchar(28) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '' AFTER `creation_method_note`
_sql

, <<<_sql
	 ALTER TABLE `[[DB_PREFIX]]users`
	 ADD COLUMN `hash_change_email_expiry` datetime DEFAULT NULL AFTER `hash_change_email`,
	 CHANGE COLUMN `hash` `hash_verify_email` varchar(28) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''
_sql


//
//	Zenario 10.0
//

//Before 10.0, the users table salutation column would accept null and the default value would be null.
//In 10.0, we changed the column definition to match that of first and last names:
//do not accept null, and make the default value be an empty string.
);	ze\dbAdm::revision(60663
, <<<_sql
	 UPDATE `[[DB_PREFIX]]users`
	 SET salutation = ''
	 WHERE salutation IS NULL
_sql

, <<<_sql
	 ALTER TABLE `[[DB_PREFIX]]users`
	 CHANGE COLUMN `salutation` `salutation` varchar(25) NOT NULL DEFAULT ''
_sql

);	ze\dbAdm::revision(60672
, <<<_sql
	 ALTER TABLE `[[DB_PREFIX]]users`
	 ADD COLUMN `email_domain` varchar(100) NOT NULL DEFAULT '' AFTER `email`
_sql

);	ze\dbAdm::revision(60673
, <<<_sql
	 ALTER TABLE `[[DB_PREFIX]]users`
	 ADD KEY (`email_domain`)
_sql


);	ze\dbAdm::revision(60675
, <<<_sql
	ALTER TABLE [[DB_PREFIX]]users 
	MODIFY COLUMN `identifier` varchar(55) DEFAULT NULL
_sql

);	ze\dbAdm::revision(60940
, <<<_sql
	ALTER TABLE [[DB_PREFIX]]users 
	ADD COLUMN `hash_verify_email_expiry` datetime DEFAULT NULL AFTER `hash_verify_email`
_sql

);