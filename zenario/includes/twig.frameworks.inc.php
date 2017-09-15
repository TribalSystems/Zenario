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

//Also include the Twig-Extensions library
require CMS_ROOT. 'zenario/libraries/mit/twig-extensions/lib/Twig/Extensions/Autoloader.php';
Twig_Extensions_Autoloader::register();


//Run the garbage collector and create the cache/frameworks/ directory if it wasn't already created
cleanCacheDir();


//Initialise Twig
cms_core::$twig = new Twig_Environment(new Zenario_Twig_Loader(), array(
	'cache' => new Zenario_Twig_Cache(),
	'autoescape' => false,
	'auto_reload' => true
));

//Add the I18n extension to add support for translating text
cms_core::$twig->addExtension(new Twig_Extensions_Extension_I18n());


//Create instances of any modules that say they are usable in Twig Frameworks
foreach (sqlFetchAssocs("
	SELECT id, class_name, status
	FROM ". DB_NAME_PREFIX. "modules
	WHERE for_use_in_twig = 1"
) as $module) {
	if (inc($module)) {
		$className = $module['class_name'];
		cms_core::$twigModules[$className] = new $className;
	}
}


cms_core::$whitelist['imageLinkArray'] = 'Ze\File::imageLinkArray';
cms_core::$whitelist['trackFileDownload'] = 'Ze\File::trackDownload';

//Add all of the whitelisted functions
function readTwigWhitelist() {
	if (!empty(cms_core::$whitelist)) {
		foreach (cms_core::$whitelist as $i => $fun) {
			if (is_numeric($i)) {
				cms_core::$twig->addFunction(new Twig_SimpleFunction($fun, $fun));
			} else {
				cms_core::$twig->addFunction(new Twig_SimpleFunction($i, $fun));
			}
		}
	}
	cms_core::$whitelist = array();
}
readTwigWhitelist();


