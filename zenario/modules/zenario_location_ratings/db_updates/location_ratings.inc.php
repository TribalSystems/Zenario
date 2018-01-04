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

revision(1

, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_RATINGS_PREFIX]]accreditors (
		`id` int(10) unsigned AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_RATINGS_PREFIX]]accreditor_scores (
		`id` int(10) unsigned AUTO_INCREMENT,
	 	`accreditor_id` int(10) unsigned NOT NULL,
		`score` int(10) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`accreditor_id`,`score`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_RATINGS_PREFIX]]location_accreditor_score_link (
		`id` int(10) unsigned AUTO_INCREMENT,
		`location_id` int(10) unsigned NOT NULL,
		`accreditor_score_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE (`location_id`,`accreditor_score_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision(2

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_RATINGS_PREFIX]]accreditors
	ADD COLUMN `score_type` enum('numeric','alpha','boolean') NOT NULL DEFAULT 'numeric'
_sql

); revision(5

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_RATINGS_PREFIX]]accreditor_scores
	MODIFY COLUMN `score` varchar(255) NOT NULL
_sql

);
?>