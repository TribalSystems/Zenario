zenarioT.doEval = function(c, lib, tuixObject, item, id, button, column, field, section, tab, tuix, slotName) {
	if (c.search(/^\s*function/) === 0) {
		return eval("(" + c + ")");
	} else {
		return eval(c);
	}
};
