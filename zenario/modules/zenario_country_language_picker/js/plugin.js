(function() {

	changeCountryConfirm = function(ajaxURL) {
		var post = {'changeCountry' : 1};
		var html = '';
 		
		zenario.ajax(ajaxURL, post).after(function(html) {
			history.go(0);
		});
	};

	window.onload = function(){
		var allCountriesLists = $('.zenario_country_language_picker.country_language_picker_full .continents_list .continent .countries_columns').hide();
		
		$('.zenario_country_language_picker.country_language_picker_full .continents_list .continent h3').click(function() {
			if ($( this ).parent().hasClass("on")) {
				$( this ).parent().find( ".countries_columns").slideUp("slow");
				$( this ).parent().removeClass('on');
			} else {
				$('.zenario_country_language_picker.country_language_picker_full .continents_list .continent').removeClass('on');
				allCountriesLists.slideUp("slow");
				$( this ).parent().addClass('on');
				$( this ).parent().find( ".countries_columns").slideDown("slow");
			}
			return false;
		});
	};
})();

zenario_country_language_picker.disablePageScrolling = function() {
	
	$("body").toggleClass("popup_active");
	
};