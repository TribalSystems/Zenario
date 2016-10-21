zenarioA.doEval = function(c, tuixObject, item, id, tuix, button, column, field, section, tab) {
	if (c.search(/^\s*function/) === 0) {
		return eval("(" + c + ")");
	} else {
		return eval(c);
	}
};
