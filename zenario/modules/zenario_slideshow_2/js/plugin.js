zenario_slideshow_2.initiateSlideshow = function(AJAXLink, slotName, mobile_resize, fx, width, height, m_width, m_height, hover_to_pause, enable_swipe, 
	auto_play, slide_duration, arrow_buttons, navigation_style, slide_caption_transitions, instanceId, slides, mobileImages) 
{

	if (mobileImages) {
		var screenLimit = 720;
		var screenWidth = $(window).width();
		// Change the slideshow to mobile or main depending on screen size
		if (screenWidth < screenLimit) {
			zenario_slideshow_2.mobileMode = 1;
			width = m_width;
			height = m_height;
		}
		$(window).resize(function() {
			waitForFinalEvent(function() {
				if (($(window).width() < screenLimit) && (!zenario_slideshow_2.mobileMode)) {
					zenario_slideshow_2.refreshPluginSlot(slotName);
					zenario_slideshow_2.mobileMode = 1;
				} else if(($(window).width() >= screenLimit) && zenario_slideshow_2.mobileMode) {
					zenario_slideshow_2.refreshPluginSlot(slotName);
					zenario_slideshow_2.mobileMode = 0;
				}
			}, 500, instanceId);
		});
	}
	
	// Create slide html
	var cursorStyle = '';
	if (enable_swipe) {
		cursorStyle = 'move';
	} else {
		cursorStyle = 'default';
	}
	var html = '';
	html += '<div id="slider1_container_'+instanceId+'"\
				class="slideshow_container" style="visibility: hidden; position: relative; top: 0px; left: 0px; width:'+width+'px; height:'+height+'px;">\
				<div u="loading" style="position: absolute; top: 0px; left: 0px;"></div>\
				<div u="slides" id="slides_container_'+instanceId+'" class="" style="cursor: '+cursorStyle+'; position: absolute; overflow: hidden; left: 0px; top: 0px; width:'+width+'px; height:'+height+'px;">';
	var count = 1;
	$.each(slides, function(key, slide) {
		var altTag = '';
		if (mobileImages && (screenWidth < screenLimit)) {
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
		if (slide.rollover_image_src && (!mobileImages || (screenWidth >= screenLimit))) {
			html += '\
				onmouseover="\
					if (\''+slide.rollover_image_src+'\') {\
						this.src=\''+slide.rollover_image_src+'\'\
					}"\
				onmouseout="this.src=\''+slide.image_src+'\'"';
		}
		html += '/>\
			</a>';
		if (!mobileImages || (screenWidth >= screenLimit)) {
			if (navigation_style == 'thumbnail_navigator' && (!mobileImages || (screenWidth >= screenLimit))) {
				html += '<div u="thumb">'+slide.tab_name+'</div>';
			}
			if (slide.slide_title || slide.slide_extra_html) {
				html += '<div class="content_container"';
				if (parseInt(slide.use_title_transition) || parseInt(slide.use_extra_html_transition)) {
					html += 'style="bottom: 0; height: auto; left: 0; overflow: hidden; padding: 2%; position: absolute; width: 60%;"';
				}
				html += '>';
				if (slide.slide_title) {
					if (parseInt(slide.use_title_transition)) {
						html += '<div class="slide_title_transition" u="caption" t="slide_title_transition_'+slide.id+'">';
					} else {
						html += '<div class="slide_title">';
					}
					html += slide.slide_title+'</div>';
				}
				if (slide.slide_extra_html) {
					if (parseInt(slide.use_extra_html_transition)) {
						html += '<div class="slide_extra_html_transition" u="caption" t="slide_extra_html_transition_'+slide.id+'">'
					} else {
						html += '<div class="slide_extra_html">';
					}
					html += slide.slide_extra_html+'</div>';
				}
				html += '</div>';
			}
			
		}
		html += '</div></div>';
		count++;
	});
	html += '</div>'
	if (arrow_buttons && (!mobileImages || (screenWidth >= screenLimit))) {
		html += '<span u="arrowleft" class="arrowl"></span>\
				 <span u="arrowright" class="arrowr"></span>';
	}
	if (navigation_style == 'bullet_navigator' && (!mobileImages || (screenWidth >= screenLimit))) {
		html += '<div u="navigator" class="bullet">\
					 <div u="prototype"></div>\
				 </div>';
	} else if (navigation_style == 'thumbnail_navigator' && (!mobileImages || (screenWidth >= screenLimit))) {
		html += '<div u="thumbnavigator" class="tab">\
					<div u="slides" style="cursor: move;">\
						<div u="prototype">\
							<thumbnailtemplate></thumbnailtemplate>\
						</div>\
					</div>\
				</div>';
	}
	html += '</div>';
	$('#slideshow_outer_'+instanceId).html(html);
	
	// Get the transition to use
	var slideshowTransition = zenario_slideshow_2.getTransition(fx);
    var options = { 
    	$AutoPlay: auto_play,
    	$FillMode: 4,
    	$AutoPlayInterval: parseInt(slide_duration),
    	$SlideDuration: 500,
    	$PauseOnHover: hover_to_pause,
    	$ArrowKeyNavigation: false,
    	$SlideSpacing: 0,
    	$PlayOrientation: 1,
    	$DragOrientation: 1,
    	$SlideshowOptions: {
    		$Class: $JssorSlideshowRunner$,
    		$Transitions: slideshowTransition
    	}
    	
    };
    
    if (!enable_swipe) {
    	options.$DragOrientation = 0;
    }
    
    if (arrow_buttons){
    	options.$ArrowNavigatorOptions = {
			$Class: $JssorArrowNavigator$,
			$ChanceToShow: 2
		}
    }
    if (navigation_style == 'bullet_navigator') {
    	options.$BulletNavigatorOptions = {
    		$Class: $JssorBulletNavigator$,
            $ChanceToShow: 2
    	};
    }
    if (navigation_style == 'thumbnail_navigator') {
    	options.$ThumbnailNavigatorOptions = {
    		$Class: $JssorThumbnailNavigator$,
            $ChanceToShow: 2
    	};
    }
    
    var _CaptionTransitions = {};
    for (slide_id in slide_caption_transitions) {
    	
    	if (slide_caption_transitions[slide_id].title) {
    		_CaptionTransitions['slide_title_transition_'+ slide_id] = eval('('+slide_caption_transitions[slide_id].title+')');
    	}
    	if (slide_caption_transitions[slide_id].extra_html) {
    		_CaptionTransitions['slide_extra_html_transition_'+ slide_id] = eval('('+slide_caption_transitions[slide_id].extra_html+')');
    	}
    }
    
    options.$CaptionSliderOptions = {
    	$Class: $JssorCaptionSlider$,
		$CaptionTransitions: _CaptionTransitions,
		$PlayInMode: 1,
		$PlayOutMode: 3
    };
    $('#slider1_container_'+instanceId).css({'visibility': 'visible'});
    var jssor_slider1 = new $JssorSlider$('slider1_container_'+instanceId, options);
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