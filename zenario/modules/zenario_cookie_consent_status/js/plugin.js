zenario_cookie_consent_status.onConsentSave = function(containerId) {
	
	//Hide cookie consent popup if visible
	$('div.zenario_cookie_consent').hide();
	
	//Fadeout success message
	setTimeout(function() {
		$('#' + containerId + ' .success').fadeOut();
	}, 2000);
	
};