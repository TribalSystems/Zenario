var zenario_user_profile_search_as_we_type_last_val = '';
var zenario_user_profile_search_as_we_type_last_timeout_id = 0;

function zenario_user_profile_search_as_we_type(obj, call_count){
	call_count = call_count || 0;
	if(zenario_user_profile_search_as_we_type_last_val == obj.value && call_count){
		obj.form.doSearch.click();
		zenario_user_profile_search_as_we_type_last_val = '';
	} else {
		if(zenario_user_profile_search_as_we_type_last_timeout_id) {
			clearTimeout(zenario_user_profile_search_as_we_type_last_timeout_id);
		}
		zenario_user_profile_search_as_we_type_last_val = obj.value;
		zenario_user_profile_search_as_we_type_last_timeout_id = setTimeout(function() { 
			zenario_user_profile_search_as_we_type(obj, ++call_count);
		}, 500);
	}
}

function zenario_user_profile_search_refresh_results(html){
		var resultDom = $(html);
		var destResult = $('.zenario_user_profile_search_results');
		destResult.html(resultDom.find('.zenario_user_profile_search_results').html());
		destResult.stop(true, true).animate({opacity: 1}, 150);
}

function zenario_user_profile_search_submit(frm) {
	var destResult = $('.zenario_user_profile_search_results');
	destResult.stop(true, true).animate({opacity: .5}, 150);
	zenario.submitFormReturningHtml(frm, zenario_user_profile_search_refresh_results);
	return false;
}