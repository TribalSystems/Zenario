(function() {

	changeCountryConfirm = function(ajaxURL) {
		var post = {'changeCountry' : 1};
		var html = '';
 		
		zenario.ajax(ajaxURL, post).after(function(html) {
			history.go(0);
		});
	};

	window.onload = function(){
		var allCountriesLists = $('.continents_list.country_accordions .continent .countries_columns').hide();
		
		$('.continents_list.country_accordions .continent h3').click(function() {
			if ($( this ).parent().hasClass("on")) {
				$( this ).parent().find( ".countries_columns").slideUp("slow");
				$( this ).parent().removeClass('on');
			} else {
				$('.continents_list.country_accordions .continent').removeClass('on');
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