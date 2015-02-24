/*
 * Copyright (c) 2015, Tribal Limited
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
(function(zenario, zenario_news_ticker, undefined) {


zenario_news_ticker.textMessage = new Array();
zenario_news_ticker.textLink = new Array();

zenario_news_ticker.delay = 3500;
zenario_news_ticker.speed = 60;

zenario_news_ticker.numberOfTexts = 0;
zenario_news_ticker.longestTextIndex = 0;

zenario_news_ticker.scrollerWidth = 0;

zenario_news_ticker.textPointer = 0;
zenario_news_ticker.nextChar = -1;
zenario_news_ticker.cursor = "_";
zenario_news_ticker.scrollerText = "";
zenario_news_ticker.url = zenario_news_ticker.textLink[zenario_news_ticker.textPointer];
zenario_news_ticker.t = "";

zenario_news_ticker.add = function($text,$link){
	zenario_news_ticker.textMessage.push($text);
	zenario_news_ticker.textLink.push($link);
}

zenario_news_ticker.start = function(){

	zenario_news_ticker.numberOfTexts = zenario_news_ticker.textMessage.length-1; 
	for ( i = 0; i <= zenario_news_ticker.numberOfTexts; i++ ){
		if (zenario_news_ticker.textMessage[i].length > zenario_news_ticker.textMessage[zenario_news_ticker.longestTextIndex].length ){
			zenario_news_ticker.longestTextIndex = i; 
		}
	}

	zenario_news_ticker.scrollerWidth = zenario_news_ticker.textMessage[zenario_news_ticker.longestTextIndex].length + 1;
	zenario_news_ticker.url = zenario_news_ticker.textLink[zenario_news_ticker.textPointer];

	$('#input_banner').click(function(){
			if(zenario_news_ticker.url){
				clearTimeout(zenario_news_ticker.t);
				location.href = zenario_news_ticker.url;
			}
	});


	t = setTimeout( "zenario_news_ticker.animate()", 200);
}

zenario_news_ticker.animate = function(){
	if ( zenario_news_ticker.scrollerText != zenario_news_ticker.textMessage[zenario_news_ticker.textPointer] & zenario_news_ticker.scrollerText.length != zenario_news_ticker.textMessage[zenario_news_ticker.textPointer].length ) {
		zenario_news_ticker.nextStep();
	} else {
		$('#input_banner').val(zenario_news_ticker.textMessage[zenario_news_ticker.textPointer]);
		if ( zenario_news_ticker.textPointer != zenario_news_ticker.numberOfTexts ){
			zenario_news_ticker.textPointer++;
		} else {
			zenario_news_ticker.textPointer = 0;
		}
		zenario_news_ticker.nextChar = -1;
		zenario_news_ticker.scrollerText = "";
		zenario_news_ticker.t = setTimeout( "zenario_news_ticker.nextStep()", zenario_news_ticker.delay );
	}
}

zenario_news_ticker.nextStep = function() {
	zenario_news_ticker.nextChar++;
	zenario_news_ticker.url = zenario_news_ticker.textLink[zenario_news_ticker.textPointer];
	zenario_news_ticker.scrollerText += zenario_news_ticker.textMessage[zenario_news_ticker.textPointer].charAt( zenario_news_ticker.nextChar );
	$('#input_banner').val(zenario_news_ticker.scrollerText + zenario_news_ticker.cursor);
	zenario_news_ticker.t = setTimeout( "zenario_news_ticker.animate()", zenario_news_ticker.speed );
}



})(zenario, zenario_news_ticker);