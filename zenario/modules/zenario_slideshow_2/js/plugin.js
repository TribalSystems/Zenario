zenario_slideshow_2.initiateSlideshow = function(slides, AJAXLink, slotName, instanceId, settings) {
	var mobileMode = false;
	if (settings.has_mobile_images) {
		var screenLimit = settings.mobile_resize_width;
		var screenWidth = $(window).width();
		// Change the slideshow to mobile or main depending on screen size
		if (screenWidth < screenLimit) {
			mobileMode = true;
			settings.desktop_width = settings.mobile_width;
			settings.desktop_height = settings.mobile_height;
		}
		$(window).resize(function() {
			waitForFinalEvent(function() {
				if (($(window).width() < screenLimit) && (!mobileMode)) {
					zenario_slideshow_2.refreshPluginSlot(slotName);
					mobileMode = true;
				} else if(($(window).width() >= screenLimit) && mobileMode) {
					zenario_slideshow_2.refreshPluginSlot(slotName);
					mobileMode = false;
				}
			}, 500, instanceId);
		});
	}
	var width = settings.desktop_width;
	var height = settings.desktop_height;
	
	// Get slideshow transition code
	var slideshowTransition = zenario_slideshow_2.getTransition(settings.slide_transition);
	
	// JSSOR slideshow options
	var options = { 
		$AutoPlay: settings.auto_play,
		$FillMode: 4,
		$AutoPlayInterval: parseInt(settings.slide_duration),
		$SlideDuration: 500,
		$PauseOnHover: settings.hover_to_pause,
		$ArrowKeyNavigation: false,
		$SlideSpacing: 0,
		$PlayOrientation: 1,
		$DragOrientation: 0,
		$SlideshowOptions: {
			$Class: $JssorSlideshowRunner$,
			$Transitions: slideshowTransition
		}
	};
	
	// Create slide html
	var cursorStyle = '';
	if (settings.enable_swipe) {
		cursorStyle = 'move';
		options.$DragOrientation = 1;
	} else {
		cursorStyle = 'default';
	}
	
	var html = '';
	html += '<div id="slider1_container_'+instanceId+'"\
				class="slideshow_container" style="visibility: hidden; position: relative; top: 0px; left: 0px; width:'+width+'px; height:'+height+'px; overflow: hidden;">\
				<div u="loading"></div>\
				<div u="slides" id="slides_container_'+instanceId+'" class="" style="cursor: '+cursorStyle+'; position: absolute; overflow: hidden; left: 0px; top: 0px; width:'+width+'px; height:'+height+'px;">';
	var count = 1;
	var _CaptionTransitions = {};
	$.each(slides, function(key, slide) {
		var altTag = '';
		if (mobileMode) {
			slide.image_src = slide.mobile_image_src;
			slide.overwrite_alt_tag = slide.mobile_overwrite_alt_tag;
			altTag = slide.mobile_overwrite_alt_tag ? slide.mobile_overwrite_alt_tag : slide.m_alt_tag;
		} else {
			altTag = slide.overwrite_alt_tag ? slide.overwrite_alt_tag : slide.alt_tag;
		}
		html += '<div id="slide_'+key+'_'+instanceId+'" class="slide_'+key+'_'+instanceId+' slide_'+count+' slide">\
					<div class="slide_inner" style="position:relative;">\
						<a ';
		if (slide.target_loc != 'none') {
			html += 'href="'+slide.dest_url+'"';
			if (parseInt(slide.open_in_new_window)) {
				html += 'target="_blank"';
			}
		}
		html += '>\
				<img u="image" src="'+slide.image_src+'" alt="'+altTag+'" id="slide_'+slide.id+'"';
		if (slide.rollover_image_src && !mobileMode) {
			html += '\
				onmouseover="\
					if (\''+slide.rollover_image_src+'\') {\
						this.src=\''+slide.rollover_image_src+'\'\
					}"\
				onmouseout="this.src=\''+slide.image_src+'\'"';
		}
		html += '/>\
			</a>';
		if (!mobileMode) {
			if (settings.navigation_style == 'thumbnail_navigator') {
				html += '<div u="thumb">'+slide.tab_name+'</div>';
			}
			if (slide.slide_title || slide.slide_extra_html) {
				html += '<div class="content_container"';
				if (parseInt(slide.use_transition_code) && slide.transition_code) {
					try {
						var transitionCode = eval('('+slide.transition_code+')');
						_CaptionTransitions['slide_transition_'+ slide.id] = transitionCode;
					} catch (e) {}
					html += ' u="caption" t="slide_transition_'+slide.id+'" ';
				}
				html += '>';
				if (slide.slide_title) {
					html += '<div class="slide_title">'+slide.slide_title+'</div>';
				}
				if (slide.slide_extra_html) {
					html += '<div class="slide_extra_html">'+slide.slide_extra_html+'</div>';
				}
				html += '</div>';
			}
		} else if (slide.mobile_slide_title || slide.mobile_slide_extra_html) {
			html += '<div class="mobile_content_container">';
			if (slide.mobile_slide_title) {
				html += '<div class="mobile_slide_title">'+slide.slide_title+'</div>';
			}
			if (slide.mobile_slide_extra_html) {
				html += '<div class="mobile_slide_extra_html">'+slide.slide_extra_html+'</div>';
			}
			html += '</div>';
		}
		html += '</div></div>';
		count++;
	});
	html += '</div>'
	
	if (!mobileMode) {
		if (settings.enable_arrow_buttons) {
			html += '<span u="arrowleft" class="arrowl"></span>\
					 <span u="arrowright" class="arrowr"></span>';
			options.$ArrowNavigatorOptions = {
				$Class: $JssorArrowNavigator$,
				$ChanceToShow: 2
			}
		}
		if (settings.navigation_style == 'bullet_navigator') {
			html += '<div u="navigator" class="bullet">\
						 <div u="prototype"></div>\
					 </div>';
			options.$BulletNavigatorOptions = {
				$Class: $JssorBulletNavigator$,
				$ChanceToShow: 2
			};
		} else if (settings.navigation_style == 'thumbnail_navigator') {
			html += '<div u="thumbnavigator" class="tab">\
						<div u="slides" style="cursor: move;">\
							<div u="prototype">\
								<thumbnailtemplate></thumbnailtemplate>\
							</div>\
						</div>\
					</div>';
			options.$ThumbnailNavigatorOptions = {
				$Class: $JssorThumbnailNavigator$,
				$ChanceToShow: 2
			};
		}
	}
	html += '</div>';
	
	// Set HTML
	$('#slideshow_outer_'+instanceId).html(html);
    
    // Set slideshow caption transitions
    options.$CaptionSliderOptions = {
    	$Class: $JssorCaptionSlider$,
		$CaptionTransitions: _CaptionTransitions,
		$PlayInMode: 1,
		$PlayOutMode: 3
    };
    
    $('#slider1_container_'+instanceId).css({'visibility': 'visible'});
    // Declare slideshow
    var jssor_slider1 = new $JssorSlider$('slider1_container_'+instanceId, options);
    
    // Responsive code
    if (settings.mobile_options == 'desktop_resize') {
    	ScaleSlider = function() {
			var bodyWidth = $(window).width();
			var resizeWidth = 0;
			if (settings.desktop_resize_greater_than_image) {
				resizeWidth = bodyWidth;
			} else {
				resizeWidth = Math.min(bodyWidth, parseInt(width));
			}
			if (resizeWidth) {
				jssor_slider1.$ScaleWidth(resizeWidth);
			} else {
				window.setTimeout(ScaleSlider, 30);
			}
		}
		ScaleSlider();
		if (!navigator.userAgent.match(/(iPhone|iPod|iPad|BlackBerry|IEMobile)/)) {
			$(window).bind('resize', ScaleSlider);
		}
    }
};

var waitForFinalEvent = (function() {
	var timers = {};
	return function(callback, ms, uniqueId) {
		if (timers[uniqueId]) {
			clearTimeout(timers[uniqueId]);
		}
		timers[uniqueId] = setTimeout(callback, ms);
	};
})();

zenario_slideshow_2.getTransition = function(fx) {
	switch(fx) {
		case '_fade':
			return [{$Duration:1200,$Opacity:2}];
		case '_slide_right':
			return [{$Duration:600,$FlyDirection:1,$Easing:$JssorEasing$.$EaseInQuad}];
		case '_switch':
			return [{$Duration:1400,$Zoom:1.5,$FlyDirection:1,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInSine},$ScaleHorizontal:0.25,$Opacity:2,$ZIndex:-10,$Brother:{$Duration:1400,$Zoom:1.5,$FlyDirection:2,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInSine},$ScaleHorizontal:0.25,$Opacity:2,$ZIndex:-10}}];
		case '_doors':
			return [{$Duration:1500,$Cols:2,$FlyDirection:1,$ChessMode:{$Column:3},$Easing:{$Left:$JssorEasing$.$EaseInOutCubic},$ScaleHorizontal:0.5,$Opacity:2,$Brother:{$Duration:1500,$Opacity:2}}];
		case '_rotate_up':
			return [{$Duration:1200,$Rotate:-0.1,$FlyDirection:5,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$ScaleHorizontal:0.25,$ScaleVertical:0.5,$Opacity:2,$Brother:{$Duration:1200,$Rotate:0.1,$FlyDirection:10,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$ScaleHorizontal:0.1,$ScaleVertical:0.7,$Opacity:2}}];
		case '_vertical_split_slide':
			return [{$Duration:1600,$Cols:2,$FlyDirection:8,$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Brother:{$Duration:1600,$Cols:2,$FlyDirection:4,$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}}];
		case '_bars_replace':
			return [{$Duration:1200,$Delay:40,$Cols:6,$FlyDirection:1,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:{$Left:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$ZIndex:-10,$Brother:{$Duration:1200,$Delay:40,$Cols:6,$FlyDirection:1,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$ZIndex:-10,$Shift:-100}}];
		case '_squares_wind':
			return [{$Duration:1800,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$FlyDirection:5,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$ScaleHorizontal:1,$ScaleVertical:0.2,$Round:{$Top:1.3}}];
		case '_squares_expand_random':
			return [{$Duration:1000,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$Easing:$JssorEasing$.$EaseInQuad}];
		case '_bounce_down':
			return [{$Duration:1000,$FlyDirection:4,$Easing:$JssorEasing$.$EaseInBounce}];
	};
};