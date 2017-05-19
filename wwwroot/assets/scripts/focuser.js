var Focuser = Class.extend({
	init: function() {
		var element = document.querySelector(".focus-me");
		if (element) {
			element.focus();
		}
	}
});
