zenario_menu_vertical.toggleOpenClosed = function(ajaxLink) {
	post = {action: 'toggleOpenClosed'};
    result = {};

    zenario.ajax(ajaxLink, post).after(function(result) {
        result = JSON.parse(result);
        
        //Update the button label
        document.querySelector('#' + result.containerId + '_open_close_toggle span').innerText = result.phrase;

        //Set the correct class on the menu nodes
        menuEl = $('#' + result.containerId + '_menu_inner');
        buttonEl = $('#' + result.containerId + '_open_close_toggle');

        if (result.current_menu_state == 'open') {
            menuEl.slideDown();
        } else {
            menuEl.slideUp();
        }

        buttonEl.removeClass(result.previous_menu_state);
        buttonEl.addClass(result.current_menu_state);
    });
};