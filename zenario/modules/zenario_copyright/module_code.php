<?php
/*
 * Copyright (c) 2019, Tribal Limited
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

class zenario_copyright extends ze\moduleBaseClass {
	
	private $data = [];
	
	public function init() {
		$companyName = $this->setting('company_name');
		if($yearDisplay = $this->setting('year_display')){
			switch($yearDisplay){
				case 'do_not_display_year':
					$this->data['copyrightNotice'] = $this->phrase('Copyright [[c]] [[companyName]]', ['c' => '&#169;','companyName' => $companyName]);
					break;
				case 'display_single_year':
					if($displaySingleYear = $this->setting('display_single_year')){
						if($displaySingleYear == 'specific_year'){
							$endYear = $this->setting('specific_year');
						}else{
							$endYear = date('Y');
						}
						$this->data['copyrightNotice'] = $this->phrase('Copyright [[c]] [[companyName]] [[year]]', ['c' => '&#169;','companyName' => $companyName,'year' => $endYear]);
					}
					break;
				case 'display_year_range':
					if($endYearType = $this->setting('end_year_type')){
						if($endYearType == 'specific_year'){
							$endYear = $this->setting('end_year');
						}else{
							$endYear = date('Y');
						}
						$year = $this->setting('start_year').'-'.$endYear;
					
						$this->data['copyrightNotice'] = $this->phrase('Copyright [[c]] [[companyName]] [[year]]', ['c' => '&#169;','companyName' => $companyName,'year' => $year]);
					}
					break;
			}
		}
		
		$link = '';
		$target = '';
		$linkType = $this->setting('link_type');
		
		if ($linkType == 'external') {
			$link = 'href="'.$this->setting('url').'"';
		} elseif ($linkType == 'internal') {
			$cID = $cType = false;
			if ($linkExists = $this->getCIDAndCTypeFromSetting($cID, $cType,'hyperlink_target',$this->setting('use_translation'))) {
				$link = 'href="'.htmlspecialchars($this->linkToItem($cID, $cType, false)).'"';
			}
		}
		if (($linkType == 'external' || $linkType == 'internal') && $this->setting('target_blank')) {
			$target = 'target="_blank"';
		}
		$this->data['target'] = $target;
		$this->data['link'] = $link;
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->data);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
			if(!$this->setting('display_single_year')){
				$values['first_tab/display_single_year'] = 'current_year';
			}
			
			if(!$this->setting('end_year_type')){
				$values['first_tab/end_year_type'] = 'current_year';
			}

		}
	}
	
	
}