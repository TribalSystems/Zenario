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

//Include the Twig-Extensions library for phrase/translation functionality
require CMS_ROOT. 'zenario/libs/manually_maintained/mit/twig-extensions/lib/Twig/Extensions/Autoloader.php';
\Twig_Extensions_Autoloader::register();

//Run the garbage collector and create the cache/frameworks/ directory if it wasn't already created
\ze\cache::cleanDirs();


class twig {
	private static $twig;
	
	public static function init() {
		//Initialise Twig
		self::$twig = new \Twig_Environment(new \Zenario_Twig_Loader(), array(
			'cache' => new \Zenario_Twig_Cache(),
			'autoescape' => false,
			'auto_reload' => true
		));

		//Add the I18n extension to add support for translating text
		self::$twig->addExtension(new \Twig_Extensions_Extension_I18n());


		//Create instances of any modules that say they are usable in Twig Frameworks
		foreach (\ze\sql::fetchAssocs("
			SELECT id, class_name, status
			FROM ". DB_NAME_PREFIX. "modules
			WHERE for_use_in_twig = 1"
		) as $module) {
			if (\ze\module::inc($module)) {
				$className = $module['class_name'];
				\ze::$twigModules[$className] = new $className;
			}
		}

		//Add references to some commonly used functions from Twig frameworks in Zenario 7,
		//just to cut down the ammount of rewriting we need to do!
		self::$twig->addFunction(new \Twig_SimpleFunction('imageLinkArray', '\ze\\file::imageLinkArray'));
		self::$twig->addFunction(new \Twig_SimpleFunction('trackFileDownload', '\ze\\file::trackDownload'));
		self::$twig->addFunction(new \Twig_SimpleFunction('get', '\ze::get'));
		self::$twig->addFunction(new \Twig_SimpleFunction('post', '\ze::post'));
		self::$twig->addFunction(new \Twig_SimpleFunction('request', '\ze::request'));
		self::$twig->addFunction(new \Twig_SimpleFunction('session', '\ze::session'));

		self::$twig->addFunction(new \Twig_SimpleFunction('print_r', 'print_r'));
		self::$twig->addFunction(new \Twig_SimpleFunction('var_dump', 'var_dump'));
		self::$twig->addFunction(new \Twig_SimpleFunction('json_encode', 'json_encode'));

		self::$twig->addFunction(new \Twig_SimpleFunction('ze', 'zenario_callLibFromTwig'));
	}
	
	public static function render($framework, $vars) {
		return self::$twig->render($framework, $vars);
	}
}
\ze\twig::init();