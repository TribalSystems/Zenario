(function() {

	changeCountryConfirm = function(ajaxURL) {
		var post = {'changeCountry' : 1};
		var html = '';
 		
		zenario.ajax(ajaxURL, post).after(function(html) {
			history.go(0);
		});
	};
})();

zenario_country_language_picker.disablePageScrolling = function() {
	
	$("body").toggleClass("popup_active");
	
};