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

//Include the Twig-Extensions library for phrase/translation functionality
require CMS_ROOT. 'zenario/libs/manually_maintained/mit/twig-extensions/lib/Twig/Extensions/Autoloader.php';
\Twig_Extensions_Autoloader::register();

//Run the garbage collector and create the cache/frameworks/ directory if it wasn't already created
\ze\cache::cleanDirs();


class twig {
	private static $twig;
	
	//A dummy filter, just used as a work-around to block other filters from working
	public static function dummyFilter() {
		return '';
	}
	
	protected static function blockFilter($name) {
		$dummyFilter = new \Twig\TwigFilter($name, ['\\ze\\twig', 'dummyFilter']);
		self::$twig->addFilter($dummyFilter);
	}
	
	public static function init() {
		//Initialise Twig
		self::$twig = new \Twig\Environment(new \Zenario_Twig_Loader(), [
			'cache' => new \Zenario_Twig_Cache(),
			'autoescape' => false,
			'auto_reload' => true
		]);

		//Add the I18n extension to add support for translating text
		self::$twig->addExtension(new \Twig_Extensions_Extension_I18n());
		
		//Remove the "filter", "map" and "reduce" filters, as these have a very bad security vulnerability
		//involving executing arbitrary functions/making arbitrary CLI calls, and I don't think we
		//use them anywhere anyway.
		//I'm also removing the "sort" filter. I can't actually reproduce the vulnerability with this one
		//but it also accepts functions as an input so I'm blocking it out of paranoia.
		self::blockFilter('filter');
		self::blockFilter('map');
		self::blockFilter('reduce');
		self::blockFilter('sort');


		//Create instances of any modules that say they are usable in Twig Frameworks
		foreach (\ze\sql::fetchAssocs("
			SELECT id, class_name, status
			FROM ". DB_PREFIX. "modules
			WHERE for_use_in_twig = 1"
		) as $module) {
			if (\ze\module::inc($module)) {
				$className = $module['class_name'];
				\ze::$twigModules[$className] = new $className;
			}
		}

		//Add references to some commonly used functions from Twig frameworks in Zenario 7,
		//just to cut down the ammount of rewriting we need to do!
		self::$twig->addFunction(new \Twig\TwigFunction('imageLinkArray', '\\ze\\file::imageLinkArray'));
		self::$twig->addFunction(new \Twig\TwigFunction('trackFileDownload', '\\ze\\file::trackDownload'));
		self::$twig->addFunction(new \Twig\TwigFunction('get', '\\ze::get'));
		self::$twig->addFunction(new \Twig\TwigFunction('post', '\\ze::post'));
		self::$twig->addFunction(new \Twig\TwigFunction('request', '\\ze::request'));
		self::$twig->addFunction(new \Twig\TwigFunction('session', '\\ze::session'));
		self::$twig->addFunction(new \Twig\TwigFunction('requireJsLib', '\\ze::requireJsLib'));

		self::$twig->addFunction(new \Twig\TwigFunction('sum', 'array_sum'));
		self::$twig->addFunction(new \Twig\TwigFunction('print_r', 'print_r'));
		self::$twig->addFunction(new \Twig\TwigFunction('var_dump', 'var_dump'));
		self::$twig->addFunction(new \Twig\TwigFunction('json_encode', 'json_encode'));

		self::$twig->addFunction(new \Twig\TwigFunction('ze', 'zenario_callLibFromTwig'));
		
		self::$twig->addFunction(new \Twig\TwigFunction('constant', '\\ze\\twig::getConstant'));
		self::$twig->addFunction(new \Twig\TwigFunction('var', '\\ze\\twig::getVar'));
		
		
		//Make a few plugin functions available in Twig, even if running without the "this" variable
		self::$twig->addFunction(new \Twig\TwigFunction('conductorEnabled', '\\ze\\twig::conductorEnabled'));
		self::$twig->addFunction(new \Twig\TwigFunction('conductorCommandEnabled', '\\ze\\twig::conductorCommandEnabled'));
		self::$twig->addFunction(new \Twig\TwigFunction('conductorLink', '\\ze\\twig::conductorLink'));
		self::$twig->addFunction(new \Twig\TwigFunction('conductorOnclick', '\\ze\\twig::conductorOnclick'));
		self::$twig->addFunction(new \Twig\TwigFunction('conductorBackLink', '\\ze\\twig::conductorBackLink'));
		self::$twig->addFunction(new \Twig\TwigFunction('callScriptBeforeAJAXReload', '\\ze\\twig::callScriptBeforeAJAXReload'));
		self::$twig->addFunction(new \Twig\TwigFunction('callScriptBeforeFoot', '\\ze\\twig::callScriptBeforeFoot'));
		self::$twig->addFunction(new \Twig\TwigFunction('callScript', '\\ze\\twig::callScript'));
		self::$twig->addFunction(new \Twig\TwigFunction('jQueryBeforeAJAXReload', '\\ze\\twig::jQueryBeforeAJAXReload'));
		self::$twig->addFunction(new \Twig\TwigFunction('jQueryBeforeFoot', '\\ze\\twig::jQueryBeforeFoot'));
		self::$twig->addFunction(new \Twig\TwigFunction('jQuery', '\\ze\\twig::jQuery'));
	}
	
	public static function render($framework, $vars) {
		return self::$twig->render($framework, $vars);
	}
	
	public static function getConstant($const) {
		
		if (defined($const)
		 && substr($const, 0, 6) != 'DBPASS') {
			return constant($const);
		}
		return null;
	}
	
	public static function getVar($var) {
		return \ze::$vars[$var] ?? null;
	}
	
	
	//Make a few plugin functions available in Twig
	public static function conductorEnabled() {
		if (\ze::$plugin) return \ze::$plugin->conductorEnabled();
	}
	public static function conductorCommandEnabled($command) {
		if (\ze::$plugin) return \ze::$plugin->conductorCommandEnabled($command);
	}
	public static function conductorLink($command, $requests = []) {
		if (\ze::$plugin) return \ze::$plugin->conductorLink($command, $requests);
	}
	public static function conductorOnclick($command, $requests = []) {
		if (\ze::$plugin) return \ze::$plugin->conductorOnclick($command, $requests);
	}
	public static function conductorBackLink() {
		if (\ze::$plugin) return \ze::$plugin->conductorBackLink();
	}
	
	public static function callScriptBeforeAJAXReload(...$args) {
		if (\ze::$plugin) call_user_func_array([\ze::$plugin, 'callScriptBeforeAJAXReload'], $args);
	}
	public static function callScriptBeforeFoot(...$args) {
		if (\ze::$plugin) call_user_func_array([\ze::$plugin, 'callScriptBeforeFoot'], $args);
	}
	public static function callScript(...$args) {
		if (\ze::$plugin) call_user_func_array([\ze::$plugin, 'callScript'], $args);
	}
	public static function jQueryBeforeAJAXReload(...$args) {
		if (\ze::$plugin) call_user_func_array([\ze::$plugin, 'jQueryBeforeAJAXReload'], $args);
	}
	public static function jQueryBeforeFoot(...$args) {
		if (\ze::$plugin) call_user_func_array([\ze::$plugin, 'jQueryBeforeFoot'], $args);
	}
	public static function jQuery(...$args) {
		if (\ze::$plugin) call_user_func_array([\ze::$plugin, 'jQuery'], $args);
	}
}
\ze\twig::init();