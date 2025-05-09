zenario_feed_reader.getFeedContent = function(containerId, url) {
	zenario.ajax(url).after(function(html) {
		$('#' + containerId).html(html);
	});
};