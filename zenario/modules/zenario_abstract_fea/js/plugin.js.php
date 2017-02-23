if (!zenarioA.init) {
	<?php
		//Include some of the JavaScript libraries needed for TUIX
		incJS('zenario/js/admin.nomin');
		
		incJS('zenario/js/admin');
		incJS('zenario/js/form');
		
		incJS('zenario/js/admin.ready');
	?>
}
		
if (!$.fn.jPaginator) {
	<?php
		//Include some jQuery plugins if they're not already on the page
		incJS('zenario/libraries/bsd/tokenize/jquery.tokenize');
		incJS('zenario/libraries/mit/jquery/jquery-ui.autocomplete');
		incJS('zenario/libraries/mit/jquery/jquery-ui.slider');
		incJS('zenario/libraries/mit/jpaginator/jPaginator');
	?>
}

<?php
	incJS('zenario/libraries/mit/jquery.fix.clone/jquery.fix.clone');
	incJS('zenario/libraries/bsd/bez/jquery.bez');
	require 'form.min.js';
?>