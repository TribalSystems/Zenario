<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


//Include Zenario's custom logic for Twig
require_once CMS_ROOT. 'zenario/includes/twig.inc.php';

//Include a Twig extension that enables support for breaks and continues
require CMS_ROOT. 'zenario/libs/manually_maintained/mit/twig-breakandcontinue/mnbreakandcontinue/twigextensions/MNBreakAndContinueTwigExtension.php';



class phi {

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
	//preg_replace() in the Phi_Twig_Cache class
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
			\ze\cache::cleanDirs();
		}
		
		
		//Initialise a Twig instance for Phi
		self::$twig = new \Twig_Environment(new \Zenario_Twig_String_Loader(), [
			'cache' => new \Zenario_Phi_Twig_Cache(),
			'autoescape' => false,
			'debug' => false,
			'auto_reload' => true
		]);
		
		//Include a Twig extension that enables support for breaks and continues
		self::$twig->addExtension(new \MNBreakAndContinueTwigExtension());
		
		
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
			self::$twig->addFunction(new \Twig_SimpleFunction($phpName, $phpName));
		}
		
		$whitelist = [
			'paste' => 'ze\\phi::paste',
			'dump' => 'ze\\phi::varDump',
			'print' => 'ze\\phi::varDump',
			'var_dump' => 'ze\\phi::varDump',
			'setValue' => 'ze\\phi::setValue',
			'_zPhiGetRV_' => 'ze\\phi::getReturnValue',
			'_zPhiRV_' => 'ze\\phi::returnValue',
			'_zPhiR_' => 'return',
			'_zPhiSNAK_' => 'ze\\phi::_zPhiSNAK_',
			'_zPhiSAK_' => 'ze\\phi::_zPhiSAK_',
			'_zPhiSAKEnd_' => 'ze\\phi::_zPhiSAKEnd_',
			'ceiling' => 'ceil',
			'count' => 'count',
			'c' => 'array',
			'trim' => 'trim',
			'list' => 'array',
			'length' => 'ze\\phi::length',
			'rev' => 'ze\\phi::reverse',
			'reverse' => 'ze\\phi::reverse',
			'sort' => 'ze\\phi::sort',
			'shuffle' => 'ze\\phi::shuffle',
			'sum' => 'ze\\phi::sum',
			'max' => 'ze\\phi::max',
			'min' => 'ze\\phi::min',
			'mean' => 'ze\\phi::mean',
			'median' => 'ze\\phi::median',
			'mode' => 'ze\\phi::mode',
			'lowerQuartile' => 'ze\\phi::firstQuartile',
			'firstQuartile' => 'ze\\phi::firstQuartile',
			'thirdQuartile' => 'ze\\phi::thirdQuartile',
			'upperQuartile' => 'ze\\phi::thirdQuartile',
			'standardDev' => 'ze\\phi::standardDev',
			'product' => 'ze\\phi::product',
			'factorial' => 'ze\\phi::factorial',
			'gcd' => 'ze\\phi::gcd',
			'lcm' => 'ze\\phi::lcm',
			'int' => 'ze\\phi::int',
			'float' => 'ze\\phi::float',
			'string' => 'ze\\phi::string'
		];
		
		//If assetwolf is running and has been included, make its getHistoricValue() function available
		if (class_exists('ze\\assetwolf')) {
			$whitelist['getMinValue'] = 'ze\\assetwolf::getMinValue';
			$whitelist['getMaxValue'] = 'ze\\assetwolf::getMaxValue';
			$whitelist['getHistoricValue'] = 'ze\\assetwolf::getHistoricValue';
			$whitelist['getMetricValue'] = 'ze\\assetwolf::getMetricValue';
			$whitelist['getTimestamp'] = 'ze\\assetwolf::getTimestamp';
			$whitelist['getAllChildIds'] = 'ze\\assetwolf::getAllChildIds';
			$whitelist['getImmediateChildIds'] = 'ze\\assetwolf::getImmediateChildIds';
			$whitelist['getInheritedMetadata'] = 'ze\\assetwolf::getInheritedMetadata';
			$whitelist['getParentNodeId'] = 'ze\\assetwolf::getParentNodeId';
			$whitelist['getMetadata'] = 'ze\\assetwolf::getMetadata';
			$whitelist['query'] = 'ze\\assetwolf::query';
		}

		foreach ($whitelist as $twigName => $phpName) {
			self::$twig->addFunction(new \Twig_SimpleFunction($twigName, $phpName));
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
		return \NumbersPHP\Statistic::product($a);
	}
	public static function factorial($n) {
		return \NumbersPHP\Statistic::factorial($n);
	}
	public static function gcd($a, $b) {
		return \NumbersPHP\Statistic::gcd($a, $b);
	}
	public static function lcm($a, $b) {
		return \NumbersPHP\Statistic::lcm($a, $b);
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
		if ($a !== []) return min($a);
	}
	public static function max(...$in) {
		$a = [];
		self::toArray($in, $a);
		if ($a !== []) return max($a);
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
			return \NumbersPHP\Statistic::median($a);
		}
	}
	public static function mode(...$in) {
		$a = [];
		self::toArray($in, $a);
		
		if ($a === []) {
			return null;
		
		} else {
			return \NumbersPHP\Statistic::mode($a);
		}
	}
	public static function standardDev(...$in) {
		$a = [];
		self::toArray($in, $a);
		
		if ($a === []) {
			return null;
		
		} else {
			return \NumbersPHP\Statistic::standardDev($a);
		}
	}
	public static function firstQuartile(...$in) {
		$a = [];
		self::toArray($in, $a);
		
		if ($a === []) {
			return null;
		
		} else {
			return \NumbersPHP\Statistic::firstQuartile($a);
		}
	}
	public static function thirdQuartile(...$in) {
		$a = [];
		self::toArray($in, $a);
		
		if ($a === []) {
			return null;
		
		} else {
			return \NumbersPHP\Statistic::thirdQuartile($a);
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
	
	
	
	
	
	public static function runTwig($twigCode, &$outputs, $vars = [], $testing = false) {
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
	
	public static function carefullyRunTwig($twigCode, &$outputs, $vars = []) {
		try {
			return self::runTwig($twigCode, $outputs, $vars);
		} catch (\Exception $e) {
			return null;
		}
	}
	
	public static function runPhi($phiCode, &$outputs, $vars = [], $allowFunctions = false) {
		return \ze\phiParser::runPhi($phiCode, $outputs, $vars, $allowFunctions);
	}
	
	public static function carefullyRunPhi($phiCode, &$outputs, $vars = [], $allowFunctions = false) {
		return \ze\phiParser::carefullyRunPhi($phiCode, $outputs, $vars, $allowFunctions);
	}
	
	public static function testPhi($phiCode, &$outputs, $vars = [], $allowFunctions = false) {
		return \ze\phiParser::testPhi($phiCode, $outputs, $vars, $allowFunctions);
	}
	
	public static function phiToTwig($phiCode, $allowFunctions = false, $preserveLineBreaks = false) {
		return \ze\phiParser::phiToTwig($phiCode, $allowFunctions, $preserveLineBreaks);
	}
}
\ze\phi::init();
