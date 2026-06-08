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
(function(zenario, zenario_accordion_interface, undefined) {
	
	var getContainerIdFromEl = zenario.getContainerIdFromEl;



zenario_accordion_interface.show = function(containerId, numTabs, opt1stOpen, optAllCanClose, optOpenMultiple, duration) {
	
	var i,
		$tabs = [],
		$slides = [],
		currentlyOpenSlides = {},
		openSlide = function(slideNum) {
			$tabs[slideNum - 1].removeClass('accordion_tab_closed').addClass('accordion_tab_open');
			$slides[slideNum - 1].slideDown(duration); 
			currentlyOpenSlides[slideNum] = true;
		},
		closeSlide = function(slideNum) {
			$tabs[slideNum - 1].removeClass('accordion_tab_open').addClass('accordion_tab_closed');
			$slides[slideNum - 1].slideUp(duration); 
			delete currentlyOpenSlides[slideNum];
		};
	
	//Track if the first slide starts open.
	//(The HTML will already be set by the twig framework, we just need to remember that it starts open.)
	if (opt1stOpen) {
		currentlyOpenSlides[1] = true;
	}
	
	for (i = 1; i <= numTabs; ++i) {
		$tabs.push($('#' + containerId + '_tab_' + i));
		$slides.push($('#' + containerId + '_slide_' + i));
	}
	
	for (i = 1; i <= numTabs; ++i) {
		(function(slideNum) {
			$tabs[slideNum - 1].on('click', function() {
				
				//Check which slide or slides are currently open
				var values = _.keys(currentlyOpenSlides),
					numOpenSlides = values.length,
					prevOpenedSlide = values[0],
					//If the user is clicking on the tab of a closed slide, that should be treated as a request to open it.
					//If the user is clicking on the tab of an open slide, that should be treated as a request to close it.
					isCloseAction = currentlyOpenSlides[slideNum];
				
				//Don't allow the last slide to close if that option was set.
				if (numOpenSlides == 1 && isCloseAction && opt1stOpen && !optAllCanClose) {
					return;
				}
				
				//If only one slide can be open at once, the previously opened slide should be shut
				//when the user tries to open a different one.
				if (numOpenSlides == 1 && !isCloseAction && !optOpenMultiple) {
					closeSlide(prevOpenedSlide);
				}
				
				
				//Open or close the slide that was just clicked on
				if (isCloseAction) {
					closeSlide(slideNum);
				} else {
					openSlide(slideNum);
				}
				
				
			});
		})(i);
		//N.b. we create and instantly call this function to break the reference on the "i" variable.
		//If we don't do this then the value of "i" above will always be the last value in the loop,
		//which isn't what we want.
	}
};


})(zenario, window.zenario_accordion_interface = function() {});


