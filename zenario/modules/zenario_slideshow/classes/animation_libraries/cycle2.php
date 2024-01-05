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

//
//	This is the sub-class for the cycle2 animation library.
//	We're using subclasses for the different animation libraries, to give us a nice way
//	of coding the specific differences between them without needing lots of "if" statements.
//

class zenario_slideshow__animation_libraries__cycle2 extends zenario_slideshow {
	
	protected static $interfaceClassName = 'zenario_cycle2_interface';
	
	public function setSlides($slides) {
		$this->slides = $slides;
	}
	
	public function initAnimationLibrary() {
		
		
		$firstTabNum = false;
		
		$tabOrd = 0;
		foreach ($this->slides as &$slide) {
			++$tabOrd;
			
			$link = $this->tabLink($tabOrd);
			
			if (!isset($this->sections['Tab'])) {
				$this->sections['Tab'] = [];
			}
			
			$this->sections['Tab'][$slide['slide_num']] = [
				'TAB_ORDINAL' => $tabOrd,
				'Class' => 'tab_'. $tabOrd. ' tab',
				'Slide_Class' => 'slide_'. $slide['slide_num']. ' '. $slide['css_class'],
				'Tab_Link' => $link,
				'Tab_Name' => $this->formatTitleText($slide['slide_label'], true)
			];
			
			if (!$firstTabNum) {
				$firstTabNum = $slide['slide_num'];
			}
		}
		
		if (isset($this->sections['Tab'][$firstTabNum]['Class'])) {
			$this->sections['Tab'][$firstTabNum]['Class'] .= '_on';
		}
		
		//Catch the unusual case where someone wants a slideshow, but has only defined one slide,
		//so there are no transistions to animate
		if ($tabOrd < 2) {
			$this->mergeFields['Next_Link'] = '';
			$this->mergeFields['Next_Disabled'] = '_disabled';
			$this->mergeFields['Prev_Link'] = '';
			$this->mergeFields['Prev_Disabled'] = '_disabled';
		} else {
			$this->mergeFields['Next_Link'] = 'href="#" onclick="return '. static::$interfaceClassName. '.next(this);"';
			$this->mergeFields['Next_Disabled'] = '';
			$this->mergeFields['Prev_Link'] = 'href="#" onclick="return '. static::$interfaceClassName. '.prev(this);"';
			$this->mergeFields['Prev_Disabled'] = '';
			$this->startSlideshow();
		}
		
		
		return true;
	}

	
	public function showSlot() {
		$this->mergeFields['Tabs'] = $this->sections['Tab'] ?? null;
		
		$hide = false;
		foreach ($this->slides as &$slide) {
			$slideNum = $slide['slide_num'];
			$this->mergeFields['Tabs'][$slideNum]['Plugins'] = ze::$slotContents[$this->slotName]->eggsOnSlideNum($slideNum);
			$this->mergeFields['Tabs'][$slideNum]['Hidden'] = $hide;
			
			//Hide the slides after slide one, until the jQuery slideshow Plugin kicks in and overrides this.
			$hide = true;
		}
		
		$this->twigFramework($this->mergeFields);
	}
	
	protected function startSlideshow() {
		
		$jsDir = ze::moduleDir('zenario_slideshow', 'js');
		$this->requireJsLib($jsDir. '/cycle2.wrapper.js.php');
		
		$opt = [
			'timeout' => $this->setting('use_timeout')? (int) $this->setting('timeout') : 0,
			'pause' => $this->setting('use_timeout')? (int) $this->setting('pause') : 0,
			'next_prev_buttons_loop' => (bool) $this->setting('next_prev_buttons_loop'),
			'fx' => $this->setting('cycle2_fx'),
			'sync' => (bool) $this->setting('cycle2_sync'),
			'speed' => (int) $this->setting('cycle2_speed')
		];
		
		$this->callScript(static::$interfaceClassName, 'show',
			$this->containerId,
			$opt,
			0
		);
	}
	
	protected function tabLink($tabOrd) {
		$link = 'href="#"';
		
		if ($this->setting('use_tab_clicks')) {
			$link .= ' onclick="return '. static::$interfaceClassName. '.page(this, '. ($tabOrd-1). ');"';
		} else {
			$link .= ' onclick="return false;"';
		}
		
		if ($this->setting('use_tab_hover')) {
			$link .= ' onmouseover="'. static::$interfaceClassName. '.page(this, '. ($tabOrd-1). ', true);"';
			
			if ($this->setting('use_timeout')) {
				$link .= ' onmouseout="'. static::$interfaceClassName. '.resume(this);"';
			}
		}
		
		return $link;
	}
}
