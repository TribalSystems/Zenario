
zenario_ctype_document.copyLink = function(text) {
	if (zenario.copy(text)) {
		toastr.success(zenario.vphrase.copiedToClipboard, '', {'positionClass': 'toast-bottom-left'});
	}
};
