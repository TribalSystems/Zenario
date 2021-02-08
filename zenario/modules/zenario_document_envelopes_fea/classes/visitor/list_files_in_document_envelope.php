<?php
/*
 * Copyright (c) 2021, Tribal Limited
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


class zenario_document_envelopes_fea__visitor__list_files_in_document_envelope extends zenario_document_envelopes_fea {
	
	protected $idVarName = 'documentId';
	protected $envelopeId = false;
	
	public function init() {
		$this->userId = ze\user::id();
		$this->envelopeId = ze::request('envelopeId');
		
		if ($this->envelopeId) {
			$this->runVisitorTUIX();
			return true;
		} else {
			return ZENARIO_403_NO_PERMISSION;
		}
	}
	
	public function returnVisitorTUIXEnabled($path) {
		return true;
	}
	
	protected function populateItemsIdCol($path, &$tags, &$fields, &$values) {
		return 'id';
	}
	
	protected function populateItemsIdColDB($path, &$tags, &$fields, &$values) {
		return 'die.id';
	}
	
	protected function populateItemsSelectCount($path, &$tags, &$fields, &$values) {
		return '
			SELECT COUNT(DISTINCT die.id)';
	}
	
	protected function populateItemsSelect($path, &$tags, &$fields, &$values) {
		$sql = '
			SELECT
				die.id,
				die.file_id,
				die.envelope_id,
				die.file_format,
				f.filename,
				f.size AS filesize';
		
		return $sql;
	}
	
	protected function populateItemsFrom($path, &$tags, &$fields, &$values) {
		$sql = '
			FROM ' . DB_PREFIX . ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'documents_in_envelope die
			LEFT JOIN ' . DB_PREFIX . 'files f
				ON f.id = die.file_id';
		
		return $sql;
	}
	
	protected function populateItemsWhere($path, &$tags, &$fields, &$values) {
		$sql = '
			WHERE die.envelope_id = ' . (int)$this->envelopeId;
		
		return $sql;
	}
	
	protected function populateItemsGroupBy($path, &$tags, &$fields, &$values) {
		return '
			GROUP BY die.id';
	}
	
	protected function populateItemsOrderBy($path, &$tags, &$fields, &$values) {
		$sql = '
			ORDER BY die.id';
		
		return $sql;
	}
	
	protected function populateItemsPageSize($path, &$tags, &$fields, &$values) {
		return false;
	}
	
	protected function formatItemRow(&$item, $path, &$tags, &$fields, &$values) {
		if ($this->setting('show_filesize')) {
			$item['filesize'] = ze\lang::formatFilesizeNicely($item['filesize']);
		}
	}
	
	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		zenario_abstract_fea::fillVisitorTUIX($path, $tags, $fields, $values);
		$this->translatePhrasesInTUIX($tags, $path);
		$this->checkNewThing(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'documents_in_envelope');
		$this->populateItems($path, $tags, $fields, $values);
		
		$envelopeName = ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes', 'name', ['id' => $this->envelopeId]);
		ze\lang::applyMergeFields($tags['title'], ['envelope_name' => $envelopeName]);
		
		if ($this->setting('show_title')) {
			$tags['title_tags'] = $this->setting('title_tags') ?: 'h2';
		} else {
			unset($tags['title']);
		}
		
		//Apply hide search bar setting
		$this->applySearchBarSetting($tags);
		
		//Permissions
		$tags['perms'] = [
			'manage' => ze\user::can('manage', 'envelopeId', $tags['items'], $multiple = true),
			'create' => ze\user::can('manage', 'envelopeId')
		];
		
		//Column visibility
		$tags['columns']['id']['hidden'] = !$this->setting('show_id');
		$tags['columns']['filesize']['hidden'] = !$this->setting('show_filesize');
	}
	
	public function showFile() {
		if (($envelopeId = ze::request('envelopeId')) && ($documentId = ze::request('documentId'))) {
			$sql = '
				SELECT f.id, f.filename, f.mime_type, f.size
				FROM ' . DB_PREFIX . 'files f
				INNER JOIN ' . DB_PREFIX . ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'documents_in_envelope die
					ON die.file_id = f.id
				WHERE die.envelope_id = ' . (int)$envelopeId . '
				AND die.id = ' . (int)$documentId;
			$result = ze\sql::select($sql);
			
			$fileData = ze\sql::fetchAssoc($result);
			$fileData['path'] = ze\link::absolute() . ze\file::link($fileData['id']);
			
			header('Content-Type: ' . $fileData['mime_type']);
			header('Content-Disposition: attachment; filename="' . htmlspecialchars($fileData['filename']) . '"');
			header('Content-Length: ' . (int)$fileData['size']);
			readfile($fileData['path']);
		}
	}
	
	public function handlePluginAJAX() {
		switch ($_REQUEST['command'] ?? false) {
			case 'delete_file_from_document_envelope':
				$envelopeId = ze::request('envelopeId');
				$documentIds = ze::request('documentId');
				self::deleteDocumentsInEnvelope($envelopeId, $documentIds);
				self::updateEnvelopeFileFormats($envelopeId);
				break;
			default:
				echo 'Error, unrecognised command';
		}
	}
}