$(window).scroll(function(){
	if ($(window).scrollTop() >= 36) {
       	$('.Grid_Header').addClass('fixed_header');
    } else {
       	$('.Grid_Header').removeClass('fixed_header');
    }
    
});