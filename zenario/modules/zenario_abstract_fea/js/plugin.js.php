if (!zenarioA.init) {
	<?php
		//Include some of the JavaScript libraries needed for TUIX
		incJS('zenario/js/admin.nomin');
		incJS('zenario/js/admin');
		incJS('zenario/js/form');
		
		incJS('zenario/libraries/mit/jquery/jquery-ui.autocomplete');
		incJS('zenario/libraries/mit/jquery/jquery-ui.slider');
		incJS('zenario/libraries/mit/jpaginator/jPaginator');
	?>
	zenarioA.init = false;
}

<?php
	incJS('zenario/libraries/bsd/bez/jquery.bez');
	incJS('zenario/libraries/mit/enquire/enquire');
	//require 'form.js';
	require 'form.min.js';
	//require 'plugin.min.js';
?>