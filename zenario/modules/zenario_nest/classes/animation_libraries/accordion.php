<?php
/*
 * Copyright (c) 2025, Tribal Limited
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

class zenario_nest__animation_libraries__accordion extends zenario_nest {
	
	protected static $interfaceClassName = 'zenario_accordion_interface';
	
	public function setSlides($slides) {
		$this->slides = $slides;
	}
	
	public function initAnimationLibrary() {
		
		$this->showImagesOnTabs = (bool) $this->setting('show_images_on_slide_links');
		
		$tabOrd = 0;
		foreach ($this->slides as &$slide) {
			++$tabOrd;
			
			
			if (!isset($this->sections['Tab'])) {
				$this->sections['Tab'] = [];
			}
			
			$tabMergeFields =  [
				'TabID' => $this->containerId. '_tab_'. $tabOrd,
				'SlideID' => $this->containerId. '_slide_'. $tabOrd,
				'Class' => 'tab_'. $tabOrd. ' tab',
				'Slide_Class' => 'slide_'. $slide['slide_num']. ' '. $slide['css_class'],
				'Visible' => $tabOrd === 1 && $this->setting('accordion_1st_open'),
				'Tab_Name' => $this->formatTitleText($slide['slide_label'], true)
			];
			
			$this->addSlideImage($tabMergeFields, $slide, $tabOrd);
			
			$this->sections['Tab'][$slide['slide_num']] = $tabMergeFields;
		}
		

		$jsDir = ze::moduleDir('zenario_nest', 'js');
		$this->requireJsLib($jsDir. '/accordion.interface.min.js');
		
		$this->callScript(static::$interfaceClassName, 'show',
			$this->containerId,
			$tabOrd,
			(bool) $this->setting('accordion_1st_open'),
			(bool) $this->setting('accordion_all_can_close'),
			(bool) $this->setting('accordion_open_multiple')
		);		
		
		return true;
	}

	
	public function showSlot() {
		$this->mergeFields['Tabs'] = $this->sections['Tab'] ?? null;
		
		$hide = false;
		foreach ($this->slides as &$slide) {
			$slideNum = $slide['slide_num'];
			$this->mergeFields['Tabs'][$slideNum]['Plugins'] = ze::$slotContents[$this->slotName]->eggsOnSlideNum($slideNum);
			#$this->mergeFields['Tabs'][$slideNum]['Hidden'] = $hide;
			#
			#//Hide the slides after slide one, until the jQuery slideshow Plugin kicks in and overrides this.
			#$hide = true;
		}
		
		$this->twigFramework($this->mergeFields);
	}
	
}
