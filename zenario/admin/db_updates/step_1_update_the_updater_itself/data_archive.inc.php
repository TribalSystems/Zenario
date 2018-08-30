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






//This file handles updates which are key to how the updater works.
//These updates are run before any other updates in any other files are run




//Create the data_archive_revision_numbers table to start tracking revisions
//made in this database.
ze\dbAdm::revision( 44390
, <<<_sql
	CREATE TABLE `[[DB_PREFIX_DA]]data_archive_revision_numbers` (
		`path` varchar(255) NOT NULL,
		`patchfile` varchar(64) NOT NULL,
		`revision_no` int(10) unsigned NOT NULL,
		PRIMARY KEY (`path`,`patchfile`),
		KEY `revision_no` (`revision_no`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Fix a mistake where a table had the wrong engine/character-set chosen by mistake.
);	ze\dbAdm::revision( 46200
, <<<_sql
	ALTER TABLE `[[DB_PREFIX_DA]]data_archive_revision_numbers`
	ENGINE=MyISAM
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX_DA]]data_archive_revision_numbers`
	CONVERT TO CHARACTER SET utf8
_sql

);
