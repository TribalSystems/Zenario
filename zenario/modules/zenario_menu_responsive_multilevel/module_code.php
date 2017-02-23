<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

class zenario_menu_responsive_multilevel extends zenario_menu {
	
	public function init(){
		
		if (parent::init()) {
			
			$this->callScript('zenario_menu_responsive_multilevel', 'init', $this->containerId. '-dl-menu');
			
			return true;
		} else {
			return false;
		}
	}
	
	function drawMenuLink($i, &$row, $recurseCount, $maxI) {
		
		//Check if a menu node has both children and a landing page
		if (!empty($row['url'])
		 && !empty($row['children'])) {
			
			//If so, check what we're supposed to do.
			//(Note the default behaviour in the jquery plugin is to show the children so we don't need to handle that case.)
			switch ($this->setting('landing_page_logic')) {
				case 'goto_landing_page':
					unset($row['children']);
					break;
				
				case 'both':
					//If the visitor clicks on menu node with children and a landing page, show the landing page and cancel
					//showing the children. (You can still see the children by clicking away from the link.)
					if (empty($row['onclick'])) {
						$row['onclick'] = "zenario.stop(event); return true;";
					} else {
						$row['onclick'] = "zenario.stop(event); return true; ". $row['onclick'];
					}
					break;
			}
		}
		
		return parent::drawMenuLink($i, $row, $recurseCount, $maxI);
	}
}