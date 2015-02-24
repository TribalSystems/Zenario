<?php

require CMS_ROOT. 'zenario/libraries/mit/ResponsiveMultiLevelMenu/js/jquery.dlmenu.min.js';

echo "\n/**/\n";

?>

zenario_menu_responsive_multilevel.init = function(id) {
	$('#' + id).dlmenu();
};
