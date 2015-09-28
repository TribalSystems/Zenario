<?php
/*
 * Copyright (c) 2015, Tribal Limited
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

//Exit if not called running via a command
if (empty($argv[1]) || !is_numeric($argv[1]) || empty($argv[0]) || !is_file($argv[0])) {
	exit;
}


//Handle process forking
$step = (int) $argv[1];

if ($step == 1 || $step == 2) {
	//Steps 1 and 2 should fork from their parent
	//Try to fork this process into a child
	$pid = @pcntl_fork();
	
	if ($pid == 0) {
		//If this is the child:
		//Detach the child from the parent
		posix_setsid();
	
	} elseif ($pid < 0) {
		//Continue running as best we can if we failed to fork
	
	} else {
		//Otherwise if this is the parent:
		//Stop the parent running
		if (is_resource(STDOUT)) fclose(STDOUT);
		if (is_resource(STDERR)) fclose(STDERR);
		exit;
	}
}

//Set up the directory path
if (!file_exists(($cmsRoot = dirname(dirname(dirname(dirname($argv[0])))). '/'). 'zenario/basicheader.inc.php')
 && !file_exists(($cmsRoot = dirname($cmsRoot). '/'). 'zenario/basicheader.inc.php')) {
	exit;
}

//Include the CMS' library of functions, but don't include any behaviour designed
//for sending a page to a Visitor as this is a scheduled task and not a page load.
define('CMS_ROOT', $cmsRoot);
require CMS_ROOT. 'zenario/includes/shell.inc.php';


if ($step == 1) {
	if (!setting('jobs_enabled')) {
		exit;
	}
	$managerClassName = 'zenario_scheduled_task_manager';

} else {
	$managerClassName = $argv[2];
}

//Attempt to activate the Module and run this step
if (inc($managerClassName)) {
	if ($step == 1) {
		zenario_scheduled_task_manager::step1();
	
	} else {
		$args = $argv;
		array_shift($args);
		array_shift($args);
		call_user_func_array(array($managerClassName, 'step'. $step), $args);
	}
}