/*
 * Copyright (c) 2023, Tribal Limited
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


//Rollover logic for banners.
//This code will be inlined, so the rollovers work instantly, without needing to wait for any JavaScript libraries to load.

//The document, containerId and sourceCodeName variables will all be set by the banner module using a string replace.
(function(document, containerId, sourceCodeName) {
	
	var i = 0,
		
		//This script works by using two different types of DOM element that have been pre-written to the page.
		//There are the visible DOM elements, that we're going to copy attributes to.
		//Then there are hidden DOM elements, that exist to preload the alternate version(s) of the image, that we're going to copy attributes from.
		copyTo = document.getElementById(containerId + '_img'),
		copyFrom = document.getElementById(containerId + '_' + sourceCodeName),
		
		//Define a function to copy all attributes that are relevant to rollovers from one image to the other.
		//Note: It might be that the dom element to copy from does not exist, as sometimes the preloaded images have less sources than the
		//initially visible DOM element. In this case the unused things should just be blanked out.
		changeAttribute = function(copyTo, copyFrom, name) {
			copyTo[name] = copyFrom && copyFrom[name] || '';
		},
		update = function(copyTo, copyFrom) {
			changeAttribute(copyTo, copyFrom, 'src');
			changeAttribute(copyTo, copyFrom, 'srcset');
			changeAttribute(copyTo, copyFrom, 'media');
			changeAttribute(copyTo, copyFrom, 'type');
		};
	
	//Update the image
	update(copyTo, copyFrom);
	
	//For every source for the image, update it with whatever values are for the source for the preloaded image
	while (copyTo = document.getElementById(containerId + '_source_' + ++i)) {
		copyFrom = document.getElementById(containerId + '_' + sourceCodeName + '_source_' + i);
		update(copyTo, copyFrom);
	}
})();