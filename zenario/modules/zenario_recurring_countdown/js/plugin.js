zenario_recurring_countdown.counter = false;

zenario_recurring_countdown.initRecurringCountdown = function(containerId, count, message) {
	if (zenario_recurring_countdown.counter) {
		clearInterval(zenario_recurring_countdown.counter);
	}
	
	zenario_recurring_countdown.counter = setInterval(timer, 1000);
	
	$timer = $('#' + containerId + '_recurring_countdown');
	$header = $('#' + containerId + '_recurring_countdown_header');
	
	function timer() {
		count -= 1;
		if (count == -1) {
			clearInterval(zenario_recurring_countdown.counter);
			$header.hide();
			$timer
				.addClass('finnished')
				.removeClass('counting')
				.html(message);
			return;
		}
		
		var seconds = count % 60,
			minutes = Math.floor(count / 60),
			hours = Math.floor(minutes / 60);
		
		minutes %= 60;
		hours %= 60;
		
		seconds = pad(seconds);
		minutes = pad(minutes);
		hours = pad(hours);
		
		var time = hours + ':' + minutes + ':' + seconds;
		
		$timer.html(time);
	};
	function pad(number) {
		var s = number + '';
		if (s.length == 1) {
			s = 0 + s;
		}
		return s;
	}
};

