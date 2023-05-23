<?php
/*
 * Copyright (c) 2023, Tribal Limited
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




//Automatically convert any table that's not using our preferred engine to that engine
if (ze\dbAdm::needRevision(50950)) {
	
	foreach (ze\sql::fetchValues("
		SELECT `TABLE_NAME`
		FROM information_schema.tables
		WHERE `TABLE_SCHEMA` = '". ze\escape::sql(DBNAME). "'
		  AND `TABLE_NAME` LIKE '". ze\escape::like(DB_PREFIX). "%'
		  AND `ENGINE` != '". ze\escape::sql(ZENARIO_TABLE_ENGINE). "'
	") as $tableName) {
		ze\sql::update("
			ALTER TABLE `". ze\escape::sql($tableName). "`
			ENGINE=". ze\escape::sql(ZENARIO_TABLE_ENGINE)
		);
	}
	
	ze\dbAdm::revision(50950);
}


//Automatically convert any table that's not using utf8mb4
if (ze\dbAdm::needRevision(55151)) {
	
	foreach (ze\sql::fetchValues("
		SELECT `TABLE_NAME`
		FROM information_schema.tables
		WHERE `TABLE_SCHEMA` = '". ze\escape::sql(DBNAME). "'
		  AND `TABLE_NAME` LIKE '". ze\escape::like(DB_PREFIX). "%'
		  AND `TABLE_COLLATION` IN ('utf8_general_ci', 'utf8mb4_general_ci')
	") as $tableName) {
		ze\sql::update("
			ALTER TABLE `". ze\escape::sql($tableName). "`
			CHARACTER SET ". ze\escape::sql(ZENARIO_TABLE_CHARSET). " COLLATE ". ze\escape::sql(ZENARIO_TABLE_COLLATION). "
		");
	}
	
	//Agressively go after any columns that are in the wrong characterset
	foreach (ze\sql::fetchValues("
		SELECT `TABLE_NAME`
		FROM information_schema.tables
		WHERE `TABLE_SCHEMA` = '". ze\escape::sql(DBNAME). "'
		  AND `TABLE_NAME` LIKE '". ze\escape::like(DB_PREFIX). "%'
		  AND `TABLE_COLLATION` IN ('utf8_general_ci', 'utf8mb4_general_ci', '". ze\escape::sql(ZENARIO_TABLE_COLLATION). "')
	") as $tableName) {
		if ($createTable = ze\sql::fetchRow("SHOW CREATE TABLE `". ze\escape::sql($tableName). "`")) {
			$start = strpos($createTable[1], '(');
			$end = strrpos($createTable[1], ')');
			$cols = explode(",\n", substr($createTable[1], $start + 1, $end - $start - 1));
		
			foreach ($cols as $col) {
				if (preg_match('@\bCHARACTER SET utf8\w*\b@', $col)) {
					//echo("\n\n". $col. "\nALTER TABLE `". ze\escape::sql($tableName). "` MODIFY COLUMN ". preg_replace('@\bCHARACTER SET utf8\w*\b@', '', $col));
					ze\sql::update("ALTER TABLE `". ze\escape::sql($tableName). "` MODIFY COLUMN ". preg_replace('@\bCHARACTER SET utf8\w*\b@', '', $col));
				}
			}
		}
	}
	
	ze\dbAdm::revision(55151);
}

