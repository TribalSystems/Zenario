<?php
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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


//Include a Twig extension that enables support for breaks and continues
require CMS_ROOT. 'zenario/libraries/mit/twig-breakandcontinue/mnbreakandcontinue/twigextensions/MNBreakAndContinueTwigExtension.php';


//A version of Zenario_Twig_Cache that uses preg_replace() on the generated code as a hack to implement the following two features:
	//Replace calls to twig_get_attribute() with the ?? operator for better efficiency
	//Implement the ability to set the value of array elements
//Note that if you use this class, you can no longer pass objects as inputs as the preg_replace()s break support for this
class Zenario_Phi_Twig_Cache extends Zenario_Twig_Cache {
	
    public function write($key, $content) {
    	
		//Replace calls to twig_get_attribute() with the ?? operator for better efficiency
    	do {
    		$count = 0;
	    	$content = preg_replace('@\btwig_get_attribute\(\$this\-\>env, \$this\-\>getSourceContext\(\), \(?\$context([\[\]\'"\w-]+) \?\? null\)?, ([\'"]?[\w-]+[\'"]?), array\(\)(, [\'"]array[\'"]|)\)@', '(\$context$1[$2] ?? null)', $content, -1, $count);
	    } while ($count > 0);
    	
		//Implement the ability to set the value of array elements
		//Twig doesn't support setting array keys, so we'll need to use a hack to work around this
    	do {
    		$count = 0;
	    	$content = preg_replace('@\bzenario_phi\:\:_zPhiSAK_\(\(?\$context([\[\]\'"\w-]+)( \?\? null|)\)?, (.*?), zenario_phi\:\:_zPhiSAKEnd_\(\)\)\;@', '$context$1 = $3;', $content, -1, $count);
	    } while ($count > 0);
    	do {
    		$count = 0;
	    	$content = preg_replace('@\bzenario_phi\:\:_zPhiSNAK_\(\(?\$context([\[\]\'"\w-]+)( \?\? null|)\)?, (.*?), zenario_phi\:\:_zPhiSAKEnd_\(\)\)\;@', '$context$1[] = $3;', $content, -1, $count);
	    } while ($count > 0);
	    
    	parent::write($key, $content);
    }
}



class zenario_phi {

	private static $initRun = false;
	private static $twig;
	private static $vars = [
		'e' => M_E,
		'pi' => M_PI,
		'euler' => M_EULER,
		'Inf' => INF,
		'Infinity' => INF
	];
	
	
	private static $testing = false;
	private static $output = null;
	private static $outputs = [];
	public static $varDumps = [];	//N.b. this is public because it's referenced in zenario_phi_parser

	public static function getReturnValue($var) {
		return self::$output;
	}

	public static function returnValue($var) {
		self::$output = $var;
		return null;
		//ob_clean();
	}

	public static function varDump() {
		if (self::$testing) {
			self::$varDumps[] = func_get_args();
		}
	}

	public static function setValue($key, $value) {
		self::$outputs[$key] = $value;
	}
	
	
	//Dummy functions; these won't actually be called but are reserved words used as a flag for a
	//preg_replace() in the Zenario_Phi_Twig_Cache class
	public static function _zPhiSNAK_(&$array, $value) {
		$array[] = $value;
	}
	public static function _zPhiSAK_(&$arrayElement, $value) {
		$arrayElement = $value;
	}
	public static function _zPhiSAKEnd_() {
	}
	
	
	public static function init() {
		
		//Only allow this to be run once
		if (self::$initRun) {
			return;
		}
		self::$initRun = true;
		
		
		//Check that the cache/frameworks directory has been created
		if (!is_dir(CMS_ROOT. 'cache/frameworks')) {
			cleanCacheDir();
		}
		
		
		//Initialise a Twig instance for Phi
		self::$twig = new Twig_Environment(new Zenario_Twig_String_Loader(), array(
			'cache' => new Zenario_Phi_Twig_Cache(),
			'autoescape' => false,
			'debug' => false,
			'auto_reload' => true
		));
		
		//Include a Twig extension that enables support for breaks and continues
		self::$twig->addExtension(new MNBreakAndContinueTwigExtension());
		
		
		//Set up the whitelist of allowed functions
		$whitelist = [
			//http://php.net/manual/en/book.math.php
			'abs',
			'acos',
			'acosh',
			'array_merge',
			'asin',
			'asinh',
			'atan',
			'atan2',
			'atanh',
			'base_convert',
			'bindec',
			'ceil',
			'cos',
			'cosh',
			'decbin',
			'dechex',
			'decoct',
			'deg2rad',
			'floor',
			'hexdec',
			'is_finite',
			'is_infinite',
			'is_nan',
			'log',
			'log10',
			'octdec',
			'rad2deg',
			'round',
			'sin',
			'sinh',
			'sqrt',
			'tan',
			'tanh'
		];

		foreach ($whitelist as $phpName) {
			self::$twig->addFunction(new Twig_SimpleFunction($phpName, $phpName));
		}
		
		$whitelist = [
			'paste' => 'zenario_phi::paste',
			'dump' => 'zenario_phi::varDump',
			'print' => 'zenario_phi::varDump',
			'var_dump' => 'zenario_phi::varDump',
			'setValue' => 'zenario_phi::setValue',
			'_zPhiGetRV_' => 'zenario_phi::getReturnValue',
			'_zPhiRV_' => 'zenario_phi::returnValue',
			'_zPhiR_' => 'return',
			'_zPhiSNAK_' => 'zenario_phi::_zPhiSNAK_',
			'_zPhiSAK_' => 'zenario_phi::_zPhiSAK_',
			'_zPhiSAKEnd_' => 'zenario_phi::_zPhiSAKEnd_',
			'ceiling' => 'ceil',
			'count' => 'count',
			'c' => 'array',
			'trim' => 'trim',
			'list' => 'array',
			'length' => 'zenario_phi::length',
			'rev' => 'zenario_phi::reverse',
			'reverse' => 'zenario_phi::reverse',
			'sort' => 'zenario_phi::sort',
			'shuffle' => 'zenario_phi::shuffle',
			'sum' => 'zenario_phi::sum',
			'max' => 'zenario_phi::max',
			'min' => 'zenario_phi::min',
			'mean' => 'zenario_phi::mean',
			'median' => 'zenario_phi::median',
			'mode' => 'zenario_phi::mode',
			'lowerQuartile' => 'zenario_phi::firstQuartile',
			'firstQuartile' => 'zenario_phi::firstQuartile',
			'thirdQuartile' => 'zenario_phi::thirdQuartile',
			'upperQuartile' => 'zenario_phi::thirdQuartile',
			'standardDev' => 'zenario_phi::standardDev',
			'product' => 'zenario_phi::product',
			'factorial' => 'zenario_phi::factorial',
			'gcd' => 'zenario_phi::gcd',
			'lcm' => 'zenario_phi::lcm',
			'int' => 'zenario_phi::int',
			'float' => 'zenario_phi::float',
			'string' => 'zenario_phi::string'
		];
		
		//If assetwolf is running and has been included, make its getHistoricValue() function available
		if (class_exists('assetwolf_common_fun')) {
			$whitelist['getHistoricValue'] = 'assetwolf_common_fun::getHistoricValue';
			$whitelist['getTimestamp'] = 'assetwolf_common_fun::getTimestamp';
		}

		foreach ($whitelist as $twigName => $phpName) {
			self::$twig->addFunction(new Twig_SimpleFunction($twigName, $phpName));
		}
	}
	
	public static function toArray($in, &$out, $topLevel = true) {
		if (is_array($in)) {
			if ($topLevel
			 && isset($in[0])
			 && !isset($in[1])
			 && is_array($in[0])) {
				$out = $in[0];
				return;
			}
			foreach ($in as $n) {
				self::toArray($n, $out, false);
			}
		
		} elseif (is_numeric($in)) {
			$out[] = $in;
		}
	}
	
	public static function int($in) {
		return (int) $in;
	}
	public static function float($in) {
		return (float) $in;
	}
	public static function string($in) {
		return (string) $in;
	}

	//https://github.com/powder96/numbers.php
	public static function product(...$in) {
		$a = [];
		self::toArray($in, $a);
		return NumbersPHP\Statistic::product($a);
	}
	public static function factorial($n) {
		return NumbersPHP\Statistic::factorial($n);
	}
	public static function gcd($a, $b) {
		return NumbersPHP\Statistic::gcd($a, $b);
	}
	public static function lcm($a, $b) {
		return NumbersPHP\Statistic::lcm($a, $b);
	}

	
	public static function reverse(...$in) {
		$a = [];
		self::toArray($in, $a);
		return array_reverse($a);
	}
	public static function sort(...$in) {
		$a = [];
		self::toArray($in, $a);
		sort($a);
		return $a;
	}
	public static function shuffle(...$in) {
		$a = [];
		self::toArray($in, $a);
		shuffle($a);
		return $a;
	}
	public static function min(...$in) {
		$a = [];
		self::toArray($in, $a);
		return min($a);
	}
	public static function max(...$in) {
		$a = [];
		self::toArray($in, $a);
		return max($a);
	}
	public static function sum(...$in) {
		$a = [];
		self::toArray($in, $a);
		return array_sum($a);
	}
	public static function mean(...$in) {
		$a = [];
		self::toArray($in, $a);
		
		if ($a === []) {
			return null;
		
		} else {
			return array_sum($a) / count($a);
		}
	}
	public static function median(...$in) {
		$a = [];
		self::toArray($in, $a);
		
		if ($a === []) {
			return null;
		
		} else {
			return NumbersPHP\Statistic::median($a);
		}
	}
	public static function mode(...$in) {
		$a = [];
		self::toArray($in, $a);
		
		if ($a === []) {
			return null;
		
		} else {
			return NumbersPHP\Statistic::mode($a);
		}
	}
	public static function standardDev(...$in) {
		$a = [];
		self::toArray($in, $a);
		
		if ($a === []) {
			return null;
		
		} else {
			return NumbersPHP\Statistic::standardDev($a);
		}
	}
	public static function firstQuartile(...$in) {
		$a = [];
		self::toArray($in, $a);
		
		if ($a === []) {
			return null;
		
		} else {
			return NumbersPHP\Statistic::firstQuartile($a);
		}
	}
	public static function thirdQuartile(...$in) {
		$a = [];
		self::toArray($in, $a);
		
		if ($a === []) {
			return null;
		
		} else {
			return NumbersPHP\Statistic::thirdQuartile($a);
		}
	}
	
	public static function length($var) {
		if (is_string($var)) {
			return strlen($var);
		} else {
			return count($var);
		}
	}
	
	public static function paste() {
		return implode(' ', func_get_args());
	}
	
	
	
	
	
	public static function runTwig($twigCode, &$outputs, $vars = array(), $testing = false) {
		self::$testing = $testing;
		self::$output = null;
		self::$outputs = &$outputs;
		self::$varDumps = [];
		
		if (empty($vars)) {
			$vars = self::$vars;
		} else {
			$vars = array_merge($vars, self::$vars);
		}
		
		self::$twig->render($twigCode, $vars);
		
		$emptyArray = [];
		self::$outputs = &$emptyArray;
		self::$testing = false;
		return self::$output;
	}
	
	public static function carefullyRunTwig($twigCode, &$outputs, $vars = array()) {
		try {
			return self::runTwig($twigCode, $outputs, $vars);
		} catch (Exception $e) {
			return null;
		}
	}
	
	public static function runPhi($phiCode, &$outputs, $vars = array(), $allowFunctions = false) {
		require_once CMS_ROOT. 'zenario/includes/twig.phi.parser.inc.php';
		return zenario_phi_parser::runPhi($phiCode, $outputs, $vars, $allowFunctions);
	}
	
	public static function carefullyRunPhi($phiCode, &$outputs, $vars = array(), $allowFunctions = false) {
		require_once CMS_ROOT. 'zenario/includes/twig.phi.parser.inc.php';
		return zenario_phi_parser::carefullyRunPhi($phiCode, $outputs, $vars, $allowFunctions);
	}
	
	public static function testPhi($phiCode, &$outputs, $vars = array(), $allowFunctions = false) {
		require_once CMS_ROOT. 'zenario/includes/twig.phi.parser.inc.php';
		return zenario_phi_parser::testPhi($phiCode, $outputs, $vars, $allowFunctions);
	}
	
	public static function phiToTwig($phiCode, $allowFunctions = false, $preserveLineBreaks = false) {
		require_once CMS_ROOT. 'zenario/includes/twig.phi.parser.inc.php';
		return zenario_phi_parser::phiToTwig($phiCode, $allowFunctions, $preserveLineBreaks);
	}
}
zenario_phi::init();




