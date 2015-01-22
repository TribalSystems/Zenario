<?php

// ----------------------------------------------------------------------------
// markItUp! BBCode Parser
// v 1.0.4
// Dual licensed under the MIT and GPL licenses.
// ----------------------------------------------------------------------------
// Copyright (C) 2008 Jay Salvat
// http://www.jaysalvat.com/
// http://markitup.jaysalvat.com/
// ----------------------------------------------------------------------------
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
// ----------------------------------------------------------------------------
// Thanks to Arialdo Martini for feedbacks.
// ----------------------------------------------------------------------------





function BBCode2Html(&$text, $enableColours, $enableImages, $enableLinks, $enableEmoticons) {
	
	$text = htmlspecialchars(trim($text));
	
	// BBCode to find...
	$in = array( 	 '/\[b\](.*?)\[\/b\]/ms',	
					 '/\[i\](.*?)\[\/i\]/ms',
					 '/\[u\](.*?)\[\/u\]/ms',
					 '/\[s\](.*?)\[\/s\]/ms',
					 '/\[sup\](.*?)\[\/sup\]/ms',
					 '/\[sub\](.*?)\[\/sub\]/ms',
					 '/\[big\](.*?)\[\/big\]/ms',
					 '/\[code\](.*?)\[\/code\]/ms',
					 '/\[small\](.*?)\[\/small\]/ms',
					 '/\[size\=?(\d*?)\](.*?)\[\/size\]/ms',
					 '/\[list\](.*?)\[\/list\]/ms',
					 '/\[olist\](.*?)\[\/olist\]/ms',
					 '/\[list\=?(\d*?)\](.*?)\[\/list\]/ms',
					 '/\[\*\](.*?)\[\/\*\]/ms',
					 '/\[br\]/ms',
					 '/\[b\]/ms'
	);
	
	// And replace them by...
	$out = array(	 '<strong>\1</strong>',
					 '<em>\1</em>',
					 '<u>\1</u>',
					 '\1',	//'<s>\1</s>',
					 '<sup>\1</sup>',
					 '<sub>\1</sub>',
					 '<big>\1</big>',
					 '<pre>\1</pre>',
					 '<small>\1</small>',
					 '<span style="font-size:\1">\2</span>',
					 '<ul>\1</ul>',
					 '<ol>\1</ol>',
					 '<ol start="\1">\2</ol>',
					 '<li>\1</li>',
					 '<br/>',
					 ''
	);
	
	$in[] = '/\[color\= ?(.*?)\](.*?)\[\/color\]/ms';
	if ($enableColours) {
		$out[] = '<span style="color:\1">\2</span>';
	} else {
		$out[] = '\2';
	}
	
	$in[] = '/\[img\](.*?)\[\/img\]/ms';
	if ($enableImages) {
		$out[] = '<img src="\1" alt="\1" />';
	} else {
		$out[] = '';
	}
	
	$in[] = '/\[url\](.*?)\[\/url\]/ms';
	$in[] = '/\[url\= ?(.*?)\](.*?)\[\/url\]/ms';
	if ($enableLinks) {
		$out[] = '<a href="\1">\1</a>';
		$out[] = '<a href="\1">\2</a>';
	} else {
		$out[] = '';
		$out[] = '';
	}
	
	
	$in[] = '/\[quote[^\]]*?\]/ms';
	$in[] = '/\[\/quote\]/ms';
	$out[] = '<blockquote><div class="quote">';
	$out[] = '</div></blockquote>';
	
	$text = preg_replace($in, $out, $text);
	
	//Add in emoticons
	if ($enableEmoticons) {
		$in = array();
		$out = array();
		$emoticons = array(
			'happy' => array(':)', '=)'),
			'unhappy' => array(':|', '=|'),
			'sad' => array(':(','=('),
			'grin' => array(':D', '=D'),
			'surprised' => array(':o',':O','=o', '=O'),
			'wink' => array(';)'),
			'halfhappy' => array('=/', '=\\', ':\\'),
			'tounge' => array(':P', ':p', '=P', '=p'),
			'lol' => array(':lol:'),
			'mad' => array(':x', ':X', ':@'),
			'rolleyes' => array(':roll:'),
			'cool' => array('8)', '8-)'));
		
		foreach ($emoticons as $name => $codes) {
			foreach ($codes as $code) {
				$in[] = $code;
				$out[] = '<img src="zenario/libraries/lgpl/punymce/trans.gif" alt="'. htmlspecialchars($code). '" class="emoticon '. $name. '"/>';
			}
		}
		
		$text = str_replace($in, $out, $text);
	}
	
	// paragraphs
	$text = str_replace("\r", "", $text);
	$text = "<p>". nl2br($text)."</p>";
	
}