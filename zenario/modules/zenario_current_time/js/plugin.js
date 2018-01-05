

(function(zenario, zenario_current_time) {
	'use strict';
	
	zenario_current_time.clocks = {};

	zenario_current_time.setClock = function(containerId, AJAXLink, timeFormat, timezone) {
		
		zenario.ajax(AJAXLink, {timezone: timezone}).after(function(data) {
			if (data && (data = data.split('~'))) {
				
				var refreshTime = 1000,
					$clock = $('#' + containerId + '__timer'),
					timeAtStart = new Date(),
					serverTimeHoursOffset = 1*data[0] - timeAtStart.getHours(),
					serverTimeMinsOffset = 1*data[1] - timeAtStart.getMinutes(),
					serverTimeSecsOffset = 1*data[2] - timeAtStart.getSeconds();
				
				timeFormat = timeFormat || '%H:%i:%s';
				
				if (zenario_current_time.clocks[containerId]) {
					clearInterval(zenario_current_time.clocks[containerId]);
				}
				
				zenario_current_time.clocks[containerId] =
					setInterval(function() {
						
						var currentTime = new Date();
						currentTime = 1*currentTime + 1000 * (serverTimeSecsOffset + 60 * (serverTimeMinsOffset + 60 * serverTimeHoursOffset));
						currentTime = new Date(currentTime);
						
						
						var hours = currentTime.getHours(),
							minutes = currentTime.getMinutes(),
							seconds = currentTime.getSeconds(),
							isPM = hours > 11,
							hours12 = (hours % 12) || 12,
							time = timeFormat
								.replace('%k', hours)
								.replace('%l', hours12)
								.replace('%H', zenario.rightHandedSubStr('0' + hours, 2))
								.replace('%h', zenario.rightHandedSubStr('0' + hours12, 2))
								.replace('%i', zenario.rightHandedSubStr('0' + minutes, 2))
								.replace('%s', zenario.rightHandedSubStr('0' + seconds, 2))
								.replace('%p', isPM? 'PM' : 'AM');
						
						$clock.text(time);
						
						
					}, refreshTime);
			}
		});
	};

})(zenario, zenario_current_time);