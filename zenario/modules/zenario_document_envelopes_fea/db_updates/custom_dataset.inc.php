<?php
/*
 * Copyright (c) 2020, Tribal Limited
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


ze\dbAdm::revision(1
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]document_envelopes_custom_data
_sql

, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]document_envelopes_custom_data (
		`envelope_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`envelope_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql
 
);

if (ze\dbAdm::needRevision(2)) {
	
	$datasetId = ze\datasetAdm::register(
		'Document envelopes',
		ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes_custom_data',
		ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes',
		'zenario_document_envelope__details',
		'zenario__document_envelopes/panels/document_envelopes',
		'',
		'_PRIV_MANAGE_ENVELOPE');
	
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'description');
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'keywords');
	
	$thumbnailFieldId = ze\datasetAdm::registerSystemField($datasetId, 'file_picker', 'details', 'thumbnail_id');
	ze\row::update('custom_dataset_fields', ['store_file' => 'in_database'], ['id' => $thumbnailFieldId, 'dataset_id' => $datasetId]);
	
	ze\dbAdm::revision(2);
}

ze\dbAdm::revision(4
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]document_envelope_languages
_sql

, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]document_envelope_languages (
		`language_id` varchar(10) NOT NULL,
		`label` varchar(255) NOT NULL DEFAULT "",
		PRIMARY KEY (`language_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT INTO [[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]document_envelope_languages
		(`language_id`, `label`)
	VALUES
		('multi', 'Multiple languages'),
		('en', 'English'),
		('fr', 'French'),
		('it', 'Italian'),
		('de', 'German'),
		('es', 'Spanish')
_sql
 
);

if (ze\dbAdm::needRevision(5)) {
	
	$datasetId = ze\datasetAdm::register(
		'Document envelopes',
		ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes_custom_data',
		ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes',
		'zenario_document_envelope__details',
		'zenario__document_envelopes/panels/document_envelopes',
		'',
		'_PRIV_MANAGE_ENVELOPE');
	
	ze\datasetAdm::registerSystemField($datasetId, 'centralised_select', 'details', 'language_id', 'language_id', 'none', 'zenario_document_envelopes_fea::getEnvelopeLanguages');
	
	ze\dbAdm::revision(5);
}

ze\dbAdm::revision(6
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]document_envelope_languages
	ADD COLUMN `multiple_languages_flag` tinyint(1) NOT NULL DEFAULT 0
_sql

, <<<_sql
	UPDATE [[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]document_envelope_languages
	SET multiple_languages_flag = 1
	WHERE language_id = "multi"
_sql
 
);