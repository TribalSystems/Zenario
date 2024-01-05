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
//	This is the sub-class for the swiper animation library.
//	We're using subclasses for the different animation libraries, to give us a nice way
//	of coding the specific differences between them without needing lots of "if" statements.
//

class zenario_slideshow__animation_libraries__swiper extends zenario_slideshow {
	
	protected static $interfaceClassName = 'zenario_swiper_interface';
	
	public function setSlides($slides) {
		$this->slides = $slides;
	}
	
	public function initAnimationLibrary() {
		
		$this->startSlideshow();
		
		return true;
	}

	
	public function showSlot() {
		$this->mergeFields['Tabs'] = $this->sections['Tab'] ?? null;
		
		foreach ($this->slides as &$slide) {
			$slideNum = $slide['slide_num'];
			$this->mergeFields['Tabs'][$slideNum]['Plugins'] = ze::$slotContents[$this->slotName]->eggsOnSlideNum($slideNum);
		}
		
		$this->twigFramework($this->mergeFields);
	}
	
	protected function startSlideshow() {
		
		$jsDir = ze::moduleDir('zenario_slideshow', 'js');
		$this->requireJsLib($jsDir. '/swiper.wrapper.js.php', 'zenario/libs/yarn/swiper/swiper-bundle.min.css');
		
		$breakpoint = 1;
		if (ze::$responsive) {
			$breakpoint = ze::$minWidth;
		}
		
		/*$opt = [
			'timeout' => $this->setting('use_timeout')? (int) $this->setting('timeout') : 0,
			'pause' => $this->setting('use_timeout')? (int) $this->setting('pause') : 0,
			'next_prev_buttons_loop' => (bool) $this->setting('next_prev_buttons_loop'),
			'fx' => $this->setting('cycle2_fx'),
			'sync' => (bool) $this->setting('cycle2_sync'),
			'speed' => (int) $this->setting('cycle2_speed')
		];*/
		
		$this->callScript(static::$interfaceClassName, 'show',
			$this->slotName,
			$this->containerId,
			$breakpoint
		);
	}
}
