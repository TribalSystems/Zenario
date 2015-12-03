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






//This file handles updates which are key to how the updater works.
//These updates are run before any other updates in any other files are run



		//					 //
		//  Changes for 7.1  //
		//					 //


//In 7.1 we renamed the directories inside zenario/admin/db_updates/ to have clearer names.
//However the old names were stored in the local_revision_numbers table, so before we do any
//other database updates, we need to fix the data in the local_revision_numbers table.
	revision( 33400
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers`
	  SET path = 'admin/db_updates/step_2_update_the_database_schema'
	WHERE path = 'admin/db_updates/core'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers`
	  SET path = 'admin/db_updates/step_4_migrate_the_data'
	WHERE path = 'admin/db_updates/data_conversion'
_sql

, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]local_revision_numbers`
	WHERE path = 'admin/db_updates/updater'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers`
	  SET patchfile = 'admin_tables.inc.php'
	WHERE patchfile = 'admin.inc.php'
	  AND path LIKE 'admin/db_updates/step_%'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers`
	  SET patchfile = 'content_tables.inc.php'
	WHERE patchfile = 'local.inc.php'
	  AND path LIKE 'admin/db_updates/step_%'
_sql

//Make sure the patch files for user-related tables are all called the same name
);	revision( 33430
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]local_revision_numbers`
	  SET patchfile = 'user_tables.inc.php'
	WHERE patchfile IN ('user.inc.php', 'users_tables.inc.php')
	  AND path LIKE 'admin/db_updates/step_%'
_sql

, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]local_revision_numbers`
	WHERE patchfile IN ('user.inc.php', 'users_tables.inc.php')
	  AND path LIKE 'admin/db_updates/step_%'
_sql
);
