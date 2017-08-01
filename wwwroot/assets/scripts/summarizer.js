var Summarizer = Class.extend({
	element: null,

	// this object encapsulates the behaviors necessary to handle the
	// showing and hiding of information in summarized tables.  it used
	// to be a jQuery plugin; this is the pure-JS port of it.  it
	// requires the class.extend npm module.

	init: function(element) {
		if (!element) {
			element = this.getElement();
		}

		if (element.tagName &&
			element.tagName === "TABLE" &&
			element.matches(".summarized")
		) {

			// now that we know the element is a table and that it's
			// summarized, we can attach our behaviors to it.

			var clickers = element.querySelectorAll(".summary *:first-child a");
			for(var i=0; i < clickers.length; i++) {
				clickers[i].addEventListener("click", this.summaryClicked.bind(this));
			}
		}
	},

	getElement: function() {

		// to get our element, we simply look for a table that's summarized.
		// at the moment, we don't worry if there's more than one; that'll
		// be for the future if necessary.

		return document.querySelector("table.summarized");
	},

	summaryClicked: function(event) {

		// when one of our summaries is clicked, we want to get the
		// nearest <tbody> element and toggle it's clicked class.

		var tbody = this.getClosest(event.target, "tbody");
		this.toggleClass(tbody, "clicked");
		event.stopPropagation();
		event.preventDefault();
	},

	getClosest: function(element, target) {

		// to find the closest target match to our element, we can
		// use a do-while loop.  we'll see if element matches our
		// target and then when we find the match, we return it.

		do {
			if (element.matches(target)) {
				return element;
			}
		} while (element = element.parentNode);

		// otherwise, if we work our way all the way to the top of
		// our DOM tree, we'll just return null instead.

		return null;
	},

	toggleClass: function(element, className) {
		element.classList.toggle(className, !element.classList.contains(className));
	}
});
