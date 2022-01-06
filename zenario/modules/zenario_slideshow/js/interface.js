/*
 * Copyright (c) 2022, Tribal Limited
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
(function(zenario, zenario_slideshow, getContainerIdFromEl, undefined) {


zenario_slideshow.interfaces = {};

zenario_slideshow.show = function(className, containerId, fx, sync, timeout, speed, pause, nowrap, startingSlide) {

	(zenario_slideshow.interfaces[containerId] = new window[className])
		.show(containerId, fx, sync, timeout, speed, pause, nowrap, startingSlide);
};

zenario_slideshow.page = function(el, i, mouseover) {
	var containerId = getContainerIdFromEl(el);
		iface = zenario_slideshow.interfaces[containerId];
	
	iface && iface.page(containerId, i, mouseover);
	return false;
};

zenario_slideshow.next = function(el) {
	var containerId = getContainerIdFromEl(el);
		iface = zenario_slideshow.interfaces[containerId];
	
	iface && iface.next(containerId);
	return false;
};

zenario_slideshow.prev = function(el) {
	var containerId = getContainerIdFromEl(el);
		iface = zenario_slideshow.interfaces[containerId];
	
	iface && iface.prev(containerId);
	return false;
};

zenario_slideshow.pause = function(el) {
	var containerId = getContainerIdFromEl(el);
		iface = zenario_slideshow.interfaces[containerId];
	
	iface && iface.pause(containerId);
	return false;
};

zenario_slideshow.resume = function(el) {
	var containerId = getContainerIdFromEl(el);
		iface = zenario_slideshow.interfaces[containerId];
	
	iface && iface.resume(containerId);
	return false;
};



})(zenario, zenario_slideshow, zenario.getContainerIdFromEl);