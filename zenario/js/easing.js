$.easing.zenarioLinearWithDelay = function(time, millisecondsSince, startValue, endValue, totalDuration) {
	if (time < 0.5) {
		return 0;
	} else {
		return 2 * time - 1;
	}
};

$.easing.zenarioLinearWithBigDelay = function(time, millisecondsSince, startValue, endValue, totalDuration) {
	if (time < 0.75) {
		return 0;
	} else {
		return 4 * time - 3;
	}
};

$.easing.zenarioLinearWithDelayAfterwards = function(time, millisecondsSince, startValue, endValue, totalDuration) {
	if (time > 0.5) {
		return 1;
	} else {
		return 2 * time;
	}
};

$.easing.zenarioOmmitEnd = function(time, millisecondsSince, startValue, endValue, totalDuration) {
	return time / 3;
};