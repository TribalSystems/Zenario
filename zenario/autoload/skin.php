<?php
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

namespace ze;

class skin {



	
	
	//	Skins  //

	public static function details($skinId) {
		return \ze\row::get('skins', ['id', 'name', 'display_name', 'extension_of_skin', 'import', 'css_class', 'missing'], ['id' => $skinId]);
	}

	public static function name($familyName, $skinName) {
		return \ze\row::get('skins', ['id', 'name', 'display_name', 'extension_of_skin', 'import', 'css_class', 'missing'], ['name' => $skinName]);
	}

	public static function path($skinName = false) {
		return 'zenario_custom/skins/'. ($skinName ?: \ze::$skinName). '/';
	}

	public static function url($skinName = false) {
		return 'zenario_custom/skins/'. rawurlencode(($skinName ?: \ze::$skinName)). '/';
	}
	

	//Find the lowest common denominator of two numbers
	public static function rationalNumber(&$a, &$b) {
	  for ($i = min($a, $b); $i > 1; --$i) {
		  if (($a % $i == 0)
		   && ($b % $i == 0)) {
			  $a = (int) ($a / $i);
			  $b = (int) ($b / $i);
		  }
	  }
	}

	//Give a grid's cell a class-name based on how many columns it takes up, and the ratio out of the total width that it takes up
	public static function rationalNumberGridClass($a, $b) {
		$w = $a;
		\ze\skin::rationalNumber($a, $b);
		return 'span span'. $w. ' span'. $a. '_'. $b;
	}
	



}