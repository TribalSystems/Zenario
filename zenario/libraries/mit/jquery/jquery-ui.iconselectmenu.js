$.widget("custom.iconselectmenu", $.ui.selectmenu, {
	_renderItem: function(ul, item) {
		
		var $li = $("<li>"),
			$wrapper = $("<div>", {
				text: item.label
			}),
			$inserted;
		
		if (item.disabled) {
			$li.addClass("ui-state-disabled");
		}

		$("<span>", {
			style: item.element.attr("data-style"),
			"class": "ui-icon " + (item.element.attr("data-class") || '')
		})
		.appendTo($wrapper);

		$inserted = $li.append($wrapper).appendTo(ul);
		
		if (item.element.attr("data-hide_in_iconselectmenu")) {
			$li.hide();
		}
		
		return $inserted;
	}
});