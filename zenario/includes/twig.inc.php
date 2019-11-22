<?php
/*
 * Copyright (c) 2019, Tribal Limited
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



//An implementation of Twig_LoaderInterface
//It works with both raw source code and paths to a twig file.
	//If the $name starts with a \n character, we'll treat it as raw code.
	//Otherwise, we'll assume it's a path to a twig file (relative to the CMS_ROOT).
class Zenario_Twig_Loader implements Twig_LoaderInterface {
    
    //public function getSource($name) {
    //	if (substr($name, 0, 1) === "\n") {
    //		return $name;
    //	} else {
	//    	return file_get_contents(CMS_ROOT. $name);
	//    }
    //}

    public function getCacheKey($name) {
    	return $name;
    }
    

    public function getSourceContext($name) {
    	if (substr($name, 0, 1) === "\n") {
	        return new Twig_Source($name, $name);
    	} else {
    		$path = CMS_ROOT. $name;
	        return new Twig_Source(file_get_contents($path), $name, $path);
	    }
    }

    public function isFresh($name, $time) {
    	if (substr($name, 0, 1) === "\n") {
    		return true;
    	} else {
	        return filemtime(CMS_ROOT. $name) <= $time;
	    }
    }
    

    public function exists($name) {
    	if (substr($name, 0, 1) === "\n") {
    		return true;
    	} else {
	        return file_exists(CMS_ROOT. $name);
	    }
    }
}





//A copy of the above that always only works with raw source code
class Zenario_Twig_String_Loader implements Twig_LoaderInterface {
    
    public function getCacheKey($name) {
    	return $name;
    }
    

    public function getSourceContext($name) {
        //return new Twig_Source($name, sha1($name));
        return new Twig_Source($name, $name);
    }

    public function isFresh($name, $time) {
   		return true;
    }

    public function exists($name) {
   		return true;
    }
}


//An implementation of Twig_CacheInterface that saves files to Zenario's cache directory.
//The main reason for the rewrite is so that we use our ze\cache::createDir() function, which has a working garbage collector.
//(Twig doesn't do any garbage collection so old frameworks can clog up the cache/ directory!)
class Zenario_Twig_Cache implements Twig_CacheInterface {
	
	public function generateKey($name, $className) {
		$hash = ze::base16To64(str_replace('__TwigTemplate_', '', $className));
		
		return CMS_ROOT. 'cache/frameworks/'. $hash .'/class.php';
	}

    public function load($key) {
        if (file_exists($key)) {
			touch(dirname($key). '/accessed');
			@include_once $key;
		}
    }

    public function write($key, $content) {
        $dir = basename(dirname($key));
        ze\cache::createDir($dir, 'cache/frameworks', false);
        file_put_contents($key, $content);
        \ze\cache::chmod($key, 0664);
    }

    public function getTimestamp($key) {
        if (!file_exists($key)) {
            return 0;
        }

        return (int) @filemtime($key);
    }
}


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
	    	$content = preg_replace('@\bze\\\\phi\:\:_zPhiSAK_\(\(?\$context([\[\]\'"\w-]+)( \?\? null|)\)?, (.*?), \\\\?ze\\\\phi\:\:_zPhiSAKEnd_\(\)\)\;@', '$context$1 = $3;', $content, -1, $count);
	    } while ($count > 0);
    	do {
    		$count = 0;
	    	$content = preg_replace('@\bze\\\\phi\:\:_zPhiSNAK_\(\(?\$context([\[\]\'"\w-]+)( \?\? null|)\)?, (.*?), \\\\?ze\\\\phi\:\:_zPhiSAKEnd_\(\)\)\;@', '$context$1[] = $3;', $content, -1, $count);
	    } while ($count > 0);
	    
    	parent::write($key, $content);
    }
}


function zenario_callLibFromTwig($lib, $fun, ...$args) {
	
	if ($lib == '') {
		$className = 'ze';
	} else {
		$className = 'ze\\'. $lib;
	}
	
	//Only allow methods that have been white-listed to be called.
	//Methods are white-listed by creating a constant with a specific name.
	$check = $className. '::'. $fun. 'FromTwig';
	
	if (defined($check) && constant($check)) {
		return call_user_func_array($className. '::'. $fun, $args);
	}
}



//Define the phrase() and the nphrase() functions for use in Twig frameworks.
//These should map to the phrase/nphrase functions of whatever plugin is currently running
function zenario_nphrase($text, $replace = []) {
	if (\ze::$plugin) {
		return \ze::$plugin->nphrase($text, $replace);
	}
}

function zenario_phrase($text, $pluralText = false, $n = 1, $replace = []) {
	if (\ze::$plugin) {
		return \ze::$plugin->phrase($text, $pluralText, $n, $replace);
	}
}