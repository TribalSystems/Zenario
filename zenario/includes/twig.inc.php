<?php
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

require CMS_ROOT. 'zenario/libraries/bsd/twig/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

require CMS_ROOT. 'zenario/libraries/mit/twig-extensions/lib/Twig/Extensions/Autoloader.php';
Twig_Extensions_Autoloader::register();



//A modified copy of Twig_Cache_Filesystem in zenario/libraries/bsd/twig/lib/Twig/Loader/Filesystem.php
//It works with both raw source code and paths to a twig file.
	//If the $name starts with a \n character, we'll treat it as raw code.
	//Otherwise, we'll assume it's a path to a twig file (relative to the CMS_ROOT).
class Zenario_Twig_Loader implements Twig_LoaderInterface {
    
    public function getSource($name) {
    	if (substr($name, 0, 1) === "\n") {
    		return $name;
    	} else {
	    	return file_get_contents(CMS_ROOT. $name);
	    }
    }

    public function getCacheKey($name) {
    	if (substr($name, 0, 1) === "\n") {
    		return $name;
    	} else {
	    	return $name;
	    }
    }

    public function isFresh($name, $time) {
    	if (substr($name, 0, 1) === "\n") {
    		return true;
    	} else {
	        return filemtime(CMS_ROOT. $name) <= $time;
	    }
    }
}


//A modified copy of Twig_Cache_Filesystem in zenario/libraries/bsd/twig/lib/Twig/Cache/Filesystem.php
//The main reason for the rewrite is so that we use our createCacheDir() function, which has a working garbage collector.
//(Twig doesn't do any garbage collection so old frameworks can clog up the cache/ directory!)
class Zenario_Twig_Cache implements Twig_CacheInterface {
	
	public function generateKey($name, $className) {
		$hash = base16To64(str_replace('__TwigTemplate_', '', $className));
		
		return CMS_ROOT. 'cache/frameworks/'. $hash .'/class.php';
	}

    public function load($key) {
    	touch(dirname($key). '/accessed');
        @include_once $key;
    }

    public function write($key, $content) {
        $dir = basename(dirname($key));
        createCacheDir($dir, 'cache/frameworks', false);
        file_put_contents($key, $content);
        @chmod($key, 0664);
    }

    public function getTimestamp($key) {
        return (int) @filemtime($key);
    }
}


//Run the garbage collector and create the cache/frameworks/ directory if it wasn't already created
cleanDownloads();


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


//Add all of the whitelisted functions
function readTwigWhitelist() {
	if (!empty(cms_core::$whitelist)) {
		foreach (cms_core::$whitelist as $fun) {
			cms_core::$twig->addFunction(new Twig_SimpleFunction($fun, $fun));
		}
	}
	cms_core::$whitelist = array();
}
readTwigWhitelist();


