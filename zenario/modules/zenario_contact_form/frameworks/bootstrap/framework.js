zenario_contact_form.validateContactFormField = function (el) {
	if ($(el).val()) {
		if ($(el).parents(".control-group").hasClass("error")) {
			$(el).parents(".control-group").removeClass("error");
			$(el).siblings("span").hide();
		}
	} else {
		if (!$(el).parents(".control-group").hasClass("error")) {
			$(el).parents(".control-group").addClass("error");
			$(el).siblings("span").show();
		}
	}
};