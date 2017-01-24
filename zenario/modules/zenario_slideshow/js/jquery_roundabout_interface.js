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

(function(zenario, zenario_roundabout_interface, undefined) {

var methods = methodsOf(zenario_roundabout_interface);


methods.show = function(containerId, opt, startingSlide) {
	
	var options = {
		childSelector: 'div',
		startingChild: startingSlide,
		shape: opt.shape,
		tilt: opt.tilt
	};
	
	if (opt.timeout) {
		options.autoplay = true;
		options.autoplayDuration = opt.speed;
		options.autoplayPauseOnHover = opt.pause;
	}
	
	
	$('#' + containerId + ' .nest_plugins_wrap .nest_plugins').css('display', '');
	$('#' + containerId + ' .nest_plugins_wrap')
		.roundabout(options)
		.bind('animationEnd', function() {
			var tab = 1 * $('#' + containerId + ' .nest_plugins_wrap').roundabout('getChildInFocus') + 1,
				sel = '#' + containerId + ' .tab_' + tab;
			
			$('#' + containerId + ' .tab_on').not(sel).removeClass('tab_on').addClass('tab');
			$(sel).removeClass('tab').addClass('tab_on');
		});
}

methods.page = function(el, i, mouseover) {
	var slotName = zenario.getSlotnameFromEl(el);
	
	if (mouseover) {
		this.pause(slotName);
	}
	
	$('#plgslt_' + slotName + ' .nest_plugins_wrap').roundabout('animateToChild', i);
	
	return false;
}

methods.next = function(containerId) {
	$('#' + containerId + ' .nest_plugins_wrap').roundabout('animateToNextChild');
	return false;
}

methods.prev = function(containerId) {
	$('#' + containerId + ' .nest_plugins_wrap').roundabout('animateToPreviousChild');
	return false;
}

methods.pause = function(containerId) {
	$('#' + containerId + ' .nest_plugins_wrap').roundabout('stopAutoplay');
}

methods.resume = function(containerId) {
	$('#' + containerId + ' .nest_plugins_wrap').roundabout('startAutoplay');
}



})(zenario, window.zenario_roundabout_interface = function() {});