var Shadowlab = new Vue({
	data: vueData,
	el: "#shadowlab",

	// since the <title> tag is outside of the div#shadowlab element,
	// we have to alter it after the Vue object is mounted by hand as
	// follows.

	mounted: function() {
		document.title = this.title + " | Shadowlab";

		// also, if we have table bodies and they have descriptions, we want
		// to quickly convert new line characters to <br> tags.  we have a
		// function below that'll do the work, so here we just see if it's
		// necessary and, if so, we loop over our table bodies and call that
		// function.

		var withBodies = this.table && this.table.bodies && this.table.bodies.length > 0;
		var withDescriptions = withBodies && this.table.bodies[0].description.description;

		if (withDescriptions) {
			for (var i = 0; i < this.table.bodies.length; i++) {
				this.table.bodies[i].description.description = nl2br(this.table.bodies[i].description.description);
			}
		}
	},

	filters: {
		capitalize: function(str) {

			// source: http://locutus.io/php/strings/ucwords/

			return (str + '').replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function($1) {
				return $1.toUpperCase()
			})
		},

		nl2br: function(str) {
			return nl2br(str);
		},

		makeUpdateLink: function(id) {
			var hrefParts = window.location.href.split("/");

			// we could be creating an update link from almost any of our
			// pages, so we want to search through the parts of our current
			// href for any of our application actions.  when we identify
			// one of them, we know where to stop our re-joining action for
			// our new href.

			var endpoint = array_search(["create", "read", "update", "delete"], hrefParts);

			// now, if we found one of our action verbs during our search, we
			// want to join the href parts back together again up to but not
			// including that verb (hence the -1).  if we couldn't find one
			// of our verbs, we just use the current href as the base of our
			// update link.

			var href = endpoint !== false
				? join("/", hrefParts, endpoint - 1)
				: window.location.href;

			return href + '/update/' + id;
		},

		makeCollectionLink: function(endpoint) {
			var hrefParts = window.location.href.split("/");

			// here, we want to stop the re-building of our href at the
			// specified endpoint.  remember: array_search() below uses an
			// Array as the first argument, so we cram our endpoint
			// parameter into one as we send it there.

			endpoint = array_search([endpoint], hrefParts);
			return join("/", hrefParts, endpoint);
		}
	}
});

document.addEventListener("DOMContentLoaded", function() {
	if (document.getElementsByClassName("searchbar").length > 0) {
		new Searchbar();
	}

	if (document.getElementsByClassName("summarized").length > 0) {
		new Summarizer();
	}

	tinymce.init({
		"selector": "textarea#description",
		"toolbar": "bold italic | superscript subscript | bullist numlist | outdent indent | undo redo",
		"plugins": "lists",
		"branding": false,
		"menubar": false
	});

	new Focuser();
});

document.addEventListener("click", function(event) {
	console.log(event.target);
});

/*
 * Additional/Helper functions
 * These functions are used above to assist in filters, for example.
 * Usually, they're mock-ups of PHP-like functions for our convenience.
 */

function nl2br(str) {

	// source: http://locutus.io/php/strings/nl2br/

	if (typeof str === 'undefined' || str === null) {
		return ''
	}

	// altered source: removed switch between XHTML and HTML
	// version of the break tag.

	return (str + '').replace(/(\r\n|\n\r|\r|\n)/g, '<br>' + '$1')
}

function join(glue, pieces, limit) {
	var joined = '';

	if (!limit) {
		limit = 9999;
	}

	// as long as we have more than one piece, we'll reverse our
	// array and pop items off of it to create our joined string.
	// so, if our array is [A, B, C], the reversal is, obviously,
	// [C, B, A].  then, we start our string by popping the A off.
	// then, we pop the B and the C off and prefixing them with
	// the glue.

	if (pieces.length > 0) {
		pieces = pieces.reverse();
		joined = pieces.pop();

		var count = 0;
		while (pieces.length > 0 && count++ < limit) {
			joined += glue + pieces.pop();
		}
	}

	return joined;
}

function array_search(needles, haystack) {

	// like the PHP function of the same name, we want to return the
	// index of our first needle within the haystack.  so we loop over
	// the needles and compare them against the haystack array.

	for (var i = 0; i < needles.length; i++) {
		for (var j = 0; j < haystack.length; j++) {
			if (haystack[j] === needles[i]) {
				return j;
			}
		}
	}

	return false
}
