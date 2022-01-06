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

//Use the edit mode class and logic in create mode as well
ze\module::incSubclass('zenario_document_envelopes_fea', 'visitor', 'edit_document_envelope');
class zenario_document_envelopes_fea__visitor__create_document_envelope extends zenario_document_envelopes_fea__visitor__edit_document_envelope {
	
	protected $idVarName = 'envelopeId';
	protected $customDatasetFieldIds = [];
	protected $dataset = [];
	protected $datasetAllCustomFields = [];
	
	public function init() {
		if (!empty(ze\user::id()) && ze\user::can('manage', $this->idVarName)) {
			
			if ($this->setting('custom_field_1') || $this->setting('custom_field_2') || $this->setting('custom_field_3')) {
				$this->dataset = ze\dataset::details(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes');
				$this->datasetAllCustomFields = ze\datasetAdm::listCustomFields($this->dataset['id'], $flat = false);
			
				//Make sure the field exists in the dataset (e.g. hasn't been deleted) before using it.
				for ($i = 1; $i <= 3; $i++) {
					if ($this->setting('custom_field_' . $i) && isset($this->datasetAllCustomFields[$this->setting('custom_field_' . $i)])) {
						$this->customDatasetFieldIds['custom_field_' . $i] = $this->setting('custom_field_' . $i);
					}
				}
			}
			
			$this->runVisitorTUIX();
			return true;
		} else {
			return ZENARIO_403_NO_PERMISSION;
		}
	}
}
