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


//These functions are included on every page, just after the body tag, so they're written to be
//as small as possible when minified
(function(document, window, script) {
	
	//Add polyfills for IE
	if (!document.currentScript) {
		script = document.createElement('script');
		script.src = URLBasePath + 'zenario/js/ie.wrapper.js.php';
		document.head.appendChild(script);
	}
	
	//This function sets/modifies a CSS class on the document.body.
	var windowDotaddEventListener = window.addEventListener,
	
		//Create a function that runs code as soon as the DOM is ready.
		//This uses jQuery's $(function() { ... }) function if possible, however if jQuery is not yet loaded
		//then it falls back to the addEventListener() function.
		zOnLoad = window.zOnLoad = function(runMe) {
			if (window.$) {
				$(runMe);
			} else if (windowDotaddEventListener) {
				windowDotaddEventListener('DOMContentLoaded', runMe);
			} else {
				runMe();
			}
		},
		
		zenarioL = window.zenarioL = {},
		lSet = zenarioL.set = function(condition, metClassName, notMetClassName, tmp) {
		
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
				responsive = zenarioGrid.responsive,
				windowWidth = window.innerWidth;
		
			lSet(zenarioGrid.fluid, 'fluid', 'fixed');
		
			lSet(responsive && windowWidth < zenarioGrid.minWidth, 'mobile', 'desktop');
			lSet(!responsive || windowWidth >= zenarioGrid.maxWidth, 'fullsize', 'notfullsize');
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
	lSet(('ontouchstart' in window) || navigator.msMaxTouchPoints, 'touchscreen', 'non_touchscreen');
	
	
	
	//This file needs to be broken up into sections after it is minified.
	//The minifier has special logic specifically just for this.
	ZENARIO_END_OF_SECTION();
	
	
	//
	//	This section is only needed when hierarchical URLs are enabled.
	//	This section won't be written to the page if hierarchical URLs are switched off in the site settings!
	//
	
	
	//If the "Show menu structure in friendly URLs" setting is set, watch out for any links, e.g.:
		//<a href="#top">
	//that would break due to this setting being enabled, and automatically fix them.
	
	//Note: this can break some modules that depend on links like above remaining unedited, e.g. the mmenu
	//jquery library used in zenario_menu_responsive_push_pull. To work around this such links with the 
	//with a class starting with "mm-" will be ignored by the code below.
	
	//Another note: there is a copy of this logic in the zenario.addJQueryElements() function, in visitor.js
	zOnLoad(function(ai) {
		var anchors = document.querySelectorAll('a[href^="#"]:not([class^="mm-"])'),
		al = anchors.length,
		loc = location;
	
		for (ai = 0; ai < al; ++ai) {
			anchors[ai].href = loc.pathname + loc.search + anchors[ai].href.replace(URLBasePath, '');
		}
	});
	
	
	//This file needs to be broken up into sections after it is minified.
	//The minifier has special logic specifically just for this.
	ZENARIO_END_OF_SECTION();


})(document, window);
