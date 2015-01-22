<?php

require '../lib/js/modernizr.custom.js';

echo "\n/**/\n";

require '../lib/js/jquery.dlmenu.js';

echo "\n/**/\n";

?>

zenario_menu_responsive_multilevel.setup = function() {
	$( '#dl-menu' ).dlmenu();
};
