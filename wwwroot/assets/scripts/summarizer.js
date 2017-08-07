var Summarizer = Class.extend({
	element: null,

	// this object encapsulates the behaviors necessary to handle the
	// showing and hiding of information in summarized tables.  it used
	// to be a jQuery plugin; this is the pure-JS port of it.  it
	// requires the class.extend npm module.

	init: function(element) {
		this.element = !element ? this.getElement() : element;

		if (this.element.tagName &&
			this.element.tagName === "TABLE" &&
			this.element.matches(".summarized")
		) {

			// now that we know the element is a table and that it's
			// summarized, we can attach our behaviors to it.

			var clickers = this.element.querySelectorAll(".summary *:first-child a");

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

		// if this row is faded, then we want to un-fade it.  we could
		// probably do this in CSS, but we might as well handle it here
		// to keep the DOM pretty.

		if (tbody.classList.contains("faded") && !tbody.classList.contains("clicked")) {
			this.toggleClass(tbody, "faded");
		}

		// now, we'll toggle the clicked class which'll open or close our
		// summary.  following that, we want to add (or remove) faded classes
		// from the rest of our table.

		this.toggleClass(tbody, "clicked");
		this.fadeNotClicked();

		// and, since we've handled this event, we'll stop it from getting
		// dealt with elsewhere.

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
	},

	fadeNotClicked: function() {

		// here, if we can find a clicked element, then we fade the other
		// ones.  but, if we can't find any clicked elements, we actually un-
		// fade all of them.

		var clicked = this.element.querySelector("tbody.clicked");

		var bodies = clicked
			? this.element.querySelectorAll("tbody:not(.clicked)")
			: this.element.querySelectorAll("tbody");

		for(var i=0; i < bodies.length; i++) {
			if (clicked) {
				bodies[i].classList.add("faded");
			} else {
				bodies[i].classList.remove("faded");
			}
		}
	}
});
