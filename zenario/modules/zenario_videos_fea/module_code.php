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

class zenario_videos_fea extends zenario_abstract_fea {
	
	protected $idVarName = 'videoId';
	protected $data = [];
	
	public function init() {
		if ($this->subClass = $this->runSubClass(__FILE__)) {
			$this->requireJSLibsForFEAs();
			return $this->subClass->init();
		}
		return false;
	}
	
	public function showSlot() {
		if ($this->data) {
			$this->twigFramework($this->data);
		} elseif ($this->subClass) {
			return $this->subClass->showSlot();
		}
	}
	
	public function handlePluginAJAX() {
		if ($this->subClass) {
			return $this->subClass->handlePluginAJAX();
		}
	}
	
	public function addToPageFoot() {
		if (ze::$isTwig) return;
		
		if ($this->subClass) {
			return $this->subClass->addToPageFoot();
		} else {
			return parent::addToPageFoot();
		}
	}
	
	public static function requestVarDisplayName($name) {
		switch ($name) {
			case 'title':
				return 'Video title';
		}
	}
	
	public static function requestVarMergeField($field) {
		if ($field == 'title') {
			$videoTitle = ze\row::get(ZENARIO_VIDEOS_MANAGER_PREFIX. 'videos', 'title', ['id' => (int) ze::get('videoId')]);
			return $videoTitle;
		} else {
			return '';
		}
	}
}
require_once CMS_ROOT. ze::moduleDir('zenario_videos_fea', 'classes/visitor/_base.php');