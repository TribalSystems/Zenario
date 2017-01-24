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
(function(zenario, zenario_font_size, undefined) {


zenario_font_size.min = 0;
zenario_font_size.max = 0;
zenario_font_size.initialSize = 0;


zenario_font_size.increaseFontSize = function () {
	var size = parseInt($("body").css("font-size").replace("px",""));

	if (size<zenario_font_size.max) {
		size = size + 1;
	}
	
	size += "px";

	$("body").css("font-size",size)
}

zenario_font_size.resetFontSize = function () {
	$("body").css("font-size",zenario_font_size.initialSize)
}


zenario_font_size.decreaseFontSize = function () {
	var size = parseInt($("body").css("font-size").replace("px",""));

	if (size>zenario_font_size.min) {
		size = size - 1;
	}
	
	size += "px";
	
	$("body").css("font-size",size)
}

zenario_font_size.defaultFontSize = function () {

}

zenario_font_size.init = function (minIn,maxIn) {
	zenario_font_size.initialSize = parseInt($("body").css("font-size").replace("px",""));
	zenario_font_size.min = minIn;
	zenario_font_size.max = maxIn;
}



})(zenario, zenario_font_size);