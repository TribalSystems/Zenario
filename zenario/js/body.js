/*
 * Copyright (c) 2020, Tribal Limited
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


//These functions are included on every page, just after the body tag, so they're written to be
//as small as possible when minified
(function(document, window, zenarioL, script) {
	
	//Add polyfills for IE
	if (!document.currentScript) {
		script = document.createElement('script');
		script.src = URLBasePath + 'zenario/js/ie.wrapper.js.php';
		document.head.appendChild(script);
	}
	
	//This function sets/modifies a CSS class on the document.body.
	var lSet = zenarioL.set = function(condition, metClassName, notMetClassName, tmp) {
		
			if (!condition) {
				tmp = metClassName;
				metClassName = notMetClassName;
				notMetClassName = tmp;
			}
	
			document.body.className = document.body.className
				.replace(
					new RegExp('\\b(' + notMetClassName + ')\\b', 'g'),
					''
				)
				+ ' '
				+ metClassName;
	
			zenarioL[notMetClassName] = !(zenarioL[metClassName] = true);
		},

		//Check the current width of the window, and set various CSS classes
		//on the body depending on whether the grid is currently responsive or not.
		lResize = zenarioL.resize = function(zenarioGrid) {
		
			var lSet = zenarioL.set,
				responds = zenarioGrid.responsive,
				windowWidth = window.innerWidth,
				bp1 = responds && zenarioGrid.bp1,
				bp2 = responds && zenarioGrid.bp2;
		
			lSet(responds, 'responds', 'unresponsive');
			lSet(zenarioGrid.fluid, 'fluid', 'fixed');
		
			lSet(responds && windowWidth < zenarioGrid.minWidth, 'mobile', 'desktop');
			lSet(!responds || windowWidth >= zenarioGrid.maxWidth, 'fullsize', 'notfullsize');
		
			if (bp1) {
				lSet(windowWidth < bp1, 'underBP1', 'overBP1');
			}
		
			if (bp2) {
				lSet(windowWidth < bp2, 'underBP2', 'overBP2');
			}
		},

		//This function sets the grid properties.
		lInit = zenarioL.init = function(zenarioGrid) {
			lResize(window.zenarioGrid = zenarioGrid);
		};
	
	//Start off with empty settings for the grid
	lInit({});
	
	
	//Set a CSS class on the body, depending on whether JavaScript is enabled
	lSet(true, 'js', 'no_js');
	
	//Add a CSS class for whether this is retina or not.
	//(Note that this won't work for IE 10 or earlier).
	lSet(window.devicePixelRatio > 1, 'retina', 'not_retina');
	
	//Add a CSS class for whether this is a touch screen or not
	lSet(('ontouchstart' in window) || navigator.msMaxTouchPoints, 'touch', 'no_touching');
	
	
	//Create a function that runs code as soon as the DOM is ready.
	//This uses jQuery's $(function() { ... }) function if possible, however if jQuery is not yet loaded
	//then it falls back to the addEventListener() function.
	window.zOnLoad = function(runMe) {
		if (window.$) {
			$(runMe);
		} else if (window.addEventListener) {
			addEventListener('DOMContentLoaded', runMe);
		} else {
			runMe();
		}
	};


})(document, window, window.zenarioL = {});