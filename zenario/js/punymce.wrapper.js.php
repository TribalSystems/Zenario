<?php

header('Content-Type: text/javascript; charset=UTF-8');
require '../basicheader.inc.php';

useCache('zenario-inc-punymce-js-'. LATEST_REVISION_NO);
useGZIP(!empty($_GET['gz']));


require CMS_ROOT. 'zenario/includes/cms.inc.php';
header('Content-Type: text/javascript; charset=UTF-8');

function incJS($file, $useSrc = false) {
	if ($useSrc && file_exists($file. '_src.js')) {
		require $file. '_src.js';
	} elseif (file_exists($file. '.pack.js')) {
		require $file. '.pack.js';
	} elseif (file_exists($file. '.min.js')) {
		require $file. '.min.js';
	} elseif (file_exists($file. '.js')) {
		require $file. '.js';
	}
	
	echo "\n/**/\n";
}


incJS('zenario/libraries/lgpl/punymce/puny_mce');
incJS('zenario/libraries/lgpl/punymce/plugins/bbcode');
incJS('zenario/libraries/lgpl/punymce/plugins/editsource/editsource');
incJS('zenario/libraries/lgpl/punymce/plugins/emoticons/emoticons');
//incJS('zenario/libraries/lgpl/punymce/plugins/entities');
//incJS('zenario/libraries/lgpl/punymce/plugins/forceblocks');
incJS('zenario/libraries/lgpl/punymce/plugins/forcenl');
incJS('zenario/libraries/lgpl/punymce/plugins/image/image');
incJS('zenario/libraries/lgpl/punymce/plugins/link/link');
incJS('zenario/libraries/lgpl/punymce/plugins/paste');
incJS('zenario/libraries/lgpl/punymce/plugins/protect');
//incJS('zenario/libraries/lgpl/punymce/plugins/safari2x');
incJS('zenario/libraries/lgpl/punymce/plugins/textcolor/textcolor');
incJS('zenario/libraries/lgpl/punymce/plugins/tabfocus');


echo '
punymce.baseURL = URLBasePath + "zenario/libraries/lgpl/punymce/";';