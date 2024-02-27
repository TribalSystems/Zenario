<?php
/*
 * Copyright (c) 2024, Tribal Limited
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
	private static $vars = [
		'e' => M_E,
		'pi' => M_PI,
		'euler' => M_EULER,
		'Inf' => INF,
		'Infinity' => INF
	];
	
	
	private static $twig;
	
	//A dummy filter, just used as a work-around to block other filters from working
	public static function dummyFilter() {
		return '';
	}
	
	protected static function blockFilter($name) {
		$dummyFilter = new \Twig\TwigFilter($name, ['\\ze\\phi', 'dummyFilter']);
		self::$twig->addFilter($dummyFilter);
	}
	
	
	private static $enableVarDumps = false;
	private static $suppressErrors = false;
	private static $outputs = [];
	public static $varDumps = [];	//N.b. this is public because it's referenced in zenario_phi_parser

	public static function varDump() {
		if (self::$enableVarDumps) {
			self::$varDumps[] = func_get_args();
		}
	}

	public static function suppressErrors() {
		return self::$suppressErrors;
	}

	public static function setValue($key = '', $value = null) {
		self::$outputs['v'. $key] = $value;
	}

	public static function fireTrigger($codeName = '', $explanation = '') {
		self::$outputs['t'. $codeName] = (string) $explanation;
	}
	
	
	
	//Version 1 of Phi used to support return statements.
	//These were not re-implemented in version 2.
#	private static $output = null;
#	public static function getReturnValue($var) {
#		return self::$output;
#	}
#	public static function returnValue($var = null) {
#		self::$output = $var;
#		return null;
#		//ob_clean();
#	}
	
	
	//This function is used as part of a work-around to allow changing object properties,
	//and to allow inserting elements into arrays.
	//Twig doesn't allow this but we wish Phi to support it, therefore we need a workaround.
	public static function _zPhiSAK_(&$arrayIn, ...$keys) {
		
		$array = &$arrayIn;
		
		$value = array_pop($keys);
		$finalKey = array_pop($keys);
		
		foreach ($keys as $key) {
			if (!isset($array[$key])) {
				$array[$key] = [];
			}
			
			$array = &$array[$key];
		}
		
		if ($finalKey === null) {
			$array[] = $value;
		} else {
			$array[$finalKey] = $value;
		}
	}
	
	
	
	






	private static $whitelistedFuns = [
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

	private static $renamedAndCustomFuns = [
		'ceiling' => 'ceil',
		'count' => 'count',
		'c' => 'array',
		'trim' => 'trim',
		'list' => 'array',
		'arrayMerge' => 'array_merge',
		'baseConvert' => 'base_convert',
		'isFinite' => 'is_finite',
		'isInfinite' => 'is_infinite',
		'isNaN' => 'is_nan',
		
		'dump' => 'ze\\phi::varDump',
		'print' => 'ze\\phi::varDump',
		'var_dump' => 'ze\\phi::varDump',
		'setValue' => 'ze\\phi::setValue',
		'fireTrigger' => 'ze\\phi::fireTrigger',
		'_zPhiSAK_' => 'ze\\phi::_zPhiSAK_',
		
		'paste' => 'ze\\phi::paste',
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

	private static $assetwolfFuns = [
		'getMinValue' => 'ze\\assetwolf::getMinValue',
		'getMaxValue' => 'ze\\assetwolf::getMaxValue',
		'getHistoricValue' => 'ze\\assetwolf::getHistoricValue',
		'getMetricValue' => 'ze\\assetwolf::getMetricValue',
		'getTimestamp' => 'ze\\assetwolf::getTimestamp',
		'getAllChildIds' => 'ze\\assetwolf::getAllChildIds',
		'getAllChildAssetIds' => 'ze\\assetwolf::getAllChildAssetIds',
		'getAllChildDataPoolIds' => 'ze\\assetwolf::getAllChildDataPoolIds',
		'getAssetReliability' => 'ze\\assetwolf::getAssetReliabilityFromPhi',
		'getImmediateChildIds' => 'ze\\assetwolf::getImmediateChildIds',
		'getInheritedMetadata' => 'ze\\assetwolf::getInheritedMetadataFromPhi',
		'getLocationMetadata' => 'ze\\assetwolf::getLocationMetadataFromPhi',
		'getParentNodeId' => 'ze\\assetwolf::getParentNodeId',
		'formatValue' => 'ze\\assetwolf::formatValue',
		'query' => 'ze\\assetwolf::query',
		
		//Deprecated
		'getMetadata' => 'ze\\assetwolf::getInheritedMetadataFromPhi'
	];
	
	
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
		self::$twig = new \Twig\Environment(new \Zenario_Twig_String_Loader(), [
			'cache' => new \Zenario_Phi_Twig_Cache(),
			'autoescape' => false,
			'debug' => false,
			'auto_reload' => true
		]);
		
		//Include a Twig extension that enables support for breaks and continues
		self::$twig->addExtension(new \MNBreakAndContinueTwigExtension());
		
		//Remove the "filter", "map" and "reduce" filters, as these have a very bad security vulnerability
		//involving executing arbitrary functions/making arbitrary CLI calls, and I don't think we
		//use them anywhere anyway.
		//I'm also removing the "sort" filter. I can't actually reproduce the vulnerability with this one
		//but it also accepts functions as an input so I'm blocking it out of paranoia.
		self::blockFilter('filter');
		self::blockFilter('map');
		self::blockFilter('reduce');
		self::blockFilter('sort');
		
		
		//Set up the whitelist of allowed functions
		foreach (self::$whitelistedFuns as $phpName) {
			self::$twig->addFunction(new \Twig\TwigFunction($phpName, $phpName));
		}
		foreach (self::$renamedAndCustomFuns as $twigName => $phpName) {
			self::$twig->addFunction(new \Twig\TwigFunction($twigName, $phpName));
		}
		
		//If assetwolf is running and has been included, make its getHistoricValue() function available
		if (class_exists('ze\\assetwolf')) {
			foreach (self::$assetwolfFuns as $twigName => $phpName) {
				self::$twig->addFunction(new \Twig\TwigFunction($twigName, $phpName));
			}
		}
		
		
		//Blacklist a few functions and variables.
		//(Note there's already another check for these in the parser, but an extra redundant check as safety net wouldn't hurt...)
		$blacklist = [
			'_self',
			'block',
			'constant',
			'include',
			'parent',
			'source',
			'template_from_string'
		];
		
		foreach ($blacklist as $twigName) {
			self::$twig->addFunction(new \Twig\TwigFunction($twigName, '\ze\\phi::returnNull'));
		}
	}
	
	public static function returnNull() {
		return null;
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
	
	
	
	
	
	public static function runTwig($twigCode, &$outputs, $vars = [], $enableVarDumps = false, $suppressErrors = false) {
		self::$enableVarDumps = $enableVarDumps;
		self::$suppressErrors = $suppressErrors;
		self::$outputs = &$outputs;
		self::$varDumps = [];
		
		if (empty($vars)) {
			$vars = self::$vars;
		} else {
			$vars = array_merge($vars, self::$vars);
		}
		
		try {
			\ze::ignoreErrors();
				\ze::$isTwig = true;
					self::$twig->render($twigCode, $vars);
				\ze::$isTwig = false;
			\ze::noteErrors();
		
		} catch (\DivisionByZeroError | \Exception $e) {
			\ze::noteErrors();
			
			if ($suppressErrors) {
				return null;
			} else {
				throw $e;
			}
		}
		
		$emptyArray = [];
		self::$outputs = &$emptyArray;
		self::$enableVarDumps = false;
		self::$suppressErrors = false;
		
		return true;
	}
	
	public static function carefullyRunTwig($twigCode, &$outputs, $vars = []) {
		return self::runTwig($twigCode, $outputs, $vars, false, true);
	}
	
	public static function runPhi($phiCode, &$outputs, $vars = []) {
		return \ze\phiParser::runPhi($phiCode, $outputs, $vars);
	}
	
	public static function carefullyRunPhi($phiCode, &$outputs, $vars = []) {
		return \ze\phiParser::carefullyRunPhi($phiCode, $outputs, $vars);
	}
	
	public static function testPhi($phiCode, &$outputs, $vars = []) {
		return \ze\phiParser::testPhi($phiCode, $outputs, $vars);
	}
	
	public static function phiToTwig($phiCode) {
		return \ze\phiParser::phiToTwig($phiCode);
	}
	
	
	
	public static function carefullyRunPhiFragment($phiCodeFragment, $vars = []) {
		$outputs = [];
		$phiCode = 'setValue("output", '. $phiCodeFragment. ')';
		\ze\phiParser::carefullyRunPhi($phiCode, $outputs, $vars);
		return $outputs['voutput'] ?? null;
	}
}
\ze\phi::init();
