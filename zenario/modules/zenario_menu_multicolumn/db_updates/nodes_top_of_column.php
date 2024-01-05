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

//By creating a db_update script with revisions inside, you can create database tables
//for your Module when it is installed.

//All tables in the CMS start with the prefix DB_PREFIX. All Module tables must
//also start with this prefix, however to avoid a clash of names, they must use an
//additional prefix generated by the CMS.
//This prefix will be available in a constant defined for you. For example, if your
//class name was "example_hello_world", your constant would be called:
	//EXAMPLE_HELLO_WORLD_PREFIX
//and you would use:
	//DB_PREFIX. EXAMPLE_HELLO_WORLD_PREFIX
//as a prefix for your tables in your PHP code, or:
	//[[DB_PREFIX]][[EXAMPLE_HELLO_WORLD_PREFIX]]
//in your Storekeeper files or your revisions.

//If you create a table, you should always use a "DROP TABLE IF EXISTS" statement and
//then a "CREATE TABLE" statement. You should never use a "CREATE TABLE IF NOT EXISTS"
//statement as this can cause problems in some cases when restoring an old database
//backup file.


//Create a new table that mirrors the animals table, and stores extra information
//on classification.
ze\dbAdm::revision(2

, <<<_sql
	DROP TABLE IF EXISTS
	`[[DB_PREFIX]][[ZENARIO_MENU_MULTICOLUMN_PREFIX]]nodes_top_of_column`
_sql

, <<<_sql
	CREATE TABLE
	`[[DB_PREFIX]][[ZENARIO_MENU_MULTICOLUMN_PREFIX]]nodes_top_of_column` (
		`node_id` int(10) unsigned NOT NULL,
		`top_of_column` tinyint(1),
		PRIMARY KEY (`node_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

);