var module = { exports: null };

(function(){
  var initializing = false, fnTest = /xyz/.test(function(){xyz;}) ? /\b_super\b/ : /.*/;

  // The base Class implementation (does nothing)
  this.Class = function(){};

  // Create a new Class that inherits from this class
  Class.extend = function(className, prop) {
    if(prop == undefined) {
        prop = className;
       className = "Class";
    }

    var _super = this.prototype;

    // Instantiate a base class (but only create the instance,
    // don't run the init constructor)
    initializing = true;
    var prototype = new this();
    initializing = false;

    // Copy the properties over onto the new prototype
    for (var name in prop) {
      // Check if we're overwriting an existing function
      prototype[name] = typeof prop[name] == "function" &&
        typeof _super[name] == "function" && fnTest.test(prop[name]) ?
        (function(name, fn){
          return function() {
            var tmp = this._super;

            // Add a new ._super() method that is the same method
            // but on the super-class
            this._super = _super[name];

            // The method only need to be bound temporarily, so we
            // remove it when we're done executing
            var ret = fn.apply(this, arguments);
            this._super = tmp;

            return ret;
          };
        })(name, prop[name]) :
        prop[name];
    }

    // The dummy class constructor
    function Class() {
      // All construction is actually done in the init method
      if ( !initializing && this.init )
        this.init.apply(this, arguments);
    }

    // Populate our constructed prototype object
    Class.prototype = prototype;

    // Enforce the constructor to be what we expect
    var func = new Function(
        "return function " + className + "(){ }"
    )();
    Class.prototype.constructor = func;

    // And make this class extendable
    Class.extend = arguments.callee;

    return Class;
  };

  //I only added this line
  module.exports = Class;
})();


// the class.extend npm module must be included prior to this
// if you use the included minimized file, we've bundled that
// module together with this object for you.

var Searchbar = Class.extend({
	searchbar: null,
	searchable: null,
	hiddenClass: "searchable-row-hidden",

	init: function() {
		this.searchbar = document.querySelector(".searchbar");
		this.searchable = document.querySelector(".searchable");

		// to have a searchbar, we need both the searchbar element and
		// the searchable data set.  without those, we can simply quit
		// because, clearly, this page doesn't need this functionality.

		if (!this.searchbar || !this.searchable) {
			return;
		}

		// now that we know we have those, we can parse the searchable
		// data set to prepare it for use.  it's possible that this method
		// returns false when there are no rows within the set to work
		// with.

		if (!this.parseSearchable()) {
			return;
		}

		// finally, our searchable data set is ready to go.  now, we can
		// hook up events to our searchbar.

		var elements = this.getSearchbarElements();
		for (var i = 0; i < elements.length; ++i) {
			this.processSearchbarElement(elements[i]);
		}
	},

	parseSearchable: function() {
		var rows = this.getSearchableRows();

		// if we don't have rows, then we can quit.

		if (rows.length === 0) {
			return false;
		}

		// otherwise, for the children of our rows -- which are likely
		// table cells, but could be something else -- we want to process
		// them individually with the method below.

		for (var i = 0; i < rows.length; ++i) {
			var cells = rows[i].querySelectorAll("[data-searchbar-value]");
			for (var j = 0; j < cells.length; ++j) {
				this.processCell(cells[j], rows[i]);
			}
		}

		return true;
	},

	getSearchableRows: function() {

		// for maximum flexibility, we want to make sure that a developer
		// using our searchbar can specify what should be considered a
		// searchable row.  but, if they don't and if the searchable DOM
		// element is a table, then we'll just use the rows in its table
		// body by default.

		var rows = this.searchable.querySelectorAll(".searchable-row");
		if (rows.length === 0 && this.searchable.tagName === "TABLE") {
			rows = this.searchable.querySelectorAll("tbody tr");
		}

		return rows;
	},

	processCell: function(cell, row) {

		// to process a cell, we want to add HTML5 data attributes that
		// correspond to information extracted from the cell itself to
		// the specified row.  this is because our search method looks
		// at the rows when matching entries against data.

		var attr = "data-" + cell.getAttribute("headers");
		var value = cell.getAttribute("data-searchbar-value");
		row.setAttribute(attr, value);

		if (cell.getAttribute("data-searchbar-value-list") === "1") {
			row.setAttribute(attr + "-list", 1);
		}
	},

	getSearchbarElements: function() {

		// our searchbar uses these HTML elements at the moment.  if we
		// ad more, we'll need to update this list.

		return this.searchbar.querySelectorAll("input, select, button");
	},

	processSearchbarElement: function(element) {
		var event = "", wait = 0;

		// the event we want to use is based on the type of element that
		// we're working with.  most of our events don't need to be
		// debounced, but if we run into one that does, we can set our
		// wait variable to something other than zero.

		switch (element.type) {
			case "text":
				event = "keyup";
				wait = 150;
				break;

			case "select-one":
				event = "change";
				break;

			case "checkbox":
				event = "click";
				break;

			case "reset":
				event = "click";
				break;
		}

		if (event.length > 0) {

			// if we identified an event to listen for, we'll now identify
			// which function to call.  if we're not messing with the reset
			// element, we search; otherwise we reset.

			var func = element.type !== "reset"
				? this.debounce(this.search, wait).bind(this)
				: this.reset.bind(this);

			element.addEventListener(event, func);
		}
	},

	search: function() {
		var beforeSearch = new Event("searchbar:beforeSearch");
		var cancelled = !this.searchbar.dispatchEvent(beforeSearch);
		if (cancelled) {
			return;
		}

		// assuming something else didn't cancel our search, the first
		// thing we do is reset our table.  that way, we know that we
		// start with the entire thing visible and we'll find the ones
		// that need to be hidden below.

		this.resetSearchable();
		var rows = this.getSearchableRows();
		for (var i = 0; i < rows.length; ++i) {
			var row = rows[i], show = true;

			// for each row, we need to compare the information in the
			// HTML5 data attributes added by the parseSearchable() method
			// above against the values in our searchbar elements.

			var elements = this.getSearchbarElements();
			for (var j = 0; j < elements.length; j++) {
				if (!show) {

					// we use AND logic here.  the first thing that would
					// hide our row means the row is hidden and we can
					// skip all other criterion.

					continue;
				}

				var element = elements[j];
				var value = this.getValue(element);
				var attr = this.getAttr(element);

				// if we don't have an attribute or value or if the value
				// we do have indicates that we should be showing all of
				// our rows, we can simply move on; this criteria is,
				// effectively, always met.

				if (!attr || !value || value === "all") {
					continue;
				}

				var criterion = element.type !== "text"
					? this.nonTextSearch(row, attr, value)
					: this.textSearch(row, attr, value);

				show = show && criterion;
			}

			if (!show) {

				// finally, if this row should not be shown, then we add
				// the hidden class to it.

				row.classList.add(this.hiddenClass);
			}
		}

		this.searchbar.dispatchEvent(new Event("searchbar:afterSearch"));
	},

	getValue: function(element) {

		// text, selects, and checkboxes all work differently with
		// respect to what we consider our "value" for searching
		// purposes.  this switch statement mixes things up and
		// grabs only what we need during our search above to make
		// things happen.

		switch (element.type) {
			case "text":
				return element.value;

			case "select-one":
				return element.options[element.selectedIndex].value;

			case "checkbox":
				return element.checked;
		}

		return "";
	},

	getAttr: function(element) {

		// the HTML5 data elements that parseSearchable() creates
		// are data-[id] where the id is the value of our element's
		// ID attribute.

		var id = element.getAttribute("id");

		if (id) {

			// if we have an ID, then we need to remove everything
			// after the underscore, including the underscore.  we
			// expect our IDs for searching elements to be in the
			// form of [id]_[type], so this simply leaves us with
			// the id.

			id = id.replace(/_.*/, "");
		} else {
			return "";
		}

		// still here?  then we can construct the our data
		// attribute using the ID we're left with.

		return "data-" + id;
	},

	nonTextSearch: function(row, attr, value) {

		// if our row has a list flag for this attribute, then we
		// want to see if our attribute contains the value.  otherwise,
		// we check to see the attribute matches it.  with respect to
		// the matches() call here, if we assume our value is 1, then
		// we search for _1_.  this is to avoid invalid selector errors
		// within the javascript.  further, it helps distinguish between
		// _1_ and _11_ or _won_ and _wonder_, for example.

		return row.hasAttribute(attr + "-list")
			? row.matches("[" + attr + "*=_" + value + "_]")
			: row.getAttribute(attr) === value;
	},

	textSearch: function(row, attr, value) {

		// for text searches, we construct a regex from the value of
		// our search field and see if this row matches it.  we put
		// this all inside a try/catch block because it's possible to
		// create a value that can't be a regex, and then an exception
		// is thrown.

		try {
			var pattern = new RegExp(value, "i");
			return row.getAttribute(attr).match(pattern);
		} catch (e) {

			// if we couldn't even make our pattern, then we'll assume
			// that whatever we have could not be matched and return
			// false.

			return false;
		}
	},

	reset: function() {
		var beforeReset = new Event("searchbar:reset");
		var cancelled = !this.searchbar.dispatchEvent(beforeReset);

		if (!cancelled) {

			// if no one cancelled our reset, then we just reset the
			// searchbar form and our searchable data set.

			this.searchbar.reset();
			this.resetSearchable();
		}

		return false;
	},

	resetSearchable: function() {
		var rows = this.getSearchableRows();

		// resetting our data set is simple:  we just remove the hidden
		// class from all rows.

		for (var i = 0; i < rows.length; ++i) {
			rows[i].classList.remove(this.hiddenClass);
		}
	},

	debounce: function(func, wait, immediate) {
		if (wait === 0) {
			return func;
		}

		// source: https://davidwalsh.name/javascript-debounce-function

		var timeout;
		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			var callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	}
});

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

var Shadowlab = new Vue({
	data: vueData,
	el: "#shadowlab",

	// since the <title> tag is outside of the div#shadowlab element,
	// we have to alter it after the Vue object is mounted by hand as
	// follows.

	mounted: function() {
		document.title = this.title + " | Shadowlab";
	},

	filters: {
		capitalize: function(str) {

			// source: http://locutus.io/php/strings/ucwords/

			return (str + '').replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function($1) {
				return $1.toUpperCase()
			})
		},

		nl2br: function(str) {

			// source: http://locutus.io/php/strings/nl2br/

			if (typeof str === 'undefined' || str === null) {
				return ''
			}

			// altered source: removed switch between XHTML and HTML
			// version of the break tag.

			return (str + '').replace(/(\r\n|\n\r|\r|\n)/g, '<br>' + '$1')
		},

		makeUpdateLink: function(id) {
			var hrefParts = window.location.href.split("/");

			// we could be creating an update link from almost any of our
			// pages, so we want to search through the parts of our current
			// href for any of our application actions.  when we identify
			// one of them, we know where to stop our re-joining action for
			// our new href.

			var endpoint = array_search(["create", "read", "update", "delete"], hrefParts);
			return join("/", hrefParts, endpoint-1) + '/update/' + id;
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

	new Focuser();
});

/*
 * Additional/Helper functions
 * These functions are used above to assist in filters, for example.
 * Usually, they're mock-ups of PHP-like functions for our convenience.
 */

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
		while(pieces.length > 0 && count++ < limit) {
			joined += glue + pieces.pop();
		}
	}

	return joined;
}

function array_search (needles, haystack) {

	// like the PHP function of the same name, we want to return the
	// index of our first needle within the haystack.  so we loop over
	// the needles and compare them against the haystack array.

	for (var i=0; i < needles.length; i++) {
		for (var j=0; j < haystack.length; j++) {
			if (haystack[j] === needles[i]) {
				return j;
			}
		}
	}

	return false
}
