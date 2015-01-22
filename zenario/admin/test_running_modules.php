<?php

require '../adminheader.inc.php';

foreach (getModules($onlyGetRunningPlugins = true) as $module) {
	echo '<br/>';
	echo htmlspecialchars($module['class_name']). ': ';
	if (inc($module['class_name'])) {
		echo ' OK';
	} else {
		echo ' <strong>FAILED</strong>';
	}
}