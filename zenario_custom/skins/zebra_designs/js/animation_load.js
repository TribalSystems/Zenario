zOnLoad(function() {
	$('slot_slideshow .slide_inner').css( "opacity", "1" );
	
	var sels = [
		'.slot_slideshow .slide_1 .banner_title',
		'.slot_slideshow .slide_1 .banner_text',

		'.banner_animation_parent .banner_wrap .banner_image',
		'.banner_animation_parent .banner_wrap .banner_title',
		'.banner_animation_parent .banner_wrap .banner_text h2',
		'.banner_animation_parent .banner_wrap .banner_text p',
		'.banner_animation_parent .banner_wrap .banner_more',

		'.nest_animation_parent .animated_child_1',
		'.nest_animation_parent .animated_child_2',
		'.nest_animation_parent .animated_child_3',

		'.slot_animation_parent .zenario_slot'
	];
	
	$(sels.join()).addClass('wow fadeInUp').css('animation-duration', '1.4s');

	
	new WOW({mobile: false}).init();
});