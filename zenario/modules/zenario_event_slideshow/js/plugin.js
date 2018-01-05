zenario_event_slideshow.initiateSlideshow = function(instanceId) {
	var options = {
		$ArrowNavigatorOptions: {
			$Class: $JssorArrowNavigator$,
			$ChanceToShow: 2
		},
		$DragOrientation: 0
	};
	$('#event_slideshow_container_'+instanceId).css('visibility', 'visible');
    var jssor_slider = new $JssorSlider$('event_slideshow_container_'+instanceId, options);
};