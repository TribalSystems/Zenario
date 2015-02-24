/*
 * Copyright (c) 2015, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
(function(
	zenario, zenario_revealable_panel,
	undefined) {


//Remove the "no javascript" class name, if the Visitor's browser can run JavaScript
zenario_revealable_panel.js = function() {
	$('.no_js').removeClass('no_js');
}

//Hide the current panel, if any are open
//Then show a specific panel, if requested
zenario_revealable_panel.hideShow = function(elOrContainerId, fx, speed, i, click) {
	var containerId = zenario.getContainerIdFromEl(elOrContainerId);
	
	//Stop any animations/timeouts
	zenario_revealable_panel.outdateTimeouts(containerId);
	$('#' + containerId + ' .panel').stop(true, true);
	
	var hides = $('#' + containerId + ' .panel').filter('.panel_open');
	var show;
	
	//Get the tab that was clicked on
	if (i !== undefined) {
		show = $('#' + containerId + '__panel' + i);
		
		//If this tab is already open and is clicked on again, close it
		if (click && show.hasClass('panel_open')) {
			show = false;
		
		//Otherwise hide every other tab, except for this one
		} else {
			hides = hides.not(show);
		}
	}
	
	//Hide the currently open panel
	if (fx == 'slide') {
		hides.slideUp(speed);
	
	} else if (fx == 'slide_and_scroll') {
		hides.slideUp(speed);
	
	} else if (fx == 'fade') {
		hides.fadeOut(speed);
	
	} else {
		hides.css('display', 'none');
	}
	
	hides.removeClass('panel_open');
	$('#' + containerId + ' .panel_tab').removeClass('panel_open');
	
	
	//Show the requested panel
	if (show) {
		if (fx == 'slide') {
			show.slideDown(speed);
		
		} else if (fx == 'slide_and_scroll') {
			show.slideDown(speed, function() {
				zenario.scrollToSlotTop(containerId);
			});
		
		} else if (fx == 'fade') {
			show.fadeIn(speed);
		
		} else {
			show.css('display', 'block');
		}
		
		show.addClass('panel_open');
		$('#' + containerId + '__tab' + i).addClass('panel_open');
	}
}

//If the Visitor clicks on a tab/panel, show it
zenario_revealable_panel.click = function(el, fx, speed, i) {
	zenario_revealable_panel.hideShow(el, fx, speed, i, true);
	return false;
}

//If the Visitor moves their mouse over a tab/panel, show it
zenario_revealable_panel.over = function(el, fx, speed, i) {
	zenario_revealable_panel.hideShow(el, fx, speed, i);
}

//If the Visitor moves their mouse away from a panel, set a timeout to close it
zenario_revealable_panel.out = function(el, fx, speed, i) {
	var containerId = zenario.getContainerIdFromEl(el);
	
	var num = zenario_revealable_panel.outdateTimeouts(containerId);
	
	setTimeout(
		function() {
			zenario_revealable_panel.out2(
				containerId, fx, speed, num)
		}, speed * 2);
}

//Close the panel on a timeout
zenario_revealable_panel.out2 = function(containerId, fx, speed, num) {
	//Check to see if this timeout has been outdated
	if (num == zenario_revealable_panel[containerId + '__out']) {
		zenario_revealable_panel.hideShow(containerId, fx, speed);
	}
}

//Outdate any existing timeouts, and return a new timeout number
zenario_revealable_panel.outdateTimeouts = function(containerId) {
	return zenario_revealable_panel[containerId + '__out'] = zenario.ifNull(zenario_revealable_panel[containerId + '__out'], 1, 1) + 1;
}



})(
	zenario, zenario_revealable_panel);