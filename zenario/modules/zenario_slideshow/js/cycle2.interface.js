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
(function(zenario, zenario_cycle2_interface, undefined) {
	


zenario_cycle2_interface.show = function(el, opt, startingSlide) {
	var containerId = zenario.getContainerIdFromEl(el),
		$slideShow = $('#' + containerId + ' .nest_plugins_wrap'),
		loopSlides = opt.next_prev_buttons_loop,
		$prevButton = $('#' + containerId + '-prevButton'),
		$nextButton = $('#' + containerId + '-nextButton');
	
	$slideShow
		.data('cycle-slides', 'div.nest_plugins')
		.cycle({
			fx: opt.fx,
			sync: opt.sync,
			timeout: opt.timeout,
			speed: opt.speed,
			pauseOnHover: !!opt.pause,
			loop: loopSlides? 0 : 1,		//N.b. 0 = loop forever, 1 = go through the slides once then stop
			startingSlide: startingSlide,
			//autoHeight: 'container', //Has height issues when loading on firefox
			maxZ: 90, //100 is default and Admin controls have z-index 99
			autoHeight: 'container',
			log: false
		})
		.on('cycle-before', function(event, optionHash, outgoingSlideEl, incomingSlideEl, forwardFlag) {
			
			var slideNum = optionHash.slideNum,
				sel = '#' + containerId + ' .tab_' + slideNum;
		
			$('#' + containerId + ' .tab_on').not(sel).removeClass('tab_on').addClass('tab');
			$(sel).removeClass('tab').addClass('tab_on');
		})
		.on('cycle-update-view', function(event, optionHash, outgoingSlideEl, incomingSlideEl, forwardFlag) {
			
			var currSlide = optionHash.currSlide;
			
			if (!loopSlides) {
				if (currSlide == 0) {
					$prevButton.addClass('prev_disabled');
					$slideShow.data('zenario-prev_disabled', '1');
				} else {
					$prevButton.removeClass('prev_disabled');
					$slideShow.data('zenario-prev_disabled', '');
				}
				
				if (currSlide == optionHash.slideCount - 1) {
					$nextButton.addClass('next_disabled');
					$slideShow.data('zenario-next_disabled', '1');
				} else {
					$nextButton.removeClass('next_disabled');
					$slideShow.data('zenario-next_disabled', '');
				}
			}
		});
};

zenario_cycle2_interface.page = function(el, i, mouseover) {
	var containerId = zenario.getContainerIdFromEl(el),
		$slideShow = $('#' + containerId + ' .nest_plugins_wrap');
	
	$slideShow.cycle('goto', i);
	
	if (mouseover) {
		this.pause(containerId);
	}
	
	return false;
};

zenario_cycle2_interface.next = function(el) {
	var containerId = zenario.getContainerIdFromEl(el),
		$slideShow = $('#' + containerId + ' .nest_plugins_wrap');
	
	if (!$slideShow.data('zenario-next_disabled')) {
		$slideShow.cycle('next');
	}
	
	return false;
};

zenario_cycle2_interface.prev = function(el) {
	var containerId = zenario.getContainerIdFromEl(el),
		$slideShow = $('#' + containerId + ' .nest_plugins_wrap');
	
	if (!$slideShow.data('zenario-prev_disabled')) {
		$slideShow.cycle('prev');
	}
	
	return false;
};

zenario_cycle2_interface.pause = function(el) {
	var containerId = zenario.getContainerIdFromEl(el),
		$slideShow = $('#' + containerId + ' .nest_plugins_wrap');
	
	$slideShow.cycle('pause');
};

zenario_cycle2_interface.resume = function(el) {
	var containerId = zenario.getContainerIdFromEl(el),
		$slideShow = $('#' + containerId + ' .nest_plugins_wrap');
	
	$slideShow.cycle('resume');
};



})(zenario, window.zenario_cycle2_interface = function() {});