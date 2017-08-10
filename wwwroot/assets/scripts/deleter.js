var Deleter = Class.extend({
	init: function(element) {

		// instead of adding a bunch of listeners on each individual
		// deleter, we expect to watch for their events on some sort of
		// containing element.  if we did not get that element as a
		// parameter, we'll default to the body.

		if (!element) {
			element = document.getElementsByTagName("body")[0];
		}

		element.addEventListener("click", this.deleterClicked.bind(this));
	},

	deleterClicked: function(event) {

		// in some cases, it's not the target of our click event that is
		// a deleter, but rather its immediate parent.  if neither are, then
		// we let the browser take over and don't do anything here.

		var elements = [event.target, event.target.parentNode];

		for (var i = 0; i < elements.length; i++) {
			if (elements[i].classList.contains("deleter")) {
				var element = elements[i];

				// if we find a deleter class, then we want to either
				// confirm that we're going to delete or actually delete
				// based on whether or not this one has already been
				// confirmed.

				var handled = !element.classList.contains("confirmed")
					? this.doConfirm(element)
					: this.doDelete(element);

				if (handled) {
					event.stopPropagation();
					event.preventDefault();
					return false;
				}
			}
		}
	},

	doConfirm: function(deleter) {

		// doing our confirmation is easy:  we just add the confirmed
		// class to our deleter.

		deleter.classList.add("confirmed");
		return true;
	},

	doDelete: function(deleter) {

		// for the purposes of the moment, once we've confirmed, we just
		// want to let the browser take over from here.  we'll return
		// false and that'll let that happen.

		return false;
	},

	getHref: function(deleter) {

		// most of the time, our deleter elements are links, so they have
		// an href attribute.  but sometimes, they'll be other elements,
		// and in those cases, we look for a data-href attribute instead.

		var attributes = ["href", "data-href"];
		for (var i=0; i < attributes.length; i++) {
			if (deleter.hasAttribute(attributes[i])) {
				return deleter.getAttribute(attributes[i]);
			}
		}

		// if we didn't return in the loop, then we return an empty string
		// here.  the calling scope should know what to do with that.

		return "";
	}
});
