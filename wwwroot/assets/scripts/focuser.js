var Focuser = Class.extend({
	init: function() {
		var element = document.querySelector("[autofocus]");
		if (element && element.length > 0) {
			element[0].focus();
			return;
		}

		// if we haven't left yet, then we'll check for a shadowlab form
		// and if we find it, we find it's first element and focus it.

		var form = document.getElementById("shadowlab-form");
		if (form) {
			var inputs = form.querySelector("input, select, textarea");
			if (inputs && inputs.length > 0) {
				inputs[0].focus();
			}
		}
	}
});
