/*
 * Copyright (c) 2018, Tribal Limited
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

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, get, engToBoolean, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	panelTypes, extraVar2, s$s
) {
	"use strict";

var 
	methods = methodsOf(
		panelTypes.calendar = extensionOf(panelTypes.base)
	);


methods.init = function() {
	var now = new Date();
	this.month = now.getMonth();
	this.year = now.getFullYear();
};

//Use this to add any requests you need to the AJAX URL used to call your panel
methods.returnAJAXRequests = function() {
	return {
		year: this.year,
		month: this.month
	};
};

//You should return the page size you wish to use, or false to disable pagination
methods.returnPageSize = function() {
	return false;
};

//Return whether you want searching/sorting/pagination to be done server-side.
//If you return true, sorting and pagination will be applied on the server.
//If you return false, your sortAndSearchItems() method will be called instead.
methods.returnDoSortingAndSearchingOnServer = function() {
	return false;
};

methods.returnSearchingEnabled = function() {
	return false;
};

methods.returnPanelTitle = function() {
	return this.tuix.title;
};

methods.showPanel = function($header, $panel, $footer) {
	$header.html('').show();
	$footer.html('').show();
	
	var 
		itemsDetails = this.tuix,
		items = this.tuix.items,
		itemsByDate = [],
		date_column = this.tuix.date_column,
		labelFormat = this.tuix.label_format_for_calendar,
		singleEvent = this.tuix.use_single_event;
	
	foreach (items as var key => var item) {
		item.label = zenarioO.applyMergeFields(labelFormat, false, key, true);
		var datetime = item[date_column];
		if (datetime) {
			var dateAndTime = datetime.split(' ');
			if (dateAndTime[0]) {
				if (!itemsByDate[dateAndTime[0]]) {
					itemsByDate[dateAndTime[0]] = [];
				}
				item['id'] = key;
				itemsByDate[dateAndTime[0]].push(item);
			}
		}
	}
	
	var 
		html = '',
		data = methods.getCalendar(this.year, this.month, itemsByDate);
	
	html = this.microTemplate('zenario_organizer_calendar', {weeks: data});
	
	$panel.html(html).show();
	
	this.updateEventsDetails();
};

methods.getCalendar = function(year, month, data) {
	var 
		first = new Date(year, month, 1),
		last = new Date(year, month+1, 1);
	    last = new Date(last - 1000 * 60 * 60 * 12);
	
	var 
		startDay = first.getDay(),
		endDate = last.getDate(),
		day = 1,
		date = 0,
		week = 0,
		started = false,
		finished = false,
		weeks = [];
	
	if (startDay == 0) {
		startDay = 7;
	}
	
	var formattedDate = year + '-';
	if (month.toString().length === 1) {
		formattedDate += '0';
	}
	formattedDate += (month + 1) + '-';
	
	while (!finished || day > 1 || week != 6) {
		if (!weeks[week]) {
			weeks[week] = {days: []};
		}
		if (day >= startDay) {
			started = true;
		}
		if (started && !finished) {
			++date;
			
			weeks[week].days[day] = {
				day: day,
				date: date
			};
			
			var currentFormattedDate = formattedDate;
			if (date.toString().length === 1) {
				currentFormattedDate += '0';
			}
			currentFormattedDate += date;
			
			
			
			if (data[currentFormattedDate]) {
				var arrayLength = data[currentFormattedDate].length;
				weeks[week].days[day].events = [];
				weeks[week].days[day].eventIds = [];
				for (var i = 0; i < arrayLength; i++) {
					weeks[week].days[day].events.push({
						id: data[currentFormattedDate][i].id,
						label: data[currentFormattedDate][i].label,
						css_class: data[currentFormattedDate][i].css_class
					});
					weeks[week].days[day].eventIds.push(data[currentFormattedDate][i].id);
				}
			}
			
		} else {
			weeks[week].days[day] = {
				day: day
			};
			
		}
		++day;
		if (day >= 8) {
			day = 1;
			++week;
		}
		if (date >= endDate) {
			finished = true;
		}
	}
	
	var month = new Array();
    month[0] = "January";
    month[1] = "February";
    month[2] = "March";
    month[3] = "April";
    month[4] = "May";
    month[5] = "June";
    month[6] = "July";
    month[7] = "August";
    month[8] = "September";
    month[9] = "October";
    month[10] = "November";
    month[11] = "December";
	
	weeks['month'] = month[last.getMonth()];
	weeks['year'] = last.getFullYear();
	
	return weeks;
};

methods.showButtons = function($buttons) {
	
	$buttons.html('').show();
};

methods.nextMonth = function() {
	++this.month;
	zenarioO.load();
};

methods.prevMonth = function() {
	--this.month;
	zenarioO.load();
};
/*
methods.eventClicked = function(id) {

	//var oldClassElements = document.getElementsByClassName('calendar-organizer-event');
	
	//for(i=0; i<oldClassElements.length; i++) {
	//   var otherElementsClassReset = document.getElementById(oldClassElements[i].id);
    //   otherElementsClassReset.className = "calendar-organizer-event";
	//}
	
    //var selectedEvent = document.getElementById(element.id);
    //selectedEvent.className = "calendar-organizer-event";
    //selectedEvent.className = selectedEvent.className + " calendar-event-selected";
    
    console.log(id);
    
	var curPage = window.location.href.indexOf("expiring_timers_report");
	
	if(curPage != -1) {
		console.log('true');
		document.getElementById("calendar-organizer-events-details-title").innerHTML = "Expiring user timers";
		
	}
	
	
};
*/






methods.selectDay = function(eventIds) {
	zenarioO.deselectAllItems();
	
	if (eventIds && eventIds.length) {
		foreach (eventIds as var i => var id) {
			this.selectItem(id);
		}
	}
	
	zenarioO.setButtons();
	zenarioO.setHash();
};


//This method should cause an item to be selected.
//It is called after your panel is drawn so you should update the state of your items
//on the page.
methods.selectItem = function(id) {
	methodsOf(panelTypes.base).selectItem.call(this, id);
	this.updateEventsDetails();
};

//This method should cause an item to be deselected
//It is called after your panel is drawn so you should update the state of your items
//on the page.
methods.deselectItem = function(id) {
	methodsOf(panelTypes.base).deselectItem.call(this, id);
	this.updateEventsDetails();
};


methods.updateEventsDetails = function() {
	
	var itemsSelected = false,
		id,
		$eventsDetails = $('#calendar-organizer-events-details-title'),
		html = '';
	
	foreach (this.selectedItems as id) {
		
		html += 'Item selected: ' + htmlspecialchars(id) + '<br/>';
		
		
		itemsSelected = true;
	}
	
	if (itemsSelected) {
		$eventsDetails.html(html);
	} else {
		$eventsDetails.html('No events selected');
	}

};







//	$(get('organizer_item_' + id)).removeClass('organizer_selected');

//This updates the checkbox for an item, if you are showing checkboxes next to items,
//and the "all items selected" checkbox, if it is on the page.
methods.updateItemCheckbox = function(id, checked) {
	
	//No checkboxes on the calendar, so we do nothing
};

//Return whether you want to enable inspection view
methods.returnInspectionViewEnabled = function() {
	return false;
};

//This method should open inspection view
methods.openInspectionView = function(id) {
	//...
};

//This method should close inspection view
methods.closeInspectionView = function(id) {
	//...
};







}, zenarioO.panelTypes);