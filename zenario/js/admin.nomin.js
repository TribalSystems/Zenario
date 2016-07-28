zenarioA.eval = function(c, tuixObject, item, id) {
	var ev;
	c += "";
	
	if (c.search(/^\s*function/) === 0) {
		ev = eval("(" + c + ")");
	} else {
		ev = eval(c);
	}
	
	if (typeof ev == "function") {
		ev = ev(tuixObject, item, id);
	}
	
	return zenario.engToBoolean(ev);
};
