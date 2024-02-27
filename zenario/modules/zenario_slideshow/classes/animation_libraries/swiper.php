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
		$this->requireJsLib($jsDir. '/swiper.bundle.js.php', 'zenario/libs/yarn/swiper/swiper-bundle.min.css');
		
		
		$opt = [
			//Set to true to enable continuous loop mode
			//Because of nature of how the loop mode works (it will rearrange slides), total number of slides must be >= slidesPerView * 2
			'loop' => (bool) $this->setting('swiper.loop'),
			
			//When enabled, will swipe slides only forward (one-way) regardless of swipe direction
			'oneWayMovement' => (bool) $this->setting('swiper.oneWayMovement'),
			
			//Duration of transition between slides (in ms)
			'speed' => (int) $this->setting('swiper.speed'),
			
			//Mobile options
			'slidesPerView' => ((int) $this->setting('swiper.mobile.slidesPerView')) ?: 1,
			'spaceBetween' => (int) $this->setting('swiper.mobile.spaceBetween')
		];
		
		//Are we on a responsive layout?
		if (!ze::$responsive) {
			//Always use the desktop options if not
			$opt['slidesPerView'] = ((int) $this->setting('swiper.desktop.slidesPerView')) ?: 1;
			$opt['spaceBetween'] = (int) $this->setting('swiper.desktop.spaceBetween');
		
		} else {
			//Otherwise set up all of the options for breakpoints
			
			//Mobile options
			$opt['slidesPerView'] = ((int) $this->setting('swiper.mobile.slidesPerView')) ?: 1;
			$opt['spaceBetween'] = (int) $this->setting('swiper.mobile.spaceBetween');
			
			//Desktop breakpoint and options
			$opt['breakpoints'] = [];
			$opt['breakpoints'][ze::$minWidth] = [
				'slidesPerView' => ((int) $this->setting('swiper.desktop.slidesPerView')) ?: 1,
				'spaceBetween' => (int) $this->setting('swiper.desktop.spaceBetween')
			];
			
			//Custom breakpoint and options
			if ($this->setting('swiper.custom_1')) {
				$opt['breakpoints'][(int) $this->setting('swiper.custom_1.breakpoint')] = [
					'slidesPerView' => ((int) $this->setting('swiper.custom_1.slidesPerView')) ?: 1,
					'spaceBetween' => (int) $this->setting('swiper.custom_1.spaceBetween')
				];
			}
		}
		
		//Set direction
		switch ($sVal = $this->setting('swiper.direction')) {
			case 'vertical':
				$opt['direction'] = $sVal;
				break;
			default:
				$opt['direction'] = 'horizontal';
		}
		
		//Transition effect
		switch ($sVal = $this->setting('swiper.effect')) {
			case 'fade':
			case 'cube':
			case 'coverflow':
			case 'flip':
				$opt['effect'] = $sVal;
				break;
			default:
				$opt['effect'] = 'slide';
		}
		
		//Auto-advance slides
		if ($this->setting('swiper.autoplay')) {
			$opt['autoplay'] = [
				'delay' => (int) $this->setting('swiper.autoplay.delay')
			];
		}
		
		$this->callScript(static::$interfaceClassName, 'show',
			$this->slotName,
			$this->containerId,
			$opt
		);
	}
}
