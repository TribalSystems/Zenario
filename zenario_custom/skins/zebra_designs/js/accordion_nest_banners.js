zOnLoad(function() {
    
  	var allPanels = $('.nest_accordion .zenario_banner .banner_text').hide();
  	var allPanelsContent = $('.nest_accordion .zenario_banner .banner_content');
  	
    
  $('.nest_accordion .zenario_banner .banner_title').click(function() {
  
  	var thisParent = $( this ).parent().parent().parent().parent().parent();
  	
  	if (thisParent.hasClass("active")) {
  		$( this ).next( ".banner_text").slideUp("slow"); 
  		thisParent.removeClass("active");
  	} else {
  		$('.nest_accordion .zenario_banner').removeClass("active");
    	allPanels.slideUp("slow");
    	$( this ).next( ".banner_text").slideDown("slow"); 
    	thisParent.addClass("active");
    }
    
    return false;
  });

});