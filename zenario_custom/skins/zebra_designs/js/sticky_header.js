zOnLoad(function() {
	$(window).scroll(function(){
		if ($(window).scrollTop() >= 41) {
       		$('.Fixed').addClass('Fixed_Header');
    	} else {
       		$('.Fixed').removeClass('Fixed_Header');
    	}
	});
});