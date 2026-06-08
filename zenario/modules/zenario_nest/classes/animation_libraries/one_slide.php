<?php
/*
 * Copyright (c) 2026, Tribal Limited
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
//	This is the sub-class for the "one slide" animation library.
//	We're using subclasses for the different animation libraries, to give us a nice way
//	of coding the specific differences between them without needing lots of "if" statements.
//	Note that there is not actually any animation here, hence the class is mostly empty!
//

class zenario_nest__animation_libraries__one_slide extends zenario_nest {
	
	public function setSlides($slides) {
		$this->slides = $slides;
	}
	
	public function initAnimationLibrary() {
		
		//The cycle2 and one_slide animation libraries both have support for the "Make all plugins in nest equal height" option
		$this->sameHeight = (bool) $this->setting('eggs_equal_height');
		
		return true;
	}

	
	public function showSlot() {
		$this->mergeFields['Tabs'] = null;
		
		$first = true;
		foreach ($this->slides as &$slide) {
			$slideNum = $slide['slide_num'];
			
			//Only show the first slide.
			if ($first) {
				$this->mergeFields['Tabs'][$slideNum]['Plugins'] = ze::$slotContents[$this->slotName]->eggsOnSlideNum($slideNum);
				$this->mergeFields['Tabs'][$slideNum]['Hidden'] = false;
			} else {
				$this->mergeFields['Tabs'][$slideNum]['Hidden'] = true;
			}
			
			$first = false;
		}
		
		$this->twigFramework($this->mergeFields);
	}
	
	protected function tabLink($tabOrd) {
		return '';
	}
}
