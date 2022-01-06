zenario_menu_responsive_push_pull.pageReady = function(containerId) {
	$( document ).ready(function() {
		//Move the <nav> element to the top of the body.
		currentPushPullEl = document.getElementById(containerId + '_push_pull_menu');
		currentPushPullEl.remove();
		document.body.prepend(currentPushPullEl);

		//Also add some classes to zenario_citem div.
		citemDiv = document.getElementById('zenario_citem');
		citemDiv.classList.add("zenario_push_pull-page");
		citemDiv.classList.add("zenario_push_pull-slideout");

		//Finally, add the wrapper blocker.
		blockerEl = document.createElement('div');
		blockerEl.setAttribute('class', 'zenario_push_pull-wrapper__blocker zenario_push_pull-slideout');
		blockerEl.setAttribute('onclick', 'zenario_menu_responsive_push_pull.hamburgerOnclick("' + containerId + '", "close")');

		closeMenuEl = document.createElement('a');

		closeMenuSpanEl = document.createElement('span');
		closeMenuSpanEl.setAttribute('class', 'zenario_push_pull-sronly');
		closeMenuSpanEl.innerText = 'Close menu';

		closeMenuEl.append(closeMenuSpanEl);
		blockerEl.append(closeMenuEl);
		document.body.append(blockerEl);

		//Remember what the header position (in pixels) was.
		//Make sure the header is never scrolled above that.
		var headerEl = $('.Fixed');
		var headerInitialTop = parseFloat(headerEl.css("top"));
		headerEl.data("data-initial-top", headerInitialTop);
	});
}

zenario_menu_responsive_push_pull.hamburgerOnclick = function(containerId, openOrClose) {
	var headerEl = $('.Fixed');
	var headerInitialTop = headerEl.data("data-initial-top");
	
	el = $('#' + containerId + '_push_pull_menu');
	elIsVisibleNow = el.is(":visible");

	if ((openOrClose == 'open' && !elIsVisibleNow) || openOrClose == 'close' && elIsVisibleNow) {
		elIsVisibleNow = !elIsVisibleNow;
	}

	htmlEl = $('html');
	navEl = document.getElementById(containerId + '_push_pull_menu');
	citemDiv = document.getElementById('zenario_citem');

	if (elIsVisibleNow) {
		htmlEl.addClass('zenario_push_pull-wrapper_opened');
		htmlEl.addClass('zenario_push_pull-wrapper_blocking');
		htmlEl.addClass('zenario_push_pull-wrapper_background');
		htmlEl.addClass('zenario_push_pull-wrapper_opening');

		navEl.removeAttribute('aria-hidden');
		navEl.classList.add('zenario_push_pull-menu_opened');

		citemDiv.setAttribute("style", "min-height: " + window.innerHeight);

		topPixel = $(document).scrollTop();
		if (topPixel < headerInitialTop) {
			headerEl.css({'top' : headerInitialTop + 'px'});
		} else {
			headerEl.css({'top' : topPixel + 'px'});
		}
	} else {
		htmlEl.removeClass('zenario_push_pull-wrapper_opened');
		htmlEl.removeClass('zenario_push_pull-wrapper_blocking');
		htmlEl.removeClass('zenario_push_pull-wrapper_background');
		htmlEl.removeClass('zenario_push_pull-wrapper_opening');

		navEl.setAttribute('aria-hidden', true);
		navEl.classList.remove('zenario_push_pull-menu_opened');

		citemDiv.removeAttribute('style');

		//Wait until the push-pull animation ends, and then remove the "style" parameter from the header.
		//This will prevent a situation where the header disappears for a split second and reappears again.
		Promise.all(
			citemDiv.getAnimations().map(
				function(animation) {
					return animation.finished
				}
			)
		).then(
			function() {
				headerEl.removeAttr('style');
			}
		);
	}
};

zenario_menu_responsive_push_pull.menuNodeToggleOnclick = function(parentId, nodeId, action) {
	parent = $('#' + parentId);
	el = $('#' + nodeId);
	var duration = 250;

	if (action == 'open') {
		el.show("slide", {direction: "right"}, duration);
		parent.hide("slide", {direction: "left"}, duration);
	} else if (action == 'close') {
		parent.show("slide", {direction: "left"}, duration);
		el.hide("slide", {direction: "right"}, duration);
	}
};